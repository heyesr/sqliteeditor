<?php
    require('../common.php');
    
    $sqle = new SQLiteEditorDownload();
    
    $sqle->header();
    $sqle->heading('An editable demo using an HTML5 date input');
?>



<SQLiteEditor::source>
<?php
    // Include the SQLiteEditor.php file at the top of the
    // page  before any output is sent to the browser.
    
    $editor = new SQLiteEditor([
        'filename'      => './sqliteeditor.db',
        'table'         => 'accounts',
        'sql_select'    => "SELECT id,
                                   username,
                                   forename,
                                   surname,
                                   created
                              FROM accounts
                             WHERE {where}
                                   {order}",
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
        'editable' => [
            'created' => true
        ],
        'editable_types' => [
            'created' => 'date',
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