<?php
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
// | Author: YOUR NAME <YOUR EMAIL>                                       |
// +----------------------------------------------------------------------+
//
// $Id$
//

// This is just a skeleton MDB2 driver.
// There may be methods missing as this skeleton is based on the methods
// implemented by the MySQL and PostGreSQL drivers in MDB2.
// Some methods may not have to be implemented in the driver, because the
// implementation in common.php is compatible with the given RDBMS.
// In each of the listed methods I have added comments that tell you where
// to look for a "reference" implementation.
// Some of these methods have been expanded or changed slightly in MDB2.
// Looking in the relevant MDB2 Wrapper should give you some pointers, some
// other difference you will only discover by looking at one of the existing
// MDB2 driver or the common implementation in common.php.
// One thing that will definately have to be modified in all "reference"
// implementations of Metabase methods is the error handling.
// Anyways don't worry if you are having problems: Lukas Smith is here to help!

/**
 * MDB2 XXX driver
 *
 * @package MDB2
 * @category Database
 * @author  YOUR NAME <YOUR EMAIL>
 */
class MDB2_xxx extends MDB2_Driver_Common
{
// Most of the class variables are taken from the corresponding Metabase driver.
// Few are taken from the corresponding PEAR DB driver.
// Some are MDB2 specific.

    var $escape_quotes =;

    // {{{ constructor

    /**
    * Constructor
    */
    function MDB2_xxx()
    {
        $this->MDB2_Driver_Common();
        $this->phptype = xxx;
        $this->dbsyntax = xxx;

        $this->supported['sequences'] = ;
        $this->supported['indexes'] = ;
        $this->supported['affected_rows'] = ;
        $this->supported['summary_functions'] = ;
        $this->supported['order_by_text'] = ;
        $this->supported['current_id'] = ;
        $this->supported['limit_queries'] = ;
        $this->supported['LOBs'] = ;
        $this->supported['replace'] = ;
        $this->supported['sub_selects'] = ;
        $this->supported['auto_increment'] = ;
        // most of the following codes needs to be taken from the corresponding Metabase driver setup() methods

        // also please remember to "register" all driver specific options here like so
        // $this->options['option_name'] = 'non null default value';
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
        // take this method from the corresponding PEAR DB driver: xxxRaiseError(), errorCode() and errorNative()
        // the error code maps from corresponding PEAR DB driver constructor
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
        // take this from the corresponding Metabase driver: AutoCommitTransactions()
        // the MetabaseShutdownTransactions function is handled by the PEAR desctructor
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
        // take this from the corresponding Metabase driver: CommitTransaction()
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
        // take this from the corresponding Metabase driver: RollbackTransaction()
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
        // take this from the corresponding Metabase driver: Connect() and Setup()
        if (PEAR::isError(PEAR::loadExtension($this->phptype))) {
            return PEAR::raiseError(null, MDB2_ERROR_NOT_FOUND,
                null, null, 'extension '.$this->phptype.' is not compiled into PHP',
                'MDB2_Error', true);
        }
    }

    // }}}
    // {{{ _close()
    /**
     * all the RDBMS specific things needed close a DB connection
     *
     * @access private
     *
     */
    function _close()
    {
        // take this from the corresponding Metabase driver: Close()
    }

    // }}}
    // {{{ query()

    /**
     * Send a query to the database and return any results
     *
     * @access public
     *
     * @param string  $query  the SQL query
     * @param array   $types  array that contains the types of the columns in
     *                        the result set
     *
     * @return mixed a result handle or MDB2_OK on success, a MDB2 error on failure
     */
    function &query($query, $types = null)
    {
        // take this from the corresponding Metabase driver: Query()
    }

    // }}}
    // {{{ subSelect()

    /**
     * simple subselect emulation for Mysql
     *
     * @access public
     *
     * @param string $query the SQL query for the subselect that may only
     *                      return a column
     * @param string $quote determines if the data needs to be quoted before
     *                      being returned
     *
     * @return string the query
     */
    function subSelect($query, $quote = false)
    {
        // This is a new method that only needs to be added if the RDBMS does
        // not support sub-selects. See the MySQL driver for an example
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
     * practically only MySQL implements it natively, this type of query is
     * emulated through this method for other DBMS using standard types of
     * queries inside a transaction to assure the atomicity of the operation.
     *
     * @access public
     *
     * @param string $table name of the table on which the REPLACE query will
     *  be executed.
     * @param array $fields associative array that describes the fields and the
     *  values that will be inserted or updated in the specified table. The
     *  indexes of the array are the names of all the fields of the table. The
     *  values of the array are also associative arrays that describe the
     *  values and other properties of the table fields.
     *
     *  Here follows a list of field properties that need to be specified:
     *
     *    Value:
     *          Value to be assigned to the specified field. This value may be
     *          of specified in database independent type format as this
     *          function can perform the necessary datatype conversions.
     *
     *    Default:
     *          this property is required unless the Null property
     *          is set to 1.
     *
     *    Type
     *          Name of the type of the field. Currently, all types Metabase
     *          are supported except for clob and blob.
     *
     *    Default: text
     *
     *    Null
     *          Boolean property that indicates that the value for this field
     *          should be set to null.
     *
     *          The default value for fields missing in INSERT queries may be
     *          specified the definition of a table. Often, the default value
     *          is already null, but since the REPLACE may be emulated using
     *          an UPDATE query, make sure that all fields of the table are
     *          listed in this function argument array.
     *
     *    Default: 0
     *
     *    Key
     *          Boolean property that indicates that this field should be
     *          handled as a primary key or at least as part of the compound
     *          unique index of the table that will determine the row that will
     *          updated if it exists or inserted a new row otherwise.
     *
     *          This function will fail if no key field is specified or if the
     *          value of a key field is set to null because fields that are
     *          part of unique index they may not be null.
     *
     *    Default: 0
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     */
    function replace($table, $fields)
    {
        // take this from the corresponding Metabase driver: Replace()
    }

    // }}}
    // {{{ nextId()

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
        // take this from the corresponding PEAR DB driver: nextId()
    }


    // }}}
    // {{{ currId()

    /**
     * returns the current id of a sequence
     *
     * @param string  $seq_name name of the sequence
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function currID($seq_name)
    {
        // take this from the corresponding Metabase driver: GetSequenceCurrentValue()
    }
}

class MDB2_Result_xxx extends MDB2_Result_Common
{
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
        // take this from the corresponding Metabase driver: FetchResult()
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
        // take this from the corresponding Metabase driver: FetchResultArray()
        // possibly you also need to take code from Metabases FetchRow() method
        // note Metabases FetchRow() method should not be confused with MDB2's fetchRow()
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
        // take this from the corresponding Metabase driver: GetColumnNames()
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
        // take this from the corresponding Metabase driver: NumberOfColumns()
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
        // take this from the corresponding Metabase driver: ResultIsNull()
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal mysql result pointer to the next available result
     * Currently not supported
     *
     * @return true if a result is available otherwise return false
     * @access public
     */
    function nextResult()
    {
        // take this from the corresponding PEAR DB driver: nextResult()
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
        // take this from the corresponding Metabase driver: FreeResult()
    }
}

class MDB2_BufferedResult_xxx extends MDB2_Result_xxx
{
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
        // take this from the corresponding Metabase driver: FetchResultArray()
        // possibly you also need to take code from Metabases FetchRow() method
        // note Metabases FetchRow() method should not be confused with MDB2's fetchRow()
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
        // take this from the corresponding Metabase driver: NumberOfRows()
    }
}

?>