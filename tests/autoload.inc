<?php

/**
 * Sets the include path, auto loader, and includes the DSN file
 *
 * @package MDB2
 * @category Database
 * @author Daniel Convissor <danielc@php.net>
 */

$dirs = array();

// Determine this directory.
$dirs['tests'] = realpath(__DIR__);

// Determine path to the MDB2 classes to be tested.
if ('@php_dir@' == '@'.'php_dir'.'@') {
    // This package hasn't been installed, use parent of this dir.
    $dirs['mdb2'] = realpath((dirname(__DIR__)));
} else {
    $dirs['mdb2'] = '@php_dir@';
}
/**
 * Path to the MDB2 files we are testing
 */
define('MDB2_TEST_MDB2_PATH', $dirs['mdb2']);

// Determine if a current version of PHPUnit is installed.
try {
    $fi = new SplFileObject('PHPUnit/Autoload.php', 'r', true);
    unset($fi);
} catch (Exception $e) {
    try {
        $fi = new SplFileObject('PHPUnit/Framework.php', 'r', true);
        die("skip Run 'pear upgrade PHPUnit' then retry.\n");
    } catch (Exception $e) {
        die("skip Run 'pear install pear.phpunit.de/PHPUnit' then retry.\n");
    }
}

// Determine if and where MDB_Schema is installed.
try {
    $fi = new SplFileObject('MDB2/Schema.php', 'r', true);
    $dirs['mdb2_schema'] = dirname(dirname($fi->getRealPath()));
    unset($fi);
} catch (Exception $e) {
    die("skip Run 'pear install MDB2_Schema-beta' then retry.\n");
}

// Set the include path.
$dirs = array_unique($dirs);
set_include_path(implode(PATH_SEPARATOR, $dirs)
    . PATH_SEPARATOR . get_include_path());

/**
 * Automatically includes files for new classes
 */
function mdb2_test_autoload($class) {
	$class_file = str_replace('_', '/', $class) . '.php';
    try {
        $fi = new SplFileObject($class_file, 'r', true);
        require_once $fi->getPathname();
        unset($fi);
    } catch (Exception $e) {
        // Fall through, perhaps another auto loader will pick it up.
    }
}
spl_autoload_register('mdb2_test_autoload');

/**
 * Line up PHPUnit's autoloader.
 */
require_once 'PHPUnit/Autoload.php';

/**
 * Gather our functions.
 */
require_once 'functions.inc';

/**
 * Gather our database connection information.
 */
require_once 'dsn.inc';
