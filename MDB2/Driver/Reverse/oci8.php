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
 * MDB2 Oracle driver for the schema reverse engineering module
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@dybnet.de>
 */
class MDB2_Driver_Reverse_oci8 extends MDB2_Driver_Reverse_Common
{
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set.
     *
     * NOTE: only supports 'table' and 'flags' if <var>$result</var>
     * is a table name.
     *
     * NOTE: flags won't contain index information.
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
            $result = strtoupper($result);
            $q_fields = 'SELECT column_name, data_type, data_length, '
                        . 'nullable '
                        . 'FROM user_tab_columns '
                        . "WHERE table_name='$result' ORDER BY column_id";

            $db->last_query = $q_fields;

            if (!$stmt = @OCIParse($db->connection, $q_fields)) {
                return $db->raiseError(MDB2_ERROR_NEED_MORE_DATA);
            }
            if (!@OCIExecute($stmt, OCI_DEFAULT)) {
                return $db->raiseError($stmt);
            }

            $i = 0;
            while (@OCIFetch($stmt)) {
                $res[$i]['table'] = $case_func($result);
                $res[$i]['name']  = $case_func(@OCIResult($stmt, 1));
                $res[$i]['type']  = @OCIResult($stmt, 2);
                $res[$i]['len']   = @OCIResult($stmt, 3);
                $res[$i]['flags'] = (@OCIResult($stmt, 4) == 'N') ? 'not_null' : '';

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
            /*
             * Probably received a result object.
             * Extract the result resource identifier.
             */
            $id = $result->getResource();
            if (empty($id)) {
                return $db->raiseError();
            }

#            if ($result === $db->last_stmt) {
                $count = @OCINumCols($id);

                for ($i=0; $i<$count; $i++) {
                    $res[$i]['table'] = '';
                    $res[$i]['name']  = $case_func(@OCIColumnName($id, $i+1));
                    $res[$i]['type']  = @OCIColumnType($id, $i+1);
                    $res[$i]['len']   = @OCIColumnSize($id, $i+1);
                    $res[$i]['flags'] = '';

                    if ($mode & MDB2_TABLEINFO_ORDER) {
                        $res['order'][$res[$i]['name']] = $i;
                    }
                    if ($mode & MDB2_TABLEINFO_ORDERTABLE) {
                        $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                    }
                }

                if ($mode) {
                    $res['num_fields'] = $i;
                }

#            } else {
#                return $db->raiseError(MDB2_ERROR_NOT_CAPABLE);
#            }
        }
        return $res;
    }
}
?>