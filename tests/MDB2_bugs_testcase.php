<?php
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2004 Manuel Lemos, Paul Cooper                    |
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
// $Id:

class MDB2_Bugs_TestCase extends PHPUnit_TestCase {
    //contains the dsn of the database we are testing
    var $dsn;
    //contains the options that should be used during testing
    var $options;
    //contains the name of the database we are testing
    var $database;
    //contains the MDB2 object of the db once we have connected
    var $db;
    // contains field names from the test table
    var $fields;
    // contains the types of the fields from the test table
    var $types;

    function MDB2_Bugs_TestCase($name) {
        $this->PHPUnit_TestCase($name);
    }

    function setUp() {
        $this->dsn = $GLOBALS['dsn'];
        $this->options = $GLOBALS['options'];
        $this->database = $GLOBALS['database'];
        $this->db =& MDB2::connect($this->dsn, $this->options);
        if (MDB2::isError($this->db)) {
            $this->assertTrue(false, 'Could not connect to database in setUp');
            exit;
        }
        $this->db->setDatabase($this->database);
        $this->fields = array(
                        'user_name',
                        'user_password',
                        'subscribed',
                        'user_id',
                        'quota',
                        'weight',
                        'access_date',
                        'access_time',
                        'approved'
                    );
        $this->types = array(
                        'text',
                        'text',
                        'boolean',
                        'integer',
                        'decimal',
                        'float',
                        'date',
                        'time',
                        'timestamp'
                    );
        $this->clearTables();
    }

    function tearDown() {
        $this->clearTables();
        unset($this->dsn);
        if (!MDB2::isError($this->db)) {
            $this->db->disconnect();
        }
        unset($this->db);
    }

    function clearTables() {
        if (MDB2::isError($this->db->query('DELETE FROM users'))) {
            $this->assertTrue(false, 'Error deleting from table users');
        }
        if (MDB2::isError($this->db->query('DELETE FROM files'))) {
            $this->assertTrue(false, 'Error deleting from table users');
        }
    }

    function insertTestValues($prepared_query, &$data) {
        for ($i = 0; $i < count($this->fields); $i++) {
            $this->db->setParam($prepared_query, ($i + 1), $data[$this->fields[$i]]);
        }
    }

    /**
     *
     */
    function testFetchModeBug() {
        $data = array();

        $prepared_query = $this->db->prepare('INSERT INTO users (user_name, user_password, subscribed, user_id, quota, weight, access_date, access_time, approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $this->types);

        $data['user_name'] = "user_=";
        $data['user_password'] = 'somepassword';
        $data['subscribed'] = true;
        $data['user_id'] = 0;
        $data['quota'] = sprintf("%.2f",strval(2/100));
        $data['weight'] = sqrt(0);
        $data['access_date'] = MDB2_Date::mdbToday();
        $data['access_time'] = MDB2_Date::mdbTime();
        $data['approved'] = MDB2_Date::mdbNow();

        $this->insertTestValues($prepared_query, $data);

        $result = $this->db->execute($prepared_query);

        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
        }

        $this->db->freePrepared($prepared_query);

        $result =& $this->db->query('SELECT * FROM users ORDER BY user_name');

        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }

        $this->db->setFetchMode(MDB2_FETCHMODE_ASSOC);

        $value = $result->fetch();
        $this->assertEquals($value, $data['user_name'], "The data returned ($value) does not match that expected (".$data['user_name'].")");
        $result->free();
    }

    /**
     * http://bugs.php.net/bug.php?id=22328
     */
    function testBug22328() {
        $result =& $this->db->query('SELECT * FROM users');
        $this->db->pushErrorHandling(PEAR_ERROR_RETURN);
        $result2 = $this->db->query('SELECT * FROM foo');

        $data = $result->fetchRow();
        $this->db->popErrorHandling();
        $this->assertEquals(false, MDB2::isError($data), "Error messages for a query affect result reading of other queries");
    }
}

?>