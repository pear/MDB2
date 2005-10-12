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

class MDB2_Reverse_TestCase extends PHPUnit_TestCase
{
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

    function MDB2_Reverse_Test($name) {
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
        $this->db->loadModule('Reverse');
        $this->fields = array(
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
    }

    function tearDown() {
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

    function tableExists($table) {
        $tables = $this->db->manager->listTables();
        return in_array($table, $tables);
    }

    /**
     * Test tableInfo('table_name')
     */
    function testTableInfo()
    {
        if (!$this->methodExists($this->db->reverse, 'tableInfo')) {
            return;
        }

        $table_info = $this->db->reverse->tableInfo('users');
        if (PEAR::isError($table_info)) {
            $this->assertTrue(false, 'Error in tableInfo(): '.$table_info->getMessage());
        } else {
            $this->assertEquals(count($this->fields), count($table_info), 'The number of fields retrieved is different from the expected one');
            foreach ($table_info as $field_info) {
                $this->assertEquals('users', $field_info['table'], "the table name is not correct");
                if (!array_key_exists(strtolower($field_info['name']), $this->fields)) {
                    $this->assertTrue(false, 'Field names do not match ('.$field_info['name'].' is unknown)');
                }
                //expand test, for instance adding a check on types...
            }
        }
    }

    /**
     * Test getTableFieldDefinition($table, $field)
     */
    function testGetTableFieldDefinition()
    {
        if (!$this->methodExists($this->db->reverse, 'getTableFieldDefinition')) {
            return;
        }

        //test integer not null
        $field_info = $this->db->reverse->getTableFieldDefinition('files', 'id');
        if (PEAR::isError($field_info)) {
            $this->assertTrue(false, 'Error in getTableFieldDefinition(): '.$field_info->getMessage());
        } else {
            $field_info = array_pop($field_info);
            $this->assertEquals('integer', $field_info['type'], 'The field type is different from the expected one');
            $this->assertEquals(4, $field_info['length'], 'The field length is different from the expected one');
            $this->assertTrue($field_info['notnull'], 'The field can be null unlike it was expected');
            $this->assertEquals('', $field_info['default'], 'The field default value is different from the expected one');
        }

        //test blob
        $field_info = $this->db->reverse->getTableFieldDefinition('files', 'document');
        if (PEAR::isError($field_info)) {
            $this->assertTrue(false, 'Error in getTableFieldDefinition(): '.$field_info->getMessage());
        } else {
            $field_info = array_pop($field_info);
            $this->assertEquals('blob', $field_info['type'], 'The field type is different from the expected one');
            $this->assertFalse($field_info['notnull'], 'The field cannot be null unlike it was expected');
            $this->assertEquals(null, $field_info['default'], 'The field default value is different from the expected one');
        }

        //test varchar(100) not null
        $field_info = $this->db->reverse->getTableFieldDefinition('numbers', 'trans_en');
        if (PEAR::isError($field_info)) {
            $this->assertTrue(false, 'Error in getTableFieldDefinition(): '.$field_info->getMessage());
        } else {
            $field_info = array_pop($field_info);
            $this->assertEquals('text', $field_info['type'], 'The field type is different from the expected one');
            $this->assertEquals(100, $field_info['length'], 'The field length is different from the expected one');
            $this->assertTrue($field_info['notnull'], 'The field can be null unlike it was expected');
            $this->assertEquals('', $field_info['default'], 'The field default value is different from the expected one');
        }
    }

    /**
     * Test getTableIndexDefinition($table, $index)
     */
    function testGetTableIndexDefinition()
    {
        if (!$this->methodExists($this->db->reverse, 'getTableIndexDefinition')) {
            return;
        }

        //setup
        $this->db->loadModule('Manager');
        $fields = array(
            'id' => array(
                'type'     => 'integer',
                'unsigned' => 1,
                'notnull'  => 1,
                'default'  => 0,
            ),
            'name' => array(
                'type'   => 'text',
                'length' => 12,
            ),
            'description' => array(
                'type'   => 'text',
                'length' => 12,
            ),
            'sex' => array(
                'type' => 'text',
                'length' => 1,
                'default' => 'M',
            ),
        );
        $table = 'newtable';
        if ($this->tableExists($table)) {
            $result = $this->db->manager->dropTable($table);
            $this->assertFalse(PEAR::isError($result), 'Error dropping table');
        }
        $result = $this->db->manager->createTable($table, $fields);
        $this->assertFalse(PEAR::isError($result), 'Error creating table');
        $indices = array(
            'uniqueindex' => array(
                'fields' => array(
                    'name' => array(
                        'sorting' => 'ascending',
                    ),
                ),
                'unique' => true,
            ),
            'pkindex' => array(
                'fields' => array(
                    'id' => array(
                        'sorting' => 'ascending',
                    ),
                ),
                'primary' => true,
            ),
            'multipleindex' => array(
                'fields' => array(
                    'description' => array(
                        'sorting' => 'ascending',
                    ),
                    'sex' => array(
                        'sorting' => 'ascending',
                    ),
                ),
            ),
        );
        foreach ($indices as $index_name => $index) {
            $result = $this->db->manager->createIndex($table, $index_name, $index);
            $this->assertFalse(PEAR::isError($result), 'Error creating index');
        }

        //test
        foreach ($indices as $index_name => $index) {
            $result = $this->db->reverse->getTableIndexDefinition($table, $index_name);
            $this->assertFalse(PEAR::isError($result), 'Error getting table index definition');
            $field_names = array_keys($index['fields']);
            $this->assertEquals($field_names, array_keys($result['fields']), 'Error listing index fields');
            if (!empty($index['unique'])) {
                $this->assertTrue($result['unique'], 'Error: missing UNIQUE constraint');
            }
            if (!empty($index['primary'])) {
                $this->assertTrue($result['primary'], 'Error: missing PRIMARY KEY constraint');
            }
        }
    }

    /**
     * Test getSequenceDefinition($sequence)
     */
    function testGetSequenceDefinition() {
        //setup
        $this->db->loadModule('Manager');
        $sequence = 'test_sequence';
        $result = $this->db->manager->createSequence($sequence);
        $this->assertFalse(PEAR::isError($result), 'Error creating a sequence');

        //test
        $start = $this->db->nextId($sequence);
        $def = $this->db->reverse->getSequenceDefinition($sequence);
        $this->assertEquals($start+1, $def['start'], 'Error getting sequence definition');

        //cleanup
        $result = $this->db->manager->dropSequence($sequence);
        $this->assertFalse(PEAR::isError($result), 'Error dropping a sequence');
    }
}
?>