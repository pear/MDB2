<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2004 Manuel Lemos, Tomas V.V.Cox,                 |
// | Stig. S. Bakken, Lukas Smith, Frank M. Kromann                       |
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
//
// $Id$
//

require_once 'MDB2/Driver/Reverse/Common.php';

/**
 * MDB2 Oracle driver for the schema reverse engineering module
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@dybnet.de>
 */
class MDB2_Driver_Reverse_oci8 extends MDB2_Driver_Reverse_Common
{
    // {{{ getTableFieldDefinition()

    /**
     * get the stucture of a field into an array
     *
     * @param string    $table         name of table that should be used in method
     * @param string    $field_name     name of field that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function getTableFieldDefinition($table, $field_name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $result = $db->loadModule('Datatype', null, true);
        if (PEAR::isError($result)) {
            return $result;
        }

        $column = $db->queryRow('SELECT column_name, data_type, data_length, '
                                . 'nullable '
                                . 'FROM user_tab_columns '
                                . 'WHERE table_name=\''.strtoupper($table).'\' AND column_name = \''.strtoupper($field_name).'\' '
                                . 'ORDER BY column_id', null, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($column)) {
            return $column;
        }
        if ($column) {
            $column['name'] = $column['column_name'];
            unset($column['column_name']);
            $column['type'] = $column['data_type'];
            unset($column['data_type']);
            if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                if ($db->options['field_case'] == CASE_LOWER) {
                    $column['name'] = strtolower($column['name']);
                } else {
                    $column['name'] = strtoupper($column['name']);
                }
            } else {
                $column = array_change_key_case($column, $db->options['field_case']);
            }
            list($types, $length, $unsigned) = $db->datatype->mapNativeDatatype($column);
            $notnull = false;
            if (array_key_exists('nullable', $column) && $column['nullable'] != 'N') {
                $notnull = true;
            }
            $default = false;
            if (array_key_exists('default', $column)) {
                $default = $column['default'];
                if (is_null($default) && $notnull) {
                    $default = '';
                }
            }
            $definition = array();
            foreach ($types as $key => $type) {
                $definition[$key] = array(
                                          'type' => $type,
                                          'notnull' => $notnull,
                                          );
                if ($length > 0) {
                    $definition[$key]['length'] = $length;
                }
                if ($unsigned) {
                    $definition[$key]['unsigned'] = true;
                }
                if ($default !== false) {
                    $definition[$key]['default'] = $default;
                }
            }
            return $definition;
        }

        return $db->raiseError(MDB2_ERROR, null, null,
            'getTableFieldDefinition: it was not specified an existing table column');
    }

    // }}}

    // {{{ getTableIndexDefinition()

    /**
     * get the stucture of an index into an array
     *
     * @param string    $table      name of table that should be used in method
     * @param string    $index_name name of index that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function getTableIndexDefinition($table, $index_name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $index_name = $db->quote($db->getIndexName(strtoupper($index_name)));
        $table = $db->quote($db->getIndexName(strtoupper($table)));
        $row = $db->queryRow("SELECT * FROM user_indexes where table_name = $table AND index_name = $index_name", null, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($row)) {
            return $row;
        }
        $definition = array();
        if ($row) {
            if (!($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE)
                || $db->options['field_case'] != CASE_LOWER
            ) {
                $row = array_change_key_case($row, CASE_LOWER);
            }
            $key_name = $row['index_name'];
            if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                if ($db->options['field_case'] == CASE_LOWER) {
                    $key_name = strtolower($key_name);
                } else {
                    $key_name = strtoupper($key_name);
                }
            }
            /*if (!$row['non_unique']) {
                return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                                       'getTableIndexDefinition: it was not specified an existing table index');
            }*/
            $result = $db->query('SELECT * FROM user_ind_columns WHERE index_name = '.$index_name.
                                 ' AND table_name = '.$table);
            if (PEAR::isError($result)) {
                return $result;
            }
            while ($colrow = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
                $column_name = $colrow['column_name'];
                if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                    if ($db->options['field_case'] == CASE_LOWER) {
                        $column_name = strtolower($column_name);
                    } else {
                        $column_name = strtoupper($column_name);
                    }
                }
                $definition['fields'][$column_name] = array();
                if (array_key_exists('descend', $colrow)) {
                    $definition['fields'][$column_name]['sorting'] = ($colrow['descend'] == 'ASC'
                                                                      ? 'ascending' : 'descending');
                }
            }
            $result->free();
        }
        if (!array_key_exists('fields', $definition)) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'getTableIndexDefinition: it was not specified an existing table index');
        }
        return $definition;
    }

    // }}}
    // {{{ getTableConstraintDefinition()

    /**
     * get the stucture of a constraint into an array
     *
     * @param string    $table      name of table that should be used in method
     * @param string    $index_name name of index that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function getTableConstraintDefinition($table, $index_name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (strtolower($index_name) != 'primary') {
            $index_name = $db->getIndexName($index_name);
        }
        $dsn = $db->getDsn();
        $dbName = $db->quote($dsn['database']);
        $index_name = $db->quote($index_name);
        $table = $db->quote($table);
        $result = $db->query("SELECT * FROM ALL_CONSTRAINTS WHERE OWNER = $dbName AND TABLE_NAME = $table AND INDEX_NAME = $index_name");
        if (PEAR::isError($result)) {
            return $result;
        }
        $definition = array();
        while (is_array($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC))) {
            if (!($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE)
                || $db->options['field_case'] != CASE_LOWER
            ) {
                $row = array_change_key_case($row, CASE_LOWER);
            }
            $key_name = $row['constraint_name'];
            if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                if ($db->options['field_case'] == CASE_LOWER) {
                    $key_name = strtolower($key_name);
                } else {
                    $key_name = strtoupper($key_name);
                }
            }
            if ($row) {
                $definition['primary'] = $row['constraint_type'] == 'P';
                $definition['unique'] = $row['constraint_type'] == 'U';

                $colres = $db->query('SELECT * FROM ALL_CONS_COLUMNS WHERE CONSTRAINT_NAME = '.
                                     $db->quote($key_name).
                                     ' AND TABLE_NAME = '.$table);
                while ($colrow = $colres->fetchRow(MDB2_FETCHMODE_ASSOC)) {
                    $column_name = $row['column_name'];
                    if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                        if ($db->options['field_case'] == CASE_LOWER) {
                            $column_name = strtolower($column_name);
                        } else {
                            $column_name = strtoupper($column_name);
                        }
                    }
                    $definition['fields'][$column_name] = array();
                }
            }
        }
        $result->free();
        if (!array_key_exists('fields', $definition)) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'getTableConstraintDefinition: it was not specified an existing table constraint');
        }
        return $definition;
    }

    // }}}


    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set
     *
     * NOTE: only supports 'table' and 'flags' if <var>$result</var>
     * is a table name.
     *
     * NOTE: flags won't contain index information.
     *
     * @param object|string  $result  MDB2_result object from a query or a
     *                                 string containing the name of a table.
     *                                 While this also accepts a query result
     *                                 resource identifier, this behavior is
     *                                 deprecated.
     * @param int            $mode    a valid tableInfo mode
     *
     * @return array  an associative array with the information requested.
     *                 A MDB2_Error object on failure.
     *
     * @see MDB2_Driver_Common::tableInfo()
     */
    function tableInfo($result, $mode = null)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            if ($db->options['field_case'] == CASE_LOWER) {
                $case_func = 'strtolower';
            } else {
                $case_func = 'strtoupper';
            }
        } else {
            $case_func = 'strval';
        }

        $res = array();

        if (is_string($result)) {
            /*
             * Probably received a table name.
             * Create a result resource identifier.
             */
            $result = strtoupper($result);
            $query = 'SELECT column_name, data_type, data_length, '
                        . 'nullable '
                        . 'FROM user_tab_columns '
                        . "WHERE table_name='$result' ORDER BY column_id";

            $stmt = $db->_doQuery($query, false);
            if (PEAR::isError($stmt)) {
                return $stmt;
            }

            $i = 0;
            while (@OCIFetch($stmt)) {
                $res[$i] = array(
                    'table'  => $case_func($result),
                    'name'   => $case_func(@OCIResult($stmt, 1)),
                    'type'   => @OCIResult($stmt, 2),
                    'length' => @OCIResult($stmt, 3),
                    'flags'  => (@OCIResult($stmt, 4) == 'N') ? 'not_null' : '',
                );
                // todo: implement $db->datatype->mapNativeDatatype();
                $res[$i]['mdb2type'] = $res[$i]['type'];
                if ($mode & MDB2_TABLEINFO_ORDER) {
                    $res['order'][$res[$i]['name']] = $i;
                }
                if ($mode & MDB2_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                }
                $i++;
            }

            if ($mode) {
                $res['num_fields'] = $i;
            }
            @OCIFreeStatement($stmt);

        } else {
            if (MDB2::isResultCommon($result)) {
                /*
                 * Probably received a result object.
                 * Extract the result resource identifier.
                 */
                $result = $result->getResource();
            }

            $res = array();

            $count = @OCINumCols($result);
            if ($mode) {
                $res['num_fields'] = $count;
            }
            for ($i = 0; $i < $count; $i++) {
                $res[$i] = array(
                    'table'  => '',
                    'name'   => $case_func(@OCIColumnName($result, $i+1)),
                    'type'   => @OCIColumnType($result, $i+1),
                    'length' => @OCIColumnSize($result, $i+1),
                    'flags'  => '',
                );
                // todo: implement $db->datatype->mapNativeDatatype();
                $res[$i]['mdb2type'] = $res[$i]['type'];
                if ($mode & MDB2_TABLEINFO_ORDER) {
                    $res['order'][$res[$i]['name']] = $i;
                }
                if ($mode & MDB2_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                }
            }
        }
        return $res;
    }

    /**
     * get the stucture of a sequence into an array
     *
     * @param string    $sequence   name of sequence that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function getSequenceDefinition($sequence)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $sequence_name = $db->quote($db->getSequenceName($sequence));
        $start = $db->queryOne("SELECT last_number FROM user_sequences WHERE sequence_name = $sequence_name");
        if (PEAR::isError($start)) {
            return $start;
        }
        if ($db->supports('current_id')) {
            $start++;
        } else {
            $db->warnings[] = 'database does not support getting current
                sequence value, the sequence value was incremented';
        }
        $definition = array();
        if ($start != 1) {
            $definition = array('start' => $start);
        }
        return $definition;
    }

    // }}}
}
?>