<?php
// vim: set et ts=4 sw=4 fdm=marker:
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2004 Manuel Lemos, Tomas V.V.Cox,                 |
// | Stig. S. Bakken, Lukas Smith, Frank M. Kromann                       |
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
// | Author: Frank M. Kromann <frank@kromann.info>                        |
// +----------------------------------------------------------------------+
//
// $Id$
//

/**
 * MDB2 MSSQL Server driver
 *
 * @package MDB2
 * @category Database
 * @author  Frank M. Kromann <frank@kromann.info>
 */
class MDB2_Driver_mssql extends MDB2_Driver_Common
{
    // {{{ properties
    var $escape_quotes = "'";

    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct();

        $this->phptype = 'mssql';
        $this->dbsyntax = 'mssql';

        $this->supported['sequences'] = true;
        $this->supported['indexes'] = true;
        $this->supported['affected_rows'] = true;
        $this->supported['summary_functions'] = true;
        $this->supported['order_by_text'] = true;
        $this->supported['current_id'] = false;
        $this->supported['limit_queries'] = true;
        $this->supported['LOBs'] = true;
        $this->supported['replace'] = true;
        $this->supported['sub_selects'] = true;
        $this->supported['transactions'] = true;
        $this->supported['auto_increment'] = true;

        $db->options['database_device'] = false;
        $db->options['database_size'] = false;
    }

    function MDB2_Driver_mssql()
    {
        $this->__construct();
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
        $native_code = null;
        if ($this->connection) {
            $result = @mssql_query('select @@ERROR as ErrorCode', $this->connection);
            if ($result) {
                $native_code = @mssql_result($result);
                @mssql_free_result($result);
            }
        }
        $native_msg = @mssql_get_last_message();
        if (is_null($error)) {
            static $ecode_map;
            if (empty($ecode_map)) {
                $ecode_map = array(
                    207   => MDB2_ERROR_NOSUCHFIELD,
                    208   => MDB2_ERROR_NOSUCHTABLE,
                    245   => MDB2_ERROR_INVALID_NUMBER,
                    515   => MDB2_ERROR_CONSTRAINT_NOT_NULL,
                    547   => MDB2_ERROR_CONSTRAINT,
                    1205  => MDB2_ERROR_DEADLOCK,
                    2627  => MDB2_ERROR_CONSTRAINT,
                    2714  => MDB2_ERROR_ALREADY_EXISTS,
                    3701  => MDB2_ERROR_NOSUCHTABLE,
                    8134  => MDB2_ERROR_DIVZERO,
                );
             }
            if (isset($ecode_map[$native_code])) {
                $error = $ecode_map[$native_code];
            }
        }
        return array($error, $native_code, $native_msg);
    }

    // }}}
    // {{{ quoteIdentifier()

    /**
     * Quote a string so it can be safely used as a table / column name
     *
     * Quoting style depends on which database driver is being used.
     *
     * @param string $str  identifier name to be quoted
     *
     * @return string  quoted identifier string
     *
     * @since 1.6.0
     * @access public
     */
    function quoteIdentifier($str)
    {
        return '[' . str_replace(']', ']]', $str) . ']';
    }

    // }}}
    // {{{ autoCommit()

    /**
     * Define whether database changes done on the database be automatically
     * committed. This function may also implicitly start or end a transaction.
     *
     * @param boolean $auto_commit    flag that indicates whether the database
     *                                changes should be committed right after
     *                                executing every query statement. If this
     *                                argument is 0 a transaction implicitly
     *                                started. Otherwise, if a transaction is
     *                                in progress it is ended by committing any
     *                                database changes that were pending.
     *
     * @access public
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     */
    function autoCommit($auto_commit)
    {
        $this->debug(($auto_commit ? 'On' : 'Off'), 'autoCommit');
        if ($this->auto_commit == $auto_commit) {
            return MDB2_OK;
        }
        if ($this->connection) {
            if ($auto_commit) {
                $result = $this->query('COMMIT TRANSACTION');
            } else {
                $result = $this->query('BEGIN TRANSACTION');
            }
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
     * @access public
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     */
    function commit()
    {
        $this->debug('commit transaction', 'commit');
        if ($this->auto_commit) {
            return $this->raiseError(MDB2_ERROR, null, null,
            'commit: transaction changes are being auto commited');
        }
        $result = $this->query('COMMIT TRANSACTION');
        if (MDB2::isError($result)) {
            return $result;
        }
        return $this->query('BEGIN TRANSACTION');
    }

    // }}}
    // {{{ rollback()

    /**
     * Cancel any database changes done during a transaction that is in
     * progress. This function may only be called when auto-committing is
     * disabled, otherwise it will fail. Therefore, a new transaction is
     * implicitly started after canceling the pending changes.
     *
     * @access public
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     */
    function rollback()
    {
        $this->debug('rolling back transaction', 'rollback');
        if ($this->auto_commit) {
            return $this->raiseError(MDB2_ERROR, null, null,
                'rollback: transactions can not be rolled back when changes are auto commited');
        }
        $result = $this->query('ROLLBACK TRANSACTION');
        if (MDB2::isError($result)) {
            return $result;
        }
        return $this->query('BEGIN TRANSACTION');
    }

    function _doQuery($query)
    {
        $this->current_row = $this->affected_rows = -1;
        return @mssql_query($query, $this->connection);
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to the database
     *
     * @return true on success, MDB2 Error Object on failure
     **/
    function connect()
    {
        if ($this->connection != 0) {
            if (count(array_diff($this->connected_dsn, $this->dsn)) == 0
                && $this->opened_persistent == $this->options['persistent'])
            {
                return MDB2_OK;
            }
            @mssql_close($this->connection);
            $this->connection = 0;
            $this->affected_rows = -1;
        }

        if (!PEAR::loadExtension($this->phptype)) {
            return $this->raiseError(null, MDB2_ERROR_NOT_FOUND, null, null,
                'connect: extension '.$this->phptype.' is not compiled into PHP');
        }

        $function = ($this->options['persistent'] ? 'mssql_pconnect' : 'mssql_connect');

        $dsninfo = $this->dsn;
        $user = $dsninfo['username'];
        $pw = $dsninfo['password'];
        $dbhost = $dsninfo['hostspec'] ? $dsninfo['hostspec'] : 'localhost';
        $port   = $dsninfo['port'] ? ':' . $dsninfo['port'] : '';
        $dbhost .= $port;

        if ($dbhost && $user && $pw) {
            $connection = @$function($dbhost, $user, $pw);
        } elseif ($dbhost && $user) {
            $connection = @$function($dbhost, $user);
        } elseif ($dbhost) {
            $connection = @$function($dbhost);
        } else {
            $connection = 0;
        }
        if ($connection <= 0) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED);
        }
        $this->connection = $connection;
        $this->connected_dsn = $this->dsn;
        $this->connected_database_name = '';
        $this->opened_persistent = $this->options['persistent'];

        if (isset($this->supported['transactions'])
            && !$this->auto_commit
            && MDB2::isError($this->_doQuery('BEGIN TRANSACTION'))
        ) {
            @mssql_close($this->connection);
            $this->connection = 0;
            $this->affected_rows = -1;
            return $this->raiseError('connect: Could not begin the initial transaction');
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ _close()
    /**
     * all the RDBMS specific things needed close a DB connection
     *
     * @return boolean
     * @access private
     **/
    function _close()
    {
        if ($this->connection != 0) {
            if (isset($this->supported['transactions']) && !$this->auto_commit) {
                $result = $this->_doQuery('ROLLBACK TRANSACTION');
            }
            @mssql_close($this->connection);
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
        if (!PEAR::loadExtension($this->phptype)) {
            return $this->raiseError(null, MDB2_ERROR_NOT_FOUND, null, null,
                'standaloneQuery: extension '.$this->phptype.' is not compiled into PHP');
        }
        $connection = @mssql_connect($this->dsn['hostspec'],$this->dsn['username'],$this->dsn['password']);
        if ($connection == 0) {
            return $this->raiseError('standaloneQuery: Could not connect to the Microsoft SQL server');
        }
        $result = @mssql_query($query, $connection);
        if (!$result) {
            return $this->raiseError('standaloneQuery: Could not query a Microsoft SQL server');
        }
        @mssql_close($connection);
        return MDB2_OK;
    }

    // }}}
    // {{{ query()

    /**
     * Send a query to the database and return any results
     *
     * @param string  $query  the SQL query
     * @param mixed   $types  array that contains the types of the columns in
     *                        the result set
     * @param mixed $result_class string which specifies which result class to use
     * @param mixed $result_wrap_class string which specifies which class to wrap results in
     * @return mixed a result handle or MDB2_OK on success, a MDB2 error on failure
     *
     * @access public
     */
    function &query($query, $types = null, $result_class = false, $result_wrap_class = false)
    {
        $ismanip = MDB2::isManip($query);
        $offset = $this->row_offset;
        $limit = $this->row_limit;
        $this->row_offset = $this->row_limit = 0;
        if ($limit > 0) {
            $fetch = $offset + $limit;
            if (!$ismanip) {
                $query = str_replace('SELECT', "SELECT TOP $fetch", $query);
            }
        }
        $this->last_query = $query;
        $this->debug($query, 'query');
        if ($this->options['disable_query']) {
            if ($ismanip) {
                return MDB2_OK;
            }
            return NULL;
        }

        $connected = $this->connect();
        if (MDB2::isError($connected)) {
            return $connected;
        }

        if ($this->database_name) {
            if (!@mssql_select_db($this->database_name, $this->connection)) {
                $error =& $this->raiseError();
                return $error;
            }
            $this->connected_database_name = $this->database_name;
        }
        if ($result = $this->_doQuery($query)) {
            if ($ismanip) {
                return MDB2_OK;
            } else {
                if (!$result_class) {
                    $result_class = $this->options['result_buffering']
                        ? $this->options['buffered_result_class'] : $this->options['result_class'];
                }
                $class_name = sprintf($result_class, $this->phptype);
                $result =& new $class_name($this, $result, $offset, $limit);
                if ($types) {
                    $err = $result->setResultTypes($types);
                    if (MDB2::isError($err)) {
                        $result->free();
                        return $err;
                    }
                }
                if (!$result_wrap_class) {
                    $result_wrap_class = $this->options['result_wrap_class'];
                }
                if ($result_wrap_class) {
                    $result =& new $result_wrap_class($result);
                }
                return $result;
            }
        }
        $error =& $this->raiseError();
        return $error;
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
        if (MDB2::isManip($this->last_query)) {
            $affected_rows = @mssql_affected_rows($this->connection);
        } else {
            $affected_rows = 0;
        }
        if ($affected_rows === false) {
            return $this->raiseError(MDB2_ERROR_NEED_MORE_DATA);
        }
        return $affected_rows;
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
     *
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function nextID($seq_name, $ondemand = true)
    {
        $sequence_name = $this->getSequenceName($seq_name);
        $this->expectError(MDB2_ERROR_NOSUCHTABLE);
        $result = $this->query("INSERT INTO $sequence_name DEFAULT VALUES");
        $this->popExpect();
        if (MDB2::isError($result)) {
            if ($ondemand && $result->getCode() == MDB2_ERROR_NOSUCHTABLE) {
                $this->loadModule('manager');
                // Since we are creating the sequence on demand
                // we know the first id = 1 so initialize the
                // sequence at 2
                $result = $this->manager->createSequence($seq_name, 2);
                if (MDB2::isError($result)) {
                    return $this->raiseError(MDB2_ERROR, null, null,
                        'nextID: on demand sequence could not be created');
                } else {
                    // First ID of a newly created sequence is 1
                    return 1;
                }
            }
            return $result;
        }
        $value = $this->queryOne("SELECT @@IDENTITY FROM $sequence_name", 'integer');
        if (is_numeric($value)
            && MDB2::isError($this->query("DELETE FROM $sequence_name WHERE ".$this->options['seqname_col_name']." < $value"))
        ) {
            $this->warnings[] = 'nextID: could not delete previous sequence table values';
        }
        return $value;
    }

    // }}}
    // {{{ getAfterID()

    /**
     * returns the autoincrement ID if supported
     *
     * @param mixed id value as returned by getBeforeId()
     * @param string $table name of the table
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function getAfterID($id, $table)
    {
        $this->loadModule('native');
        return $this->native->getInsertID();
    }
}

class MDB2_Result_mssql extends MDB2_Result_Common
{
    // {{{ _skipLimitOffset()

    /**
     * Skip the first row of a result set.
     *
     * @param resource $result
     * @return mixed a result handle or MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    function _skipLimitOffset()
    {
        if ($this->limit) {
            if ($this->rownum >= $this->limit) {
                return MDB2_ERROR;
            }
        }
        if ($this->offset) {
            while ($this->offset_count < $this->offset) {
                ++$this->offset_count;
                if (!is_array(@mysql_fetch_row($this->result))) {
                    $this->offset_count = $this->limit;
                    return MDB2_ERROR;
                }
            }
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ fetch()

    /**
    * fetch value from a result set
    *
    * @param int    $rownum    number of the row where the data can be found
    * @param int    $colnum    field number where the data can be found
    * @return mixed string on success, a MDB2 error on failure
    * @access public
    */
    function fetch($rownum = 0, $colnum = 0)
    {
        $value = @mssql_result($this->result, $rownum, $colnum);
        if (!$value) {
            if (is_null($this->result)) {
                return $this->mdb->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'fetch: resultset has already been freed');
            }
        }
        if (isset($this->types[$colnum])) {
            $value = $this->mdb->datatype->convertResult($value, $this->types[$colnum]);
        }
        if ($this->mdb->options['portability'] & MDB2_PORTABILITY_RTRIM) {
            $value = rtrim($value);
        }
        if ($value === ''
            && $this->mdb->options['portability'] & MDB2_PORTABILITY_EMPTY_TO_NULL
        ) {
            $value = null;
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
    function &fetchrow($fetchmode = MDB2_FETCHMODE_DEFAULT)
    {
        if ($fetchmode == MDB2_FETCHMODE_DEFAULT) {
            $fetchmode = $this->mdb->fetchmode;
        }
        if (!$this->_skipLimitOffset()) {
            return null;
        }
        if ($fetchmode & MDB2_FETCHMODE_ASSOC) {
            $row = @mssql_fetch_assoc($this->result);
            if (is_array($row)
                && $this->mdb->options['portability'] & MDB2_PORTABILITY_LOWERCASE
            ) {
                $row = array_change_key_case($row, CASE_LOWER);
            }
        } else {
            $row = @mssql_fetch_row($this->result);
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
        if ($this->mdb->options['portability'] & MDB2_PORTABILITY_EMPTY_TO_NULL) {
            $this->mdb->_convertEmptyArrayValuesToNull($row);
        }
        if ($fetchmode === MDB2_FETCHMODE_OBJECT) {
            $object_class = $this->mdb->options['fetch_class'];
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
    // {{{ getColumnNames()

    /**
     * Retrieve the names of columns returned by the DBMS in a query result.
     *
     * @param resource $result result identifier
     * @return mixed associative array variable
     *      that holds the names of columns. The indexes of the array are
     *      the column names mapped to lower case and the values are the
     *      respective numbers of the columns starting from 0. Some DBMS may
     *      not return any columns when the result set does not contain any
     *      rows.
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
            $column_name = @mssql_field_name($this->result, $column);
            $columns[$column_name] = $column;
        }
        if ($this->mdb->options['portability'] & MDB2_PORTABILITY_LOWERCASE) {
            $columns = array_change_key_case($columns, CASE_LOWER);
        }
        return $columns;
    }

    // }}}
    // {{{ numCols()

    /**
     * Count the number of columns returned by the DBMS in a query result.
     *
     * @return mixed integer value with the number of columns, a MDB2 error
     *      on failure
     * @access public
     */
    function numCols()
    {
        $cols = @mssql_num_fields($this->result);
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
    // {{{ nextResult()

    /**
     * Move the internal result pointer to the next available result
     * Currently not supported
     *
     * @return true if a result is available otherwise return false
     * @access public
     */
    function nextResult()
    {
        if (is_null($this->result)) {
            return $this->mdb->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                'nextResult: resultset has already been freed');
        }
        return @mssql_next_result($this->result);
    }

    // }}}
    // {{{ free()

    /**
     * Free the internal resources associated with $result.
     *
     * @return boolean true on success, false if $result is invalid
     * @access public
     */
    function free()
    {
        $free = @mssql_free_result($this->result);
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

class MDB2_BufferedResult_mssql extends MDB2_Result_mssql
{
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
        if (MDB2::isError($numrows)) {
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
        $rows = @mssql_num_rows($this->result);
        if (is_null($rows)) {
            if (is_null($this->result)) {
                return $this->mdb->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'numRows: resultset has already been freed');
            }
            return $this->raiseError();
        }
        if ($this->limit) {
            $rows -= $this->limit;
            if ($rows < 0) {
                $rows = 0;
            }
        }
        return $rows;
    }
}

?>