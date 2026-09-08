<?php
    require('../common.php');
    
    $sqle = new SQLiteEditorDownload();
    
    $sqle->header();
    $sqle->heading('An editable demo using a select');
?>



<p>
    In this demo the usual text input that appears when you edit 
    the <b>username</b> cell is actually a select dropdown list. This can be done by
    setting an array of options that you want to see using the
    <i>editable_types_select_options</i> configuration option.
</p>

<p>
    The <i>editable_types_select_options</i>  property can also be a
    function that returns an array of the possible options. In the
    configuration of the editor you specify the name of the function
    as a string, as you can see in the source code below. The
    function then returns an array of the options for that cell.
</p>

<p>
    The <i>editable_types_select_options</i> option can also be a simple comma
    separated list of the options that you wish to see in the
    dropdown and also an SQL query which fetches the list of items
    that appear in the dropdown list. See
    <a href="https://www.rgraph.net/sqliteeditor/api.html#property-name-editable_types_select_options">the documentation</a> for the
    exact details of the format to use.
</p>

<SQLiteEditor::source>
<script>
    function fetchUsernames(column)
    {
        return ['','henryb','kevinj','peterf','jospehk','lyrav','olgaf'];
    }
</script>

<div style="width: 800px; margin-left: auto; margin-right: auto">
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
            'forename' => 'First name',
            'surname'  => 'Second name',
            'created'  => 'Created'
        ],
        'columns_widths' => [
            'id' => 50
        ],
        'editable' => [
            'username' => true,
            'forename' => true,
            'surname'  => true
        ],
        'editable_types' => [
            'username' => 'select',
            'forename' => 'select',
            'surname'  => 'select'
        ],
        'editable_types_select_options' => [
            'username' => "function:fetchUsernames",
            'forename' => ['John Campbell::johnc','Barry McGuigan::barrym','Luis Kapoldi::luisk'],
            'surname'  => 'Campbell::cam,Digworth::dig,Billingham::bill,Constantine::const,Jamie'
        ],
        'style'  => [
            'div.editor {line-height: initial;}',
            'div.editor tbody td div{max-width: 200px;}',
            'div.editor-modaldialog-dialog form table td :where(input, select, textarea) {font-size: 14pt  !important;}'
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