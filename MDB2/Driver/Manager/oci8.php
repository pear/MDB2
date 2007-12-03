<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2007 Manuel Lemos, Tomas V.V.Cox,                 |
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
// | Author: Lukas Smith <smith@pooteeweet.org>                           |
// +----------------------------------------------------------------------+

// $Id$

require_once 'MDB2/Driver/Manager/Common.php';

/**
 * MDB2 oci8 driver for the management modules
 *
 * @package MDB2
 * @category Database
 * @author Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Driver_Manager_oci8 extends MDB2_Driver_Manager_Common
{
    // {{{ createDatabase()

    /**
     * create a new database
     *
     * @param string $name    name of the database that should be created
     * @param array  $options array with charset, collation info
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createDatabase($name, $options = array())
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (!$db->options['emulate_database']) {
            return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'database creation is only supported if the "emulate_database" option is enabled', __FUNCTION__);
        }

        $username = $db->options['database_name_prefix'].$name;
        $password = $db->dsn['password'] ? $db->dsn['password'] : $name;
        $tablespace = $db->options['default_tablespace']
            ? ' DEFAULT TABLESPACE '.$db->options['default_tablespace'] : '';

        $query = 'CREATE USER '.$username.' IDENTIFIED BY '.$password.$tablespace;
        $result = $db->standaloneQuery($query, null, true);
        if (PEAR::isError($result)) {
            return $result;
        }
        $query = 'GRANT CREATE SESSION, CREATE TABLE, UNLIMITED TABLESPACE, CREATE SEQUENCE, CREATE TRIGGER TO '.$username;
        $result = $db->standaloneQuery($query, null, true);
        if (PEAR::isError($result)) {
            $query = 'DROP USER '.$username.' CASCADE';
            $result2 = $db->standaloneQuery($query, null, true);
            if (PEAR::isError($result2)) {
                return $db->raiseError($result2, null, null,
                    'could not setup the database user', __FUNCTION__);
            }
            return $result;
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ dropDatabase()

    /**
     * drop an existing database
     *
     * @param object $db database object that is extended by this class
     * @param string $name name of the database that should be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function dropDatabase($name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (!$db->options['emulate_database']) {
            return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'database dropping is only supported if the "emulate_database" option is enabled', __FUNCTION__);
        }

        $username = $db->options['database_name_prefix'].$name;
        return $db->standaloneQuery('DROP USER '.$username.' CASCADE', null, true);
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
    function _makeAutoincrement($name, $table, $start = 1)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $table = strtoupper($table);
        $index_name  = $table . '_AI_PK';
        $definition = array(
            'primary' => true,
            'fields' => array($name => true),
        );
        $result = $this->createConstraint($table, $index_name, $definition);
        if (PEAR::isError($result)) {
            return $db->raiseError($result, null, null,
                'primary key for autoincrement PK could not be created', __FUNCTION__);
        }

        if (is_null($start)) {
            $db->beginTransaction();
            $query = 'SELECT MAX(' . $db->quoteIdentifier($name, true) . ') FROM ' . $db->quoteIdentifier($table, true);
            $start = $this->db->queryOne($query, 'integer');
            if (PEAR::isError($start)) {
                return $start;
            }
            ++$start;
            $result = $this->createSequence($table, $start);
            $db->commit();
        } else {
            $result = $this->createSequence($table, $start);
        }
        if (PEAR::isError($result)) {
            return $db->raiseError($result, null, null,
                'sequence for autoincrement PK could not be created', __FUNCTION__);
        }
        $sequence_name         = $db->getSequenceName($table);
        $trigger_name          = $db->quoteIdentifier($table . '_AI_PK', true);
        $sequence_name_quoted  = $db->quoteIdentifier($sequence_name, true);
        $table = $db->quoteIdentifier($table, true);
        $name  = $db->quoteIdentifier($name, true);
        $trigger_sql = '
CREATE TRIGGER '.$trigger_name.'
   BEFORE INSERT
   ON '.$table.'
   FOR EACH ROW
DECLARE
   last_Sequence NUMBER;
   last_InsertID NUMBER;
BEGIN
   SELECT '.$sequence_name_quoted.'.NEXTVAL INTO :NEW.'.$name.' FROM DUAL;
   IF (:NEW.'.$name.' IS NULL OR :NEW.'.$name.' = 0) THEN
      SELECT '.$sequence_name_quoted.'.NEXTVAL INTO :NEW.'.$name.' FROM DUAL;
   ELSE
      SELECT NVL(Last_Number, 0) INTO last_Sequence
        FROM User_Sequences
       WHERE UPPER(Sequence_Name) = UPPER(\''.$sequence_name.'\');
      SELECT :NEW.'.$name.' INTO last_InsertID FROM DUAL;
      WHILE (last_InsertID > last_Sequence) LOOP
         SELECT '.$sequence_name_quoted.'.NEXTVAL INTO last_Sequence FROM DUAL;
      END LOOP;
   END IF;
END;
';
        $result = $db->exec($trigger_sql);
        if (PEAR::isError($result)) {
            return $result;
        }
        return MDB2_OK;
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

        $table = strtoupper($table);
        $trigger_name = $table . '_AI_PK';
        $trigger_name_quoted = $db->quote($trigger_name, 'text');
        $query = 'SELECT trigger_name FROM user_triggers';
        $query.= ' WHERE trigger_name='.$trigger_name_quoted.' OR trigger_name='.strtoupper($trigger_name_quoted);
        $trigger = $db->queryOne($query);
        if (PEAR::isError($trigger)) {
            return $trigger;
        }

        if ($trigger) {
            $trigger_name  = $db->quoteIdentifier($table . '_AI_PK', true);
            $trigger_sql = 'DROP TRIGGER ' . $trigger_name;
            $result = $db->exec($trigger_sql);
            if (PEAR::isError($result)) {
                return $db->raiseError($result, null, null,
                    'trigger for autoincrement PK could not be dropped', __FUNCTION__);
            }

            $result = $this->dropSequence($table);
            if (PEAR::isError($result)) {
                return $db->raiseError($result, null, null,
                    'sequence for autoincrement PK could not be dropped', __FUNCTION__);
            }

            $index_name = $table . '_AI_PK';
            $result = $this->dropConstraint($table, $index_name);
            if (PEAR::isError($result)) {
                return $db->raiseError($result, null, null,
                    'primary key for autoincrement PK could not be dropped', __FUNCTION__);
            }
        }

        return MDB2_OK;
    }

    // }}}
    // {{{ _getTemporaryTableQuery()

    /**
     * A method to return the required SQL string that fits between CREATE ... TABLE
     * to create the table as a temporary table.
     *
     * @return string The string required to be placed between "CREATE" and "TABLE"
     *                to generate a temporary table, if possible.
     */
    function _getTemporaryTableQuery()
    {
        return 'GLOBAL TEMPORARY';
    }

    // }}}
    // {{{ _getAdvancedFKOptions()

    /**
     * Return the FOREIGN KEY query section dealing with non-standard options
     * as MATCH, INITIALLY DEFERRED, ON UPDATE, ...
     *
     * @param array $definition
     * @return string
     * @access protected
     */
    function _getAdvancedFKOptions($definition)
    {
        $query = '';
        if (!empty($definition['ondelete']) && (strtoupper($definition['ondelete']) != 'NO ACTION')) {
            $query .= ' ON DELETE '.$definition['ondelete'];
        }
        if (!empty($definition['deferrable'])) {
            $query .= ' DEFERRABLE';
        } else {
            $query .= ' NOT DEFERRABLE';
        }
        if (!empty($definition['initiallydeferred'])) {
            $query .= ' INITIALLY DEFERRED';
        } else {
            $query .= ' INITIALLY IMMEDIATE';
        }
        return $query;
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
     * @param array $options  An associative array of table options:
     *                          array(
     *                              'comment' => 'Foo',
     *                              'temporary' => true|false,
     *                          );
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createTable($name, $fields, $options = array())
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        $db->beginNestedTransaction();
        $result = parent::createTable($name, $fields, $options);
        if (!PEAR::isError($result)) {
            foreach ($fields as $field_name => $field) {
                if (!empty($field['autoincrement'])) {
                    $result = $this->_makeAutoincrement($field_name, $name);
                }
            }
        }
        $db->completeNestedTransaction();
        return $result;
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        $db->beginNestedTransaction();
        $result = $this->_dropAutoincrement($name);
        if (!PEAR::isError($result)) {
            $result = parent::dropTable($name);
        }
        $db->completeNestedTransaction();
        return $result;
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
     *                                 be the same as defined by the MDB2 parser.
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
     *                                 as defined by the MDB2 parser.
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
            case 'change':
            case 'name':
            case 'rename':
                break;
            default:
                return $db->raiseError(MDB2_ERROR_CANNOT_ALTER, null, null,
                    'change type "'.$change_name.'" not yet supported', __FUNCTION__);
            }
        }

        if ($check) {
            return MDB2_OK;
        }

        $name = $db->quoteIdentifier($name, true);

        if (!empty($changes['add']) && is_array($changes['add'])) {
            $fields = array();
            foreach ($changes['add'] as $field_name => $field) {
                $fields[] = $db->getDeclaration($field['type'], $field_name, $field);
            }
            $result = $db->exec("ALTER TABLE $name ADD (". implode(', ', $fields).')');
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        if (!empty($changes['change']) && is_array($changes['change'])) {
            $fields = array();
            foreach ($changes['change'] as $field_name => $field) {
                $fields[] = $field_name. ' ' . $db->getDeclaration($field['definition']['type'], '', $field['definition']);
            }
            $result = $db->exec("ALTER TABLE $name MODIFY (". implode(', ', $fields).')');
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        if (!empty($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $field_name => $field) {
                $field_name = $db->quoteIdentifier($field_name, true);
                $query = "ALTER TABLE $name RENAME COLUMN $field_name TO ".$db->quoteIdentifier($field['name']);
                $result = $db->exec($query);
                if (PEAR::isError($result)) {
                    return $result;
                }
            }
        }

        if (!empty($changes['remove']) && is_array($changes['remove'])) {
            $fields = array();
            foreach ($changes['remove'] as $field_name => $field) {
                $fields[] = $db->quoteIdentifier($field_name, true);
            }
            $result = $db->exec("ALTER TABLE $name DROP COLUMN ". implode(', ', $fields));
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        if (!empty($changes['name'])) {
            $change_name = $db->quoteIdentifier($changes['name'], true);
            $result = $db->exec("ALTER TABLE $name RENAME TO ".$change_name);
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        return MDB2_OK;
    }

    // }}}
    // {{{ _fetchCol()

    /**
     * Utility method to fetch and format a column from a resultset
     *
     * @param resource $result
     * @param boolean $fixname (used when listing indices or constraints)
     * @return mixed array of names on success, a MDB2 error on failure
     * @access private
     */
    function _fetchCol($result, $fixname = false)
    {
        if (PEAR::isError($result)) {
            return $result;
        }
        $col = $result->fetchCol();
        if (PEAR::isError($col)) {
            return $col;
        }
        $result->free();
        
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        
        if ($fixname) {
            foreach ($col as $k => $v) {
                $col[$k] = $this->_fixIndexName($v);
            }
        }
        
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE
            && $db->options['field_case'] == CASE_LOWER
        ) {
            $col = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $col);
        }
        return $col;
    }

    // }}}
    // {{{ listDatabases()

    /**
     * list all databases
     *
     * @return mixed array of database names on success, a MDB2 error on failure
     * @access public
     */
    function listDatabases()
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (!$db->options['emulate_database']) {
            return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'database listing is only supported if the "emulate_database" option is enabled', __FUNCTION__);
        }

        if ($db->options['database_name_prefix']) {
            $query = 'SELECT SUBSTR(username, ';
            $query.= (strlen($db->options['database_name_prefix'])+1);
            $query.= ") FROM sys.dba_users WHERE username LIKE '";
            $query.= $db->options['database_name_prefix']."%'";
        } else {
            $query = 'SELECT username FROM sys.dba_users';
        }
        $result = $db->standaloneQuery($query, array('text'), false);
        return $this->_fetchCol($result);
    }

    // }}}
    // {{{ listUsers()

    /**
     * list all users
     *
     * @return mixed array of user names on success, a MDB2 error on failure
     * @access public
     */
    function listUsers()
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if ($db->options['emulate_database'] && $db->options['database_name_prefix']) {
            $query = 'SELECT SUBSTR(username, ';
            $query.= (strlen($db->options['database_name_prefix'])+1);
            $query.= ") FROM sys.dba_users WHERE username NOT LIKE '";
            $query.= $db->options['database_name_prefix']."%'";
        } else {
            $query = 'SELECT username FROM sys.dba_users';
        }
        return $db->queryCol($query);
    }

    // }}}
    // {{{ listViews()

    /**
     * list all views in the current database
     *
     * @param string owner, the current is default
     * @return mixed array of view names on success, a MDB2 error on failure
     * @access public
     */
    function listViews($owner = null)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        
        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }

        $query = 'SELECT view_name
                    FROM sys.all_views
                   WHERE owner=? OR owner=?';
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $result = $stmt->execute(array($owner, strtoupper($owner)));
        return $this->_fetchCol($result);
    }

    // }}}
    // {{{ listFunctions()

    /**
     * list all functions in the current database
     *
     * @param string owner, the current is default
     * @return mixed array of function names on success, a MDB2 error on failure
     * @access public
     */
    function listFunctions($owner = null)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }

        $query = "SELECT name
                    FROM sys.all_source
                   WHERE line = 1
                     AND type = 'FUNCTION'
                     AND (owner=? OR owner=?)";
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $result = $stmt->execute(array($owner, strtoupper($owner)));
        return $this->_fetchCol($result);
    }

    // }}}
    // {{{ listTableTriggers()

    /**
     * list all triggers in the database that reference a given table
     *
     * @param string table for which all referenced triggers should be found
     * @return mixed array of trigger names on success, a MDB2 error on failure
     * @access public
     */
    function listTableTriggers($table = null)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }

        $query = "SELECT trigger_name
                    FROM sys.all_triggers
                   WHERE (table_name=? OR table_name=?)
                     AND (owner=? OR owner=?)";
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $args = array(
            $table,
            strtoupper($table),
            $owner,
            strtoupper($owner),
        );
        $result = $stmt->execute($args);
        return $this->_fetchCol($result);
    }

    // }}}
    // {{{ listTables()

    /**
     * list all tables in the database
     *
     * @param string owner, the current is default
     * @return mixed array of table names on success, a MDB2 error on failure
     * @access public
     */
    function listTables($owner = null)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        
        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }

        $query = 'SELECT table_name
                    FROM sys.all_tables
                   WHERE owner=? OR owner=?';
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $result = $stmt->execute(array($owner, strtoupper($owner)));
        return $this->_fetchCol($result);
    }

    // }}}
    // {{{ listTableFields()

    /**
     * list all fields in a table in the current database
     *
     * @param string $table name of table that should be used in method
     * @return mixed array of field names on success, a MDB2 error on failure
     * @access public
     */
    function listTableFields($table)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        
        list($owner, $table) = $this->splitTableSchema($table);
        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }

        $query = 'SELECT column_name
                    FROM all_tab_columns
                   WHERE (table_name=? OR table_name=?)
                     AND (owner=? OR owner=?)
                ORDER BY column_id';
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $args = array(
            $table,
            strtoupper($table),
            $owner,
            strtoupper($owner),
        );
        $result = $stmt->execute($args);
        return $this->_fetchCol($result);
    }

    // }}}
    // {{{ listTableIndexes()

    /**
     * list all indexes in a table
     *
     * @param string $table name of table that should be used in method
     * @return mixed array of index names on success, a MDB2 error on failure
     * @access public
     */
    function listTableIndexes($table)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }
        
        list($owner, $table) = $this->splitTableSchema($table);
        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }
        
        $query = 'SELECT i.index_name name
                    FROM all_indexes i
               LEFT JOIN all_constraints c
                      ON c.index_name = i.index_name
                     AND c.owner = i.owner
                     AND c.table_name = i.table_name
                   WHERE (i.table_name=? OR i.table_name=?)
                     AND (i.owner=? OR i.owner=?)
                     AND c.index_name IS NULL
                     AND i.generated=' .$db->quote('N', 'text');
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $args = array(
            $table,
            strtoupper($table),
            $owner,
            strtoupper($owner),
        );
        $result = $stmt->execute($args);
        return $this->_fetchCol($result, true);
    }

    // }}}
    // {{{ listTableConstraints()

    /**
     * list all constraints in a table
     *
     * @param string $table name of table that should be used in method
     * @return mixed array of constraint names on success, a MDB2 error on failure
     * @access public
     */
    function listTableConstraints($table)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        list($owner, $table) = $this->splitTableSchema($table);
        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }

        $query = 'SELECT constraint_name
                    FROM all_constraints
                   WHERE (table_name=? OR table_name=?)
                     AND (owner=? OR owner=?)';
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $args = array(
            $table,
            strtoupper($table),
            $owner,
            strtoupper($owner),
        );
        $result = $stmt->execute($args);
        return $this->_fetchCol($result, true);
    }

    // }}}
    // {{{ createSequence()

    /**
     * create sequence
     *
     * @param object $db database object that is extended by this class
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

        $sequence_name = $db->quoteIdentifier($db->getSequenceName($seq_name), true);
        $query = "CREATE SEQUENCE $sequence_name START WITH $start INCREMENT BY 1 NOCACHE";
        $query.= ($start < 1 ? " MINVALUE $start" : '');
        return $db->exec($query);
    }

    // }}}
    // {{{ dropSequence()

    /**
     * drop existing sequence
     *
     * @param object $db database object that is extended by this class
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

        $sequence_name = $db->quoteIdentifier($db->getSequenceName($seq_name), true);
        return $db->exec("DROP SEQUENCE $sequence_name");
    }

    // }}}
    // {{{ listSequences()

    /**
     * list all sequences in the current database
     *
     * @param string owner, the current is default
     * @return mixed array of sequence names on success, a MDB2 error on failure
     * @access public
     */
    function listSequences($owner = null)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (empty($owner)) {
            $owner = $db->dsn['username'];
        }

        $query = 'SELECT sequence_name
                    FROM sys.all_sequences
                   WHERE (sequence_owner=? OR sequence_owner=?)';
        $stmt = $db->prepare($query);
        if (PEAR::isError($stmt)) {
            return $stmt;
        }
        $result = $stmt->execute(array($owner, strtoupper($owner)));
        if (PEAR::isError($result)) {
            return $result;
        }
        $col = $result->fetchCol();
        if (PEAR::isError($col)) {
            return $col;
        }
        $result->free();
        
        foreach ($col as $k => $v) {
            $col[$k] = $this->_fixSequenceName($v);
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE
            && $db->options['field_case'] == CASE_LOWER
        ) {
            $col = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $col);
        }
        return $col;
    }
}
?>