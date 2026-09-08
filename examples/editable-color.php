<?php
    require('../common.php');
    
    $sqle = new SQLiteEditorDownload();
    
    $sqle->header();
    $sqle->heading('An editable demo using a color selector');
?>

<p>
    In this demo the username column is reused as the Favourite color
    column - so thats why you'll see usernames in the color column! This
    doesn't affect the editing of the column though.
</p>

<SQLiteEditor::source>
<?php
    // Include the SQLiteEditor.php file at the top of the
    // page  before any output is sent to the browser.
    
    $editor = new SQLiteEditor([

        'filename'      => './sqliteeditor.db',
        'table'         => 'accounts',

        'sql_select'    => "SELECT id, username, forename, surname FROM accounts WHERE {where} {order}",
        'columns_names' => [
            'id'       => 'ID',
            'username' => 'Favourite color',
            'forename' => 'Forename',
            'surname'  => 'Surname'
        ],
        'columns_widths' => [
            'id' => 50,
            '*' => 250
        ],
        'editable'  => [
            'username' => true
        ],
        'editable_types' =>[
            'username' => 'color'
        ],
        'style' => [
            'div.editor {line-height: initial;}'
        ]
    ]);
    
    $editor->draw();
?>
</SQLiteEditor::source>

<?php
    $sqle->source();
    $sqle->footer();
?>