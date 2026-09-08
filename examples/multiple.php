<?php
    require('../common.php');
    
    $sqle = new SQLiteEditorDownload();
    
    $sqle->header();
    $sqle->heading('Multiple editors on one page');
?>

<SQLiteEditor::source>
<?php
    // Include the SQLiteEditor.php file at the top of the
    // page  before any output is sent to the browser.

    echo '<h2>First editor</h2>';

    $editor1 = new SQLiteEditor([
        'filename'       => './sqliteeditor.db',
        'table'          => 'accounts',
        'editable'       => [
            'username' => true,
            'forename' => true,
            'surname'  => true
        ],
        'columns_callbacks' => [
            'username' => function ($obj, $columns, $column, $value)
            {
                return rtrim(substr($value ? $value : '', 0, 20)) . (strlen($value ? $value : '') > 15 ? '...' : '');
            }
        ],
        'style'         => [
            'div.editor {line-height: initial;}',
            'div.editor-container-editor1 table {width: 75%;margin-left: auto; margin-right: auto;}'
        ]
    ]);
    
    $editor1->draw();



    echo '<h2>Second editor</h2>';
    
    $editor2 = new SQLiteEditor([
        'filename'     => './sqliteeditor.db',
        'table'        => 'accounts',
        'editable'     => [
            'forename' => true,
            'surname'  => true
        ],
        'columns_callbacks' => [
            'username' => function ($obj, $columns, $column, $value)
            {
                return rtrim(substr($value ? $value : '', 0, 20)) . (strlen($value ? $value : '') > 15 ? '...' : '');
            }
        ],
        'style' => [
            'div.editor-container-editor2 table {width: 75%;margin-left: auto; margin-right: auto;}'
        ]
    ]);
    
    $editor2->draw();








    echo '<h2>Third editor</h2>';
    
    $editor3 = new SQLiteEditor([
        'filename'       => './sqliteeditor.db',
        'table'          => 'accounts',
        'editable'       => [
            'username' => true,
            'forename' => true,
            'surname'  => true
        ],
        'sql_select'     => "SELECT id,
                                    forename,
                                    surname,
                                    username,
                                    created
                               FROM accounts
                              WHERE INSTR(username, 'a')
                                AND {where}
                                    {order}",
        'sql_insert' => "INSERT INTO accounts (id, username) VALUES(NULL, 'andrewo')",
        'columns_callbacks' => [
            'username' => function ($obj, $columns, $column, $value)
            {
                return rtrim(substr($value ? $value : '', 0, 20)) . (strlen($value ? $value : '') > 15 ? '...' : '');
            }
        ],
        'style' => [
            'div.editor-container-editor3 table {width: 75%;margin-left: auto; margin-right: auto;}'
        ]
    ]);
    
    $editor3->draw();








    echo '<h2>Fourth editor</h2>';
    
    $editor4 = new SQLiteEditor([
        'filename'       => './sqliteeditor.db',
        'table'          => 'accounts',
        'editable'       => [
            'username' => true,
            'forename' => true,
            'surname'  => true
        ],
        'columns_callbacks' => [
            'username' => function ($obj, $columns, $column, $value)
            {
                return rtrim(substr($value ? $value : '', 0, 20)) . (strlen($value ? $value : '') > 15 ? '...' : '');
            }
        ],
        'style' => [
            'div.editor-container-editor4 table {width: 75%;margin-left: auto; margin-right: auto;}'
        ]
    ]);
    
    $editor4->draw();
?>
</SQLiteEditor::source>

<?php
    $sqle->source();
    $sqle->footer();
?>