<?php
    require('../common.php');
    
    $sqle = new SQLiteEditorDownload();
    
    $sqle->header();
    $sqle->heading('An editable demo using checkboxes');
?>

<p>
    In this demo the fruits can be chosen from a list of checkboxes.
</p>

<script>
    function getFruits ()
    {
        return ['kiwi','mango','melon'];
    }
</script>

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
            'username' => 'Fruits',
            'forename' => 'Forename',
            'surname'  => 'Surname'
        ],
        'columns_widths' => [
            'id' => 75,
            '*' => 250
        ],
        'editable'  => [
            'username' => true
        ],
        'editable_types' =>[
            'username' => 'checkbox'
        ],
        'editable_types_checkbox_options' => [
            //'username' => "sql:SELECT username FROM accounts"
            //'username' => 'function:getFruits'
            //'username' => ['Apple::apple','orange','Banana (best fruit ever?)::banana']
            'username' => 'apple,orange,banana,pomegranite,kiwi'
        ]
    ]);
    
    $editor->draw();
?>
</SQLiteEditor::source>

<?php
    $sqle->source();
    $sqle->footer();
?>