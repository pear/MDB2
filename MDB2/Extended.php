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
// | Author: Lukas Smith <smith@backendmedia.com>                         |
// +----------------------------------------------------------------------+
//
// $Id$

/**
 * @package  MDB2
 * @category Database
 * @author   Lukas Smith <smith@backendmedia.com>
 */

/**
 * Used by autoPrepare()
 */
define('MDB2_AUTOQUERY_INSERT', 1);
define('MDB2_AUTOQUERY_UPDATE', 2);

/**
 * MDB2_Extended: class which adds several high level methods to MDB2
 *
 * @package MDB2
 * @category Database
 * @author Lukas Smith <smith@backendmedia.com>
 */
class MDB2_Extended
{
    var $db_index;

    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function MDB2_Extended($db_index)
    {
        $this->db_index = $db_index;
    }

    // }}}
    // {{{ autoPrepare()

    /**
     * Make automaticaly an insert or update query and call prepare() with it
     *
     * @param string $table name of the table
     * @param array $table_fields ordered array containing the fields names
     * @param array $types array that contains the types of the columns in
     *        the result set
     * @param int $mode type of query to make (MDB2_AUTOQUERY_INSERT or MDB2_AUTOQUERY_UPDATE)
     * @param string $where in case of update queries, this string will be put after the sql WHERE statement
     * @return resource handle for the query
     * @see buildManipSQL
     * @access public
     */
    function autoPrepare($table, $table_fields, $types = null,
        $mode = MDB2_AUTOQUERY_INSERT, $where = false)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $query = $this->buildManipSQL($table, $table_fields, $mode, $where);
        return $db->prepare($query, $types);
    }

    // {{{
    // }}} autoExecute()

    /**
     * Make automaticaly an insert or update query and call prepare() and executeParams() with it
     *
     * @param string $table name of the table
     * @param array $fields_values assoc ($key=>$value) where $key is a field name and $value its value
     * @param array $types array that contains the types of the columns in
     *        the result set
     * @param int $mode type of query to make (MDB2_AUTOQUERY_INSERT or MDB2_AUTOQUERY_UPDATE)
     * @param array $param_types array that contains the types of the values
     *        defined in $params
     * @param string $where in case of update queries, this string will be put after the sql WHERE statement
     * @param mixed $result_class string which specifies which result class to use
     * @return mixed  a new MDB2_Result or a MDB2 Error Object when fail
     * @see buildManipSQL
     * @see autoPrepare
     * @access public
    */
    function &autoExecute($table, $fields_values, $types = null, $param_types = null,
        $mode = MDB2_AUTOQUERY_INSERT, $where = false, $result_class = false)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $prepared_query = $this->autoPrepare($table, array_keys($fields_values), $types, $mode, $where);
        $result =& $this->executeParams($prepared_query, array_values($fields_values), $param_types, $result_class);
        $db->freePrepared($prepared_query);
        return $result;
    }

    // {{{
    // }}} buildManipSQL()

    /**
     * Make automaticaly an sql query for prepare()
     *
     * Example : buildManipSQL('table_sql', array('field1', 'field2', 'field3'), MDB2_AUTOQUERY_INSERT)
     *           will return the string : INSERT INTO table_sql (field1,field2,field3) VALUES (?,?,?)
     * NB : - This belongs more to a SQL Builder class, but this is a simple facility
     *      - Be carefull ! If you don't give a $where param with an UPDATE query, all
     *        the records of the table will be updated !
     *
     * @param string $table name of the table
     * @param array $table_fields ordered array containing the fields names
     * @param int $mode type of query to make (MDB2_AUTOQUERY_INSERT or MDB2_AUTOQUERY_UPDATE)
     * @param string $where in case of update queries, this string will be put after the sql WHERE statement
     * @return string sql query for prepare()
     * @access public
     */
    function buildManipSQL($table, $table_fields, $mode, $where = false)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if (count($table_fields) == 0) {
            $db->raiseError(MDB2_ERROR_NEED_MORE_DATA);
        }
        $first = true;
        switch ($mode) {
            case MDB2_AUTOQUERY_INSERT:
                $values = '';
                $names = '';
                while (list(, $value) = each($table_fields)) {
                    if ($first) {
                        $first = false;
                    } else {
                        $names .= ',';
                        $values .= ',';
                    }
                    $names .= $value;
                    $values .= '?';
                }
                return "INSERT INTO $table ($names) VALUES ($values)";
                break;
            case MDB2_AUTOQUERY_UPDATE:
                $set = '';
                while (list(, $value) = each($table_fields)) {
                    if ($first) {
                        $first = false;
                    } else {
                        $set .= ',';
                    }
                    $set .= "$value = ?";
                }
                $sql = "UPDATE $table SET $set";
                if ($where) {
                    $sql .= " WHERE $where";
                }
                return $sql;
                break;
            default:
                $db->raiseError(MDB2_ERROR_SYNTAX);
        }
    }

    // {{{
    // }}} limitQuery()

    /**
     * Generates a limited query
     *
     * @param string $query query
     * @param mixed   $types  array that contains the types of the columns in
     *                        the result set
     * @param integer $from the row to start to fetching
     * @param integer $count the numbers of rows to fetch
     * @param mixed $result_class string which specifies which result class to use
     * @return mixed a valid ressource pointer or a MDB2 Error Object
     * @access public
     */
    function &limitQuery($query, $types, $count, $from = 0, $result_class = false)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $result = $db->setLimit($count, $from);
        if (MDB2::isError($result)) {
            return $result;
        }
        $result =& $db->query($query, $types, $result_class);
        return $result;
    }
}
?>