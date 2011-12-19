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
     * A PHPUnit dataProvider callback to supply the MDB2 objects for testing
     * @uses mdb2_test_db_object_provider()
     * @return array  the MDB2_Driver_Common objects to test against
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
        if (!$this->db || PEAR::isError($this->db)) {
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
        $this->db->exec('DELETE FROM users');
        $this->db->exec('DELETE FROM files');
    }

    public function supported($feature) {
        if (!$this->db->supports($feature)) {
            return false;
        }
        return true;
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

    public function methodExists(&$class, $name) {
        if (is_object($class)
            && in_array(strtolower($name), array_map('strtolower', get_class_methods($class)))
        ) {
            return true;
        }
        $this->fail('method '. $name.' not implemented in '.get_class($class));
        return false;
    }

    public function tableExists($table) {
        $this->db->loadModule('Manager', null, true);
        $tables = $this->db->manager->listTables();
        if (PEAR::isError($tables)) {
            $this->fail('Cannot list tables: '. $tables->getUserInfo());
            return false;
        }
        return in_array(strtolower($table), array_map('strtolower', $tables));
    }
}
