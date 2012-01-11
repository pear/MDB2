<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2006 Lukas Smith, Lorenzo Alberton                |
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

require_once dirname(__DIR__) . '/autoload.inc';

class Standard_FunctionTest extends Standard_Abstract
{
    /**
     * Can not use setUp() because we are using a dataProvider to get multiple
     * MDB2 objects per test.
     *
     * @param array $ci  an associative array with two elements.  The "dsn"
     *                   element must contain an array of DSN information.
     *                   The "options" element must be an array of connection
     *                   options.
     */
    protected function manualSetUp($ci) {
        parent::manualSetUp($ci);

        $this->db->loadModule('Function', null, true);
    }

    /**
     * Test functionTable()
     * @dataProvider provider
     */
    public function testFunctionTable($ci) {
        $this->manualSetUp($ci);

        if (!$this->methodExists($this->db->function, 'functionTable')) {
            return;
        }

        $functionTable_clause = $this->db->function->functionTable();
        $query = 'SELECT 1 '.$functionTable_clause;
        $result = $this->db->queryOne($query);
        if (MDB2::isError($result)) {
            $this->fail('Error fetching from function table: '.$result->getMessage().' :: '.$result->getUserInfo());
        } else {
            $this->assertEquals('1', $result, 'Error fetching value from function table');
        }
    }

    /**
     * Test now()
     * @dataProvider provider
     */
    public function testNow($ci) {
        $this->manualSetUp($ci);

        if (!$this->methodExists($this->db->function, 'now')) {
            return;
        }

        $tests = array(
            'timestamp' => '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            'date' => '/^\d{4}-\d{2}-\d{2}$/',
            'time' => '/^\d{2}:\d{2}:\d{2}$/',
        );

        foreach ($tests as $type => $regexp) {
            $functionTable_clause = $this->db->function->functionTable();
            $now_clause = $this->db->function->now($type);
            $query = 'SELECT '.$now_clause . $functionTable_clause;
            $result = $this->db->queryOne($query, $type);
            if (MDB2::isError($result)) {
                $this->fail('Error getting '.$type);
            } else {
                $this->assertRegExp($regexp, $result, 'Error: not a proper '.$type);
            }
        }
    }

    /**
     * Test substring()
     * @dataProvider provider
     */
    public function testSubstring($ci) {
        $this->manualSetUp($ci);

        if (!$this->methodExists($this->db->function, 'substring')) {
            return;
        }
        $data = $this->getSampleData(1234);

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);

        $result = $stmt->execute(array_values($data));
        $stmt->free();

        if (MDB2::isError($result)) {
            $this->fail('Error executing prepared query'.$result->getMessage());
        }

        $substring_clause = $this->db->function->substring('user_name', 1, 4);
        $query = 'SELECT '.$substring_clause .' FROM ' . $this->table_users;
        $result = $this->db->queryOne($query);
        if (MDB2::isError($result)) {
            $this->fail('Error getting substring');
        } else {
            $this->assertEquals('user', $result, 'Error: substrings not equals');
        }

        $substring_clause = $this->db->function->substring('user_name', 5, 1);
        $query = 'SELECT '.$substring_clause .' FROM ' . $this->table_users;
        $result = $this->db->queryOne($query);
        if (MDB2::isError($result)) {
            $this->fail('Error getting substring');
        } else {
            $this->assertEquals('_', $result, 'Error: substrings not equals');
        }

        //test NULL 2nd parameter
        $substring_clause = $this->db->function->substring('user_name', 6);
        $query = 'SELECT '.$substring_clause .' FROM ' . $this->table_users;
        $result = $this->db->queryOne($query);
        if (MDB2::isError($result)) {
            $this->fail('Error getting substring');
        } else {
            $this->assertEquals('1234', $result, 'Error: substrings not equals');
        }
    }

    /**
     * Test concat()
     * @dataProvider provider
     */
    public function testConcat($ci) {
        $this->manualSetUp($ci);

        if (!$this->methodExists($this->db->function, 'concat')) {
            return;
        }

        $functionTable_clause = $this->db->function->functionTable();
        $concat_clause = $this->db->function->concat($this->db->quote('time', 'text'), $this->db->quote('stamp', 'text'));
        $query = 'SELECT '.$concat_clause . $functionTable_clause;
        $result = $this->db->queryOne($query);
        if (MDB2::isError($result)) {
            $this->fail('Error getting concat');
        } else {
            $this->assertEquals('timestamp', $result, 'Error: could not concatenate "time+stamp"');
        }
    }

    /**
     * Test random()
     * @dataProvider provider
     */
    public function testRandom($ci) {
        $this->manualSetUp($ci);

        if (!$this->methodExists($this->db->function, 'random')) {
            return;
        }

        $rand_clause = $this->db->function->random();
        $functionTable_clause = $this->db->function->functionTable();
        $query = 'SELECT '.$rand_clause . $functionTable_clause;
        $result = $this->db->queryOne($query, 'float');
        if (MDB2::isError($result)) {
            $this->fail('Error getting random value:'. $result->getMessage());
        } else {
            $this->assertTrue(($result >= 0 && $result <= 1), 'Error: could not get random value between 0 and 1: '.$result);
        }
    }

    /**
     * Test lower()
     * @dataProvider provider
     */
    public function testLower($ci) {
        $this->manualSetUp($ci);

        if (!$this->methodExists($this->db->function, 'lower')) {
            return;
        }
        $string = $this->db->quote('FoO');
        $lower_clause = $this->db->function->lower($string);
        $functionTable_clause = $this->db->function->functionTable();
        $query = 'SELECT '.$lower_clause . $functionTable_clause;
        $result = $this->db->queryOne($query, 'text');
        if (MDB2::isError($result)) {
            $this->fail('Error getting lower case value:'. $result->getMessage());
        } else {
            $this->assertTrue(($result === 'foo'), 'Error: could not lower case "FoO": '.$result);
        }
    }

    /**
     * Test upper()
     * @dataProvider provider
     */
    public function testUpper($ci) {
        $this->manualSetUp($ci);

        if (!$this->methodExists($this->db->function, 'upper')) {
            return;
        }
        $string = $this->db->quote('FoO');
        $upper_clause = $this->db->function->upper($string);
        $functionTable_clause = $this->db->function->functionTable();
        $query = 'SELECT '.$upper_clause . $functionTable_clause;
        $result = $this->db->queryOne($query, 'text');
        if (MDB2::isError($result)) {
            $this->fail('Error getting upper case value:'. $result->getMessage());
        } else {
            $this->assertTrue(($result === 'FOO'), 'Error: could not upper case "FoO": '.$result);
        }
    }

    /**
     * Test length()
     * @dataProvider provider
     */
    public function testLenght($ci) {
        $this->manualSetUp($ci);

        if (!$this->methodExists($this->db->function, 'length')) {
            return;
        }
        $string = $this->db->quote('foo');
        $length_clause = $this->db->function->length($string);
        $functionTable_clause = $this->db->function->functionTable();
        $query = 'SELECT '.$length_clause . $functionTable_clause;
        $len = $this->db->queryOne($query, 'integer');
        if (MDB2::isError($len)) {
            $this->fail('Error getting upper case value:'. $len->getMessage());
        } else {
            $this->assertEquals(3, $len, 'Error: incorrect length for "foo" string: '.$len);
        }
    }

    /**
     * Test replace()
     * @dataProvider provider
     */
    public function testReplace($ci) {
        $this->manualSetUp($ci);

        if (!$this->methodExists($this->db->function, 'replace')) {
            return;
        }

        $string  = $this->db->quote('so what');
        $search  = $this->db->quote('o');
        $replace = $this->db->quote('ay');
        $this->db->pushErrorHandling(PEAR_ERROR_RETURN);
        $this->db->expectError(MDB2_ERROR_UNSUPPORTED);
        $replace_clause = $this->db->function->replace($string, $search, $replace);
        $this->db->popExpect();
        $this->db->popErrorHandling();
        if (MDB2::isError($replace_clause) && $replace_clause->getCode() == MDB2_ERROR_UNSUPPORTED) {
            return;
        }

        $functionTable_clause = $this->db->function->functionTable();
        $query = 'SELECT '.$replace_clause . $functionTable_clause;
        $result = $this->db->queryOne($query, 'text');
        if (MDB2::isError($result)) {
            $this->fail('Error getting replaced value:'. $result->getMessage() . ' :: ' . $result->getUserInfo());
        } else {
            $this->assertEquals('say what', $result, 'Error: could not get replace string: '.$result);
        }
    }

    /**
     * Test unixtimestamp()
     * @dataProvider provider
     */
    public function testUnixtimestamp($ci) {
        $this->manualSetUp($ci);

        if (!$this->methodExists($this->db->function, 'unixtimestamp')) {
            return;
        }

        $datetime = '2008-01-01 00:00:00';
        $expected = 1199163600;

        $quoted_dt = $this->db->quote($datetime, 'timestamp');
        $this->db->pushErrorHandling(PEAR_ERROR_RETURN);
        $this->db->expectError(MDB2_ERROR_UNSUPPORTED);
        $unixts_clause = $this->db->function->unixtimestamp($quoted_dt);
        $this->db->popExpect();
        $this->db->popErrorHandling();
        if (MDB2::isError($unixts_clause) && $unixts_clause->getCode() == MDB2_ERROR_UNSUPPORTED) {
            return;
        }

        $functionTable_clause = $this->db->function->functionTable();
        $query = 'SELECT '.$unixts_clause . $functionTable_clause;
        $result = $this->db->queryOne($query, 'text');
        if (MDB2::isError($result)) {
            $this->fail('Error getting UNIX timestamp: ' . $result->getUserInfo());
        }
        $this->assertEquals($expected, $result);
    }
}
