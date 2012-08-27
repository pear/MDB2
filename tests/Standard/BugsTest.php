<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2006 Manuel Lemos, Paul Cooper                    |
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
// | Author: Paul Cooper <pgc@ucecom.com>                                 |
// +----------------------------------------------------------------------+
//
// $Id$

require_once dirname(__DIR__) . '/autoload.inc';

class Standard_BugsTest extends Standard_Abstract {
    /**
     * @dataProvider provider
     */
    public function testFetchModeBug($ci) {
        $this->manualSetUp($ci);

        $data = array();

        $stmt = $this->db->prepare('INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($this->fields), MDB2_PREPARE_MANIP);

        $data['user_name'] = 'user_=';
        $data['user_password'] = 'somepass';
        $data['subscribed'] = true;
        $data['user_id'] = 0;
        $data['quota'] = sprintf("%.2f", strval(2/100));
        $data['weight'] = sqrt(0);
        $data['access_date'] = MDB2_Date::mdbToday();
        $data['access_time'] = MDB2_Date::mdbTime();
        $data['approved'] = MDB2_Date::mdbNow();

        $result = $stmt->execute(array_values($data));

        if (MDB2::isError($result)) {
            $this->fail('Error executing prepared query '.$result->getMessage());
        }

        $stmt->free();

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users . ' ORDER BY user_name';
        $result = $this->db->query($query);

        if (MDB2::isError($result)) {
            $this->fail('Error selecting from users: '.$result->getMessage());
        }

        $this->db->setFetchMode(MDB2_FETCHMODE_ASSOC);

        $firstRow = $result->fetchRow();
        $this->assertEquals($firstRow['user_name'], $data['user_name'], 'The data returned does not match that expected');

        $result = $this->db->query('SELECT user_name, user_id, quota FROM ' . $this->table_users . ' ORDER BY user_name');
        if (MDB2::isError($result)) {
            $this->fail('Error selecting from users: '.$result->getMessage());
        }
        $this->db->setFetchMode(MDB2_FETCHMODE_ORDERED);

        $value = $result->fetchOne();
        $this->assertEquals($data['user_name'], $value, 'The data returned does not match that expected');
        $result->free();
    }

    /**
     * @see http://bugs.php.net/bug.php?id=22328
     * @dataProvider provider
     */
    public function testBug22328($ci) {
        $this->manualSetUp($ci);

        $result = $this->db->query('SELECT * FROM ' . $this->table_users);
        $this->db->pushErrorHandling(PEAR_ERROR_RETURN);
        $result2 = $this->db->query('SELECT * FROM foo');

        $data = $result->fetchRow();
        $this->db->popErrorHandling();
        $this->assertFalse(MDB2::isError($data), 'Error messages for a query affect result reading of other queries');
    }

    /**
     * @see http://pear.php.net/bugs/bug.php?id=670
     * @dataProvider provider
     */
    public function testBug670($ci) {
        $this->manualSetUp($ci);

        $data['user_name'] = null;
        $data['user_password'] = 'somepass';
        $data['subscribed'] = true;
        $data['user_id'] = 1;
        $data['quota'] = sprintf("%.2f",strval(3/100));
        $data['weight'] = sqrt(1);
        $data['access_date'] = MDB2_Date::mdbToday();
        $data['access_time'] = MDB2_Date::mdbTime();
        $data['approved'] = MDB2_Date::mdbNow();

        $stmt = $this->db->prepare('INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($this->fields), MDB2_PREPARE_MANIP);
        $result = $stmt->execute(array_values($data));

        $result = $this->db->query('SELECT user_name FROM ' . $this->table_users);
        $col = $result->fetchCol('user_name');
        if (MDB2::isError($col)) {
            $this->fail('Error when fetching column first first row as NULL: '.$col->getMessage());
        }

        $data['user_name'] = "user_1";
        $data['user_id'] = 2;

        $result = $stmt->execute(array_values($data));

        $result = $this->db->query('SELECT user_name FROM ' . $this->table_users);
        $col = $result->fetchCol('user_name');
        if (MDB2::isError($col)) {
            $this->fail('Error when fetching column: '.$col->getMessage());
        }

        $data['user_name'] = null;

        $stmt->free();
    }

    /**
     * @see http://pear.php.net/bugs/bug.php?id=681
     * @dataProvider provider
     */
    public function testBug681($ci) {
        $this->manualSetUp($ci);

        $result = $this->db->query('SELECT * FROM ' . $this->table_users . ' WHERE 1=0');

        $numrows = $result->numRows();
        $this->assertEquals(0, $numrows, 'Numrows is not returning 0 for empty result sets');

        $data = $this->getSampleData(1);

        $stmt = $this->db->prepare('INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($this->fields), MDB2_PREPARE_MANIP);
        $result = $stmt->execute(array_values($data));

        $result = $this->db->query('SELECT * FROM ' . $this->table_users);
        $numrows = $result->numRows();
        $this->assertEquals(1, $numrows, 'Numrows is not returning proper value');

        $stmt->free();
    }

    /**
     * @see http://pear.php.net/bugs/bug.php?id=718
     * @dataProvider provider
     */
    public function testBug718($ci) {
        $this->manualSetUp($ci);

        $data = $this->getSampleData(1);

        $stmt = $this->db->prepare('INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($this->fields), MDB2_PREPARE_MANIP);
        $result = $stmt->execute(array_values($data));

        $row = $this->db->queryRow('SELECT a.user_id, b.user_id FROM ' . $this->table_users . ' a, ' . $this->table_users . ' b where a.user_id = b.user_id', array('integer', 'integer'), MDB2_FETCHMODE_ORDERED);
        $this->assertEquals(2, count($row), "Columns with the same name get overwritten in ordered mode");

        $stmt->free();
    }

    /**
     * @see http://pear.php.net/bugs/bug.php?id=946
     * @dataProvider provider
     */
    public function testBug946($ci) {
        $this->manualSetUp($ci);

        $data = array();
        $total_rows = 5;

        $stmt = $this->db->prepare('INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($this->fields), MDB2_PREPARE_MANIP);

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);

            $result = $stmt->execute(array_values($data[$row]));

            if (MDB2::isError($result)) {
                $this->fail('Error executing prepared query: '.$result->getMessage());
            }
        }
        $stmt->free();

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users;

        $this->db->setLimit(3, 1);
        $result = $this->db->query($query);
        $numrows = $result->numRows();
        while ($row = $result->fetchRow()) {
            if (MDB2::isError($row)) {
                $this->fail('Error fetching a row: '.$row->getMessage());
            }
        }
        $result->free();

        $result = $this->db->query($query);
        $numrows = $result->numRows();
        while ($row = $result->fetchRow()) {
            if (MDB2::isError($row)) {
                $this->fail('Error fetching a row: '.$row->getMessage());
            }
        }
        $result->free();
    }

    /**
     * @see http://pear.php.net/bugs/bug.php?id=3146
     * @dataProvider provider
     */
    public function testBug3146($ci) {
        $this->manualSetUp($ci);

        $data = array();
        $total_rows = 5;

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);

            $result = $stmt->execute(array_values($data[$row]));
            if (MDB2::isError($result)) {
                $this->fail('Error executing prepared query: '.$result->getMessage());
            }
        }
        $stmt->free();

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users . ' ORDER BY user_id';
        $result = $this->db->query($query, $this->fields);

        $numrows = $result->numRows($result);

        $this->verifyFetchedValues($result, 0, $data[0]);
        $this->verifyFetchedValues($result, 2, $data[2]);
        $this->verifyFetchedValues($result, null, $data[3]);
        $this->verifyFetchedValues($result, 1, $data[1]);

        $result->free();
    }

    /**
     * Strong typing query result misbehaves when $n_columns > $n_types
     * @see http://pear.php.net/bugs/bug.php?id=9502
     * @dataProvider provider
     */
    public function testBug9502($ci) {
        $this->manualSetUp($ci);

        $row = 5;
        $data = $this->getSampleData($row);
        $stmt = $this->db->prepare('INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($this->fields), MDB2_PREPARE_MANIP);
        $result = $stmt->execute(array_values($data));
        $stmt->free();

        //provide an incomplete and scrambled types array
        $types = array();
        $types['subscribed'] = $this->fields['subscribed'];
        $types['user_name']  = $this->fields['user_name'];
        $types['weight']     = $this->fields['weight'];

        $query = 'SELECT weight, user_name, user_id, quota, subscribed FROM ' . $this->table_users . ' WHERE user_id = '.$row;
        $result = $this->db->queryRow($query, $types, MDB2_FETCHMODE_ASSOC);
        if (MDB2::isError($result)) {
            $this->fail('Error executing query: '.$result->getMessage() .' - '. $result->getUserInfo());
        } else {
            $this->assertInternalType('boolean', $result['subscribed']);
            $this->assertInternalType('numeric', $result['user_id']);
            $this->assertInternalType('float', $result['weight']);
            $this->assertInternalType('string', $result['user_name']);
        }
    }

    /**
     * Type introspection breaks with associative arrays if names are identical
     * @see http://pear.php.net/bugs/bug.php?id=18203
     * @dataProvider provider
     */
    public function testBug18203($ci) {
        $this->manualSetUp($ci);

        $res = $this->db->query("SELECT 1 as id, 2 as id, 'foo' as title", true);
        if (MDB2::isError($res)) {
            $this->fail($res->getMessage());
        }
        $record = $res->fetchRow(MDB2_FETCHMODE_ASSOC);
        $expected = array(
            'id'    => 2,
            'title' => 'foo'
        );
        $this->assertEquals($expected, $record);
    }

    /**
     * Call to a member function seek() on a non-object
     * @see https://pear.php.net/bugs/bug.php?id=18978
     * @dataProvider provider
     */
    public function testBug18978($ci) {
        $this->manualSetUp($ci);

        $data = $this->populateUserData(3);
        $this->db->setFetchMode(MDB2_FETCHMODE_ASSOC);
        MDB2::loadFile('Iterator');

        // This was test in bug.
        $res = $this->db->query('SELECT * FROM ' . $this->table_users, true, true, 'MDB2_BufferedIterator');
        if (MDB2::isError($res)) {
            $this->fail($res->getUserInfo());
        }
        foreach($res as $key => $row) {
            $this->assertEquals($data[$key - 1]['user_name'], $row['user_name']);
        }
        $res->free();

        // Making sure direct instantiation works as well.
        $res = $this->db->query('SELECT * FROM ' . $this->table_users);
        $i = new MDB2_Iterator($res, MDB2_FETCHMODE_ASSOC);
        $i->seek(1);
        $row = $i->current();
        $this->assertEquals($data[1]['user_name'], $row['user_name']);
        unset($i);
        $res->free();

        // Make sure constructor type checking works.
        $this->setExpectedException('PHPUnit_Framework_Error', 'must be an instance of MDB2_Result_Common');
        $i = new MDB2_Iterator('foo');
    }

    /**
     * Make setOption('result_wrap_class') work without convoluted query() calls
     * @see https://pear.php.net/bugs/bug.php?id=16970
     * @dataProvider provider
     */
    public function testRequest16970($ci) {
        $this->manualSetUp($ci);

        $data = $this->populateUserData(1);
        $this->db->setFetchMode(MDB2_FETCHMODE_ASSOC);
        MDB2::loadFile('Iterator');

        switch ($this->db->phptype) {
            case 'mysqli':
                $expect = 'mysqli_result';
                break;
            default:
                $expect = 'resource';
        }

        // Regular behavior.

        $res = $this->db->query('SELECT * FROM ' . $this->table_users);
        $this->assertInstanceOf('MDB2_Result_Common', $res);

        $res = $this->db->query('SELECT * FROM ' . $this->table_users, null, true);
        $this->assertInstanceOf('MDB2_Result_Common', $res);

        $res = $this->db->query('SELECT * FROM ' . $this->table_users, null, false);
        if ($expect == 'resource') {
            $this->assertInternalType('resource', $res);
        } else {
            $this->assertInstanceOf($expect, $res);
        }

        $res = $this->db->query('SELECT * FROM ' . $this->table_users, null, true, true);
        $this->assertInstanceOf('MDB2_Result_Common', $res);

        $res = $this->db->query('SELECT * FROM ' . $this->table_users, null, true, false);
        $this->assertInstanceOf('MDB2_Result_Common', $res);

        $res = $this->db->query('SELECT * FROM ' . $this->table_users, null, true, 'MDB2_BufferedIterator');
        $this->assertEquals('MDB2_BufferedIterator', get_class($res));

        // Setting third parameter to false forces raw results to be returned.

        $res = $this->db->query('SELECT * FROM ' . $this->table_users, null, false, true);
        if ($expect == 'resource') {
            $this->assertInternalType('resource', $res);
        } else {
            $this->assertInstanceOf($expect, $res);
        }

        $res = $this->db->query('SELECT * FROM ' . $this->table_users, null, false, false);
        if ($expect == 'resource') {
            $this->assertInternalType('resource', $res);
        } else {
            $this->assertInstanceOf($expect, $res);
        }

        $res = $this->db->query('SELECT * FROM ' . $this->table_users, null, false, 'MDB2_BufferedIterator');
        if ($expect == 'resource') {
            $this->assertInternalType('resource', $res);
        } else {
            $this->assertInstanceOf($expect, $res);
        }


        // Utilize a default result wrap class.

        $this->db->setOption('result_wrap_class', 'MDB2_Iterator');

        $res = $this->db->query('SELECT * FROM ' . $this->table_users);
        $this->assertEquals('MDB2_Iterator', get_class($res));

        $res = $this->db->query('SELECT * FROM ' . $this->table_users, null, true);
        $this->assertEquals('MDB2_Iterator', get_class($res));

        $res = $this->db->query('SELECT * FROM ' . $this->table_users, null, false);
        if ($expect == 'resource') {
            $this->assertInternalType('resource', $res);
        } else {
            $this->assertInstanceOf($expect, $res);
        }

        $res = $this->db->query('SELECT * FROM ' . $this->table_users, null, true, true);
        $this->assertEquals('MDB2_Iterator', get_class($res));

        $res = $this->db->query('SELECT * FROM ' . $this->table_users, null, true, false);
        $this->assertInstanceOf('MDB2_Result_Common', $res);

        $res = $this->db->query('SELECT * FROM ' . $this->table_users, null, true, 'MDB2_BufferedIterator');
        $this->assertEquals('MDB2_BufferedIterator', get_class($res));

        // Setting third parameter to false forces raw results to be returned.

        $res = $this->db->query('SELECT * FROM ' . $this->table_users, null, false, true);
        if ($expect == 'resource') {
            $this->assertInternalType('resource', $res);
        } else {
            $this->assertInstanceOf($expect, $res);
        }

        $res = $this->db->query('SELECT * FROM ' . $this->table_users, null, false, false);
        if ($expect == 'resource') {
            $this->assertInternalType('resource', $res);
        } else {
            $this->assertInstanceOf($expect, $res);
        }

        $res = $this->db->query('SELECT * FROM ' . $this->table_users, null, false, 'MDB2_BufferedIterator');
        if ($expect == 'resource') {
            $this->assertInternalType('resource', $res);
        } else {
            $this->assertInstanceOf($expect, $res);
        }
    }

    /**
     * non-static functions called statically
     * @see https://pear.php.net/bugs/bug.php?id=18398
     */
    public function testBug18398() {
        $oer = error_reporting(error_reporting() | E_STRICT);
        $dsn = array('phptype' => 'x');
        $db = new MDB2;
        $db->connect($dsn);
        error_reporting($oer);
    }
}
