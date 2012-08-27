<?php


require_once 'MDB2/Driver/Datatype/Common.php';

/**
 * MDB2 MS SQL driver
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Driver_Datatype_odbc extends MDB2_Driver_Datatype_Common
{
    // {{{ _baseConvertResult()

    /**
     * general type conversion method
     *
     * @param mixed   $value refernce to a value to be converted
     * @param string  $type  specifies which type to convert to
     * @param boolean $rtrim [optional] when TRUE [default], apply rtrim() to text
     * @return object a MDB2 error on failure
     * @access protected
     */
    function _baseConvertResult($value, $type, $rtrim = true)
    {
        if (is_null($value)) {
            return null;
        }
        switch ($type) {
        case 'boolean':
            return $value == '1';
        case 'date':
            if (strlen($value) > 10) {
                $value = substr($value,0,10);
            }
            return $value;
        case 'time':
            if (strlen($value) > 8) {
                $value = substr($value,11,8);
            }
            return $value;
        }
        return parent::_baseConvertResult($value, $type, $rtrim);
    }

    // }}}
    // {{{ _getCollationFieldDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to set the COLLATION
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $collation name of the collation
     *
     * @return string DBMS specific SQL code portion needed to set the COLLATION
     *                of a field declaration.
     */
    function _getCollationFieldDeclaration($collation)
    {
        return 'COLLATE '.$collation;
    }

    // }}}
    // {{{ getTypeDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param array $field  associative array with the name of the properties
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
     * @access public
     */
    function getTypeDeclaration($field)
    {
        $db =& $this->getDBInstance();
        if (MDB2::isError($db)) {
            return $db;
        }

        switch ($field['type']) {
        case 'text':
            $length = !empty($field['length'])
                ? $field['length'] : false;
            $fixed = !empty($field['fixed']) ? $field['fixed'] : false;
            return $fixed ? ($length ? 'CHAR('.$length.')' : 'CHAR('.$db->options['default_text_field_length'].')')
                : ($length ? 'VARCHAR('.$length.')' : 'TEXT');
        case 'clob':
            if (!empty($field['length'])) {
                $length = $field['length'];
                if ($length <= 8000) {
                    return 'VARCHAR('.$length.')';
                }
             }
             return 'TEXT';
        case 'blob':
            if (!empty($field['length'])) {
                $length = $field['length'];
                if ($length <= 8000) {
                    return "VARBINARY($length)";
                }
            }
            return 'IMAGE';
        case 'integer':
            return 'INT';
        case 'boolean':
            return 'BIT';
        case 'date':
            return 'CHAR ('.strlen('YYYY-MM-DD').')';
        case 'time':
            return 'CHAR ('.strlen('HH:MM:SS').')';
        case 'timestamp':
            return 'CHAR ('.strlen('YYYY-MM-DD HH:MM:SS').')';
        case 'float':
            return 'FLOAT';
        case 'decimal':
            $length = !empty($field['length']) ? $field['length'] : 18;
            $scale = !empty($field['scale']) ? $field['scale'] : $db->options['decimal_places'];
            return 'DECIMAL('.$length.','.$scale.')';
        }
        return '';
    }

    // }}}
    // {{{ _getIntegerDeclaration()

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
     * @access protected
     */
    function _getIntegerDeclaration($name, $field)
    {
        $db =& $this->getDBInstance();
        if (MDB2::isError($db)) {
            return $db;
        }

        $notnull = empty($field['notnull']) ? ' NULL' : ' NOT NULL';
        $default = $autoinc = '';
        if (!empty($field['autoincrement'])) {
            $autoinc = ' IDENTITY PRIMARY KEY';
        } elseif (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $field['default'] = 0;
            }
            if (is_null($field['default'])) {
                $default = ' DEFAULT (null)';
            } else {
                $default = ' DEFAULT (' . $this->quote($field['default'], 'integer') . ')';
            }
        }

        if (!empty($field['unsigned'])) {
            $db->warnings[] = "unsigned integer field \"$name\" is being declared as signed integer";
        }

        $name = $db->quoteIdentifier($name, true);
        return $name.' '.$this->getTypeDeclaration($field).$notnull.$default.$autoinc;
    }

    // }}}
    // {{{ _getCLOBDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an character
     * large object type field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
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
        $db =& $this->getDBInstance();
        if (MDB2::isError($db)) {
            return $db;
        }

        $notnull = empty($field['notnull']) ? ' NULL' : ' NOT NULL';
        $name = $db->quoteIdentifier($name, true);
        return $name.' '.$this->getTypeDeclaration($field).$notnull;
    }

    // }}}
    // {{{ _getBLOBDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an binary large
     * object type field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
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
     * @access protected
     */
    function _getBLOBDeclaration($name, $field)
    {
        $db =& $this->getDBInstance();
        if (MDB2::isError($db)) {
            return $db;
        }

        $notnull = empty($field['notnull']) ? ' NULL' : ' NOT NULL';
        $name = $db->quoteIdentifier($name, true);
        return $name.' '.$this->getTypeDeclaration($field).$notnull;
    }

    // }}}
    // {{{ _quoteBLOB()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string  text string that represents the given argument value in
     *                 a DBMS specific format.
     * @access protected
     */
    function _quoteBLOB($value, $quote, $escape_wildcards)
    {
        if (!$quote) {
            return $value;
        }
        $value = '0x'.bin2hex($this->_readFile($value));
        return $value;
    }

    // }}}
    // {{{ _mapNativeDatatype()

    /**
     * Maps a native array description of a field to a MDB2 datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     * @access public
     */
    function _mapNativeDatatype($field)
    {
    	
        // todo: handle length of various int variations
        $db_type = preg_replace('/\d/', '', strtolower($field['type']));
        $length = $field['length'];
        $type = array();
        // todo: unsigned handling seems to be missing
        $unsigned = $fixed = null;
        switch ($db_type) {
        case 'bit':
            $type[0] = 'boolean';
            break;
        case 'tinyint':
            $type[0] = 'integer';
            $length = 1;
            break;
        case 'smallint':
            $type[0] = 'integer';
            $length = 2;
            break;
        case 'integer':
            $type[0] = 'integer';
            $length = 4;
            break;
        case 'bigint':
            $type[0] = 'integer';
            $length = 8;
            break;
        case 'smalldatetime':
        case 'timestamp':
        case 'time':
        case 'date':
            $type[0] = 'timestamp';
            break;
        case 'float':
        case 'real':
        case 'numeric':
            $type[0] = 'float';
            break;
        case 'decimal':
        case 'money':
            $type[0] = 'decimal';
            $length = $field['numeric_precision'].','.$field['numeric_scale'];
            break;
        case 'text':
        case 'ntext':
        case 'varchar() for bit data':
        case 'varchar':
        case 'nvarchar':
            $fixed = false;
        case 'char':
        case 'nchar':
            $type[0] = 'text';
            if ($length == '1') {
                $type[] = 'boolean';
                if (preg_match('/^(is|has)/', $field['name'])) {
                    $type = array_reverse($type);
                }
            } elseif (strstr($db_type, 'text')) {
                $type[] = 'clob';
                $type = array_reverse($type);
            }
            if ($fixed !== false) {
                $fixed = true;
            }
            break;
        case 'image':
        case 'blob':
        case 'vargraphic() ccsid ':
        case 'varbinary':
            $type[] = 'blob';
            $length = null;
            break;
        default:
            $db =& $this->getDBInstance();
            if (MDB2::isError($db)) {
                return $db;
            }
            return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'unknown database attribute type: '.$db_type, __FUNCTION__);
        }

        if ((int)$length <= 0) {
            $length = null;
        }

        return array($type, $length, $unsigned, $fixed);
    }
    // }}}
}

?>
