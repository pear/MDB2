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
    function MDB2_Driver_pgsql()
    {
        $this->MDB2_Driver_Common();
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
        $this->supported['replace'] = true;
        $this->supported['sub_selects'] = true;
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
        if (is_resource($error)) {
            $native_msg = @pg_result_error($error);
        } else {
            $native_msg = @pg_errormessage($this->connection);
        }

        // Fall back to MDB2_ERROR if there was no mapping.
        $error = MDB2_ERROR;

        static $error_regexps;
        if (empty($error_regexps)) {
            $error_regexps = array(
                '/([Tt]able does not exist\.|[Rr]elation [\"\'].*[\"\'] does not exist|[Ss]equence does not exist|[Cc]lass ".+" not found)$/' => MDB2_ERROR_NOSUCHTABLE,
                '/[Tt]able [\"\'].*[\"\'] does not exist/' => MDB2_ERROR_NOSUCHTABLE,
                '/[Rr]elation [\"\'].*[\"\'] already exists|[Cc]annot insert a duplicate key into (a )?unique index.*/' => MDB2_ERROR_ALREADY_EXISTS,
                '/divide by zero$/'                     => MDB2_ERROR_DIVZERO,
                '/pg_atoi: error in .*: can\'t parse /' => MDB2_ERROR_INVALID_NUMBER,
                '/ttribute [\"\'].*[\"\'] not found$|[Rr]elation [\"\'].*[\"\'] does not have attribute [\"\'].*[\"\']/' => MDB2_ERROR_NOSUCHFIELD,
                '/parser: parse error at or near \"/'   => MDB2_ERROR_SYNTAX,
                '/syntax error at/'                     => MDB2_ERROR_SYNTAX,
                '/violates not-null constraint/'        => MDB2_ERROR_CONSTRAINT_NOT_NULL,
                '/violates [\w ]+ constraint/'          => MDB2_ERROR_CONSTRAINT,
                '/referential integrity violation/'     => MDB2_ERROR_CONSTRAINT,
                '/deadlock detected/'                   => MDB2_ERROR_DEADLOCK
            );
        }
        foreach ($error_regexps as $regexp => $code) {
            if (preg_match($regexp, $native_msg)) {
                $error = $code;
                break;
            }
        }

        return array($error, null, $native_msg);
    }

    // }}}
    // {{{ autoCommit()

    /**
     * Define whether database changes done on the database be automatically
     * committed. This function may also implicitly start or end a transaction.
     *
     * @param boolean $auto_commit flag that indicates whether the database
     *     changes should be committed right after executing every query
     *     statement. If this argument is 0 a transaction implicitly started.
     *     Otherwise, if a transaction is in progress it is ended by committing
     *     any database changes that were pending.
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function autoCommit($auto_commit)
    {
        $this->debug(($auto_commit ? 'On' : 'Off'), 'autoCommit');
        if ($this->auto_commit == $auto_commit) {
            return MDB2_OK;
        }
        if ($this->connection) {
            $result = $this->_doQuery($auto_commit ? 'END' : 'BEGIN');
            if (MDB2::isError($result)) {
                return $result;
            }
        }
        $this->auto_commit = $auto_commit;
        $this->in_transaction = !$auto_commit;
        return MDB2_OK;
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
        $this->debug('commit transaction', 'commit');
        if ($this->auto_commit) {
            return $this->raiseError(MDB2_ERROR, null, null,
                'commit: transaction changes are being auto commited');
        }
        $result = $this->_doQuery('COMMIT');
        if (MDB2::isError($result)) {
            return $result;
        }
        return $this->_doQuery('BEGIN');
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
        if ($this->auto_commit) {
            return $this->raiseError(MDB2_ERROR, null, null,
                'rollback: transactions can not be rolled back when changes are auto commited');
        }
        $result = $this->_doQuery('ROLLBACK');
        if (MDB2::isError($result)) {
            return $result;
        }
        return $this->_doQuery('BEGIN');
    }

    // }}}
    // {{{ _doConnect()

    /**
     * Does the grunt work of connecting to the database
     *
     * @return mixed connection resource on success, MDB2 Error Object on failure
     * @access private
     **/
    function _doConnect($database_name, $persistent)
    {
        if ($database_name == '') {
            $database_name = 'template1';
        }
        $dsninfo = $this->dsn;
        $protocol = (isset($dsninfo['protocol'])) ? $dsninfo['protocol'] : 'tcp';
        $connstr = '';

        if ($protocol == 'tcp') {
            if (!empty($dsninfo['hostspec'])) {
                $connstr = 'host=' . $dsninfo['hostspec'];
            }
            if (!empty($dsninfo['port'])) {
                $connstr .= ' port=' . $dsninfo['port'];
            }
        }

        if (isset($database_name)) {
            $connstr .= ' dbname=\'' . addslashes($database_name) . '\'';
        }
        if (!empty($dsninfo['username'])) {
            $connstr .= ' user=\'' . addslashes($dsninfo['username']) . '\'';
        }
        if (!empty($dsninfo['password'])) {
            $connstr .= ' password=\'' . addslashes($dsninfo['password']) . '\'';
        }
        if (!empty($dsninfo['options'])) {
            $connstr .= ' options=' . $dsninfo['options'];
        }
        if (!empty($dsninfo['tty'])) {
            $connstr .= ' tty=' . $dsninfo['tty'];
        }
        putenv('PGDATESTYLE=ISO');

        $function = ($persistent ? 'pg_pconnect' : 'pg_connect');
        // catch error
        ob_start();
        $connection = @$function($connstr);
        $error_msg = ob_get_contents();
        ob_end_clean();

        if ($connection > 0) {
            return $connection;
        }
        if (!$error_msg) {
            $error_msg = 'Could not connect to PostgreSQL server';
        }
        return $this->raiseError(MDB2_ERROR_CONNECT_FAILED, null, null,
            $error_msg);
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
        if ($this->connection != 0) {
            if (count(array_diff($this->connected_dsn, $this->dsn)) == 0
                && !strcmp($this->connected_database_name, $this->database_name)
                && ($this->opened_persistent == $this->options['persistent']))
            {
                return MDB2_OK;
            }
            @pg_close($this->connection);
            $this->affected_rows = -1;
            $this->connection = 0;
        }

        if (!PEAR::loadExtension($this->phptype)) {
            return $this->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'connect: extension '.$this->phptype.' is not compiled into PHP');
        }

        if (function_exists('pg_cmdtuples')) {
            $connection = $this->_doConnect('template1', 0);
            if (!MDB2::isError($connection)) {
                if (($result = @pg_exec($connection, 'BEGIN'))) {
                    $error_reporting = error_reporting(63);
                    @pg_cmdtuples($result);
                    if (!isset($php_errormsg)
                        || strcmp($php_errormsg, 'This compilation does not support pg_cmdtuples()')
                    ) {
                        $this->supported['affected_rows'] = true;
                    }
                    error_reporting($error_reporting);
                } else {
                    $err = $this->raiseError($result);
                }
                @pg_close($connection);
            } else {
                $err = $this->raiseError(MDB2_ERROR, null, null,
                    'connect: could not execute BEGIN');
            }
            if (isset($err) && MDB2::isError($err)) {
                return $err;
            }
        }
        $connection = $this->_doConnect($this->database_name, $this->options['persistent']);
        if (MDB2::isError($connection)) {
            return $connection;
        }
        $this->connection = $connection;
        $this->connected_dsn = $this->dsn;
        $this->connected_database_name = $this->database_name;
        $this->opened_persistent = $this->options['persistent'];

        if (!$this->auto_commit
            && MDB2::isError($trans_result = $this->_doQuery('BEGIN'))
        ) {
            @pg_close($this->connection);
            $this->connection = 0;
            $this->affected_rows = -1;
            return $trans_result;
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ _close()
    /**
     * Close the database connection
     *
     * @return boolean
     * @access private
     **/
    function _close()
    {
        if ($this->connection != 0) {
            if (!$this->auto_commit) {
                $result = $this->_doQuery('END');
            }
            @pg_close($this->connection);
            $this->connection = 0;
            $this->affected_rows = -1;
            unset($GLOBALS['_MDB2_databases'][$this->db_index]);

            if (isset($result) && MDB2::isError($result)) {
                return $result;
            }
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ _doQuery()

    /**
     * Execute a query
     * @param string $query the SQL query
     * @return mixed result identifier if query executed, else MDB2_error
     * @access private
     **/
    function _doQuery($query)
    {
        $result = @pg_exec($this->connection, $query);
        if ($result) {
            $this->affected_rows = (isset($this->supported['affected_rows']) ? @pg_cmdtuples($result) : -1);
        } else {
            return $this->raiseError($result);
        }
        return $result;
    }

    // }}}
    // {{{ standaloneQuery()

   /**
     * execute a query as DBA
     *
     * @param string $query the SQL query
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function standaloneQuery($query)
    {
        if (($connection = $this->_doConnect('template1', 0)) == 0) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED, null, null,
                'Cannot connect to template1');
        }
        if (!($result = @pg_exec($connection, $query))) {
            $this->raiseError($result);
        }
        @pg_close($connection);
        return $result;
    }

    // }}}
    // {{{ query()

    /**
     * Send a query to the database and return any results
     *
     * @param string $query the SQL query
     * @param array $types array that contains the types of the columns in
     *                         the result set
     *
     * @return mixed a result handle or MDB2_OK on success, a MDB2 error on failure
     *
     * @access public
     */
    function &query($query, $types = null)
    {
        $ismanip = MDB2::isManip($query);
        $offset = $this->row_offset;
        $limit = $this->row_limit;
        $this->row_offset = $this->row_limit = 0;
        $this->last_query = $query;
        $this->debug($query, 'query');

        $connected = $this->connect();
        if (MDB2::isError($connected)) {
            return $connected;
        }

        if (!$ismanip && $limit > 0) {
            if ($this->auto_commit && MDB2::isError($result = $this->_doQuery('BEGIN'))) {
                return $result;
            }
            $result = $this->_doQuery('DECLARE select_cursor SCROLL CURSOR FOR '.$query);
            if (!MDB2::isError($result)) {
                if ($offset > 0
                    && MDB2::isError($result = $this->_doQuery("MOVE FORWARD $offset FROM select_cursor"))
                ) {
                    @pg_free_result($result);
                    return $result;
                }
                $result = $this->_doQuery("FETCH FORWARD $limit FROM select_cursor");
                if (MDB2::isError($result)) {
                    @pg_free_result($result);
                    return $result;
                }
            } else {
                return $result;
            }
            if ($this->auto_commit && MDB2::isError($result2 = $this->_doQuery('END'))) {
                @pg_free_result($result);
                return $result2;
            }
        } else {
            $result = $this->_doQuery($query);
            if (MDB2::isError($result)) {
                return $result;
            }
        }
        if (!MDB2::isError($result)) {
            if ($ismanip) {
                $this->affected_rows = @pg_cmdtuples($result);
                return MDB2_OK;
            } elseif ((preg_match('/^\s*\(?\s*SELECT\s+/si', $query)
                    && !preg_match('/^\s*\(?\s*SELECT\s+INTO\s/si', $query))
                || preg_match('/^\s*EXPLAIN/si',$query)
            ) {
                if (!$result_class) {
                    $result_class = $this->options['result_buffering']
                        ? $this->options['buffered_result_class'] : $this->options['result_class'];
                }
                $class_name = sprintf($result_class, $this->phptype);
                $result =& new $class_name($this, $result);
                if ($types) {
                    $err = $result->setResultTypes($types);
                    if (MDB2::isError($err)) {
                        $result->free();
                        return $err;
                    }
                }
                return $result;
            } else {
                $this->affected_rows = 0;
                return MDB2_OK;
            }
        }
        return $result;
    }

    // }}}
    // {{{ nextID()

    /**
     * returns the next free id of a sequence
     *
     * @param string  $seq_name name of the sequence
     * @param boolean $ondemand when true the seqence is
     *                          automatic created, if it
     *                          not exists
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function nextID($seq_name, $ondemand = true)
    {
        $sequence_name = $this->getSequenceName($seq_name);
        $this->expectError(MDB2_ERROR_NOSUCHTABLE);
        $result = $this->queryOne("SELECT NEXTVAL('$sequence_name')", 'integer');
        $this->popExpect();
        if (MDB2::isError($result)) {
            if ($ondemand && $result->getCode() == MDB2_ERROR_NOSUCHTABLE) {
                $this->loadModule('manager');
                $result = $this->manager->createSequence($seq_name, 1);
                if (MDB2::isError($result)) {
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
     * @param string  $seq_name name of the sequence
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function currID($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        return $this->queryOne("SELECT last_value FROM $seqname", 'integer');
    }
}

class MDB2_Result_pgsql extends MDB2_Result_Common
{
    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function MDB2_Result_pgsql(&$mdb, &$result)
    {
        parent::MDB2_Result_Common($mdb, $result);
    }

    // }}}
    // {{{ fetch()

    /**
    * fetch value from a result set
    *
    * @param int    $rownum    number of the row where the data can be found
    * @param int    $field    field number where the data can be found
    * @return mixed string on success, a MDB2 error on failure
    * @access public
    */
    function fetch($rownum = 0, $field = 0)
    {
        $value = @pg_result($this->result, $rownum, $field);
        if (!$value) {
            if (is_null($this->result)) {
                return $this->mdb->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'fetch: resultset has already been freed');
            }
        } elseif (isset($this->types[$field])) {
            $value = $this->mdb->datatype->convertResult($value, $this->types[$field]);
        }
        return $value;
    }

    // }}}
    // {{{ fetchRow()

    /**
     * Fetch a row and insert the data into an existing array.
     *
     * @param int       $fetchmode  how the array data should be indexed
     * @return int data array on success, a MDB2 error on failure
     * @access public
     */
    function fetchRow($fetchmode = MDB2_FETCHMODE_DEFAULT)
    {
        if ($fetchmode == MDB2_FETCHMODE_DEFAULT) {
            $fetchmode = $this->mdb->fetchmode;
        }
        if ($fetchmode & MDB2_FETCHMODE_ASSOC) {
            $row = @pg_fetch_array($this->result, null, PGSQL_ASSOC);
            if (is_array($row) && $this->options['optimize'] == 'portability') {
                $row = array_change_key_case($row, CASE_LOWER);
            }
        } else {
            $row = @pg_fetch_row($this->result);
        }
        if (!$row) {
            if (is_null($this->result)) {
                return $this->mdb->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'fetchRow: resultset has already been freed');
            }
            return null;
        }
        if (isset($this->types)) {
            $row = $this->mdb->datatype->convertResultRow($this->types, $row);
        }
        ++$this->rownum;
        return $row;
    }

    // }}}
    // {{{ getColumnNames()

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
     * @access public
     */
    function getColumnNames()
    {
        $columns = array();
        $numcols = $this->numCols();
        if (MDB2::isError($numcols)) {
            return $numcols;
        }
        for ($column = 0; $column < $numcols; $column++) {
            $column_name = @pg_field_name($this->result, $column);
            if ($this->mdb->options['optimize'] == 'portability') {
                $column_name = strtolower($column_name);
            }
            $columns[$column_name] = $column;
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
                return $this->mdb->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'numCols: resultset has already been freed');
            }
            return $this->mdb->raiseError();
        }
        return $cols;
    }

    // }}}
    // {{{ resultIsNull()

    /**
     * Determine whether the value of a query result located in given row and
     *    field is a null.
     *
     * @param int $rownum number of the row where the data can be found
     * @param int $field field number where the data can be found
     * @return mixed true or false on success, a MDB2 error on failure
     * @access public
     */
    function resultIsNull($rownum, $field)
    {
        $value = pg_field_is_null($this->result, $rownum, $field);
        if (!$value) {
            if (is_null($this->result)) {
                return $this->mdb->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'fetch: resultset has already been freed');
            }
        }
        return (bool)$value;
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
            return $this->mdb->raiseError();
        }
        $this->result = null;
        return MDB2_OK;
    }
}

class MDB2_BufferedResult_pgsql extends MDB2_Result_pgsql
{
    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function MDB2_BufferedResult_pgsql(&$mdb, &$result)
    {
        parent::MDB2_Result_pgsql($mdb, $result);
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
        if (!@pg_result_seek($this->result, $rownum)) {
            if (is_null($this->result)) {
                return $this->mdb->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'seek: resultset has already been freed');
            }
            return $this->mdb->raiseError(MDB2_ERROR_INVALID, null, null,
                'seek: tried to seek to an invalid row number ('.$rownum.')');
        }
        $this->rownum = $rownum - 1;
        return MDB2_OK;
    }

    // }}}
    // {{{ hasMore()

    /**
    * check if the end of the result set has been reached
    *
    * @return mixed true or false on sucess, a MDB2 error on failure
    * @access public
    */
    function hasMore()
    {
        $numrows = $this->numRows();
        if (MDB2::isError($numrows)) {
            return $numrows;
        }
        return $this->rownum < $numrows - 1;
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
                return $this->mdb->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'numRows: resultset has already been freed');
            }
            return $this->raiseError();
        }
        return $rows;
    }
}

?>