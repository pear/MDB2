<?php
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
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
 * MDB2 InterbaseBase driver for the reverse engineering module
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@dybnet.de>
 */
class MDB2_Driver_Reverse_ibase extends MDB2_Driver_Reverse_Common
{
    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function MDB2_Driver_Reverse_ibase($db_index)
    {
        $this->MDB2_Driver_Reverse_Common($db_index);
    }

    // }}}
    // {{{ tableInfo()

    /**
     * returns meta data about the result set
     *
     * @param  mixed $resource FireBird/InterBase result identifier or table name
     * @param mixed $mode depends on implementation
     * @return array an nested array, or a MDB2 error
     * @access public
     */
    function tableInfo($result, $mode = null)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $count = 0;
        $id = 0;
        $res = array();

        /**
         * depending on $mode, metadata returns the following values:
         *
         * - mode is false (default):
         * $result[]:
         *    [0]['table']  table name
         *    [0]['name']   field name
         *    [0]['type']   field type
         *    [0]['len']    field length
         *    [0]['flags']  field flags
         *
         * - mode is MDB2_TABLEINFO_ORDER
         * $result[]:
         *    ['num_fields'] number of metadata records
         *    [0]['table']  table name
         *    [0]['name']   field name
         *    [0]['type']   field type
         *    [0]['len']    field length
         *    [0]['flags']  field flags
         *    ['order'][field name]  index of field named 'field name'
         *    The last one is used, if you have a field name, but no index.
         *    Test:  if (isset($result['meta']['myfield'])) { ...
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
         *       you can combine MDB2_TABLEINFO_ORDER and
         *       MDB2_TABLEINFO_ORDERTABLE with MDB2_TABLEINFO_ORDER |
         *       MDB2_TABLEINFO_ORDERTABLE * or with MDB2_TABLEINFO_FULL
         **/

        // if $result is a string, then we want information about a
        // table without a resultset
        if (is_string($result)) {
            $id = @ibase_query($db->connection,"SELECT * FROM $result");
            if (empty($id)) {
                return $db->raiseError();
            }
        } else { // else we want information about a resultset
            $id = $result;
            if (empty($id)) {
                return $db->raiseError();
            }
        }

        $count = @ibase_num_fields($id);

        // made this IF due to performance (one if is faster than $count if's)
        if (empty($mode)) {
            for ($i=0; $i<$count; $i++) {
                $info = @ibase_field_info($id, $i);
                //$res[$i]['table'] = (is_string($result)) ? $result : '';
                $res[$i]['table'] = (is_string($result)) ? $result : $info['relation'];
                $res[$i]['name']  = $info['name'];
                $res[$i]['type']  = $info['type'];
                $res[$i]['len']   = $info['length'];
                //$res[$i]['flags'] = (is_string($result)) ? $this->_ibaseFieldFlags($info['name'], $result) : '';
                $res[$i]['flags'] = (is_string($result)) ? $this->_ibaseFieldFlags($id, $i, $result) : '';
            }
        } else { // full
            $res['num_fields'] = $count;

            for ($i=0; $i<$count; $i++) {
                $info = @ibase_field_info($id, $i);
                //$res[$i]['table'] = (is_string($result)) ? $result : '';
                $res[$i]['table'] = (is_string($result)) ? $result : $info['relation'];
                $res[$i]['name']  = $info['name'];
                $res[$i]['type']  = $info['type'];
                $res[$i]['len']   = $info['length'];
                //$res[$i]['flags'] = (is_string($result)) ? $this->_ibaseFieldFlags($info['name'], $result) : '';
                $res[$i]['flags'] = (is_string($result)) ? $this->_ibaseFieldFlags($id, $i, $result) : '';
                if ($mode & MDB2_TABLEINFO_ORDER) {
                    $res['order'][$res[$i]['name']] = $i;
                }
                if ($mode & MDB2_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                }
            }
        }

        // free the result only if we were called on a table
        if (is_string($result) && is_resource($id)) {
            @ibase_free_result($id);
        }
        return $res;
    }

    // }}}
    // {{{ _ibaseFieldFlags()

    /**
     * get the Flags of a Field
     *
     * @param int $resource FireBird/InterBase result identifier
     * @param int $num_field the field number
     * @return string The flags of the field ('not_null', 'default_xx', 'primary_key',
     *                 'unique' and 'multiple_key' are supported)
     * @access private
     **/
    function _ibaseFieldFlags($resource, $num_field, $table_name)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $field_name = @ibase_field_info($resource, $num_field);
        $field_name = @$field_name['name'];
        $sql = 'SELECT R.RDB$CONSTRAINT_TYPE CTYPE'.
               ' FROM RDB$INDEX_SEGMENTS I'.
               ' JOIN RDB$RELATION_CONSTRAINTS R ON I.RDB$INDEX_NAME=R.RDB$INDEX_NAME'.
               ' WHERE I.RDB$FIELD_NAME=\''.$field_name.'\''.
               ' AND R.RDB$RELATION_NAME=\''.$table_name.'\'';
        $result = @ibase_query($db->connection, $sql);
        if (empty($result)) {
            return $db->raiseError();
        }
        $flags = '';
        if ($obj = @ibase_fetch_object($result)) {
            @ibase_free_result($result);
            if (isset($obj->CTYPE)  && trim($obj->CTYPE) == 'PRIMARY KEY') {
                $flags = 'primary_key ';
            }
            if (isset($obj->CTYPE)  && trim($obj->CTYPE) == 'UNIQUE') {
                $flags .= 'unique_key ';
            }
        }

        $sql = 'SELECT  R.RDB$NULL_FLAG AS NFLAG,'.
            ' R.RDB$DEFAULT_SOURCE AS DSOURCE,'.
            ' F.RDB$FIELD_TYPE AS FTYPE,'.
            ' F.RDB$COMPUTED_SOURCE AS CSOURCE'.
            ' FROM RDB$RELATION_FIELDS R '.
            ' JOIN RDB$FIELDS F ON R.RDB$FIELD_SOURCE=F.RDB$FIELD_NAME'.
            ' WHERE R.RDB$RELATION_NAME=\''.$table_name.'\''.
            ' AND R.RDB$FIELD_NAME=\''.$field_name.'\'';
        $result = @ibase_query($db->connection, $sql);
        if (empty($result)) {
            return $db->raiseError();
        }
        if ($obj = @ibase_fetch_object($result)) {
            @ibase_free_result($result);
            if (isset($obj->NFLAG)) {
                $flags .= 'not_null ';
            }
            if (isset($obj->DSOURCE)) {
                $flags .= 'default ';
            }
            if (isset($obj->CSOURCE)) {
                $flags .= 'computed ';
            }
            if (isset($obj->FTYPE)  && $obj->FTYPE == 261) {
                $flags .= 'blob ';
            }
        }

        return trim($flags);
    }
}
?>