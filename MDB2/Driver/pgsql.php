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
        $this->supported['replace'] = true;
        $this->supported['sub_selects'] = true;
        $this->supported['auto_increment'] = false;
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
        $native_msg = '';
        if (is_resource($error)) {
            $native_msg = @pg_result_error($error);
        } elseif ($this->connection) {
            $native_msg = @pg_errormessage($this->connection);
        }

        // Fall back to MDB2_ERROR if there was no mapping.
        $error = MDB2_ERROR;

        static $error_regexps;
        if (empty($error_regexps)) {
            $error_regexps = array(
                '/(([Rr]elation|[Ss]equence|[Tt]able)( [\"\'].*[\"\'])? does not exist|[Cc]lass ".+" not found)$/' => MDB2_ERROR_NOSUCHTABLE,
                '/[Cc]olumn [\"\'].*[\"\'] .*does not exist/' => MDB2_ERROR_NOSUCHFIELD,
                '/[Rr]elation [\"\'].*[\"\'] already exists|[Cc]annot insert a duplicate key into (a )?unique index.*/' => MDB2_ERROR_ALREADY_EXISTS,
                '/(divide|division) by zero$/'          => MDB2_ERROR_DIVZERO,
                '/pg_atoi: error in .*: can\'t parse /' => MDB2_ERROR_INVALID_NUMBER,
                '/invalid input syntax for integer/'    => MDB2_ERROR_INVALID_NUMBER,
                '/ttribute [\"\'].*[\"\'] not found$|[Rr]elation [\"\'].*[\"\'] does not have attribute [\"\'].*[\"\']/' => MDB2_ERROR_NOSUCHFIELD,
                '/parser: parse error at or near \"/'   => MDB2_ERROR_SYNTAX,
                '/syntax error at/'                     => MDB2_ERROR_SYNTAX,
                '/permission denied/'                   => MDB2_ERROR_ACCESS_VIOLATION,
                '/violates not-null constraint/'        => MDB2_ERROR_CONSTRAINT_NOT_NULL,
                '/violates [\w ]+ constraint/'          => MDB2_ERROR_CONSTRAINT,
                '/referential integrity violation/'     => MDB2_ERROR_CONSTRAINT
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
        if (MDB2::isError($result)) {
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
        if (MDB2::isError($result)) {
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
        if (MDB2::isError($result)) {
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
     * @access private
     **/
    function _doConnect($database_name, $persistent = false)
    {
        if ($database_name == '') {
            $database_name = 'template1';
        }
        $dsninfo = $this->dsn;
        $protocol = (isset($dsninfo['protocol'])) ? $dsninfo['protocol'] : 'tcp';
        $connstr = '';

        if ($protocol == 'tcp') {
            if ($dsninfo['hostspec']) {
                $connstr .= ' host=' . $dsninfo['hostspec'];
            }
            if ($dsninfo['port']) {
                $connstr .= ' port=' . $dsninfo['port'];
            }
        } elseif ($protocol == 'unix') {
            // Allow for pg socket in non-standard locations.
            if ($dsninfo['socket']) {
                $connstr .= ' host=' . $dsninfo['socket'];
            }
            if ($dsninfo['port']) {
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
        $ini = ini_get('track_errors');
        if ($ini) {
            $connection = @$function($connstr);
        } else {
            ini_set('track_errors', 1);
            $connection = @$function($connstr);
            ini_set('track_errors', $ini);
        }
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
        if ($this->connection != 0) {
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
            if (MDB2::isError($connection)) {
                return $connection;
            }
            $this->connection = $connection;
            $this->connected_dsn = $this->dsn;
            $this->connected_database_name = $this->database_name;
            $this->opened_persistent = $this->options['persistent'];
            $this->dbsyntax = $dsninfo['dbsyntax'] ? $dsninfo['dbsyntax'] : $this->phptype;
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
        if ($this->connection != 0) {
            if (!$this->opened_persistent || $force) {
                @pg_close($this->connection);
            }
            $this->connection = 0;
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
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function &standaloneQuery($query, $types = null)
    {
        $connection = $this->_doConnect('template1', false);
        if (MDB2::isError($connection)) {
            return $this->raiseError(MDB2_ERROR_CONNECT_FAILED, null, null,
                'Cannot connect to template1');
        }

        $isManip = MDB2::isManip($query);
        $offset = $this->row_offset;
        $limit = $this->row_limit;
        $this->row_offset = $this->row_limit = 0;
        $query = $this->_modifyQuery($query, $isManip, $limit, $offset);

        $result = $this->_doQuery($query, $isManip, $connection, false);
        @pg_close($connection);
        if (MDB2::isError($result)) {
            return $result;
        }

        if ($isManip) {
            return $result;
        }

        return $this->_wrapResult($result, $types, true, false, $limit, $offset);
    }

    // }}}
    // {{{ _doQuery()

    /**
     * Execute a query
     * @param string $query  query
     * @param boolean $isManip  if the query is a manipulation query
     * @param resource $connection
     * @param string $database_name
     * @return result or error object
     * @access private
     */
    function _doQuery($query, $isManip = false, $connection = null, $database_name = null)
    {
        $this->last_query = $query;
        $this->debug($query, 'query');
        if ($this->options['disable_query']) {
            if ($isManip) {
                return MDB2_OK;
            }
            return null;
        }

        if (is_null($connection)) {
            if (!$this->connection) {
                $error = $this->connect();
                if (MDB2::isError($error)) {
                    return $error;
                }
            }
            $connection = $this->connection;
        }

        $result = @pg_exec($connection, $query);
        if (!$result) {
            return $this->raiseError();
        }

        if ($isManip) {
            return @pg_affected_rows($result);
        }
        return $result;
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
    function _modifyQuery($query, $isManip, $limit, $offset)
    {
        if ($limit > 0) {
            if ($isManip) {
                $manip = preg_replace('/^(DELETE FROM|UPDATE).*$/', '\\1', $query);
                $from = $match[2];
                $where = $match[3];
                $query = $manip.' '.$from.' WHERE ctid=(SELECT ctid FROM '.$from.' '.$where.' LIMIT '.$limit.')';
            } else {
                $query .= " LIMIT $limit OFFSET $offset";
            }
        }
        return $query;
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
        $this->expectError(MDB2_ERROR_NOSUCHTABLE);
        $result = $this->queryOne("SELECT NEXTVAL('$seq_name')", 'integer');
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
        return $this->queryOne("SELECT last_value FROM $seq_name", 'integer');
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
            if (MDB2::isError($seek)) {
                return $seek;
            }
        }
        if ($fetchmode == MDB2_FETCHMODE_DEFAULT) {
            $fetchmode = $this->db->fetchmode;
        }
        if ($fetchmode & MDB2_FETCHMODE_ASSOC) {
            $row = @pg_fetch_array($this->result, null, PGSQL_ASSOC);
            if (is_array($row)
                && $this->db->options['portability'] & MDB2_PORTABILITY_LOWERCASE
            ) {
                $row = array_change_key_case($row, CASE_LOWER);
            }
        } else {
            $row = @pg_fetch_row($this->result);
        }
        if (!$row) {
            if (is_null($this->result)) {
                return $this->db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'fetchRow: resultset has already been freed');
            }
            return null;
        }
        if ($this->db->options['portability'] & MDB2_PORTABILITY_EMPTY_TO_NULL) {
            $this->db->_convertEmptyArrayValuesToNull($row);
        }
        if (isset($this->types)) {
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
            $columns[$column_name] = $column;
        }
        if ($this->db->options['portability'] & MDB2_PORTABILITY_LOWERCASE) {
            $columns = array_change_key_case($columns, CASE_LOWER);
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
        $rows = @pg_num_rows($this->result);
        if (is_null($rows)) {
            if (is_null($this->result)) {
                return $this->db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'numRows: resultset has already been freed');
            }
            return $this->raiseError();
        }
        return $rows;
    }
}

class MDB2_Statement_pgsql extends MDB2_Statement_Common
{

}
?>