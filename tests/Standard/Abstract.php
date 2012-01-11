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
// | Authors: Paul Cooper <pgc@ucecom.com>                                |
// |          Daniel Convissor <danielc@php.net>                          |
// +----------------------------------------------------------------------+
//
// $Id$

abstract class Standard_Abstract extends PHPUnit_Framework_TestCase {
    /**
     * Should the tables be cleared in the setUp() and tearDown() methods?
     * @var bool
     */
    protected $clear_tables = true;

    /**
     * The database name currently being tested
     * @var string
     */
    public $database;

    /**
     * The MDB2 object being currently tested
     * @var MDB2_Driver_Common
     */
    public $db;

    /**
     * The DSN of the database that is currently being tested
     * @var array
     */
    public $dsn;

    /**
     * The unserialized value of MDB2_TEST_SERIALIZED_DSNS
     * @var array
     */
    protected static $dsns;

    /**
     * Field names of the test table
     * @var array
     */
    public $fields = array(
            'user_name'     => 'text',
            'user_password' => 'text',
            'subscribed'    => 'boolean',
            'user_id'       => 'integer',
            'quota'         => 'decimal',
            'weight'        => 'float',
            'access_date'   => 'date',
            'access_time'   => 'time',
            'approved'      => 'timestamp',
    );

    /**
     * Options to use on the current database run
     * @var array
     */
    public $options;

    /**
     * @var string  the name of the users table
     */
    public $table_users = 'mdb2_users';

    /**
     * @var string  the name of the file table
     */
    public $table_files = 'mdb2_files';


    /**
     * Override PHPUnit's default behavior so authentication data doesn't
     * get broadcasted
     */
    protected function getDataSetAsString($strict = true) {
        return parent::getDataSetAsString(false);
    }

    public static function setUpBeforeClass() {
        $dsns = unserialize(MDB2_TEST_SERIALIZED_DSNS);
        self::$dsns = $dsns;
    }

    /**
     * A PHPUnit dataProvider callback to supply the connection info for tests
     * @uses mdb2_test_db_object_provider()
     * @return array  the $dsn and $options information for MDB2::factory()
     */
    public function provider() {
        return mdb2_test_db_object_provider();
    }

    /**
     * Establishes the class properties for each test
     *
     * Can not use setUp() because we are using a dataProvider to get multiple
     * MDB2 objects per test.
     *
     * @param array $ci  an associative array with two elements.  The "dsn"
     *                   element must contain an array of DSN information.
     *                   The "options" element must be an array of connection
     *                   options.
     */
    protected function manualSetUp($ci) {
        $this->db = MDB2::factory($ci['dsn'], $ci['options']);
        if (MDB2::isError($this->db)) {
            $this->markTestSkipped($this->db->getMessage());
        }
        $this->dsn = self::$dsns[$this->db->phptype]['dsn'];
        $this->options = self::$dsns[$this->db->phptype]['options'];
        $this->database = $this->db->getDatabase();

        $this->db->setDatabase($this->database);
        if ($this->database == ':memory:') {
            // Disable messages from other packages while building schema.
            $prior = error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);
            build_schema($this->db);
            error_reporting($prior);
        }
        $this->db->expectError(MDB2_ERROR_UNSUPPORTED);
        $this->clearTables();
    }

    public function tearDown() {
        if (!$this->db || MDB2::isError($this->db)) {
            return;
        }
        $this->clearTables();
        $this->db->disconnect();
        $this->db->popExpect();
        unset($this->db);
    }


    public function clearTables() {
        if (!$this->clear_tables) {
            return;
        }
        $this->db->exec('DELETE FROM ' . $this->table_users);
        $this->db->exec('DELETE FROM ' . $this->table_files);
    }

    public function supported($feature) {
        if (!$this->db->supports($feature)) {
            return false;
        }
        return true;
    }

    /**
     * Checks if a result is an MDB2 error and calls the
     * appropriate PHPUnit method if it is
     *
     * + MDB2_ERROR_UNSUPPORTED: markTestSkipped(not supported)
     * + MDB2_ERROR_NOT_CAPABLE: markTestSkipped(not supported)
     * + MDB2_ERROR_NO_PERMISSION: markTestSkipped(lacks permission)
     * + MDB2_ERROR_ACCESS_VIOLATION: markTestSkipped(lacks permission)
     * + Other errors: fail(error details)
     *
     * NOTE: calling PHPUnit's skip and fail methods causes the current
     * test to halt execution, so no conditional statements or other error
     * handling are needed by this method or the test methods calling this
     * method.
     *
     * @param mixed $result   the query result to inspect
     * @param string $action  a description of what is being checked
     * @return void
     */
    public function checkResultForErrors($result, $action)
    {
        if (MDB2::isError($result)) {
            if ($result->getCode() == MDB2_ERROR_UNSUPPORTED
                || $result->getCode() == MDB2_ERROR_NOT_CAPABLE) {
                $this->markTestSkipped("$action not supported");
            }
            if ($result->getCode() == MDB2_ERROR_NO_PERMISSION
                || $result->getCode() == MDB2_ERROR_ACCESS_VIOLATION)
            {
                $this->markTestSkipped("User lacks permission to $action");
            }
            $this->fail("$action ERROR: ".$result->getUserInfo());
        }
    }

    /**
     * @param MDB2_Result_Common $result  the query result to check
     * @param type $rownum  the row in the $result to check
     * @param type $data  the expected data
     * @return bool
     */
    public function verifyFetchedValues(&$result, $rownum, $data) {
        $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC, $rownum);
        if (!is_array($row)) {
            $result->free();
            $this->fail('Error result row is not an array');
            return;
        }

        foreach ($this->fields as $field => $type) {
            $value = $row[$field];
            if ($type == 'float') {
                $delta = 0.0000000001;
            } else {
                $delta = 0;
            }
            $this->assertEquals($data[$field], $value, "the value retrieved for field \"$field\" doesn't match what was stored into the rownum $rownum", $delta);
        }
    }

    public function getSampleData($row = 1) {
        $data = array();
        $data['user_name']     = 'user_' . $row;
        $data['user_password'] = 'somepass';
        $data['subscribed']    = $row % 2 ? true : false;
        $data['user_id']       = $row;
        $data['quota']         = strval($row/100);
        $data['weight']        = sqrt($row);
        $data['access_date']   = MDB2_Date::mdbToday();
        $data['access_time']   = MDB2_Date::mdbTime();
        $data['approved']      = MDB2_Date::mdbNow();
        return $data;
    }

    /**
     * Populates the user table with some data and then returns the data for
     * later comparison
     *
     * @param int $rows  the number for rows to insert
     * @return array  a multi-dimensional associative array of the data inserted
     */
    public function populateUserData($rows = 1) {
        $result = $this->db->loadModule('Extended');
        if (MDB2::isError($result)) {
            $this->fail('populateUserData() problem loading module: ' . $result->getUserInfo());
        }

        $this->db->loadModule('Extended');
        $stmt = $this->db->extended->autoPrepare($this->table_users,
            array_keys($this->fields),
            MDB2_AUTOQUERY_INSERT, null, $this->fields);

        if (MDB2::isError($stmt)) {
            $this->fail('populateUserData() problem preparing statement: ' . $stmt->getUserInfo());
        }

        $data_save = array();
        $data_return = array();
        for ($i = 0; $i < $rows; $i++) {
            $row = $this->getSampleData($i);
            $data_save[] = array_values($row);
            $data_return[] = $row;
        }

        $result = $this->db->extended->executeMultiple($stmt, $data_save);
        if (MDB2::isError($result)) {
            $this->fail('populateUserData() problem inserting the data: ' . $result->getUserInfo());
        }

        return $data_return;
    }

    public function methodExists(&$class, $name) {
        if (is_object($class)
            && in_array(strtolower($name), array_map('strtolower', get_class_methods($class)))
        ) {
            return true;
        }
        //$this->fail('method '. $name.' not implemented in '.get_class($class));
        return false;
    }

    public function tableExists($table) {
        $this->db->loadModule('Manager', null, true);
        $tables = $this->db->manager->listTables();
        if (MDB2::isError($tables)) {
            //$this->fail('Cannot list tables: '. $tables->getUserInfo());
            return false;
        }
        return in_array(strtolower($table), array_map('strtolower', $tables));
    }
}
