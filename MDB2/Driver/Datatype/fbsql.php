<?php
// vim: set et ts=4 sw=4 fdm=marker:
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

require_once 'MDB2/Driver/Datatype/Common.php';

/**
 * MDB2 FrontbaseSQL driver
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@backendmedia.com>
 */
class MDB2_Driver_Datatype_fbsql extends MDB2_Driver_Datatype_Common
{
    // }}}
    // {{{ convertResult()

    /**
     * convert a value to a RDBMS indepdenant MDB2 type
     *
     * @param mixed  $value   value to be converted
     * @param int    $type    constant that specifies which type to convert to
     * @return mixed converted value
     * @access public
     */
    function convertResult($value, $type)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        switch ($type) {
             case MDB2_TYPE_BOOLEAN:
                 return $value == 'T';
             case MDB2_TYPE_TIME:
                if ($value[0] == '+') {
                    return substr($value, 1);
                } else {
                    return $value;
                }
            default:
                return $this->_baseConvertResult($value, $type);
        }
        return $this->_baseConvertResult($value, $type);
    }

    // }}}
    // {{{ getIntegerDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       unsigned
     *                        Boolean flag that indicates whether the field
     *                        should be declared as unsigned integer if
     *                        possible.
     *
     *                       default
     *                        Integer value to be used as default for this
     *                        field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getIntegerDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if (isset($field['unsigned'])) {
            $this->warnings[] = "unsigned integer field \"$name\" is being
                declared as signed integer";
        }
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quoteInteger($field['default']) : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' INT'.$default.$notnull;
    }

    // }}}
    // {{{ getTextDeclaration()

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
     *           to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access public
     */
    function getTextDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quoteText($field['default']) : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        $length = isset($field['length']) ? $field['length'] : $db->max_text_length;
        return $name.' VARCHAR ('.$length.')'.$default.$notnull;
    }

    // }}}
    // {{{ getCLOBDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an character
     * large object type field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the
     *                        properties of the field being declared as array
     *                        indexes. Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       length
     *                        Integer value that determines the maximum length
     *                        of the large object field. If this argument is
     *                        missing the field should be declared to have the
     *                        longest length allowed by the DBMS.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field
     *                        is constrained to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getCLOBDeclaration($name, $field)
    {
        return "$name CLOB".(isset($field['notnull']) ? ' NOT NULL' : '');
    }

    // }}}
    // {{{ getBLOBDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an binary large
     * object type field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       length
     *                        Integer value that determines the maximum length
     *                        of the large object field. If this argument is
     *                        missing the field should be declared to have the
     *                        longest length allowed by the DBMS.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getBLOBDeclaration($name, $field)
    {
        return "$name BLOB".(isset($field['notnull']) ? ' NOT NULL' : '');
    }

    // }}}
    // {{{ getBooleanDeclaration()

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
     *           to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access public
     */
    function getBooleanDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quoteBoolean($field['default']) : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' BOOLEAN)'.$default.$notnull;
    }

    // }}}
    // {{{ getDateDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an date type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field properties
     *                        are as follows:
     *
     *                       default
     *                        Date value to be used as default for this field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getDateDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT DATE '.
            $this->quoteDate($field['default']) : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' DATE'.$default.$notnull;
    }

    // }}}
    // {{{ getTimestampDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an timestamp
     * type field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       default
     *                        Time stamp value to be used as default for this
     *                        field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getTimestampDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT TIMESTAMP '.
            $this->quoteTimestamp($field['default']) : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' TIMESTAMP'.$default.$notnull;
    }

    // }}}
    // {{{ getTimeDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an time type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       default
     *                        Time value to be used as default for this field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getTimeDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT TIME '.
            $this->quoteTime($field['default']) : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' TIME'.$default.$notnull;
    }

    // }}}
    // {{{ getFloatDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an float type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       default
     *                        Integer value to be used as default for this
     *                        field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getFloatDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quoteFloat($field['default']) : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' FLOAT'.$default.$notnull;
    }

    // }}}
    // {{{ getDecimalDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an decimal type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       default
     *                        Integer value to be used as default for this
     *                        field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getDecimalDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $type = 'DECIMAL(18,'.$db->options['decimal_places'].')';
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quoteDecimal($field['default']) : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' '.$type.$default.$notnull;
    }

    // }}}
    // {{{ quoteCLOB()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param           $clob
     * @return string  text string that represents the given argument value in
     *                 a DBMS specific format.
     * @access public
     */
    function quoteCLOB($clob)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if ($clob === null) {
            return 'NULL';
        }
        $value = "'";
        $data = null;
        while (!$this->endOfLOB($clob)) {
            $result = $this->readLOB($clob, $data, $db->options['lob_buffer_length']);
            if (MDB2::isError($result)) {
                return $result;
            }
            $value .= $db->escape($data);
        }
        $value .= "'";
        return $value;
    }

    // }}}
    // {{{ quoteBLOB()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param           $blob
     * @return string  text string that represents the given argument value in
     *                 a DBMS specific format.
     * @access public
     */
    function quoteBLOB($blob)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if ($blob === null) {
            return 'NULL';
        }
        $value = "'";
        $data = null;
        while (!$this->endOfLOB($blob)) {
            $result = $this->readLOB($blob, $data, $db->options['lob_buffer_length']);
            if (MDB2::isError($result)) {
                return $result;
            }
            $value .= addslashes($data);
        }
        $value .= "'";
        return $value;
    }

    // }}}
    // {{{ quoteBoolean()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access public
     */
    function quoteBoolean($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return ($value === null) ? 'NULL' : ($value ? 'True' : 'False');
    }

    // }}}
    // {{{ quoteDate()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
     * @access public
     */
    function quoteDate($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return ($value === null) ? 'NULL' : "DATE'$value'";
    }

    // }}}
    // {{{ quoteTimestamp()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
     * @access public
     */
    function quoteTimestamp($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return ($value === null) ? 'NULL' : "TIMESTAMP'$value'";
    }

    // }}}
    // {{{ quoteTime()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     *        compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
     * @access public
     */
    function quoteTime($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return ($value === null) ? 'NULL' : "TIME'$value'";
    }

    // }}}
    // {{{ quoteFloat()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string  $value text string value that is intended to be converted.
     * @return string  text string that represents the given argument value in
     *                 a DBMS specific format.
     * @access public
     */
    function quoteFloat($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return ($value === null) ? 'NULL' : (float)$value;
    }

    // }}}
    // {{{ quoteDecimal()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string  $value text string value that is intended to be converted.
     * @return string  text string that represents the given argument value in
     *                 a DBMS specific format.
     * @access public
     */
    function quoteDecimal($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return ($value === null) ? 'NULL' : $value;
    }
}

?>
