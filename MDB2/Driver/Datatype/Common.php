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
     * @access private
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
            $db->lobs[] = array(
                'value' => $value,
                'position' => 0
            );
            end($db->lobs);
            $lob = key($db->lobs);
            $dst_lob = array(
                'database' => &$db,
                'type' => 'resultlob',
                'resultLOB' => $lob,
            );
            $lob = $this->createLOB($dst_lob);
            return $lob;
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
                if (MDB2::isError($value)) {
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
        if (method_exists($this, "_get{$type}Declaration")) {
            return $this->{"_get{$type}Declaration"}($name, $field);
        }
        return $db->raiseError('type not defined: '.$type);
    }

    // }}}
    // {{{ _getIntegerDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
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
     * @access private
     */
    function _getIntegerDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if (isset($field['unsigned'])) {
            $db->warnings[] = "unsigned integer field \"$name\" is being declared as signed integer";
        }
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'integer') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' INT'.$default.$notnull;
    }

    // }}}
    // {{{ _getTextDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
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
     * @access private
     */
    function _getTextDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'text') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
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
     * @param string $field associative array with the name of the properties
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
     * @access private
     */
    function _getCLOBDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
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
     * @param string $field associative array with the name of the properties
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
     * @access private
     */
    function _getBLOBDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
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
     * @param string $field associative array with the name of the properties
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
     * @access private
     */
    function _getBooleanDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'boolean') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' CHAR (1)'.$default.$notnull;
    }

    // }}}
    // {{{ _getDateDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a date type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
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
     * @access private
     */
    function _getDateDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'date') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' CHAR ('.strlen('YYYY-MM-DD').')'.$default.$notnull;
    }

    // }}}
    // {{{ _getTimestampDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a timestamp
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
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
     * @access private
     */
    function _getTimestampDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'timestamp') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' CHAR ('.strlen('YYYY-MM-DD HH:MM:SS').')'.$default.$notnull;
    }

    // }}}
    // {{{ _getTimeDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a time
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
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
     * @access private
     */
    function _getTimeDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'time') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' CHAR ('.strlen('HH:MM:SS').')'.$default.$notnull;
    }

    // }}}
    // {{{ _getFloatDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a float type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
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
     * @access private
     */
    function _getFloatDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'float') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' TEXT'.$default.$notnull;
    }

    // }}}
    // {{{ _getDecimalDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a decimal type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
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
     * @access private
     */
    function _getDecimalDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'decimal') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' TEXT'.$default.$notnull;
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
    function quote($value, $type = null)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if (is_null($value)
            || ($value === '' && $db->options['portability'] & MDB2_PORTABILITY_EMPTY_TO_NULL)
        ) {
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

        if (method_exists($this, "_quote{$type}")) {
            return $this->{"_quote{$type}"}($value);
        }
        return $db->raiseError('type not defined: '.$type);
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
     * @access private
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
     * @access private
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
     * @access private
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
     * @access private
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
     * @access private
     */
    function _quoteCLOB($value)
    {
        return $this->_quoteLOB($value);
    }

    // }}}
    // {{{ freeCLOBValue()

    /**
     * free a character large object
     *
     * @param int $value lob index
     * @access public
     */
    function freeCLOBValue($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        unset($db->lobs[$value]);
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
     * @access private
     */
    function _quoteBLOB($value)
    {
        return $this->_quoteLOB($value);
    }

    // }}}
    // {{{ freeBLOBValue()

    /**
     * free a binary large object
     *
     * @param int $value lob index
     * @access public
     */
    function freeBLOBValue($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        unset($db->lobs[$value]);
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
     * @access private
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
     * @access private
     */
    function _quoteDate($value)
    {
        return "'$value'";
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
     * @access private
     */
    function _quoteTimestamp($value)
    {
        return "'$value'";
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
     * @access private
     */
    function _quoteTime($value)
    {
        return "'$value'";
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
     * @access private
     */
    function _quoteFloat($value)
    {
        return "'$value'";
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
     * @access private
     */
    function _quoteDecimal($value)
    {
        return "'$value'";
    }

    // }}}
    // {{{ createLOB()

    /**
     * Create a handler object of a specified class with functions to
     * retrieve data from a large object data stream.
     *
     * @param array $arguments An associative array with parameters to create
     *                  the handler object. The array indexes are the names of
     *                  the parameters and the array values are the respective
     *                  parameter values.
     *
     *                  Some parameters are specific of the class of each type
     *                  of handler object that is created. The following
     *                  parameters are common to all handler object classes:
     *
     *                  type
     *
     *                      Name of the type of the built-in supported class
     *                      that will be used to create the handler object.
     *                      There are currently four built-in types of handler
     *                      object classes: data, resultlob, inputfile and
     *                      outputfile.
     *
     *                      The data handler class is the default class. It
     *                      simply reads data from a given data string.
     *
     *                      The resultlob handler class is meant to read data
     *                      from a large object retrieved from a query result.
     *                      This class is not used directly by applications.
     *
     *                      The inputfile handler class is meant to read data
     *                      from a file to use in prepared queries with large
     *                      object field parameters.
     *
     *                      The outputfile handler class is meant to write to
     *                      a file data from result columns with large object
     *                      fields. The functions to read from this type of
     *                      large object do not return any data. Instead, the
     *                      data is just written to the output file with the
     *                      data retrieved from a specified large object handle.
     *
     *                  class
     *
     *                      Name of the class of the handler object that will be
     *                      created if the Type argument is not specified. This
     *                      argument should be used when you need to specify a
     *                      custom handler class.
     *
     *                  database
     *
     *                      Database object as returned by MDB2::connect.
     *                      This is an option argument needed by some handler
     *                      classes like resultlob.
     *
     *                  The following arguments are specific of the inputfile
     *                  handler class:
     *
     *                      file
     *
     *                          Integer handle value of a file already opened
     *                          for writing.
     *
     *                      file_name
     *
     *                          Name of a file to be opened for writing if the
     *                          File argument is not specified.
     *
     *                  The following arguments are specific of the outputfile
     *                  handler class:
     *
     *                      file
     *
     *                          Integer handle value of a file already opened
     *                          for writing.
     *
     *                      file_name
     *
     *                          Name of a file to be opened for writing if the
     *                          File argument is not specified.
     *
     *                      buffer_length
     *
     *                          Integer value that specifies the length of a
     *                          buffer that will be used to read from the
     *                          specified large object.
     *
     *                      LOB
     *
     *                          Integer handle value that specifies a large
     *                          object from which the data to be stored in the
     *                          output file will be written.
     *
     *                      result
     *
     *                          Integer handle value as returned by the function
     *                          MDB2::query() or MDB2::execute() that specifies
     *                          the result set that contains the large object value
     *                          to be retrieved. If the LOB argument is specified,
     *                          this argument is ignored.
     *
     *                      row
     *
     *                          Integer value that specifies the number of the
     *                          row of the result set that contains the large
     *                          object value to be retrieved. If the LOB
     *                          argument is specified, this argument is ignored.
     *
     *                      field
     *
     *                          Integer or string value that specifies the
     *                          number or the name of the column of the result
     *                          set that contains the large object value to be
     *                          retrieved. If the LOB argument is specified,
     *                          this argument is ignored.
     *
     *                      binary
     *
     *                          Boolean value that specifies whether the large
     *                          object column to be retrieved is of binary type
     *                          (blob) or otherwise is of character type (clob).
     *                          If the LOB argument is specified, this argument
     *                          is ignored.
     *
     *                  The following argument is specific of the data
     *                  handler class:
     *
     *                  data
     *
     *                      String of data that will be returned by the class
     *                      when it requested with the readLOB() method.
     *
     *                  The following argument is specific of the resultlob
     *                  handler class:
     *
     *                      resultLOB
     *
     *                          Integer handle value of a large object result
     *                          row field.
     * @return integer handle value that should be passed as argument insubsequent
     * calls to functions that retrieve data from the large object input stream.
     * @access public
     */
    function createLOB($arguments)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $result = MDB2::loadFile('LOB');
        if (MDB2::isError($result)) {
            return $result;
        }
        $class_name = 'MDB2_LOB';
        if (isset($arguments['type'])) {
            switch ($arguments['type']) {
            case 'data':
                break;
            case 'resultlob':
                $class_name = 'MDB2_LOB_Result';
                break;
            case 'outputfile':
                $class_name = 'MDB2_LOB_Output_File';
                break;
            default:
                return $db->raiseError('createLOB: '.$arguments['type']
                    . ' is not a valid type of large object');
            }
        } else {
            if (isset($arguments['class'])) {
                $class = $arguments['class'];
            }
        }
        end($GLOBALS['_MDB2_LOBs']);
        $lob = key($GLOBALS['_MDB2_LOBs'])+1;
        $GLOBALS['_MDB2_LOBs'][$lob] =& new $class_name;
        $GLOBALS['_MDB2_LOBs'][$lob]->db = &$db;

        $result = $GLOBALS['_MDB2_LOBs'][$lob]->create($arguments);
        if (MDB2::isError($result)) {
            $GLOBALS['_MDB2_LOBs'][$lob]->db->datatype->destroyLOB($lob);
            return $result;
        }
        return $lob;
    }

    // }}}
    // {{{ setLOBFile()

    /**
     * retrieve LOB from the database
     *
     * @param int $lob handle to a lob created by the createLOB() function
     * @param string $file name of the file into which the LOb should be fetched
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    function setLOBFile($lob, $file)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $dst_lob = array(
            'database' => &$db,
            'LOB' => $lob,
            'file_name' => $file,
            'type' => 'outputfile'
        );
        return $this->createLOB($dst_lob);
    }

    // }}}
    // {{{ _retrieveLOB()

    /**
     * retrieve LOB from the database
     *
     * @param int $lob handle to a lob created by the createLOB() function
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    function _retrieveLOB($lob)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if (!isset($db->lobs[$lob])) {
            return $db->raiseError(MDB2_ERROR, null, null,
                'it was not specified a valid lob');
        }
        return MDB2_OK;
    }

    // }}}
    // {{{ _readResultLOB()

    /**
     * Read data from large object input stream.
     *
     * @param int $lob handle to a lob created by the createLOB() function
     * @param blob $data reference to a variable that will hold data to be
     *       read from the large object input stream
     * @param int $length integer value that indicates the largest ammount of
     *       data to be read from the large object input stream.
     * @return mixed length on success, a MDB2 error on failure
     * @access private
     */
    function _readResultLOB($lob, &$data, $length)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $lobresult = $this->_retrieveLOB($lob);
        if (MDB2::isError($lobresult)) {
            return $lobresult;
        }
        $length = min($length, strlen($db->lobs[$lob]['value']) - $db->lobs[$lob]['position']);
        $data = substr($db->lobs[$lob]['value'], $db->lobs[$lob]['position'], $length);
        $db->lobs[$lob]['position'] += $length;
        return $length;
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
    function readLOB($lob, &$data, $length)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return $GLOBALS['_MDB2_LOBs'][$lob]->readLOB($data, $length);
    }

    // }}}
    // {{{ _endOfResultLOB()

    /**
     * Determine whether it was reached the end of the large object and
     * therefore there is no more data to be read for the its input stream.
     *
     * @param int $lob handle to a lob created by the createLOB() function
     * @return mixed true or false on success, a MDB2 error on failure
     * @access private
     */
    function _endOfResultLOB($lob)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $lobresult = $this->_retrieveLOB($lob);
        if (MDB2::isError($lobresult)) {
            return $lobresult;
        }
        return $db->lobs[$lob]['position'] >= strlen($db->lobs[$lob]['value']);
    }

    // }}}
    // {{{ endOfLOB()

    /**
     * Determine whether it was reached the end of the large object and
     * therefore there is no more data to be read for the its input stream.
     *
     * @param integer $lob argument handle that is returned by the
     *                          MDB2::createLOB() method.
     * @access public
     * @return boolean flag that indicates whether it was reached the end of the large object input stream
     */
    function endOfLOB($lob)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return $GLOBALS['_MDB2_LOBs'][$lob]->endOfLOB();
    }

    // }}}
    // {{{ _destroyResultLOB()

    /**
     * Free any resources allocated during the lifetime of the large object
     * handler object.
     *
     * @param int $lob handle to a lob created by the createLOB() function
     * @access private
     */
    function _destroyResultLOB($lob)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if (isset($db->lobs[$lob])) {
            $db->lobs[$lob] = '';
        }
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
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $GLOBALS['_MDB2_LOBs'][$lob]->destroy();
        unset($GLOBALS['_MDB2_LOBs'][$lob]);
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