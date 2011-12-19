<?php

/**
 * A place to point debuggers for running specific tests
 *
 * To change the test called, edit addTestSuite()'s parameter.
 *
 * You may need to add your <DBMS>_TEST_* environment variables here.
 * See the example in the code, below.
 *
 * @package MDB2
 * @category Database
 * @author Daniel Convissor <danielc@php.net>
 */


/*
$_ENV['MYSQL_TEST_USER']='test';
$_ENV['MYSQL_TEST_PASSWD']='test';
$_ENV['MYSQL_TEST_DB']='test';
$_ENV['MYSQL_TEST_SOCKET']='/var/run/mysqld/mysqld.sock';
*/


// Setting this here because this isn't the general test suite.
error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);

// Keep tests from running twice when calling this file directly via PHPUnit.
$call_main = false;
if (strpos($_SERVER['argv'][0], 'phpunit') === false) {
    // Called via php, not PHPUnit.  Pass the request to PHPUnit.
    if (!defined('PHPUnit_MAIN_METHOD')) {
        /** An indicator of which test was called. */
        define('PHPUnit_MAIN_METHOD', 'Debug::main');
        $call_main = true;
    }
}

/**
 * Establish the test suite's environment.
 */
require_once __DIR__ . '/autoload.inc';

/**
 * A place to point debuggers for running specific tests
 *
 * To change the test called, edit addTestSuite()'s parameter.
 *
 * @package MDB2
 * @category Database
 * @author Daniel Convissor <danielc@php.net>
 */
class Debug {
    public static function main() {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite('MDB2 Test Debugging');

        $suite->addTestSuite('Standard_DatatypeTest');

        return $suite;
    }
}

if ($call_main) {
    Debug::main();
}
