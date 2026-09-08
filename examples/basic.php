<?php
    require('../common.php');
    
    $sqle = new SQLiteEditorDownload();
    
    $sqle->header();
    $sqle->heading('Basic demo using a simple editor');
?>


<SQLiteEditor::source>
<?php
    // Include the SQLiteEditor.php file at the top of the
    // page  before any output is sent to the browser.

    $editor = new SQLiteEditor([
        'filename' => './sqliteeditor.db',
        'table'    => 'accounts'
    ]);
    
    $editor->draw();
?>
</SQLiteEditor::source>

<?php
    $sqle->source();
    $sqle->footer();
?>