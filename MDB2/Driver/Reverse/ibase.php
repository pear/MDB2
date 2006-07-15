<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2006 Manuel Lemos, Tomas V.V.Cox,                 |
// | Stig. S. Bakken, Lukas Smith, Frank M. Kromann, Lorenzo Alberton     |
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
//

require_once 'MDB2/Driver/Reverse/Common.php';

/**
 * MDB2 InterbaseBase driver for the reverse engineering module
 *
 * @package MDB2
 * @category Database
 * @author Lorenzo Alberton  <l.alberton@quipo.it>
 */
class MDB2_Driver_Reverse_ibase extends MDB2_Driver_Reverse_Common
{
    /**
     * Array for converting constant values to text values
     * @var    array
     * @access public
     */
    var $types = array(
        7   => 'smallint',
        8   => 'integer',
        9   => 'quad',
        10  => 'float',
        11  => 'd_float',
        12  => 'date',      //dialect 3 DATE
        13  => 'time',
        14  => 'char',
        16  => 'int64',
        27  => 'double',
        35  => 'timestamp', //DATE in older versions
        37  => 'varchar',
        40  => 'cstring',
        261 => 'blob',
    );

    /**
     * Array for converting constant values to text values
     * @var    array
     * @access public
     */
    var $subtypes = array(
        //char subtypes
        14 => array(
            0 => 'unspecified',
            1 => 'fixed', //BINARY data
        ),
        //blob subtypes
        261 => array(
            0 => 'unspecified',
            1 => 'text',
            2 => 'BLR', //Binary Language Representation
            3 => 'access control list',
            4 => 'reserved for future use',
            5 => 'encoded description of a table\'s current metadata',
            6 => 'description of multi-database transaction that finished irregularly',
        ),
        //smallint subtypes
        7 => array(
            0 => 'RDB$FIELD_TYPE',
            1 => 'numeric',
            2 => 'decimal',
        ),
        //integer subtypes
        8 => array(
            0 => 'RDB$FIELD_TYPE',
            1 => 'numeric',
            2 => 'decimal',
        ),
        //int64 subtypes
        16 => array(
            0 => 'RDB$FIELD_TYPE',
            1 => 'numeric',
            2 => 'decimal',
        ),
    );

    // {{{ getTableFieldDefinition()

    /**
     * Get the stucture of a field into an array
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
        $table = $db->quote(strtoupper($table), 'text');
        $field_name = $db->quote(strtoupper($field_name), 'text');
        $query = "SELECT RDB\$RELATION_FIELDS.RDB\$FIELD_NAME AS name,
                         RDB\$FIELDS.RDB\$FIELD_LENGTH AS \"length\",
                         RDB\$FIELDS.RDB\$FIELD_TYPE AS field_type_code,
                         RDB\$FIELDS.RDB\$FIELD_SUB_TYPE AS field_sub_type_code,
                         RDB\$RELATION_FIELDS.RDB\$DESCRIPTION AS description,
                         RDB\$RELATION_FIELDS.RDB\$NULL_FLAG AS null_flag,
                         RDB\$FIELDS.RDB\$DEFAULT_SOURCE AS default_source
                    FROM RDB\$FIELDS
               LEFT JOIN RDB\$RELATION_FIELDS ON RDB\$FIELDS.RDB\$FIELD_NAME = RDB\$RELATION_FIELDS.RDB\$FIELD_SOURCE
                   WHERE UPPER(RDB\$RELATION_FIELDS.RDB\$RELATION_NAME)=$table
                     AND UPPER(RDB\$RELATION_FIELDS.RDB\$FIELD_NAME)=$field_name;";
        $column = $db->queryRow($query, null, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($column)) {
            return $column;
        }
        if (empty($column)) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'it was not specified an existing table column', __FUNCTION__);
        }
        $column = array_change_key_case($column, CASE_LOWER);
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            if ($db->options['field_case'] == CASE_LOWER) {
                $column['name'] = strtolower($column['name']);
            } else {
                $column['name'] = strtoupper($column['name']);
            }
        }

        $column['type'] = array_key_exists((int)$column['field_type_code'], $this->types)
            ? $this->types[(int)$column['field_type_code']] : 'undefined';
        if ($column['field_sub_type_code']
            && array_key_exists((int)$column['field_type_code'], $this->subtypes)
            && array_key_exists($column['field_sub_type_code'], $this->subtypes[(int)$column['field_type_code']])
        ) {
            $column['field_sub_type'] = $this->subtypes[(int)$column['field_type_code']][$column['field_sub_type_code']];
        } else {
            $column['field_sub_type'] = null;
        }
        list($types, $length, $unsigned, $fixed) = $db->datatype->mapNativeDatatype($column);
        $notnull = !empty($column['null_flag']);
        $default = $column['default_source'];
        if (is_null($default) && $notnull) {
            $default = ($types[0] == 'integer') ? 0 : '';
        }

        $definition[0] = array('notnull' => $notnull);
        if ($length > 0) {
            $definition[0]['length'] = $length;
        }
        if (!is_null($unsigned)) {
            $definition[0]['unsigned'] = $unsigned;
        }
        if (!is_null($fixed)) {
            $definition[0]['fixed'] = $fixed;
        }
        if ($default !== false) {
            $definition[0]['default'] = $default;
        }
        foreach ($types as $key => $type) {
            $definition[$key] = $definition[0];
            $definition[$key]['type'] = $type;
        }
        return $definition;
    }

    // }}}
    // {{{ getTableIndexDefinition()

    /**
     * Get the stucture of an index into an array
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
        $table = $db->quote(strtoupper($table), 'text');
        $index_name = $db->quote(strtoupper($db->getIndexName($index_name)), 'text');
        $query = "SELECT RDB\$INDEX_SEGMENTS.RDB\$FIELD_NAME AS field_name,
                         RDB\$INDICES.RDB\$UNIQUE_FLAG AS unique_flag,
                         RDB\$INDICES.RDB\$FOREIGN_KEY AS foreign_key,
                         RDB\$INDICES.RDB\$DESCRIPTION AS description
                    FROM RDB\$INDEX_SEGMENTS
               LEFT JOIN RDB\$INDICES ON RDB\$INDICES.RDB\$INDEX_NAME = RDB\$INDEX_SEGMENTS.RDB\$INDEX_NAME
               LEFT JOIN RDB\$RELATION_CONSTRAINTS ON RDB\$RELATION_CONSTRAINTS.RDB\$INDEX_NAME = RDB\$INDEX_SEGMENTS.RDB\$INDEX_NAME
                   WHERE UPPER(RDB\$INDICES.RDB\$RELATION_NAME)=$table
                     AND UPPER(RDB\$INDICES.RDB\$INDEX_NAME)=$index_name
                     AND RDB\$RELATION_CONSTRAINTS.RDB\$CONSTRAINT_TYPE IS NULL
                ORDER BY RDB\$INDEX_SEGMENTS.RDB\$FIELD_POSITION;";
        $result = $db->query($query);
        if (PEAR::isError($result)) {
            return $result;
        }

        $index = $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        if (empty($index)) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'it was not specified an existing table index', __FUNCTION__);
        }

        $fields = array();
        do {
            $row = array_change_key_case($row, CASE_LOWER);
            $fields[] = $row['field_name'];
        } while (is_array($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)));
        $result->free();

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $fields = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $fields);
        }

        $definition = array();
        foreach ($fields as $field) {
            $definition['fields'][$field] = array();
            //collation?!?
            /*
            if (!empty($row['collation'])) {
                $definition['fields'][$field]['sorting'] = ($row['collation'] == 'A'
                    ? 'ascending' : 'descending');
            }
            */
        }
        return $definition;
    }

    // }}}
    // {{{ getTableConstraintDefinition()

    /**
     * Get the stucture of a constraint into an array
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
        $table = $db->quote(strtoupper($table), 'text');
        $index_name = $db->quote(strtoupper($db->getIndexName($index_name)), 'text');
        $query = "SELECT RDB\$INDEX_SEGMENTS.RDB\$FIELD_NAME AS field_name,
                         RDB\$INDICES.RDB\$UNIQUE_FLAG AS unique_flag,
                         RDB\$INDICES.RDB\$FOREIGN_KEY AS foreign_key,
                         RDB\$INDICES.RDB\$DESCRIPTION AS description,
                         RDB\$RELATION_CONSTRAINTS.RDB\$CONSTRAINT_TYPE AS constraint_type
                    FROM RDB\$INDEX_SEGMENTS
               LEFT JOIN RDB\$INDICES ON RDB\$INDICES.RDB\$INDEX_NAME = RDB\$INDEX_SEGMENTS.RDB\$INDEX_NAME
               LEFT JOIN RDB\$RELATION_CONSTRAINTS ON RDB\$RELATION_CONSTRAINTS.RDB\$INDEX_NAME = RDB\$INDEX_SEGMENTS.RDB\$INDEX_NAME
                   WHERE UPPER(RDB\$INDICES.RDB\$RELATION_NAME)=$table
                     AND UPPER(RDB\$INDICES.RDB\$INDEX_NAME)=$index_name
                ORDER BY RDB\$INDEX_SEGMENTS.RDB\$FIELD_POSITION;";
        $result = $db->query($query);
        if (PEAR::isError($result)) {
            return $result;
        }

        $index = $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        if (empty($index)) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'it was not specified an existing table constraint', __FUNCTION__);
        }
        $fields = array();
        do {
            $row = array_change_key_case($row, CASE_LOWER);
            $fields[] = $row['field_name'];
        } while (is_array($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)));
        $result->free();

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $fields = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $fields);
        }

        $definition = array();
        if ($index['constraint_type'] == 'PRIMARY KEY') {
            $definition['primary'] = true;
        } elseif ($index['unique_flag']) {
            $definition['unique'] = true;
        } elseif ($index['foreign_key']) {
            $definition['foreign'] = true;
        }
        if (!$index['unique_flag'] && !$index['foreign_key']) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'it was not specified an existing table constraint', __FUNCTION__);
        }
        foreach ($fields as $field) {
            $definition['fields'][$field] = array();
            //collation?!?
            /*
            if (!empty($row['collation'])) {
                $definition['fields'][$field]['sorting'] = ($row['collation'] == 'A'
                    ? 'ascending' : 'descending');
            }
            */
        }
        return $definition;
    }

    // }}}
    // {{{ getTriggerDefinition()

    /**
     * Get the stucture of an trigger into an array
     *
     * @param string    $trigger    name of trigger that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function getTriggerDefinition($trigger)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $trigger = $db->quote(strtoupper($trigger), 'text');
        $query = "SELECT RDB\$TRIGGER_NAME AS trigger_name,
                         RDB\$RELATION_NAME AS table_name,
                         RDB\$TRIGGER_SOURCE AS trigger_body,
                         RDB\$TRIGGER_TYPE AS trigger_type,
                         RDB\$DESCRIPTION AS comment
                    FROM RDB\$TRIGGERS
                   WHERE UPPER(RDB\$TRIGGER_NAME)=$trigger";
        return $db->queryRow();
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (is_string($result)) {
            /*
             * Probably received a table name.
             * Create a result resource identifier.
             */
            $id =& $db->_doQuery('SELECT * FROM '.$db->quoteIdentifier($result).' WHERE 1=0', false);
            if (PEAR::isError($id)) {
                return $id;
            }
            $got_string = true;
        } elseif (MDB2::isResultCommon($result)) {
            /*
             * Probably received a result object.
             * Extract the result resource identifier.
             */
            $id = $result->getResource();
            $got_string = false;
        } else {
            /*
             * Probably received a result resource identifier.
             * Copy it.
             * Deprecated.  Here for compatibility only.
             */
            $id = $result;
            $got_string = false;
        }

        if (!is_resource($id)) {
            return $db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                'Could not generate result ressource', __FUNCTION__);
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

        $count = @ibase_num_fields($id);
        $res   = array();

        if ($mode) {
            $res['num_fields'] = $count;
        }

        $db->loadModule('Datatype', null, true);
        for ($i = 0; $i < $count; $i++) {
            $info = @ibase_field_info($id, $i);
            if (($pos = strpos($info['type'], '(')) !== false) {
                $info['type'] = substr($info['type'], 0, $pos);
            }
            $res[$i] = array(
                'table'  => $got_string ? $case_func($result) : '',
                'name'   => $case_func($info['name']),
                'type'   => $info['type'],
                'length' => $info['length'],
                'flags'  => ($got_string)
                            ? $this->_ibaseFieldFlags($info['name'], $result) : '',
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

        // free the result only if we were called on a table
        if ($got_string) {
            @ibase_free_result($id);
        }
        return $res;
    }

    // }}}
    // {{{ _ibaseFieldFlags()

    /**
     * Get the column's flags
     *
     * Supports "primary_key", "unique_key", "not_null", "default",
     * "computed" and "blob".
     *
     * @param string $field_name  the name of the field
     * @param string $table_name  the name of the table
     *
     * @return string  the flags
     *
     * @access protected
     */
    function _ibaseFieldFlags($field_name, $table_name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = 'SELECT R.RDB$CONSTRAINT_TYPE CTYPE'
               .' FROM RDB$INDEX_SEGMENTS I'
               .'  JOIN RDB$RELATION_CONSTRAINTS R ON I.RDB$INDEX_NAME=R.RDB$INDEX_NAME'
               .' WHERE I.RDB$FIELD_NAME=\'' . $field_name . '\''
               .'  AND UPPER(R.RDB$RELATION_NAME)=\'' . strtoupper($table_name) . '\'';

        $result =& $db->_doQuery($query, false);
        if (PEAR::isError($result)) {
            return $result;
        }

        $flags = '';
        if ($obj = @ibase_fetch_object($result)) {
            @ibase_free_result($result);
            if (isset($obj->CTYPE)  && trim($obj->CTYPE) == 'PRIMARY KEY') {
                $flags.= 'primary_key ';
            }
            if (isset($obj->CTYPE)  && trim($obj->CTYPE) == 'UNIQUE') {
                $flags.= 'unique_key ';
            }
        }

        $query = 'SELECT R.RDB$NULL_FLAG AS NFLAG,'
               .'  R.RDB$DEFAULT_SOURCE AS DSOURCE,'
               .'  F.RDB$FIELD_TYPE AS FTYPE,'
               .'  F.RDB$COMPUTED_SOURCE AS CSOURCE'
               .' FROM RDB$RELATION_FIELDS R '
               .'  JOIN RDB$FIELDS F ON R.RDB$FIELD_SOURCE=F.RDB$FIELD_NAME'
               .' WHERE UPPER(R.RDB$RELATION_NAME)=\'' . strtoupper($table_name) . '\''
               .'  AND R.RDB$FIELD_NAME=\'' . $field_name . '\'';

        $result =& $db->_doQuery($query, false);
        if (PEAR::isError($result)) {
            return $result;
        }

        if ($obj = @ibase_fetch_object($result)) {
            @ibase_free_result($result);
            if (isset($obj->NFLAG)) {
                $flags.= 'not_null ';
            }
            if (isset($obj->DSOURCE)) {
                $flags.= 'default ';
            }
            if (isset($obj->CSOURCE)) {
                $flags.= 'computed ';
            }
            if (isset($obj->FTYPE)  && $obj->FTYPE == 261) {
                $flags.= 'blob ';
            }
        }

        return trim($flags);
    }
}
?>