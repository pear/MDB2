<?php
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
// | Author: Frank M. Kromann <frank@kromann.info>                        |
// +----------------------------------------------------------------------+
//
// $Id$
//

require_once 'MDB2/Driver/Manager/Common.php';

/**
 * MDB2 MSSQL driver for the management modules
 *
 * @package MDB2
 * @category Database
 * @author  Frank M. Kromann <frank@kromann.info>
 */
class MDB2_Driver_Manager_mssql extends MDB2_Driver_Manager_Common
{
    // }}}
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
        $database_device = (isset($db->options['database_device'])) ? $db->options['database_device'] : 'DEFAULT';
        $database_size = (isset($db->options['database_size'])) ? '='.$db->options['database_size'] : '';
        return $db->standaloneQuery("CREATE DATABASE $name ON $database_device$database_size");
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
        return $db->standaloneQuery("DROP DATABASE $name");
    }

    // }}}
    // {{{ alterTable()

    /**
     * alter an existing table
     *
     * @param string $name         name of the table that is intended to be changed.
     * @param array $changes     associative array that contains the details of each type
     *                             of change that is intended to be performed. The types of
     *                             changes that are currently supported are defined as follows:
     *
     *                             name
     *
     *                                New name for the table.
     *
     *                            added_fields
     *
     *                                Associative array with the names of fields to be added as
     *                                 indexes of the array. The value of each entry of the array
     *                                 should be set to another associative array with the properties
     *                                 of the fields to be added. The properties of the fields should
     *                                 be the same as defined by the Metabase parser.
     *
     *                                Additionally, there should be an entry named Declaration that
     *                                 is expected to contain the portion of the field declaration already
     *                                 in DBMS specific SQL code as it is used in the CREATE TABLE statement.
     *
     *                            removed_fields
     *
     *                                Associative array with the names of fields to be removed as indexes
     *                                 of the array. Currently the values assigned to each entry are ignored.
     *                                 An empty array should be used for future compatibility.
     *
     *                            renamed_fields
     *
     *                                Associative array with the names of fields to be renamed as indexes
     *                                 of the array. The value of each entry of the array should be set to
     *                                 another associative array with the entry named name with the new
     *                                 field name and the entry named Declaration that is expected to contain
     *                                 the portion of the field declaration already in DBMS specific SQL code
     *                                 as it is used in the CREATE TABLE statement.
     *
     *                            changed_fields
     *
     *                                Associative array with the names of the fields to be changed as indexes
     *                                 of the array. Keep in mind that if it is intended to change either the
     *                                 name of a field and any other properties, the changed_fields array entries
     *                                 should have the new names of the fields as array indexes.
     *
     *                                The value of each entry of the array should be set to another associative
     *                                 array with the properties of the fields to that are meant to be changed as
     *                                 array entries. These entries should be assigned to the new values of the
     *                                 respective properties. The properties of the fields should be the same
     *                                 as defined by the Metabase parser.
     *
     *                                If the default property is meant to be added, removed or changed, there
     *                                 should also be an entry with index ChangedDefault assigned to 1. Similarly,
     *                                 if the notnull constraint is to be added or removed, there should also be
     *                                 an entry with index ChangedNotNull assigned to 1.
     *
     *                                Additionally, there should be an entry named Declaration that is expected
     *                                 to contain the portion of the field changed declaration already in DBMS
     *                                 specific SQL code as it is used in the CREATE TABLE statement.
     *                            Example
     *                                array(
     *                                    'name' => 'userlist',
     *                                    'added_fields' => array(
     *                                        'quota' => array(
     *                                            'type' => 'integer',
     *                                            'unsigned' => 1
     *                                            'declaration' => 'quota INT'
     *                                        )
     *                                    ),
     *                                    'removed_fields' => array(
     *                                        'file_limit' => array(),
     *                                        'time_limit' => array()
     *                                        ),
     *                                    'changed_fields' => array(
     *                                        'gender' => array(
     *                                            'default' => 'M',
     *                                            'change_default' => 1,
     *                                            'declaration' => "gender CHAR(1) DEFAULT 'M'"
     *                                        )
     *                                    ),
     *                                    'renamed_fields' => array(
     *                                        'sex' => array(
     *                                            'name' => 'gender',
     *                                            'declaration' => "gender CHAR(1) DEFAULT 'M'"
     *                                        )
     *                                    )
     *                                )
     *
     * @param boolean $check     indicates whether the function should just check if the DBMS driver
     *                             can perform the requested table alterations if the value is true or
     *                             actually perform them otherwise.
     * @access public
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     */
    function alterTable($name, $changes, $check)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if ($check) {
            foreach ($changes as $change_name => $change) {
                switch ($change_name) {
                case 'added_fields':
                    break;
                case 'removed_fields':
                case 'name':
                case 'renamed_fields':
                case 'changed_fields':
                default:
                    return $db->raiseError(MDB2_ERROR_CANNOT_ALTER, null, null,
                        'alterTable: change type "'.$change_name.'" not yet supported');
                }
            }
            return MDB2_OK;
        } else {
            if (isset($changes[$change = 'removed_fields'])
                || isset($changes[$change = 'name'])
                || isset($changes[$change = 'renamed_fields'])
                || isset($changes[$change = 'changed_fields'])
            ) {
                return $db->raiseError(MDB2_ERROR_CANNOT_ALTER, null, null,
                    'alterTable: change type "'.$change.'" is not supported by the server"');
            }
            $query = '';
            if (isset($changes['added_fields'])) {
                if ($query) {
                    $query.= ', ';
                }
                $query.= 'ADD ';
                $fields = $changes['added_fields'];
                foreach ($fields as $field) {
                    if ($query) {
                        $query.= ', ';
                    }
                    $query.= $field['declaration'];
                }
            }
            return $query ? $db->query("ALTER TABLE $name $query") : MDB2_OK;
        }
    }

    // }}}
    // {{{ listTables()

    /**
     * list all tables in the current database
     *
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listTables()
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $query = 'EXECUTE sp_tables @table_type = "\'TABLE\'"';
        $table_names = $db->queryCol($query, null, 2);
        if (MDB2::isError($table_names)) {
            return $table_names;
        }
        $tables = array();
        for ($i = 0, $j = count($table_names); $i <$j; ++$i) {
            if (!$this->_isSequenceName($db, $table_names[$i])) {
                $tables[] = $table_names[$i];
            }
        }
        return $tables;
    }

    // }}}
    // {{{ listTableFields()

    /**
     * list all fields in a tables in the current database
     *
     * @param string $table name of table that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listTableFields($table)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $result = $db->query("SELECT * FROM $table");
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
    // {{{ listTableIndexes()

    /**
     * list all indexes in a table
     *
     * @param string    $table     name of table that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listTableIndexes($table)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $key_name = 'INDEX_NAME';
        $pk_name = 'PK_NAME';
        if ($db->options['portability'] & MDB2_PORTABILITY_LOWERCASE) {
            $key_name = strtolower($key_name);
            $pk_name = strtolower($pk_name);
        }
        $query = "EXEC sp_statistics @table_name='$table'";
        $indexes_all = $db->query($query, 'text', $key_name);
        if (MDB2::isError($indexes_all)) {
            return $indexes_all;
        }
        $query = "EXEC sp_pkeys @table_name='$table'";
        $pk_all = $db->queryCol($query, 'text', $pk_name);
        $found = $indexes = array();
        for ($index = 0, $j = count($indexes_all); $index < $j; ++$index) {
            if (!in_array($indexes_all[$index], $pk_all)
                && $indexes_all[$index] != null
                && !isset($found[$indexes_all[$index]])
            ) {
                $indexes[] = $indexes_all[$index];
                $found[$indexes_all[$index]] = 1;
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
        $query = "CREATE TABLE $sequence_name (".$db->options['seqname_col_name']
            ." INT NOT NULL IDENTITY($start,1) PRIMARY KEY CLUSTERED)";
        return $db->query($query);
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
        return $db->Query("DROP TABLE $sequence_name");
    }
}
?>