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
// | Author: Lukas Smith <smith@backendmedia.com>                         |
// +----------------------------------------------------------------------+
//
// $Id$
//

require_once 'MDB2/Driver/Manager/Common.php';

/**
 * MDB2 MySQL driver for the management modules
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@backendmedia.com>
 */
class MDB2_Driver_Manager_mysql extends MDB2_Driver_Manager_Common
{
    // {{{ properties
    var $verified_table_types = array();#
    // }}}

    // }}}
    // {{{ _verifyTableType()

    /**
     * verify that chosen transactional table hanlder is available in the database
     *
     * @param string $table_type name of the table handler
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    function _verifyTableType($table_type)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        switch (strtoupper($table_type)) {
        case 'BERKELEYDB':
        case 'BDB':
            $check = array('have_bdb');
            break;
        case 'INNODB':
            $check = array('have_innobase', 'have_innodb');
            break;
        case 'GEMINI':
            $check = array('have_gemini');
            break;
        case 'HEAP':
        case 'ISAM':
        case 'MERGE':
        case 'MRG_MYISAM':
        case 'MYISAM':
        case '':
            return MDB2_OK;
        default:
            return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                $table_type.' is not a supported table type');
        }
        if (isset($this->verified_table_types[$table_type])
            && $this->verified_table_types[$table_type] == $db->connection
        ) {
            return MDB2_OK;
        }
        $not_supported = false;
        for ($i=0, $j=count($check); $i<$j; ++$i) {
            $query = 'SHOW VARIABLES LIKE '.$db->quote($check[$i], 'text');
            $has = $db->queryRow($query, null, MDB2_FETCHMODE_ORDERED);
            if (MDB2::isError($has)) {
                return $has;
            }
            if (is_array($has)) {
                $not_supported = true;
                if ($has[1] == 'YES') {
                    $this->verified_table_types[$table_type] = $db->connection;
                    return MDB2_OK;
                }
            }
        }
        if ($not_supported) {
            return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                $table_type.' is not a supported table type by this MySQL database server');
        }
        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'could not tell if '.$table_type.' is a supported table type');
    }

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
        $query = 'CREATE DATABASE '.$name;
        $result = $db->query($query);
        if (MDB2::isError($result)) {
            return $result;
        }
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
        $query = 'DROP DATABASE '.$name;
        $result = $db->query($query);
        if (MDB2::isError($result)) {
            return $result;
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
        if (!$name) {
            return $db->raiseError(MDB2_ERROR_CANNOT_CREATE, null, null,
                'createTable: no valid table name specified');
        }
        if (!count($fields)) {
            return $db->raiseError(MDB2_ERROR_CANNOT_CREATE, null, null,
                'createTable: no fields specified for table "'.$name.'"');
        }
        if (MDB2::isError($verify = $this->_verifyTableType($db->options['default_table_type']))) {
            return $verify;
        }
        if (MDB2::isError($query_fields = $this->getFieldDeclarationList($fields))) {
            return $db->raiseError(MDB2_ERROR_CANNOT_CREATE, null, null,
                'createTable: unkown error');
        }
        if (isset($db->supported['transactions'])
            && ($db->options['default_table_type'] == 'BDB' || $db->options['default_table_type'] == 'BERKELEYDB')
        ) {
            $query_fields .= ', dummy_primary_key INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (dummy_primary_key)';
        }
        $query = "CREATE TABLE $name ($query_fields)".(strlen($db->options['default_table_type']) ? ' TYPE='.$db->options['default_table_type'] : '');

        return $db->query($query);
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
                case 'removed_fields':
                case 'changed_fields':
                case 'renamed_fields':
                case 'name':
                    break;
                default:
                    return $db->raiseError(MDB2_ERROR_CANNOT_ALTER, null, null,
                        'alterTable: change type "'.$change_name.'" not yet supported');
                }
            }
            return MDB2_OK;
        } else {
            $query = (isset($changes['name']) ? 'RENAME AS '.$changes['name'] : '');
            if (isset($changes['added_fields'])) {
                $fields = $changes['added_fields'];
                foreach ($fields as $field) {
                    if ($query) {
                        $query .= ',';
                    }
                    $query .= 'ADD '.$field['declaration'];
                }
            }
            if (isset($changes['removed_fields'])) {
                $fields = $changes['removed_fields'];
                foreach ($fields as $field_name => $field) {
                    if ($query) {
                        $query .= ',';
                    }
                    $query .= 'DROP '.$field_name;
                }
            }
            $renamed_fields = array();
            if (isset($changes['renamed_fields'])) {
                $fields = $changes['renamed_fields'];
                foreach ($fields as $field_name => $field) {
                    $renamed_fields[$field['name']] = $field_name;
                }
            }
            if (isset($changes['changed_fields'])) {
                $fields = $changes['changed_fields'];
                foreach ($fields as $field_name => $field) {
                    if ($query) {
                        $query .= ',';
                    }
                    if (isset($renamed_fields[$field_name])) {
                        $old_field_name = $renamed_fields[$field_name];
                        unset($renamed_fields[$field_name]);
                    } else {
                        $old_field_name = $field_name;
                    }
                    $query .= "CHANGE $old_field_name ".$field['declaration'];
                }
            }
            if (count($renamed_fields)) {
                foreach ($renamed_fields as $renamed_fields_name => $renamed_field) {
                    if ($query) {
                        $query .= ',';
                    }
                    $old_field_name = $renamed_field;
                    $query .= "CHANGE $old_field_name ".$changes['renamed_fields'][$old_field_name]['declaration'];
                }
            }
            return $db->query("ALTER TABLE $name $query");
        }
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
        $databases = $db->queryCol('SHOW DATABASES');
        return $databases;
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
        $users = $db->queryCol('SELECT DISTINCT USER FROM USER');
        return $users;
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
        $table_names = $db->queryCol('SHOW TABLES');
        if (MDB2::isError($table_names)) {
            return $table_names;
        }
        $tables = array();
        for ($i = 0, $j = count($table_names); $i < $j; ++$i) {
            if (!$this->_isSequenceName($table_names[$i]))
                $tables[] = $table_names[$i];
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_LOWERCASE) {
            $tables = array_flip(array_change_key_case(array_flip($tables), CASE_LOWER));
        }
        return $tables;
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
        $fields = $db->queryCol("SHOW COLUMNS FROM $table");
        if (MDB2::isError($fields)) {
            return $fields;
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_LOWERCASE) {
            $fields = array_flip(array_change_key_case(array_flip($fields), CASE_LOWER));
        }
        if (is_array($fields)) {
            return array_diff($fields, array($db->dummy_primary_key));
        }
        return array();
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
     *                                 function supports() to determine whether the DBMS driver can manage indexes.

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
        $query = "ALTER TABLE $table ADD ".(isset($definition['unique']) ? 'UNIQUE' : 'INDEX')." $name (";
        $skipped_first = false;
        foreach ($definition['fields'] as $field_name => $field) {
            if ($skipped_first) {
                $query .= ',';
            }
            $query .= $field_name;
            $skipped_first = true;
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
        return $db->query("ALTER TABLE $table DROP INDEX $name");
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
        $key_name = 'Key_name';
        if ($db->options['portability'] & MDB2_PORTABILITY_LOWERCASE) {
            $key_name = strtolower($key_name);
        }
        $query = "SHOW INDEX FROM $table";
        $indexes_all = $db->queryCol($query, 'text', $key_name);
        if (MDB2::isError($indexes_all)) {
            return $indexes_all;
        }
        $found = $indexes = array();
        for ($index = 0, $j = count($indexes_all); $index < $j; ++$index) {
            if ($indexes_all[$index] != 'PRIMARY'
                && !isset($found[$indexes_all[$index]])
            ) {
                $indexes[] = $indexes_all[$index];
                $found[$indexes_all[$index]] = true;
            }
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_LOWERCASE) {
            $indexes = array_flip(array_change_key_case(array_flip($indexes), CASE_LOWER));
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
        $result = $this->_verifyTableType($db->options['default_table_type']);
        if (MDB2::isError($result)) {
            return $result;
        }
        $res = $db->query("CREATE TABLE $sequence_name".
            "($seqname_col_name INT NOT NULL AUTO_INCREMENT, PRIMARY KEY ($seqname_col_name))".
            (strlen($db->options['default_table_type']) ? ' TYPE='.$db->options['default_table_type'] : '')
        );
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
        $table_names = $db->queryCol('SHOW TABLES');
        if (MDB2::isError($table_names)) {
            return $table_names;
        }
        $sequences = array();
        for ($i = 0, $j = count($table_names); $i < $j; ++$i) {
            if ($sqn = $this->_isSequenceName($table_names[$i]))
                $sequences[] = $sqn;
        }
        return $sequences;
    }

    // }}}
}
?>
