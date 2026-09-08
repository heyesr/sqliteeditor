<?php
    require('../common.php');
    
    $sqle = new SQLiteEditorDownload();
    
    $sqle->header();
    $sqle->heading('An editable demo using a number input');
?>

<p>
    The username column is set to use a number input type.
</p>

<SQLiteEditor::source>
<?php
    // Include the SQLiteEditor.php file at the top of the
    // page  before any output is sent to the browser.

    $editor = new SQLiteEditor([
        'filename'      => './sqliteeditor.db',
        'table'         => 'accounts',
        'sql_select'    => "SELECT id, username, forename, surname, created FROM accounts WHERE {where} {order}",
        'columns_names' => [
            'id'       => 'ID',
            'username' => 'Username',
            'forename' => 'Forename',
            'surname'  => 'Surname',
            'created'  => 'Created'
        ],
        'columns_widths' => [
            'id' => 50,
            '*' => 100
        ],
        'editable' => [
            'username' => true
        ],
        'editable_types' => [
            'username' => 'number'
        ],
        'style'  => [
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