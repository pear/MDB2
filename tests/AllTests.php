<?php

/**
 * This script is here to tell the Continuous Integration server that this
 * package should be tested
 *
 * See the README file for how to run the test suite manually.
 *
 * @package MDB2
 * @category Database
 * @author Daniel Convissor <danielc@php.net>
 */

// Keep tests from running twice when calling this file directly via PHPUnit.
$call_main = false;
if (strpos($_SERVER['argv'][0], 'phpunit') === false) {
    // Called via php, not PHPUnit.  Pass the request to PHPUnit.
    if (!defined('PHPUnit_MAIN_METHOD')) {
        /** An indicator of which test was called. */
        define('PHPUnit_MAIN_METHOD', 'AllTests::main');
        $call_main = true;
    }
}

/**
 * Establish the test suite's environment.
 */
require_once __DIR__ . '/autoload.inc';

/**
 * This class is here to tell the Continuous Integration server that this
 * package should be tested
 *
 * See the README file for how to run the test suite manually.
 *
 * @package MDB2
 * @category Database
 * @author Daniel Convissor <danielc@php.net>
 */
class AllTests {
    public static function main() {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite('MDB2 Unit Tests');

        $dir = new GlobIterator(__DIR__ . '/Standard/*Test.php');
        $suite->addTestFiles($dir);

        return $suite;
    }
}

if ($call_main) {
    AllTests::main();
}
