<?php
    // Include the SQLiteEditor.php file at the top of the
    // page  before any output is sent to the browser.
    require_once('../SQLiteEditor.php');
?>
<html>
<body>

    <h1>An example with nothing but the editor</h1>

<?php
    $editor1 = new SQLiteEditor([
        'filename'       => './sqliteeditor.db',
        'table'          => 'accounts',
        'search_columns' => ['username','forename','surname'],
        'paging_perpage' => 10,
        'sql_select'     => 'SELECT id,
                                    username,
                                    forename,
                                    surname
                               FROM {table}
                              WHERE {where}
                                    {order}',
        'editable' => [
            'username' => true,
            'forename' => true,
            'surname'  => true
        ],
        'columns_widths' => [
            'id' => 50,
            '*' => 200
        ],
    ]);
    
    $editor1->draw();
?>
</body>
</html>