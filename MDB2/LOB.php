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
//


/**
 * @package  MDB2
 * @category Database
 * @author   Lukas Smith <smith@backendmedia.com>
 */

/**
 * MDB2 Large Object (BLOB/CLOB) core class
 *
 * @package MDB2
 * @category Database
 * @access private
 * @author  Lukas Smith <smith@backendmedia.com>
 */
class MDB2_LOB
{
    var $db;
    var $lob;
    var $data = '';
    var $position = 0;
    var $parameter;
    var $prepared_query;

    function create(&$arguments)
    {
        if (isset($arguments['data'])) {
            $this->data = $arguments['data'];
        }
        if (isset($arguments['parameter'])) {
            $this->parameter = $arguments['parameter'];
        }
        return MDB2_OK;
    }

    function destroy()
    {
        $this->data = '';
    }

    function endOfLOB()
    {
        return $this->position >= strlen($this->data);
    }

    function readLOB(&$data, $length)
    {
        $length = min($length, strlen($this->data) - $this->position);
        $data = substr($this->data, $this->position, $length);
        $this->position += $length;
        return $length;
    }
}

/**
 * MDB2 Large Object (BLOB/CLOB) class for reading results
 *
 * @package MDB2
 * @category Database
 * @access private
 * @author  Lukas Smith <smith@backendmedia.com>
 */
class MDB2_LOB_Result extends MDB2_LOB
{
    var $result_lob = 0;

    function create(&$arguments)
    {
        if (!isset($arguments['resultLOB'])) {
            return MDB2_Driver_Common::raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                'MDB2_LOB_Result::create: it was not specified a result LOB identifier');
        }
        $this->result_lob = $arguments['resultLOB'];
        return MDB2_OK;
    }

    function destroy()
    {
        $this->db->datatype->_destroyResultLOB($this->result_lob);
    }

    function endOfLOB()
    {
        return $this->db->datatype->_endOfResultLOB($this->result_lob);
    }

    function readLOB(&$data, $length)
    {
        $read_length = $this->db->datatype->_readResultLOB($this->result_lob, $data, $length);
        if (MDB2::isError($read_length)) {
            return $read_length;
        }
        if ($read_length < 0) {
            return MDB2_Driver_Common::raiseError(MDB2_ERROR_INVALID, null, null,
                'MDB2_LOB_Result::readLOB: data was read beyond end of data source');
        }
        return $read_length;
    }
}

/**
 * MDB2 Large Object (BLOB/CLOB) class to read file into DB
 *
 * @package MDB2
 * @category Database
 * @access private
 * @author  Lukas Smith <smith@backendmedia.com>
 */
/*
class MDB2_LOB_Input_File extends MDB2_LOB
{
    var $file = '';
    var $opened_file = false;

    function create(&$arguments)
    {
        if (isset($arguments['parameter'])) {
            $this->parameter = $arguments['parameter'];
        }
        if (isset($arguments['file'])) {
            if (intval($arguments['file']) == 0) {
                return MDB2_Driver_Common::raiseError(MDB2_ERROR_INVALID, null, null,
                    'MDB2_LOB_Input_File::create: it was specified an invalid input file identifier');
            }
            $this->file = $arguments['file'];
        } else {
            if (isset($arguments['file_name'])) {
                if ((!$this->file = fopen($arguments['file_name'], 'rb'))) {
                return MDB2_Driver_Common::raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                    'MDB2_LOB_Input_File::create: could not open specified input file ("'.$arguments['file_name'].'")');
                }
                $this->opened_file = true;
            } else {
                return MDB2_Driver_Common::raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'MDB2_LOB_Input_File::create: it was not specified the input file');
            }
        }
        return MDB2_OK;
    }

    function destroy()
    {
        if ($this->opened_file) {
            fclose($this->file);
            $this->file = '';
            $this->opened_file = false;
        }
    }

    function endOfLOB() {
        return feof($this->file);
    }

    function readLOB(&$data, $length)
    {
        if (!is_string($data = @fread($this->file, $length))) {
            return MDB2_Driver_Common::raiseError(MDB2_ERROR, null, null,
                'MDB2_LOB_Input_File::readLOB: could not read from the input file');
        }
        return strlen($data);
    }
}
*/
/**
 * MDB2 Large Object (BLOB/CLOB) class to read into a file from DB
 *
 * @package MDB2
 * @category Database
 * @access private
 * @author  Lukas Smith <smith@backendmedia.com>
 */
class MDB2_LOB_Output_File extends MDB2_LOB
{
    var $file = '';
    var $opened_file = false;
    var $input_lob = 0;
    var $opened_lob = false;
    var $buffer_length = 8000;

    function create(&$arguments)
    {
        if (isset($arguments['buffer_fength'])) {
            if ($arguments['buffer_length'] <= 0) {
                return MDB2_Driver_Common::raiseError(MDB2_ERROR_INVALID, null, null,
                    'MDB2_LOB_Output_File::create: it was specified an invalid buffer length');
            }
            $this->buffer_length = $arguments['buffer_length'];
        }
        if (isset($arguments['file'])) {
            if (intval($arguments['file']) == 0) {
                return MDB2_Driver_Common::raiseError(MDB2_ERROR_INVALID, null, null,
                    'MDB2_LOB_Output_File::create: it was specified an invalid output file identifier');
            }
            $this->file = $arguments['file'];
        } else {
            if (isset($arguments['file_name'])) {
                if ((!$this->file = fopen($arguments['file_name'],'wb'))) {
                    return MDB2_Driver_Common::raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                        'MDB2_LOB_Output_File::create: could not open specified output file ("'.$arguments['file_name'].'")');
                }
                $this->opened_file = true;
            } else {
                return MDB2_Driver_Common::raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                    'MDB2_LOB_Output_File::create: it was not specified the output file');
            }
        }
        if (isset($arguments['LOB'])) {
            $this->input_lob = $arguments['LOB'];
            $this->opened_lob = true;
        }
        return MDB2_OK;
    }

    function destroy()
    {
        if ($this->opened_file) {
            fclose($this->file);
            $this->opened_file = false;
            $this->file = '';
        }
        if ($this->opened_lob) {
            $this->db->datatype->destroyLOB($this->input_lob);
            $this->input_lob = 0;
            $this->opened_lob = false;
        }
    }

    function endOfLOB()
    {
        return $this->db->datatype->endOfLOB($this->input_lob);
    }

    function readLOB(&$data, $length) {
        $buffer_length = ($length == 0 ? $this->buffer_length : $length);
        $written_full = $read = 0;
        $buffer = null;
        do {
            for ($written = 0;
                !$this->db->datatype->endOfLOB($this->input_lob)
                && $written < $buffer_length;
                $written += $read
            ) {
                $result = $this->db->datatype->readLOB($this->input_lob, $buffer, $buffer_length);
                if (MDB2::isError($result)) {
                    return $result;
                }
                $read = strlen($buffer);
                if (@fwrite($this->file, $buffer, $read)!= $read) {
                    return MDB2_Driver_Common::raiseError(MDB2_ERROR, null, null,
                        'MDB2_LOB_Output_File::readLOB: could not write to the output file');
                }
            }
            $written_full += $written;
        } while ($length == 0 && !$this->db->datatype->endOfLOB($this->input_lob));
        return $written_full;
    }
}

?>