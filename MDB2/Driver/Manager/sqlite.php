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
//

require_once 'MDB2/Driver/Manager/Common.php';

/**
 * MDB2 SQLite driver for the management modules
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@backendmedia.com>
 */
class MDB2_Driver_Manager_sqlite extends MDB2_Driver_Manager_Common
{
    // {{{ createDatabase()

    /**
     * create a new database
     *
     * @param string $name name of the database that should be created
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createDatabase($name)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $database_file = $db->_getDatabaseFile($name);
        if (file_exists($database_file)) {
            return $db->raiseError(MDB2_ERROR_CANNOT_CREATE, null, null,
                'createDatabase: database already exists');
        }
        $php_errormsg = '';
        $handle = @sqlite_open($database_file, $db->dsn['mode'], $php_errormsg);
        if (!$handle) {
            return $db->raiseError(MDB2_ERROR_CANNOT_CREATE, null, null,
                'createDatabase: '.(isset($php_errormsg) ? $php_errormsg : 'could not create the database file'));
        }
        @sqlite_close($handle);
        return MDB2_OK;
    }

    // }}}
    // {{{ dropDatabase()

    /**
     * drop an existing database
     *
     * @param string $name name of the database that should be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function dropDatabase($name)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $database_file = $db->_getDatabaseFile($name);
        if (!@file_exists($database_file)) {
            return $db->raiseError(MDB2_ERROR_CANNOT_DROP, null, null,
                'dropDatabase: database does not exist');
        }
        $success = @unlink($database_file);
        if (!$success) {
            return $db->raiseError(MDB2_ERROR_CANNOT_DROP, null, null,
                'dropDatabase: '.(isset($php_errormsg) ? $php_errormsg : 'could not remove the database file'));
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ createTable()

    /**
     * create a new table
     *
     * @param string $name     Name of the database that should be created
     * @param array $fields Associative array that contains the definition of each field of the new table
     *                        The indexes of the array entries are the names of the fields of the table an
     *                        the array entry values are associative arrays like those that are meant to be
     *                         passed with the field definitions to get[Type]Declaration() functions.
     *
     *                        Example
     *                        array(
     *
     *                            'id' => array(
     *                                'type' => 'integer',
     *                                'unsigned' => 1
     *                                'notnull' => 1
     *                                'default' => 0
     *                            ),
     *                            'name' => array(
     *                                'type' => 'text',
     *                                'length' => 12
     *                            ),
     *                            'password' => array(
     *                                'type' => 'text',
     *                                'length' => 12
     *                            )
     *                        );
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createTable($name, $fields)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if (!isset($name) || !strcmp($name, '')) {
            return $db->raiseError(MDB2_ERROR_CANNOT_CREATE, null, null,
                'createTable: no valid table name specified');
        }
        if (count($fields) == 0) {
            return $db->raiseError(MDB2_ERROR_CANNOT_CREATE, null, null,
                'createTable: no fields specified for table "'.$name.'"');
        }
        if (MDB2::isError($query_fields = $this->getFieldDeclarationList($fields))) {
            return $db->raiseError(MDB2_ERROR_CANNOT_CREATE, null, null,
                'createTable: unkown error');
        }
        $query = "CREATE TABLE $name ($query_fields)";
        return $db->query($query);
    }
    // }}}
    // {{{ listDatabases()

    /**
     * list all databases
     *
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function listDatabases()
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'listDatabases: list databases is not supported');
    }

    // }}}
    // {{{ listUsers()

    /**
     * list all users
     *
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function listUsers()
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return $db->queryCol('SELECT DISTINCT USER FROM USER');
    }

    // }}}
    // {{{ listTables()

    /**
     * list all tables in the current database
     *
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function listTables()
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $query = "SELECT name FROM sqlite_master WHERE type='table' AND sql NOT NULL ORDER BY name";
        $table_names = $db->queryCol($query);
        if (MDB2::isError($table_names)) {
            return $table_names;
        }
        for ($i = 0, $j = count($table_names), $tables = array(); $i < $j; ++$i)
        {
            if (!$this->_isSequenceName($table_names[$i]))
                $tables[] = $table_names[$i];
        }
        return $tables;
    }

    function _getTableColumns($sql)
    {
        $start_pos = strpos($sql,"(");
        $end_pos = strrpos($sql,")");
        $column_def = substr($sql, $start_pos+1, $end_pos-$start_pos-1);
        $column_sql = split(",", $column_def);
        $columns = array();
        $count = count($column_sql);
        if ($count == 0) {
            return $db->raiseError('unexpected empty table column definition list');
        }
        $regexp = '/^([^ ]+) (CHAR|VARCHAR|VARCHAR2|TEXT|INT|INTEGER|BIGINT|DOUBLE|FLOAT|DATETIME|DATE|TIME|LONGTEXT|LONGBLOB)( PRIMARY)?( \(([1-9][0-9]*)(,([1-9][0-9]*))?\))?( DEFAULT (\'[^\']*\'|[^ ]+))?( NOT NULL)?$/i';
        for ($i=0, $j=0; $i<$count; ++$i) {
            if (!preg_match($regexp, $column_sql[$i], $matches)) {
                return $db->raiseError('unexpected table column SQL definition');
            }
            $columns[$j]['name'] = $matches[1];
            $columns[$j]['type'] = strtolower($matches[2]);
            if (isset($matches[5]) && strlen($matches[5])) {
                $columns[$j]['length'] = $matches[5];
            }
            if (isset($matches[7]) && strlen($matches[7])) {
                $columns[$j]['decimal'] = $matches[7];
            }
            if (isset($matches[9]) && strlen($matches[9])) {
                $default = $matches[9];
                if (strlen($default) && $default[0]=="'") {
                    $default = str_replace("''","'",substr($default, 1, strlen($default)-2));
                }
                $columns[$j]['default'] = $default;
            }
            if (isset($matches[10]) && strlen($matches[10])) {
                $columns[$j]['notnull'] = true;
            }
            ++$j;
        }
        return $columns;
    }

    // }}}
    // {{{ listTableFields()

    /**
     * list all fields in a tables in the current database
     *
     * @param string $table name of table that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function listTableFields($table)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $query = "SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'";
        $result = $db->queryCol($query);
        if (MDB2::isError($result)) {
            return $result;
        }
        $columns = $result->getColumnNames();
        $result->free();
        if (MDB2::isError($columns)) {
            return $columns;
        }
        return array_flip($columns);
    }

    // }}}
    // {{{ createIndex()

    /**
     * get the stucture of a field into an array
     *
     * @param string    $table         name of the table on which the index is to be created
     * @param string    $name         name of the index to be created
     * @param array     $definition        associative array that defines properties of the index to be created.
     *                                 Currently, only one property named FIELDS is supported. This property
     *                                 is also an associative with the names of the index fields as array
     *                                 indexes. Each entry of this array is set to another type of associative
     *                                 array that specifies properties of the index that are specific to
     *                                 each field.
     *
     *                                Currently, only the sorting property is supported. It should be used
     *                                 to define the sorting direction of the index. It may be set to either
     *                                 ascending or descending.
     *
     *                                Not all DBMS support index sorting direction configuration. The DBMS
     *                                 drivers of those that do not support it ignore this property. Use the
     *                                 function support() to determine whether the DBMS driver can manage indexes.

     *                                 Example
     *                                    array(
     *                                        'fields' => array(
     *                                            'user_name' => array(
     *                                                'sorting' => 'ascending'
     *                                            ),
     *                                            'last_login' => array()
     *                                        )
     *                                    )
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createIndex($table, $name, $definition)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $query = 'CREATE '.(isset($definition['unique']) ? 'UNIQUE' : '')." INDEX $name ON $table (";
        for ($field = 0, reset($definition['fields']);
            $field < count($definition['fields']);
            $field++, next($definition['fields']))
        {
            if ($field > 0) {
                $query .= ',';
            }
            $query .= key($definition['fields']);
        }
        $query .= ')';
        return $db->query($query);
    }

    // }}}
    // {{{ dropIndex()

    /**
     * drop existing index
     *
     * @param string    $table         name of table that should be used in method
     * @param string    $name         name of the index to be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function dropIndex($table, $name)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return $db->query("DROP INDEX $name");
    }

    // }}}
    // {{{ listTableIndexes()

    /**
     * list all indexes in a table
     *
     * @param string    $table      name of table that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function listTableIndexes($table)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $query = "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='$table' AND sql NOT NULL ORDER BY name";
        $indexes_all = $db->queryCol($query);
        if (MDB2::isError($indexes_all)) {
            return $indexes_all;
        }
        for ($found = $indexes = array(), $index = 0, $indexes_all_cnt = count($indexes_all);
            $index < $indexes_all_cnt;
            $index++)
        {
            if (!isset($found[$indexes_all[$index]]))
            {
                $indexes[] = $indexes_all[$index];
                $found[$indexes_all[$index]] = true;
            }
        }
        return $indexes;
    }

    // }}}
    // {{{ createSequence()

    /**
     * create sequence
     *
     * @param string    $seq_name     name of the sequence to be created
     * @param string    $start         start value of the sequence; default is 1
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createSequence($seq_name, $start = 1)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $sequence_name = $db->getSequenceName($seq_name);
        $seqname_col_name = $db->options['seqname_col_name'];
        $query = "CREATE TABLE $sequence_name ($seqname_col_name INTEGER PRIMARY KEY DEFAULT 0 NOT NULL)";
        $res = $db->query($query);
        if (MDB2::isError($res)) {
            return $res;
        }
        if ($start == 1) {
            return MDB2_OK;
        }
        $res = $db->query("INSERT INTO $sequence_name ($seqname_col_name) VALUES (".($start-1).')');
        if (!MDB2::isError($res)) {
            return MDB2_OK;
        }
        // Handle error
        $result = $db->query("DROP TABLE $sequence_name");
        if (MDB2::isError($result)) {
            return $db->raiseError(MDB2_ERROR, null, null,
                'createSequence: could not drop inconsistent sequence table ('.
                $result->getMessage().' ('.$result->getUserinfo().'))');
        }
        return $db->raiseError(MDB2_ERROR, null, null,
            'createSequence: could not create sequence table ('.
            $res->getMessage().' ('.$res->getUserinfo().'))');
    }

    // }}}
    // {{{ dropSequence()

    /**
     * drop existing sequence
     *
     * @param string    $seq_name     name of the sequence to be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function dropSequence($seq_name)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $sequence_name = $db->getSequenceName($seq_name);
        return $db->query("DROP TABLE $sequence_name");
    }

    // }}}
    // {{{ listSequences()

    /**
     * list all sequences in the current database
     *
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function listSequences()
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $query = "SELECT name FROM sqlite_master WHERE type='table' AND sql NOT NULL ORDER BY name";
        $table_names = $db->queryCol($query);
        if (MDB2::isError($table_names)) {
            return $table_names;
        }
        for ($i = 0, $j = count($table_names), $sequences = array(); $i < $j; ++$i)
        {
            if ($sqn = $this->_isSequenceName($table_names[$i]))
                $sequences[] = $sqn;
        }
        return $sequences;
    }

    // }}}
}
?>
