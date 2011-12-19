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
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('MDB2 Unit Tests');

        $dir = new RecursiveDirectoryIterator(__DIR__ . '/Standard',
                FilesystemIterator::SKIP_DOTS);
        $suite->addTestFiles($dir);

		return $suite;
	}
}
