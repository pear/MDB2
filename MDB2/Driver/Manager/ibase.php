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
 * @author  Lorenzo Alberton <l.alberton@quipo.it>
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
     **/
    function createDatabase($name)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
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
     **/
    function dropDatabase($name)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null, 'Drop database',
                'dropDatabase: PHP Interbase API does not support direct queries. You have '.
                'to drop the db manually by using isql command or a similar program');
    }

    // }}}
    // {{{ checkSupportedChanges()

    /**
     * check if planned changes are supported
     *
     * @param string $name name of the database that should be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     **/
    function checkSupportedChanges(&$changes)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        foreach ($changes as $change_name => $change) {
            switch ($change_name) {
            case 'changed_not_null':
            case 'notnull':
                return $this->raiseError(MDB2_ERROR_MANAGER, '', '',
                    'checkSupportedChanges: it is not supported changes to field not null constraint');
            case 'ChangedDefault':
            case 'default':
                return $this->raiseError(MDB2_ERROR_MANAGER, '', '',
                    'checkSupportedChanges: it is not supported changes to field default value');
            case 'length':
                return $this->raiseError(MDB2_ERROR_MANAGER, '', '',
                    'checkSupportedChanges: it is not supported changes to field default length');
            case 'unsigned':
            case 'type':
            case 'declaration':
            case 'definition':
                break;
            default:
                return $this->raiseError(MDB2_ERROR_MANAGER, '', '',
                    'checkSupportedChanges: it is not supported change of type' . $change_name);
            }
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ alterTable()

    /**
     * alter an existing table
     *
     * @param string $name name of the table that is intended to be changed.
     * @param array $changes associative array that contains the details of each type
     *                              of change that is intended to be performed. The types of
     *                              changes that are currently supported are defined as follows:
     *
     *                              name
     *
     *                                 New name for the table.
     *
     *                             added_fields
     *
     *                                 Associative array with the names of fields to be added as
     *                                  indexes of the array. The value of each entry of the array
     *                                  should be set to another associative array with the properties
     *                                  of the fields to be added. The properties of the fields should
     *                                  be the same as defined by the Metabase parser.
     *
     *                                 Additionally, there should be an entry named Declaration that
     *                                  is expected to contain the portion of the field declaration already
     *                                  in DBMS specific SQL code as it is used in the CREATE TABLE statement.
     *
     *                             removed_fields
     *
     *                                 Associative array with the names of fields to be removed as indexes
     *                                  of the array. Currently the values assigned to each entry are ignored.
     *                                  An empty array should be used for future compatibility.
     *
     *                             renamed_fields
     *
     *                                 Associative array with the names of fields to be renamed as indexes
     *                                  of the array. The value of each entry of the array should be set to
     *                                  another associative array with the entry named name with the new
     *                                  field name and the entry named Declaration that is expected to contain
     *                                  the portion of the field declaration already in DBMS specific SQL code
     *                                  as it is used in the CREATE TABLE statement.
     *
     *                             changed_fields
     *
     *                                 Associative array with the names of the fields to be changed as indexes
     *                                  of the array. Keep in mind that if it is intended to change either the
     *                                  name of a field and any other properties, the changed_fields array entries
     *                                  should have the new names of the fields as array indexes.
     *
     *                                 The value of each entry of the array should be set to another associative
     *                                  array with the properties of the fields to that are meant to be changed as
     *                                  array entries. These entries should be assigned to the new values of the
     *                                  respective properties. The properties of the fields should be the same
     *                                  as defined by the Metabase parser.
     *
     *                                 If the default property is meant to be added, removed or changed, there
     *                                  should also be an entry with index ChangedDefault assigned to 1. Similarly,
     *                                  if the notnull constraint is to be added or removed, there should also be
     *                                  an entry with index ChangedNotNull assigned to 1.
     *
     *                                 Additionally, there should be an entry named Declaration that is expected
     *                                  to contain the portion of the field changed declaration already in DBMS
     *                                  specific SQL code as it is used in the CREATE TABLE statement.
     *                             Example
     *                                 array(
     *                                     'name' => 'userlist',
     *                                     'added_fields' => array(
     *                                         'quota' => array(
     *                                             'type' => 'integer',
     *                                             'unsigned' => 1
     *                                             'declaration' => 'quota INT'
     *                                         )
     *                                     ),
     *                                     'removed_fields' => array(
     *                                         'file_limit' => array(),
     *                                         'time_limit' => array()
     *                                         ),
     *                                     'changed_fields' => array(
     *                                         'gender' => array(
     *                                             'default' => 'M',
     *                                             'change_default' => 1,
     *                                             'declaration' => "gender CHAR(1) DEFAULT 'M'"
     *                                         )
     *                                     ),
     *                                     'renamed_fields' => array(
     *                                         'sex' => array(
     *                                             'name' => 'gender',
     *                                             'declaration' => "gender CHAR(1) DEFAULT 'M'"
     *                                         )
     *                                     )
     *                                 )
     * @param boolean $check indicates whether the function should just check if the DBMS driver
     *                              can perform the requested table alterations if the value is true or
     *                              actually perform them otherwise.
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     **/
    function alterTable($name, &$changes, $check)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if ($check) {
            foreach ($changes as $change_name => $change) {
                switch ($change_name) {
                case 'added_fields':
                case 'removed_fields':
                case 'renamed_fields':
                    break;
                case 'changed_fields':
                    $fields = $changes['changed_fields'];
                    foreach ($fields as $field) {
                        if (MDB2::isError($err = $this->checkSupportedChanges($field))) {
                            return $err;
                        }
                    }
                    break;
                default:
                    return $this->raiseError(MDB2_ERROR_MANAGER, '', '',
                        'alterTable: change type ' . $change_name . ' not yet supported');
                }
            }
            return MDB2_OK;
        } else {
            $query = '';
            if (isset($changes['added_fields'])) {
                $fields = $changes['added_fields'];
                foreach ($fields as $field_name => $field) {
                    if ($query) {
                        $query .= ', ';
                    }
                    $query .= 'ADD ' . $field['declaration'];
                }
            }
            if (isset($changes['removed_fields'])) {
                $fields = $changes['removed_fields'];
                foreach ($fields as $field_name => $field) {
                    if ($query) {
                        $query .= ', ';
                    }
                    $query .= 'DROP ' . $field_name;
                }
            }
            if (isset($changes['renamed_fields'])) {
                $fields = $changes['renamed_fields'];
                foreach ($fields as $field_name => $field) {
                    if ($query) {
                        $query .= ', ';
                    }
                    $query .= 'ALTER ' . $field_name . ' TO ' . $field['name'];
                }
            }
            if (isset($changes['changed_fields'])) {
                $fields = $changes['changed_fields'];
                foreach ($fields as $field_name => $field) {
                    if (MDB2::isError($err = $this->checkSupportedChanges($field))) {
                        return $err;
                    }
                    if ($query) {
                        $query .= ', ';
                    }
                    $db->loadModule('datatype');
                    $query .= 'ALTER '.$field_name.' TYPE '.$db->datatype->getTypeDeclaration($field['definition']);
                }
            }
            if (MDB2::isError($err = $db->query("ALTER TABLE $name $query"))) {
                return $err;
            }
            return MDB2_OK;
        }
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
        $query = 'SELECT RDB$FIELD_SOURCE FROM RDB$RELATION_FIELDS WHERE RDB$RELATION_NAME=\'$table\'';
        $result = $db->query($query, null, false);
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
    // {{{ listViews()

    /**
     * list the views in the database
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     **/
    function listViews()
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return $db->queryCol('SELECT RDB$VIEW_NAME');
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
        $query_sort = $query_fields = '';
        foreach ($definition['fields'] as $field_name => $field) {
            if ($query_fields) {
                $query_fields .= ',';
            }
            $query_fields .= $field_name;
            if ($query_sort && $db->support('index_sorting')
                && isset($definition['fields'][$field_name]['sorting'])
            ) {
                switch ($definition['fields'][$field_name]['sorting']) {
                case 'ascending':
                    $query_sort = ' ASC';
                    break;
                case 'descending':
                    $query_sort = ' DESC';
                    break;
                }
            }
        }
        return $db->query('CREATE'.(isset($definition['unique']) ? ' UNIQUE' : '') . $query_sort.
             " INDEX $name  ON $table ($query_fields)");
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
     **/
    function createSequence($seq_name, $start = 1)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $seq_name = strtoupper($seq_name);
        if (MDB2::isError($result = $db->query('CREATE GENERATOR '.$seq_name))) {
            return $result;
        }
        if (MDB2::isError($result = $db->query('SET GENERATOR '.$seq_name.' TO '.($start-1)))) {
            if (MDB2::isError($err = $db->dropSequence($seq_name))) {
                return $this->raiseError(MDB2_ERROR_MANAGER, null, null,
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
     **/
    function dropSequence($seq_name)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return $db->query('DELETE FROM RDB$GENERATORS WHERE RDB$GENERATOR_NAME=\''.strtoupper($seq_name).'\'');
    }

    // }}}
    // {{{ listSequences()

    /**
     * list all sequences in the current database
     *
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     **/
    function listSequences()
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return $db->queryCol('SELECT RDB$GENERATOR_NAME FROM RDB$GENERATORS');
    }
}
?>