<?php

/**
 * Defines DSN's for all databases that have <DBMS>_TEST_* environment
 * variables set and have their corresponding extensions loaded
 *
 * <DBMS>_TEST_USER, <DBMS>_MYSQL_TEST_PASSWD and <DBMS>_TEST_DB are required,
 * except for SQLite.
 *
 * SQLite runs by default as long as the extension is loaded.  The tests use
 * :memory: as the database unless the SQLITE_TEST_DB environment variable is
 * defined.
 *
 * <DBMS> can be one or more of the following:
 *   + FBSQL
 *   + IBASE
 *   + MSSQL
 *   + MYSQL (for mysql and mysqli extensions)
 *   + OCI8
 *   + PGSQL
 *   + SQLITE
 *   + SQLSRV
 *
 * Examine this file to see exactly which settings are available for
 * the DBMS's you are interested in testing.
 *
 * In the event you do NOT want a particular driver tested but you have that
 * extension installed in PHP and have the requisite environment variables set,
 * that driver's tests can be disabled by UN-commenting the corresponding
 * entry in the $skip array at the top of this script.
 *
 * The resulting DSN's are passed to serialize() and stored in the
 * MDB2_TEST_SERIALIZED_DSNS constant.
 *
 * @package MDB2
 * @category Database
 * @author Daniel Convissor <danielc@php.net>
 */


// See description in the docblock, above.
$skip = array(
//    'fbsql',
//    'ibase',
//    'mssql',
//    'mysql',
//    'mysqli',
//    'oci8',
//    'pgsql',
//    'sqlite',
//    'sqlsrv',
);


$dsns = array();

if (!empty($_ENV['FBSQL_TEST_USER']) && !in_array('fbsql', $skip)
    && is_driver_available('fbsql') && extension_loaded('fbsql'))
{
    $dsns['fbsql'] = array(
        'dsn' => array(
            'phptype' => 'fbsql',
            'username' => $_ENV['FBSQL_TEST_USER'],
            'password' => $_ENV['FBSQL_TEST_PASSWD'],
            'database' => $_ENV['FBSQL_TEST_DB'],

            'hostspec' => empty($_ENV['FBSQL_TEST_HOST'])
                    ? null : $_ENV['FBSQL_TEST_HOST'],
        ),
        'options' => array(
        ),
    );
}

if (!empty($_ENV['IBASE_TEST_USER']) && !in_array('ibase', $skip)
    && is_driver_available('ibase') && extension_loaded('interbase'))
{
    $dsns['ibase'] = array(
        'dsn' => array(
            'phptype' => 'ibase',
            'username' => $_ENV['IBASE_TEST_USER'],
            'password' => $_ENV['IBASE_TEST_PASSWD'],
            'database' => $_ENV['IBASE_TEST_DB'],

            'hostspec' => empty($_ENV['IBASE_TEST_HOST'])
                    ? null : $_ENV['IBASE_TEST_HOST'],

            'buffers' => empty($_ENV['IBASE_TEST_BUFFERS'])
                    ? null : $_ENV['IBASE_TEST_BUFFERS'],

            'dialect' => empty($_ENV['IBASE_TEST_DIALECT'])
                    ? null : $_ENV['IBASE_TEST_DIALECT'],

            'role' => empty($_ENV['IBASE_TEST_ROLE'])
                    ? null : $_ENV['IBASE_TEST_ROLE'],

            'dbsyntax' => empty($_ENV['IBASE_TEST_SYNTAX'])
                    ? null : $_ENV['IBASE_TEST_SYNTAX'],
        ),
        'options' => array(
        ),
    );
}

if (!empty($_ENV['MSSQL_TEST_USER']) && !in_array('mssql', $skip)
    && is_driver_available('mssql') && function_exists('mssql_connect'))
{
    $dsns['mssql'] = array(
        'dsn' => array(
            'phptype' => 'mssql',
            'username' => $_ENV['MSSQL_TEST_USER'],
            'password' => $_ENV['MSSQL_TEST_PASSWD'],
            'database' => $_ENV['MSSQL_TEST_DB'],

            'hostspec' => empty($_ENV['MSSQL_TEST_HOST'])
                    ? null : $_ENV['MSSQL_TEST_HOST'],

            'port' => empty($_ENV['MSSQL_TEST_PORT'])
                    ? null : $_ENV['MSSQL_TEST_PORT'],
        ),
        'options' => array(
        ),
    );
}

if (!empty($_ENV['MYSQL_TEST_USER']) && !in_array('mysql', $skip)
    && is_driver_available('mysql') && extension_loaded('mysql'))
{
    $dsns['mysql'] = array(
        'dsn' => array(
            'phptype' => 'mysql',
            'username' => $_ENV['MYSQL_TEST_USER'],
            'password' => $_ENV['MYSQL_TEST_PASSWD'],
            'database' => $_ENV['MYSQL_TEST_DB'],

            'hostspec' => empty($_ENV['MYSQL_TEST_HOST'])
                    ? null : $_ENV['MYSQL_TEST_HOST'],

            'port' => empty($_ENV['MYSQL_TEST_PORT'])
                    ? null : $_ENV['MYSQL_TEST_PORT'],

            'socket' => empty($_ENV['MYSQL_TEST_SOCKET'])
                    ? null : $_ENV['MYSQL_TEST_SOCKET'],

            // Hack for MDB2's silly connect method logic.
            'protocol' => empty($_ENV['MYSQL_TEST_SOCKET'])
                    ? null : 'unix',
        ),
        'options' => array(
            'use_transactions' => true
        ),
    );
}

if (!empty($_ENV['MYSQL_TEST_USER']) && !in_array('mysqli', $skip)
    && is_driver_available('mysqli') && extension_loaded('mysqli'))
{
    $dsns['mysqli'] = array(
        'dsn' => array(
            'phptype' => 'mysqli',
            'username' => $_ENV['MYSQL_TEST_USER'],
            'password' => $_ENV['MYSQL_TEST_PASSWD'],
            'database' => $_ENV['MYSQL_TEST_DB'],

            'hostspec' => empty($_ENV['MYSQL_TEST_HOST'])
                    ? null : $_ENV['MYSQL_TEST_HOST'],

            'port' => empty($_ENV['MYSQL_TEST_PORT'])
                    ? null : $_ENV['MYSQL_TEST_PORT'],

            'socket' => empty($_ENV['MYSQL_TEST_SOCKET'])
                    ? null : $_ENV['MYSQL_TEST_SOCKET'],
        ),
        'options' => array(
            'use_transactions' => true
        ),
    );
}

if (!empty($_ENV['OCI8_TEST_USER']) && !in_array('oci8', $skip)
    && is_driver_available('oci8') && extension_loaded('oci8'))
{
    $dsns['oci8'] = array(
        'dsn' => array(
            'phptype' => 'oci8',
            'username' => empty($_ENV['OCI8_TEST_USER'])
                    ? null : $_ENV['OCI8_TEST_USER'],

            'password' => empty($_ENV['OCI8_TEST_PASSWD'])
                    ? null : $_ENV['OCI8_TEST_PASSWD'],

            'database' => empty($_ENV['OCI8_TEST_DB'])
                    ? null : $_ENV['OCI8_TEST_DB'],

            'hostspec' => empty($_ENV['OCI8_TEST_HOST'])
                    ? null : $_ENV['OCI8_TEST_HOST'],

            'port' => empty($_ENV['OCI8_TEST_PORT'])
                    ? null : $_ENV['OCI8_TEST_PORT'],

            'service' => empty($_ENV['OCI8_TEST_SERVICE'])
                    ? null : $_ENV['OCI8_TEST_SERVICE'],
        ),
        'options' => array(
        ),
    );
}

if (!empty($_ENV['PGSQL_TEST_USER']) && !in_array('pgsql', $skip)
    && is_driver_available('pgsql') && extension_loaded('pgsql'))
{
    $dsns['pgsql'] = array(
        'dsn' => array(
            'phptype' => 'pgsql',
            'username' => $_ENV['PGSQL_TEST_USER'],
            'password' => $_ENV['PGSQL_TEST_PASSWD'],
            'database' => $_ENV['PGSQL_TEST_DB'],

            'hostspec' => empty($_ENV['PGSQL_TEST_HOST'])
                    ? null : $_ENV['PGSQL_TEST_HOST'],

            'port' => empty($_ENV['PGSQL_TEST_PORT'])
                    ? null : $_ENV['PGSQL_TEST_PORT'],

            'socket' => empty($_ENV['PGSQL_TEST_SOCKET'])
                    ? null : $_ENV['PGSQL_TEST_SOCKET'],

            'protocol' => empty($_ENV['PGSQL_TEST_PROTOCOL'])
                    ? null : $_ENV['PGSQL_TEST_PROTOCOL'],

            'options' => empty($_ENV['PGSQL_TEST_OPTIONS'])
                    ? null : $_ENV['PGSQL_TEST_OPTIONS'],

            'tty' => empty($_ENV['PGSQL_TEST_TTY'])
                    ? null : $_ENV['PGSQL_TEST_TTY'],

            'connect_timeout' => empty($_ENV['PGSQL_TEST_CONNECT_TIMEOUT'])
                    ? null : $_ENV['PGSQL_TEST_CONNECT_TIMEOUT'],

            'sslmode' => empty($_ENV['PGSQL_TEST_SSL_MODE'])
                    ? null : $_ENV['PGSQL_TEST_SSL_MODE'],

            'service' => empty($_ENV['PGSQL_TEST_SERVICE'])
                    ? null : $_ENV['PGSQL_TEST_SERVICE'],
        ),
        'options' => array(
        ),
    );
}

// SQLite is different.  Runs using :memory: table even if variable is not set.
if (!in_array('sqlite', $skip)
    && is_driver_available('sqlite') && extension_loaded('sqlite'))
{
    if (empty($_ENV['SQLITE_TEST_DB'])) {
        $dsns['sqlite'] = array(
            'dsn' => array(
                'phptype' => 'sqlite',
                'database' => ':memory:',
            ),
            'options' => array(
            ),
        );
    } else {
        $dsns['sqlite'] = array(
            'dsn' => array(
                'phptype' => 'sqlite',
                'database' => $_ENV['SQLITE_TEST_DB'],
            ),
            'options' => array(
            ),
        );
    }
}

if (!empty($_ENV['SQLSRV_TEST_USER']) && !in_array('sqlsrv', $skip)
    && is_driver_available('sqlsrv') && extension_loaded('sqlsrv'))
{
    $dsns['sqlsrv'] = array(
        'dsn' => array(
            'phptype' => 'sqlsrv',
            'username' => $_ENV['SQLSRV_TEST_USER'],
            'password' => $_ENV['SQLSRV_TEST_PASSWD'],
            'database' => $_ENV['SQLSRV_TEST_DB'],

            'hostspec' => empty($_ENV['SQLSRV_TEST_HOST'])
                    ? null : $_ENV['SQLSRV_TEST_HOST'],

            'port' => empty($_ENV['SQLSRV_TEST_PORT'])
                    ? null : $_ENV['SQLSRV_TEST_PORT'],
        ),
        'options' => array(
        ),
    );
}


// Just making sure to avoid fatal errors if the file gets included twice.
if (!defined('MDB2_TEST_SERIALIZED_DSNS')) {
    /**
     * A serialize()'ed array of DSN's for all active databases
     */
    define('MDB2_TEST_SERIALIZED_DSNS', serialize($dsns));
}
