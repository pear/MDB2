<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2005 Manuel Lemos, Tomas V.V.Cox,                 |
// | Stig. S. Bakken, Lukas Smith, Lorenzo Alberton                       |
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
// | Author: Lorenzo Alberton <l.alberton@quipo.it>                       |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'MDB2/Driver/Manager/Common.php';

/**
 * MDB2 FireBird/InterBase driver for the management modules
 *
 * @package MDB2
 * @category Database
 * @author Lorenzo Alberton <l.alberton@quipo.it>
 */
class MDB2_Driver_Manager_ibase extends MDB2_Driver_Manager_Common
{
    // {{{ createDatabase()

    /**
     * create a new database
     *
     * @param string $name  name of the database that should be created
     * @return mixed        MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createDatabase($name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null, 'Create database',
                'createDatabase: PHP Interbase API does not support direct queries. You have to '.
                'create the db manually by using isql command or a similar program');
    }

    // }}}
    // {{{ dropDatabase()

    /**
     * drop an existing database
     *
     * @param string $name  name of the database that should be dropped
     * @return mixed        MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function dropDatabase($name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null, 'Drop database',
                'dropDatabase: PHP Interbase API does not support direct queries. You have '.
                'to drop the db manually by using isql command or a similar program');
    }

    // }}}
    // {{{ _makeAutoincrement()

    /**
     * add an autoincrement sequence + trigger
     *
     * @param string $name  name of the PK field
     * @param string $table name of the table
     * @param string $start start value for the sequence
     * @return mixed        MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    function _makeAutoincrement($name, $table, $start = null)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (is_null($start)) {
            $db->beginTransaction();
            $query = 'SELECT MAX(' . $db->quoteIdentifier($name) . ') FROM ' . $db->quoteIdentifier($table);
            $start = $this->db->queryOne($query, 'integer');
            if (PEAR::isError($start)) {
                return $start;
            }
            ++$start;
            $result = $db->manager->createSequence($table, $start);
            $db->commit();
        } else {
            $result = $db->manager->createSequence($table, $start);
        }
        if (PEAR::isError($result)) {
            return $db->raiseError(MDB2_ERROR, null, null,
                '_makeAutoincrement: sequence for autoincrement PK could not be created');
        }

        $table = strtoupper($table);
        $sequence_name = strtoupper($db->getSequenceName($table));
        $trigger_name  = $table . '_AUTOINCREMENT_PK';
        $trigger_name = $db->quoteIdentifier($trigger_name);
        $table = $db->quoteIdentifier($table);
        $name = $db->quoteIdentifier($name);
        $trigger_sql = 'CREATE TRIGGER ' . $trigger_name . ' FOR ' . $table . '
                        ACTIVE BEFORE INSERT POSITION 0
                        AS
                        BEGIN
                        IF (NEW.' . $name . ' IS NULL) THEN
                            NEW.' . $name . ' = GEN_ID('.$sequence_name.', 1);
                        END';

        return $db->query($trigger_sql);
    }

    // }}}
    // {{{ _dropAutoincrement()

    /**
     * drop an existing autoincrement sequence + trigger
     *
     * @param string $table name of the table
     * @return mixed        MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    function _dropAutoincrement($table)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        $result = $db->manager->dropSequence($table);
        if (PEAR::isError($result)) {
            return $db->raiseError(MDB2_ERROR, null, null,
                '_dropAutoincrement: sequence for autoincrement PK could not be dropped');
        }
        //remove autoincrement trigger associated with the table
        $table = strtoupper($table);
        $trigger_name  = $table . '_AUTOINCREMENT_PK';
        $result = $db->query("DELETE FROM RDB\$TRIGGERS WHERE UPPER(RDB\$RELATION_NAME)='$table' AND UPPER(RDB\$TRIGGER_NAME)='$trigger_name'");
        if (PEAR::isError($result)) {
            return $db->raiseError(MDB2_ERROR, null, null,
                '_dropAutoincrement: trigger for autoincrement PK could not be dropped');
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
     *                                'unsigned' => 1,
     *                                'notnull' => 1,
     *                                'default' => 0,
     *                            ),
     *                            'name' => array(
     *                                'type' => 'text',
     *                                'length' => 12,
     *                            ),
     *                            'description' => array(
     *                                'type' => 'text',
     *                                'length' => 12,
     *                            )
     *                        );
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createTable($name, $fields)
    {
        $name = strtoupper($name);
        $result = parent::createTable($name, $fields);
        if (PEAR::isError($result)) {
            return $result;
        }
        foreach($fields as $field_name => $field) {
            if (array_key_exists('autoincrement', $field) && $field['autoincrement']) {
                return $this->_makeAutoincrement($field_name, $name, 1);
            }
        }
    }

    // }}}
    // {{{ checkSupportedChanges()

    /**
     * check if planned changes are supported
     *
     * @param string $name name of the database that should be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function checkSupportedChanges(&$changes)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        foreach ($changes as $change_name => $change) {
            switch ($change_name) {
            case 'notnull':
                return $db->raiseError(MDB2_ERROR, null, null,
                    'checkSupportedChanges: it is not supported changes to field not null constraint');
            case 'default':
                return $db->raiseError(MDB2_ERROR, null, null,
                    'checkSupportedChanges: it is not supported changes to field default value');
            case 'length':
                /*
                return $db->raiseError(MDB2_ERROR, null, null,
                    'checkSupportedChanges: it is not supported changes to field default length');
                */
            case 'unsigned':
            case 'type':
            case 'declaration':
            case 'definition':
                break;
            default:
                return $db->raiseError(MDB2_ERROR, null, null,
                    'checkSupportedChanges: it is not supported change of type' . $change_name);
            }
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ dropTable()

    /**
     * drop an existing table
     *
     * @param string $name name of the table that should be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function dropTable($name)
    {
        $name = strtoupper($name);
        $result = $this->_dropAutoincrement($name);
        if (PEAR::isError($result)) {
            return $result;
        }

        return parent::dropTable($name);
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
     *                            add
     *
     *                                Associative array with the names of fields to be added as
     *                                 indexes of the array. The value of each entry of the array
     *                                 should be set to another associative array with the properties
     *                                 of the fields to be added. The properties of the fields should
     *                                 be the same as defined by the Metabase parser.
     *
     *
     *                            remove
     *
     *                                Associative array with the names of fields to be removed as indexes
     *                                 of the array. Currently the values assigned to each entry are ignored.
     *                                 An empty array should be used for future compatibility.
     *
     *                            rename
     *
     *                                Associative array with the names of fields to be renamed as indexes
     *                                 of the array. The value of each entry of the array should be set to
     *                                 another associative array with the entry named name with the new
     *                                 field name and the entry named Declaration that is expected to contain
     *                                 the portion of the field declaration already in DBMS specific SQL code
     *                                 as it is used in the CREATE TABLE statement.
     *
     *                            change
     *
     *                                Associative array with the names of the fields to be changed as indexes
     *                                 of the array. Keep in mind that if it is intended to change either the
     *                                 name of a field and any other properties, the change array entries
     *                                 should have the new names of the fields as array indexes.
     *
     *                                The value of each entry of the array should be set to another associative
     *                                 array with the properties of the fields to that are meant to be changed as
     *                                 array entries. These entries should be assigned to the new values of the
     *                                 respective properties. The properties of the fields should be the same
     *                                 as defined by the Metabase parser.
     *
     *                            Example
     *                                array(
     *                                    'name' => 'userlist',
     *                                    'add' => array(
     *                                        'quota' => array(
     *                                            'type' => 'integer',
     *                                            'unsigned' => 1
     *                                        )
     *                                    ),
     *                                    'remove' => array(
     *                                        'file_limit' => array(),
     *                                        'time_limit' => array()
     *                                    ),
     *                                    'change' => array(
     *                                        'name' => array(
     *                                            'length' => '20',
     *                                            'definition' => array(
     *                                                'type' => 'text',
     *                                                'length' => 20,
     *                                            ),
     *                                        )
     *                                    ),
     *                                    'rename' => array(
     *                                        'sex' => array(
     *                                            'name' => 'gender',
     *                                            'definition' => array(
     *                                                'type' => 'text',
     *                                                'length' => 1,
     *                                                'default' => 'M',
     *                                            ),
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        foreach ($changes as $change_name => $change) {
            switch ($change_name) {
            case 'add':
            case 'remove':
            case 'rename':
                break;
            case 'change':
                foreach ($changes['change'] as $field) {
                    if (PEAR::isError($err = $this->checkSupportedChanges($field))) {
                        return $err;
                    }
                }
                break;
            default:
                return $db->raiseError(MDB2_ERROR, null, null,
                    'alterTable: change type ' . $change_name . ' not yet supported');
            }
        }
        if ($check) {
            return MDB2_OK;
        }
        $query = '';
        if (array_key_exists('add', $changes)) {
            foreach ($changes['add'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $query.= 'ADD ' . $db->getDeclaration($field['type'], $field_name, $field, $name);
            }
        }

        if (array_key_exists('remove', $changes)) {
            foreach ($changes['remove'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $field_name = $db->quoteIdentifier($field_name);
                $query.= 'DROP ' . $field_name;
            }
        }

        if (array_key_exists('rename', $changes)) {
            foreach ($changes['rename'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $field_name = $db->quoteIdentifier($field_name);
                $query.= 'ALTER ' . $field_name . ' TO ' . $db->quoteIdentifier($field['name']);
            }
        }

        if (array_key_exists('change', $changes)) {
            // missing support to change DEFAULT and NULLability
            foreach ($changes['change'] as $field_name => $field) {
                if (PEAR::isError($err = $this->checkSupportedChanges($field))) {
                    return $err;
                }
                if ($query) {
                    $query.= ', ';
                }
                $db->loadModule('Datatype');
                $field_name = $db->quoteIdentifier($field_name);
                $query.= 'ALTER ' . $field_name.' TYPE ' . $db->datatype->getTypeDeclaration($field['definition']);
            }
        }

        if (!strlen($query)) {
            return MDB2_OK;
        }

        $name = $db->quoteIdentifier(strtoupper($name));
        return $db->query("ALTER TABLE $name $query");
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        $query = 'SELECT DISTINCT R.RDB$RELATION_NAME FROM RDB$RELATION_FIELDS R WHERE R.RDB$SYSTEM_FLAG=0';
        $result = $db->queryCol($query);
        if (PEAR::isError($result)) {
            return $result;
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        $table = strtoupper($table);
        $query = "SELECT RDB\$FIELD_NAME FROM RDB\$RELATION_FIELDS WHERE UPPER(RDB\$RELATION_NAME)='$table'";
        $result = $db->queryCol($query);
        if (PEAR::isError($result)) {
            return $result;
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        return $db->queryCol('SELECT DISTINCT RDB$USER FROM RDB$USER_PRIVILEGES');
    }

    // }}}
    // {{{ listViews()

    /**
     * list the views in the database
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function listViews()
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $result = $db->queryCol('SELECT RDB$VIEW_NAME');
        if (PEAR::isError($result)) {
            return $result;
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        $query = 'CREATE';
        if (array_key_exists('unique', $definition) && $definition['unique']) {
            $query.= ' UNIQUE';
        }
        $query_sort = '';
        foreach ($definition['fields'] as $field) {
            if (!strcmp($query_sort, '') && isset($field['sorting'])) {
                switch ($field['sorting']) {
                case 'ascending':
                    $query_sort = ' ASC';
                    break;
                case 'descending':
                    $query_sort = ' DESC';
                    break;
                }
            }
        }
        $table = $db->quoteIdentifier(strtoupper($table));
        $name = $db->quoteIdentifier(strtoupper($name));
        $query .= $query_sort. " INDEX $name ON $table";
        $fields = array();
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $db->quoteIdentifier($field);
        }
        $query .= ' ('.implode(', ', $fields) . ')';
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
        return parent::dropIndex(strtoupper($table), strtoupper($name));
    }

    // }}}
    // {{{ listTableIndexes()

    /**
     * list all indexes in a table
     *
     * @param string $table name of table that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function listTableIndexes($table)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        $table = strtoupper($table);
        $query = "SELECT RDB\$INDEX_NAME FROM RDB\$INDICES WHERE RDB\$RELATION_NAME='$table'";
        $result = $db->queryCol($query);
        if (PEAR::isError($result)) {
            return $result;
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }

    // }}}
    // {{{ createConstraint()

    /**
     * create a constraint on a table
     *
     * @param string    $table      name of the table on which the constraint is to be created
     * @param string    $name       name of the constraint to be created
     * @param array     $definition associative array that defines properties of the constraint to be created.
     *                              Currently, only one property named FIELDS is supported. This property
     *                              is also an associative with the names of the constraint fields as array
     *                              constraints. Each entry of this array is set to another type of associative
     *                              array that specifies properties of the constraint that are specific to
     *                              each field.
     *
     *                              Example
     *                                  array(
     *                                      'fields' => array(
     *                                          'user_name' => array(),
     *                                          'last_login' => array(),
     *                                      )
     *                                  )
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createConstraint($table, $name, $definition)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        $table = $db->quoteIdentifier(strtoupper($table));
        $name = $db->quoteIdentifier($name);
        $query = "ALTER TABLE $table ADD CONSTRAINT $name";
        if (array_key_exists('primary', $definition) && $definition['primary']) {
            $query.= ' PRIMARY KEY';
        }
        $fields = array();
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $db->quoteIdentifier($field);
        }
        $query .= ' ('. implode(', ', $fields) . ')';
        return $db->query($query);
    }

    // }}}
    // {{{ dropConstraint()

    /**
     * drop existing constraint
     *
     * @param string    $table         name of table that should be used in method
     * @param string    $name         name of the constraint to be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function dropConstraint($table, $name)
    {
        return parent::dropConstraint(strtoupper($table), $name);
    }

    // }}}
    // {{{ listTableConstraints()

    /**
     * list all sonstraints in a table
     *
     * @param string    $table      name of table that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function listTableConstraints($table)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        $table = strtoupper($table);
        $query = "SELECT RDB\$CONSTRAINT_NAME AS constraint_name,
                         RDB\$CONSTRAINT_TYPE AS constraint_type,
                         RDB\$INDEX_NAME AS index_name
                    FROM RDB\$RELATION_CONSTRAINTS
                   WHERE RDB\$RELATION_NAME='$table'";
        $result = $db->queryCol($query);
        if (PEAR::isError($result)) {
            return $result;
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }

    // }}}
    // {{{ createSequence()

    /**
     * create sequence
     *
     * @param string $seq_name name of the sequence to be created
     * @param string $start start value of the sequence; default is 1
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createSequence($seq_name, $start = 1)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $sequence_name = strtoupper($db->getSequenceName($seq_name));
        if (PEAR::isError($result = $db->query('CREATE GENERATOR '.$sequence_name))) {
            return $result;
        }
        if (PEAR::isError($result = $db->query('SET GENERATOR '.$sequence_name.' TO '.($start-1)))) {
            if (PEAR::isError($err = $db->dropSequence($seq_name))) {
                return $db->raiseError(MDB2_ERROR, null, null,
                    'createSequence: Could not setup sequence start value and then it was not possible to drop it: '.
                    $err->getMessage().' - ' .$err->getUserInfo());
            }
        }
        return $result;
    }

    // }}}
    // {{{ dropSequence()

    /**
     * drop existing sequence
     *
     * @param string $seq_name name of the sequence to be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function dropSequence($seq_name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $sequence_name = strtoupper($db->getSequenceName($seq_name));
        $query = "DELETE FROM RDB\$GENERATORS WHERE UPPER(RDB\$GENERATOR_NAME)='$sequence_name'";
        return $db->query($query);
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = 'SELECT RDB$GENERATOR_NAME FROM RDB$GENERATORS WHERE RDB$SYSTEM_FLAG IS NULL';
        $table_names = $db->queryCol($query);
        if (PEAR::isError($table_names)) {
            return $table_names;
        }
        $result = array();
        foreach ($table_names as $table_name) {
            if ($sqn = $this->_isSequenceName($table_name)) {
                $result[] = $sqn;
            }
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }
}
?>