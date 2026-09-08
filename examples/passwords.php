<?php
    require('../common.php');
    
    $sqle = new SQLiteEditorDownload();
    
    $sqle->header();
    $sqle->heading('An example of setting a password');
?>


<SQLiteEditor::source>
<script>
    //
    // This is the dialog that pops up asking the user to change a
    // password. You can change this dialog to look as you please.
    //
    function showPasswordDialog (id)
    {
        var rows = document.querySelectorAll('div.editor table tr');

        for (var i=0; i<rows.length; ++i) {
            if (rows[i].querySelector('input[type=radio]') && rows[i].querySelector('input[type=radio]').checked) {
                var row = rows[i];
                var tds = row.querySelectorAll('td');
                
                for (var j=0; j<tds.length; ++j) {
                    if (tds[j].getAttribute('data-column-name') === 'forename') {
                        var forename = tds[j].getAttribute('data-original');
                    }
                }
            }
        }

        editor_modal.show(`
            <p>Change password for user: ${forename}</p>
            <form action="setpassword.php" method="get">
                
                <input type="hidden" name="id" value="${id}" />
                
                <i>New password:</i><br />
                <input type="password" name="new_password" style="width: 100%"/><br /><br />
                
                <i>Confirm password:</i><br />
                <input type="password" name="confirm_password" style="width: 100%" />
                
                <p />
                
                <p style="text-align: center">
                    <input type="submit" value="Change password" style="font-size: 16pt; cursor: pointer" onclick="alert('This is just a demo!'); return false" /><br />
                </p>
            </form>
        `, {width:400});
    }
</script>







<!-- And below is the code for the editor -->





    

<div style="width: 500px; margin-left: auto; margin-right: auto">
<?php
    require_once('../SQLiteEditor.php');

    $editor = new SQLiteEditor([
        'filename'      => './sqliteeditor.db',
        'table'         => 'passwords',
        'sql_insert'    => false,
        'sql_delete'    => false,
        'sql_select'    => "SELECT id,
                                   forename,
                                   surname,
                                   username
                              FROM passwords
                             WHERE {where}
                                   {order}",
        'checkboxes'        => true,
        'checkboxes_radio'  => true,
        'columns_names'     => [
            'id'            => 'ID',
            'forename'      => 'Forename',
            'surname'       => 'Surname',
            'username'      => 'Username'
        ],
        'columns_callbacks' => [
            'username' => function ($obj, $columns, $column, $value)
            {
                return rtrim(substr($value ? $value : '', 0, 20)) . (strlen($value ? $value : '') > 15 ? '...' : '');
            }
        ],
        'actions'      => [
            '<button type="button" style="cursor: pointer" onclick="var checked = editor_getchecked(); if (checked && checked.length) {showPasswordDialog(checked[0])} else {editor_modal.show(`To change a password please select a user!

<br /><br />

<div style=&quot;float: right&quot;>
    <button type=&quot;button&quot; onclick=&quot;editor_modal.hide()&quot;>OK</button>
</div>
`);}">Set password</button>'
        ],
        'columns_escape' => [
            'actions' => false
        ],
        'style' => [
            'table {width: 100%;}',
            'div.editor input[type=radio] {transform: scale(1.3);cursor:pointer;}'
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