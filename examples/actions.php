<?php
    require('../common.php');
    
    $sqle = new SQLiteEditorDownload();
    
    $sqle->header();
    $sqle->heading('A demo with action buttons');
?>

<p>
    This demo shows a couple of action buttons and a dropdown &lt;select&gt;
    box below the editor which can be added by using the actions
    option. These could do anything you choose and you can retrieve
    the IDs of the rows that are selected by using the
    editor_getchecked() function. If you don't see the checkboxes
    in your editor then you can enable them by setting the
    <i>checkboxes</i> option to true.
</p>

<p>
    The second example shows the editor_getchecked() function being
    used.
</p>

<SQLiteEditor::source>
<div>
<?php
    // Include the SQLiteEditor.php file at the top of the
    // page  before any output is sent to the browser.

    $editor = new SQLiteEditor([
        'filename'      => './sqliteeditor.db',
        'table'         => 'accounts',
        'columns_names' => [
            'id'       => 'ID',
            'username' => 'Username',
            'forename' => 'Forename',
            'surname'  => 'Surname',
            'created'  => 'Created'
        ],
        'columns_widths' => [
            'id' => 50
        ],
        'actions' => [
            '<button type="button" onclick="alert(`You clicked me!`)">Click me!</button>',
            [
                'No, click me instead!',
                'editor_modal.show(`<p style=&quot;text-align: center&quot;>You clicked me instead! <p /> You can use the editor_modal.show() function for our own purposes if you want to.</p> <p>There\'s also the more comprehensive <a href=&quot;https://www.rgraph.net/canvas/integration.html&quot; target=&quot;_blank&quot;>Modal Dialog from the RGraph website</a> if you prefer to use that.</p> <div align=&quot;right&quot;><button type=&quot;button&quot; style=&quot;font-size: 20pt&quot; onclick=&quot;editor_modal.close()&quot;>Close</button></div>`)'
            ],
            '<select onchange="alert(`Option selected: ` + this.value);this.selectedIndex = 0"><option><option value="option-1">If you have a lot of options...</option><option value="option-2">a dropdown selector...</option><option value="option-3">may work better for you...</option></option></select>'
        ],
        'style' => [
            'div.editor table {width: 75%; margin-left: auto; margin-right: auto;}',
            'tfoot :where(input[type=submit], button, select) {font-size: 16pt;}',
            'tfoot :where(select) {font-size: 18pt;}',
            'div.editor-modaldialog-dialog button {cursor: pointer;}'
        ]
    ]);
    
    $editor->draw();


    echo '<p>&nbsp;</p>';
    echo '<p>&nbsp;</p>';



    $editor = new SQLiteEditor([
        'filename'      => './sqliteeditor.db',
        'table'         => 'accounts',
        'columns_names' => [
            'id'       => 'ID',
            'username' => 'Username',
            'forename' => 'Forename',
            'surname'  => 'Surname',
            'created'  => 'Created'
        ],
        'columns_widths' => [
            'id' => 50
        ],
        'sql_delete' => false,
        'sql_add' => false,
        'checkboxes' => true,
        'actions' => [
            '<button type="button" onclick="alert(editor_getchecked(`editor2`))">An example showing the editor_getchecked() usage</button>'
        ],
        'style' => [
            'div.editor table {width: 75%; margin-left: auto; margin-right: auto;}',
            'tfoot :where(input[type=submit], button, select) {font-size: 16pt;}',
            'tfoot :where(select) {font-size: 18pt;}',
            'div.editor-modaldialog-dialog button {cursor: pointer;}'
        ]
    ]);
    
    $editor->draw();

    echo '<p>&nbsp;</p>';
    echo '<p>&nbsp;</p>';
?>
</div>
</SQLiteEditor::source>

<?php
    $sqle->source();
    $sqle->footer();
?>