<?php
// vim: set et ts=4 sw=4 fdm=marker:
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
//

require_once 'MDB2/Driver/Datatype/Common.php';

/**
 * MDB2 MySQL driver
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@backendmedia.com>
 */
class MDB2_Driver_Datatype_ibase extends MDB2_Driver_Datatype_Common
{
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
        if (is_null($value)) {
            return null;
        }
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        switch ($type) {
        case 'decimal':
            return sprintf('%.'.$db->options['decimal_places'].'f', doubleval($value)/pow(10.0, $db->options['decimal_places']));
        case 'timestamp':
            return substr($value, 0, strlen('YYYY-MM-DD HH:MM:SS'));
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
            $length = (isset($field['length']) ? $field['length'] : (!MDB2::isError($length = $db->options['default_text_field_length']) ? $length : 4000));
            return 'VARCHAR ('.$length.')';
        case 'clob':
            return 'BLOB SUB_TYPE 1';
        case 'blob':
            return 'BLOB SUB_TYPE 0';
        case 'integer':
            return 'INTEGER';
        case 'boolean':
            return 'CHAR (1)';
        case 'date':
            return 'DATE';
        case 'time':
            return 'TIME';
        case 'timestamp':
            return 'TIMESTAMP';
        case 'float':
            return 'DOUBLE PRECISION';
        case 'decimal':
            return 'DECIMAL(18,'.$db->options['decimal_places'].')';
        }
        return '';
    }

    // }}}
    // {{{ _getTextDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
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
    function _getTextDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $type = $this->getTypeDeclaration($field);
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
     * @param string $name   name the field to be declared.
     * @param string $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the large
     *          object field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access private
     */
    function _getCLOBDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' '.$this->getTypeDeclaration($field).$notnull;
    }

    // }}}
    // {{{ _getBLOBDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an binary large
     * object type field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
     * @param string $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the large
     *          object field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access private
     */
    function _getBLOBDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' '.$this->getTypeDeclaration($field).$notnull;
    }

    // }}}
    // {{{ _getDateDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a date type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
     * @param string $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      default
     *          Date value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access private
     */
    function _getDateDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'date') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' '.$this->getTypeDeclaration($field).$default.$notnull;
    }

    // }}}
    // {{{ _getTimestampDeclaration()

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
     * @access private
     */
    function _getTimestampDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'timestamp') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' '.$this->getTypeDeclaration($field).$default.$notnull;
    }

    // }}}
    // {{{ _getTimeDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a time
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
     * @param string $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      default
     *          Time value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access private
     */
    function _getTimeDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'time') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' '.$this->getTypeDeclaration($field).$default.$notnull;
    }

    // }}}
    // {{{ _getFloatDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a float type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
     * @param string $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      default
     *          Float value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access private
     */
    function _getFloatDeclaration($name, $field)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'float') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' '.$this->getTypeDeclaration($field).$default.$notnull;
    }

    // }}}
    // {{{ _getDecimalDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a decimal type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
     * @param string $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      default
     *          Decimal value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access private
     */
    function _getDecimalDeclaration($name, $field)
    {

        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $default = isset($field['default']) ? ' DEFAULT '.
            $this->quote($field['default'], 'decimal') : '';
        $notnull = isset($field['notnull']) ? ' NOT NULL' : '';
        return $name.' '.$this->getTypeDeclaration($field).$default.$notnull;
    }

    // }}}
    // {{{ _quoteLOB()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param           $value
     * @return string text string that represents the given argument value in
     *      a DBMS specific format.
     * @access private
     */
    function _quoteLOB($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if (MDB2::isError($connect = $db->connect())) {
            return $connect;
        }
        if (!$db->transaction_id = @ibase_trans(IBASE_COMMITTED, $db->connection)) {
            return $db->raiseError(MDB2_ERROR, null, null,
                'Could not start a new transaction: '.ibase_errmsg());
        }
        $close = true;
        if (is_resource($value)) {
            $close = false;
        } elseif (preg_match('/^(\w+:\/\/)(.*)$/', $value, $match)) {
            if ($match[1] == 'file://') {
                $value = $match[2];
            }
            $value = @fopen($value, 'r');
        } else {
            $fp = @tmpfile();
            @fwrite($fp, $value);
            @rewind($fp);
            $value = $fp;
        }
        $handle = @ibase_blob_create($db->in_transaction ? $db->transaction_id : $db->connection);
        if ($handle) {
            while (!@feof($value)) {
                $data = @fread($value, $db->options['lob_buffer_length']);
                $result = @ibase_blob_add($handle, $data);
                if ($result === false) {
                    $result = $db->raiseError(MDB2_ERROR, null, null,
                        'Could not add data to a large object: '.ibase_errmsg());
                    break;
                }
            }
            if ($close) {
                @fclose($value);
            }
            if (MDB2::isError($result)) {
                @ibase_blob_cancel($handle);
            } else {
                $value = @ibase_blob_close($handle);
            }
        } else {
            $result = $db->raiseError();
        }

        if ($db->in_transaction) {
            $db->commit();
        }
        return $value;
    }

    // }}}
    // {{{ _quoteCLOB()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param           $value
     * @return string text string that represents the given argument value in
     *      a DBMS specific format.
     * @access private
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
     * @param           $value
     * @return string text string that represents the given argument value in
     *      a DBMS specific format.
     * @access private
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
     * @access private
     */
    function _quoteBoolean($value)
    {
        return ($value ? "'Y'" : "'N'");
    }

    // }}}
    // {{{ _quoteDecimal()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *      a DBMS specific format.
     * @access private
     */
    function _quoteDecimal($value)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return (strval(round($value*pow(10.0, $db->options['decimal_places']))));
    }

    // }}}
    // {{{ _retrieveLOB()

    /**
     * fetch a lob value from a result set
     *
     * @param int $lob handle to a lob created by the createLob() function
     * @return mixed MDB2_OK on success, a MDB error on failure
     * @access private
     */
    function _retrieveLOB($lob)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];

        if (!isset($db->lobs[$lob])) {
            return $db->raiseError(MDB2_ERROR, NULL, NULL,
                '_retrieveLOB: it was not specified a valid lob');
        }

        if (!isset($db->lobs[$lob]['handle'])) {
            $db->lobs[$lob]['handle'] =
                @ibase_blob_open($db->lobs[$lob]['value']);
            if (!$db->lobs[$lob]['handle']) {
                unset($db->lobs[$lob]['value']);
                return $db->raiseError(MDB2_ERROR, NULL, NULL,
                    '_retrieveLOB: Could not open fetched large object field' . @ibase_errmsg());
            }
        }

        return MDB2_OK;
    }

    // }}}
    // {{{ _endOfResultLOB()

    /**
     * Determine whether it was reached the end of the large object and
     * therefore there is no more data to be read for the its input stream.
     *
     * @param int    $lob handle to a lob created by the createLOB() function
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
        return isset($db->lobs[$lob]['EndOfLOB']);
    }

    // }}}
    // {{{ _readResultLOB()

    /**
     * Read data from large object input stream.
     *
     * @param int $lob handle to a lob created by the createLob() function
     * @param blob $data reference to a variable that will hold data to be
     *      read from the large object input stream
     * @param int $length integer value that indicates the largest ammount of
     *      data to be read from the large object input stream.
     * @return mixed length on success, a MDB error on failure
     * @access private
     */
    function _readResultLOB($lob, &$data, $length)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if (MDB2::isError($lobresult = $this->_retrieveLOB($lob))) {
            return $lobresult;
        }
        $data = @ibase_blob_get($db->lobs[$lob]['handle'], $length);
        if (!is_string($data)) {
            $db->raiseError(MDB2_ERROR, NULL, NULL,
                'Read Result LOB: ' . @ibase_errmsg());
        }
        if (($length = strlen($data)) == 0) {
            $db->lobs[$lob]['EndOfLOB'] = 1;
        }
        return $length;
    }

    // }}}
    // {{{ _destroyResultLOB()

    /**
     * Free any resources allocated during the lifetime of the large object
     * handler object.
     *
     * @param int $lob handle to a lob created by the createLob() function
     * @access private
     */
    function _destroyResultLOB($lob)
    {
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        if (isset($db->lobs[$lob])) {
            if (isset($db->lobs[$lob]['value'])) {
               @ibase_blob_close($db->lobs[$lob]['handle']);
            }
            $db->lobs[$lob] = '';
        }
    }

    // }}}
}
?>