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

// $Id$

require_once 'MDB2/Driver/Datatype/Common.php';

/**
 * MDB2 OCI8 driver
 *
 * @package MDB2
 * @category Database
 * @author Lukas Smith <smith@backendmedia.com>
 */
class MDB2_Driver_Datatype_oci8 extends MDB2_Driver_Datatype_Common
{
    // {{{ convertResult()

    /**
     * convert a value to a RDBMS indepdenant MDB2 type
     *
     * @param mixed $value value to be converted
     * @param int $type constant that specifies which type to convert to
     * @return mixed converted value
     * @access public
     */
    function convertResult($value, $type)
    {
        if (is_null($value)) {
            return null;
        }
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        switch ($type) {
        case 'date':
            return substr($value, 0, strlen('YYYY-MM-DD'));
        case 'time':
            return substr($value, strlen('YYYY-MM-DD '), strlen('HH:MI:SS'));
        default:
            return $this->_baseConvertResult($value, $type);
        }
    }

    // }}}
    // {{{ _getTypeDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the text
     *          field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      default
     *          Text value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access private
     */
    function _getTypeDeclaration($field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        switch ($field['type']) {
        case 'text':
            $length = (isset($field['length']) ? $field['length'] : (($length = $db->options['default_text_field_length']) ? $length : 4000));
            return 'VARCHAR ('.$length.')';
        case 'clob':
            return 'CLOB';
        case 'blob':
            return 'BLOB';
        case 'integer':
            return 'INT';
        case 'boolean':
            return 'CHAR (1)';
        case 'date':
        case 'time':
        case 'timestamp':
            return 'DATE';
        case 'float':
            return 'NUMBER';
        case 'decimal':
            return 'NUMBER(*,'.$db->options['decimal_places'].')';
        }
    }

    // }}}
    // {{{ _getIntegerDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Id
     * ently, the types
     *        of supported field properties are as follows:
     *
     *        unsigned
     *            Boolean flag that indicates whether the field should be
     *            declared as unsigned integer if possible.
     *
     *        default
     *            Integer value to be used as default for this field.
     *
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access private
     */
    function _getIntegerDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if (isset($field['unsigned'])) {
            $db->warning = "unsigned integer field \"$name\" is being declared as signed integer";
        }
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'integer') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' '.$this->_getTypeDeclaration($field).$default.$notnull;
    }

    // }}}
    // {{{ _getTextDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     *
     *        length
     *            Integer value that determines the maximum length of the text
     *            field. If this argument is missing the field should be
     *            declared to have the longest length allowed by the DBMS.
     *
     *        default
     *            Text value to be used as default for this field.
     *
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access private
     */
    function _getTextDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $type = $this->_getTypeDeclaration($field);
        $default = isset($field['default']) ? ' DEFAULT TIME'.
            $this->quote($field['default'], 'text') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
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
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     *
     *        length
     *            Integer value that determines the maximum length of the large
     *            object field. If this argument is missing the field should be
     *            declared to have the longest length allowed by the DBMS.
     *
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access public
     */
    function _getCLOBDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' '.$this->_getTypeDeclaration($field).$notnull;
    }

    // }}}
    // {{{ _getBLOBDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an binary large
     * object type field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     *
     *        length
     *            Integer value that determines the maximum length of the large
     *            object field. If this argument is missing the field should be
     *            declared to have the longest length allowed by the DBMS.
     *
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access private
     */
    function _getBLOBDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' '.$this->_getTypeDeclaration($field).$notnull;
    }

    // }}}
    // {{{ _getDateDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a date type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     *
     *        default
     *            Date value to be used as default for this field.
     *
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access private
     */
    function _getDateDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'date') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' '.$this->_getTypeDeclaration($field).$default.$notnull;
    }

    // }}}
    // {{{ _getTimestampDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a timestamp
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     *
     *        default
     *            Timestamp value to be used as default for this field.
     *
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access private
     */
    function _getTimestampDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quoteTimstamp($field['default']) : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' '.$this->_getTypeDeclaration($field).$default.$notnull;    }

    // }}}
    // {{{ _getTimeDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a time
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     *
     *        default
     *            Time value to be used as default for this field.
     *
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access private
     */
    function _getTimeDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $db->quoteime($field['default']) : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' '.$this->_getTypeDeclaration($field).$default.$notnull;
    }

    // }}}
    // {{{ _getFloatDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a float type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     *
     *        default
     *            Float value to be used as default for this field.
     *
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access private
     */
    function _getFloatDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'float') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' '.$this->_getTypeDeclaration($field).$default.$notnull;
    }

    // }}}
    // {{{ _getDecimalDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a decimal type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     *
     *        default
     *            Decimal value to be used as default for this field.
     *
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access private
     */
    function _getDecimalDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'decimal') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' '.$this->_getTypeDeclaration($field).$default.$notnull;
    }

    // }}}
    // {{{ _quoteCLOB()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param  $value
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
     * @access private
     */
    function _quoteCLOB($value)
    {
        return 'EMPTY_CLOB()';
    }

    // }}}
    // {{{ _quoteBLOB()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param  $value
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
     * @access private
     */
    function _quoteBLOB($value)
    {
        return 'EMPTY_BLOB()';
    }

    // }}}
    // {{{ _quoteDate()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
     * @access private
     */
    function _quoteDate($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return "'$value 00:00:00'";
    }

    // }}}
    // {{{ _quoteTimestamp()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
     * @access private
     */
    function _quoteTimestamp($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return "'$value'";
    }

    // }}}
    // {{{ _quoteTime()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     *        compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
     * @access private
     */
    function _quoteTime($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return "'0001-01-01 $value'";
    }

    // }}}
    // {{{ _quoteFloat()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
     * @access private
     */
    function _quoteFloat($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return (float)$value;
    }

    // }}}
    // {{{ _quoteDecimal()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
     * @access private
     */
    function _quoteDecimal($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return $value;
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
        if (!isset($db->lobs[$lob]['loaded'])) {
            if (!is_object($db->lobs[$lob]['value'])) {
               return $db->raiseError(MDB2_ERROR, null, null,
                   'attemped to retrieve LOB from non existing or NULL column');
            }
            $db->lobs[$lob]['value'] = $db->lobs[$lob]['value']->load();
            $db->lobs[$lob]['loaded'] = true;
        }
        return MDB2_OK;
    }
}

?>