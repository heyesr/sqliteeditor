<?php
    // ***************************************
    // * SQLiteEditor for PHP                *
    // *                                     *
    // * This is free code - it's  released  *
    // * to the public domain (ie the same   *
    // * license as SQLite itself). Do with  *
    // * it as you wish.                     *
    // *                                     *
    // * © Richard Heyes 2026                *
    // *                                     *
    // * https://www.rgraph.net/sqliteeditor *
    // ***************************************

    class SQLiteEditor
    {
        //
        // The ID of this instance
        //
        public $id;


        //
        // The path to the database file.
        //
        public $database_file;


        //
        // The database table that you want to use.
        //
        public $database_table;


        //
        // These are all of the  database table columns.
        //
        public $database_columns;
        
        //
        // These are all of the selected columns in the result set.
        //
        public $database_selected_columns;


        //
        // The connection reference to the database.
        //
        public $sqlite;


        //
        // The options that are givwen to the constructor that
        // determine the way that the editor behaves. 
        //
        public $options;


        //
        // This will hold a copy of the original options, after
        // they've been set by the user, but before they have
        // been touched by this library.
        //
        public $options_original;


        //
        // This is the unique ID for this SQLiteEditor instance
        //
        public static $counter = 1;
        
        
        //
        // Used to store the order by column once it has been
        // calculated.
        //
        public $ordering_column;
        
        
        
        //
        // Used to store the order by direction once it has been
        // calculated.
        //
        public $ordering_dir;
        
        
        
        //
        // The table structure
        //
        public $structure;


        //
        // The constructor
        //
        // @param $options array The options to configure the editor
        //
        public function __construct($options)
        {
            $this->options = $options;
        }








        //
        // Draws the editor
        //
        public function draw()
        {
            $options = $this->options;


            $this->database_file  = $options['filename'];
            $this->database_table = $options['table'];

            // Default options
            $this->options = [
                'filename'                          => null,
                'table'                             => null,
                'primary_key'                       => null,
                'sql_select'                        => "SELECT * FROM {table} WHERE {where} {order}",
                'sql_insert'                        => null, // This gets built based on the table structure
                'sql_delete'                        => "DELETE FROM {table} WHERE {primary_key} IN({ids}) LIMIT {COUnt}",
                'sql_update'                        => "UPDATE {table} SET {column} = {value} WHERE {primary_key} = {id}",
                'editable'                          => [],
                'editable_save_url'                 => null,
                'editable_types'                    => [],
                'editable_types_select_options'     => [],
                'editable_types_radio_options'      => [],
                'editable_types_checkbox_options'   => [],
                'editable_view_only'                => false,
                'editable_event'                    => 'dblclick',
                'editable_callback'                 => null,
                'columns_names'                     => [],
                'columns_widths'                    => [],
                'columns_tooltips'                  => null,
                'columns_callbacks'                 => [],
                'columns_escape'                    => [],
                'paging_current'                    => 1,
                'paging_perpage'                    => 25,
                'paging_numpages'                   => null,
                'paging_info_colspan'               => 999,
                'search'                            => true,
                'search_columns'                    => null,
                'ordering_column'                   => null,
                'ordering_dir'                      => null,
                'ordering_include'                  => null,
                'ordering_exclude'                  => null,
                'ordering_changeable'               => true,
                'ordering_case'                     => false,
                'style'                             => [],
                'actions'                           => [],
                'checkboxes'                        => false,
                'checkboxes_radio'                  => false
            ];

            // Add the user specified options
            foreach ($options as $k => $v) {
                $this->options[$k] = $v;
            }
            
            // Remove the "on" from the start of the event name in
            // case the user has given "onclick" or "ondblclick"
            $this->options['editable_event'] = preg_replace('/^on/', '', $this->options['editable_event']);

            // Now make a copy of the starting options to the
            // options_original array.
            foreach ($this->options as $k => $v) {
                $this->options_original[$k] = $v;
            }




            //
            // The id is used as an anchor to this sqliteeditor
            //
            $this->id = 'editor' . SQLiteEditor::$counter++;


            // If the order_dir is equal to one then change to be empty
            if ($this->options['ordering_dir'] == 'none') {
                $this->options['ordering_dir'] = '';
            }
            
            // Replace mixed or upper case macros with lower case
            // versions in the SQL queries.
            //
            foreach (['sql_select','sql_insert','sql_update','sql_delete'] as $s) {
                if (is_string($this->options[$s])) {
                    $this->options[$s] = preg_replace_callback('/{[-a-z_0-9]+}/i', function ($matches)
                    {
                        return strtolower($matches[0]);
                    }, $this->options[$s]);
                }
            }

            if ($this->options['paging_current'] < 1) {
                $this->options['paging_current'] = 1;
            }


            // Get the number from the query string. Override
            // the page set in the paging_current setting.
            if (!empty($_GET[$this->qs('paging')])) {

                if (!preg_match('/^[0-9]+$/', $_GET[$this->qs('paging')])) {
                    
                    $url = new editor_url($_SERVER['REQUEST_URI']);
                    $url->removequerystringparameter($this->qs('paging'));
                    $url->setAnchor($this->id);
                    $u = $url->get();

                    editor_redirect($u);
                }
                
                $this->options['paging_current'] = $_GET[$this->qs('paging')];

                if ($this->options['paging_current'] < 1) {
                    $this->options['paging_current'] = 1;
                }

                // This should be enough...
                if ($this->options['paging_current'] > 1000000000) {
                    $this->options['paging_current'] = 1;
                    $url = new editor_url($_SERVER['REQUEST_URI']);
                    $url->removequerystringparameter($this->qs('paging'));
                    $url->setAnchor($this->id);
                    $u = $url->get();
                    
                    editor_redirect($u);
                }
            }

            //
            // Open the database.
            //
            $this->sqlite = new SQLite3($this->database_file);

            //
            // Determine the columns that make up the table
            //
            $columns = [];
            $result = $this->sqlite->query('PRAGMA table_info(' . $this->database_table . ')');
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $columns[] = $row['name'];
            }
            $this->database_columns = $columns;
            
            //
            // Now get the fulll structure of the table
            //
            $this->getStructure();
            
            //
            // Now the structure has been determined build the
            // default insert query.
            //
            if (is_null($this->options['sql_insert'])) {
                $this->options['sql_insert'] = $this->buildInsertQuery();
            }


            //
            // Determine the primary key field by looking at the
            // pragma.
            //
            if (empty($this->options['primary_key'])) {

                $rows   = array();
                $result = $this->sqlite->query($sql = 'pragma table_info(' . $this->database_table . ')');
    
                while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $row;

                    if ($row['pk']) {
                        $this->options['primary_key'] = $row['name'];
                    }
                }
            }





            //
            // Allow the use of * in the various properties
            //
            foreach (['columns_widths','editable_types','editable','search_columns', 'ordering_include', 'ordering_exclude','columns_callbacks','columns_escape'] as $column) {

                if (is_array($this->options[$column])) {
                    foreach ($this->options[$column] as $k => $v) {
                        if (trim($k) === '*') {
                            foreach ($this->database_columns as $c) {
                                if (!isset($this->options[$column][$c])) {
                                    $this->options[$column][$c] = $v;
                                }
                            }
                            // Get rid of the asterisk
                            unset($this->options[$column]['*']);
                        }
                    }
                }
            }



            // First, if the request is a POST then handle that. It
            // will be an edit of a new row.

            if (!empty($this->options['editable']) AND !$this->options['editable_view_only'] AND $this->options['sql_update'] AND !empty($_POST['editor_action']) AND $_POST['editor_action'] === 'save' AND !empty($_POST['editor_id']) AND $_POST['editor_id'] === $this->id) {

                //
                // First, go through the POST data and add blank entries
                // if there are checkboxes in the configuration but nothing
                // has been submitted. The browser won't send anything if
                // no checkboxes were checked so this needs to be done
                // for when there's something in the database but no
                // checkboxes were checked. In that case, nothing
                // would change for that field normally but it needs
                // to be set to nothing in case theres already
                // something in that field.
                //
                foreach ($this->options['editable_types'] as $k => $v) {
                    if ( ($v === 'checkbox' OR $v === 'radio') AND !isset($_POST['data'][$k])) {
                        $_POST['data'][$k] = '';
                    }
                }

                // Pass the data to the editable_callback function,
                // if its defined. if this function returns a
                // falsey value then the save SQL is not run.
                //
                // If the sql_update option is a function then it's
                // not called.
                //
                if (is_callable($this->options['editable_callback'])) {
                    $callback_return = $this->options['editable_callback']($this, $_POST['data']);
                }

                if (!isset($callback_return) OR is_null($callback_return) OR $callback_return) {
                    if (is_callable($this->options['sql_update'])) {
                        $this->options['sql_update'](
                            $this,
                            $_POST['data'],
                            $_POST['editor_primary_key_column'],
                            $_POST['editor_primary_key_value']
                        );
    
                    } else {
    
                        // Initially set this to true. If any query
                        // fails it's set to false.
                        $final_result = true;
                        $sqliteErrorMessages = [];
    
                        if (!empty($_POST['data'])) {
                            foreach ($_POST['data'] as $k => $v) {
        
                                // Only allow values that have been
                                // designated as being editable are allowed
                                // to be saved.
                                if (!isset($this->options['editable'][$k]) OR !$this->options['editable'][$k]) {
                                    continue;
                                }
        
        
        
                                // Convert datetime formats
                                if (!is_array($v) AND preg_match('/(\d\d\d\d-\d\d-\d\d)(?: |T)(\d\d:\d\d)((?::\d\d)?)/', $v, $matches)) {
                                    $_POST['data'][$k] = sprintf('%s %s', $matches[1], $matches[2]);
                                    
                                    // Add seconds
                                    if (!$matches[3]) {
                                        $_POST['data'][$k] .= ':00';
                                    }
                                }
        
                                $sql = str_ireplace('{table}', $this->options['table'], $this->options['sql_update']);
                                $sql = str_ireplace('{column}', $k, $sql);
                                $sql = str_ireplace('{value}', "'" . SQLite3::escapeString(is_array($v) ? implode(',', $v): $v) . "'", $sql);
                                $sql = str_ireplace('{id}', (is_integer($_POST['editor_primary_key_value']) OR is_numeric($_POST['editor_primary_key_value'])) ? intval($_POST['editor_primary_key_value']) : "'" . SQLite3::escapeString($_POST['editor_primary_key_value']) . "'", $sql);
                                $sql = str_ireplace('{primary_key}',$_POST['editor_primary_key_column'], $sql);
        
        
                                // Run the SQL query to update the
                                // database.
                                $this->sqlite->enableExceptions(true);
                                
                                try {
                                    $result = $this->sqlite->query($sql);
                                } catch (Exception $e) {
                                    $sqliteErrorMessages[] = $e->getMessage();
                                }
        
                                $final_result = $final_result && empty($sqliteErrorMessages);
                            }
                        }
    
                        if ($final_result) {
                            editor_messages::success($this->id, 'The database was updated');
                        } else {
                            foreach ($sqliteErrorMessages as $e) {
                                editor_messages::error($this->id, 'The update failed (SQLite said: ' . $e . ').');
                            }
                        }
                    }
                }

                // Redirect back to the referring page
                //
                // Build the URL to redirect back to.
                //
                $url = new editor_url();
                $url->removequerystringparameter('r');
                $url->addquerystringparameter('r', mt_rand(999999, 1999999999));

                $redirectTo = $url->get();

                //editor_redirect($url->get());
                editor_redirect($redirectTo . '#' . $this->id);
                exit;
            }




            foreach (['editable_types_select_options', 'editable_types_radio_options', 'editable_types_checkbox_options'] as $p) {
                foreach ($this->options[$p] as $k => $v){
                    if (gettype($this->options[$p][$k]) === 'string' AND preg_match('|^\s*sql\s*:|', $this->options[$p][$k])) {
                        $query   = preg_replace('|^\s*sql\s*:|','',$this->options[$p][$k]);
                        $result  = $this->sqlite->query($query);
                        $results = [];
                        
                        while($row = $result->fetchArray()) {
                            $results[] = $row[0];
                        }
                        
                        $this->options[$p][$k] = $results;
                    }
                }
            }







            // Add a new row by running the given SQL (in
            // the configuration).
            if ($this->options['sql_insert'] AND !empty($_GET[$this->qs('add')]) AND $_GET[$this->qs('add')] === $this->id) {

                if (is_callable($this->options['sql_insert'])) {
                    $this->options['sql_insert']($this);

                } else if (is_string($this->options['sql_insert']) AND $this->options['sql_insert']) {

                    $this->sqlite->enableExceptions(true);
                    $sqliteErrorMessage = false;

                    try {

                        $result = $this->sqlite->query($this->options['sql_insert']);
                    }catch (Exception $e) {
                        $sqliteErrorMessage = $e->getMessage();
                    }

                    if (!$sqliteErrorMessage) {
                        editor_messages::success($this->id, 'A new row was added');
                    } else {
                        editor_messages::error($this->id, 'The addition failed (SQLite said: ' . $sqliteErrorMessage . ').');
                    }
                }

                // Build the URL to redirect back to.
                //
                $url = new editor_url();
                $url->removequerystringparameter($this->qs('add'));
                $url->setanchor($this->id);

                editor_redirect($url->get());
                exit;
            }








            //
            // Build the SQL for the delete from what has been
            // given in the configuration.
            //
            if ($this->options['sql_delete'] AND !empty($this->options['sql_delete']) AND !empty($_GET[$this->qs('action')]) AND $_GET[$this->qs('action')] === 'delete' AND $_GET[$this->qs('id', false)] === $this->id) {

                if (!empty($_GET[$this->qs('delete')])) {
 
                    $ids = $_GET[$this->qs('delete')];

                    if (is_callable($this->options['sql_delete'])) {
                        $this->options['sql_delete']($this, $ids);
    
                    } else if (is_string($this->options['sql_delete'])) {
                        
                        // If any of the submitted IDs are not numeric
                        // then quote them.
                        foreach ($ids AS &$v) {
                            if (!is_numeric($v)) {
                                $v = "'" . $this->sqlite->escapeString($v) . "'";
                            }
                        }

                        $sql = str_ireplace('{table}', $this->options['table'], $this->options['sql_delete']);
                        $sql = str_ireplace('{ids}', implode(',', $ids), $sql);
                        $sql = str_ireplace('LIMIT {count}', 'LIMIT ' . count($ids), $sql);
                        $sql = str_ireplace('{primary_key}',$this->options['primary_key'], $sql);

                        $this->sqlite->enableExceptions(true);
                        $sqliteErrorMessage = false;

                        try {
                            $result = $this->sqlite->query($sql);
                        }catch (Exception $e) {
                            $sqliteErrorMessage = $e->getMessage();
                        }


                        
                        if (!$sqliteErrorMessage) {
                            $affected = $this->sqlite->changes();
                            editor_messages::success($this->id, $affected > 1 ? $affected . ' rows were deleted' : 'That row was deleted');
                        } else {
                            editor_messages::error($this->id, 'The delete failed (SQLite said: ' . $sqliteErrorMessage . ').');
                        }
                    }
                } else {
                    editor_messages::error($this->id, 'No rows were selected!');
                }

                // Build the URL to redirect back to
                $url = new editor_url();
                $url->removequerystringparameter($this->qs('action'));
                $url->removequerystringparameter($this->qs('delete'));
                $url->setanchor($this->id);

                editor_redirect($url->get());
                exit;
            }
            
            
            //
            // Determines the order by part of the query from the
            // options and the query-string.
            //
            $this->addOrdering();




            //
            // Print the list of database rows in the requested
            // table.
            //
            $this->printTable();
        }








        //
        // Shows the HTML header
        //
        public function header()
        {
            ?>
                <!-- <div style="height: 25px">&nbsp;</div> -->
            <?php
            editor_messages::display($this->id);

        }








        //
        // Shows the HTML footer
        //
        public function footer()
        {
        }







        //
        // This function determines if a column can be ordered by
        // based on the ordering include and ordering exclude
        // properties.
        //
        // @param  string name The column name
        // @return bool        Whether the column can be ordered
        //                     by or not
        //
        function orderingIncludeExclude($name)
        {
            $result = false;

            //
            // Inclusions
            //
            $include_null     = is_null($this->options['ordering_include']);
            $include_asterisk = isset($this->options['ordering_include']) && is_array($this->options['ordering_include']) && !empty($this->options['ordering_include']['*']);
            $include_column   = isset($this->options['ordering_include']) && is_array($this->options['ordering_include']) && !empty($this->options['ordering_include'][$name]);
            
            if ($include_null OR $include_asterisk OR $include_column) {
                $result = true;
            }

            //
            // Exclusions
            //
            if (isset($this->options['ordering_exclude'])) {
                $exclude_asterisk = is_array($this->options['ordering_exclude']) && !empty($this->options['ordering_exclude']['*']);
                $exclude_column   = is_array($this->options['ordering_exclude']) && !empty($this->options['ordering_exclude'][$name]);
    
                
                if ($exclude_asterisk OR $exclude_column) {
                    $result = false;
                }
            }


            // Having an asterisk in the ordering_include array
            // allows all columns to be orderable
            //if (is_array($this->options['ordering_include']) AND in_array('*', $this->options['ordering_include'])) {
            //    $result = true;
            //} elseif (is_array($this->options['ordering_include'])) {
            //if (is_array($this->options['ordering_include'])) {
            //    if (in_array($name, $this->options['ordering_include'])) {
            //        $result = true;
            //    } else {
            //        $result = false;
            //    }
            //}
            
            //if (in_array($name, (array)$this->options['ordering_exclude']) OR in_array('*', (array)$this->options['ordering_exclude'])) {
            //if (in_array($name, (array)$this->options['ordering_exclude'])) {
            //    $result = false;
            //}
            
            
            return $result;
        }








        //
        // Determines the correct order by part of the query and
        // adds it.
        //
        public function addOrdering ()
        {
            // Get the ID of this editor instance
            $id = $this->id;

            if (    stripos($this->options['sql_select'],'{order}') > 0
                AND (@$_GET[$this->qs('order_column')] !== 'none')
                AND (@$_GET[$this->qs('order_dir')] !== 'none')) {

                
                // Start by getting the order by from the
                // configuration.
                $order_column = !empty($this->options['ordering_column']) ? $this->options['ordering_column'] : '';
                $order_dir    = !empty($this->options['ordering_dir']) ? $this->options['ordering_dir'] : 'asc';

                // If the order_column is set on the QS - use it
                // (is the ordering_changeable property is set to
                // false).
                if (!empty($this->options['ordering_changeable']) AND !empty($_GET[$this->qs('order_column')]) AND preg_match('/^[a-z0-9]+$/', $_GET[$this->qs('order_column')])) {
                    $order_column = $_GET[$this->qs('order_column')];
                }

                // If the order_dir is set on the QS - use it.
                if (!empty($this->options['ordering_changeable'])) {
                    if (!empty($_GET[$this->qs('order_dir')]) AND preg_match('/^asc|desc$/i', $_GET[$this->qs('order_dir')])) {
                        $order_dir = strtoupper($_GET[$this->qs('order_dir')]);
                    } else if (!empty($this->options['ordering_dir'])) {
                        $order_dir = @strtolower($this->options['ordering_dir']);
                    } else {
                        $order_dir = 'asc';
                    }
                }

                // Order by column and dir have been given so use
                // them in the query. But if the column is in the
                // exclude array - ignore it.
                if (!empty($order_column) AND !empty($order_dir) AND $this->orderingIncludeExclude($order_column)) {

                    $this->options['sql_select'] = str_ireplace(
                        '{order}',
                        'ORDER BY' . sprintf(' %s(%s) ', ($this->options['ordering_case'] ? '': 'LOWER'), $order_column) . $order_dir,
                        $this->options['sql_select']
                    );

                // No order given so just lose the {order} from the
                // query.
                } else {

                    $this->options['sql_select'] = str_ireplace(
                        '{order}',
                        '',
                        $this->options['sql_select']
                    );
                }

            // Get rid of the {order}
            } else if (stripos($this->options['sql_select'],'{order}') > 0) {

                $this->options['sql_select'] = str_ireplace(
                    '{order}',
                    '',
                    $this->options['sql_select']
                );
            }
            
            $this->ordering_column = !empty($order_column) ? $order_column : '';
            $this->ordering_dir    = !empty($order_dir) ? $order_dir : '';
        }








        //
        // Builds the structure of the table
        //
        public function getStructure ()
        {
            $result  = $this->sqlite->query("pragma table_info({$this->database_table})");
            $rows = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $rows [] = $row;
            }
            $this->structure = $rows;
        }

        //
        // Builds the default insert query based on the structure.
        // All the values are blank by default.
        //
        public function buildInsertQuery ()
        {
            $fields     = $this->database_columns;
            $fields_str = '';
            $values     = [];

            //
            // Add all of the field names to the query
            //
            $fields_str = implode(', ', $fields);
            $sql       = sprintf(
                'INSERT INTO %s (%s) values({values})',
                $this->options['table'],
                $fields_str
            );
            
            //
            // Now add default field values into the query
            //
            $values_str = '';
            $values = [];
            foreach($this->structure as $f) {
                switch (strtolower($f['type'])) {
                    case 'real':
                    case 'integer':
                        $values[] = $f['pk'] ? 'NULL' : ($f['dflt_value'] ? $f['dflt_value'] : 0);
                        break;
                    
                    case 'null':
                        $values[] = ($f['dflt_value'] ? $f['dflt_value'] : 'NULL');
                        break;

                    case 'blob':
                    case 'text':
                        $values[] = ($f['dflt_value'] ? $f['dflt_value'] : "''");
                        break;
                }
            }
            
            // Add the values to the SQL query
            $sql = str_replace('{values}', implode(',', $values), $sql);

            return $sql;
        }








        //
        // Shows the table
        //
        // printData
        //
        public function printTable ()
        {
            $html    = '';
            $columns = array();
            $sql     = $this->options['sql_select'];
            
            // Replace the {table} macro in the SQL with the name
            // of the table.
            $sql = str_ireplace('{table}',$this->options['table'], $sql);










            ////////////////////////////////////////////////////
            // Replace the search placeholder with the SEARCH //
            // parameters.                                    //
            ////////////////////////////////////////////////////
            if ($this->options['search'] AND strpos($this->options['sql_select'], '{where}') > 0 AND !empty($_GET[$this->qs('search')])) {

                // Split the search string into individual words
                $search_clause  = '';
                $clauses        = [];
                $terms          = preg_split('/\s+/', trim($_GET[$this->qs('search')]));
                $columns        = $this->getSearchColumns();
                $concat         = 'LOWER(IFNULL(' . implode(",'') || IFNULL(", $columns) . ",''))";
                $not            = false;
                
                
                
                foreach ($terms as $t) {
                    // If this is a negated search expression then
                    // the NOT keyword should preced the GLOB
                    if (substr($t, 0, 1) === '!') {
                        $not    = true;
                        $t      = substr($t, 1); // Get rid of the !
                    }
                    $clauses[] = "\r\n" . $concat . ($not ? ' NOT ' : '') . " GLOB '*" . strtolower(SQLite3::escapeString($t)) . "*'";
                };
                
                $search_clause = implode(' AND ', $clauses);
                $sql = str_ireplace('{where}',$search_clause,$sql);

            } else {
                $sql = str_ireplace('{where}','1',$sql);
            }





            //
            // First - run the query with no limit clause to
            // determine how many rows there are in the unpaged
            // result set.
            //
            // Run the SQL query to update the
            // database.
            $this->sqlite->enableExceptions(true);
            
            try {
                $result = $this->sqlite->query($sql);
            } catch (Exception $e) {
                $sqliteErrorMessage = $e->getMessage();
            }
            
            if (!empty($sqliteErrorMessage)) {
                editor_messages::error($this->id, $sqliteErrorMessage);
            }

            $unpaged_numrows = 0;

            if (!empty($result)) {
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $unpaged_numrows++;
                    
                    // Also get an array of column names uasing
                    // this loop (on the first iteration only though)
                    if (empty($this->database_selected_columns)) {
                        $this->database_selected_columns = array_keys($row);
                    }
                }
            }



            
            
            //
            // PAGING
            //







            // Generate the paging links here
            $paging_numpages = ceil($unpaged_numrows / $this->options['paging_perpage']);
            $this->options['paging_numpages'] = $paging_numpages;
            $paging_page_numbers_string = '';
            $paging_page_numbers_array  = [];
            $qs = preg_replace('/' . $this->qs('paging') . '=[-\d.]*&?/','', $_SERVER['QUERY_STRING'] ?? '');
            
            // Get rid of trailing ampersands
            $qs = trim($qs,'&');
            





            if (isset($_GET[$this->qs('paging')]) AND (!empty($_GET[$this->qs('paging')]) OR $_GET[$this->qs('paging')] === '0')) {

                // Ignore negative page numbers - redirect to page
                // one.
                if (!empty($_GET[$this->qs('paging')]) AND  $_GET[$this->qs('paging')] < 1) {

                    $url = new editor_url($_SERVER['REQUEST_URI']);
                    $url->removequerystringparameter($this->qs('paging'));
                    $url->setanchor($this->id);
                    
                    $u = $url->get();

                    editor_redirect($u);
                }

                // Page requested is higher than the number of pages
                if (!empty($_GET[$this->qs('paging')]) AND $_GET[$this->qs('paging')] > $paging_numpages) {

                    $url = new editor_url($_SERVER['REQUEST_URI']);
                        $url->removequerystringparameter($this->qs('paging'));
                        $url->addquerystringparameter($this->qs('search'), urlencode($_GET[$this->qs('search')]));
                        $url->setquerystringparameter($this->qs('paging'), $paging_numpages);
                        $url->setanchor($this->id);
                    $u = $url->get();
                    editor_redirect($u);
                }
            }


            $paging_start = max(1, $this->options['paging_current'] - 4);
            $paging_end   = min($paging_numpages, $paging_start + 9);

            


            while($paging_end - $paging_start < 10 && $paging_start > 0) {
                $paging_start--;
            }
            
            // Catch zero
            if ($paging_start < 1) {
                $paging_start = 1;
            }


            //
            // Now that we have the page numbers - build the page
            // numbers HTML (but only if theres more tha single page).
            //
            if ($paging_numpages > 1) {
                for ($str='',$i=$paging_start; $i<=($paging_start + 10) && $i<=$paging_end; ++$i) {
    
                    if ( $i == $this->options['paging_current']) { // DOUBLE EQUALS
                        $str = '<span class="paging-links-current">' . $i . '</span>&nbsp;&nbsp;';
                    } else {
                        $str = '<a href="' . $_SERVER['PHP_SELF'] .'?' . $qs . '&' . $this->qs('paging'). '=' . $i . '#' . $this->id . '" class="paging-links-link">' . $i . '</a>&nbsp;&nbsp;';
                    }
                    
                    
                    // Clean up
                    $str = str_replace('?&' . $this->qs('paging') . '=','?' . $this->qs('paging') . '=', $str);
                    
                    $paging_page_numbers_array[] = [$i, $str];
                }
            }

            // Now convert the array of page numbers into a
            // string.
            for ($i=0; $i<count($paging_page_numbers_array); ++$i) {
                $paging_page_numbers_string .= $paging_page_numbers_array[$i][1];
            }

            if ($this->options['paging_numpages'] > 10) {
                $paging_page_numbers_string .= '<a href="" onclick="event.preventDefault();editor_objects[`' . $this->id . '`].editor_showallpagenumbers(event, `' . $this->id . '`,' . $this->options['paging_numpages'] .'); return false">...</a>';
            }


            //
            // Add a LIMIT to the SQL query to facilitate paging
            //
            $limit = ' LIMIT {paging_current},{paging_perpage}';
            $limit = str_ireplace('{paging_current}', ($this->options['paging_current'] - 1) * $this->options['paging_perpage'], $limit);
            $limit = str_ireplace('{paging_perpage}', $this->options['paging_perpage'], $limit);

            $sql .= $limit;


            $this->sqlite->enableExceptions(true);
            
            try {
                $result = $this->sqlite->query($sql);
            } catch (Exception $e) {
                $sqliteErrorMessage = $e->getMessage();
            }
            
            if (!empty($sqliteErrorMessage)) {
                editor_messages::error($this->id, $sqliteErrorMessage);
            }

            // Loop through the result set counting the number of
            // rows.
            $numRows = 0;
            if (!empty($result)) {
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $numRows++;
                }
            }



            //
            // Determine the maximum page number
            //
            $maxpage = ceil($unpaged_numrows / $this->options['paging_perpage']);


            if ($numRows < 1 AND !empty($_GET[$this->qs('paging')]) AND $_GET[$this->qs('paging')] > 1) {
                $url = new editor_url();
                $url->setquerystringparameter($this->qs('paging'), $maxpage);
                $url->setanchor($this->id);
                $u = $url->get();

                editor_redirect($u);
            }

            if (!empty($result)) {
                $result->reset();
            }

            // This determines if the current rerquested page number
            //is greater than what the result set provides for.

            if( (($this->options['paging_current'] - 1) * $this->options['paging_perpage']) > $unpaged_numrows) {
                $url = new editor_url();
                $url->removequerystringparameter($this->qs('paging'));
                $url->setquerystringparameter($this->qs('paging'), $maxpage);
                $url->setanchor($this->id);
                $u = $url->get();
                
                editor_redirect($u);
            }

            //
            // Loop through the results
            //
            
            // First calculate the paging start number.
            $this->options['paging_start'] = ((($this->options['paging_current'] - 1) * $this->options['paging_perpage']) + 1);












// BEFORE the loop that loops through the results - print these
// JavaScript functions.
echo '
<a name="'. $this->id . '"></a> 
<script>
    $a = alert;
    $c = console.log;
    
    // This "Registry"-style object is a container for all of
    // the editor objects that are created so that each can be
    // accessed correctly. It stops earlier editors using
    // subsequently created editors functions
    var editor_objects = editor_objects || {};
        editor_objects["' . $this->id . '"] = {};

    editor_objects["' . $this->id . '"].editor_modal = {};
    editor_objects["' . $this->id . '"].editor_modal.show = function (html, options = {})
    {
        if (!options.className) {
            options.className = "editor-modaldialog";
        }
        
        // 
        // Create a container DIV the the modal dialog background
        // and foreground are added to.
        //
        var container = document.createElement("div");
            container.id        = options.className + "-container";
            container.className = options.className + "-container";
        document.body.appendChild(container);
        
        editor_objects["' . $this->id . '"].editor_modal.container = container;
        

        //
        // Create and show the background
        //
        var bg = document.createElement("div");
            bg.className        = options.className + "-background";
            bg.style.position   = "fixed";
            bg.style.top        = "-10px";
            bg.style.left       = "-10px";
            bg.style.width      = "calc(100% + 20px)";
            bg.style.height     = "calc(100% + 20px)";
            bg.style.backgroundColor = "rgb(204,204,204)";
            bg.style.opacity    = 0;
            bg.style.zIndex     = 3276;
            bg.style.transition = "0.25s opacity ease-out";
            //bg.style.visibility = "visible";
            bg.style.display    = "inline";
        editor_objects["' . $this->id . '"].editor_modal.container.appendChild(bg);
        
        editor_objects["' . $this->id . '"].editor_modal.background = bg;









        //
        // Show the foreground window
        //
        var dialog = document.createElement("div");
            dialog.id                    = options.className + "-dialog";
            dialog.className             = options.className + "-dialog";
            dialog.style.position        = "fixed";
            dialog.style.backgroundColor = "white";
            dialog.style.width           = options.width ? options.width + "px" : "500px";
            //dialog.style.border        = "1px solid #999";
            dialog.style.zIndex          = 32767;
            dialog.style.padding         = "15px";
            dialog.style.opacity         = 0;
            //dialog.style.minHeight       = "100px";
            //dialog.style.maxHeight       = "400px";
            dialog.style.fontFamily      = "Verdana";
            //dialog.style.fontSize        = "10pt";
            dialog.style.lineHeight      = "initial";
            dialog.style.transition      = "0.25s opacity ease-out";
            dialog.style.overflow        = "auto";
        editor_objects["' . $this->id . '"].editor_modal.container.appendChild(dialog);
        
        editor_objects["' . $this->id . '"].editor_modal.dialog = dialog;










        // Trigger the dialog and the background to fade in.
        setTimeout(function ()
        {
            bg.style.opacity = 0.75;
            dialog.style.opacity = 1;

            dialog.style.left = "calc(50% - " + (dialog.offsetWidth / 2) + "px)";
            dialog.style.top  = "calc(50% - " + (dialog.offsetHeight / 2) + "px";
        }, 50);
        
        
        

        //
        // Add the event listener for getting rid of the
        // dialog
        //
        if (options.hideOnBackground !== false) {
            bg.addEventListener("click", function (e)
            {
                editor_objects["' . $this->id . '"].editor_modal.hide();
            });
        }
        
        //
        // Disable page scrolling
        //
        setTimeout(function ()
        {
            var scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
            if (!editor_objects["' . $this->id . '"].editor_modal.originalCSS) {
                editor_objects["' . $this->id . '"].editor_modal.originalCSS = {};
            };
            editor_objects["' . $this->id . '"].editor_modal.originalCSS.paddingRight = document.body.style.paddingRight;
            editor_objects["' . $this->id . '"].editor_modal.originalCSS.overflow     = document.body.style.overflow;
            document.body.style.overflow          = "hidden";
            document.body.style.paddingRight      = scrollbarWidth + "px";
        }, 50);

        

        //
        // Add the event listener for getting rid of the
        // dialog
        //
        window.addEventListener("keydown", editor_objects["' . $this->id . '"].editor_modal.window_onkeydown_listener_function = function (e)
        {
            if (e.keyCode === 27) {
                editor_objects["' . $this->id . '"].editor_modal.hide();
        
                e.stopPropagation();
                e.preventDefault();
                
                return false;
            }
        });




        //
        // Add the content for the dialog
        //
        dialog.innerHTML = html;
    };




    //
    // Hide the dialog
    //
    editor_objects["' . $this->id . '"].editor_modal.hide = function ()
    {
        if (editor_objects["' . $this->id . '"].editor_modal && editor_objects["' . $this->id . '"].editor_modal.container) {
            editor_objects["' . $this->id . '"].editor_modal.container.replaceChildren();
        }

        // Enable the documents scrollbars after a short delay to
        // allow the dialog to be hidden.
        if (editor_objects["' . $this->id . '"].editor_modal.originalCSS) {
            setTimeout(function ()
            {
                document.body.style.overflow     = editor_objects["' . $this->id . '"].editor_modal.originalCSS.overflow;
                document.body.style.paddingRight = editor_objects["' . $this->id . '"].editor_modal.originalCSS.paddingRight;
            }, 25);
        }
        
        // And finally remove the container DIV
        if (editor_objects["' . $this->id . '"].editor_modal.container) {
            editor_objects["' . $this->id . '"].editor_modal.container.style.display = "none";
            editor_objects["' . $this->id . '"].editor_modal.container.parentNode.removeChild(editor_objects["' . $this->id . '"].editor_modal.container);
            
            editor_objects["' . $this->id . '"].editor_modal.container = null;
        }
    };
    
    //
    // Add the shorter aliases for these functions
    //
    if (!window.editor_modal) {
        editor_modal = {
            show:  editor_objects["' . $this->id . '"].editor_modal.show,
            hide:  editor_objects["' . $this->id . '"].editor_modal.hide,
            close: editor_objects["' . $this->id . '"].editor_modal.hide
        };
    }




    //
    // Return all of the data from the editor. By default this
    // function returns all of the data but if you pass a row
    // number (which starts at zero for the first row) then
    // an array of just the data in that row will be returned.
    //
    // The data is returned by looking at the data-original
    // attributes on each cell of the table.
    //
    // @param number id  OPTIONAL The id of the editor. This
    //                            defaults to editor1.
    // @param number row OPTIONAL If given then just the data for
    //                            the given row number will be
    //                            returned.
    //
    editor_getdata =
    editor_objects["' . $this->id . '"].editor_getdata = function (id = "editor1", index = null)
    {
        var id    = id ?? "editor1";
        var table = document.querySelector("div.editor-container-" + id + " div.editor table");
        
        if (!table) {
            return null;
        }
        
        var trs   = table.querySelectorAll("tbody tr");
        var data  = [];
        var count = typeof index === "number" ? 1 : trs.length;

        // Loop through all of the tr objects getting each rows
        // data from the data-original attributes
        for (var i=0; i<trs.length; ++i) {

            if (typeof index !== "number" || i === index) {

                var tds = trs[i].querySelectorAll("td");
                var row = [];

                for (var j=0; j<tds.length; ++j) {
                    if (tds[j].hasAttribute("data-original")) {
                        row.push(tds[j].getAttribute("data-original"));
                    }
                }
            }
        
            if (typeof index === "number" && index === i) {
                return row;
            } else {
                data.push(row);
            }
        }

        return data;
    };








    //
    // Returns true/false as to whether a variable is null or
    // like null (NaN or undefined)
    //
    editor_objects["' . $this->id . '"].editor_isnullish = function (obj)
    {
        if (editor_objects["' . $this->id . '"].editor_isundefined(obj)) return true;
        if (editor_objects["' . $this->id . '"].editor_isnull(obj))      return true;        
        if (Number.isNaN(obj))       return true;
    
        return false;
    };

    editor_objects["' . $this->id . '"].editor_isnull = function (obj)
    {
        return typeof obj === "object" && !obj;
    };
    
    editor_objects["' . $this->id . '"].editor_isundefined = function (obj)
    {
        return typeof obj === "undefined";
    };

    //
    // Add the shorter aliases for these functions
    //
    if (!window.editor_isnullish  ) editor_isnullish   = editor_objects["' . $this->id . '"].editor_isnullish;
    if (!window.editor_isnull     ) editor_isnull      = editor_objects["' . $this->id . '"].editor_isnull;
    if (!window.editor_isundefined) editor_isundefined = editor_objects["' . $this->id . '"].editor_isundefined;



    //
    // Shows the help window for the search that the user
    // can read to learn about the search functionality.
    //
    editor_objects["' . $this->id . '"].editor_showsearchhelp = function ()
    {
        editor_objects["' . $this->id . '"].editor_modal.show(`
<div style="font-size: 12pt; height: 80vh">
    <div style="margin-bottom: 10px; margin-top: 10px">
        <b><big style="margin-bottom: 10px">Search help</big></b>
    </div>

    The search function is versatile and is based on the Unix glob
    syntax. This means that various wild card and pattern
    matching is available to you. There are two wild cards
    available:
    <style>
        ul li {
            list-style-type: none;
        }
    </style>
    
    <ul>
        <li><b>*</b> - This matches any number of characters</li>
        <li><b>?</b> - This matches a single character</li>
    </ul>
    
    Asterisks are implicitly placed at either end of the term(s)
    that you enter. This means that a search for <i>cde</i> would
    find the word <i>abcdefgh</i>.
    
    You can add a <b>!</b> to the front of a word or a sequence of
    characters to negate it. so a search for <i>john !Barry</i> would
    find rows that contain the word <i>john</i> but not with the word
    "barry".

    <div style="margin-bottom: 10px; margin-top: 10px">
        <b><big style="margin-bottom: 10px">Character classes</big></b><br />
    </div>

    Similar to regular expressions you can use square
    brackets - <b>[]</b> - to define a range of characters. So the
    following: <i>[abcdef]</i> would match any of those characters
    - but only one of them. And you can also negate the character
    class by adding a caret sign at the start like this:
    <i>[^abcdef]</i>
    
    <p />
    Remember though that this type of character
    class only matches a single character and if you negate the
    class it will match any character that\'s not in that class.

    <div style="margin-bottom: 10px; margin-top: 10px">
        <b><big style="margin-bottom: 10px">Negating a whole word</big></b><br />
    </div>
    
    If you want to negate a whole word (so maybe you could search
    for: <i>big yellow !balloons</i>, which would look for rows
    that contain the words <i>big</i> and <i>yellow</i> but not
    the word <i>balloons</i>, then you can prefix an exclamation
    point to that word.


    <div style="margin-bottom: 10px; margin-top: 10px">
        <b><big style="margin-bottom: 10px">Examples</big></b><br />
    </div>
    
    <table cellspacing="0" cellpadding="3" border="1">
        <tr><td valign="top"><i>black</i></td><td>Search for rows that contain the word <i>black</i></td></tr>
        <tr><td valign="top"><i>!black</i></td><td>Search for rows that DO NOT contain the word <i>black</i></td></tr>
        <tr><td valign="top"><i>jack&nbsp;black</i></td><td>Search for rows that contain both of the words <i>jack</i> and <i>black</i></td></tr>
        <tr><td valign="top"><i>jack&nbsp;!black</i></td><td>Search for rows that contain the word <i>jack</i> but DO NOT contain the word <i>black</i></td></tr>
        <tr><td valign="top"><i>ja[chdrt]k</i></td><td>Search for rows that contain the given word where the third character can be <i>c</i>,<i>h</i>,<i>d</i>,<i>r</i> or <i>t</i></td></tr>
        <tr><td valign="top"><i>ja[^chdrt]k</i></td><td>Search for rows that contain the given word where the third character MUST NOT be <i>c</i>,<i>h</i>,<i>d</i>,<i>r</i> or <i>t</i></td></tr>
    </table>
    
    <br /><br />
    
</div>`, {
    className: "editor-search-help",
});
    };






    //
    // Cancels a search, removes the GET parameters and redirects
    // back to the page.
    //
    editor_objects["' . $this->id . '"].editor_clearsearch = function(id)
    {
        var searchParams = new URLSearchParams(window.location.search);

        searchParams.delete("' . $this->qs('search', false) . '-"  + id);

        window.location.search = searchParams.toString();
    }




    //
    // Adds the relevant querystring option for a search.
    //
    editor_objects["' . $this->id . '"].editor_search = function (id, text)
    {
        var searchParams = new URLSearchParams(window.location.search);

        searchParams.set("' . $this->qs('search', false) . '-" + id, text);
        searchParams.set("' . $this->qs('paging', false) . '-" + id, 1);
        var qs = searchParams.toString();

        window.location.href = "?" + qs + "#" + id
    }








    //
    // Cancels a search, removes the GET parameters and redirects
    // back to the page.
    //
    editor_objects["' . $this->id . '"].editor_cancelsearch = function (id)
    {
        var searchParams = new URLSearchParams(window.location.search);
        
        searchParams.delete("' . $this->qs('search', false) . '-" + id);

        var url = window.location.href.replace(/\?.*$/,"") + "?" + searchParams.toString() + "#" + id;
        window.location.href = url;
    }




    //
    // Is this a search?
    //
    editor_objects["' . $this->id . '"].editor_issearch = function (id)
    {
        var searchParams = new URLSearchParams(window.location.search);

        return searchParams.has("' . $this->qs('search') . '");
    }







    //
    // Shows all of the page numbers for this particular editor
    //
    editor_objects["' . $this->id . '"].editor_showallpagenumbers = function (e, id, numpages)
    {
        var div = document.createElement("div");
            div.id                      = "' . $this->qs('page-selector') . '";
            div.style.position          = "absolute";
            div.style.textAlign         = "center";
            div.style.cursor            = "pointer";
            div.style.maxHeight         = "200px";
            div.style.backgroundColor   = "white";
            div.style.overflowY         = "auto";
            div.style.fontSize          = "20pt";
            div.style.overflowX         = "hidden";
            div.style.border            = "1px solid black";
            div.style.lineHeight        = "initial";
        document.body.appendChild(div);

        // Add the page numbers to the DIV
        for (var pn=1; pn<=numpages; ++pn) {
            var pnDiv = document.createElement("div");
            pnDiv.style.width = "100%";
            pnDiv.insertAdjacentHTML("afterbegin", pn);
            div.appendChild(pnDiv);

            (function (index)
            {
                pnDiv.onclick = function (e)
                {
                    editor_objects["' . $this->id . '"].editor_setpage(id, index);
                };

                pnDiv.addEventListener("mouseover", function (e)
                {
                    e.target.style.backgroundColor = "#ccc";
                });
                pnDiv.addEventListener("mouseout", function (e)
                {
                    e.target.style.backgroundColor = "white";
                });
            })(pn, id);
        }


        // Now position the div
        div.style.top  = e.pageY + "px";
        div.style.left = e.pageX - 50 + "px";

        //
        // These event listeners handle the hiding of the page
        // numbers DIV.
        //
        div.onmousedown = function (e)
        {
            e.stopPropagation();
        }
        
        window.addEventListener("mousedown", function (e)
        {
            if (div && div.parentNode) {
                div.parentNode.removeChild(div);
            }
        }, false);




        return false;
    }








    editor_objects["' . $this->id . '"].editor_setpage = function (id, page)
    {
        var searchParams = new URLSearchParams(window.location.search);
            searchParams.delete("editor-paging-" + id);
            searchParams.set("editor-paging-" + id, Number(page));
        
        var url = searchParams.toString();
            url = window.location.href.replace(/\?.*$/,"") + "?" + searchParams.toString() + "#" + id;

        window.location.href = url;
    };

    //
    // Add the shorter aliases for these functions
    //
    if (!window.editor_setpage) {
        window.editor_setpage = editor_objects["' . $this->id . '"].editor_setpage;
    }
</script>








<script>
    //
    // Escapes HTML so that it can be printed ontothe page.
    // Equivalent(ish) to the PHP htmlspecialchars() function.
    //
    // @param  string str The string to escape
    // @return string     The escaped string
    //
    editor_objects["' . $this->id . '"].editor_htmlspecialchars = function (str)
    {
        var escape = {
             "&": "&amp;",
             "<": "&lt;",
             ">": "&gt;",
            "\"": "&quot;",
             "\'": "&#039;"
        };

        return str.replace(/[&<>"\']/g, function(c)
        {
            return escape[c];
        });
    }








    //
    // This function can be used by the user when the checkboxes are
    // enabled in order to get hold of which checkboxes are checked.
    // This function is used when you specify user defined action
    // buttons in the configuration. It allows you to get hold of
    // which checkboxes are checked.
    //
    editor_objects["' . $this->id . '"].editor_getchecked = function (id = null)
    {
        if (!id) {
            id = "editor1";
        }

        // A string is given as the id
        if (typeof id === "string") {
            
            // Remove any hash to the beginning if necessary
            id = id.replace(/^\./,"");
            id = id.replace(/^#/,"");

            var root = document.querySelector("div.editor-container-" + id);
        }

        var checkbox_radio = ' . intval($this->options['checkboxes_radio']) . ';
        var checkboxes     = root.querySelector("div.editor").querySelectorAll("input[type=" + (checkbox_radio ? "radio" : "checkbox") + "]");
        var checked        = [];

        for (var i=0; i<checkboxes.length; ++i) {
            if (checkboxes[i].checked) {
                // Convert stringy numbers to real numbers
                if (checkboxes[i].value.match(/^[0-9]+$/)) {
                    checked.push(Number(checkboxes[i].value));
                } else {
                    checked.push(checkboxes[i].value);
                }
            }
        }
        
        return checked;
    };

    //
    // Add the shorter aliases for these functions
    //
    if (!window.editor_getchecked) editor_getchecked = editor_objects["' . $this->id . '"].editor_getchecked;








    //
    // This function adds the input to the document (which is in a
    // popup modal).
    //
    editor_objects["' . $this->id . '"].editor_addedittextinput = function (td, e)
    {

        // Clear any input that is already onscreen
        editor_objects["' . $this->id . '"].editor_canceledittext();


        // Get some of the PHP objects properties
        var properties  = {
            editable:                        ' . JSON_encode($this->options['editable']) . ',
            editable_types_select_options:   ' . JSON_encode($this->options['editable_types_select_options']) . ',
            editable_types_checkbox_options: ' . JSON_encode($this->options['editable_types_checkbox_options']) . ',
            editable_types_radio_options:    ' . JSON_encode($this->options['editable_types_radio_options']) . ',
            editable_view_only:              ' . JSON_encode($this->options['editable_view_only']) . ',
            editable_types:                  ' . JSON_encode($this->options['editable_types']) . ',
            columns_names:                   ' . JSON_encode($this->options['columns_names']) . ',
            database_selected_columns:       ' . JSON_encode($this->database_selected_columns) . '
        };



        // The ID of the editor
        var id             = td.getAttribute("data-id");
        var tr             = td.parentNode;
        //var original     = td.getAttribute("data-original");
        //var column       = td.getAttribute("data-column-name");
        var select_options = [];
        var index          = tr.getAttribute("data-index");
        var tds            = tr.querySelectorAll("td[data-id]");
        var values         = {};


        //
        // Get the original values for the row from the tds
        // variable.
        //
        tds.forEach(function (v, k, a)
        {
            values[v.getAttribute("data-column-name")] = (v.getAttribute("data-original"));
        });















        //////////////////////////////////////////////////////
        // CREATE THE EDIT DIALOG FOR THE WHOLE ROWS INPUTS //
        //////////////////////////////////////////////////////
    












    
    
        // Create the string that becomes the HTML thats shown in
        // the Modal Dialog

        var html = `
<form action="' . (!empty($this->options['editable_save_url']) ? $this->options['editable_save_url'] : $_SERVER['REQUEST_URI'] . '#' . $this->id) . '"  method="post">
<input type="hidden" name="editor_id" value="${td.getAttribute(\'data-id\')}" />
' . (!empty($this->options['editable_save_url']) ? '<input type="hidden" name="editor_database" value="' . $this->database_file . '" />' : '') . '
' . (!empty($this->options['editable_save_url']) ? '<input type="hidden" name="editor_table" value="' . $this->database_table . '" />' : '') . '
<input type="hidden" name="editor_primary_key_value" value="${index}" />
<input type="hidden" name="editor_primary_key_column" value="' . $this->options['primary_key'] . '" />
<input type="hidden" name="editor_action" value="save" />

<table border="0" width="100%">`;

        properties.database_selected_columns.forEach (function (v, k, arr)
        {
            // Determine the input type (if any)
            var input_str ="";
            var column = v;

            if (!properties.editable_view_only && properties.editable[column]) {

                //
                // The select options can be a function too
                //
                if (typeof properties.editable_types_select_options[column] === "string" && properties.editable_types_select_options[column].trim().match(/^function:/)) {
                    var name = properties.editable_types_select_options[column].trim().replace(/^function:/,"")
                    select_options  = (window[name])(column, tr.querySelectorAll("td[data-column-name]"));
                //
                // The options can be a comma separated list
                //
                } else if (typeof properties.editable_types_select_options[column] === "string") {
                    select_options = properties.editable_types_select_options[column].trim().split(/\s*,\s*/);
                //
                // The entry in the editable_types_select_options property is
                // an array
                //
                } else {
                    select_options = properties.editable_types_select_options[column];
                }




                //
                // The radio options can be a function.
                //
                if (typeof properties.editable_types_radio_options[column] === "string" && properties.editable_types_radio_options[column].trim().match(/^function:/)) {
                    var name = properties.editable_types_radio_options[column].trim().replace(/^function:/,"")
                    radio_options  = (window[name])(column, tr.querySelectorAll("td[data-column-name]"));
                //
                // The radio button options can be a comma
                // separated list.
                //
                } else if (typeof properties.editable_types_radio_options[column] === "string") {
                    radio_options = properties.editable_types_radio_options[column].trim().split(/\s*,\s*/);
                //
                // The entry in the editable_types_radio_options
                // property is an array.
                //
                } else {
                    radio_options = properties.editable_types_radio_options[column];
                }




                //
                // The checkbox options can be a function.
                //
                if (typeof properties.editable_types_checkbox_options[column] === "string" && properties.editable_types_checkbox_options[column].trim().match(/^function:/)) {
                    var name = properties.editable_types_checkbox_options[column].trim().replace(/^function:/,"")
                    checkbox_options  = (window[name])(column, tr.querySelectorAll("td[data-column-name]"));
                //
                // The checkbox button options can be a comma
                // separated list.
                //
                } else if (typeof properties.editable_types_checkbox_options[column] === "string") {
                    checkbox_options = properties.editable_types_checkbox_options[column].trim().split(/\s*,\s*/);
                //
                // The entry in the editable_types_checkbox_options
                // property is an array.
                //
                } else {
                    checkbox_options = properties.editable_types_checkbox_options[column];
                }






                // The various edit input types are built here. It
                // could be:
                //     o A regular text input
                //     o A textarea input
                //     o A select dropdown
                //     o Radio buttons
                switch (properties.editable_types[column]) {
                    
                    case "textarea":
                        input_str = `<textarea name="data[${column}]" style="box-sizing: border-box;width: 300px; height: 200px; padding: 2px !important; margin: 0 !important; border: 1px solid #333  !important" class="editor-edit-input editor-edit-input-${column}" data-original="${editor_objects["' . $this->id . '"].editor_htmlspecialchars(values[column])}" >${editor_objects["' . $this->id . '"].editor_htmlspecialchars(values[column])}</textarea>`;
                        break;




                    case "radio":                        
                        var input_str = "";
                        
                        // Add the options that are in the
                        // radio_options variable.
                        //
                        if (radio_options && radio_options.length) {
                                                
                            for (var i=0; i<=radio_options.length; i++){
                                if (typeof radio_options[i] === "string") {
                                    if (radio_options[i].indexOf("::") > 0) {
                                        var a = radio_options[i].split(/::/);
                                        var label = a[0];
                                        var value = a[1];
                                    } else {
                                        var label = radio_options[i];
                                        var value = radio_options[i];
                                    }
                                    
                                    // Is the option selected?
                                    if (values[column] === value) {
                                        var checked = "checked=\"checked\"";
                                    } else {
                                        var checked = "";
                                    }
                        
                                    input_str += `<input type="radio" name="data[${column}]" class="editor-edit-input editor-edit-input-${v}" value="${editor_objects["' . $this->id . '"].editor_htmlspecialchars(value)}" ${checked} id="${"radio_" + i}" style="cursor: pointer" /><label for="${"radio_" + i}" style="cursor: pointer">${editor_objects["' . $this->id . '"].editor_htmlspecialchars(label)}</label><br />`;
                                }
                            }
                        }
                        break;




                    case "checkbox":                        
                        var input_str = "";
                        var current_values = values[column].split(/,/);

                        // Add the options that are in the
                        // checkbox_options variable.
                        //
                        if (checkbox_options && checkbox_options.length) {
                                                
                            for (var i=0; i<=checkbox_options.length; i++){
                                if (typeof checkbox_options[i] === "string") {
                                    if (checkbox_options[i].indexOf("::") > 0) {
                                        var a = checkbox_options[i].split(/::/);
                                        var label = a[0];
                                        var value = a[1];
                                    } else {
                                        var label = checkbox_options[i];
                                        var value = checkbox_options[i];
                                    }
                                    
                                    // Is the option selected?
                                    if (current_values.indexOf(value) > -1) {
                                        var checked = "checked=\"checked\"";
                                    } else {
                                        var checked = "";
                                    }
                        
                                    input_str += `<input type="checkbox" name="data[${column}][]" class="editor-edit-input editor-edit-input-${v}" value="${editor_objects["' . $this->id . '"].editor_htmlspecialchars(value)}" ${checked} id="${"checkbox_" + i}" style="cursor: pointer" /><label for="${"checkbox_" + i}" style="cursor: pointer">${editor_objects["' . $this->id . '"].editor_htmlspecialchars(label)}</label><br />`;
                                }
                            }
                        }
                        break;




                    case "select":
                        input_str = `<select name="data[${column}]" style="box-sizing: border-box;width: 300px; padding: 2px !important; margin: 0 !important; border: 1px solid #333  !important" class="editor-edit-input editor-edit-input-${v}" data-original="${editor_objects["' . $this->id . '"].editor_htmlspecialchars(values[column])}" value="${editor_objects["' . $this->id . '"].editor_htmlspecialchars(values[column])}">`;

                        // Add the options that are in the
                        // select_options variable.
                        //
                        if (select_options && select_options.length) {
                        
                            // Add a blank option at the start
                            //
                            // *** User can do this ***
                            //
                            // input_str += "<option></option>";
                        
                            for (var i=0; i<=select_options.length; i++){
                                if (typeof select_options[i] === "string") {
                                    if (select_options[i].indexOf("::") > 0) {
                                        var a = select_options[i].split(/::/);
                                        var label = a[0];
                                        var value = a[1];
                                    } else {
                                        var label = select_options[i];
                                        var value = select_options[i];
                                    }
                                    
                                    // Is the option selected?
                                    if (values[column] === value) {
                                        var selected = "selected=\"selected\"";
                                    } else {
                                        var selected = "";
                                    }
                        
                                    input_str += `<option value="${editor_objects["' . $this->id . '"].editor_htmlspecialchars(value)}" ${selected}>${editor_objects["' . $this->id . '"].editor_htmlspecialchars(label)}</option>`;
                                }
                            }
                        }
                        input_str += `</select>`;
                        break;




                    default:
                        var type = properties.editable_types[column];

                        if (type === "datetime") {
                            type = "datetime-local";
                        }
                        
                        if (type && type.trim().match(/range/)) {
                        
                            var min  = (Number(type.trim().replace(/^.*min\s*=\s*(\d*).*$/i, "$1")) || 0);
                            var max  = (Number(type.trim().replace(/^.*max\s*=\s*(\d*).*$/i, "$1")) || 100);
                            var step = (Number(type.trim().replace(/^.*step\s*=\s*(\d*).*$/i, "$1")) || 1);

                            input_str = `<input type="range"
                                                style="box-sizing: border-box; width: 300px; padding: 2px !important;"
                                                value="${parseFloat(values[column])}"
                                                name="data[${column}]"
                                                class="editor-edit-input editor-edit-input-${column}"
                                                id="editor-edit-input-range-${column}"
                                                data-original="${parseFloat(values[column])}"
                                                title="Value: ${parseFloat(values[column])}"
                                                min="${min}"
                                                max="${max}"
                                                step="${step}"
                                                onchange="this.title = \'Value: \' + this.value"
                                                oninput="this.nextElementSibling.innerText = this.value"/>
                                            <span>${parseFloat(values[column])}</span>`;

                        } else {
                            input_str = `<input type="${type || "text"}" style="box-sizing: border-box; width: 300px; padding: 2px !important; margin: 0 !important; border: 1px solid #333  !important" value="${editor_objects["' . $this->id . '"].editor_htmlspecialchars(values[column])}" name="data[${column}]" autocomplete="on" class="editor-edit-input editor-edit-input-${column}" data-original="${editor_objects["' . $this->id . '"].editor_htmlspecialchars(values[column])}" />`;
                        }
                }
            } else {
                input_str = `${editor_objects["' . $this->id . '"].editor_htmlspecialchars(values[v])}`;
            }

            html += `
<tr>
    <td width="1" align="right" valign="top">${properties.columns_names[column] || column}:</td>
    <td>${input_str || ""}</td>
</tr>
`;
        });
        
        //
        // Close the table and add the save and cancel buttons
        // accounting for whether the editor is in view-only
        // mode or not.
        //

        if (properties.editable_view_only) {
            html += `
</table>
<div style="margin-top: 5px">
    <input type="reset" id="editor-cancel-edit-button" style="margin: 2px; width: 100px; font-size: 120%; font-weight: bold; cursor: pointer" value="OK" />
</div>
</form>
`;
        } else {
            html += `
</table>
<div style="margin-top: 5px">
    <input type="submit" id="editor-save-edit-button" style="margin: 2px; width: 100px; color: green; font-size: 120%; font-weight: bold; cursor: pointer" value="Save" />
    <input type="reset" id="editor-cancel-edit-button" style="margin: 2px; width: 100px; color: red;   opacity: 0.5; font-size: 120%; font-weight: bold; cursor: pointer" value="Cancel" />
</div>
</form>
`;
        }




        // Show the inputs in a ModalDialog
        editor_objects["' . $this->id . '"].editor_modal.show(html, {
            hideOnBackground: false,
            className: "editor-edit-row-popup"
        });




        // Now that the modal has been shown add an event listener
        // to the cancel button.
        document.getElementById("editor-cancel-edit-button").addEventListener("click", function (e)
        {
            editor_objects["' . $this->id . '"].editor_modal.hide();
            e.stopPropagation();
            e.preventDefault();

        }, false);






        //
        // Add an event listener to the window keydown event
        //
        window.addEventListener("keydown",function ()
        {
            if (e.keyCode === 27) {
                editor_objects["' . $this->id . '"].editor_canceledittext();
            }
        }, false);


        // Focus the first input element
        editor_objects["' . $this->id . '"].editor_modal.dialog.querySelector(":where(input, select, textarea):not(input[type=hidden])").focus();
    };

    //
    // Cancels the edit text functionality and returns to normality
    //
    editor_objects["' . $this->id . '"].editor_canceledittext = function ()
    {
        editor_objects["' . $this->id . '"].editor_modal.hide();
    }



    //
    // Select all of the checkboxes in the table
    //
    // ** Doesnt use the prefix **
    //
    editor_selectall= function (div)
    {
        var table = div.parentNode.parentNode.parentNode.parentNode;
        
        var checkboxes = table.querySelectorAll("input[type=checkbox]");
        
        if (checkboxes && checkboxes.length) {
            var state = checkboxes[0].checked;
            for (var i=0; i<checkboxes.length; ++i) {
                checkboxes[i].checked = !state;
            }
        }
    }








    //
    // Adds the necessary querystring parameter to the URL and
    // redirects to it in order to add a new row.
    //
    editor_objects["' . $this->id . '"].editor_addbuttonredirect = function (id)
    {
        var url = new URL(location.href);
        url.searchParams.set(\'' . $this->qs('add', false) . '-\' + id, id);
        
        var str = url.toString();
        
        // Remove the anchor
        str  = str.replace(/#[-_.a-z0-9]+$/,"");
        
        // Add the anchor back on based on the current id
        str += "#" + id;

        location.href = str;
    };








    //
    // This function facilitates selecting a row.
    //
    // @param object tr The row to select.
    //
    editor_selectrow = function (tr)
    {
        var checkbox = tr.querySelector("input[type=checkbox]");
        var radio    = tr.querySelector("input[type=radio]");
        
        if (checkbox) {
            checkbox.checked = true;
        } else if (radio) {
            radio.checked = true;
        }
    };








    //
    // This function toggles the selection of a row.
    //
    // ** Doesnt need the prefix **
    //
    // @param object tr The row to select
    //
    editor_togglerow = function (tr)
    {
        var checkbox = tr.querySelector("input[type=checkbox]");
        var radio    = tr.querySelector("input[type=radio]");

        if (checkbox) {
            checkbox.checked = !checkbox.checked;
        } else if (radio) {
            radio.checked = !radio.checked;
        }
    };







    //
    // Orders the data
    //
    // @param string column The column to order the results by
    //
    editor_objects["' . $this->id . '"].editor_order = function (id, column)
    {
        var href = location.href;
        var url  = new URL(href);

        if (href.indexOf("' . $this->qs('order_column', false) . '-" + id) > 0 && href.indexOf("' . $this->qs('order_column', false) . '-" + id + "="  + column) > 0) {

            // Change the dir to desc
            if (href.indexOf("' . $this->qs('order_dir', false) . '-" + id) > 0 && href.indexOf("' . $this->qs('order_dir', false) . '-" + id + "=asc") > 0) {
                url.searchParams.set("' . $this->qs('order_dir', false) . '-" + id, "desc");
            
            // Change the dir to none
            } else if (href.indexOf("' . $this->qs('order_dir', false) . '-" + id) > 0 && href.indexOf("' . $this->qs('order_dir', false) . '-" + id + "=desc") > 0) {
                url.searchParams.set("' . $this->qs('order_column', false) . '-" + id, "none");
                url.searchParams.set("' . $this->qs('order_dir', false) . '-" + id, "none");

            //} else {
            //    // Delete both ordering qs crumbs
            //    url.searchParams.delete("' . $this->qs('order_column', false) . '-" + id);
            //    url.searchParams.delete("' . $this->qs('order_dir', false) . '-" + id);
            }
        } else {

            var dir = "asc";
            if (!url.searchParams.get("' . $this->qs('order_dir', false) . '-" + id)) {
                if ("' . @$this->options['ordering_column'] . '" === column && ("' . @$this->options['ordering_dir'] . '" === "asc" || !"' . @$this->options['ordering_dir'] . '")) {
                    dir = "desc";
                } else if ("' . @$this->options['ordering_column'] . '" === column && "' . @$this->options['ordering_dir'] . '" === "desc") {
                    dir = "none";
                    column = "none";
                }
            }


            // Change order column to the one in the args,
            // with _dir set to asc
            url.searchParams.set("' . $this->qs('order_column', false) . '-" + id, column);
            url.searchParams.set("' . $this->qs('order_dir', false) . '-" + id   , dir);
        }

        url.hash = id;
        var str = url.toString();


        //console.log(str);
        location.href = str;
    };

</script>

<style>' . $this->css() . '</style>

<!-- The anchor for the editor is now added in the header function -->
<div class="editor-container-' . $this->id . '">
<div class="editor" style="width: 100%">
';


            //
            // If no rows were found then tell the user!
            //
            if (!$numRows) {
                $msg = 'Nothing found!';
                
                // Add a back link if this was the result of a
                // search (probably).
                if ($this->options['search'] AND !empty($_GET[$this->qs('search')])) {
                    // $msg .= ' [<a href="javascript:editor_objects[`' . $this->id . '`].editor_clearsearch(\'' . $this->id . '\')"><small>clear search</small></a>]';
                    $msg .= ' <small><small>[<a href="javascript:history.go(-1)">back to previous page</a>]</small></small>';
                }
                
                editor_messages::warning($this->id, $msg);
            }

            while (!empty($result) AND $row = $result->fetchArray(SQLITE3_ASSOC)) {

                //
                // Build the header of the table - the column
                // headers
                //
                if (empty($html)) {
                    $columns = array_keys($row);
                    $html = '
<script>
    // Calculate the column width here but this should take into
    // account the widths that have been given by the user. This
    // makes it feasible to allow users to specify  a small width
    // for (for example) an ID column.
    //var s = getComputedStyle(document.getElementById("editor-container"));
    //var width = parseFloat(s.width);

    // Take off all of the widths that have been specified by the
    // user.
    //width -= ' . array_sum($this->options['columns_widths']) . ';
    //editor_columnwidths = [];

    //(function ()
    //{
    //    var user = '.JSON_encode($this->options['columns_widths']).'
    //    
    //    for (var i=0; i<300; ++i) { // 300 is just an abitrary number
    //        if (typeof user[i] === "number") {
    //            editor_columnwidths[i] = user[i];
    //        } else {
    //            editor_columnwidths[i] = width / ' . (count($columns) - count($this->options['columns_widths'])) . ';
    //        }
    //    }
    //})();
</script>





<form action="' . $_SERVER['REQUEST_URI'] . '" method="get" id="editor_delete_form_' . $this->id . '">


' . (function ()
{
    $str = '';

    foreach ($_GET as $k => $v) {

        if ($k !== 'editor-id' AND is_string($v)) {
            $str .= sprintf(
                '<input type="hidden" name="%s" value="%s" />',
                $k,
                htmlentities($v)
            );
        }
    }
    
    return $str;
})() . '
<!-- TODO END -->  

<input type="hidden" name="' . $this->qs('id', false) . '" value="' . $this->id . '" />
<input type="hidden" name="' . $this->qs('paging') . '" value="' . (!empty($_GET[$this->qs('paging')]) ? $_GET[$this->qs('paging')] : 1) . '" />
<input type="hidden" name="' . $this->qs('action') . '" value="delete" />
<input type="hidden" name="' . $this->qs('search') . '" value="' . @htmlspecialchars($_GET[$this->qs('search')]) . '" />


<table>
    <thead>

        ' . (!empty($this->options['search'] AND !empty($this->getSearchColumns()) AND strpos($this->options['sql_select'], '{where}') > 0) ? '
            <tr class="editor-search-row">
                
                
                ' . (($this->options['checkboxes'] OR !empty($this->options['sql_delete'])) ? '<td>&nbsp;</td>' : '') . '
                
                
                <td colspan="999">
                    <div class="editor-search-input-container">
                        <input class="editor-search-input" id="' . $this->qs('search-input') . '" type="text" value="' . @htmlspecialchars($_GET[$this->qs('search')]) . '" onkeydown="if (event.keyCode === 13) {editor_objects[`' . $this->id . '`].editor_search(`' . $this->id . '`, this.value); event.preventDefault(); event.stopPropagation(); return false;}" placeholder="Search..." />
                        <span class="cancel-search-icon" id="' . $this->qs('cancel-search-icon') . '" onclick="editor_objects[`' . $this->id . '`].editor_cancelsearch(\'' . $this->id . '\')">&#11198;</span>
                        <span class="submit-search-icon" id="' . $this->qs('submit-search-icon') . '" onclick="editor_objects[`' . $this->id . '`].editor_search(\'' . $this->id . '\', document.getElementById(\'' . $this->qs('search-input') . '\').value); event.preventDefault(); event.stopPropagation(); return false;">&#x26B2;</span>
                    </div>
                    &nbsp;
                    <span id="search-help-link" onclick="editor_objects[`' . $this->id . '`].editor_showsearchhelp()" style="font-size: 10pt">help</span>
                    
                    <script>
                        // If there is text in the search box set
                        // the icon to the cross.
                        var id    = "' . $this->qs('search-input') . '";
                        var value = document.getElementById(id).value;
                        
                        if (value) {
                            document.getElementById("' . $this->qs('cancel-search-icon') . '").style.display = "inline";
                            document.getElementById("' . $this->qs('submit-search-icon') . '").style.display = "none";
                        } else {                    
                            document.getElementById("' . $this->qs('cancel-search-icon') . '").style.display = "none";
                            document.getElementById("' . $this->qs('submit-search-icon') . '").style.display = "inline";
                        }
                    </script>
                </td>
            </tr>' : '') . '






        <tr class="page-numbers-row">
            ' . ((!empty($this->options['sql_delete']) || !empty($this->options['checkboxes']) ) ? '<td></td>' : '') . '
            <td colspan="' . intval($this->options['paging_info_colspan']) . '" >
                Displaying ' . $this->options['paging_start'] . ' - ' . ($this->options['paging_start'] + (min($numRows, $this->options['paging_perpage'])) - 1) . ' of ' . $unpaged_numrows . ' results
                <div class="editor-page-numbers" style="float: right">
                    ' . $paging_page_numbers_string . '
                </div>
            </td>
        </tr>

        <tr>
';
                    if (!empty($this->options['sql_delete']) || !empty($this->options['checkboxes'])) {
                        $html .= '<td align="center">' . (empty($this->options['checkboxes_radio']) ? '<div style="transform: scale(1.3); cursor: pointer" width="30" onclick="editor_selectall(this)">&#9660;</div>' : '') . '</td>';
                    }
                    
                    //
                    // Create a function that does htmlspecialchars()
                    // if required. It's enabled by default.
                    $obj = $this;
                    $escape = function ($str, $column_name) use ($obj)
                    {
                        $hs = isset($obj->options['columns_escape'][$column_name]) ? $obj->options['columns_escape'][$column_name] : true;

                        return $hs ? htmlspecialchars($str) : $str;
                    };

                    foreach ($columns as $k => $c) {
                        
                        if ($c === $this->ordering_column AND $this->orderingIncludeExclude($c)) {
                            if (strtolower($this->ordering_dir) === 'desc') {
                                $order_pointer_character = '&#9662;';
                            } else {
                                $order_pointer_character = '&#9652;';
                            }
                        } else {
                            $order_pointer_character = '';
                        }

                        $html .= sprintf(
                            '<th %s style="%s" data-column-name="' . preg_replace('/[^a-z0-9]/', '', $c) . '" %s><div style="%s; white-space: nowrap;overflow: hidden;text-overflow: ellipsis" title="%s">%s%s</div></th>',
                            
                            !empty($this->options['columns_widths'][$c]) ? 'width="' . $this->options['columns_widths'][$c] . '" ' : '',
                            !empty($this->options['columns_widths'][$c]) ? 'width: ' . $this->options['columns_widths'][$c] . 'px' : '',
                            ($this->options['ordering_changeable']    AND    $this->orderingIncludeExclude($c)    AND    stripos($this->options_original['sql_select'], '{order}') !== false)        ?        'onmousemove="event.target.style.cursor = \'pointer\'" onclick="editor_objects[`' . $this->id . '`].editor_order(`' . $this->id . '`, `' . $c . '`)"' : '',
                            !empty($this->options['columns_widths'][$c]) ? 'width: ' . $this->options['columns_widths'][$c] . 'px' : '',
                            
                            strip_tags(!empty($this->options['columns_names'][$c]) ? $this->options['columns_names'][$c] : $c),
                            $escape(isset($this->options['columns_names'][$c]) ? $this->options['columns_names'][$c] : $c, $c),
                            $order_pointer_character
                        );
                    }
                    
                    $html .= '</tr></thead><tbody>';
                    
                    // Save the number of columns
                    $numColumns = count($columns);
                }

                //
                // Add the data to the table
                //
                $html .= '<tr data-index="' . $row[$this->options['primary_key']] . '" onclick="editor_togglerow(this)">';
                
                // Add the checkbox at the start of the row if
                // necessary
                if (!empty($this->options['sql_delete']) OR !empty($this->options['checkboxes'])) {
                    $html .= '<td class="checkbox-table-cell" data-row-index="' . $row[$this->options['primary_key']] . '" width="30" style="text-align: center">
                              <input type="' . ($this->options['checkboxes_radio'] ? 'radio' : 'checkbox') . '" onclick="event.stopPropagation()" name="' . $this->qs('delete') . '[]" value="' . $row[$this->options['primary_key']] . '" />
                              </td>';
                }
                    
                    
                    
                    
                    
                    
                    
                    $idx = 0;
                    foreach ($row as $k => $cell) {

                        $original = $cell;

                        // >Facilitate editing
                        //
                        // If editing is permiitted then add the
                        // event listeners for it.
                        
                        $edit_event_listener = 'data-row-index="' . $row[$this->options['primary_key']] . '"';

                        if (!empty($this->options['editable'])) {
                            $edit_event_listener .= ' onmousemove="this.style.cursor = `pointer`" on' . $this->options['editable_event'].'="editor_objects[`' . $this->id . '`].editor_addedittextinput(this, event); event.stopPropagation();"';
                        }

                        // Determine if there's a column callback
                        // that has been specified.
                        if (    !empty($this->options['columns_callbacks'])
                            AND !empty($this->options['columns_callbacks'][$k])
                            AND is_callable($this->options['columns_callbacks'][$k])
                           ) {
                            $cell = $this->options['columns_callbacks'][$k](
                                $this,
                                $row,
                                $k,
                                $cell
                            );
                        }

                        $html .= sprintf(
                        
                            // First argument to the sprintf 
                            // function...
                            '<td %s data-original="%s" data-id="' . $this->id . '" data-table-name="' . $this->database_table . '" data-primary-key="' . $this->options['primary_key'] . '" data-display="%s" data-column-name="' . htmlspecialchars($k) . '" %s style="%s" data-column-name="' . preg_replace('/[^a-z0-9]/', '', $k) . '"><div style="%s; white-space: nowrap;overflow: hidden;text-overflow: ellipsis" ' .
                            
                             '%s'

                            . '>%s</div></td>',
                             
                            // Other arguments to the sprintf function...
                            !empty($this->options['columns_widths'][$k]) ? 'width="' . $this->options['columns_widths'][$k] . '"' : '', 
                            
                            !empty($original) || $original == '0'
                              ? htmlspecialchars($original)
                              : '',
                            !empty($cell) ? htmlspecialchars($cell) : '',
                            $edit_event_listener,

                            !empty($this->options['columns_widths'][$k]) ? 'width: ' . $this->options['columns_widths'][$k] . 'px' : '', 
                            !empty($this->options['columns_widths'][$k]) ? 'width: ' . $this->options['columns_widths'][$k] . 'px' : '',

                             (
                              is_array($this->options['columns_tooltips'])
                               && isset($this->options['columns_tooltips'][$k])
                               && $this->options['columns_tooltips'][$k] == false // DOUBLE equals!!
                             )
                             ? '' : ('title="' . htmlspecialchars(!empty($original) ? $original : '') . '"'),
                             
                            $escape(!empty($cell) || $cell == '0' ? $cell : '', $k)
                        );
                        
                        $idx++;
                    }
                $html .= '</tr>';
            }











            // Print the header
            $this->header();

            // Print the main table that has just been generated
            echo $html . '</tbody>';

            if ($this->options['sql_insert'] OR $this->options['sql_delete'] OR !empty($this->options['actions'])) {
                echo   '<tfoot><tr>' . ( (!empty($this->options['sql_delete']) || !empty($this->options['checkboxes'])) ? '<td> &nbsp;</td>' : '') . '<td colspan="999">'
                        . '
<script>
    function editor_confirmdeleterows (id)
    {

        // Store the id on the function
        // instead of making a global
        // variable.
        editor_confirmdeleterows.id = id;

        editor_modal.show(`
Are you sure that you want to <b>delete</b> the selected row(s)?<br />

<p style="float: right; margin-bottom: 0">
    <button type="button" id="editor-deleterowsmodal-cancel" onclick="editor_modal.hide()">Cancel</button>
    <button type="button" id="editor-deleterowsmodal-ok" onclick="document.forms[\'editor_delete_form_\' + editor_confirmdeleterows.id].submit();">OK</button>
</p>
`, {hideOnBackground: false});

        document.getElementById(`editor-deleterowsmodal-cancel`).style.minHeight = ``;
        document.getElementById(`editor-deleterowsmodal-ok`).focus();
        
        return false;
    }
</script>
'
                        . ($this->options['sql_delete'] ? '<input class="editor-button-delete" ' . (!$numRows ? ' disabled="true" style="opacity: 0.35" ' : '') . 'type="submit" value="Delete" onclick="event.preventDefault();editor_confirmdeleterows(`' . $this->id . '`); return false;" >' : '') . '
                        </form> <!-- Close the delete form -->
                        <script>
                            function editor_confirmaddnewrow (id)
                            {
                                // Store the id on the function
                                // instead of making a global
                                // variable.
                                editor_confirmaddnewrow.id = id;

                                editor_modal.show(`
Are you sure that you want to add a new row?<br />

<p style="float: right; margin-bottom: 0">
    <button type="button" id="editor-addrowmodal-cancel" onclick="editor_modal.hide()">Cancel</button>
    <button type="button" id="editor-addrowmodal-ok" onclick="editor_objects[editor_confirmaddnewrow.id].editor_addbuttonredirect(editor_confirmaddnewrow.id);">OK</button>
</p>`, {hideOnBackground: false,});
                                document.getElementById(`editor-modaldialog-dialog`).style.minHeight = ``;
                                document.getElementById(`editor-addrowmodal-ok`).focus();
                                
                                return false;
                            }
                        </script>
                        '
                        . ($this->options['sql_insert'] ? '<button class="editor-button-add" onclick="editor_confirmaddnewrow(`' . $this->id . '`);return false">Add</button>' : '')
                        . $this->printActionButtons()
                        . '</td></tr></tfoot>';
            }
            
            echo '  </table>
                    </div>
                    </div>
                    
' .  /* Now fix the size of the columns */ '
<script>
    //var width = document.getElementById("editor-container-' . $this->id . '").offsetWidth;

    //table = document.getElementById("editor-container-' . $this->id . '").getElementsByTagName("tbody")[0];
    //table.style.width = "700px";
/*
    thead     = document.getElementById("editor-container-' . $this->id . '").getElementsByTagName("thead")[0];
    thead_trs = thead.getElementsByTagName("tr");
    thead_ths = thead_trs[1].getElementsByTagName("th");

    tbody     = document.getElementById("editor-container-' . $this->id . '").getElementsByTagName("tbody")[0];
    tbody_trs = tbody.getElementsByTagName("tr");

    // Loop thru each the <thead> row
    for (var i=0; i<thead_ths.length; ++i) {
        
        thead_ths[i].width                          = editor_objects["' . $this->id . '"].editor_columnwidths[i];
        thead_ths[i].style.width                    = editor_objects["' . $this->id . '"].editor_columnwidths[i] + "px";
        thead_ths[i].firstChild.style.width         = editor_objects["' . $this->id . '"].editor_columnwidths[i] + "px";
        thead_ths[i].firstChild.style.whiteSpace    = "nowrap";
        thead_ths[i].firstChild.style.overflow      = "hidden";
        thead_ths[i].firstChild.style.textOverflow  = "ellipsis";
    }

    // Now loop through the <tbody> <tr> tags. Then loop through all
    // of the td cells in that row. Check first though that it does
    // not have the checkbox-table-cell class.
    for (var i=0; i<tbody_trs.length; ++i) {
        
        var tds = tbody_trs[i].getElementsByTagName("td");
        
        for (var j=0,columnIndex=0; j<tds.length; ++j) {
            if (tds[j].className.indexOf("checkbox-table-cell") < 0) {
                var width = editor_objects["' . $this->id . '"].editor_columnwidths[columnIndex++];
                tds[j].width = width;
                tds[j].style.width = width + "px";
                
                tds[j].firstChild.style.width = width + "px";
            }
        }
    }

*/





/*
        // Ignore the first row
        if (i > 0) {
        
            // Get all of the td table cells
            tds = trs[i].getElementsByTagName("td");
            
            // Get all of the th table headers
            ths = trs[i].getElementsByTagName("th");

            // Apply the fixed size to the column headers
            for (var j=0; j<ths.length; j++) {
    
                ths[j].width = editor_objects["' . $this->id . '"].editor_columnwidths[j];
                ths[j].style.width = editor_objects["' . $this->id . '"].editor_columnwidths[j] + "px";
                
                // If the first child is a DIV then set the width on that too
                if (ths[j].firstChild.toString().indexOf("HTMLDivElement") > -1 ) {
                    ths[j].firstChild.style.width = editor_objects["' . $this->id . '"].editor_columnwidths[j] + "px";;
                }
            }

            // Apply the fixed size to the table cells
            for (var j=0; j<tds.length; j++) {
    
                tds[j].width = editor_objects["' . $this->id . '"].editor_columnwidths[j];
                tds[j].style.width = editor_objects["' . $this->id . '"].editor_columnwidths[j] + "px";
                
                // If the first child is a DIV then set the width on that too
                if (tds[j].firstChild.toString().indexOf("HTMLDivElement") > -1 ) {
                    tds[j].firstChild.style.width = editor_objects["' . $this->id . '"].editor_columnwidths[j] + "px";;
                }
            }
        }
*/
</script>
                    ';
            $this->footer();
        }


        //
        // Support for editing passwords has been removed. There is,
        // however, a demo that shows how you edit passwords in the
        // download archive.
        //
        public function isEditablePasswordColumn($column)
        {
            return false;
        }








        //
        // Returns an appropriate, associative array of the
        // columns that are allowed to be searched.
        //
        public function getSearchColumns ()
        {
            $columns = [];
            
            IF (is_array($this->options['search_columns'])) {
                foreach ($this->options['search_columns'] as $k => $v) {
                    if (is_numeric($k)) {
                        $columns[] = $v;
                    } else {
                        if ($this->options['search_columns'][$k]) {
                            $columns[] = $k;
                        }
                    }
                }
            } else {
                foreach ($this->database_columns as $c) {
                    $columns[] = $c;
                }
            }

            return $columns;
        }








        //
        // Returns the CSS
        //
        public function css ()
        {
            $css = '

        div.editor-container-' . $this->id . ' {}
        div.editor-container-' . $this->id . ' div.editor {display: inline-block;}
        div.editor-container-' . $this->id . ' div.editor form input[type=submit]{}
        div.editor-container-' . $this->id . ' div.editor form input[type=submit]:hover {cursor: pointer;}
        div.editor-container-' . $this->id . ' div.editor table tr th {background-color: #ccc; padding: 3px}
        div.editor-container-' . $this->id . ' div.editor table tbody tr td.checkbox-table-cell input[type=checkbox] {cursor: pointer; transform: scale(1.5);}
    
        /* Get rid of the outline/focus ring on checkbox*/
        div.editor-container-' . $this->id . ' div.editor table tbody input[type=checkbox] {outline: none;}
    
        /* Make the rows change color when hovered over */
        div.editor-container-' . $this->id . ' div.editor table tbody tr:hover {background-color: #eee;}
';
        if (empty($this->options['sql_delete']) AND empty($this->options['checkboxes']) ) {
            // Nada
        } else {
            $css .= "\r\n div.editor-container-{$this->id} div.editor table tbody tr:hover td:nth-child(1) {background-color: white !important}\r\n";
        }

            $css .= '
        div.editor-container-' . $this->id . ' div.editor input[type=submit]:hover,
        div.editor-container-' . $this->id . ' div.editor button:hover {cursor: pointer;}
        div.editor-container-' . $this->id . ' div.editor input.editor-button-delete {color: red; font-weight: bold;}
        div.editor-container-' . $this->id . ' div.editor div.editor-page-numbers a {text-decoration: none; outline: none;}
        div.editor-container-' . $this->id . ' div.editor div.editor-page-numbers span.paging-links-current{font-weight: bold;}

        div.editor-container-' . $this->id . ' div.editor .editor-search-input {margin: 5px; padding: 3px; font-size: 120%; width: 200px;; border: 1px solid #666;margin: 0;}
        div.editor-container-' . $this->id . ' div.editor div.editor-search-input-container{position: relative; width: 200px; display: inline-block;}

        div.editor-container-' . $this->id . ' div.editor div.editor-search-input-container input {box-sizing: border-box; position: relative;}
        div.editor-container-' . $this->id . ' div.editor div.editor-search-input-container span.cancel-search-icon {position: absolute; right: 0; top: 50%; cursor: pointer; transform: translateX(-8px) translateY(-49%) scale(1.2); line-height: 25px; color: #666;}
        div.editor-container-' . $this->id . ' div.editor div.editor-search-input-container span.submit-search-icon {position: absolute; right: 0; top: 50%; cursor: pointer; transform: translateX(-12px) translateY(-48%) scale(1.8) rotate(45deg); line-height: 25px;color: #666;}
        div.editor-container-' . $this->id . ' div.editor span#search-help-link {color: blue; cursor: pointer;}

        button#editor-addrowmodal-cancel {cursor: pointer; opacity: 0.5; padding: 3px; font-size: 120%;}
        button#editor-addrowmodal-ok {cursor: pointer; color: green; font-weight: bold; padding: 3px; font-size: 120%}
        button#editor-deleterowsmodal-cancel {cursor: pointer; opacity: 0.5; font-size: 120%;}
        button#editor-deleterowsmodal-ok {cursor: pointer;color: red; font-weight: bold; font-size: 120%;}
';

            // Add user specified style
            if (!empty($this->options['style'])) {
                foreach ($this->options['style'] as $v) {
                    $css .= "\r\n        " . $v . "\r\n";
                }
            }
            
            return $css . "    ";
        }








    //
    // Adds the prefix to the start and the ID to the end of the
    // given QS parameter name. Takes the pain out of adding the
    // QS prefix and ID to the QS param. Call it like this:
    //
    // $this->qs('paging-page)
    //
    // @param string $qs       The requested name of the qs crumb.
    // @param bool   $appendID Whether to append thwe ID to the
    //                         crumb. Defaults to true.
    // @param string $id       You can pass in the ID to use if
    //                         you wih instead of the function
    //                         getting it from this object.
    //
    function qs ($qs, $appendID = true, $id = null)
    {
        // Change underscores to hyphens - looks nicer
        $qs = str_replace('_','-',$qs);

        return sprintf('%s%s%s',
            'editor-',
            $qs,
            $appendID ? '-' . (!empty($id) ? $id : $this->id) : ''
        );
    }








        //
        // Prints user defined buttons
        //
        public function printActionButtons ()
        {
            $str = '';

            foreach ($this->options['actions'] as $a) {
                if (is_array($a)) {
                    $str .= '&nbsp;<button type="button" onclick="' . str_ireplace('{id}',"'" . $this->id . "'",$a[1]) . '">' . $a[0] . '</button>';
                } else if (is_string($a)) {
                    $str .= '&nbsp;';
                    $str .= str_ireplace('{id}', "'" . $this->id . "'", $a);
                }
            }
            
            return $str;
        }
    }








    //
    // Debug functions
    //
    if (!function_exists('p')) {
        function p ($var, $exit = true)
        {
            echo '<pre>';
            $str = print_r($var, true);
            echo htmlspecialchars($str);
            echo '</pre>';
            
            if ($exit) {
                exit;
            }
        }
    }
    
    if (!function_exists('v')) {
        function v ($var, $exit = true)
        {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
            
            if ($exit) {
                exit;
            }
        }
    }




    //
    // Because output may be sent by the user before the editor is
    // run a JS redirect is used instead of an HTTP redirect. Takes
    // a few extra milliseconds but, somehow, I don't think that
    // you'll notice.
    //
    function editor_redirect ($url, $exit = true)
    {
        // Can't issue a header() here as output may have already
        // been sent.
        echo '<script>window.location.href = "'. $url .'";</script>';
        
        if ($exit) {
            exit;
        }
    }









    /**
    * This class allows easy and attractive messages to be displayed to users
    */
    class editor_messages
    {
        /**
        * Shows a nicely formatted success message
        *
        * @param string $msg The success message
        */
        public static function showsuccess($msg, $escape = true)
        {
            $id = md5(time() . microtime());
    ?>
    <div id="<?php echo $id ?>" class="messages" style="margin: 10px; background-color: #0f04;border-left: 10px solid #0a0;padding: 10px;line-height: 25px">
        <?php echo $escape ? htmlspecialchars($msg) : $msg ?>
    </div>
<?php
        }




        /**
        * Shows a nicely formatted warning message
        *
        * @param string $msg The warning message
        */
        public static function showwarning($msg, $escape = true)
        {
            $id = md5(time() . microtime());
?>
    <div id="<?php echo $id ?>" class="messages" style="margin: 10px; background-color: #fff7d0;padding: 10px;border-left: 10px solid #e7c000;line-height: 25px">
        <?php echo $escape ? htmlspecialchars($msg) : $msg?>
    </div>
<?php
        }




        /**
        * Shows a nicely formatted error message
        *
        */
        public static function showerror($msg, $escape = true)
        {
            $id = md5(time() . microtime());

?>
    <div id="<?php echo $id ?>" class="messages" style="margin: 10px; background-color: #f004;border-left: 10px solid #f00;padding: 10px;line-height: 25px">
        <?php echo $escape ? htmlspecialchars($msg) : $msg ?>
    </div>
<?php
        }




        /**
        * Shows a nicely formatted error message
        *
        */
        public static function shownotice($msg, $escape = true)
        {
            $id = md5(time() . microtime());

?>
    <div id="<?php echo $id ?>" class="messages" style="margin: 10px; background-color: #00f3;border-left: 10px solid #00f8;padding: 10px;line-height: 25px; border-top: 1px solid #ccc; border-right: 1px solid #ccc; border-bottom: 1px solid #ccc">
        <?php echo $escape ? htmlspecialchars($msg) : $msg ?>
    </div>
<?php
        }




        /**
        * This function adds an error message
        */
        public static function error($id, $msg)
        {
            $_SESSION['messages'][$id]['error'][] = $msg;
        }




        /**
        * This function adds a warning message
        */
        public static function warning($id, $msg)
        {
            $_SESSION['messages'][$id]['warning'][] = $msg;
        }




        /**
        * This function adds a success message
        */
        public static function success($id, $msg)
        {
            $_SESSION['messages'][$id]['success'][] = $msg;
        }




        /**
        * This function adds a notice message
        */
        public static function notice($id, $msg)
        {
            $_SESSION['messages'][$id]['notice'][] = $msg;
        }




        /**
        * This function shows all messages that are stored in the
        * session for the given id.
        * 
        * @param string $id The ID of the messages. Could be any
        *                   string.
        * @param bool   $escape Whether to escape the messaqges with
        *                       htmlspecialchars().
        */
        public static function display ($id, $escape = false)
        {
            // Show success messages
            if (!empty($_SESSION['messages'][$id]['success'])) {
                foreach ($_SESSION['messages'][$id]['success'] as $msg) {
                    editor_messages::showsuccess($msg, $escape);
                }
            }




            // Show warning messages
            if (!empty($_SESSION['messages'][$id]['warning'])) {
                foreach ($_SESSION['messages'][$id]['warning'] as $msg) {
                    editor_messages::showwarning($msg, $escape);
                }
            }
            
            // Show error messages
            if (!empty($_SESSION['messages'][$id]['error'])) {

                foreach ($_SESSION['messages'][$id]['error'] as $msg) {
                    editor_messages::showerror($msg, $escape);
                }
            }




            // Show notice messages
            if (!empty($_SESSION['messages'][$id]['notice'])) {
                foreach ($_SESSION['messages'][$id]['notice'] as $msg) {
                    editor_messages::shownotice($msg, $escape);
                }
            }




            /**
            * Get rid of the messages now that they've been
            * displayed.
            */
            $_SESSION['messages'][$id] = array();
        }
    }








    //
    // A URL class that encapsulates a URL and has various
    // functions that pertain to URLs.
    //
    class editor_url
    {
        // The URL
        public $url;

        //
        // Constructor
        //
        function __construct ($url = null)
        {
            if (!$url) {
                $url = $_SERVER['REQUEST_URI'];
            }
            
            $this->url = urldecode($url);
        }
        
        
        
        
        //
        // Adds a querystring parameter to the URL. Doesn't
        // replace it if it's already present.
        //
        public function addquerystringparameter ($name, $value)
        {
            // Temporarily remove the anchor
            $anchor = $this->removeanchor();
            
            $value = urlencode($value);

            // Add the query string parameter
            if (strpos($this->url, '?') > 0) {
                $this->url = $this->url . "&{$name}={$value}";
            } else {
                $this->url = $this->url . "?{$name}={$value}";
            }
            
            // Add the anchor back on
            $this->setanchor($anchor);
        }
        
        
        
        
        
        //
        // Sets a query string parameter - changing the one that's
        // already there if one is present.
        //
        public function setquerystringparameter ($name, $value)
        {
            $this->removequerystringparameter($name);
            $this->addquerystringparameter($name, $value);
        }




        //
        // Removes a querystring parameter. All occurrences of
        // the parameter are removed.
        //
        public function removequerystringparameter($name)
        {
            $this->clean();

            $anchor = $this->removeanchor();
            
            if (   strpos($this->url, $name . '=') > 0
                OR strpos($this->url, $name . '[]=') > 0) {

                $this->url = preg_replace('|' . preg_quote($name) . '(\\[\\])?=[^&]*|is', '', $this->url);
            }
            $this->clean();

            $this->setanchor($anchor);
        }




        //
        // Sets the anchor.
        //
        public function setanchor ($anchor)
        {
            $this->removeAnchor();
            
            if ($anchor) {
                $this->url .= '#' . $anchor;
            }
        }




        //
        // Removes the anchor
        //
        public function removeanchor()
        {
            $regexp = '/(#[-_0-9a-z]+)$/';
            
            // First, get hold of the anchor
            preg_match($regexp, $this->url, $matches);            
            
            if (!empty($matches[1])) {
                $anchor = ltrim($matches[1], '#');
            
                $this->url = preg_replace($regexp,'',$this->url);
            }
            
            return !empty($anchor) ? $anchor : null;
        }
        
        //
        // Cleans the URL by removing multiple ampersands and/or
        // question marks where they're not necessary. And also
        // it removes redundant query string parameters of the
        // form 4567=
        //
        public function clean()
        {
            // Remove repeated ampersands
            $this->url = preg_replace('/&+/','&',$this->url);
            
            // Remove repeated hashes with just 1
            $this->url = preg_replace('/#+/','#',$this->url);
            
            // Remove question mark followed by ampersand
            $this->url = preg_replace('/\?&/','?',$this->url);
            
            // Remove question mark followed by hash - is no query string
            //$this->url = preg_replace('/\?#/','#',$this->url);
            
            // Remove trailing amperands and hash
            $this->url= preg_replace('/[\&#]$/','', $this->url);
            
            // Remove amperands followed by a hash
            $this->url= preg_replace('/\&#[a-z0-9]/','#', $this->url);
            
            // Remove this sequence
            $this->url= preg_replace('/\?#+&+/','?', $this->url);
            
            // Remove trailing empty hases
            trim($this->url, '#');
        }




        //
        // Returns the URL.
        //
        public function get()
        {

            $this->clean();

            return $this->url;
        }
    }




    //
    // This is code that runs when the library is included - not
    // simply when the editor is created. This is so that the
    // session can be started early in the page before any output
    // might be sent.
    //
    // Need to start a session so that notices and errors
    // work correctly. But only do this if the headers
    // haven't already been sent.
    //
    if (!headers_sent()) {
        @session_start();
    }
?>