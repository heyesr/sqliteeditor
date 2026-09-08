<?php
    require('../common.php');
    
    $sqle = new SQLiteEditorDownload();
    
    $sqle->header();
    $sqle->heading('A demo showing a list of accounts');
?>

<SQLiteEditor::source>
    <div style="width: 800px; margin-left: auto; margin-right: auto">
<?php
    // Include the SQLiteEditor.php file at the top of the
    // page  before any output is sent to the browser.

    $editor = new SQLiteEditor([
        'filename'     => './sqliteeditor.db',
        'table'        => 'accounts',
        'sql_select'   => "SELECT id,
                                  username,
                                  created,
                                  forename,
                                  surname,
                                  '' AS `actions`
                             FROM accounts
                             WHERE {where}
                                   {order}",
        'columns_names' => [
            'id'       => 'ID',
            'username' => 'Username',
            'created'  => 'Created',
            'forename' => 'Forename',
            'surname'  => 'Surname',
            'actions'  => ''
        ],
        'columns_escape' => ['actions' => false],
        'columns_callbacks' => [
            'actions' => function ($editor, $row, $name, $value)
            {
                return sprintf('<select onchange="alert(\'Requested to view the account at the URL: \' + this.value)"><option></option><option value="account.php?id=' . $row['id'] . '">View account</option></select>');
            }
        ],
        'columns_widths' => [
            'id' => 50,
            '*' => 140
        ],
        'paging_info_colspan' => 5,
        'ordering_exclude' => ['actions'],
        'style' => [
            'div.editor {line-height: initial;}',
            'div.editor table thead tr th:nth-child(7) {background-color: white !important;}',
            'div.editor table tr :where(td, th):nth-child(1) {width: 30px !important;}',
            'div.editor table tbody tr:hover td:not(td:nth-child(7)) {background-color: #eee;}',
            'div.editor table tfoot :where(button,input) {font-size: 100%;}'
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