<?php
    require('common.php');
    
    //
    // Get the list of demo pages
    //
    $list = glob('./examples/*.php');
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="chrome=1">
    
    <meta name="robots" content="noindex, follow">

    <title>The SQLite Editor for PHP tool</title>
    
    <link rel="stylesheet" href="css/common.css" type="text/css" media="screen" />
    
    <style>    
        body {
            text-align: left;
            padding-top: 10px;
        }

        ul {
            list-style: none;
        }
    </style>    

    <script src="js/jquery.min.351.js"></script>

</head>
<body>

    <h1>SQLite Editor for PHP</h1>


    <ul>
        <li><a href="#introduction">Introduction</a></li>
        <li><a href="#example-pages">Example pages</a></li>
        <li><a href="#support">Support</a></li>
    </ul>


    <a name="introduction"></a>
    <h2>Introduction</h2>
    <p>
        SQLite Editor for PHP is a tool for providing
        add/edit/delete interfaces to your websites users.
        The examples that are linked to below should work
        straight-away without you having to configure anything
        (assuming your server can run PHP files). SQLite is
        built into modern PHP so you shouldn't have to
        install that.
    </p>




    <div style="line-height: 25px; width: 75%; margin-left: auto; margin-right: auto">
<?php
    $editor = new SQLiteEditor([
        'filename'  => 'examples/sqliteeditor.db',
        'table'     => 'accounts',
        'sql_select' => 'SELECT id,
                                forename,
                                surname,
                                username,
                                created
                           FROM accounts
                          WHERE {where}
                                {order}',
        'columns_names' => $names = [
            'id'       => 'ID',
            'forename' => 'First name',
            'surname'  => 'Second name',
            'username' => 'Username',
            'created'  => 'Created'
        ],
        'columns_widths' => [
            'id' => 50,
            '*' => 150
        ],
        'search_columns' => array_keys($names),
        'editable' => [
            'username' => true,
            'forename' => true
        ],
        'editable_types' => [
            'username' => 'text',
            'forename' => 'select'
        ],
        'editable_types_select_options' => [
            'forename' => ',Richard,John,Dave,Edgar'
        ],
        'style' => [
            'tfoot button,tfoot input[type=submit] {font-size: 16pt;}',
            'div.editor :where(th, td) div {padding-left: 5px;}',
            'div.editor tfoot :where(button.editor_button_add, input.editor_button_delete) {font-size: 110%;}'
        ]
    ]);
    
    $editor->draw();
?>
    </div>

    <pre class="code">
&lt;?php
    $editor = new SQLiteEditor([
        'filename'  => 'examples/sqliteeditor.db',
        'table'     => 'accounts',
        'sql_select' => 'SELECT id,
                                forename,
                                surname,
                                username,
                                created
                           FROM accounts
                          WHERE {where}
                                {order}',
        'columns_names' => $names = [
            'id'       => 'ID',
            'forename' => 'First name',
            'surname'  => 'Second name',
            'username' => 'Username',
            'created'  => 'Created'
        ],
        'columns_widths' => [
            'id' => 50,
            '*' => 150
        ],
        'search_columns' => array_keys($names),
        'editable' => [
            'username' => true,
            'forename' => true
        ],
        'editable_types' => [
            'username' => 'text',
            'forename' => 'select'
        ],
        'editable_types_select_options' => [
            'forename' => ',Richard,John,Dave,Edgar'
        ],
        'style' => [
            'tfoot button,tfoot input[type=submit] {font-size: 16pt;}',
            'div.editor :where(th, td) div {padding-left: 5px;}',
            'div.editor tfoot :where(button.editor_button_add, input.editor_button_delete) {font-size: 110%;}'
        ]
    ]);
    
    $editor-&gt;draw();
?&gt;
</pre>
    

    <a name="example-pages"></a>
    <h2>Example pages</h2>
    
    <p>
        All of these are in the <a href="./examples/">./examples/</a> directory.
    </p>

    <ul>
        <?php
            foreach ($list as $l) {
                printf('<li><a href="%s">%s</a></li>', $l, basename($l));
            }
        ?>
    </ul>
















    <a name="support"></a>
    <h2>Support</h2>
    <p>
        If you have an issue with Sqlite Editor then please email me
        directly using the email address richardheyes at Google's
        popular wedbmail app.
    </p>




    <div>
        <div style="float: right">
            <i><a href="https://www.rgraph.net/sqliteeditor/index.html" target="_blank" rel="nofollow">The SQLite Editor for PHP website</a></i>
        </div>
    </div>


    <script>
        //
        // Make all anchor links scroll to the anchors
        // instead of jumping to them.
        //
        // IMPORTANT: THIS IS REPEATED IN THE PAGE
        //            HEADER FOR THE WEBSITE
        //
        function scrollToAnchor(name)
        {
            var tag = $("a[name='" + name + "']");
            
            $("html,body").animate({
                scrollTop: tag.offset().top - 60
            },"slow");
        }

        $("a").on('mousedown', function()
        {
            var href = this.getAttribute("href");
    
            if (href.substr(0,1) === "#") {
                var target = href.substr(1).replace(/'/i,'');
                scrollToAnchor(target);
            }
        });
    </script>
</body>
</html>