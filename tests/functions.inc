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
 * @param MDB2_Driver_Common $db
 * @param string $phptype
 */
function build_schema(MDB2_Driver_Common $db, $phptype) {
    $file = __DIR__ . '/schema.xml';

    $variables = array(
        'name'   => $db->getDatabase(),
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
    $options = array_merge($options, $db->options);

    $err_base = "TEST SCHEMA BUILD ERROR FOR $phptype: ";

    $schema = MDB2_Schema::factory($db, $options);
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
 * Produces a multi-diemnsional array containing instantiated MDB2 objects
 * for each DSN defined
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
        foreach ($dsns as $driver => $data) {
            $db = MDB2::factory($data['dsn'], $data['options']);
            if (PEAR::isError($db)) {
                continue;
            }

            $dbs[$driver] = array(
                $db,
            );

            // Building schema here so will work with SQLite :memory:.
            build_schema($db, $driver);
        }
    }

    return $dbs;
}
