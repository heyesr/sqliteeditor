<?php
    require('../common.php');
    
    $sqle = new SQLiteEditorDownload();
    
    $sqle->header();
    $sqle->heading('An editable demo using radio buttons');
?>

<p>
    In this demo the name can be chosen from a list of radio
    buttons.
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
            'username' => 'Username',
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
            'username' => 'radio'
        ],
        'editable_types_radio_options' => [
            //'username' => "sql:SELECT username FROM accounts"
            //'username' => 'function:getUsernames'
            //'username' => ['Richard Heyes::heyesr','Gary Barlow::garyb','Fred Bloggs::fredb','Luis Carroll::luisc']
            'username' => 'heyesr,hartnettj,biggsj,killer,joshh'
        ]
    ]);
    
    $editor->draw();
?>
</SQLiteEditor::source>

<?php
    $sqle->source();
    $sqle->footer();
?>