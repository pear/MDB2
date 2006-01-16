<?php
// vim: set et ts=4 sw=4 fdm=marker:
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
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
// | Author: Paul Cooper <pgc@ucecom.com>                                 |
// +----------------------------------------------------------------------+
//
// $Id$

/**
 * MDB2 PostGreSQL driver
 *
 * @package MDB2
 * @category Database
 * @author  Paul Cooper <pgc@ucecom.com>
 */
class MDB2_Driver_pgsql extends MDB2_Driver_Common
{
    // {{{ properties
    var $escape_quotes = "\\";

    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct();

        $this->phptype = 'pgsql';
        $this->dbsyntax = 'pgsql';

        $this->supported['sequences'] = true;
        $this->supported['indexes'] = true;
        $this->supported['affected_rows'] = true;
        $this->supported['summary_functions'] = true;
        $this->supported['order_by_text'] = true;
        $this->supported['transactions'] = true;
        $this->supported['current_id'] = true;
        $this->supported['limit_queries'] = true;
        $this->supported['LOBs'] = true;
        $this->supported['replace'] = 'emulated';
        $this->supported['sub_selects'] = true;
        $this->supported['auto_increment'] = 'emulated';
        $this->supported['primary_key'] = true;
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
        // Fall back to MDB2_ERROR if there was no mapping.
        $error_code = MDB2_ERROR;

        $native_msg = '';
        if (is_resource($error)) {
            $native_msg = @pg_result_error($error);
        } elseif ($this->connection) {
            $native_msg = @pg_last_error($this->connection);
            if (!$native_msg && @pg_connection_status($this->connection) === PGSQL_CONNECTION_BAD) {
                $native_msg = 'Database connection has been lost.';
                $error_code = MDB2_ERROR_CONNECT_FAILED;
            }
        }

        static $error_regexps;
        if (empty($error_regexps)) {
            $error_regexps = array(
                '/column .* (of relation .*)?does not exist/i'
                    => MDB2_ERROR_NOSUCHFIELD,
                '/(relation|sequence|table).*does not exist|class .* not found/i'
                    => MDB2_ERROR_NOSUCHTABLE,
                '/index .* does not exist/'
                    => MDB2_ERROR_NOT_FOUND,
                '/relation .* already exists/i'
                    => MDB2_ERROR_ALREADY_EXISTS,
                '/(divide|division) by zero$/i'
                    => MDB2_ERROR_DIVZERO,
                '/pg_atoi: error in .*: can\'t parse /i'
                    => MDB2_ERROR_INVALID_NUMBER,
                '/invalid input syntax for( type)? (integer|numeric)/i'
                    => MDB2_ERROR_INVALID_NUMBER,
                '/value .* is out of range for type \w*int/i'
                    => MDB2_ERROR_INVALID_NUMBER,
                '/integer out of range/i'
                    => MDB2_ERROR_INVALID_NUMBER,
                '/value too long for type character/i'
                    => MDB2_ERROR_INVALID,
                '/attribute .* not found|relation .* does not have attribute/i'
                    => MDB2_ERROR_NOSUCHFIELD,
                '/column .* specified in USING clause does not exist in (left|right) table/i'
                    => MDB2_ERROR_NOSUCHFIELD,
                '/parser: parse error at or near/i'
                    => MDB2_ERROR_SYNTAX,
                '/syntax error at/'
                    => MDB2_ERROR_SYNTAX,
                '/column reference .* is ambiguous/i'
                    => MDB2_ERROR_SYNTAX,
                '/permission denied/'
                    => MDB2_ERROR_ACCESS_VIOLATION,
                '/violates not-null constraint/'
                    => MDB2_ERROR_CONSTRAINT_NOT_NULL,
                '/violates [\w ]+ constraint/'
                    => MDB2_ERROR_CONSTRAINT,
                '/referential integrity violation/'
                    => MDB2_ERROR_CONSTRAINT,
                '/more expressions than target columns/i'
                    => MDB2_ERROR_VALUE_COUNT_ON_ROW,
            );
        }
        foreach ($error_regexps as $regexp => $code) {
            if (preg_match($regexp, $native_msg)) {
                $error_code = $code;
                break;
            }
        }

        return array($error_code, null, $native_msg);
    }

    // }}}
    // {{{ beginTransaction()

    /**
     * Start a transaction.
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function beginTransaction()
    {
        $this->debug('starting transaction', 'beginTransaction');
        if ($this->in_transaction) {
            return MDB2_OK;  //nothing to do
        }
        if (!$this->destructor_registered && $this->opened_persistent) {
            $this->destructor_registered = true;
            register_shutdown_function('MDB2_closeOpenTransactions');
        }
        $result = $this->_doQuery('BEGIN', true);
        if (PEAR::isError($result)) {
            return $result;
        }
        $this->in_transaction = true;
        return MDB2_OK;
    }

    // }}}
    // {{{ commit()

    /**
     * Commit the database changes done during a transaction that is in
     * progress.
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function commit()
    {
        $this->debug('commit transaction', 'commit');
        if (!$this->in_transaction) {
            return $this->raiseError(MDB2_ERROR, null, null,
                'commit: transaction changes are being auto committed');
        }
        $result = $this->_doQuery('COMMIT', true);
        if (PEAR::isError($result)) {
            return $result;
        }
        $this->in_transaction = false;
        return MDB2_OK;
    }

    // }}}
    // {{{ rollback()

    /**
     * Cancel any database changes done during a transaction that is in
     * progress.
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function rollback()
    {
        $this->debug('rolling back transaction', 'rollback');
        if (!$this->in_transaction) {
            return $this->raiseError(MDB2_ERROR, null, null,
                'rollback: transactions can not be rolled back when changes are auto committed');
        }
        $result = $this->_doQuery('ROLLBACK', true);
        if (PEAR::isError($result)) {
            return $result;
        }
        $this->in_transaction = false;
        return MDB2_OK;
    }

    // }}}
    // {{{ _doConnect()

    /**
     * Does the grunt work of connecting to the database
     *
     * @return mixed connection resource on success, MDB2 Error Object on failure
     * @access protected
     **/
    function _doConnect($database_name, $persistent = false)
    {
        if ($database_name == '') {
            $database_name = 'template1';
        }

        $protocol = $this->dsn['protocol'] ? $this->dsn['protocol'] : 'tcp';

        $params = array('');
        if ($protocol == 'tcp') {
            if ($this->dsn['hostspec']) {
                $params[0].= 'host=' . $this->dsn['hostspec'];
            }
            if ($this->dsn['port']) {
                $params[0].= ' port=' . $this->dsn['port'];
            }
        } elseif ($protocol == 'unix') {
            // Allow for pg socket in non-standard locations.
            if ($this->dsn['socket']) {
                $params[0].= 'host=' . $this->dsn['socket'];
            }
            if ($this->dsn['port']) {
                $params[0].= ' port=' . $this->dsn['port'];
            }
        }
        if ($database_name) {
            $params[0].= ' dbname=\'' . addslashes($database_name) . '\'';
        }
        if ($this->dsn['username']) {
            $params[0].= ' user=\'' . addslashes($this->dsn['username']) . '\'';
        }
        if ($this->dsn['password']) {
            $params[0].= ' password=\'' . addslashes($this->dsn['password']) . '\'';
        }
        if (!empty($this->dsn['options'])) {
            $params[0].= ' options=' . $this->dsn['options'];
        }
        if (!empty($this->dsn['tty'])) {
            $params[0].= ' tty=' . $this->dsn['tty'];
        }
        if (!empty($this->dsn['connect_timeout'])) {
            $params[0].= ' connect_timeout=' . $this->dsn['connect_timeout'];
        }
        if (!empty($this->dsn['sslmode'])) {
            $params[0].= ' sslmode=' . $this->dsn['sslmode'];
        }
        if (!empty($this->dsn['service'])) {
            $params[0].= ' service=' . $this->dsn['service'];
        }

        if (isset($this->dsn['new_link'])
            && ($this->dsn['new_link'] == 'true' || $this->dsn['new_link'] === true))
        {
            if (version_compare(phpversion(), '4.3.0', '>=')) {
                $params[] = PGSQL_CONNECT_FORCE_NEW;
            }
        }

        $connect_function = $persistent ? 'pg_pconnect' : 'pg_connect';

        putenv('PGDATESTYLE=ISO');

        @ini_set('track_errors', true);
        $php_errormsg = '';
        $connection = @call_user_func_array($connect_function, $params);
        @ini_restore('track_errors');
        if (!$connection) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED,
                null, null, strip_tags($php_errormsg));
        }
        return $connection;
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to the database
     *
     * @return true on success, MDB2 Error Object on failure
     * @access public
     **/
    function connect()
    {
        if (is_resource($this->connection)) {
            if (count(array_diff($this->connected_dsn, $this->dsn)) == 0
                && $this->connected_database_name == $this->database_name
                && ($this->opened_persistent == $this->options['persistent'])
            ) {
                return MDB2_OK;
            }
            $this->disconnect(false);
        }

        if (!PEAR::loadExtension($this->phptype)) {
            return $this->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'connect: extension '.$this->phptype.' is not compiled into PHP');
        }

        if ($this->database_name) {
            $connection = $this->_doConnect($this->database_name, $this->options['persistent']);
            if (PEAR::isError($connection)) {
                return $connection;
            }
            $this->connection = $connection;
            $this->connected_dsn = $this->dsn;
            $this->connected_database_name = $this->database_name;
            $this->opened_persistent = $this->options['persistent'];
            $this->dbsyntax = $this->dsn['dbsyntax'] ? $this->dsn['dbsyntax'] : $this->phptype;
        }
        return MDB2_OK;
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
    function disconnect($force = true)
    {
        if (is_resource($this->connection)) {
            if ($this->in_transaction) {
                $this->rollback();
            }
            if (!$this->opened_persistent || $force) {
                @pg_close($this->connection);
            }
            $this->connection = 0;
            $this->in_transaction = false;
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ standaloneQuery()

   /**
     * execute a query as DBA
     *
     * @param string $query the SQL query
     * @param mixed   $types  array that contains the types of the columns in
     *                        the result set
     * @param boolean $is_manip  if the query is a manipulation query
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function &standaloneQuery($query, $types = null, $is_manip = false)
    {
        $connection = $this->_doConnect('template1', false);
        if (PEAR::isError($connection)) {
            $err =& $this->raiseError(MDB2_ERROR_CONNECT_FAILED, null, null,
                'Cannot connect to template1');
            return $err;
        }

        $offset = $this->row_offset;
        $limit = $this->row_limit;
        $this->row_offset = $this->row_limit = 0;
        $query = $this->_modifyQuery($query, $is_manip, $limit, $offset);

        $result = $this->_doQuery($query, $is_manip, $connection, false);
        @pg_close($connection);
        if (PEAR::isError($result)) {
            return $result;
        }

        if ($is_manip) {
            $affected_rows =  $this->_affectedRows($connection, $result);
            return $affected_rows;
        }
        $result =& $this->_wrapResult($result, $types, true, false, $limit, $offset);
        return $result;
    }

    // }}}
    // {{{ _doQuery()

    /**
     * Execute a query
     * @param string $query  query
     * @param boolean $is_manip  if the query is a manipulation query
     * @param resource $connection
     * @param string $database_name
     * @return result or error object
     * @access protected
     */
    function _doQuery($query, $is_manip = false, $connection = null, $database_name = null)
    {
        $this->last_query = $query;
        $this->debug($query, 'query');
        if ($this->options['disable_query']) {
            if ($is_manip) {
                return 0;
            }
            return null;
        }

        if (is_null($connection)) {
            $connection = $this->getConnection();
            if (PEAR::isError($connection)) {
                return $connection;
            }
        }

        $result = @pg_query($connection, $query);
        if (!$result) {
            return $this->raiseError();
        }

        return $result;
    }

    // }}}
    // {{{ _affectedRows()

    /**
     * returns the number of rows affected
     *
     * @param resource $result
     * @param resource $connection
     * @return mixed MDB2 Error Object or the number of rows affected
     * @access private
     */
    function _affectedRows($connection, $result = null)
    {
        if (is_null($connection)) {
            $connection = $this->getConnection();
            if (PEAR::isError($connection)) {
                return $connection;
            }
        }
        return @pg_affected_rows($result);
    }

    // }}}
    // {{{ _modifyQuery()

    /**
     * Changes a query string for various DBMS specific reasons
     *
     * @param string $query  query to modify
     * @return the new (modified) query
     * @access protected
     */
    function _modifyQuery($query, $is_manip, $limit, $offset)
    {
        if ($limit > 0
            && !preg_match('/LIMIT\s*\d(\s*(,|OFFSET)\s*\d+)?/i', $query)
        ) {
            $query = rtrim($query);
            if (substr($query, -1) == ';') {
                $query = substr($query, 0, -1);
            }
            if ($is_manip) {
                $manip = preg_replace('/^(DELETE FROM|UPDATE).*$/', '\\1', $query);
                $from = $match[2];
                $where = $match[3];
                $query = $manip.' '.$from.' WHERE ctid=(SELECT ctid FROM '.$from.' '.$where.' LIMIT '.$limit.')';
            } else {
                $query.= " LIMIT $limit OFFSET $offset";
            }
        }
        return $query;
    }

    // }}}
    // {{{ getServerVersion()

    /**
     * return version information about the server
     *
     * @param string     $native  determines if the raw version string should be returned
     * @return mixed array with versoin information or row string
     * @access public
     */
    function getServerVersion($native = false)
    {
        $query = 'SHOW SERVER_VERSION';
        $server_info = $this->queryOne($query, 'text');
        if (!$native && !PEAR::isError($server_info)) {
            $tmp = explode('.', $server_info);
            if (!array_key_exists(2, $tmp)) {
                preg_match('/(\d+)(.*)/', @$tmp[1], $tmp2);
                $server_info = array(
                    'major' => @$tmp[0],
                    'minor' => @$tmp2[1],
                    'patch' => null,
                    'extra' => @$tmp2[2],
                    'native' => $server_info,
                );
            } else {
                $server_info = array(
                    'major' => @$tmp[0],
                    'minor' => @$tmp[1],
                    'patch' => @$tmp[2],
                    'extra' => null,
                    'native' => $server_info,
                );
            }
        }
        return $server_info;
    }

    // }}}
    // {{{ prepare()

    /**
     * Prepares a query for multiple execution with execute().
     * With some database backends, this is emulated.
     * prepare() requires a generic query as string like
     * 'INSERT INTO numbers VALUES(?,?)' or
     * 'INSERT INTO numbers VALUES(:foo,:bar)'.
     * The ? and :[a-zA-Z] and  are placeholders which can be set using
     * bindParam() and the query can be send off using the execute() method.
     *
     * @param string $query the query to prepare
     * @param mixed   $types  array that contains the types of the placeholders
     * @param mixed   $result_types  array that contains the types of the columns in
     *                        the result set, if set to MDB2_PREPARE_MANIP the
                              query is handled as a manipulation query
     * @return mixed resource handle for the prepared query on success, a MDB2
     *        error on failure
     * @access public
     * @see bindParam, execute
     */
    function &prepare($query, $types = null, $result_types = null)
    {
        $is_manip = ($result_types === MDB2_PREPARE_MANIP);
        $offset = $this->row_offset;
        $limit = $this->row_limit;
        $this->row_offset = $this->row_limit = 0;
        $this->debug($query, 'prepare');
        if (!empty($types)) {
            $this->loadModule('Datatype', null, true);
        }
        $query = $this->_modifyQuery($query, $is_manip, $limit, $offset);
        $placeholder_type_guess = $placeholder_type = null;
        $question = '?';
        $colon = ':';
        $positions = array();
        $position = $parameter = 0;
        while ($position < strlen($query)) {
            $q_position = strpos($query, $question, $position);
            $c_position = strpos($query, $colon, $position);
            if ($q_position && $c_position) {
                $p_position = min($q_position, $c_position);
            } elseif ($q_position) {
                $p_position = $q_position;
            } elseif ($c_position) {
                $p_position = $c_position;
            } else {
                break;
            }
            if (is_null($placeholder_type)) {
                $placeholder_type_guess = $query[$p_position];
            }
            if (is_int($quote = strpos($query, "'", $position)) && $quote < $p_position) {
                if (!is_int($end_quote = strpos($query, "'", $quote + 1))) {
                    $err =& $this->raiseError(MDB2_ERROR_SYNTAX, null, null,
                        'prepare: query with an unterminated text string specified');
                    return $err;
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
            } elseif ($query[$position] == $placeholder_type_guess) {
                if (is_null($placeholder_type)) {
                    $placeholder_type = $query[$p_position];
                    $question = $colon = $placeholder_type;
                    if (is_array($types) && !empty($types)) {
                        if ($placeholder_type == ':') {
                        } else {
                            $types = array_values($types);
                        }
                    }
                }
                if ($placeholder_type_guess == '?') {
                    $length = 1;
                    $name = $parameter;
                } else {
                    $name = preg_replace('/^.{'.($position+1).'}([a-z0-9_]+).*$/si', '\\1', $query);
                    if ($name === '') {
                        $err =& $this->raiseError(MDB2_ERROR_SYNTAX, null, null,
                            'prepare: named parameter with an empty name');
                        return $err;
                    }
                    $length = strlen($name) + 1;
                }
                if (array_key_exists($name, $types)) {
                    $pgtypes[] = $this->datatype->mapPrepareDatatype($types[$name]);
                } elseif (array_key_exists($parameter, $types)) {
                    $pgtypes[] = $this->datatype->mapPrepareDatatype($types[$parameter]);
                } else {
                    $pgtypes[] = 'text';
                }
                $positions[$name] = $p_position;
                $query = substr_replace($query, '$'.++$parameter, $position, $length);
                $position = $p_position + strlen($parameter);
            } else {
                $position = $p_position;
            }
        }
        $connection = $this->getConnection();
        if (PEAR::isError($connection)) {
            return $connection;
        }

        $types_string = '';
        if ($pgtypes) {
            $types_string = ' ('.implode(', ', $pgtypes).') ';
        }
        $statement_name = 'MDB2_Statement_'.$this->phptype.md5(time());
        $query = 'PREPARE '.$statement_name.$types_string.' AS '.$query;
        $statement = $this->_doQuery($query, $is_manip, $connection);
        if (PEAR::isError($statement)) {
            return $statement;
        }

        $class_name = 'MDB2_Statement_'.$this->phptype;
        $obj =& new $class_name($this, $statement_name, $positions, $query, $types, $result_types, $is_manip, $limit, $offset);
        return $obj;
    }

    // }}}
    // {{{ nextID()

    /**
     * returns the next free id of a sequence
     *
     * @param string $seq_name name of the sequence
     * @param boolean $ondemand when true the seqence is
     *                          automatic created, if it
     *                          not exists
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function nextID($seq_name, $ondemand = true)
    {
        $sequence_name = $this->quoteIdentifier($this->getSequenceName($seq_name), true);
        $query = "SELECT NEXTVAL('$sequence_name')";
        $this->expectError(MDB2_ERROR_NOSUCHTABLE);
        $result = $this->queryOne($query, 'integer');
        $this->popExpect();
        if (PEAR::isError($result)) {
            if ($ondemand && $result->getCode() == MDB2_ERROR_NOSUCHTABLE) {
                $this->loadModule('Manager', null, true);
                $result = $this->manager->createSequence($seq_name, 1);
                if (PEAR::isError($result)) {
                    return $this->raiseError(MDB2_ERROR, null, null,
                        'nextID: on demand sequence could not be created');
                }
                return $this->nextId($seq_name, false);
            }
        }
        return $result;
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
        $sequence_name = $this->quoteIdentifier($this->getSequenceName($seq_name), true);
        return $this->queryOne("SELECT last_value FROM $sequence_name", 'integer');
    }
}

class MDB2_Result_pgsql extends MDB2_Result_Common
{
    // }}}
    // {{{ fetchRow()

    /**
     * Fetch a row and insert the data into an existing array.
     *
     * @param int       $fetchmode  how the array data should be indexed
     * @param int    $rownum    number of the row where the data can be found
     * @return int data array on success, a MDB2 error on failure
     * @access public
     */
    function &fetchRow($fetchmode = MDB2_FETCHMODE_DEFAULT, $rownum = null)
    {
        if (!is_null($rownum)) {
            $seek = $this->seek($rownum);
            if (PEAR::isError($seek)) {
                return $seek;
            }
        }
        if ($fetchmode == MDB2_FETCHMODE_DEFAULT) {
            $fetchmode = $this->db->fetchmode;
        }
        if ($fetchmode & MDB2_FETCHMODE_ASSOC) {
            $row = @pg_fetch_array($this->result, null, PGSQL_ASSOC);
            if (is_array($row)
                && $this->db->options['portability'] & MDB2_PORTABILITY_FIX_CASE
            ) {
                $row = array_change_key_case($row, $this->db->options['field_case']);
            }
        } else {
            $row = @pg_fetch_row($this->result);
        }
        if (!$row) {
            if (is_null($this->result)) {
                $err =& $this->db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'fetchRow: resultset has already been freed');
                return $err;
            }
            $null = null;
            return $null;
        }
        if ($this->db->options['portability'] & MDB2_PORTABILITY_EMPTY_TO_NULL) {
            $this->db->_fixResultArrayValues($row, MDB2_PORTABILITY_EMPTY_TO_NULL);
        }
        if (!empty($this->values)) {
            $this->_assignBindColumns($row);
        }
        if (!empty($this->types)) {
            $row = $this->db->datatype->convertResultRow($this->types, $row);
        }
        if ($fetchmode === MDB2_FETCHMODE_OBJECT) {
            $object_class = $this->db->options['fetch_class'];
            if ($object_class == 'stdClass') {
                $row = (object) $row;
            } else {
                $row = &new $object_class($row);
            }
        }
        ++$this->rownum;
        return $row;
    }

    // }}}
    // {{{ _getColumnNames()

    /**
     * Retrieve the names of columns returned by the DBMS in a query result.
     *
     * @return mixed                an associative array variable
     *                              that will hold the names of columns. The
     *                              indexes of the array are the column names
     *                              mapped to lower case and the values are the
     *                              respective numbers of the columns starting
     *                              from 0. Some DBMS may not return any
     *                              columns when the result set does not
     *                              contain any rows.
     *
     *                              a MDB2 error on failure
     * @access private
     */
    function _getColumnNames()
    {
        $columns = array();
        $numcols = $this->numCols();
        if (PEAR::isError($numcols)) {
            return $numcols;
        }
        for ($column = 0; $column < $numcols; $column++) {
            $column_name = @pg_field_name($this->result, $column);
            $columns[$column_name] = $column;
        }
        if ($this->db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $columns = array_change_key_case($columns, $this->db->options['field_case']);
        }
        return $columns;
    }

    // }}}
    // {{{ numCols()

    /**
     * Count the number of columns returned by the DBMS in a query result.
     *
     * @access public
     * @return mixed integer value with the number of columns, a MDB2 error
     *                       on failure
     */
    function numCols()
    {
        $cols = @pg_num_fields($this->result);
        if (is_null($cols)) {
            if (is_null($this->result)) {
                return $this->db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'numCols: resultset has already been freed');
            }
            return $this->db->raiseError();
        }
        return $cols;
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
        $free = @pg_free_result($this->result);
        if (!$free) {
            if (is_null($this->result)) {
                return MDB2_OK;
            }
            return $this->db->raiseError();
        }
        $this->result = null;
        return MDB2_OK;
    }
}

class MDB2_BufferedResult_pgsql extends MDB2_Result_pgsql
{
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
        if ($this->rownum != ($rownum - 1) && !@pg_result_seek($this->result, $rownum)) {
            if (is_null($this->result)) {
                return $this->db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'seek: resultset has already been freed');
            }
            return $this->db->raiseError(MDB2_ERROR_INVALID, null, null,
                'seek: tried to seek to an invalid row number ('.$rownum.')');
        }
        $this->rownum = $rownum - 1;
        return MDB2_OK;
    }

    // }}}
    // {{{ valid()

    /**
     * check if the end of the result set has been reached
     *
     * @return mixed true or false on sucess, a MDB2 error on failure
     * @access public
     */
    function valid()
    {
        $numrows = $this->numRows();
        if (PEAR::isError($numrows)) {
            return $numrows;
        }
        return $this->rownum < ($numrows - 1);
    }

    // }}}
    // {{{ numRows()

    /**
     * returns the number of rows in a result object
     *
     * @return mixed MDB2 Error Object or the number of rows
     * @access public
     */
    function numRows()
    {
        $rows = @pg_num_rows($this->result);
        if (is_null($rows)) {
            if (is_null($this->result)) {
                return $this->db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'numRows: resultset has already been freed');
            }
            return $this->db->raiseError();
        }
        return $rows;
    }
}

class MDB2_Statement_pgsql extends MDB2_Statement_Common
{
    // {{{ _execute()

    /**
     * Execute a prepared query statement helper method.
     *
     * @param mixed $result_class string which specifies which result class to use
     * @param mixed $result_wrap_class string which specifies which class to wrap results in
     * @return mixed a result handle or MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    function &_execute($result_class = true, $result_wrap_class = false)
    {
        $this->db->last_query = $this->query;
        $this->db->debug($this->query, 'execute');
        if ($this->db->getOption('disable_query')) {
            if ($this->is_manip) {
                $return = 0;
                return $return;
            }
            $null = null;
            return $null;
        }

        $connection = $this->db->getConnection();
        if (PEAR::isError($connection)) {
            return $connection;
        }

        $query = 'EXECUTE '.$this->statement;
        if (!empty($this->positions)) {
            $parameters = array();
            foreach ($this->positions as $parameter => $current_position) {
                if (!array_key_exists($parameter, $this->values)) {
                    return $this->db->raiseError();
                }
                $value = $this->values[$parameter];
                $type = array_key_exists($parameter, $this->types) ? $this->types[$parameter] : null;
                if (is_resource($value) || $type == 'clob' || $type == 'blob') {
                    if (!is_resource($value) && preg_match('/^(\w+:\/\/)(.*)$/', $value, $match)) {
                        if ($match[1] == 'file://') {
                            $value = $match[2];
                        }
                        $value = @fopen($value, 'r');
                        $close = true;
                    }
                    if (is_resource($value)) {
                        $data = '';
                        while (!@feof($value)) {
                            $data.= @fread($value, $this->db->options['lob_buffer_length']);
                        }
                        if ($close) {
                            @fclose($value);
                        }
                        $value = $data;
                    }
                }
                $parameters[] = $this->db->quote($value, $type);
            }
            $query.= ' ('.implode(', ', $parameters).')';
        }

        $result = $this->db->_doQuery($query, $this->is_manip, $connection);
        if (PEAR::isError($result)) {
            return $result;
        }

        if ($this->is_manip) {
            $affected_rows = $this->db->_affectedRows($connection, $result);
            return $affected_rows;
        }

        $result =& $this->db->_wrapResult($result, $this->result_types, $result_class, $result_wrap_class);
        return $result;
    }

    // }}}

    // }}}

    // }}}
    // {{{ free()

    /**
     * Release resources allocated for the specified prepared query.
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function free()
    {
        $connection = $this->db->getConnection();
        if (PEAR::isError($connection)) {
            return $connection;
        }

        $query = 'DEALLOCATE PREPARE '.$this->statement;
        return $this->db->_doQuery($query, true, $connection);
    }
}
?>