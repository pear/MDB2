<?php
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2003 Manuel Lemos, Tomas V.V.Cox,                 |
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
    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function MDB2_Driver_Reverse_mssql($db_index)
    {
        $this->MDB2_Driver_Reverse_Common($db_index);
    }

    // }}}
    // {{{ tableInfo()

  /**
     * Returns information about a table or a result set
     *
     * NOTE: doesn't support table name and flags if called from a db_result
     *
     * @param  mixed $resource SQL Server result identifier or table name
     * @param  int $mode A valid tableInfo mode (MDB2_TABLEINFO_ORDERTABLE or
     *                   MDB2_TABLEINFO_ORDER)
     *
     * @return array An array with all the information
     */
    function tableInfo($result, $mode = null)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $count = 0;
        $id    = 0;
        $res   = array();

        /*
         * depending on $mode, metadata returns the following values:
         *
         * - mode is false (default):
         * $result[]:
         *   [0]['table']  table name
         *   [0]['name']   field name
         *   [0]['type']   field type
         *   [0]['len']    field length
         *   [0]['flags']  field flags
         *
         * - mode is MDB2_TABLEINFO_ORDER
         * $result[]:
         *   ["num_fields"] number of metadata records
         *   [0]['table']  table name
         *   [0]['name']   field name
         *   [0]['type']   field type
         *   [0]['len']    field length
         *   [0]['flags']  field flags
         *   ['order'][field name]  index of field named "field name"
         *   The last one is used, if you have a field name, but no index.
         *   Test:  if (isset($result['meta']['myfield'])) { ...
         *
         * - mode is MDB2_TABLEINFO_ORDERTABLE
         *    the same as above. but additionally
         *   ["ordertable"][table name][field name] index of field
         *      named "field name"
         *
         *      this is, because if you have fields from different
         *      tables with the same field name * they override each
         *      other with MDB2_TABLEINFO_ORDER
         *
         *      you can combine MDB2_TABLEINFO_ORDER and
         *      MDB2_TABLEINFO_ORDERTABLE with MDB2_TABLEINFO_ORDER |
         *      MDB2_TABLEINFO_ORDERTABLE * or with MDB2_TABLEINFO_FULL
         */

        // if $result is a string, then we want information about a
        // table without a resultset

        if (is_string($result)) {
            if (!@mssql_select_db($db->database_name, $db->connection)) {
                return $db->mssqlRaiseError(MDB2_ERROR_NODBSELECTED);
            }
            $id = @mssql_query("SELECT * FROM $result", $db->connection);
            if (empty($id)) {
                return $db->mssqlRaiseError();
            }
        } else { // else we want information about a resultset
            $id = $result;
            if (empty($id)) {
                return $db->mssqlRaiseError();
            }
        }

        $count = @mssql_num_fields($id);

        // made this IF due to performance (one if is faster than $count if's)
        if (empty($mode)) {

            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = (is_string($result)) ? $result : '';
                $res[$i]['name']  = @mssql_field_name($id, $i);
                $res[$i]['type']  = @mssql_field_type($id, $i);
                $res[$i]['len']   = @mssql_field_length($id, $i);
                // We only support flags for tables
                $res[$i]['flags'] = is_string($result) ? $this->_mssql_field_flags($result, $res[$i]['name']) : '';
            }

        } else { // full
            $res['num_fields']= $count;

            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = (is_string($result)) ? $result : '';
                $res[$i]['name']  = @mssql_field_name($id, $i);
                $res[$i]['type']  = @mssql_field_type($id, $i);
                $res[$i]['len']   = @mssql_field_length($id, $i);
                // We only support flags for tables
                $res[$i]['flags'] = is_string($result) ? $this->_mssql_field_flags($result, $res[$i]['name']) : '';
                if ($mode & MDB2_TABLEINFO_ORDER) {
                    $res['order'][$res[$i]['name']] = $i;
                }
                if ($mode & MDB2_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                }
            }
        }

        // free the result only if we were called on a table
        if (is_string($result)) {
            @mssql_free_result($id);
        }
        return $res;
    }

    // }}}
    // {{{ _mssql_field_flags()
    /**
    * Get the flags for a field, currently only supports "isnullable" and "primary_key"
    *
    * @param string The table name
    * @param string The field
    * @access private
    */
    function _mssql_field_flags($table, $column)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        static $current_table = null;
        static $flags;
        // At the first call we discover the flags for all fields
        if ($table != $current_table) {
            $flags = array();
            // find nullable fields
            $q_nulls = "SELECT syscolumns.name, syscolumns.isnullable
                        FROM sysobjects
                        INNER JOIN syscolumns ON sysobjects.id = syscolumns.id
                        WHERE sysobjects.name ='$table' AND syscolumns.isnullable = 1";
            $nullables = $db->queryAll($q_nulls, null, MDB2_FETCHMODE_ASSOC);
            if (MDB2::isError($nullables)) {
                return $nullables;
            }
            foreach ($nullables as $data) {
                if ($data['isnullable'] == 1) {
                    $flags[$data['name']][] = 'isnullable';
                }
            }
            // find primary keys
            $primarykeys = $db->queryAll("EXEC SP_PKEYS[$table]", null, MDB2_FETCHMODE_ASSOC);
            if (MDB2::isError($primarykeys)) {
                return $primarykeys;
            }
            foreach ($primarykeys as $data) {
                if (!empty($data['COLUMN_NAME'])) {
                    $flags[$data['COLUMN_NAME']][] = 'primary_key';
                }
            }
            $current_table = $table;
        }
        if (isset($flags[$column])) {
            return implode(',', $flags[$column]);
        }
        return '';
    }
}
?>