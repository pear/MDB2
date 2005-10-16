<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2005 Lukas Smith, Lorenzo Alberton                |
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
// | Author: Lorenzo Alberton <l dot alberton at quipo dot it>            |
// +----------------------------------------------------------------------+
//
// $Id$

class MDB2_Function_TestCase extends PHPUnit_TestCase
{
    //contains the dsn of the database we are testing
    var $dsn;
    //contains the options that should be used during testing
    var $options;
    //contains the name of the database we are testing
    var $database;
    //contains the MDB2 object of the db once we have connected
    var $db;

    function MDB2_Function_TestCase($name) {
        $this->PHPUnit_TestCase($name);
    }

    function setUp() {
        $this->dsn = $GLOBALS['dsn'];
        $this->options = $GLOBALS['options'];
        $this->database = $GLOBALS['database'];
        $this->db =& MDB2::factory($this->dsn, $this->options);
        if (PEAR::isError($this->db)) {
            $this->assertTrue(false, 'Could not connect to database in setUp - ' .$this->db->getMessage() . ' - ' .$this->db->getUserInfo());
            exit;
        }
        $this->db->setDatabase($this->database);
        $this->db->expectError(MDB2_ERROR_UNSUPPORTED);
        $this->db->loadModule('Function');
    }

    function tearDown() {
        $this->db->popExpect();
        unset($this->dsn);
        if (!PEAR::isError($this->db)) {
            $this->db->disconnect();
        }
        unset($this->db);
    }

    function methodExists(&$class, $name) {
        if (is_object($class)
            && array_key_exists(strtolower($name), array_change_key_case(array_flip(get_class_methods($class)), CASE_LOWER))
        ) {
            return true;
        }
        $this->assertTrue(false, 'method '. $name.' not implemented in '.get_class($class));
        return false;
    }

    /**
     * Test substring()
     */
    function testSubstring()
    {
        if (!$this->methodExists($this->db->function, 'substring')) {
            return;
        }
        $record = array(
            'number' => 1,
            'trans_en' => 'number_one',
            'trans_fr' => 'blahblah',
        );
        
        $result = $this->db->query('DELETE FROM numbers');
        $this->assertFalse(PEAR::isError($result), 'Error truncating table');
        $query = 'INSERT INTO numbers (number, trans_en, trans_fr) VALUES ('
                . $record['number'] . ','
                . $this->db->quote($record['trans_en']) . ','
                . $this->db->quote($record['trans_fr']) . ')';
        $result = $this->db->query($query);
        $this->assertFalse(PEAR::isError($result), 'Error inserting sample record');

        $substring_clause = $this->db->function->substring('trans_en', 1, 6);
        $query = 'SELECT '.$substring_clause .' FROM numbers';
        $result = $this->db->queryOne($query);
        if (PEAR::isError($result)) {
            $this->assertFalse(true, 'Error getting substring');
        } else {
            $this->assertEquals('number', $result, 'Error: substrings not equals');
        }
        
        $substring_clause = $this->db->function->substring('trans_en', 5, 3);
        $query = 'SELECT '.$substring_clause .' FROM numbers';
        $result = $this->db->queryOne($query);
        if (PEAR::isError($result)) {
            $this->assertFalse(true, 'Error getting substring');
        } else {
            $this->assertEquals('er_', $result, 'Error: substrings not equals');
        }
        
        //test NULL 2nd parameter
        $substring_clause = $this->db->function->substring('trans_en', 8);
        $query = 'SELECT '.$substring_clause .' FROM numbers';
        $result = $this->db->queryOne($query);
        if (PEAR::isError($result)) {
            $this->assertFalse(true, 'Error getting substring');
        } else {
            $this->assertEquals('one', $result, 'Error: substrings not equals');
        }
    }
}
?>