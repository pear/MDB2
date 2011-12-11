<?php
// {{{ DSN Constants
/**
 * Constants used in PackageName_MDB2
 */
define ('DSN_PHPTYPE',  'mysql');
define ('DSN_USERNAME', 'username');
define ('DSN_PASSWORD', 'password');
define ('DSN_HOSTNAME', 'hostname');
define ('DSN_DATABASE', 'databasename');
// }}}

if ('@php_dir@' == '@'.'php_dir'.'@') {
    // This package hasn't been installed.
    // Adjust path to ensure includes find files in working directory.
    set_include_path(dirname(dirname(__FILE__))
        . PATH_SEPARATOR . get_include_path());
}
