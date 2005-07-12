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
// | Author: YOUR NAME <YOUR EMAIL>                                       |
// +----------------------------------------------------------------------+
//
// $Id$
//

// This is just a skeleton MDB2 driver.
// There may be methods missing as this skeleton is based on the methods
// implemented by the MySQL and PostGreSQL drivers in MDB2.
// Some methods may not have to be implemented in the driver, because the
// implementation in common.php is compatible with the given RDBMS.
// In each of the listed methods I have added comments that tell you where
// to look for a "reference" implementation.
// Some of these methods have been expanded or changed slightly in MDB2.
// Looking in the relevant MDB2 Wrapper should give you some pointers, some
// other difference you will only discover by looking at one of the existing
// MDB2 driver or the common implementation in common.php.
// One thing that will definately have to be modified in all "reference"
// implementations of Metabase methods is the error handling.
// Anyways don't worry if you are having problems: Lukas Smith is here to help!

require_once 'MDB2/Driver/Datatype/Common.php';

/**
 * MDB2 XXX driver
 *
 * @package MDB2
 * @category Database
 * @author  YOUR NAME <YOUR EMAIL>
 */
class MDB2_Datatype_xxx extends MDB2_Datatype_Common
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
        // take this from the corresponding Metabase driver: ConvertResult()
    }

    // }}}
    // {{{ get*Declaration()

    // take phpdoc comments from MDB2 common.php: get*Declaration()

    function get*Declaration($name, $field)
    {
        // take this from the corresponding Metabase driver: Get*FieldValue()
    }

    // }}}
    // {{{ quote*()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param resource  $prepared_query query handle from prepare()
     * @param           $parameter
     * @param           $clob
     * @return string  text string that represents the given argument value in
     *                 a DBMS specific format.
     * @access public
     */
    function quote*($prepared_query, $parameter, $clob)
    {
        // take this from the corresponding Metabase driver: Get*FieldValue()
    }

    // }}}
    // {{{ quoteClob()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param resource  $prepared_query query handle from prepare()
     * @param           $parameter
     * @param           $clob
     * @return string  text string that represents the given argument value in
     *                 a DBMS specific format.
     * @access public
     */
    function quoteClob($prepared_query, $parameter, $clob)
    {
        // take this from the corresponding Metabase driver: GetCLOBFieldValue()
    }

    // }}}
    // {{{ quoteBlob()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param resource  $prepared_query query handle from prepare()
     * @param           $parameter
     * @param           $blob
     * @return string  text string that represents the given argument value in
     *                 a DBMS specific format.
     * @access public
     */
    function quoteBlob($prepared_query, $parameter, $blob)
    {
        // take this from the corresponding Metabase driver: GetBLOBFieldValue()
    }

    // }}}
}

?>