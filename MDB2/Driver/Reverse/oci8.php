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
 * MDB2 Oracle driver for the schema reverse engineering module
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@dybnet.de>
 */
class MDB2_Driver_Reverse_oci8 extends MDB2_Driver_Reverse_Common
{
    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function MDB2_Driver_Reverse_oci8($db_index)
    {
        $this->MDB2_Driver_Reverse_Common($db_index);
    }

    // }}}
    // {{{ tableInfo()

    /**
     * returns meta data about the result set
     *
     * @param resource $result result identifier
     * @param mixed $mode depends on implementation
     * @return array an nested array, or a MDB2 error
     * @access public
     */
    function tableInfo($result, $mode = null)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $count = 0;
        $res = array();
        /**
         * depending on $mode, metadata returns the following values:
         *
         * - mode is false (default):
         * $res[]:
         *    [0]['table']       table name
         *    [0]['name']        field name
         *    [0]['type']        field type
         *    [0]['len']         field length
         *    [0]['nullable']    field can be null (boolean)
         *    [0]['format']      field precision if NUMBER
         *    [0]['default']     field default value
         *
         * - mode is MDB2_TABLEINFO_ORDER
         * $res[]:
         *    ['num_fields']     number of fields
         *    [0]['table']       table name
         *    [0]['name']        field name
         *    [0]['type']        field type
         *    [0]['len']         field length
         *    [0]['nullable']    field can be null (boolean)
         *    [0]['format']      field precision if NUMBER
         *    [0]['default']     field default value
         *    ['order'][field name] index of field named 'field name'
         *    The last one is used, if you have a field name, but no index.
         *    Test:  if (isset($result['order']['myfield'])) { ...
         *
         * - mode is MDB2_TABLEINFO_ORDERTABLE
         *     the same as above. but additionally
         *    ['ordertable'][table name][field name] index of field
         *       named 'field name'
         *
         *       this is, because if you have fields from different
         *       tables with the same field name * they override each
         *       other with MDB2_TABLEINFO_ORDER
         *
         *       you can combine DB_TABLEINFO_ORDER and
         *       MDB2_TABLEINFO_ORDERTABLE with MDB2_TABLEINFO_ORDER |
         *       MDB2_TABLEINFO_ORDERTABLE * or with MDB2_TABLEINFO_FULL
         */
        // if $result is a string, we collect info for a table only
        if (is_string($result)) {
            $result = strtoupper($result);
            $q_fields = "SELECT column_name, data_type, data_length, data_precision,
                     nullable, data_default FROM user_tab_columns
                     WHERE table_name='$result' order by column_id";
            if (!$stmt = @OCIParse($db->connection, $q_fields)) {
                return $db->oci8RaiseError();
            }
            if (!@OCIExecute($stmt, OCI_DEFAULT)) {
                return $db->oci8RaiseError($stmt);
            } while (@OCIFetch($stmt)) {
                $res[$count]['table'] = strtolower($result);
                $res[$count]['name'] = strtolower(@OCIResult($stmt, 1));
                $res[$count]['type'] = strtolower(@OCIResult($stmt, 2));
                $res[$count]['len'] = @OCIResult($stmt, 3);
                $res[$count]['format'] = @OCIResult($stmt, 4);
                $res[$count]['nullable'] = (@OCIResult($stmt, 5) == 'Y') ? true : false;
                $res[$count]['default'] = @OCIResult($stmt, 6);
                if ($mode & MDB2_TABLEINFO_ORDER) {
                    $res['order'][$res[$count]['name']] = $count;
                }
                if ($mode & MDB2_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$count]['table']][$res[$count]['name']] = $count;
                }
                $count++;
            }
            $res['num_fields'] = $count;
            @OCIFreeStatement($stmt);
        } else { // else we want information about a resultset
            #if ($result === $db->last_stmt) {
                $result = $result->getResource();
                $count = @OCINumCols($result);
                for ($i = 0; $i < $count; $i++) {
                    $res[$i]['name']  = strtolower(@OCIColumnName($result, $i+1));
                    $res[$i]['type']  = strtolower(@OCIColumnType($result, $i+1));
                    $res[$i]['len'] = @OCIColumnSize($result, $i + 1);

                    $q_fields = "SELECT table_name, data_precision, nullable, data_default
                        FROM user_tab_columns
                        WHERE column_name='$name'";
                    if (!$stmt = @OCIParse($db->connection, $q_fields)) {
                        return $db->oci8RaiseError();
                    }
                    if (!@OCIExecute($stmt, OCI_DEFAULT)) {
                        return $db->oci8RaiseError($stmt);
                    }
                    @OCIFetch($stmt);
                    $res[$i]['table'] = strtolower(@OCIResult($stmt, 1));
                    $res[$i]['format'] = @OCIResult($stmt, 2);
                    $res[$i]['nullable'] = (@OCIResult($stmt, 3) == 'Y') ? true : false;
                    $res[$i]['default'] = @OCIResult($stmt, 4);
                    @OCIFreeStatement($stmt);

                    if ($mode & MDB2_TABLEINFO_ORDER) {
                        $res['order'][$res[$i]['name']] = $i;
                    }
                    if ($mode & MDB2_TABLEINFO_ORDERTABLE) {
                        $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                    }
                }
                $res['num_fields'] = $count;
            #} else {
            #    return $db->raiseError(MDB2_ERROR_NOT_CAPABLE);
            #}
        }
        return $res;
    }
}
?>