<?php
// vim: set et ts=4 sw=4 fdm=marker:
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2004 Manuel Lemos, Tomas V.V.Cox,                 |
// | Stig. S. Bakken, Lukas Smith                                         |
// | All rights reserved.                                                 |
// +----------------------------------------------------------------------+
// | MDB2 is a merge of PEAR DB and Metabases that provides a unified DB  |
// | API as well as database abstraction for PHP applications.            |
// | This LICENSE is in the BSD license style.                            |
// |                                                                      |
// | Redistribution and use in source and binary forms, with or without   |
// | modification, are permitted provided that the following conditions   |
// | are met:                                                             |
// |                                                                      |
// | Redistributions of source code must retain the above copyright       |
// | notice, this list of conditions and the following disclaimer.        |
// |                                                                      |
// | Redistributions in binary form must reproduce the above copyright    |
// | notice, this list of conditions and the following disclaimer in the  |
// | documentation and/or other materials provided with the distribution. |
// |                                                                      |
// | Neither the name of Manuel Lemos, Tomas V.V.Cox, Stig. S. Bakken,    |
// | Lukas Smith nor the names of his contributors may be used to endorse |
// | or promote products derived from this software without specific prior|
// | written permission.                                                  |
// |                                                                      |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
// | FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE      |
// | REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,          |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
// | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS|
// |  OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED  |
// | AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT          |
// | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY|
// | WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE          |
// | POSSIBILITY OF SUCH DAMAGE.                                          |
// +----------------------------------------------------------------------+
// | Author: Lukas Smith <smith@backendmedia.com>                         |
// +----------------------------------------------------------------------+
//
// $Id$
//

/**
 * @package  MDB2
 * @category Database
 * @author   Lukas Smith <smith@backendmedia.com>
 */

/**
 * include the PEAR core
 */
require_once 'PEAR.php';

/**
 * The method mapErrorCode in each MDB2_dbtype implementation maps
 * native error codes to one of these.
 *
 * If you add an error code here, make sure you also add a textual
 * version of it in MDB2::errorMessage().
 */

define('MDB2_OK',                         1);
define('MDB2_ERROR',                     -1);
define('MDB2_ERROR_SYNTAX',              -2);
define('MDB2_ERROR_CONSTRAINT',          -3);
define('MDB2_ERROR_NOT_FOUND',           -4);
define('MDB2_ERROR_ALREADY_EXISTS',      -5);
define('MDB2_ERROR_UNSUPPORTED',         -6);
define('MDB2_ERROR_MISMATCH',            -7);
define('MDB2_ERROR_INVALID',             -8);
define('MDB2_ERROR_NOT_CAPABLE',         -9);
define('MDB2_ERROR_TRUNCATED',          -10);
define('MDB2_ERROR_INVALID_NUMBER',     -11);
define('MDB2_ERROR_INVALID_DATE',       -12);
define('MDB2_ERROR_DIVZERO',            -13);
define('MDB2_ERROR_NODBSELECTED',       -14);
define('MDB2_ERROR_CANNOT_CREATE',      -15);
define('MDB2_ERROR_CANNOT_DELETE',      -16);
define('MDB2_ERROR_CANNOT_DROP',        -17);
define('MDB2_ERROR_NOSUCHTABLE',        -18);
define('MDB2_ERROR_NOSUCHFIELD',        -19);
define('MDB2_ERROR_NEED_MORE_DATA',     -20);
define('MDB2_ERROR_NOT_LOCKED',         -21);
define('MDB2_ERROR_VALUE_COUNT_ON_ROW', -22);
define('MDB2_ERROR_INVALID_DSN',        -23);
define('MDB2_ERROR_CONNECT_FAILED',     -24);
define('MDB2_ERROR_EXTENSION_NOT_FOUND',-25);
define('MDB2_ERROR_NOSUCHDB',           -26);
define('MDB2_ERROR_ACCESS_VIOLATION',   -27);
define('MDB2_ERROR_CANNOT_REPLACE',     -28);
define('MDB2_ERROR_CONSTRAINT_NOT_NULL',-29);
define('MDB2_ERROR_DEADLOCK',           -30);
define('MDB2_ERROR_CANNOT_ALTER',       -31);
define('MDB2_ERROR_MANAGER',            -32);
define('MDB2_ERROR_MANAGER_PARSE',      -33);
define('MDB2_ERROR_LOADMODULE',         -34);
define('MDB2_ERROR_INSUFFICIENT_DATA',  -35);


/**
 * This is a special constant that tells MDB2 the user hasn't specified
 * any particular get mode, so the default should be used.
 */

define('MDB2_FETCHMODE_DEFAULT', 0);

/**
 * Column data indexed by numbers, ordered from 0 and up
 */

define('MDB2_FETCHMODE_ORDERED',  1);

/**
 * Column data indexed by column names
 */

define('MDB2_FETCHMODE_ASSOC',    2);

/**
 * For multi-dimensional results: normally the first level of arrays
 * is the row number, and the second level indexed by column number or name.
 * MDB2_FETCHMODE_FLIPPED switches this order, so the first level of arrays
 * is the column name, and the second level the row number.
 */

define('MDB2_FETCHMODE_FLIPPED',  4);

// }}}
// {{{ portability modes


/**
 * Portability: turn off all portability features.
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_NONE', 0);

/**
 * Portability: convert names of tables and fields to lower case
 * when using the query*(), fetch*() and tableInfo() methods.
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_LOWERCASE', 1);

/**
 * Portability: right trim the data output by query*() and fetch*().
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_RTRIM', 2);

/**
 * Portability: force reporting the number of rows deleted.
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_DELETE_COUNT', 4);

/**
 * Portability: not needed in MDB2 (just left here for compatibility to DB)
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_NUMROWS', 8);

/**
 * Portability: makes certain error messages in certain drivers compatible
 * with those from other DBMS's.
 *
 * + mysql, mysqli:  change unique/primary key constraints
 *   MDB2_ERROR_ALREADY_EXISTS -> MDB2_ERROR_CONSTRAINT
 *
 * + odbc(access):  MS's ODBC driver reports 'no such field' as code
 *   07001, which means 'too few parameters.'  When this option is on
 *   that code gets mapped to MDB2_ERROR_NOSUCHFIELD.
 *
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_ERRORS', 16);

/**
 * Portability: convert empty values to null strings in data output by
 * query*() and fetch*().
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_EMPTY_TO_NULL', 32);

/**
 * Portability: turn on all portability features.
 * @see MDB2_Driver_Common::setOption()
 */
define('MDB2_PORTABILITY_ALL', 63);

/**
 * These are global variables that are used to track the various class instances
 */

$GLOBALS['_MDB2_LOBs'] = array();
$GLOBALS['_MDB2_databases'] = array();

/**
 * The main 'MDB2' class is simply a container class with some static
 * methods for creating DB objects as well as some utility functions
 * common to all parts of DB.
 *
 * The object model of MDB2 is as follows (indentation means inheritance):
 *
 * MDB2          The main MDB2 class.  This is simply a utility class
 *              with some 'static' methods for creating MDB2 objects as
 *              well as common utility functions for other MDB2 classes.
 *
 * MDB2_Driver_Common   The base for each MDB2 implementation.  Provides default
 * |            implementations (in OO lingo virtual methods) for
 * |            the actual DB implementations as well as a bunch of
 * |            query utility functions.
 * |
 * +-MDB2_mysql  The MDB2 implementation for MySQL. Inherits MDB2_Driver_Common.
 *              When calling MDB2::factory or MDB2::connect for MySQL
 *              connections, the object returned is an instance of this
 *              class.
 * +-MDB2_pgsql  The MDB2 implementation for PostGreSQL. Inherits MDB2_Driver_Common.
 *              When calling MDB2::factory or MDB2::connect for PostGreSQL
 *              connections, the object returned is an instance of this
 *              class.
 *
 * MDB2_Date     This class provides several method to convert from and to
 *              MDB2 timestamps.
 *
 * @package  MDB2
 * @category Database
 * @author   Lukas Smith <smith@backendmedia.com>
 */
class MDB2
{
    // }}}
    // {{{ setOptions()

    /**
     * set option array in an exiting database object
     *
     * @param   object  $db       MDB2 object
     * @param   array   $options  An associative array of option names and their values.
     * @access  public
     */
    function setOptions(&$db, $options)
    {
        if (is_array($options)) {
            foreach ($options as $option => $value) {
                $test = $db->setOption($option, $value);
                if (MDB2::isError($test)) {
                    return $test;
                }
            }
        }

        return MDB2_OK;
    }

    // }}}
    // {{{ factory()

    /**
     * Create a new MDB2 object for the specified database type
     * type
     *
     * @param   string  $type   database type, for example 'mysql'
     * @return  mixed   a newly created MDB2 object, or false on error
     * @access  public
     */
    function &factory($type, $debug = false)
    {
        $class_name    = 'MDB2_Driver_'.$type;
        $include       = 'MDB2/Driver/'.$type.'.php';

        if ($debug) {
            include_once $include;
            $db =& new $class_name();
        } else {
            @include_once $include;
            if (!class_exists($class_name)) {
                $error =& MDB2_Driver_Common::raiseError(MDB2_ERROR_NOT_FOUND,
                    null, null, 'Unable to include the '.$include.' file');
                return $error;
            }
            @$db =& new $class_name();
        }

        return $db;
    }

    // }}}
    // {{{ connect()

    /**
     * Create a new MDB2 connection object and connect to the specified
     * database
     *
     * IMPORTANT: In order for MDB2 to work properly it is necessary that
     * you make sure that you work with a reference of the original
     * object instead of a copy (this is a PHP4 quirk).
     *
     * For example:
     *     $mdb =& MDB2::connect($dsn);
     *          ^^
     * And not:
     *     $mdb = MDB2::connect($dsn);
     *          ^^
     *
     * @param   mixed   $dsn      'data source name', see the MDB2::parseDSN
     *                            method for a description of the dsn format.
     *                            Can also be specified as an array of the
     *                            format returned by MDB2::parseDSN.
     * @param   array   $options  An associative array of option names and
     *                            their values.
     * @return  mixed   a newly created MDB2 connection object, or a MDB2
     *                  error object on error
     * @access  public
     * @see     MDB2::parseDSN
     */
    function &connect($dsn, $options = false)
    {
        $dsninfo = MDB2::parseDSN($dsn);
        if (!isset($dsninfo['phptype'])) {
            $error =& MDB2_Driver_Common::raiseError(MDB2_ERROR_NOT_FOUND,
                null, null, 'no RDBMS driver specified');
            return $error;
        }
        $type = $dsninfo['phptype'];

        if (is_array($options)
            && isset($options['debug'])
            && $options['debug'] >= 2
        ) {
            $debug = true;
        } else {
            $debug = false;
        }

        $db =& MDB2::factory($type, $debug);
        if (MDB2::isError($db)) {
            return $db;
        }

        $db->setDSN($dsninfo);
        $err = MDB2::setOptions($db, $options);
        if (MDB2::isError($err)) {
            $db->disconnect();
            return $err;
        }

        if (isset($dsninfo['database'])) {
            $err = $db->connect();
            if (MDB2::isError($err)) {
                $dsn = $db->getDSN();
                $err->addUserInfo($dsn);
                return $err;
            }
        }
        return $db;
    }

    // }}}
    // {{{ singleton()

    /**
     * Returns a MDB2 connection with the requested DSN.
     * A newnew MDB2 connection object is only created if no object with the
     * reuested DSN exists yet.
     *
     * IMPORTANT: In order for MDB2 to work properly it is necessary that
     * you make sure that you work with a reference of the original
     * object instead of a copy (this is a PHP4 quirk).
     *
     * For example:
     *     $mdb =& MDB2::singleton($dsn);
     *          ^^
     * And not:
     *     $mdb = MDB2::singleton($dsn);
     *          ^^
     *
     * @param   mixed   $dsn      'data source name', see the MDB2::parseDSN
     *                            method for a description of the dsn format.
     *                            Can also be specified as an array of the
     *                            format returned by MDB2::parseDSN.
     * @param   array   $options  An associative array of option names and
     *                            their values.
     * @return  mixed   a newly created MDB2 connection object, or a MDB2
     *                  error object on error
     * @access  public
     * @see     MDB2::parseDSN
     */
    function &singleton($dsn = null, $options = false)
    {
        if ($dsn) {
            $dsninfo = MDB2::parseDSN($dsn);
            $dsninfo_default = array(
                'phptype'  => false,
                'dbsyntax' => false,
                'username' => false,
                'password' => false,
                'protocol' => false,
                'hostspec' => false,
                'port'     => false,
                'socket'   => false,
                'database' => false,
                'mode'     => false,
            );
            $dsninfo = array_merge($dsninfo_default, $dsninfo);
            $keys = array_keys($GLOBALS['_MDB2_databases']);
            for ($i=0, $j=count($keys); $i<$j; ++$i) {
                $tmp_dsn = $GLOBALS['_MDB2_databases'][$keys[$i]]->getDSN('array');
                if (count(array_diff($tmp_dsn, $dsninfo)) == 0) {
                    MDB2::setOptions($GLOBALS['_MDB2_databases'][$keys[$i]], $options);
                    return $GLOBALS['_MDB2_databases'][$keys[$i]];
                }
            }
        } else {
            if (is_array($GLOBALS['_MDB2_databases'])
                && reset($GLOBALS['_MDB2_databases'])
            ) {
                $db =& $GLOBALS['_MDB2_databases'][key($GLOBALS['_MDB2_databases'])];
                return $db;
            }
        }
        $db =& MDB2::connect($dsn, $options);
        return $db;
    }

    // }}}
    // {{{ loadFile()

    /**
     * load a file (like 'Date')
     *
     * @param  string     $file  name of the file in the MDB2 directory (without '.php')
     * @return $module    name of the file to be included from the MDB2 modules dir
     * @access public
     */
    function loadFile($file)
    {
        include_once 'MDB2/'.$file.'.php';
    }

    // }}}
    // {{{ apiVersion()

    /**
     * Return the MDB2 API version
     *
     * @return string     the MDB2 API version number
     * @access public
     */
    function apiVersion()
    {
        return '2.0.0';
    }

    // }}}
    // {{{ isError()

    /**
     * Tell whether a result code from a MDB2 method is an error
     *
     * @param   integer   $value  result code
     * @return  boolean   whether $value is an MDB2 Error Object
     * @access public
     */
    function isError($value)
    {
        return PEAR::isError($value);
    }

    // }}}
    // {{{ isConnection()
    /**
     * Tell whether a value is a MDB2 connection
     *
     * @param mixed $value value to test
     * @return bool whether $value is a MDB2 connection
     * @access public
     */
    function isConnection($value)
    {
        return is_a($value, 'MDB2_Driver_Common');
    }

    // }}}
    // {{{ isResult()
    /**
     * Tell whether a value is a MDB2 result
     *
     * @param mixed $value value to test
     * @return bool whether $value is a MDB2 result
     * @access public
     */
    function isResult($value)
    {
        return is_a($value, 'MDB2_Result');
    }

    // }}}
    // {{{ isResultCommon()
    /**
     * Tell whether a value is a MDB2 result implementing the common interface
     *
     * @param mixed $value value to test
     * @return bool whether $value is a MDB2 result implementing the common interface
     * @access public
     */
    function isResultCommon($value)
    {
        return is_a($value, 'MDB2_Result_Common');
    }

    // }}}
    // {{{ isManip()

    /**
     * Tell whether a query is a data manipulation query (insert,
     * update or delete) or a data definition query (create, drop,
     * alter, grant, revoke).
     *
     * @param   string   $query the query
     * @return  boolean  whether $query is a data manipulation query
     * @access public
     */
    function isManip($query)
    {
        $manips = 'INSERT|UPDATE|DELETE|'.'REPLACE|CREATE|DROP|'.
                  'ALTER|GRANT|REVOKE|'.'LOCK|UNLOCK';
        if (preg_match('/^\s*"?('.$manips.')\s+/i', $query)) {
            return true;
        }
        return false;
    }

    // }}}
    // {{{ errorMessage()

    /**
     * Return a textual error message for a MDB2 error code
     *
     * @param   int     $value error code
     * @return  string  error message, or false if the error code was
     *                  not recognized
     * @access public
     */
    function errorMessage($value)
    {
        static $errorMessages;
        if (!isset($errorMessages)) {
            $errorMessages = array(
                MDB2_ERROR                    => 'unknown error',
                MDB2_ERROR_ALREADY_EXISTS     => 'already exists',
                MDB2_ERROR_CANNOT_CREATE      => 'can not create',
                MDB2_ERROR_CANNOT_ALTER       => 'can not alter',
                MDB2_ERROR_CANNOT_REPLACE     => 'can not replace',
                MDB2_ERROR_CANNOT_DELETE      => 'can not delete',
                MDB2_ERROR_CANNOT_DROP        => 'can not drop',
                MDB2_ERROR_CONSTRAINT         => 'constraint violation',
                MDB2_ERROR_CONSTRAINT_NOT_NULL=> 'null value violates not-null constraint',
                MDB2_ERROR_DIVZERO            => 'division by zero',
                MDB2_ERROR_INVALID            => 'invalid',
                MDB2_ERROR_INVALID_DATE       => 'invalid date or time',
                MDB2_ERROR_INVALID_NUMBER     => 'invalid number',
                MDB2_ERROR_MISMATCH           => 'mismatch',
                MDB2_ERROR_NODBSELECTED       => 'no database selected',
                MDB2_ERROR_NOSUCHFIELD        => 'no such field',
                MDB2_ERROR_NOSUCHTABLE        => 'no such table',
                MDB2_ERROR_NOT_CAPABLE        => 'MDB2 backend not capable',
                MDB2_ERROR_NOT_FOUND          => 'not found',
                MDB2_ERROR_NOT_LOCKED         => 'not locked',
                MDB2_ERROR_SYNTAX             => 'syntax error',
                MDB2_ERROR_UNSUPPORTED        => 'not supported',
                MDB2_ERROR_VALUE_COUNT_ON_ROW => 'value count on row',
                MDB2_ERROR_INVALID_DSN        => 'invalid DSN',
                MDB2_ERROR_CONNECT_FAILED     => 'connect failed',
                MDB2_OK                       => 'no error',
                MDB2_ERROR_NEED_MORE_DATA     => 'insufficient data supplied',
                MDB2_ERROR_EXTENSION_NOT_FOUND=> 'extension not found',
                MDB2_ERROR_NOSUCHDB           => 'no such database',
                MDB2_ERROR_ACCESS_VIOLATION   => 'insufficient permissions',
                MDB2_ERROR_MANAGER            => 'manager error',
                MDB2_ERROR_MANAGER_PARSE      => 'manager schema parse error',
                MDB2_ERROR_LOADMODULE         => 'Error while including on demand module',
                MDB2_ERROR_TRUNCATED          => 'truncated',
                MDB2_ERROR_DEADLOCK           => 'deadlock detected',
            );
        }

        if (MDB2::isError($value)) {
            $value = $value->getCode();
        }

        return isset($errorMessages[$value]) ?
           $errorMessages[$value] : $errorMessages[MDB2_ERROR];
    }

    // }}}
    // {{{ parseDSN()

    /**
     * Parse a data source name.
     *
     * Additional keys can be added by appending a URI query string to the
     * end of the DSN.
     *
     * The format of the supplied DSN is in its fullest form:
     * <code>
     *  phptype(dbsyntax)://username:password@protocol+hostspec/database?option=8&another=true
     * </code>
     *
     * Most variations are allowed:
     * <code>
     *  phptype://username:password@protocol+hostspec:110//usr/db_file.db?mode=0644
     *  phptype://username:password@hostspec/database_name
     *  phptype://username:password@hostspec
     *  phptype://username@hostspec
     *  phptype://hostspec/database
     *  phptype://hostspec
     *  phptype(dbsyntax)
     *  phptype
     * </code>
     *
     * @param string $dsn Data Source Name to be parsed
     *
     * @return array an associative array with the following keys:
     *  + phptype:  Database backend used in PHP (mysql, odbc etc.)
     *  + dbsyntax: Database used with regards to SQL syntax etc.
     *  + protocol: Communication protocol to use (tcp, unix etc.)
     *  + hostspec: Host specification (hostname[:port])
     *  + database: Database to use on the DBMS server
     *  + username: User name for login
     *  + password: Password for login
     *
     * @author Tomas V.V.Cox <cox@idecnet.com>
     */
    function parseDSN($dsn)
    {
        $parsed = array(
            'phptype'  => false,
            'dbsyntax' => false,
            'username' => false,
            'password' => false,
            'protocol' => false,
            'hostspec' => false,
            'port'     => false,
            'socket'   => false,
            'database' => false,
            'mode'     => false,
        );

        if (is_array($dsn)) {
            $dsn = array_merge($parsed, $dsn);
            if (!$dsn['dbsyntax']) {
                $dsn['dbsyntax'] = $dsn['phptype'];
            }
            return $dsn;
        }

        // Find phptype and dbsyntax
        if (($pos = strpos($dsn, '://')) !== false) {
            $str = substr($dsn, 0, $pos);
            $dsn = substr($dsn, $pos + 3);
        } else {
            $str = $dsn;
            $dsn = null;
        }

        // Get phptype and dbsyntax
        // $str => phptype(dbsyntax)
        if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
            $parsed['phptype']  = $arr[1];
            $parsed['dbsyntax'] = !$arr[2] ? $arr[1] : $arr[2];
        } else {
            $parsed['phptype']  = $str;
            $parsed['dbsyntax'] = $str;
        }

        if (!count($dsn)) {
            return $parsed;
        }

        // Get (if found): username and password
        // $dsn => username:password@protocol+hostspec/database
        if (($at = strrpos($dsn,'@')) !== false) {
            $str = substr($dsn, 0, $at);
            $dsn = substr($dsn, $at + 1);
            if (($pos = strpos($str, ':')) !== false) {
                $parsed['username'] = rawurldecode(substr($str, 0, $pos));
                $parsed['password'] = rawurldecode(substr($str, $pos + 1));
            } else {
                $parsed['username'] = rawurldecode($str);
            }
        }

        // Find protocol and hostspec

        // $dsn => proto(proto_opts)/database
        if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {
            $proto       = $match[1];
            $proto_opts  = $match[2] ? $match[2] : false;
            $dsn         = $match[3];

        // $dsn => protocol+hostspec/database (old format)
        } else {
            if (strpos($dsn, '+') !== false) {
                list($proto, $dsn) = explode('+', $dsn, 2);
            }
            if (strpos($dsn, '/') !== false) {
                list($proto_opts, $dsn) = explode('/', $dsn, 2);
            } else {
                $proto_opts = $dsn;
                $dsn = null;
            }
        }

        // process the different protocol options
        $parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
        $proto_opts = rawurldecode($proto_opts);
        if ($parsed['protocol'] == 'tcp') {
            if (strpos($proto_opts, ':') !== false) {
                list($parsed['hostspec'], $parsed['port']) = explode(':', $proto_opts);
            } else {
                $parsed['hostspec'] = $proto_opts;
            }
        } elseif ($parsed['protocol'] == 'unix') {
            $parsed['socket'] = $proto_opts;
        }

        // Get dabase if any
        // $dsn => database
        if ($dsn) {
            // /database
            if (($pos = strpos($dsn, '?')) === false) {
                $parsed['database'] = $dsn;
            // /database?param1=value1&param2=value2
            } else {
                $parsed['database'] = substr($dsn, 0, $pos);
                $dsn = substr($dsn, $pos + 1);
                if (strpos($dsn, '&') !== false) {
                    $opts = explode('&', $dsn);
                } else { // database?param1=value1
                    $opts = array($dsn);
                }
                foreach ($opts as $opt) {
                    list($key, $value) = explode('=', $opt);
                    if (!isset($parsed[$key])) {
                        // don't allow params overwrite
                        $parsed[$key] = rawurldecode($value);
                    }
                }
            }
        }

        return $parsed;
    }
}

/**
 * MDB2_Driver_Common: Base class that is extended by each MDB2 driver
 *
 * @package MDB2
 * @category Database
 * @author Lukas Smith <smith@backendmedia.com>
 */
class MDB2_Driver_Common extends PEAR
{
    // {{{ properties
    /**
     * index of the MDB2 object within the $GLOBALS['_MDB2_databases'] array
     * @var integer
     * @access public
     */
    var $db_index = 0;

    /**
     * DSN used for the next query
     * @var array
     * @access private
     */
    var $dsn = array();

    /**
     * DSN that was used to create the current connection
     * @var array
     * @access private
     */
    var $connected_dsn = array();

    /**
     * connection resource
     * @var mixed
     * @access private
     */
    var $connection = 0;

    /**
     * if the current opened connection is a persistent connection
     * @var boolean
     * @access private
     */
    var $opened_persistent;

    /**
     * the name of the database for the next query
     * @var string
     * @access private
     */
    var $database_name = '';

    /**
     * the name of the database currrently selected
     * @var string
     * @access private
     */
    var $connected_database_name = '';

    /**
     * list of all supported features of the given driver
     * @var array
     * @access public
     */
    var $supported = array();

    /**
     * $options['result_class'] -> class used for result sets
     * $options['buffered_result_class'] -> class used for buffered result sets
     * $options['result_wrap_class'] -> class used to wrap result sets into
     * $options['result_buffering'] -> boolean should results be buffered or not?
     * $options['persistent'] -> boolean persistent connection?
     * $options['debug'] -> integer numeric debug level
     * $options['debug_handler'] -> string function/meothd that captures debug messages
     * $options['lob_buffer_length'] -> integer LOB buffer length
     * $options['log_line_break'] -> string line-break format
     * $options['seqname_format'] -> string pattern for sequence name
     * $options['sequence_col_name'] -> string sequence column name
     * $options['use_transactions'] -> boolean
     * $options['decimal_places'] -> integer
     * $options['portability'] -> portability constant
     * @var array
     * @access public
     */
    var $options = array(
            'result_class' => 'MDB2_Result_%s',
            'buffered_result_class' => 'MDB2_BufferedResult_%s',
            'result_wrap_class' => false,
            'result_buffering' => true,
            'persistent' => false,
            'debug' => 0,
            'debug_handler' => 'MDB2_defaultDebugOutput',
            'lob_buffer_length' => 8192,
            'log_line_break' => "\n",
            'seqname_format' => '%s_seq',
            'seqname_col_name' => 'sequence',
            'use_transactions' => false,
            'decimal_places' => 2,
            'portability' => MDB2_PORTABILITY_ALL,
        );

    /**
     * escape character
     * @var string
     * @access private
     */
    var $escape_quotes = '';

    /**
     * warnings
     * @var array
     * @access private
     */
    var $warnings = array();

    /**
     * string with the debugging information
     * @var string
     * @access public
     */
    var $debug_output = '';

    /**
     * determine if queries should auto commit or not
     * @var boolean
     * @access public
     */
    var $auto_commit = true;

    /**
     * determine if there is an open transaction
     * @var boolean
     * @access private
     */
    var $in_transaction = false;

    /**
     * result offset used in the next query
     * @var integer
     * @access private
     */
    var $row_offset = 0;

    /**
     * result limit used in the next query
     * @var integer
     * @access private
     */
    var $row_limit = 0;

    /**
     * Database backend used in PHP (mysql, odbc etc.)
     * @var string
     * @access private
     */
    var $phptype;

    /**
     * Database used with regards to SQL syntax etc.
     * @var string
     * @access private
     */
    var $dbsyntax;

    /**
     * contains metadata about all prepared queries
     * @var array
     * @access private
     */
    var $prepared_queries = array();

    /**
     * the last query sent to the driver
     * @var string
     * @access public
     */
    var $last_query = '';

    /**
     * the default fetchmode used
     * @var integer
     * @access private
     */
    var $fetchmode = MDB2_FETCHMODE_ORDERED;

    /**
     * the affected rows from the last manipulation query
     * @var integer
     * @access private
     */
    var $affected_rows = -1;

    /**
     * contains all LOB objects created with this MDB2 instance
    * @var array
    * @access private
    */
    var $lobs = array();

    /**
     * contains all CLOB objects created with this MDB2 instance
    * @var array
    * @access private
    */
    var $clobs = array();

    /**
     * contains all BLOB objects created with this MDB2 instance
    * @var array
    * @access private
    */
    var $blobs = array();

    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function MDB2_Driver_Common()
    {
        $db_index = count($GLOBALS['_MDB2_databases']) + 1;
        $GLOBALS['_MDB2_databases'][$db_index] = &$this;
        $this->db_index = $db_index;

        $this->PEAR();
    }

    // }}}
    // {{{ __toString()

    /**
     * String conversation
     *
     * @return string
     * @access public
     */
    function __toString()
    {
        $info = get_class($this);
        $info .= ': (phptype = '.$this->phptype.', dbsyntax = '.$this->dbsyntax.')';
        if ($this->connection) {
            $info .= ' [connected]';
        }
        return $info;
    }

    // }}}
    // {{{ errorInfo()

    /**
     * This method is used to collect information about an error
     *
     * @param integer $error
     * @return array
     * @access public
     */
    function errorInfo($error = null)
    {
        return array($error, null, null);
    }

    // }}}
    // {{{ raiseError()

    /**
     * This method is used to communicate an error and invoke error
     * callbacks etc.  Basically a wrapper for PEAR::raiseError
     * without the message string.
     *
     * @param mixed    integer error code, or a PEAR error object (all
     *                 other parameters are ignored if this parameter is
     *                 an object
     *
     * @param int      error mode, see PEAR_Error docs
     *
     * @param mixed    If error mode is PEAR_ERROR_TRIGGER, this is the
     *                 error level (E_USER_NOTICE etc).  If error mode is
     *                 PEAR_ERROR_CALLBACK, this is the callback function,
     *                 either as a function name, or as an array of an
     *                 object and method name.  For other error modes this
     *                 parameter is ignored.
     *
     * @param string   Extra debug information.  Defaults to the last
     *                 query and native error code.
     *
     * @return object  a PEAR error object
     *
     * @see PEAR_Error
     */
    function &raiseError($code = null, $mode = null, $options = null,
                         $userinfo = null)
    {
        // The error is yet a MDB2 error object
        if (is_object($code)) {
            return PEAR::raiseError($code, null, null, null, null, null, true);
        }

        if (is_null($userinfo) && isset($this->connection) && $this->connection) {
            if (!empty($this->last_query)) {
                $userinfo = "[Last query: {$this->last_query}]\n";
            }
            $native_errno = $native_msg = null;
            list($code, $native_errno, $native_msg) = $this->errorInfo($code);
            if ($native_errno !== null) {
                $userinfo .= "[Native code: $native_errno]\n";
            }
            if ($native_msg !== null) {
                $userinfo .= "[Native message: ". strip_tags($native_msg) ."]\n";
            }
        } else {
            $userinfo = "[Error message: $userinfo]\n";
        }
        if (empty($code)) {
            $code = MDB2_ERROR;
        }
        $msg = MDB2::errorMessage($code);
        return PEAR::raiseError("MDB2 Error: $msg", $code, $mode, $options, $userinfo);
    }

    // }}}
    // {{{ errorNative()

    /**
     * returns an errormessage, provides by the database
     *
     * @return mixed MDB2 Error Object or message
     * @access public
     */
    function errorNative()
    {
        return $this->raiseError(MDB2_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ resetWarnings()

    /**
     * reset the warning array
     *
     * @access public
     */
    function resetWarnings()
    {
        $this->warnings = array();
    }

    // }}}
    // {{{ getWarnings()

    /**
     * get all warnings in reverse order.
     * This means that the last warning is the first element in the array
     *
     * @return array with warnings
     * @access public
     * @see resetWarnings()
     */
    function getWarnings()
    {
        return array_reverse($this->warnings);
    }

    // }}}
    // {{{ setFetchMode()

    /**
     * Sets which fetch mode should be used by default on queries
     * on this connection.
     *
     * @param integer $fetchmode MDB2_FETCHMODE_ORDERED or MDB2_FETCHMODE_ASSOC,
     *       possibly bit-wise OR'ed with MDB2_FETCHMODE_FLIPPED.
     * @access public
     * @see MDB2_FETCHMODE_ORDERED
     * @see MDB2_FETCHMODE_ASSOC
     * @see MDB2_FETCHMODE_FLIPPED
     */
    function setFetchMode($fetchmode)
    {
        switch ($fetchmode) {
            case MDB2_FETCHMODE_ORDERED:
            case MDB2_FETCHMODE_ASSOC:
                $this->fetchmode = $fetchmode;
                break;
            default:
                return $this->raiseError('invalid fetchmode mode');
        }
    }

    // }}}
    // {{{ setOption()

    /**
     * set the option for the db class
     *
     * @param string $option option name
     * @param mixed $value value for the option
     * @return mixed MDB2_OK or MDB2 Error Object
     * @access public
     */
    function setOption($option, $value)
    {
        if (isset($this->options[$option])) {
            if (is_null($value)) {
                return $this->raiseError(MDB2_ERROR, null, null,
                    'may not set an option to value null');
            }
            $this->options[$option] = $value;
            return MDB2_OK;
        }
        return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            "unknown option $option");
    }

    // }}}
    // {{{ getOption()

    /**
     * returns the value of an option
     *
     * @param string $option option name
     * @return mixed the option value or error object
     * @access public
     */
    function getOption($option)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }
        return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            "unknown option $option");
    }

    // }}}
    // {{{ debug()

    /**
     * set a debug message
     *
     * @param string $message Message with information for the user.
     * @access public
     */
    function debug($message, $scope = '')
    {
        if ($this->options['debug'] && $this->options['debug_handler']) {
            call_user_func_array($this->options['debug_handler'], array(&$this, $scope, $message));
        }
    }

    // }}}
    // {{{ debugOutput()

    /**
     * output debug info
     *
     * @return string content of the debug_output class variable
     * @access public
     */
    function debugOutput()
    {
        return $this->debug_output;
    }

    // }}}
    // {{{ escape()

    /**
     * Quotes a string so it can be safely used in a query. It will quote
     * the text so it can safely be used within a query.
     *
     * @param string $text the input string to quote
     * @return string quoted string
     * @access public
     */
    function escape($text)
    {
        if (strcmp($this->escape_quotes, "'")) {
            $text = str_replace($this->escape_quotes, $this->escape_quotes.$this->escape_quotes, $text);
        }
        return str_replace("'", $this->escape_quotes . "'", $text);
    }

    // }}}
    // {{{ quoteIdentifier()

    /**
     * Quote a string so it can be safely used as a table or column name
     *
     * Delimiting style depends on which database driver is being used.
     *
     * NOTE: just because you CAN use delimited identifiers doesn't mean
     * you SHOULD use them.  In general, they end up causing way more
     * problems than they solve.
     *
     * Portability is broken by using the following characters inside
     * delimited identifiers:
     *   + backtick (<kbd>`</kbd>) -- due to MySQL
     *   + double quote (<kbd>"</kbd>) -- due to Oracle
     *   + brackets (<kbd>[</kbd> or <kbd>]</kbd>) -- due to Access
     *
     * Delimited identifiers are known to generally work correctly under
     * the following drivers:
     *   + mssql
     *   + mysql
     *   + mysqli
     *   + oci8
     *   + odbc(access)
     *   + odbc(db2)
     *   + pgsql
     *   + sqlite
     *   + sybase
     *
     * InterBase doesn't seem to be able to use delimited identifiers
     * via PHP 4.  They work fine under PHP 5.
     *
     * @param string $str  identifier name to be quoted
     *
     * @return string  quoted identifier string
     *
     * @access public
     */
    function quoteIdentifier($str)
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }

    // }}}
    // {{{ _rtrimArrayValues()

    /**
     * Right trim all strings in an array
     *
     * @param array  $array  the array to be trimmed (passed by reference)
     * @return void
     * @access private
     */
    function _rtrimArrayValues(&$array)
    {
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $array[$key] = rtrim($value);
            }
        }
    }

    // }}}
    // {{{ _convertEmptyArrayValuesToNull()

    /**
     * Convert all empty values in an array to null strings
     *
     * @param array  $array  the array to be de-nullified (passed by reference)
     * @return void
     * @access private
     */
    function _convertEmptyArrayValuesToNull(&$array)
    {
        foreach ($array as $key => $value) {
            if ($value === '') {
                $array[$key] = null;
            }
        }
    }

    // }}}
    // {{{ loadModule()

    /**
     * loads a module
     *
     * @param string $module name of the module that should be loaded
     *      (only used for error messages)
     * @param string $property name of the property into which the class will be loaded
     * @return object on success a reference to the given module is returned
     *                and on failure a PEAR error
     * @access public
     */
    function &loadModule($module, $property = null)
    {
        $module = strtolower($module);
        if (!$property) {
            $property = $module;
        }
        if (!isset($this->{$property})) {
            $include_dir = 'MDB2/';
            if (@include_once($include_dir.ucfirst($module).'.php')) {
                $class_name = 'MDB2_'.ucfirst($module);
            } elseif (@include_once($include_dir.'/Driver/'.ucfirst($module).'/'.$this->phptype.'.php')) {
                $class_name = 'MDB2_Driver_'.ucfirst($module).'_'.$this->phptype;
            } else {
                $error =& $this->raiseError(MDB2_ERROR_LOADMODULE, null, null,
                    'unable to find module: '.$module);
                return $error;
            }

            if (!class_exists($class_name)) {
                $error =& $this->raiseError(MDB2_ERROR_LOADMODULE, null, null,
                    'unable to load module: '.$module.' into property: '.$property);
                return $error;
            }
            $this->{$property} =& new $class_name($this->db_index);
        } else if (!is_object($this->{$property})) {
            $error =& $this->raiseError(MDB2_ERROR_LOADMODULE, null, null,
                'unable to load module: '.$module.' into property: '.$property);
            return $error;
        }
        return $this->{$property};
    }

    // }}}
    // {{{ autoCommit()

    /**
     * Define whether database changes done on the database be automatically
     * committed. This function may also implicitly start or end a transaction.
     *
     * @param boolean $auto_commit flag that indicates whether the database
     *      changes should be committed right after executing every query
     *      statement. If this argument is 0 a transaction implicitly started.
     *      Otherwise, if a transaction is in progress it is ended by committing
     *      any database changes that were pending.
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function autoCommit($auto_commit)
    {
        $this->debug(($auto_commit ? 'On' : 'Off'), 'autoCommit');
        return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'autoCommit: transactions are not supported');
    }

    // }}}
    // {{{ commit()

    /**
     * Commit the database changes done during a transaction that is in
     * progress. This function may only be called when auto-committing is
     * disabled, otherwise it will fail. Therefore, a new transaction is
     * implicitly started after committing the pending changes.
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function commit()
    {
        $this->debug('commiting transaction', 'commit');
        return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'commit: commiting transactions is not supported');
    }

    // }}}
    // {{{ rollback()

    /**
     * Cancel any database changes done during a transaction that is in
     * progress. This function may only be called when auto-committing is
     * disabled, otherwise it will fail. Therefore, a new transaction is
     * implicitly started after canceling the pending changes.
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function rollback()
    {
        $this->debug('rolling back transaction', 'rollback');
        return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'rollback: rolling back transactions is not supported');
    }

    // }}}
    // {{{ disconnect()

    /**
     * Log out and disconnect from the database.
     *
     * @return mixed true on success, false if not connected and error
     *                object on error
     * @access public
     */
    function disconnect()
    {
        if ($this->in_transaction
            && !MDB2::isError($this->rollback())
            && !MDB2::isError($this->autoCommit(true))
        ) {
            $this->in_transaction = false;
        }
        return $this->_close();
    }

    // }}}
    // {{{ _close()

    /**
     * all the RDBMS specific things needed to close a DB connection
     *
     * @access private
     */
    function _close()
    {
        unset($GLOBALS['_MDB2_databases'][$this->db_index]);
        return MDB2_OK;
    }

    // }}}
    // {{{ setDatabase()

    /**
     * Select a different database
     *
     * @param string $name name of the database that should be selected
     * @return string name of the database previously connected to
     * @access public
     */
    function setDatabase($name)
    {
        $previous_database_name = (isset($this->database_name)) ? $this->database_name : '';
        $this->database_name = $name;
        return $previous_database_name;
    }

    // }}}
    // {{{ setDSN()

    /**
     * set the DSN
     *
     * @param mixed     $dsn    DSN string or array
     * @return MDB2_OK
     * @access public
     */
    function setDSN($dsn)
    {
        $dsn_default = array (
            'phptype'  => false,
            'dbsyntax' => false,
            'username' => false,
            'password' => false,
            'protocol' => false,
            'hostspec' => false,
            'port'     => false,
            'socket'   => false,
            'mode'     => false,
        );
        $dsn = MDB2::parseDSN($dsn);
        if (isset($dsn['database'])) {
            $this->database_name = $dsn['database'];
            unset($dsn['database']);
        }
        $this->dsn = array_merge($dsn_default, $dsn);
        return MDB2_OK;
    }

    // }}}
    // {{{ getDSN()

    /**
     * return the DSN as a string
     *
     * @param string     $type    type to return
     * @return mixed DSN in the chosen type
     * @access public
     */
    function getDSN($type = 'string')
    {
        $dsn_default = array (
            'phptype'  => $this->phptype,
            'dbsyntax' => false,
            'username' => false,
            'password' => false,
            'protocol' => false,
            'hostspec' => false,
            'port'     => false,
            'socket'   => false,
            'database' => $this->database_name,
            'mode'     => false,
        );
        $dsn = array_merge($dsn_default, $this->dsn);
        switch ($type) {
            // expand to include all possible options
            case 'string':
                $dsn = $dsn['phptype'].'://'.$dsn['username'].':'.
                    $dsn['password'].'@'.$dsn['hostspec'].
                    ($dsn['port'] ? (':'.$dsn['port']) : '').
                    '/'.$dsn['database'];
                break;
        }
        return $dsn;
    }

    // }}}
    // {{{ standaloneQuery()

   /**
     * execute a query as database administrator
     *
     * @param string $query the SQL query
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function standaloneQuery($query)
    {
        return $this->query($query);
    }

    // }}}
    // {{{ _modifyQuery()

    /**
     * This method is used by backends to alter queries for various
     * reasons.  It is defined here to assure that all implementations
     * have this method defined.
     *
     * @param string $query  query to modify
     * @return the new (modified) query
     * @access private
     */
    function _modifyQuery($query) {
        return $query;
    }

    // }}}
    // {{{ query()

    /**
     * Send a query to the database and return any results
     *
     * @param string $query the SQL query
     * @param mixed   $types  array that contains the types of the columns in
     *                        the result set
     * @param mixed $result_class string which specifies which result class to use
     * @param mixed $result_wrap_class string which specifies which class to wrap results in
     * @return mixed a result handle or MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function &query($query, $types = null, $result_class = false, $result_wrap_class = false)
    {
        $this->debug($query, 'query');
        $error =& $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'query: method not implemented');
        return $error;
    }

    // }}}
    // {{{ setLimit()

    /**
     * set the range of the next query
     *
     * @param string $limit number of rows to select
     * @param string $offset first row to select
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function setLimit($limit, $offset = null)
    {
        if (!isset($this->supported['limit_queries'])) {
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'setLimit: limit is not supported by this driver');
        }
        $limit = (int)$limit;
        if ($limit < 1) {
            return $this->raiseError(MDB2_ERROR_SYNTAX, null, null,
                'setLimit: it was not specified a valid selected range row limit');
        }
        $this->row_limit = $limit;
        if (!is_null($offset)) {
            $offset = (int)$offset;
            if ($offset < 0) {
                return $this->raiseError(MDB2_ERROR_SYNTAX, null, null,
                    'setLimit: it was not specified a valid first selected range row');
            }
            $this->row_offset = $offset;
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ subSelect()

    /**
     * simple subselect emulation: leaves the query untouched for all RDBMS
     * that support subselects
     *
     * @access public
     *
     * @param string $query the SQL query for the subselect that may only
     *                      return a column
     * @param string $type determines type of the field
     *
     * @return string the query
     */
    function subSelect($query, $type = false)
    {
        if ($this->supported['sub_selects'] == true) {
            return $query;
        }
        return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'subSelect: method not implemented');
    }

    // }}}
    // {{{ replace()

    /**
     * Execute a SQL REPLACE query. A REPLACE query is identical to a INSERT
     * query, except that if there is already a row in the table with the same
     * key field values, the REPLACE query just updates its values instead of
     * inserting a new row.
     *
     * The REPLACE type of query does not make part of the SQL standards. Since
     * pratically only MySQL implements it natively, this type of query is
     * emulated through this method for other DBMS using standard types of
     * queries inside a transaction to assure the atomicity of the operation.
     *
     * @param string $table name of the table on which the REPLACE query will
     *       be executed.
     * @param array $fields associative array that describes the fields and the
     *       values that will be inserted or updated in the specified table. The
     *       indexes of the array are the names of all the fields of the table.
     *       The values of the array are also associative arrays that describe
     *       the values and other properties of the table fields.
     *
     *       Here follows a list of field properties that need to be specified:
     *
     *       value
     *           Value to be assigned to the specified field. This value may be
     *           of specified in database independent type format as this
     *           function can perform the necessary datatype conversions.
     *
     *           Default: this property is required unless the Null property is
     *           set to 1.
     *
     *       type
     *           Name of the type of the field. Currently, all types Metabase
     *           are supported except for clob and blob.
     *
     *           Default: no type conversion
     *
     *       null
     *           Boolean property that indicates that the value for this field
     *           should be set to null.
     *
     *           The default value for fields missing in INSERT queries may be
     *           specified the definition of a table. Often, the default value
     *           is already null, but since the REPLACE may be emulated using
     *           an UPDATE query, make sure that all fields of the table are
     *           listed in this function argument array.
     *
     *           Default: 0
     *
     *       key
     *           Boolean property that indicates that this field should be
     *           handled as a primary key or at least as part of the compound
     *           unique index of the table that will determine the row that will
     *           updated if it exists or inserted a new row otherwise.
     *
     *           This function will fail if no key field is specified or if the
     *           value of a key field is set to null because fields that are
     *           part of unique index they may not be null.
     *
     *           Default: 0
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function replace($table, $fields)
    {
        if (!$this->supported['replace']) {
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'replace: replace query is not supported');
        }
        $count = count($fields);
        for ($keys = 0, $condition = $insert = $values = '', reset($fields), $colnum = 0;
            $colnum < $count;
            next($fields), $colnum++)
        {
            $name = key($fields);
            if ($colnum > 0) {
                $insert .= ', ';
                $values .= ', ';
            }
            $insert .= $name;
            if (isset($fields[$name]['null']) && $fields[$name]['null']) {
                $value = 'NULL';
            } else {
                if (isset($fields[$name]['type'])) {
                    $value = $this->quote($fields[$name]['value'], $fields[$name]['type']);
                } else {
                    $value = $fields[$name]['value'];
                }
            }
            $values .= $value;
            if (isset($fields[$name]['key']) && $fields[$name]['key']) {
                if ($value === 'NULL') {
                    return $this->raiseError(MDB2_ERROR_CANNOT_REPLACE, null, null,
                        'replace: key value '.$name.' may not be NULL');
                }
                $condition .= ($keys ? ' AND ' : ' WHERE ') . $name . '=' . $value;
                $keys++;
            }
        }
        if ($keys == 0) {
            return $this->raiseError(MDB2_ERROR_CANNOT_REPLACE, null, null,
                'replace: not specified which fields are keys');
        }
        $in_transaction = $this->in_transaction;
        if (!$in_transaction && MDB2::isError($result = $this->autoCommit(false))) {
            return $result;
        }
        $success = $this->query("DELETE FROM $table$condition");
        if (!MDB2::isError($success)) {
            $affected_rows = $this->affected_rows;
            $success = $this->query("INSERT INTO $table ($insert) VALUES ($values)");
            $affected_rows += $this->affected_rows;
        }

        if (!$in_transaction) {
            if (!MDB2::isError($success)) {
                if (!MDB2::isError($success = $this->commit())
                    && !MDB2::isError($success = $this->autoCommit(TRUE))
                    && isset($this->supported['affected_rows'])
                ) {
                    $this->affected_rows = $affected_rows;
                }
            } else {
                $this->rollback();
                $this->autoCommit(true);
            }
        }
        return $success;
    }

    // }}}
    // {{{ prepare()

    /**
     * Prepares a query for multiple execution with execute().
     * With some database backends, this is emulated.
     * prepare() requires a generic query as string like
     * 'INSERT INTO numbers VALUES(?,?,?)'. The ? are wildcards.
     * Types of wildcards:
     *    ? - a quoted scalar value, i.e. strings, integers
     *
     * @param string $query the query to prepare
     * @param array $types array thats specifies the types of the fields
     * @return mixed resource handle for the prepared query on success, a DB
     *        error on failure
     * @access public
     * @see execute
     */
    function prepare($query, $types = null)
    {
        $this->debug($query, 'prepare');
        $positions = array();
        for ($position = 0;
            $position < strlen($query) && is_int($question = strpos($query, '?', $position));
        ) {
            if (is_int($quote = strpos($query, "'", $position)) && $quote < $question) {
                if (!is_int($end_quote = strpos($query, "'", $quote + 1))) {
                    return $this->raiseError(MDB2_ERROR_SYNTAX, null, null,
                        'prepare: query with an unterminated text string specified');
                }
                switch ($this->escape_quotes) {
                    case '':
                    case "'":
                        $position = $end_quote + 1;
                        break;
                    default:
                        if ($end_quote == $quote + 1) {
                            $position = $end_quote + 1;
                        } else {
                            if ($query[$end_quote-1] == $this->escape_quotes) {
                                $position = $end_quote;
                            } else {
                                $position = $end_quote + 1;
                            }
                        }
                        break;
                }
            } else {
                $positions[] = $question;
                $position = $question + 1;
            }
        }
        if (!$types) {
            if ($count = count($positions)) {
                $types = array_fill(0, $count, null);
            } else {
                $types = array();
            }
        } else if (!is_array($types)) {
            if ($count = count($positions)) {
                $types = array_fill(0, $count, $types);
            } else {
                $types = array();
            }
        }
        $result = $this->loadModule('datatype');
        if (MDB2::isError($result)) {
            return $result;
        }
        $this->prepared_queries[] = array(
            'query' => $query,
            'positions' => $positions,
            'types' => $types,
            'values' => array(),
        );
        $prepared_query = count($this->prepared_queries);
        if ($this->row_limit > 0) {
            $this->prepared_queries[$prepared_query-1]['offset'] = $this->row_offset;
            $this->prepared_queries[$prepared_query-1]['limit'] = $this->row_limit;
        }
        return $prepared_query;
    }

    // }}}
    // {{{ _validatePrepared()

    /**
     * validate that a handle is infact a prepared query
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepare()
     * @access private
     */
    function _validatePrepared($prepared_query)
    {
        if ($prepared_query < 1 || $prepared_query > count($this->prepared_queries)) {
            return $this->raiseError(MDB2_ERROR_INVALID, null, null,
                'invalid prepared query');
        }
        if (!is_array($this->prepared_queries[$prepared_query-1])) {
            return $this->raiseError(MDB2_ERROR_INVALID, null, null,
                'prepared query was already freed');
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ setParam()

    /**
     * Set the value of a parameter of a prepared query.
     *
     * @param int $prepared_query argument is a handle that was returned
     *       by the function prepare()
     * @param int $parameter the order number of the parameter in the query
     *       statement. The order number of the first parameter is 1.
     * @param mixed $value value that is meant to be assigned to specified
     *       parameter. The type of the value depends on the $type argument.
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function setParam($prepared_query, $parameter, $value)
    {
       $result = $this->_validatePrepared($prepared_query);
        if (MDB2::isError($result)) {
            return $result;
        }

        if ($parameter < 1
            || $parameter > count($this->prepared_queries[$prepared_query-1]['positions'])
        ) {
            return $this->raiseError(MDB2_ERROR_SYNTAX, null, null,
                'setParam: it was not specified a valid argument number');
        }

        $this->prepared_queries[$prepared_query-1]['values'][$parameter-1] = $value;
        return MDB2_OK;
    }

    // }}}
    // {{{ setParamArray()

    /**
     * Set the values of multiple a parameter of a prepared query in bulk.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepare()
     * @param array $params array thats specifies all necessary infromation
     *       for setParam() the array elements must use keys corresponding to
     *       the number of the position of the parameter.
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     * @see setParam()
     */
    function setParamArray($prepared_query, $params)
    {
        for ($i = 0, $j = count($params); $i < $j; ++$i) {
            $success = $this->setParam($prepared_query, $i + 1, $params[$i]);
            if (MDB2::isError($success)) {
                return $success;
            }
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ freePrepared()

    /**
     * Release resources allocated for the specified prepared query.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepare()
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function freePrepared($prepared_query)
    {
        $result = $this->_validatePrepared($prepared_query);
        if (MDB2::isError($result)) {
            return $result;
        }
        $this->prepared_queries[$prepared_query-1] = '';
        return MDB2_OK;
    }

    // }}}
    // {{{ _executePrepared()

    /**
     * Execute a prepared query statement.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepare()
     * @param string $query query to be executed
     * @param array $types array that contains the types of the columns in
     *       the result set
     * @param mixed $result_class string which specifies which result class to use
     * @param mixed $result_wrap_class string which specifies which class to wrap results in
     * @return mixed a result handle or MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    function &_executePrepared($prepared_query, $query, $types = null,
        $result_class = false, $result_wrap_class = false)
    {
        $result =& $this->query($query, $types, $result_class, $result_wrap_class);
        return $result;
    }

    // }}}
    // {{{ execute()

    /**
     * Execute a prepared query statement.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepare()
     * @param array $types array that contains the types of the columns in the
     *       result set
     * @param mixed $result_class string which specifies which result class to use
     * @param mixed $result_wrap_class string which specifies which class to wrap results in
     * @return mixed a result handle or MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function &execute($prepared_query, $types = null,
        $result_class = false, $result_wrap_class = false)
    {
        $result = $this->_validatePrepared($prepared_query);
        if (MDB2::isError($result)) {
            return $result;
        }
        $query = '';
        $index = $prepared_query - 1;
        $this->clobs[$prepared_query] = $this->blobs[$prepared_query] = array();
        $count = count($this->prepared_queries[$index]['positions']);
        for ($last_position = $position = 0; $position < $count; $position++) {
            $current_position = $this->prepared_queries[$index]['positions'][$position];
            $query .= substr($this->prepared_queries[$index]['query'],
                $last_position, $current_position - $last_position);
            if (!isset($this->prepared_queries[$index]['values'][$position])
                && !isset($this->prepared_queries[$index]['types'][$position])
            ) {
                $value_quoted = 'NULL';
            } else {
                $value = $this->prepared_queries[$index]['values'][$position];
                $type = $this->prepared_queries[$index]['types'][$position];
                if ($type == 'clob' || $type == 'blob') {
                    if (is_array($value)) {
                        $value['database'] = &$this;
                        $value['prepared_query'] = $prepared_query;
                        $value['parameter'] = $position + 1;
                        $this->prepared_queries[$index]['fields'][$position] = $value['field'];
                        $value = $this->datatype->createLOB($value);
                        if (MDB2::isError($value)) {
                            return $value;
                        }
                    }
                }
                $value_quoted = $this->quote($value, $type);
                if (MDB2::isError($value_quoted)) {
                    return $value_quoted;
                }
                if (is_numeric($value)) {
                    if ($type == 'clob') {
                        $this->clobs[$prepared_query][$value] = $value_quoted;
                    } elseif ($type == 'blob') {
                        $this->blobs[$prepared_query][$value] = $value_quoted;
                    }
                }
            }
            $query .= $value_quoted;
            $last_position = $current_position + 1;
        }

        $query .= substr($this->prepared_queries[$index]['query'], $last_position);
        if ($this->row_limit > 0) {
            $this->prepared_queries[$index]['offset'] = $this->row_offset;
            $this->prepared_queries[$index]['limit'] = $this->row_limit;
        }
        if (isset($this->prepared_queries[$index]['limit'])
            && $this->prepared_queries[$index]['limit'] > 0
        ) {
            $this->row_offset = $this->prepared_queries[$index]['offset'];
            $this->row_limit = $this->prepared_queries[$index]['limit'];
        } else {
            $this->row_offset = $this->row_limit = 0;
        }
        $success =& $this->_executePrepared($prepared_query, $query, $types, $result_class, $result_wrap_class);

        foreach ($this->clobs[$prepared_query] as $key => $value) {
             $this->datatype->destroyLOB($key);
             $this->datatype->freeCLOBValue($key, $value);
        }
        unset($this->clobs[$prepared_query]);
        foreach ($this->blobs[$prepared_query] as $key => $value) {
             $this->datatype->destroyLOB($key);
             $this->datatype->freeBLOBValue($key, $value);
        }
        unset($this->blobs[$prepared_query]);
        return $success;
    }

    // }}}
    // {{{ affectedRows()

    /**
     * returns the affected rows of a query
     *
     * @return mixed MDB2 Error Object or number of rows
     * @access public
     */
    function affectedRows()
    {
        if (!$this->supports('affected_rows')) {
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'affectedRows: method not implemented');
        }
        if ($this->affected_rows == -1) {
            return $this->raiseError(MDB2_ERROR_NEED_MORE_DATA);
        }
        return $this->affected_rows;
    }

    // }}}
    // {{{ quote()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param string $type type to which the value should be converted to
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access public
     */
    function quote($value, $type = null)
    {
        if (is_null($value)) {
            return 'NULL';
        } elseif (is_null($type)) {
            switch (gettype($value)) {
            case 'integer':
                $type = 'integer';
                break;
            case 'double':
                // todo
                $type = 'decimal';
                $type = 'float';
                break;
            case 'boolean':
                $type = 'boolean';
                break;
            case 'array':
                // todo
                if (true && isset($value['data'])) {
                    $type = 'blob';
                } else {
                    $type = 'clob';
                }
                break;
            default:
                if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $value)) {
                    $type = 'timestamp';
                } elseif (preg_match('/\d{2}:\d{2}/', $value)) {
                    $type = 'time';
                } elseif (preg_match('/\d{4}-\d{2}-\d{2}/', $value)) {
                    $type = 'date';
                } else {
                    $type = 'text';
                }
                break;
            }
        }

        $result = $this->loadModule('datatype');
        if (MDB2::isError($result)) {
            return $result;
        }
        if (method_exists($this->datatype, "quote{$type}")) {
            return $this->datatype->{"quote{$type}"}($value);
        }
        return $this->raiseError('type not defined: '.$type);
    }

    // }}}
    // {{{ getDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare
     * of the given type
     *
     * @param string $type type to which the value should be converted to
     * @param string  $name   name the field to be declared.
     * @param string  $field  definition of the field
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getDeclaration($type, $name, $field)
    {
        $result = $this->loadModule('datatype');
        if (MDB2::isError($result)) {
            return $result;
        }
        if (method_exists($this->datatype, "get{$type}Declaration")) {
            return $this->datatype->{"get{$type}Declaration"}($name, $field);
        }
        return $this->raiseError('type not defined: '.$type);
    }

    // }}}
    // {{{ supports()

    /**
     * Tell whether a DB implementation or its backend extension
     * supports a given feature.
     *
     * @param string $feature name of the feature (see the MDB2 class doc)
     * @return boolean whether this DB implementation supports $feature
     * @access public
     */
    function supports($feature)
    {
        return (isset($this->supported[$feature]) && $this->supported[$feature]);
    }

    // }}}
    // {{{ getSequenceName()

    /**
     * adds sequence name formating to a sequence name
     *
     * @param string $sqn name of the sequence
     * @return string formatted sequence name
     * @access public
     */
    function getSequenceName($sqn)
    {
        return sprintf($this->options['seqname_format'],
            preg_replace('/[^a-z0-9_]/i', '_', $sqn));
    }

    // }}}
    // {{{ nextID()

    /**
     * returns the next free id of a sequence
     *
     * @param string $seq_name name of the sequence
     * @param boolean $ondemand when true the seqence is
     *                           automatic created, if it
     *                           not exists
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function nextID($seq_name, $ondemand = false)
    {
        return $this->raiseError(MDB2_ERROR_NOT_CAPABLE, null, null,
            'nextID: method not implemented');
    }

    // }}}
    // {{{ currID()

    /**
     * returns the current id of a sequence
     *
     * @param string $seq_name name of the sequence
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function currID($seq_name)
    {
        $this->warnings[] = 'database does not support getting current
            sequence value, the sequence value was incremented';
        $this->expectError(MDB2_ERROR_NOT_CAPABLE);
        $id = $this->nextID($seq_name);
        $this->popExpect(MDB2_ERROR_NOT_CAPABLE);
        if (MDB2::isError($id)) {
            if ($id->getCode() == MDB2_ERROR_NOT_CAPABLE) {
                return $this->raiseError(MDB2_ERROR_NOT_CAPABLE, null, null,
                    'currID: getting current sequence value not supported');
            }
            return $id;
        }
        return $id;
    }

    // }}}
    // {{{ queryOne()

    /**
     * Execute the specified query, fetch the value from the first column of
     * the first row of the result set and then frees
     * the result set.
     *
     * @param string $query the SELECT query statement to be executed.
     * @param string $type optional argument that specifies the expected
     *       datatype of the result set field, so that an eventual conversion
     *       may be performed. The default datatype is text, meaning that no
     *       conversion is performed
     * @return mixed MDB2_OK or field value on success, a MDB2 error on failure
     * @access public
     */
    function queryOne($query, $type = null)
    {
        $result = $this->query($query, $type);
        if (!MDB2::isResultCommon($result)) {
            return $result;
        }

        $one = $result->fetch();
        $result->free();
        return $one;
    }

    // }}}
    // {{{ queryRow()

    /**
     * Execute the specified query, fetch the values from the first
     * row of the result set into an array and then frees
     * the result set.
     *
     * @param string $query the SELECT query statement to be executed.
     * @param array $types optional array argument that specifies a list of
     *       expected datatypes of the result set columns, so that the eventual
     *       conversions may be performed. The default list of datatypes is
     *       empty, meaning that no conversion is performed.
     * @param int $fetchmode how the array data should be indexed
     * @return mixed MDB2_OK or data array on success, a MDB2 error on failure
     * @access public
     */
    function queryRow($query, $types = null, $fetchmode = MDB2_FETCHMODE_DEFAULT)
    {
        $result = $this->query($query, $types);
        if (!MDB2::isResultCommon($result)) {
            return $result;
        }

        $row = $result->fetchRow($fetchmode);
        $result->free();
        return $row;
    }

    // }}}
    // {{{ queryCol()

    /**
     * Execute the specified query, fetch the value from the first column of
     * each row of the result set into an array and then frees the result set.
     *
     * @param string $query the SELECT query statement to be executed.
     * @param string $type optional argument that specifies the expected
     *       datatype of the result set field, so that an eventual conversion
     *       may be performed. The default datatype is text, meaning that no
     *       conversion is performed
     * @param int $colnum the row number to fetch
     * @return mixed MDB2_OK or data array on success, a MDB2 error on failure
     * @access public
     */
    function queryCol($query, $type = null, $colnum = 0)
    {
        $result = $this->query($query, $type);
        if (!MDB2::isResultCommon($result)) {
            return $result;
        }

        $col = $result->fetchCol($colnum);
        $result->free();
        return $col;
    }

    // }}}
    // {{{ queryAll()

    /**
     * Execute the specified query, fetch all the rows of the result set into
     * a two dimensional array and then frees the result set.
     *
     * @param string $query the SELECT query statement to be executed.
     * @param array $types optional array argument that specifies a list of
     *       expected datatypes of the result set columns, so that the eventual
     *       conversions may be performed. The default list of datatypes is
     *       empty, meaning that no conversion is performed.
     * @param int $fetchmode how the array data should be indexed
     * @param boolean $rekey if set to true, the $all will have the first
     *       column as its first dimension
     * @param boolean $force_array used only when the query returns exactly
     *       two columns. If true, the values of the returned array will be
     *       one-element arrays instead of scalars.
     * @param boolean $group if true, the values of the returned array is
     *       wrapped in another array.  If the same key value (in the first
     *       column) repeats itself, the values will be appended to this array
     *       instead of overwriting the existing values.
     * @return mixed MDB2_OK or data array on success, a MDB2 error on failure
     * @access public
     */
    function queryAll($query, $types = null, $fetchmode = MDB2_FETCHMODE_DEFAULT,
        $rekey = false, $force_array = false, $group = false)
    {
        $result = $this->query($query, $types);
        if (!MDB2::isResultCommon($result)) {
            return $result;
        }

        $all = $result->fetchAll($fetchmode, $rekey, $force_array, $group);
        $result->free();
        return $all;
    }

    // }}}
    // {{{ executeParams()

    /**
     * Executes a prepared SQL query
     * With executeParams() the generic query of prepare is assigned with the given
     * data array. The values of the array inserted into the query in the same
     * order like the array order
     *
     * @param resource $prepared_query query handle from prepare()
     * @param array $types array that contains the types of the columns in
     *        the result set
     * @param array $params numeric array containing the data to insert into
     *        the query
     * @param mixed $result_class string which specifies which result class to use
     * @param mixed $result_wrap_class string which specifies which class to wrap results in
     * @return mixed MDB2_OK or a new result handle or a MDB2 Error Object when fail
     * @access public
     * @see prepare()
     */
    function &executeParams($prepared_query, $types = null, $params = false,
        $result_class = false, $result_wrap_class = false)
    {
        $this->setParamArray($prepared_query, $params);

        $result =& $this->execute($prepared_query, $types, $result_class, $result_wrap_class);
        return $result;
    }

    // }}}
    // {{{ executeMultiple()

    /**
     * This function does several executeParams() calls on the same statement handle.
     * $params must be an array indexed numerically from 0, one execute call is
     * done for every 'row' in the array.
     *
     * If an error occurs during executeParams(), executeMultiple() does not execute
     * the unfinished rows, but rather returns that error.
     *
     * @param resource $prepared_query query handle from prepare()
     * @param array $types array that contains the types of the columns in
     *        the result set
     * @param array $params numeric array containing the
     *        data to insert into the query
     * @return mixed a result handle or MDB2_OK on success, a MDB2 error on failure
     * @access public
     * @see prepare(), executeParams()
     */
    function executeMultiple($prepared_query, $types = null, $params = null)
    {
        for ($i = 0, $j = count($params); $i < $j; $i++) {
            $result = $this->executeParams($prepared_query, $types, $params[$i]);
            if (MDB2::isError($result)) {
                return $result;
            }
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ Destructor

    /**
    * this function closes open transactions to be executed at shutdown
    *
    * @access private
    */
    function _MDB2_Driver_Common()
    {
        if ($this->in_transaction && !MDB2::isError($this->rollback())) {
            $this->autoCommit(true);
        }
    }
}

class MDB2_Result
{
}

class MDB2_Result_Common extends MDB2_Result
{
    var $mdb;
    var $result;
    var $rownum = -1;
    var $types;

    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function MDB2_Result_Common(&$mdb, &$result)
    {
        $this->mdb =& $mdb;
        $this->result =& $result;
    }

    // }}}
    // {{{ setResultTypes()

    /**
     * Define the list of types to be associated with the columns of a given
     * result set.
     *
     * This function may be called before invoking fetch(),
     * fetchRow(), fetchCol() and fetchAll() so that the necessary data type
     * conversions are performed on the data to be retrieved by them. If this
     * function is not called, the type of all result set columns is assumed
     * to be text, thus leading to not perform any conversions.
     *
     * @param string $types array variable that lists the
     *       data types to be expected in the result set columns. If this array
     *       contains less types than the number of columns that are returned
     *       in the result set, the remaining columns are assumed to be of the
     *       type text. Currently, the types clob and blob are not fully
     *       supported.
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function setResultTypes($types)
    {
        $load = $this->mdb->loadModule('datatype');
        if (MDB2::isError($load)) {
            return $load;
        }
        return $this->mdb->datatype->setResultTypes($this, $types);
    }

    // }}}
    // {{{ fetch()

    /**
     * fetch value from a result set
     *
     * @param int $rownum number of the row where the data can be found
     * @param int $colnum field number where the data can be found
     * @return mixed string on success, a MDB2 error on failure
     * @access public
     */
    function fetch($rownum = 0, $colnum = 0)
    {
        return $this->mdb->raiseError(MDB2_ERROR_UNSUPPORTED, NULL, NULL,
            'fetch: method not implemented');
    }

    // }}}
    // {{{ seek()

    /**
    * seek to a specific row in a result set
    *
    * @param int    $rownum    number of the row where the data can be found
    * @return mixed MDB2_OK on success, a MDB2 error on failure
    * @access public
    */
    function seek($rownum = 0)
    {
        $target_rownum = $rownum - 1;
        if ($this->rownum > $target_rownum) {
            return $this->mdb->raiseError(MDB2_ERROR_UNSUPPORTED, NULL, NULL,
                'seek: seeking to previous rows not implemented');
        }
        while ($this->rownum < $target_rownum) {
            $this->fetchRow();
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ fetchRow()

    /**
     * Fetch and return a row of data
     *
     * @param int $fetchmode how the array data should be indexed
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function fetchRow($fetchmode = MDB2_FETCHMODE_DEFAULT)
    {
        $columns = $this->numCols();
        if (MDB2::isError($columns)) {
            return $columns;
        }
        $rownum = ++$this->rownum;
        if ($fetchmode == MDB2_FETCHMODE_DEFAULT) {
            $fetchmode = $this->mdb->fetchmode;
        }
        if ($fetchmode & MDB2_FETCHMODE_ASSOC) {
            $column_names = $this->getColumnNames();
            if (MDB2::isError($column_names)) {
                return $column_names;
            }
        }
        if ($this->mdb->options['portability'] & MDB2_PORTABILITY_EMPTY_TO_NULL) {
            $null_value = '';
        } else {
            $null_value = null;
        }
        for ($column = 0; $column < $columns; $column++) {
            if (!$this->resultIsNull($rownum, $column)) {
                $value = $this->fetch($rownum, $column);
                if (is_null($value)) {
                    return null;
                }
            } else {
                $value = $null_value;
            }
            $row[$column] = $value;
        }
        if ($fetchmode & MDB2_FETCHMODE_ASSOC) {
            $column_names = $this->getColumnNames();
            foreach ($column_names as $name => $i) {
                $column_names[$name] = $row[$i];
            }
            $row = $column_names;
            if (is_array($row)
                && $this->options['portability'] & MDB2_PORTABILITY_LOWERCASE
            ) {
                $row = array_change_key_case($row, CASE_LOWER);
            }
        }
        return $row;
    }

    // }}}
    // {{{ fetchCol()

    /**
     * Fetch and return a column of data (it uses current for that)
     *
     * @param int $colnum the column number to fetch
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function fetchCol($colnum = 0)
    {
        $column = array();
        $fetchmode = is_numeric($colnum) ? MDB2_FETCHMODE_ORDERED : MDB2_FETCHMODE_ASSOC;
        while (is_array($row = $this->fetchRow($fetchmode))) {
            if (!array_key_exists($colnum, $row)) {
                return($this->mdb->raiseError(MDB2_ERROR_TRUNCATED));
            }
            $column[] = $row[$colnum];
        }

        if (MDB2::isError($row)) {
            return $row;
        }
        return $column;
    }

    // }}}
    // {{{ fetchAll()

    /**
     * Fetch and return a column of data (it uses fetchRow for that)
     *
     * @param int $fetchmode how the array data should be indexed
     * @param boolean $rekey if set to true, the $all will have the first
     *       column as its first dimension
     * @param boolean $force_array used only when the query returns exactly
     *       two columns. If true, the values of the returned array will be
     *       one-element arrays instead of scalars.
     * @param boolean $group if true, the values of the returned array is
     *       wrapped in another array.  If the same key value (in the first
     *       column) repeats itself, the values will be appended to this array
     *       instead of overwriting the existing values.
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     * @see getAssoc()
     */
    function fetchAll($fetchmode = MDB2_FETCHMODE_DEFAULT, $rekey = false,
        $force_array = false, $group = false)
    {
        $all = array();
        while (is_array($row = $this->fetchRow($fetchmode))) {
            if ($rekey && count($row) < 2) {
                return $this->mdb->raiseError(MDB2_ERROR_TRUNCATED);
            }
            if ($rekey) {
                if ($fetchmode & MDB2_FETCHMODE_ASSOC) {
                    $key = reset($row);
                    unset($row[key($row)]);
                } else {
                    $key = array_shift($row);
                }
                if (!$force_array && count($row) == 1) {
                    $row = array_shift($row);
                }
                if ($group) {
                    $all[$key][] = $row;
                } else {
                    $all[$key] = $row;
                }
            } else {
                if ($fetchmode & MDB2_FETCHMODE_FLIPPED) {
                    foreach ($row as $key => $val) {
                        $all[$key][] = $val;
                    }
                } else {
                    $all[] = $row;
                }
            }
        }

        if (MDB2::isError($row)) {
            return $row;
        }
        return $all;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal result pointer to the next available result
     *
     * @param a valid result resource
     * @return true on success or an error object on failure
     * @access public
     */
    function nextResult()
    {
        return $this->mdb->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'nextResult: method not implemented');
    }

    // }}}
    // {{{ getColumnNames()

    /**
     * Retrieve the names of columns returned by the DBMS in a query result.
     *
     * @return mixed associative array variable
     *       that holds the names of columns. The indexes of the array are
     *       the column names mapped to lower case and the values are the
     *       respective numbers of the columns starting from 0. Some DBMS may
     *       not return any columns when the result set does not contain any
     *       rows.
     *      a MDB2 error on failure
     * @access public
     */
    function getColumnNames()
    {
        return $this->mdb->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'getColumnNames: method not implemented');
    }

    // }}}
    // {{{ numCols()

    /**
     * Count the number of columns returned by the DBMS in a query result.
     *
     * @return mixed integer value with the number of columns, a MDB2 error
     *       on failure
     * @access public
     */
    function numCols()
    {
        return $this->mdb->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'numCols: method not implemented');
    }

    // }}}
    // {{{ resultIsNull()

    /**
     * Determine whether the value of a query result located in given row and
     *    field is a null.
     *
     * @param int $rownum number of the row where the data can be found
     * @param int $colnum field number where the data can be found
     * @return mixed true or false on success, a MDB2 error on failure
     * @access public
     */
    function resultIsNull($rownum, $colnum)
    {
        $value = $this->fetch($rownum, $colnum);
        if (MDB2::isError($value)) {
            return $value;
        }
        return !isset($value);
    }

    // }}}
    // {{{ getResource()

    /**
     * return the resource associated with the result object
     *
     * @return resource
     * @access public
     */
    function getResource()
    {
        return $this->result;
    }

    // }}}
    // {{{ free()

    /**
     * Free the internal resources associated with result.
     *
     * @return boolean true on success, false if result is invalid
     * @access public
     */
    function free()
    {
        $this->result = null;
        return MDB2_OK;
    }
}

// }}}
// {{{ MDB2_defaultDebugOutput()

/**
 * default debug output handler
 *
 * @param object $db reference to an MDB2 database object
 * @param string $message message that should be appended to the debug
 *       variable
 * @return string the corresponding error message, of false
 * if the error code was unknown
 * @access public
 */
function MDB2_defaultDebugOutput(&$db, $scope, $message)
{
    $db->debug_output .= $scope.'('.$db->db_index.'): ';
    $db->debug_output .= $message.$db->getOption('log_line_break');
}

?>