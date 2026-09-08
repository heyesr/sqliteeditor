<?php
    require('../common.php');
    
    $sqle = new SQLiteEditorDownload();
    
    $sqle->header();
    $sqle->heading('An editable demo');
?>

<SQLiteEditor::source>
<div style="width: 800px; margin-left: auto; margin-right: auto">
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
        'editable'      => [
            'username' => true,
            'forename' => true,
            'surname'  => true,
            'created'  => true
        ],
        'editable_types' => [
            'username' => 'text'
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
        'paging_perpage' => 10,
        'style' =>[
            'div.editor {line-height: initial;}',
            'div.editor table tr td[data-column-name=created] div {color:gray; text-align: center; font-style: italic;}',
            'div.editor :where(button, input) {font-size: 16pt;}',
            'div.editor input[type=checkbox]{cursor: pointer;transform:scale(1.5) !important;}'
            
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