<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2006 Manuel Lemos, Tomas V.V.Cox,                 |
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
 * MDB2 MSSQL driver for the schema reverse engineering module
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@dybnet.de>
 */
class MDB2_Driver_Reverse_mssql extends MDB2_Driver_Reverse_Common
{
    // {{{ getTableFieldDefinition()

    /**
     * Get the structure of a field into an array
     *
     * @param string    $table       name of table that should be used in method
     * @param string    $field_name  name of field that should be used in method
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
        $table = $db->quoteIdentifier($table, true);
        $fldname = $db->quoteIdentifier($field_name, true);

        $query = "SELECT c.name name,
                         c.length,
                         c.prec precision,
                         c.scale,
                         c.isnullable,
                         s.text 'default',
                         t.name type,
                         t.variable,
                         o.name tablename
                    FROM syscolumns c,
                         systypes t,
                         sysobjects o,
                         syscomments s
                   WHERE o.name = '$table'
                     AND c.name = '$fldname'
                     AND o.id = c.id
                     AND c.xtype = t.xtype
                     AND c.cdefault = s.id";

        $column = $db->queryRow($query, null, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($column)) {
            return $column;
        }
        if (empty($column)) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'it was not specified an existing table column', __FUNCTION__);
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            if ($db->options['field_case'] == CASE_LOWER) {
                $column['name'] = strtolower($column['name']);
            } else {
                $column['name'] = strtoupper($column['name']);
            }
        } else {
            $column = array_change_key_case($column, $db->options['field_case']);
        }

        $mapped_datatype = $db->datatype->mapNativeDatatype($column);
        if (PEAR::IsError($mapped_datatype)) {
            return $mapped_datatype;
        }
        list($types, $length, $unsigned, $fixed) = $mapped_datatype;
        $notnull = true;
        if ($column['isnullable']) {
            $notnull = false;
        }
        $default = false;
        if (array_key_exists('default', $column)) {
            $default = $column['default'];
            if (is_null($default) && $notnull) {
                $default = '';
            } elseif (strlen($default) > 4
                   && substr($default, 0, 1) == '('
                   &&  substr($default, -1, 1) == ')'
            ) {
                //mssql wraps the default value in parentheses: "((1234))", "(NULL)"
                $default = trim($default, '()');
                if ($default == 'NULL') {
                    $default = null;
                }
            }
        }
        $definition[0] = array(
            'notnull' => $notnull,
            'nativetype' => preg_replace('/^([a-z]+)[^a-z].*/i', '\\1', $column['type'])
        );
        if ($length > 0) {
            $definition[0]['length'] = $length;
        }
        if (!is_null($unsigned)) {
            $definition[0]['unsigned'] = $unsigned;
        }
        /*
        if (!is_null($fixed)) {
            $definition[0]['fixed'] = $fixed;
        }
        */
        $definition[0]['fixed'] = !$column['variable'];
        if ($default !== false) {
            $definition[0]['default'] = $default;
        }
        foreach ($types as $key => $type) {
            $definition[$key] = $definition[0];
            if ($type == 'clob' || $type == 'blob') {
                unset($definition[$key]['default']);
            }
            $definition[$key]['type'] = $type;
            $definition[$key]['mdb2type'] = $type;
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
        if (is_string($result)) {
           return parent::tableInfo($result, $mode);
        }

        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $resource = MDB2::isResultCommon($result) ? $result->getResource() : $result;
        if (!is_resource($resource)) {
            return $db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                'Could not generate result resource', __FUNCTION__);
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

        $count = @mssql_num_fields($resource);
        $res   = array();

        if ($mode) {
            $res['num_fields'] = $count;
        }

        $db->loadModule('Datatype', null, true);
        for ($i = 0; $i < $count; $i++) {
            $res[$i] = array(
                'table' => '',
                'name'  => $case_func(@mssql_field_name($resource, $i)),
                'type'  => @mssql_field_type($resource, $i),
                'length'   => @mssql_field_length($resource, $i),
                'flags' => '',
            );
            $mdb2type_info = $db->datatype->mapNativeDatatype($res[$i]);
            if (PEAR::isError($mdb2type_info)) {
               return $mdb2type_info;
            }
            $res[$i]['mdb2type'] = $mdb2type_info[0][0];
            if ($mode & MDB2_TABLEINFO_ORDER) {
                $res['order'][$res[$i]['name']] = $i;
            }
            if ($mode & MDB2_TABLEINFO_ORDERTABLE) {
                $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
            }
        }

        return $res;
    }

    // }}}
    // {{{ _mssql_field_flags()

    /**
     * Get a column's flags
     *
     * Supports "not_null", "primary_key",
     * "auto_increment" (mssql identity), "timestamp" (mssql timestamp),
     * "unique_key" (mssql unique index, unique check or primary_key) and
     * "multiple_key" (multikey index)
     *
     * mssql timestamp is NOT similar to the mysql timestamp so this is maybe
     * not useful at all - is the behaviour of mysql_field_flags that primary
     * keys are alway unique? is the interpretation of multiple_key correct?
     *
     * @param string $table   the table name
     * @param string $column  the field name
     *
     * @return string  the flags
     *
     * @access protected
     * @author Joern Barthel <j_barthel@web.de>
     */
    function _mssql_field_flags($table, $column)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        static $tableName = null;
        static $flags = array();

        if ($table != $tableName) {

            $flags = array();
            $tableName = $table;

            // get unique and primary keys
            $res = $db->queryAll("EXEC SP_HELPINDEX[$table]", null, MDB2_FETCHMODE_ASSOC);

            foreach ($res as $val) {
                $val = array_change_key_case($val, CASE_LOWER);
                $keys = explode(', ', $val['index_keys']);

                if (sizeof($keys) > 1) {
                    foreach ($keys as $key) {
                        $this->_add_flag($flags[$key], 'multiple_key');
                    }
                }

                if (strpos($val['index_description'], 'primary key')) {
                    foreach ($keys as $key) {
                        $this->_add_flag($flags[$key], 'primary_key');
                    }
                } elseif (strpos($val['index_description'], 'unique')) {
                    foreach ($keys as $key) {
                        $this->_add_flag($flags[$key], 'unique_key');
                    }
                }
            }

            // get auto_increment, not_null and timestamp
            $res = $db->queryAll("EXEC SP_COLUMNS[$table]", null, MDB2_FETCHMODE_ASSOC);

            foreach ($res as $val) {
                $val = array_change_key_case($val, CASE_LOWER);
                if ($val['nullable'] == '0') {
                    $this->_add_flag($flags[$val['column_name']], 'not_null');
                }
                if (strpos($val['type_name'], 'identity')) {
                    $this->_add_flag($flags[$val['column_name']], 'auto_increment');
                }
                if (strpos($val['type_name'], 'timestamp')) {
                    $this->_add_flag($flags[$val['column_name']], 'timestamp');
                }
            }
        }

        if (!empty($flags[$column])) {
            return(implode(' ', $flags[$column]));
        }
        return '';
    }

    // }}}
    // {{{ _add_flag()

    /**
     * Adds a string to the flags array if the flag is not yet in there
     * - if there is no flag present the array is created
     *
     * @param array  &$array  the reference to the flag-array
     * @param string $value   the flag value
     *
     * @return void
     *
     * @access protected
     * @author Joern Barthel <j_barthel@web.de>
     */
    function _add_flag(&$array, $value)
    {
        if (!is_array($array)) {
            $array = array($value);
        } elseif (!in_array($value, $array)) {
            array_push($array, $value);
        }
    }

    // }}}
}
?>