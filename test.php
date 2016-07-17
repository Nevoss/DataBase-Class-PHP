<?php
require_once 'DB.php';

// This page is a test page to check the method,
// this is also a good way to see how can you use the methods in the DB class

// This is some test on my DB called 'testdb', in table called 'pages' //

$db = DB::getInstance()->select('*', 'pages');



?>
