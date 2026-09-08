<?php
    require('../common.php');
    
    $sqle = new SQLiteEditorDownload();
    
    $sqle->header();
    $sqle->heading('An editable demo using a textarea');
?>

<p>
    This demo  shows you how you can use a textarea 
    for editing a column by setting the columns_type
    option appropriately (in the example below the column
    in question is the username column). It also uses a
    columns_callback
    function so that when it's displayed the text doesn't
    throw off the column sizes in the table.
</p>

<SQLiteEditor::source>
<div style="width: 75%; margin-left: auto; margin-right: auto">
<?php    
    // Include the SQLiteEditor.php file at the top of the
    // page  before any output is sent to the browser.

    $editor = new SQLiteEditor([
        'filename'      => './sqliteeditor.db',
        'table'         => 'accounts',
        'sql_select'    => 'SELECT id,
                                   username,
                                   forename,
                                   surname,
                                   created
                              FROM accounts
                             WHERE {where}
                             {order}',
        'sql_insert' => function ($obj)
        {
            $obj->sqlite->query("INSERT INTO accounts (id, created) VALUES(NULL, DATETIME(CURRENT_DATE))");
        },
        'editable'       => [
            'username' => true
        ],
        'editable_types' => [
            'username' => 'textarea'
        ],
        'columns_names' => [
            'id'       => 'ID',
            'username' => 'Username',
            'forename' => 'Forename',
            'surname'  => 'Surname',
            'created'  => 'Created'
        ],
        'columns_widths' => [
            'id' => 50,
            '*' => 200
        ],
        'columns_callbacks' => [
            'username' => function ($obj, $columns, $column, $value)
            {
                return rtrim(substr($value ? $value : '', 0, 20)) . (strlen($value ? $value : '') > 15 ? '...' : '');
            }
        ],
        'style' =>[
            'div.editor {line-height: initial;}',
            'tfoot input.editor-button-delete, tfoot button {font-size: 120%;}'
        ]
    ]);
    
    $editor->draw();
?>
</div>
</SQLiteEditor::source>


<?php
    $sqle->source();
    $sqle->footer();
?>