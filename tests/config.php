<?php

if (empty($_ENV['MYSQL_TEST_USER'])) {
    $dsn = array(
        'phptype' => 'mysqli',
        'username' => 'username',
        'password' => 'password',
        'database' => 'databasename',
        'hostspec' => 'hostname',
        'port' => 'port',
        'socket' => 'socket',
    );
} else {
    $dsn = array(
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
    );
}

/**
 * Constant used in MDB2_Connect_Test
 */
define('MDB2_DSN', serialize($dsn));

if ('@php_dir@' == '@'.'php_dir'.'@') {
    // This package hasn't been installed.
    // Adjust path to ensure includes find files in working directory.
    set_include_path(dirname(dirname(__FILE__))
        . PATH_SEPARATOR . get_include_path());
}
