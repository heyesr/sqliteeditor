<?php
    require('../common.php');
    
    $sqle = new SQLiteEditorDownload();
    
    $sqle->header();
    $sqle->heading('An editable demo showing a range input');
?>

<SQLiteEditor::source>
<div style="width: 800px; margin-left: auto; margin-right: auto">
<?php    
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
            'username' => true
        ],
        'editable_types' => [
            'username' => 'range'
            //'username' => 'range,step=5,min=50,max=200'
        ],
        //'editable_callback' => function ($obj, &$data)
        //{
        //    return false;
        //},
        'columns_names' => [
            'id'       => 'ID',
            'username' => 'Quantity',
            'forename' => 'Forename',
            'surname'  => 'Surname',
            'created'  => 'Created'
        ],
        'columns_widths' => [
            'id' => 50,
            '*' => 200
        ],
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