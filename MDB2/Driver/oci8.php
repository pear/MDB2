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

// $Id$

/**
 * MDB2 OCI8 driver
 *
 * @package MDB2
 * @category Database
 * @author Lukas Smith <smith@backendmedia.com>
 */
class MDB2_Driver_oci8 extends MDB2_Driver_Common
{
    // {{{ properties
    var $escape_quotes = "'";

    var $uncommitedqueries = 0;

    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function MDB2_Driver_oci8()
    {
        $this->MDB2_Driver_Common();
        $this->phptype = 'oci8';
        $this->dbsyntax = 'oci8';

        $this->supported['sequences'] = true;
        $this->supported['indexes'] = true;
        $this->supported['summary_functions'] = true;
        $this->supported['order_by_text'] = true;
        $this->supported['current_id'] = true;
        $this->supported['affected_rows'] = true;
        $this->supported['transactions'] = true;
        $this->supported['limit_queries'] = true;
        $this->supported['LOBs'] = true;
        $this->supported['replace'] = true;
        $this->supported['sub_selects'] = true;

        $this->options['DBA_username'] = false;
        $this->options['DBA_password'] = false;
        $this->options['database_name_prefix'] = false;
        $this->options['default_tablespace'] = false;
        $this->options['HOME'] = false;
        $this->options['default_text_field_length'] = 4000;
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
            $error_data = @OCIError($error);
            $error = null;
        } else {
            $error_data = @OCIError($this->connection);
        }
        $native_code = $error_data['code'];
        $native_msg  = $error_data['message'];
        if (is_null($error)) {
            static $ecode_map;
            if (empty($ecode_map)) {
                $ecode_map = array(
                    900 => MDB2_ERROR_SYNTAX,
                    904 => MDB2_ERROR_NOSUCHFIELD,
                    921 => MDB2_ERROR_SYNTAX,
                    923 => MDB2_ERROR_SYNTAX,
                    942 => MDB2_ERROR_NOSUCHTABLE,
                    955 => MDB2_ERROR_ALREADY_EXISTS,
                    1476 => MDB2_ERROR_DIVZERO,
                    1722 => MDB2_ERROR_INVALID_NUMBER,
                    2289 => MDB2_ERROR_NOSUCHTABLE,
                    2291 => MDB2_ERROR_CONSTRAINT,
                    2449 => MDB2_ERROR_CONSTRAINT,
                );
            }
            if (isset($ecode_map[$native_code])) {
                $error = $ecode_map[$native_code];
            }
        }
        return array($error, $native_code, $native_msg);
    }

    // }}}
    // {{{ autoCommit()

    /**
     * Define whether database changes done on the database be automatically
     * committed. This function may also implicitly start or end a transaction.
     *
     * @param boolean $auto_commit flag that indicates whether the database
     *                                 changes should be committed right after
     *                                 executing every query statement. If this
     *                                 argument is 0 a transaction implicitly
     *                                 started. Otherwise, if a transaction is
     *                                 in progress it is ended by committing any
     *                                 database changes that were pending.
     * @access public
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     */
    function autoCommit($auto_commit)
    {
        $this->debug(($auto_commit ? 'On' : 'Off'), 'autoCommit');
        if ($this->auto_commit == $auto_commit) {
            return MDB2_OK;
        }
        if ($this->connection && $auto_commit && MDB2::isError($commit = $this->commit())) {
            return $commit;
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
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     */
    function commit()
    {
        $this->debug('commit transaction', 'commit');
        if (!isset($this->supported['transactions'])) {
            return $this->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'commit: transactions are not in use');
        }
        if ($this->auto_commit) {
            return $this->raiseError(MDB2_ERROR, null, null,
            'commit: transaction changes are being auto commited');
        }
        if ($this->uncommitedqueries) {
            if (!@OCICommit($this->connection)) {
                return $this->raiseError();
            }
            $this->uncommitedqueries = 0;
        }
        return MDB2_OK;
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
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     */
    function rollback()
    {
        $this->debug('rolling back transaction', 'rollback');
        if ($this->auto_commit) {
            return $this->raiseError(MDB2_ERROR, null, null,
                'rollback: transactions can not be rolled back when changes are auto commited');
        }
        if ($this->uncommitedqueries) {
            if (!@OCIRollback($this->connection)) {
                return $this->raiseError();
            }
            $this->uncommitedqueries = 0;
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ _doConnect()

    /**
     * do the grunt work of the connect
     *
     * @return connection on success or MDB2 Error Object on failure
     * @access private
     */
    function _doConnect($username, $password, $persistent = false)
    {
        if (!PEAR::loadExtension($this->phptype)) {
            return $this->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'extension '.$this->phptype.' is not compiled into PHP');
        }

        if (isset($this->dsn['hostspec'])) {
            $sid = $this->dsn['hostspec'];
        } else {
            $sid = getenv('ORACLE_SID');
        }
        if (empty($sid)) {
            return $this->raiseError(MDB2_ERROR, null, null,
                'it was not specified a valid Oracle Service Identifier (SID)');
        }

        if ($this->options['HOME']) {
            putenv('ORACLE_HOME='.$this->options['HOME']);
        }
        putenv('ORACLE_SID='.$sid);
        $function = ($persistent ? 'OCIPLogon' : 'OCINLogon');
        $connection = @$function($username, $password, $sid);
        if (!$connection) {
            $connection =  $this->raiseError();
        }
        return $connection;
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to the database
     *
     * @return MDB2_OK on success, MDB2 Error Object on failure
     * @access public
     */
    function connect()
    {
        if ($this->connection != 0) {
            if (count(array_diff($this->connected_dsn, $this->dsn)) == 0
                && $this->opened_persistent == $this->options['persistent'])
            {
                return MDB2_OK;
            }
            $this->_close();
        }

        if ($this->database_name) {
            $database_name = $this->options['database_name_prefix'].$this->database_name;
            $connection = $this->_doConnect($database_name, $this->dsn['password'], $this->options['persistent']);
            if (MDB2::isError($connection)) {
                return $connection;
            }
            $this->connection = $connection;
            $this->connected_dsn = $this->dsn;
            $this->opened_persistent = $this->options['persistent'];
            $query = "ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'";
            $doquery = $this->_doQuery($query);
            if (MDB2::isError($doquery)) {
                $this->_close();
                return $doquery;
            }
            $query = "ALTER SESSION SET NLS_NUMERIC_CHARACTERS='. '";
            $doquery = $this->_doQuery($query);
            if (MDB2::isError($doquery)) {
                $this->_close();
                return $doquery;
            }
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
                $result = $this->autoCommit(true);
            }

            @OCILogOff($this->connection);
            $this->connection = 0;
            $this->affected_rows = -1;
            $this->uncommitedqueries = 0;
            unset($GLOBALS['_MDB2_databases'][$this->db_index]);

            if (isset($result) && MDB2::isError($result)) {
                return $result;
            }
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ _modifyQuery()

    /**
     * This method is used by backends to alter queries for various
     * reasons.
     *
     * @param string $query  query to modify
     * @return the new (modified) query
     * @access private
     */
    function _modifyQuery($query)
    {
        // "SELECT 2+2" must be "SELECT 2+2 FROM dual" in Oracle
        if (preg_match('/^\s*SELECT/i', $query)
            && !preg_match('/\sFROM\s/i', $query)
        ) {
            $query .= " FROM dual";
        }
        return $query;
    }

    // }}}
    // {{{ _doQuery()

    /**
     * Execute a query
     * @param string $query the SQL query
     * @return mixed result identifier if query executed, else MDB2_error
     * @access private
     **/
    function _doQuery($query, $ismanip = null, $prepared_query = 0)
    {
        $lobs = 0;
        $success = MDB2_OK;
        $result = 0;
        $descriptors = array();

        if ($prepared_query) {
            $columns = '';
            $variables = '';
            for (reset($this->clobs[$prepared_query]), $clob = 0;
                $clob < count($this->clobs[$prepared_query]);
                $clob++, next($this->clobs[$prepared_query])
            ) {
                $clob_stream = key($this->clobs[$prepared_query]);
                $descriptors[$clob_stream] = @OCINewDescriptor($this->connection, OCI_D_LOB);
                if (!is_object($descriptors[$clob_stream])) {
                    $success = $this->raiseError(MDB2_ERROR, null, null,
                        'Could not create descriptor for clob parameter');
                    break;
                }
                $parameter = $GLOBALS['_MDB2_LOBs'][$clob_stream]->parameter;
                $columns.= ($lobs == 0 ? ' RETURNING ' : ',').
                    $this->prepared_queries[$prepared_query-1]['fields'][$parameter-1];
                $variables.= ($lobs == 0 ? ' INTO ' : ',').':clob'.$parameter;
                $lobs++;
            }
            if (!MDB2::isError($success)) {
                for (reset($this->blobs[$prepared_query]), $blob = 0;
                    $blob < count($this->blobs[$prepared_query]);
                    $blob++, next($this->blobs[$prepared_query])
                ) {
                    $blob_stream = key($this->blobs[$prepared_query]);
                    $descriptors[$blob_stream] = @OCINewDescriptor($this->connection, OCI_D_LOB);
                    if (!is_object($descriptors[$blob_stream])) {
                        $success = $this->raiseError(MDB2_ERROR, null, null,
                            'Could not create descriptor for blob parameter');
                        break;
                    }
                    $parameter = $GLOBALS['_MDB2_LOBs'][$blob_stream]->parameter;
                    $columns.= ($lobs == 0 ? ' RETURNING ' : ',').
                        $this->prepared_queries[$prepared_query-1]['fields'][$parameter-1];
                    $variables.= ($lobs == 0 ? ' INTO ' : ',').':blob'.$parameter;
                    $lobs++;
                }
                $query.= $columns.$variables;
            }
        }

        if (!MDB2::isError($success)) {
            if (($statement = @OCIParse($this->connection, $query))) {
                if ($lobs) {
                    for (reset($this->clobs[$prepared_query]), $clob = 0;
                        $clob < count($this->clobs[$prepared_query]);
                        $clob++, next($this->clobs[$prepared_query])
                    ) {
                        $clob_stream = key($this->clobs[$prepared_query]);
                        $parameter = $GLOBALS['_MDB2_LOBs'][$clob_stream]->parameter;
                        if (!OCIBindByName($statement, ':clob'.$parameter, $descriptors[$clob_stream], -1, OCI_B_CLOB)) {
                            $success = $this->raiseError();
                            break;
                        }
                    }
                    if (!MDB2::isError($success)) {
                        for (reset($this->blobs[$prepared_query]), $blob = 0;
                            $blob < count($this->blobs[$prepared_query]);
                            $blob++, next($this->blobs[$prepared_query])
                        ) {
                            $blob_stream = key($this->blobs[$prepared_query]);
                            $parameter = $GLOBALS['_MDB2_LOBs'][$blob_stream]->parameter;
                            if (!OCIBindByName($statement, ':blob'.$parameter, $descriptors[$blob_stream], -1, OCI_B_BLOB)) {
                                $success = $this->raiseError();
                                break;
                            }
                        }
                    }
                }
                if (!MDB2::isError($success)) {
                    $mode = ($lobs == 0 && $this->auto_commit) ? OCI_COMMIT_ON_SUCCESS : OCI_DEFAULT;
                    $result = @OCIExecute($statement, $mode);
                    if ($result) {
                        if ($lobs) {
                            for (reset($this->clobs[$prepared_query]), $clob = 0;
                                $clob < count($this->clobs[$prepared_query]);
                                $clob++, next($this->clobs[$prepared_query])
                            ) {
                                $clob_stream = key($this->clobs[$prepared_query]);
                                for ($value = ''; !$this->datatype->endOfLOB($clob_stream);) {
                                    if ($this->datatype->readLOB($clob_stream, $data, $this->options['lob_buffer_length']) < 0) {
                                        $success = $this->raiseError();
                                        break;
                                    }
                                    $value.= $data;
                                }
                                if (!MDB2::isError($success) && !$descriptors[$clob_stream]->save($value)) {
                                    $success = $this->raiseError();
                                }
                            }
                            if (!MDB2::isError($success)) {
                                for (reset($this->blobs[$prepared_query]), $blob = 0;
                                    $blob < count($this->blobs[$prepared_query]);
                                    $blob++, next($this->blobs[$prepared_query])
                                ) {
                                    $blob_stream = key($this->blobs[$prepared_query]);
                                    for ($value = ''; !$this->datatype->endOfLOB($blob_stream);) {
                                        if ($this->datatype->readLOB($blob_stream, $data, $this->options['lob_buffer_length']) < 0) {
                                            $success = $this->raiseError();
                                            break;
                                        }
                                        $value.= $data;
                                    }
                                    if (!MDB2::isError($success) && !$descriptors[$blob_stream]->save($value)) {
                                        $success = $this->raiseError();
                                    }
                                }
                            }
                        }
                        if ($this->auto_commit) {
                            if ($lobs) {
                                if (MDB2::isError($success)) {
                                    if (!OCIRollback($this->connection)) {
                                        $success = $this->raiseError();
                                    }
                                } else {
                                    if (!OCICommit($this->connection)) {
                                        $success = $this->raiseError();
                                    }
                                }
                            }
                        } else {
                            $this->uncommitedqueries++;
                        }
                        if (!MDB2::isError($success)) {
                            if (is_null($ismanip)) {
                                $ismanip = MDB2::isManip($query);
                            }
                            if ($ismanip) {
                                $this->affected_rows = @OCIRowCount($statement);
                                @OCIFreeCursor($statement);
                            }
                            $result = $statement;
                        }
                    } else {
                        return $this->raiseError($statement);
                    }
                }
            } else {
                return $this->raiseError();
            }
        }
        for (reset($descriptors), $descriptor = 0;
            $descriptor < count($descriptors);
            $descriptor++, next($descriptors)
        ) {
            @$descriptors[key($descriptors)]->free();
        }
        if (MDB2::isError($success)) {
            return $success;
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
        $connection = $this->_doConnect($this->options['DBA_username'], $this->options['DBA_password'], $this->options['persistent']);
        if (MDB2::isError($connection)) {
            return $connection;
        }
        $result = @OCIParse($connection, $query);
        if (!$result) {
            return $this->raiseError($connection);
        }
        if ($this->auto_commit) {
            $success = @OCIExecute($result, OCI_COMMIT_ON_SUCCESS);
        } else {
            $success = @OCIExecute($result, OCI_DEFAULT);
        }
        if (!$success) {
            return $this->raiseError($result);
        }
        @OCILogOff($connection);
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
     * @param array $types array that contains the types of the columns in the result set
     * @param mixed $result_class string which specifies which result class to use
     * @return mixed a result handle or MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    function &_executePrepared($prepared_query, $query, $types = null, $result_class = false)
     {
        $ismanip = MDB2::isManip($query);
        $offset = $this->row_offset;
        $limit = $this->row_limit;
        $this->row_offset = $this->row_limit = 0;
        $query = $this->_modifyQuery($query);
        $this->last_query = $query;
        $this->debug($query, 'query');

        $connected = $this->connect();
        if (MDB2::isError($connected)) {
            return $connected;
        }

        $result = $this->_doQuery($query, $ismanip, $prepared_query);
        if (!MDB2::isError($result)) {
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
                return $result;
            }
        }
        return $result;
    }

    // }}}
    // {{{ query()

    /**
     * Send a query to the database and return any results
     *
     * @param string $query the SQL query
     * @param array $types array that contains the types of the columns in the result set
     * @param mixed $result_class string which specifies which result class to use
     *
     * @return mixed a result handle or MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function &query($query, $types = null, $result_class = false)
    {
        $result =& $this->_executePrepared(0, $query, $types, $result_class);
        return $result;
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
    function nextID($seq_name, $ondemand = true)
    {
        $sequence_name = $this->getSequenceName($seq_name);
        $this->expectError(MDB2_ERROR_NOSUCHTABLE);
        $result = $this->queryOne("SELECT $sequence_name.nextval FROM DUAL");
        $this->popExpect();
        if (MDB2::isError($result)) {
            if ($ondemand && $result->getCode() == MDB2_ERROR_NOSUCHTABLE) {
                $this->loadModule('manager');
                $result = $this->manager->createSequence($seq_name, 1);
                if (MDB2::isError($result)) {
                    return $result;
                }
                return $this->nextId($seq_name, false);
            }
        }
        return $result;
    }

    // }}}
    // {{{ currId()

    /**
     * returns the current id of a sequence
     *
     * @param string $seq_name name of the sequence
     * @return mixed MDB2_Error or id
     * @access public
     */
    function currId($seq_name)
    {
        $sequence_name = $this->getSequenceName($seq_name);
        return $this->queryOne("SELECT $sequence_name.currval FROM DUAL");
    }
}

class MDB2_Result_oci8 extends MDB2_Result_Common
{
    var $limits;

    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function MDB2_Result_oci8(&$mdb, &$result, $offset, $limit)
    {
        parent::MDB2_Result_Common($mdb, $result);
        if ($offset || $limit) {
            $this->limits = array(
                'offset' => $offset,
                'limit' => $limit,
                'count' => 0
            );
        }
    }

    // }}}
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
        if (isset($this->limits) && is_array($this->limits)) {
            if ($this->rownum >= $this->limits['limit']) {
                return false;
            }
            while ($this->limits['count'] < $this->limits['offset']) {
                $this->limits['count']++;
                if (!@OCIFetch($this->result)) {
                    $this->limits['count'] = $this->limits['offset'];
                    return false;
                }
            }
        }
        return true;
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
        $seek = $this->seek($rownum);
        if (MDB2::isError($seek)) {
            return $seek;
        }
        $fetchmode = is_numeric($colnum) ? MDB2_FETCHMODE_ORDERED : MDB2_FETCHMODE_ASSOC;
        $row = $this->fetchRow($fetchmode);
        if (!$row || MDB2::isError($row)) {
            return $row;
        }
        if (!array_key_exists($colnum, $row)) {
            return null;
        }
        return $row[$colnum];
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
        if (!$this->_skipLimitOffset()) {
            return null;
        }
        if ($fetchmode & MDB2_FETCHMODE_ASSOC) {
            @OCIFetchInto($this->result, $row, OCI_ASSOC+OCI_RETURN_NULLS);
            if ($this->mdb->options['portability'] & MDB2_PORTABILITY_LOWERCASE
                && is_array($row)
            ) {
                $row = array_change_key_case($row, CASE_LOWER);
            }
        } else {
            @OCIFetchInto($this->result, $row, OCI_RETURN_NULLS);
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
        if ($this->mdb->options['portability'] & MDB2_PORTABILITY_RTRIM) {
            $this->mdb->_rtrimArrayValues($row);
        }
        if ($this->mdb->options['portability'] & MDB2_PORTABILITY_NULL_TO_EMPTY) {
            $this->mdb->_convertNullArrayValuesToEmpty($row);
        }
        ++$this->rownum;
        return $row;
    }

    // }}}
    // {{{ getColumnNames()

    /**
     * Retrieve the names of columns returned by the DBMS in a query result.
     *
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
            $column_name = @OCIColumnName($this->result, $column + 1);
            if ($this->mdb->options['portability'] & MDB2_PORTABILITY_LOWERCASE) {
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
     * @return mixed integer value with the number of columns, a MDB2 error
     *      on failure
     * @access public
     */
    function numCols()
    {
        $cols = @OCINumCols($this->result);
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
    // {{{ free()

    /**
     * Free the internal resources associated with $result.
     *
     * @return boolean true on success, false if $result is invalid
     * @access public
     */
    function free()
    {
        $free = @OCIFreeCursor($this->result);
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

class MDB2_BufferedResult_oci8 extends MDB2_Result_oci8
{
    var $buffer;
    var $buffer_rownum = - 1;

    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function MDB2_BufferedResult_oci8(&$mdb, &$result, $offset, $limit)
    {
        parent::MDB2_Result_oci8($mdb, $result, $offset, $limit);
    }

    // }}}
    // {{{ _fillBuffer()

    /**
     * Fill the row buffer
     *
     * @param int $rownum   row number upto which the buffer should be filled
                            if the row number is null all rows are ready into the buffer
     * @return boolean true on success, false on failure
     * @access private
     */
    function _fillBuffer($rownum = null)
    {
        if (isset($this->buffer) && is_array($this->buffer)) {
            if (is_null($rownum)) {
                if (!end($this->buffer)) {
                    return false;
                }
            } else if (isset($this->buffer[$rownum])) {
                return (bool)$this->buffer[$rownum];
            }
        }

        if (!$this->_skipLimitOffset()) {
            return false;
        }

        $row = true;
        while ((is_null($rownum) || $this->buffer_rownum < $rownum)
            && (!isset($this->limits) || $this->buffer_rownum < $this->limits['limit'])
            && ($row = @OCIFetchInto($this->result, $buffer, OCI_RETURN_NULLS))
        ) {
            $this->buffer_rownum++;
            $this->buffer[$this->buffer_rownum] = $buffer;
        }

        if ((isset($this->limits) && $this->buffer_rownum >= $this->limits['limit'])
            || !$row
        ) {
            $this->buffer_rownum++;
            $this->buffer[$this->buffer_rownum] = false;
            return false;
        }
        return true;
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
        if (is_null($this->result)) {
            return $this->mdb->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                'fetchRow: resultset has already been freed');
        }
        $target_rownum = $this->rownum + 1;
        if ($fetchmode == MDB2_FETCHMODE_DEFAULT) {
            $fetchmode = $this->mdb->fetchmode;
        }
        if (!$this->_fillBuffer($target_rownum)) {
            return null;
        }
        $row = $this->buffer[$target_rownum];
        if ($fetchmode & MDB2_FETCHMODE_ASSOC) {
            $column_names = $this->getColumnNames();
            foreach ($column_names as $name => $i) {
                $column_names[$name] = $row[$i];
            }
            $row = $column_names;
        }
        if (isset($this->types)) {
            $row = $this->mdb->datatype->convertResultRow($this->types, $row);
        }
        if ($this->mdb->options['portability'] & MDB2_PORTABILITY_RTRIM) {
            $this->mdb->_rtrimArrayValues($row);
        }
        if ($this->mdb->options['portability'] & MDB2_PORTABILITY_NULL_TO_EMPTY) {
            $this->mdb->_convertNullArrayValuesToEmpty($row);
        }
        ++$this->rownum;
        return $row;
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
        if (is_null($this->result)) {
            return $this->mdb->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                'seek: resultset has already been freed');
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
        if (is_null($this->result)) {
            return $this->mdb->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                'hasMore: resultset has already been freed');
        }
        if ($this->_fillBuffer($this->rownum + 1)) {
            return true;
        }
        return false;
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
        if (is_null($this->result)) {
            return $this->mdb->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                'seek: resultset has already been freed');
        }
        $this->_fillBuffer();
        return $this->buffer_rownum;
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
        $this->buffer = null;
        $this->buffer_rownum = null;
        $free = parent::free();
    }
}

?>