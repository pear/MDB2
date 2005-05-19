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
// | Author: Lukas Smith <smith@backendmedia.com>                         |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'MDB2/LOB.php';

/**
 * @package  MDB2
 * @category Database
 * @author   Lukas Smith <smith@backendmedia.com>
 */

/**
 * MDB2_Driver_Common: Base class that is extended by each MDB2 driver
 *
 * @package MDB2
 * @category Database
 * @author Lukas Smith <smith@backendmedia.com>
 */
class MDB2_Driver_Datatype_Common
{
    var $valid_types = array(
        'text'      => true,
        'boolean'   => true,
        'integer'   => true,
        'decimal'   => true,
        'float'     => true,
        'date'      => true,
        'time'      => true,
        'timestamp' => true,
        'clob'      => true,
        'blob'      => true,
    );
    var $db_index;

    /**
     * contains all LOB objects created with this MDB2 instance
    * @var array
    * @access protected
    */
    var $lobs = array();

    // {{{ constructor

    /**
     * Constructor
     */
    function __construct($db_index)
    {
        $this->db_index = $db_index;
    }

    function MDB2_Driver_Datatype_Common($db_index)
    {
        $this->__construct($db_index);
    }

    // }}}
    // {{{ setResultTypes()

    /**
     * Define the list of types to be associated with the columns of a given
     * result set.
     *
     * This function may be called before invoking fetchRow(), fetchOne()
     * fetchCole() and fetchAll() so that the necessary data type
     * conversions are performed on the data to be retrieved by them. If this
     * function is not called, the type of all result set columns is assumed
     * to be text, thus leading to not perform any conversions.
     *
     * @param resource $result result identifier
     * @param string $types array variable that lists the
     *       data types to be expected in the result set columns. If this array
     *       contains less types than the number of columns that are returned
     *       in the result set, the remaining columns are assumed to be of the
     *       type text. Currently, the types clob and blob are not fully
     *       supported.
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function setResultTypes(&$result, $types)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];

        if (!is_array($types)) {
            $types = array($types);
        } else {
            $types = array_values($types);
        }
        foreach ($types as $key => $type) {
            if (!isset($this->valid_types[$type])) {
                return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                    'setResultTypes: ' . $type . ' is not a supported column type');
            }
        }
        $result->types = $types;
        return MDB2_OK;
    }

    // }}}
    // {{{ _baseConvertResult()

    /**
     * general type conversion method
     *
     * @param mixed $value refernce to a value to be converted
     * @param int $type constant that specifies which type to convert to
     * @return object a MDB2 error on failure
     * @access protected
     */
    function _baseConvertResult($value, $type)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        switch ($type) {
        case 'text':
            return $value;
        case 'integer':
            return intval($value);
        case 'boolean':
            return $value == 'Y';
        case 'decimal':
            return $value;
        case 'float':
            return doubleval($value);
        case 'date':
            return $value;
        case 'time':
            return $value;
        case 'timestamp':
            return $value;
        case 'clob':
        case 'blob':
            $this->lobs[] = array(
                'buffer' => null,
                'position' => 0,
                'lob_index' => null,
                'endOfLOB' => false,
                'ressource' => $value,
                'value' => null,
            );
            end($this->lobs);
            $lob_index = key($this->lobs);
            $this->lobs[$lob_index]['lob_index'] = $lob_index;
            return fopen('MDB2LOB://'.$lob_index.'@'.$this->db_index, 'r+');
        default:
            return $db->raiseError(MDB2_ERROR_INVALID, null, null,
                'attempt to convert result value to an unknown type ' . $type);
        }
    }

    // }}}
    // {{{ convertResult()

    /**
     * convert a value to a RDBMS indepdenant MDB2 type
     *
     * @param mixed $value value to be converted
     * @param int $type constant that specifies which type to convert to
     * @return mixed converted value or a MDB2 error on failure
     * @access public
     */
    function convertResult($value, $type)
    {
        if (is_null($value)) {
            return null;
        }
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return $this->_baseConvertResult($value, $type);
    }

    // }}}
    // {{{ convertResultRow()

    /**
     * convert a result row
     *
     * @param resource $result result identifier
     * @param array $row array with data
     * @return mixed MDB2_OK on success,  a MDB2 error on failure
     * @access public
     */
    function convertResultRow($types, $row)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if (is_array($types)) {
            $current_column = -1;
            foreach ($row as $key => $column) {
                ++$current_column;
                if (!isset($column)
                   || !isset($types[$current_column])
                ) {
                    continue;
                }
                $value = $this->convertResult($row[$key], $types[$current_column]);
                if (PEAR::isError($value)) {
                    return $value;
                }
                $row[$key] = $value;
            }
        }
        return $row;
    }

    // }}}
    // {{{ getDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare
     * of the given type
     *
     * @param string $type type to which the value should be converted to
     * @param string  $name   name the field to be declared.
     * @param string  $field  definition of the field
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getDeclaration($type, $name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if (!method_exists($this, "_get{$type}Declaration")) {
            return $db->raiseError('type not defined: '.$type);
        }
        return $this->{"_get{$type}Declaration"}($name, $field);
    }

    // }}}
    // {{{ _getIntegerDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       unsigned
     *           Boolean flag that indicates whether the field should be
     *           declared as unsigned integer if possible.
     *
     *       default
     *           Integer value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getIntegerDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if (isset($field['unsigned']) && $field['unsigned']) {
            $db->warnings[] = "unsigned integer field \"$name\" is being declared as signed integer";
        }
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'integer') : '';
        $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';
        return $name.' INT'.$default.$notnull;
    }

    // }}}
    // {{{ _getTextDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       length
     *           Integer value that determines the maximum length of the text
     *           field. If this argument is missing the field should be
     *           declared to have the longest length allowed by the DBMS.
     *
     *       default
     *           Text value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getTextDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'text') : '';
        $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';
        $type = isset($field['length']) ? 'CHAR ('.$field['length'].')' : 'TEXT';
        return $name.' '.$type.$default.$notnull;
    }

    // }}}
    // {{{ _getCLOBDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an character
     * large object type field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       length
     *           Integer value that determines the maximum length of the large
     *           object field. If this argument is missing the field should be
     *           declared to have the longest length allowed by the DBMS.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getCLOBDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';
        $type = isset($field['length']) ? 'CHAR ('.$field['length'].')' : 'TEXT';
        return $name.' '.$type.$notnull;
    }

    // }}}
    // {{{ _getBLOBDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an binary large
     * object type field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       length
     *           Integer value that determines the maximum length of the large
     *           object field. If this argument is missing the field should be
     *           declared to have the longest length allowed by the DBMS.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getBLOBDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';
        $type = isset($field['length']) ? 'CHAR ('.$field['length'].')' : 'TEXT';
        return $name.' '.$type.$notnull;
    }

    // }}}
    // {{{ _getBooleanDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a boolean type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Boolean value to be used as default for this field.
     *
     *       notnullL
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getBooleanDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'boolean') : '';
        $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';
        return $name.' CHAR (1)'.$default.$notnull;
    }

    // }}}
    // {{{ _getDateDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a date type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Date value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getDateDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'date') : '';
        $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';
        return $name.' CHAR ('.strlen('YYYY-MM-DD').')'.$default.$notnull;
    }

    // }}}
    // {{{ _getTimestampDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a timestamp
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Timestamp value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getTimestampDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'timestamp') : '';
        $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';
        return $name.' CHAR ('.strlen('YYYY-MM-DD HH:MM:SS').')'.$default.$notnull;
    }

    // }}}
    // {{{ _getTimeDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a time
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Time value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getTimeDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'time') : '';
        $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';
        return $name.' CHAR ('.strlen('HH:MM:SS').')'.$default.$notnull;
    }

    // }}}
    // {{{ _getFloatDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a float type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Float value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getFloatDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'float') : '';
        $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';
        return $name.' TEXT'.$default.$notnull;
    }

    // }}}
    // {{{ _getDecimalDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a decimal type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Decimal value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access protected
     */
    function _getDecimalDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'decimal') : '';
        $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';
        return $name.' TEXT'.$default.$notnull;
    }

    // }}}
    // {{{ compareDefinition()

    /**
     * Obtain an array of changes that may need to applied
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containg all changes that will need to be applied
     * @access public
     */
    function compareDefinition($current, $previous)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $type = isset($current['type']) ? $current['type'] : null;

        if (!method_exists($this, "_compare{$type}Definition")) {
            return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'type "'.$current['type'].'" is not yet supported');
        }

        if (!isset($previous['type']) || $previous['type'] != $type) {
            return $current;
        }

        $change = $this->{"_compare{$type}Definition"}($current, $previous);
        $previous_notnull = isset($previous['notnull']);
        $notnull = isset($current['notnull']);
        if ($previous_notnull != $notnull) {
            $change['changed_not_null'] = true;
            if ($notnull) {
                $change['notnull'] = isset($current['notnull']);
            }
        }

        $previous_default = isset($previous['default']);
        $default = isset($current['default']);
        if ($previous_default != $default) {
            $change['changed_default'] = true;
            if ($default) {
                $change['default'] = $current['default'];
            }
        } elseif ($default && $previous['default']!= $current['default']) {
            $change['changed_default'] = true;
            $change['default'] = $current['default'];
        }

        return $change;
    }

    // }}}
    // {{{ _compareIntegerDefinition()

    /**
     * Obtain an array of changes that may need to applied to an integer field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containg all changes that will need to be applied
     * @access protected
     */
    function _compareIntegerDefinition($current, $previous)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $change = array();
        $previous_unsigned = isset($previous['unsigned']);
        $unsigned = isset($current['unsigned']);
        if ($previous_unsigned != $unsigned) {
            $change['unsigned'] = $unsigned;
        }
        return $change;
    }

    // }}}
    // {{{ _compareTextDefinition()

    /**
     * Obtain an array of changes that may need to applied to an text field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containg all changes that will need to be applied
     * @access protected
     */
    function _compareTextDefinition($current, $previous)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $change = array();
        $previous_length = (isset($previous['length']) ? $previous['length'] : 0);
        $length = (isset($current['length']) ? $current['length'] : 0);
        if ($previous_length != $length) {
            $change['length'] = $length;
        }
        return $change;
    }

    // }}}
    // {{{ _compareCLOBDefinition()

    /**
     * Obtain an array of changes that may need to applied to an CLOB field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containg all changes that will need to be applied
     * @access protected
     */
    function _compareCLOBDefinition($current, $previous)
    {
        return $this->_compareTextDefinition($current, $previous);
    }

    // }}}
    // {{{ _compareBLOBDefinition()

    /**
     * Obtain an array of changes that may need to applied to an BLOB field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containg all changes that will need to be applied
     * @access protected
     */
    function _compareBLOBDefinition($current, $previous)
    {
        return $this->_compareTextDefinition($current, $previous);
    }

    // }}}
    // {{{ _compareDateDefinition()

    /**
     * Obtain an array of changes that may need to applied to an date field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containg all changes that will need to be applied
     * @access protected
     */
    function _compareDateDefinition($current, $previous)
    {
        return array();
    }

    // }}}
    // {{{ _compareTimeDefinition()

    /**
     * Obtain an array of changes that may need to applied to an time field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containg all changes that will need to be applied
     * @access protected
     */
    function _compareTimeDefinition($current, $previous)
    {
        return array();
    }

    // }}}
    // {{{ _compareTimestampDefinition()

    /**
     * Obtain an array of changes that may need to applied to an timestamp field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containg all changes that will need to be applied
     * @access protected
     */
    function _compareTimestampDefinition($current, $previous)
    {
        return array();
    }

    // }}}
    // {{{ _compareBooleanDefinition()

    /**
     * Obtain an array of changes that may need to applied to an boolean field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containg all changes that will need to be applied
     * @access protected
     */
    function _compareBooleanDefinition($current, $previous)
    {
        return array();
    }

    // }}}
    // {{{ _compareFloatDefinition()

    /**
     * Obtain an array of changes that may need to applied to an float field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containg all changes that will need to be applied
     * @access protected
     */
    function _compareFloatDefinition($current, $previous)
    {
        return array();
    }

    // }}}
    // {{{ _compareDecimalDefinition()

    /**
     * Obtain an array of changes that may need to applied to an decimal field
     *
     * @param array $current new definition
     * @param array  $previous old definition
     * @return array  containg all changes that will need to be applied
     * @access protected
     */
    function _compareDecimalDefinition($current, $previous)
    {
        return array();
    }

    // }}}
    // {{{ quote()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param string $type type to which the value should be converted to
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access public
     */
    function quote($value, $type = null, $quote = true)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if (is_null($value)
            || ($value === '' && $db->options['portability'] & MDB2_PORTABILITY_EMPTY_TO_NULL)
        ) {
            if (!$quote) {
                return null;
            }
            return 'NULL';
        } elseif (is_null($type)) {
            switch (gettype($value)) {
            case 'integer':
                $type = 'integer';
                break;
            case 'double':
                // todo
                $type = 'decimal';
                $type = 'float';
                break;
            case 'boolean':
                $type = 'boolean';
                break;
            case 'array':
            case 'object':
                 $type = 'text';
                break;
            default:
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
                    $type = 'timestamp';
                } elseif (preg_match('/^\d{2}:\d{2}$/', $value)) {
                    $type = 'time';
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    $type = 'date';
                } else {
                    $type = 'text';
                }
                break;
            }
        }

        if (!method_exists($this, "_quote{$type}")) {
            return $db->raiseError('type not defined: '.$type);
        }
        $value = $this->{"_quote{$type}"}($value);
        if (!$quote && isset($value[0]) && $value[0] === "'") {
            $value = substr($value, 1, -1);
        }
        return $value;
    }

    // }}}
    // {{{ _quoteInteger()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteInteger($value)
    {
        return (int)$value;
    }

    // }}}
    // {{{ _quoteText()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that already contains any DBMS specific
     *       escaped character sequences.
     * @access protected
     */
    function _quoteText($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return "'".$db->escape($value)."'";
    }

    // }}}
    // {{{ _readFile()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param  $value
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _readFile($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $close = false;
        if (preg_match('/^(\w+:\/\/)(.*)$/', $value, $match)) {
            $close = true;
            if ($match[1] == 'file://') {
                $value = $match[2];
            }
            $value = @fopen($value, 'r');
        }

        if (is_resource($value)) {
            $fp = $value;
            $value = '';
            while (!@feof($fp)) {
                $value .= @fread($fp, $db->options['lob_buffer_length']);
            }
            if ($close) {
                @fclose($fp);
            }
        }

        return $value;
    }

    // }}}
    // {{{ _quoteLOB()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param  $value
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteLOB($value)
    {
        $value = $this->_readFile($value);
        return $this->_quoteText($value);
    }

    // }}}
    // {{{ _quoteCLOB()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param  $value
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteCLOB($value)
    {
        return $this->_quoteLOB($value);
    }

    // }}}
    // {{{ _quoteBLOB()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param  $value
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteBLOB($value)
    {
        return $this->_quoteLOB($value);
    }

    // }}}
    // {{{ _quoteBoolean()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteBoolean($value)
    {
        return ($value ? "'Y'" : "'N'");
    }

    // }}}
    // {{{ _quoteDate()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteDate($value)
    {
       return $this->_quoteText($value);
    }

    // }}}
    // {{{ _quoteTimestamp()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteTimestamp($value)
    {
       return $this->_quoteText($value);
    }

    // }}}
    // {{{ _quoteTime()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     *       compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteTime($value)
    {
       return $this->_quoteText($value);
    }

    // }}}
    // {{{ _quoteFloat()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteFloat($value)
    {
       return $this->_quoteText($value);
    }

    // }}}
    // {{{ _quoteDecimal()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteDecimal($value)
    {
       return $this->_quoteText($value);
    }

    // }}}
    // {{{ writeLOBToFile()

    /**
     * retrieve LOB from the database
     *
     * @param int $lob handle to a lob created by the createLOB() function
     * @param string $file name of the file into which the LOb should be fetched
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access protected
     */
    function writeLOBToFile($lob, $file)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $fp = fopen($file, 'wb');
        while (!feof($lob)) {
            $result = fread($lob, $db->options['lob_buffer_length']);
            $read = strlen($result);
            if (fwrite($fp, $result, $read)!= $read) {
                return MDB2::raiseError(MDB2_ERROR, null, null,
                    'writeLOBToFile: could not write to the output file');
            }
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ _retrieveLOB()

    /**
     * retrieve LOB from the database
     *
     * @param int $lob handle to a lob created by the createLOB() function
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access protected
     */
    function _retrieveLOB(&$lob)
    {
        if (is_null($lob['value'])) {
            $lob['value'] = $lob['ressource'];
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ readLOB()

    /**
     * Read data from large object input stream.
     *
     * @param integer $lob argument handle that is returned by the
     *                          MDB2::createLOB() method.
     * @param string $data reference to a variable that will hold data
     *                          to be read from the large object input stream
     * @param integer $length    value that indicates the largest ammount ofdata
     *                          to be read from the large object input stream.
     * @return mixed the effective number of bytes read from the large object
     *                      input stream on sucess or an MDB2 error object.
     * @access public
     * @see endOfLOB()
     */
    function _readLOB($lob, $length)
    {
        return substr($lob['value'], $lob['position'], $length);
    }

    // }}}
    // {{{ _endOfLOB()

    /**
     * Determine whether it was reached the end of the large object and
     * therefore there is no more data to be read for the its input stream.
     *
     * @param int    $lob handle to a lob created by the createLOB() function
     * @return mixed true or false on success, a MDB2 error on failure
     * @access protected
     */
    function _endOfLOB($lob)
    {
        return $lob['endOfLOB'];
    }

    // }}}
    // {{{ destroyLOB()

    /**
     * Free any resources allocated during the lifetime of the large object
     * handler object.
     *
     * @param integer $lob argument handle that is returned by the
     *                          MDB2::createLOB() method.
     * @access public
     */
    function destroyLOB($lob)
    {
        $lob_data = stream_get_meta_data($lob);
        $lob_index = $lob_data['wrapper_data']->lob_index;
        if (isset($this->lobs[$lob_index])) {
            $this->_destroyLOB($lob_index);
            unset($this->lobs[$lob_index]);
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ _destroyLOB()

    /**
     * Free any resources allocated during the lifetime of the large object
     * handler object.
     *
     * @param integer $lob argument handle that is returned by the
     *                          MDB2::createLOB() method.
     * @access private
     */
    function _destroyLOB($lob_index)
    {
        return MDB2_OK;
    }

    // }}}
    // {{{ implodeArray()

    /**
     * apply a type to all values of an array and return as a comma seperated string
     * useful for generating IN statements
     *
     * @access public
     *
     * @param array $array data array
     * @param string $type determines type of the field
     *
     * @return string comma seperated values
     */
    function implodeArray($array, $type = false)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if (!is_array($array) || empty($array)) {
            return 'NULL';
        }
        if ($type) {
            foreach ($array as $value) {
                $return[] = $db->quote($value, $type);
            }
        } else {
            $return = $array;
        }
        return implode(', ', $return);
    }
}

?>