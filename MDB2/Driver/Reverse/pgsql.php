<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
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
// | Author: Paul Cooper <pgc@ucecom.com>                                 |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'MDB2/Driver/Reverse/Common.php';

/**
 * MDB2 PostGreSQL driver for the schema reverse engineering module
 *
 * @package MDB2
 * @category Database
 * @author  Paul Cooper <pgc@ucecom.com>
 */
class MDB2_Driver_Reverse_pgsql extends MDB2_Driver_Reverse_common
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
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $columns = $db->queryRow("SELECT
                    attnum,attname,typname,attlen,attnotnull,
                    atttypmod,usename,usesysid,pg_class.oid,relpages,
                    reltuples,relhaspkey,relhasrules,relacl,adsrc
                    FROM pg_class,pg_user,pg_type,
                         pg_attribute left outer join pg_attrdef on
                         pg_attribute.attrelid=pg_attrdef.adrelid
                    WHERE (pg_class.relname='$table')
                        and (pg_class.oid=pg_attribute.attrelid)
                        and (pg_class.relowner=pg_user.usesysid)
                        and (pg_attribute.atttypid=pg_type.oid)
                        and attnum > 0
                        and attname = '$field_name'
                        ORDER BY attnum
                        ", null, MDB2_FETCHMODE_ASSOC);
        if (MDB2::isError($columns)) {
            return $columns;
        }
        $field_column = $columns['attname'];
        $type_column = $columns['typname'];
        $db_type = preg_replace('/\d/','', strtolower($type_column) );
        $length = $columns['attlen'];
        if ($length == -1) {
            $length = $columns['atttypmod']-4;
        }
        //$decimal = strtok('(), '); = eh?
        $type = array();
        switch ($db_type) {
        case 'int':
            $type[0] = 'integer';
            if ($length == '1') {
                $type[1] = 'boolean';
            }
            break;
        case 'text':
        case 'char':
        case 'varchar':
        case 'bool':
            $type[0] = 'boolean';
            break;
        case 'bpchar':
            $type[0] = 'text';

            if ($length == '1') {
                $type[1] = 'boolean';
            } elseif (strstr($db_type, 'text'))
                $type[1] = 'clob';
            break;
        case 'date':
            $type[0] = 'date';
            break;
        case 'datetime':
        case 'timestamp':
            $type[0] = 'timestamp';
            break;
        case 'time':
            $type[0] = 'time';
            break;
        case 'float':
        case 'double':
        case 'real':

            $type[0] = 'float';
            break;
        case 'decimal':
        case 'money':
        case 'numeric':
            $type[0] = 'decimal';
            break;
        case 'oid':
        case 'tinyblob':
        case 'mediumblob':
        case 'longblob':
        case 'blob':
            $type[0] = 'blob';
            $type[1] = 'text';
            break;
        case 'year':
            $type[0] = 'integer';
            $type[1] = 'date';
            break;
        default:
            return $db->raiseError(MDB2_ERROR, null, null,
                'getTableFieldDefinition: unknown database attribute type');
        }

        if ($columns['attnotnull'] == 'f') {
            $notnull = true;
        }

        if (!preg_match("/nextval\('([^']+)'/",$columns['adsrc']))  {
            $default = substr($columns['adsrc'],1,-1);
        }
        $definition = array();
        for ($field_choices = array(), $datatype = 0; $datatype < count($type); $datatype++) {
            $field_choices[$datatype] = array('type' => $type[$datatype]);
            if (isset($notnull)) {
                $field_choices[$datatype]['notnull'] = true;
            }
            if (isset($default)) {
                $field_choices[$datatype]['default'] = $default;
            }
            if ($type[$datatype] != 'boolean'
                && $type[$datatype] != 'time'
                && $type[$datatype] != 'date'
                && $type[$datatype] != 'timestamp'
            ) {
                if (strlen($length)) {
                    $field_choices[$datatype]['length'] = $length;
                }
            }
        }
        $definition[0] = $field_choices;
        if (preg_match("/nextval\('([^']+)'/",$columns['adsrc'],$nextvals)) {
            $implicit_sequence = array();
            $implicit_sequence['on'] = array();
            $implicit_sequence['on']['table'] = $table;
            $implicit_sequence['on']['field'] = $field_name;
            $definition[1]['name'] = $nextvals[1];
            $definition[1]['definition'] = $implicit_sequence;
        }

        // check that its not just a unique field
        if (MDB2::isError($indexes = $db->queryAll("SELECT
                oid,indexrelid,indrelid,indkey,indisunique,indisprimary
                FROm pg_index, pg_class
                WHERE (pg_class.relname='$table')
                    AND (pg_class.oid=pg_index.indrelid)", null, MDB2_FETCHMODE_ASSOC))) {
            return $indexes;
        }
        $indkeys = explode(' ',$indexes['indkey']);
        if (in_array($columns['attnum'],$indkeys)) {
            // doesnt look like queryAll should be used here
            if (MDB2::isError($indexname = $db->queryAll("SELECT
                    relname FROM pg_class WHERE oid={$columns['indexrelid']}", null))
            ) {
                return $indexname;
            }
            $is_primary = ($indexes['isdisprimary'] == 't') ;
            $is_unique = ($indexes['isdisunique'] == 't') ;

            $implicit_index = array();
            $implicit_index['unique'] = true;
            $implicit_index['fields'][$field_name] = $indexname['relname'];
            $definition[2]['name'] = $field_name;
            $definition[2]['definition'] = $implicit_index;
        }
        return $definition;
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
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $query = "SELECT * from pg_index, pg_class
                                WHERE (pg_class.relname='$index_name')
                                AND (pg_class.oid=pg_index.indexrelid)";
        $row = $db->queryRow($query, null, MDB2_FETCHMODE_ASSOC);
        if (MDB2::isError($row)) {
            return $row;
        }
        if ($row['relname'] != $index_name) {
            return $db->raiseError(MDB2_ERROR, null, null,
                'getTableIndexDefinition: it was not specified an existing table index');
        }

        $db->loadModule('manager');
        $columns = $db->manager->listTableFields($table);

        $definition = array();
        if ($row['indisunique'] == 't') {
            $definition['unique'] = true;
        }

        $index_column_numbers = explode(' ', $row['indkey']);

        foreach ($index_column_numbers as $number) {
            $definition['fields'][$columns[($number - 1)]] = array('sorting' => 'ascending');
        }
        return $definition;
    }


    // }}}
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
            $id = @pg_exec($db->connection, "SELECT * FROM $result LIMIT 0");
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

        $count = @pg_numfields($id);

        // made this IF due to performance (one if is faster than $count if's)
        if (!$mode) {

            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = $got_string ? $case_func($result) : '';
                $res[$i]['name']  = $case_func(@pg_fieldname($id, $i));
                $res[$i]['type']  = @pg_fieldtype($id, $i);
                $res[$i]['len']   = @pg_fieldsize($id, $i);
                $res[$i]['flags'] = $got_string ? $this->_pgFieldflags($id, $i, $result) : '';
            }

        } else { // full
            $res['num_fields']= $count;

            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = $got_string ? $case_func($result) : '';
                $res[$i]['name']  = $case_func(@pg_fieldname($id, $i));
                $res[$i]['type']  = @pg_fieldtype($id, $i);
                $res[$i]['len']   = @pg_fieldsize($id, $i);
                $res[$i]['flags'] = $got_string ? $this->_pgFieldFlags($id, $i, $result) : '';

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
            @pg_freeresult($id);
        }
        return $res;
    }

    // }}}
    // {{{ _pgFieldFlags()

    /**
     * Flags of a Field
     *
     * @param int $resource PostgreSQL result identifier
     * @param int $num_field the field number
     *
     * @return string The flags of the field ("not_null", "default_value",
     *                "primary_key", "unique_key" and "multiple_key"
     *                are supported).  The default value is passed
     *                through rawurlencode() in case there are spaces in it.
     * @access private
     */
    function _pgFieldFlags($resource, $num_field, $table_name)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];

        $field_name = @pg_fieldname($resource, $num_field);

        $result = @pg_exec($db->connection, "SELECT f.attnotnull, f.atthasdef
                                FROM pg_attribute f, pg_class tab, pg_type typ
                                WHERE tab.relname = typ.typname
                                AND typ.typrelid = f.attrelid
                                AND f.attname = '$field_name'
                                AND tab.relname = '$table_name'");
        if (@pg_numrows($result) > 0) {
            $row = @pg_fetch_row($result, 0);
            $flags  = ($row[0] == 't') ? 'not_null ' : '';

            if ($row[1] == 't') {
                $result = @pg_exec($db->connection, "SELECT a.adsrc
                                    FROM pg_attribute f, pg_class tab, pg_type typ, pg_attrdef a
                                    WHERE tab.relname = typ.typname AND typ.typrelid = f.attrelid
                                    AND f.attrelid = a.adrelid AND f.attname = '$field_name'
                                    AND tab.relname = '$table_name' AND f.attnum = a.adnum");
                $row = @pg_fetch_row($result, 0);
                $num = preg_replace("/'(.*)'::\w+/", "\\1", $row[0]);
                $flags .= 'default_' . rawurlencode($num) . ' ';
            }
        } else {
            $flags = '';
        }
        $result = @pg_exec($db->connection, "SELECT i.indisunique, i.indisprimary, i.indkey
                                FROM pg_attribute f, pg_class tab, pg_type typ, pg_index i
                                WHERE tab.relname = typ.typname
                                AND typ.typrelid = f.attrelid
                                AND f.attrelid = i.indrelid
                                AND f.attname = '$field_name'
                                AND tab.relname = '$table_name'");
        $count = @pg_numrows($result);

        for ($i = 0; $i < $count ; $i++) {
            $row = @pg_fetch_row($result, $i);
            $keys = explode(' ', $row[2]);

            if (in_array($num_field + 1, $keys)) {
                $flags .= ($row[0] == 't' && $row[1] == 'f') ? 'unique_key ' : '';
                $flags .= ($row[1] == 't') ? 'primary_key ' : '';
                if (count($keys) > 1)
                    $flags .= 'multiple_key ';
            }
        }

        return trim($flags);
    }
}
?>