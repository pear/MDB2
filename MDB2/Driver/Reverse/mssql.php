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
// | Author: Lukas Smith <smith@backendmedia.com>                         |
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
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set.
     *
     * NOTE: only supports 'table' and 'flags' if <var>$result</var>
     * is a table name.
     *
     * @param object|string  $result  MDB2_result object from a query or a
     *                                string containing the name of a table
     * @param int            $mode    a valid tableInfo mode
     * @return array  an associative array with the information requested
     *                or an error object if something is wrong
     * @access public
     * @internal
     * @see MDB2_Driver_Common::tableInfo()
     */
    function tableInfo($result, $mode = null)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if ($db->options['portability'] & MDB2_PORTABILITY_LOWERCASE) {
            $case_func = 'strtolower';
        } else {
            $case_func = 'strval';
        }

        if (is_string($result)) {
            /*
             * Probably received a table name.
             * Create a result resource identifier.
             */
            if (MDB2::isError($connect = $db->connect())) {
                return $connect;
            }
            $id = @mssql_query("SELECT * FROM $result WHERE 1=0",
                $db->connection);
            $got_string = true;
        } else {
            /*
             * Probably received a result object.
             * Extract the result resource identifier.
             */
            $id = $result->getResource();
            if (empty($id)) {
                return $db->raiseError();
            }
            $got_string = false;
        }

        if (!is_resource($id)) {
            return $db->raiseError(MDB2_ERROR_NEED_MORE_DATA);
        }

        $count = @mssql_num_fields($id);

        // made this IF due to performance (one if is faster than $count if's)
        if (!$mode) {
            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = $got_string ? $case_func($result) : '';
                $res[$i]['name']  = $case_func(@mssql_field_name($id, $i));
                $res[$i]['type']  = @mssql_field_type($id, $i);
                $res[$i]['len']   = @mssql_field_length($id, $i);
                // We only support flags for tables
                $res[$i]['flags'] = $got_string ? $this->_mssql_field_flags($result, $res[$i]['name']) : '';
            }

        } else { // full
            $res['num_fields']= $count;

            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = $got_string ? $case_func($result) : '';
                $res[$i]['name']  = $case_func(@mssql_field_name($id, $i));
                $res[$i]['type']  = @mssql_field_type($id, $i);
                $res[$i]['len']   = @mssql_field_length($id, $i);
                // We only support flags for tables
                $res[$i]['flags'] = $got_string ? $this->_mssql_field_flags($result, $res[$i]['name']) : '';

                if ($mode & MDB2_TABLEINFO_ORDER) {
                    $res['order'][$res[$i]['name']] = $i;
                }
                if ($mode & MDB2_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                }
            }
        }

        // free the result only if we were called on a table
        if ($got_string) {
            @mssql_free_result($id);
        }
        return $res;
    }


    // }}}
    // {{{ _mssql_field_flags()

    /**
     * Get the flags for a field, currently supports "not_null", "primary_key",
     * "auto_increment" (mssql identity), "timestamp" (mssql timestamp),
     * "unique_key" (mssql unique index, unique check or primary_key) and
     * "multiple_key" (multikey index)
     *
     * mssql timestamp is NOT similar to the mysql timestamp so this is maybe
     * not useful at all - is the behaviour of mysql_field_flags that primary
     * keys are alway unique? is the interpretation of multiple_key correct?
     *
     * @param string The table name
     * @param string The field
     * @author Joern Barthel <j_barthel@web.de>
     * @access private
     */
    function _mssql_field_flags($table, $column)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];

        static $tableName = null;
        static $flags = array();

        if ($table != $tableName) {

            $flags = array();
            $tableName = $table;

            // get unique and primary keys
            $res = $db->queryAll("EXEC SP_HELPINDEX[$table]", null, MDB2_FETCHMODE_ASSOC);

            foreach ($res as $val) {
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

        if (array_key_exists($column, $flags)) {
            return implode(' ', $flags[$column]);
        }
        return '';
    }

    // }}}
    // {{{ _add_flag()

    /**
     * Adds a string to the flags array if the flag is not yet in there
     * - if there is no flag present the array is created.
     *
     * @param reference  Reference to the flag-array
     * @param value      The flag value
     * @access private
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
}
?>