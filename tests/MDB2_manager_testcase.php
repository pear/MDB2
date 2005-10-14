<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2005 Manuel Lemos, Paul Cooper, Lorenzo Alberton  |
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
// |          Lorenzo Alberton <l dot alberton at quipo dot it>           |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'MDB2/Schema.php';

class MDB2_Manager_TestCase extends PHPUnit_TestCase {
    //contains the dsn of the database we are testing
    var $dsn;
    //contains the options that should be used during testing
    var $options;
    //contains the name of the database we are testing
    var $database;
    //contains the MDB2 object of the db once we have connected
    var $db;
    //test table name (it is dynamically created/dropped)
    var $table = 'newtable';
    //test table fields
    var $fields;

    function MDB2_Manager_Test($name) {
        $this->PHPUnit_TestCase($name);
    }

    function setUp() {
        $this->dsn = $GLOBALS['dsn'];
        $this->options = $GLOBALS['options'];
        $this->database = $GLOBALS['database'];

        $this->db =& MDB2::connect($this->dsn, $this->options);
        $this->db->setDatabase($this->database);
        $this->db->loadModule('Manager');
        $this->db->expectError(MDB2_ERROR_UNSUPPORTED);
        if (PEAR::isError($this->db) || PEAR::isError($this->db->manager) ) {
            $this->assertTrue(false, 'Could not connect to manager in setUp');
            exit;
        }
        $this->fields = array(
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
        if (!$this->tableExists()) {
            $this->db->manager->createTable($this->table, $this->fields);
        }
    }

    function tearDown() {
        if ($this->tableExists()) {
            $this->db->manager->dropTable($this->table);
        }
        $this->db->popExpect();
        unset($this->dsn);
        if (!PEAR::isError($this->db->manager)) {
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

    function tableExists() {
        $tables = $this->db->manager->listTables();
        return in_array($this->table, $tables);
    }

    /**
     * Create a sample table, test the new fields, and drop it.
     */
    function testCreateTable() {
        if (!$this->methodExists($this->db->manager, 'createTable')) {
            return;
        }
        if ($this->tableExists()) {
            $this->db->manager->dropTable($this->table);
        }

        $result = $this->db->manager->createTable($this->table, $this->fields);
        $this->assertFalse(PEAR::isError($result), 'Error creating table');
    }

    /**
     *
     */
    function testListTableFields() {
        if (!$this->methodExists($this->db->manager, 'listTableFields')) {
            return;
        }
        $this->assertEquals(
            array_keys($this->fields),
            $this->db->manager->listTableFields($this->table),
            'Error creating table: incorrect fields'
        );
    }

    /**
     *
     */
    function testCreateIndex() {
        if (!$this->methodExists($this->db->manager, 'createIndex')) {
            return;
        }
        $index = array(
            'fields' => array(
                'name' => array(
                    'sorting' => 'ascending',
                ),
            ),
        );
        $name = 'simpleindex';
        $result = $this->db->manager->createIndex($this->table, $name, $index);
        $this->assertFalse(PEAR::isError($result), 'Error creating index');
    }

    /**
     *
     */
    function testCreateUniqueIndex() {
        if (!$this->methodExists($this->db->manager, 'createIndex')) {
            return;
        }
        $index = array(
            'fields' => array(
                'name' => array(
                    'sorting' => 'ascending',
                ),
            ),
            'unique' => true,
        );
        $name = 'uniqueindex';
        $result = $this->db->manager->createIndex($this->table, $name, $index);
        $this->assertFalse(PEAR::isError($result), 'Error creating unique index');
    }

    /**
     *
     */
    function testDropIndex() {
        if (!$this->methodExists($this->db->manager, 'dropIndex')) {
            return;
        }
        $index = array(
            'fields' => array(
                'name' => array(
                    'sorting' => 'ascending',
                ),
            ),
        );
        $name = 'simpleindex';
        $result = $this->db->manager->createIndex($this->table, $name, $index);
        if (PEAR::isError($result)) {
            $this->assertFalse(true, 'Error creating index');
        } else {
            $result = $this->db->manager->dropIndex($this->table, $name);
            $this->assertFalse(PEAR::isError($result), 'Error dropping index');
            $indices = $this->db->manager->listTableIndexes($this->table);
            $this->assertFalse(PEAR::isError($indices), 'Error listing indices');
            $this->assertFalse(in_array($name, $indices), 'Error dropping index');
        }
    }

    /**
     *
     */
    function testListIndexes() {
        if (!$this->methodExists($this->db->manager, 'listTableIndexes')) {
            return;
        }
        $index = array(
            'fields' => array(
                'name' => array(
                    'sorting' => 'ascending',
                ),
            ),
        );
        $name = 'simpleindex';
        $result = $this->db->manager->createIndex($this->table, $name, $index);
        if (PEAR::isError($result)) {
            $this->assertFalse(true, 'Error creating index');
        } else {
            $indices = $this->db->manager->listTableIndexes($this->table);
            $this->assertFalse(PEAR::isError($indices), 'Error listing indices');
            $this->assertTrue(in_array($name, $indices), 'Error listing indices');
        }
    }

    /**
     *
     */
    function testCreatePrimaryKey() {
        if (!$this->methodExists($this->db->manager, 'createConstraint')) {
            return;
        }
        $index = array(
            'fields' => array(
                'id' => array(
                    'sorting' => 'ascending',
                ),
            ),
            'primary' => true,
        );
        $name = 'pkindex';
        $result = $this->db->manager->createConstraint($this->table, $name, $index);
        $this->assertFalse(PEAR::isError($result), 'Error creating primary index');
    }

    /**
     *
     */
    function testDropPrimaryKey() {
        if (!$this->methodExists($this->db->manager, 'dropConstraint')) {
            return;
        }
        $index = array(
            'fields' => array(
                'id' => array(
                    'sorting' => 'ascending',
                ),
            ),
            'primary' => true,
        );
        $name = 'pkindex';
        $result = $this->db->manager->createConstraint($this->table, $name, $index);
        if (PEAR::isError($result)) {
        $this->assertFalse(true, 'Error creating primary index');
        } else {
            $result = $this->db->manager->dropConstraint($this->table, $name);
            $this->assertFalse(PEAR::isError($result), 'Error dropping primary key index');
        }
    }

    /**
     *
     */
    function testListConstraints() {
        if (!$this->methodExists($this->db->manager, 'listTableConstraint')) {
            return;
        }
        $index = array(
            'fields' => array(
                'id' => array(
                    'sorting' => 'ascending',
                ),
            ),
            'primary' => true,
        );
        $name = 'pkindex';
        $result = $this->db->manager->createConstraint($this->table, $name, $index);
        if (PEAR::isError($result)) {
        $this->assertFalse(true, 'Error creating primary index');
        } else {
            $constraints = $this->db->manager->listTableConstraints($this->table);
            $this->assertFalse(PEAR::isError($constraints), 'Error listing constraints');
            $this->assertTrue(in_array($name, $constraints), 'Error listing primary key index');
        }
    }

    /**
     *
     */
    function testListTables() {
        if (!$this->methodExists($this->db->manager, 'listTables')) {
            return;
        }
        $this->assertTrue($this->tableExists(), 'Error listing tables');
    }

    /**
     *
     */
    function testAlterTable() {
        if (!$this->methodExists($this->db->manager, 'alterTable')) {
            return;
        }
        $changes = array(
            'add' => array(
                'quota' => array(
                    'type' => 'integer',
                    'unsigned' => 1,
                ),
            ),
            'remove' => array(
                'description' => array(),
            ),
            'change' => array(
                'name' => array(
                    'length' => '20',
                    'definition' => array(
                        'type' => 'text',
                        'length' => 20,
                    ),
                )
            ),
            'rename' => array(
                'sex' => array(
                    'name' => 'gender',
                    'definition' => array(
                        'type' => 'text',
                        'length' => 1,
                        'default' => 'M',
                    ),
                ),
            ),
        );

        $result = $this->db->manager->alterTable($this->table, $changes, false);
        $this->assertFalse(PEAR::isError($result), 'Error altering table');

        $altered_table_fields = $this->db->manager->listTableFields($this->table);
        foreach ($changes['add'] as $newfield => $dummy) {
            $this->assertTrue(in_array($newfield, $altered_table_fields), 'Error: new field "'.$newfield.'"not added');
        }
        foreach ($changes['remove'] as $newfield => $dummy) {
            $this->assertFalse(in_array($newfield, $altered_table_fields), 'Error: field "'.$newfield.'"not removed');
        }
        foreach ($changes['rename'] as $oldfield => $newfield) {
            $this->assertFalse(in_array($oldfield, $altered_table_fields), 'Error: field "'.$oldfield.'"not renamed');
            $this->assertTrue(in_array($newfield['name'], $altered_table_fields), 'Error: field "'.$oldfield.'"not renamed correctly');
        }

        /*
        //ideally, get the new table definition, and check all field properties
        $this->db->loadModule('Reverse');
        $altered_table = $this->db->reverse->tableInfo($this->table);
        $this->assertFalse(PEAR::isError($altered_table), 'Error getting table info');
        //echo '<pre>';var_dump($altered_table);echo '</pre>';
        //check field properties
        */
    }

    /**
     *
     */
    function testDropTable() {
        if (!$this->methodExists($this->db->manager, 'dropTable')) {
            return;
        }
        $result = $this->db->manager->dropTable($this->table);
        $this->assertFalse(PEAR::isError($result), 'Error dropping table');
    }

    /**
     *
     */
    function testListTablesNoTable() {
        if (!$this->methodExists($this->db->manager, 'listTables')) {
            return;
        }
        $result = $this->db->manager->dropTable($this->table);
        $this->assertFalse($this->tableExists(), 'Error listing tables');
    }
    
    /**
     *
     */
    function testSequences() {
        if (!$this->methodExists($this->db->manager, 'createSequence')) {
            return;
        }
        $seq_name = 'testsequence';
        $result = $this->db->manager->createSequence($seq_name);
        $this->assertFalse(PEAR::isError($result), 'Error creating a sequence');
        $this->assertTrue(in_array($seq_name, $this->db->manager->listSequences()), 'Error listing sequences');
        $result = $this->db->manager->dropSequence($seq_name);
        $this->assertFalse(PEAR::isError($result), 'Error dropping a sequence');
        $this->assertFalse(in_array($seq_name, $this->db->manager->listSequences()), 'Error listing sequences');
    }
}
?>