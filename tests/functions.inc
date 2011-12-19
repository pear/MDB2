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
 * @param array $ci  an associative array with two elements.  The "dsn"
 *                   element must contain an array of DSN information.
 *                   The "options" element must be an array of connection
 *                   options.
 */
function build_schema($ci) {
    $file = __DIR__ . '/schema.xml';

    if (!is_array($ci['dsn'])) {
        die('$ci["dsn"] must use the array DSN format.' . "\n");
    }

    if (!array_key_exists('options', $ci)) {
        die('$ci["options"] is missing.' . "\n");
    }

    $variables = array(
        'name'   => $ci['dsn']['database'],
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
    $options = array_merge($options, $ci['options']);

    $err_base = 'TEST SCHEMA BUILD ERROR FOR ' . $ci['dsn']['phptype'] . ': ';

    $schema = MDB2_Schema::factory($ci['dsn'], $options);
    if (PEAR::isError($schema)) {
        die($err_base . $schema->getMessage() . ' '
            . $schema->getUserInfo() . "\n");
    }

    $definition = $schema->parseDatabaseDefinitionFile($file, $variables, true, true);
    if (PEAR::isError($definition)) {
        die($err_base . $definition->getMessage() . ' - '
            . $definition->getUserInfo() . "\n");
    } else {
        $operation = $schema->createDatabase($definition);
        if (PEAR::isError($operation)) {
            die($err_base . $operation->getMessage() . ' '
                . $operation->getUserInfo() . "\n");
        }
    }
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

            // Building schema here so will work with SQLite :memory:.
            build_schema($ci);
        }
    }

    return $dbs;
}
