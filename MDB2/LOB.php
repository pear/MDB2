<?php

require_once 'PEAR.php';
require_once 'MDB2.php';

class MDB2_LOB
{
    var $db_index;
    var $lob_index;
    var $lob;

    function stream_open($path, $mode, $options, &$opened_path)
    {
        if (!preg_match('/^rb?\+?$/', $mode)) {
            return false;
        }
        $url = parse_url($path);
        if (!isset($url['host']) && !isset($url['user'])) {
            return false;
        }
        $this->db_index = $url['host'];
        if (!isset($GLOBALS['_MDB2_databases'][$this->db_index])) {
            return false;
        }
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        $this->lob_index = $url['user'];
        if (!isset($db->datatype->lobs[$this->lob_index])) {
            return false;
        }
        $this->lob =& $db->datatype->lobs[$this->lob_index];
        $db->datatype->_retrieveLOB($this->lob);
        return true;
    }

    function stream_read($count)
    {
        if (isset($GLOBALS['_MDB2_databases'][$this->db_index])) {
            $db =& $GLOBALS['_MDB2_databases'][$this->db_index];

            $data = $db->datatype->_readLOB($this->lob, $count);
            $length = strlen($data);
            if ($length == 0) {
                $this->lob['endOfLOB'] = true;
            }
            $this->lob['position'] += $length;
            return $data;
        }
   }

    function stream_write($data)
    {
        return 0;
    }

    function stream_tell()
    {
        return $this->lob['position'];
    }

    function stream_eof()
    {
        if (!isset($GLOBALS['_MDB2_databases'][$this->db_index])) {
            return true;
        }
        $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
        return $db->datatype->_endOfLOB($this->lob);
    }

    function stream_seek($offset, $whence)
    {
        return false;
    }

    function stream_close()
    {
        if (isset($GLOBALS['_MDB2_databases'][$this->db_index])
            && isset($this->lob['lob_id'])
        ) {
            $db =& $GLOBALS['_MDB2_databases'][$this->db_index];
            $db->datatype->destroyLOB($this->lob['lob_id']);
        }
    }
}

if (!stream_wrapper_register("MDB2LOB", "MDB2_LOB")) {
    MDB2::raiseError();
    return false;
}

?>