--TEST--
MDB2: Basic connectivity
--FILE--
<?php

require_once dirname(__FILE__) . '/MDB2_Connect_Test.php';
require_once dirname(__FILE__) . '/config.php';

if (! ($dbc =& new MDB2_Connect_Test(true)))
{
	die("Unable to instantiate MDB2 object\n");
}
$e = $dbc->connect();

if (PEAR::isError($e)) {
    print $e->getMessage() . "\n";
}

echo 'Success!';
?>
--EXPECT--
Success!
