<?php
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
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
// | Author: Lukas Smith <smith@backendmedia.com>                         |
// +----------------------------------------------------------------------+
//
// $Id$
//

/**
 * @package MDB2
 * @category Database
 */

/**
 * These are constants for the tableInfo-function
 * they are bitwised or'ed. so if there are more constants to be defined
 * in the future, adjust MDB2_TABLEINFO_FULL accordingly
 */

define('MDB2_TABLEINFO_ORDER',      1);
define('MDB2_TABLEINFO_ORDERTABLE', 2);
define('MDB2_TABLEINFO_FULL',       3);

/**
 * Base class for the schema reverse engineering module that is extended by each MDB2 driver
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@backendmedia.com>
 */
class MDB2_Driver_Reverse_Common
{
    var $db_index;

    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function MDB2_Driver_Reverse_Common($db_index)
    {
        $this->db_index = $db_index;
    }

    // }}}
    // {{{ getTableFieldDefinition()

    /**
     * get the stucture of a field into an array
     *
     * @param string    $table         name of table that should be used in method
     * @param string    $fields     name of field that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function getTableFieldDefinition($table, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return $db->raiseError(MDB2_ERROR_NOT_CAPABLE, null, null,
            'getTableFieldDefinition: table field definition is not supported');
    }

    // }}}
    // {{{ getTableIndexDefinition()

    /**
     * get the stucture of an index into an array
     *
     * @param string    $table      name of table that should be used in method
     * @param string    $index      name of index that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function getTableIndexDefinition($table, $index)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return $db->raiseError(MDB2_ERROR_NOT_CAPABLE, null, null,
            'getTableIndexDefinition: getting index definition is not supported');
    }

    // }}}
    // {{{ getSequenceDefinition()

    /**
     * get the stucture of a sequence into an array
     *
     * @param string    $sequence   name of sequence that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function getSequenceDefinition($sequence)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $start = $db->currId($sequence);
        if (MDB2::isError($start)) {
            return $start;
        }
        if ($db->supports('current_id')) {
            $start++;
        } else {
            $db->warnings[] = 'database does not support getting current
                sequence value,the sequence value was incremented';
        }
        $definition = array();
        if ($start != 1) {
            $definition = array('start' => $start);
        }
        return $definition;
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
        return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
            'tableInfo: method not implemented');
    }
}
?>