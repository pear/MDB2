<?php

/**
 * A means to create the database and tables used by the test suite.
 *
 * The test suite builds the requisite structures automatically.  This script
 * is made available in the event you want to do so manually.
 *
 * @package MDB2
 * @category Database
 * @author Daniel Convissor <danielc@php.net>
 */

error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);

/**
 * Establish the test suite's environment.
 */
require_once __DIR__ . '/autoload.inc';

mdb2_test_db_object_provider();

?>
DONE!
