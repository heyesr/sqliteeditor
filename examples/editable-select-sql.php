<?php
    require('../common.php');
    
    $sqle = new SQLiteEditorDownload();
    
    $sqle->header();
    $sqle->heading('An editable demo using a select which has been populated by an SQL query');
?>

<p>
    In this demo the options for the HTML select form input are
    retrieved by an SQL query.
</p>

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
            'surname' => true
        ],
        'editable_types' => [
            'surname'  => 'select'
        ],
        'editable_types_select_options' => [
            'surname'  => "sql:SELECT DISTINCT surname FROM accounts ORDER BY surname"
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