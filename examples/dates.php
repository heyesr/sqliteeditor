<?php
    require('../common.php');
    
    $sqle = new SQLiteEditorDownload();
    
    $sqle->header();
    $sqle->heading('Dates demo');
?>

<p>
    Fetching date columns as Unix times so that they can be formatted
    more easily. In this example the SQLite date column, <i>created</i>,
    is fetched as a unix timestamp so that it can then be formatted
    easily using the <i>columns_callback</i> property that sets a PHP
    function to format the column. The output of the function is
    then displayed to the user. Doing it this way means that
    ordering still works correctly when you click the column header
    because the ordering takes place at the SQL level so it's done
    before the time that the PHP callback function runs.
</p>

<p>
    You could also format the date with SQLite itself if you're
    familiar with its date formatting functions.
</p>

<SQLiteEditor::source>
<?php
    // Include the SQLiteEditor.php file at the top of the
    // page  before any output is sent to the browser.

    $editor = new SQLiteEditor([
        'filename'   => './sqliteeditor.db',
        'table'      => 'accounts',
        'sql_select' => "SELECT id,
                                forename,
                                surname,
                                strftime('%s', created) AS `unixtime`
                           FROM accounts
                          WHERE {where}
                                {order}",
        'columns_callbacks' => [
            'unixtime' => function ($editor, $row)
            {
                return date('H:i, jS F Y', (int)$row['unixtime']);
            }
        ],
        'columns_names' => [
            'id'       => 'ID',
            'forename' => 'First name',
            'surname'  => 'Second name',
            'unixtime' => 'Date and time'
        ],
        'columns_widths' => [
            'id' => 65,
            'unixtime' => 250,
        ],
        'paging_perpage' => 10,
        'style' => [
            'div.editor table tbody tr td:nth-child(2) {text-align: center;}',
        ],
        'sql_insert' => false,
        'sql_delete' => false,
        'checkboxes' => true,
        'styles' => [
            'div.editor {line-height: initial;}',
        ]
    ]);
    
    $editor->draw();
?>
</SQLiteEditor::source>

<?php
    $sqle->source();
    $sqle->footer();
?>