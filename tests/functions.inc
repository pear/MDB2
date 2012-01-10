<?php

/**
 * Declares functions that need to be available in the global scope
 *
 * @package MDB2
 * @category Database
 * @author Daniel Convissor <danielc@php.net>
 */

/**
 * Builds the tables used by the test suite.
 *
 * @param array|MDB2_Driver_Common $ci  either a MDB2_Driver_Common object or
 *                   an associative array with two elements.  The "dsn"
 *                   element must contain an array of DSN information.
 *                   The "options" element must be an array of connection
 *                   options.
 */
function build_schema($ci) {
    $file = __DIR__ . '/schema.xml';

    if (is_object($ci)) {
        if (! $ci instanceof MDB2_Driver_Common) {
            die("Must be a MDB2_Driver_Common object.\n");
        }
        $db = $ci;
        $database = $ci->getDatabase();
        $phptype = $ci->phptype;
        $original_options = $ci->options;
    } else {
        if (!is_array($ci['dsn'])) {
            die('$ci["dsn"] must use the array DSN format.' . "\n");
        }
        $db = $ci['dsn'];
        $database = $db['database'];
        $phptype = $db['phptype'];

        if (!array_key_exists('options', $ci)) {
            die('$ci["options"] is missing.' . "\n");
        }
        $original_options = $ci['options'];
    }

    $variables = array(
        'name'   => $database,
        'create' => true,
    );

    $options = array(
        'log_line_break'   => '<br />',
        'idxname_format'   => '%s',
        'debug'            => true,
        'quote_identifier' => true,
        'force_defaults'   => false,
        'portability'      => false
    );
    $options = array_merge($options, $original_options);

    $err_base = "TEST SCHEMA BUILD ERROR FOR $phptype: ";

    $schema = MDB2_Schema::factory($db, $options);
    if (MDB2::isError($schema)) {
        die($err_base . $schema->getMessage() . ' '
            . $schema->getUserInfo() . "\n");
    }

    $definition = $schema->parseDatabaseDefinitionFile($file, $variables, true, true);
    if (MDB2::isError($definition)) {
        die($err_base . $definition->getMessage() . ' - '
            . $definition->getUserInfo() . "\n");
    } else {
        $operation = $schema->createDatabase($definition);
        if (MDB2::isError($operation)) {
            die($err_base . $operation->getMessage() . ' '
                . $operation->getUserInfo() . "\n");
        }
    }
}

/**
 * Determines if the desired MDB2_Driver class is available IN THE LOCATION
 * WE ARE TESTING
 *
 * Because there's not much point in testing some other installation.
 *
 * @param string $phptype  the "phptype" of the driver we're looking for
 * @return bool
 * @uses MDB2_TEST_MDB2_PATH  to determine the path
 */
function is_driver_available($phptype) {
    return file_exists(MDB2_TEST_MDB2_PATH . "/MDB2/Driver/$phptype.php");
}

/**
 * Produces a multi-diemnsional array containing the connection information
 * for each DBMS to be tested
 *
 * The connection information for each DBMS is an associative array with two
 * elements.  The "dsn" element must contain an array of DSN information.
 * The "options" element must be an array of connection options.
 *
 * Used by Standard_Abstract::provider()
 *
 * @return array
 */
function mdb2_test_db_object_provider() {
    static $dbs;

    if (!isset($dbs)) {
        $dsns = unserialize(MDB2_TEST_SERIALIZED_DSNS);
        $dbs = array();
        foreach ($dsns as $driver => $ci) {
            $dbs[$driver] = array(
                $ci,
            );

            // Disable messages from other packages while building schema.
            $prior = error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);
            build_schema($ci);
            error_reporting($prior);
        }
    }

    return $dbs;
}
