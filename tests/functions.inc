<?php

/**
 * Declares functions that need to be available in the global scope
 *
 * @package MDB2
 * @category Database
 * @author Daniel Convissor <danielc@php.net>
 */

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
        }
    }

    return $dbs;
}
