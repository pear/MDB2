<?php

// BC hack to define PATH_SEPARATOR for version of PHP prior 4.3
if (!defined('PATH_SEPARATOR')) {
    if (defined('DIRECTORY_SEPARATOR') && DIRECTORY_SEPARATOR == "\\") {
        define('PATH_SEPARATOR', ';');
    } else {
        define('PATH_SEPARATOR', ':');
    }
}
ini_set('include_path', '..'.PATH_SEPARATOR.ini_get('include_path'));

require_once 'PHPUnit.php';
require_once 'test_setup.php';
require_once 'testUtils.php';

$output = '';
foreach ($testcases as $testcase) {
    include_once $testcase.'.php';
    $output .= "<div class=\"testlineup\">\n";
    $output .= "<h1>TestCase : $testcase</h1>\n";
    $testmethods[$testcase] = getTests($testcase);
    foreach ($testmethods[$testcase] as $method) {
        $output .= testCheck($testcase, $method);
    }
    $output .= "</div>\n";
}

?>
<html>
<head>
<title>MDB2 Tests</title>
<link href="tests.css" rel="stylesheet" type="text/css">
</head>
<body>

<form method="post" action="test.php">
<?php
echo($output);
?>
<input type="submit">
</form>
</body>
</html>