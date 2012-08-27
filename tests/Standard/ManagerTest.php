<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2008 Manuel Lemos, Paul Cooper, Lorenzo Alberton  |
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
// |          Daniel Convissor <danielc@php.net>                          |
// +----------------------------------------------------------------------+
//
// $Id$

require_once dirname(__DIR__) . '/autoload.inc';

class Standard_ManagerTest extends Standard_Abstract {
    //test table name (it is dynamically created/dropped)
    public $table = 'newtable';

    /**
     * The non-standard helper
     * @var Nonstandard_Base
     */
    protected $nonstd;


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

        $this->nonstd = Nonstandard_Base::factory($this->db, $this);

        $this->db->loadModule('Manager', null, true);
        $this->fields = array(
            'id' => array(
                'type'     => 'integer',
                'unsigned' => true,
                'notnull'  => true,
                'default'  => 0,
            ),
            'somename' => array(
                'type'     => 'text',
                'length'   => 12,
            ),
            'somedescription'  => array(
                'type'     => 'text',
                'length'   => 12,
            ),
            'sex' => array(
                'type'     => 'text',
                'length'   => 1,
                'default'  => 'M',
            ),
        );
        $options = array();
        if ('mysql' == substr($this->db->phptype, 0, 5)) {
            $options['type'] = 'innodb';
        }
        if (!$this->tableExists($this->table)) {
            $result = $this->db->manager->createTable($this->table, $this->fields, $options);
            $this->checkResultForErrors($result, 'createTable');
        }
    }

    public function tearDown() {
        if (!$this->db || MDB2::isError($this->db)) {
            return;
        }
        if ($this->tableExists($this->table)) {
            $this->db->manager->dropTable($this->table);
        }
        parent::tearDown();
    }

    /**
     * @covers MDB2_Driver_Manager_Common::createTable()
     * @covers MDB2_Driver_Manager_Common::listTables()
     * @covers MDB2_Driver_Manager_Common::dropTable()
     * @dataProvider provider
     */
    public function testTableActions($ci) {
        $this->manualSetUp($ci);

        // Make sure it doesn't exist before trying to create it.
        if ($this->methodExists($this->db->manager, 'dropTable')) {
            $this->db->manager->dropTable($this->table);
        } else {
            $this->db->exec("DROP TABLE $this->table");
        }

        $action = 'createTable';
        if (!$this->methodExists($this->db->manager, $action)) {
            $this->markTestSkipped("Driver lacks $action() method");
        }
        $result = $this->db->manager->createTable($this->table, $this->fields);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        $action = 'listTables';
        if ($this->methodExists($this->db->manager, $action)) {
            $result = $this->db->manager->listTables();
            $this->checkResultForErrors($result, $action);
            $this->assertContains($this->table, $result,
                    "Result of $action() does not contain expected value");
        }

        $action = 'dropTable';
        if (!$this->methodExists($this->db->manager, $action)) {
            $this->db->exec("DROP TABLE $this->table");
            $this->markTestSkipped("Driver lacks $action() method");
        }
        $result = $this->db->manager->dropTable($this->table);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        // Check that it's actually gone.
        if ($this->tableExists($this->table)) {
            $this->fail("dropTable() passed but the table still exists");
        }
    }

    /**
     * Create a sample table, test the new fields, and drop it.
     * @dataProvider provider
     */
    public function testCreateAutoIncrementTable($ci) {
        $this->manualSetUp($ci);

        if (!$this->methodExists($this->db->manager, 'createTable')) {
            $this->markTestSkipped("Driver lacks createTable() method");
        }
        if ($this->tableExists($this->table)) {
            $this->db->manager->dropTable($this->table);
        }
        $seq_name = $this->table;
        if ('ibase' == $this->db->phptype) {
            $seq_name .= '_id';
        }
        //remove existing PK sequence
        $sequences = $this->db->manager->listSequences();
        if (in_array($seq_name, $sequences)) {
            $this->db->manager->dropSequence($seq_name);
        }

        $action = 'createTable';
        $fields = $this->fields;
        $fields['id']['autoincrement'] = true;
        $result = $this->db->manager->createTable($this->table, $fields);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        $query = 'INSERT INTO '.$this->db->quoteIdentifier($this->table, true);
        $query.= ' (somename, somedescription)';
        $query.= ' VALUES (:somename, :somedescription)';
        $stmt = $this->db->prepare($query, array('text', 'text'), MDB2_PREPARE_MANIP);
        $this->checkResultForErrors($stmt, 'prepare');

        $values = array(
            'somename' => 'foo',
            'somedescription' => 'bar',
        );
        $rows = 5;
        for ($i =0; $i < $rows; ++$i) {
            $result = $stmt->execute($values);
            $this->checkResultForErrors($result, 'execute');
        }
        $stmt->free();

        $query = 'SELECT id FROM '.$this->table;
        $data = $this->db->queryCol($query, 'integer');
        $this->checkResultForErrors($data, 'queryCol');
        for ($i=0; $i<$rows; ++$i) {
            if (!isset($data[$i])) {
                $this->fail('Error in data returned by select');
            }
            if ($data[$i] !== ($i+1)) {
                $this->fail('Error executing autoincrementing insert');
            }
        }
    }

    /**
     * @dataProvider provider
     */
    public function testListTableFields($ci) {
        $this->manualSetUp($ci);

        if (!$this->methodExists($this->db->manager, 'listTableFields')) {
            $this->markTestSkipped("Driver lacks listTableFields() method");
        }
        $this->assertEquals(
            array_keys($this->fields),
            $this->db->manager->listTableFields($this->table),
            'Error creating table: incorrect fields'
        );
    }

    /**
     * @covers MDB2_Driver_Manager_Common::createIndex()
     * @covers MDB2_Driver_Manager_Common::listTableIndexes()
     * @covers MDB2_Driver_Manager_Common::dropIndex()
     * @dataProvider provider
     */
    public function testIndexActions($ci) {
        $this->manualSetUp($ci);

        $index = array(
            'fields' => array(
                'somename' => array(
                    'sorting' => 'ascending',
                ),
            ),
        );
        $name = 'simpleindex';

        $action = 'createIndex';
        if (!$this->methodExists($this->db->manager, $action)) {
            $this->markTestSkipped("Driver lacks $action() method");
        }
        $result = $this->db->manager->createIndex($this->table, $name, $index);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        $action = 'listTableIndexes';
        if ($this->methodExists($this->db->manager, $action)) {
            $result = $this->db->manager->listTableIndexes($this->table);
            $this->checkResultForErrors($result, $action);
            $this->assertContains($name, $result,
                    "Result of $action() does not contain expected value");
        }

        $action = 'dropIndex';
        if (!$this->methodExists($this->db->manager, $action)) {
            $this->markTestSkipped("Driver lacks $action() method");
        }
        $result = $this->db->manager->dropIndex($this->table, $name);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        // Check that it's actually gone.
        $action = 'listTableIndexes';
        if ($this->methodExists($this->db->manager, $action)) {
            $result = $this->db->manager->listTableIndexes($this->table);
            $this->checkResultForErrors($result, $action);
            $this->assertNotContains($name, $result,
                    "dropIndex() passed but the index is still there");
        }
    }

    /**
     * @dataProvider provider
     */
    public function testCreatePrimaryKey($ci) {
        $this->manualSetUp($ci);

        if (!$this->methodExists($this->db->manager, 'createConstraint')) {
            $this->markTestSkipped("Driver lacks createConstraint() method");
        }
        $constraint = array(
            'fields' => array(
                'id' => array(
                    'sorting' => 'ascending',
                ),
            ),
            'primary' => true,
        );
        $name = 'pkindex';

        $action = 'createConstraint';
        if (!$this->methodExists($this->db->manager, $action)) {
            $this->markTestSkipped("Driver lacks $action() method");
        }
        $result = $this->db->manager->createConstraint($this->table, $name, $constraint);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");
    }

    /**
     * @covers MDB2_Driver_Manager_Common::createConstraint()
     * @covers MDB2_Driver_Manager_Common::listTableConstraints()
     * @covers MDB2_Driver_Manager_Common::dropConstraint()
     * @dataProvider provider
     */
    public function testConstraintActions($ci) {
        $this->manualSetUp($ci);

        $constraint = array(
            'fields' => array(
                'id' => array(
                    'sorting' => 'ascending',
                ),
            ),
            'unique' => true,
        );
        $name = 'uniqueindex';

        $action = 'createConstraint';
        if (!$this->methodExists($this->db->manager, $action)) {
            $this->markTestSkipped("Driver lacks $action() method");
        }
        // Make sure it doesn't exist before trying to create it.
        $this->db->manager->dropConstraint($this->table, $name);
        $result = $this->db->manager->createConstraint($this->table, $name, $constraint);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        $action = 'listTableConstraints';
        $result = $this->db->manager->listTableConstraints($this->table);
        $this->checkResultForErrors($result, $action);
        $this->assertContains($name, $result,
                "Result of $action() does not contain expected value");

        $action = 'dropConstraint';
        $result = $this->db->manager->dropConstraint($this->table, $name);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        // Check that it's actually gone.
        $action = 'listTableConstraints';
        $result = $this->db->manager->listTableConstraints($this->table);
        $this->checkResultForErrors($result, $action);
        $this->assertNotContains($name, $result,
                "dropConstraint() passed but the constraint is still there");
    }

    /**
     * MYSQL NOTE:  If this test fails with native code 1005
     * "Can't create table './peartest/#sql-540_2c871.frm' (errno: 150)"
     * that means your server's default storage engine is MyISAM.
     * Edit my.cnf to have "default-storage-engine = InnoDB"
     *
     * @dataProvider provider
     */
    public function testCreateForeignKeyConstraint($ci) {
        $this->manualSetUp($ci);

        $constraint = array(
            'fields' => array(
                'id' => array(
                    'sorting' => 'ascending',
                ),
            ),
            'foreign' => true,
            'references' => array(
                'table' => $this->table_users,
                'fields' => array(
                    'user_id' => array(
                        'position' => 1,
                    ),
                ),
            ),
            'initiallydeferred' => false,
            'deferrable' => false,
            'match' => 'SIMPLE',
            'onupdate' => 'CASCADE',
            'ondelete' => 'CASCADE',
        );

        $name = 'fkconstraint';

        $action = 'createConstraint';
        if (!$this->methodExists($this->db->manager, $action)) {
            $this->markTestSkipped("Driver lacks $action() method");
        }
        // Make sure it doesn't exist before trying to create it.
        $this->db->manager->dropConstraint($this->table, $name);
        $result = $this->db->manager->createConstraint($this->table, $name, $constraint);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        $action = 'listTableConstraints';
        $result = $this->db->manager->listTableConstraints($this->table);
        $this->checkResultForErrors($result, $action);
        $name_idx = $this->db->getIndexName($name);
        $this->checkResultForErrors($name_idx, 'getIndexName');
        $this->assertTrue(in_array($name_idx, $result)
                || in_array($name, $result),
                "Result of $action() does not contain expected value");


        //now check that it is enforced...

        //insert a row in the primary table
        $result = $this->db->exec('INSERT INTO ' . $this->table_users . ' (user_id) VALUES (1)');
        $this->checkResultForErrors($result, 'exec');

        //insert a row in the FK table with an id that references
        //the newly inserted row on the primary table: should not fail
        $query = 'INSERT INTO '.$this->db->quoteIdentifier($this->table, true)
                .' ('.$this->db->quoteIdentifier('id', true).') VALUES (1)';
        $result = $this->db->exec($query);
        $this->checkResultForErrors($result, 'exec');

        //try to insert a row into the FK table with an id that does not
        //exist in the primary table: should fail
        $query = 'INSERT INTO '.$this->db->quoteIdentifier($this->table, true)
                .' ('.$this->db->quoteIdentifier('id', true).') VALUES (123456)';
        $this->db->pushErrorHandling(PEAR_ERROR_RETURN);
        $this->db->expectError('*');
        $result = $this->db->exec($query);
        $this->db->popExpect();
        $this->db->popErrorHandling();
        $this->assertInstanceOf('MDB2_Error', $result,
                'Foreign Key constraint was not enforced for INSERT query');
        $this->assertEquals(MDB2_ERROR_CONSTRAINT, $result->getCode(),
                "Wrong error code. See full output for clues.\n"
                . $result->getUserInfo());

        //try to update the first row of the FK table with an id that does not
        //exist in the primary table: should fail
        $query = 'UPDATE '.$this->db->quoteIdentifier($this->table, true)
                .' SET '.$this->db->quoteIdentifier('id', true).' = 123456 '
                .' WHERE '.$this->db->quoteIdentifier('id', true).' = 1';
        $this->db->expectError('*');
        $result = $this->db->exec($query);
        $this->db->popExpect();
        $this->assertInstanceOf('MDB2_Error', $result,
                'Foreign Key constraint was not enforced for UPDATE query');
        $this->assertEquals(MDB2_ERROR_CONSTRAINT, $result->getCode(),
                "Wrong error code. See full output for clues.\n"
                . $result->getUserInfo());

        $numrows_query = 'SELECT COUNT(*) FROM '. $this->db->quoteIdentifier($this->table, true);
        $numrows = $this->db->queryOne($numrows_query, 'integer');
        $this->assertEquals(1, $numrows, 'Invalid number of rows in the FK table');

        //update the PK value of the primary table: the new value should be
        //propagated to the FK table (ON UPDATE CASCADE)
        $result = $this->db->exec('UPDATE ' . $this->table_users . ' SET user_id = 2');
        $this->checkResultForErrors($result, 'exec');

        $numrows = $this->db->queryOne($numrows_query, 'integer');
        $this->assertEquals(1, $numrows, 'Invalid number of rows in the FK table');

        $query = 'SELECT id FROM '.$this->db->quoteIdentifier($this->table, true);
        $newvalue = $this->db->queryOne($query, 'integer');
        $this->assertEquals(2, $newvalue, 'The value of the FK field was not updated (CASCADE failed)');

        //delete the row of the primary table: the row in the FK table should be
        //deleted automatically (ON DELETE CASCADE)
        $result = $this->db->exec('DELETE FROM ' . $this->table_users);
        $this->checkResultForErrors($result, 'exec');

        $numrows = $this->db->queryOne($numrows_query, 'integer');
        $this->assertEquals(0, $numrows, 'Invalid number of rows in the FK table (CASCADE failed)');


        $action = 'dropConstraint';
        $result = $this->db->manager->dropConstraint($this->table, $name);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");
    }

    /**
     * @dataProvider provider
     */
    public function testDropPrimaryKey($ci) {
        $this->manualSetUp($ci);

        if (!$this->methodExists($this->db->manager, 'dropConstraint')) {
            $this->markTestSkipped("Driver lacks dropConstraint() method");
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

        $action = 'createConstraint';
        $result = $this->db->manager->createConstraint($this->table, $name, $index);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        $action = 'dropConstraint';
        $result = $this->db->manager->dropConstraint($this->table, $name, true);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");
    }

    /**
     * @dataProvider provider
     */
    public function testListDatabases($ci) {
        $this->manualSetUp($ci);

        $action = 'listDatabases';
        if (!$this->methodExists($this->db->manager, $action)) {
            $this->markTestSkipped("Driver lacks $action() method");
        }
        $result = $this->db->manager->listDatabases();
        $this->checkResultForErrors($result, $action);
        $this->assertTrue(in_array(strtolower($this->database), $result), 'Error listing databases');
    }

    /**
     * @dataProvider provider
     */
    public function testAlterTable($ci) {
        $this->manualSetUp($ci);

        $newer = 'newertable';
        if ($this->tableExists($newer)) {
            $this->db->manager->dropTable($newer);
        }
        $changes = array(
            'add' => array(
                'quota' => array(
                    'type' => 'integer',
                    'unsigned' => 1,
                ),
                'note' => array(
                    'type' => 'text',
                    'length' => '20',
                ),
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
            'change' => array(
                'id' => array(
                    'unsigned' => false,
                    'definition' => array(
                        'type'     => 'integer',
                        'notnull'  => false,
                        'default'  => 0,
                    ),
                ),
                'somename' => array(
                    'length' => '20',
                    'definition' => array(
                        'type' => 'text',
                        'length' => 20,
                    ),
                )
            ),
            'remove' => array(
                'somedescription' => array(),
            ),
            'name' => $newer,
        );

        $action = 'alterTable';
        if (!$this->methodExists($this->db->manager, $action)) {
            $this->markTestSkipped("Driver lacks $action() method");
        }
        $this->db->expectError(MDB2_ERROR_CANNOT_ALTER);
        $result = $this->db->manager->alterTable($this->table, $changes, true);
        $this->db->popExpect();
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        $result = $this->db->manager->alterTable($this->table, $changes, false);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");
    }

    /**
     * @dataProvider provider
     */
    public function testAlterTable2($ci) {
        $this->manualSetUp($ci);

        $newer = 'newertable2';
        if ($this->tableExists($newer)) {
            $this->db->manager->dropTable($newer);
        }
        $changes_all = array(
            'add' => array(
                'quota' => array(
                    'type' => 'integer',
                    'unsigned' => 1,
                ),
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
            'change' => array(
                'somename' => array(
                    'length' => '20',
                    'definition' => array(
                        'type' => 'text',
                        'length' => 20,
                    ),
                )
            ),
            'remove' => array(
                'somedescription' => array(),
            ),
            'name' => $newer,
        );

        $action = 'alterTable';
        if (!$this->methodExists($this->db->manager, $action)) {
            $this->markTestSkipped("Driver lacks $action() method");
        }

        foreach ($changes_all as $type => $change) {
            $changes = array($type => $change);
            $this->db->expectError(MDB2_ERROR_CANNOT_ALTER);
            $result = $this->db->manager->alterTable($this->table, $changes, true);
            $this->db->popExpect();
            $this->checkResultForErrors($result, $action);
            $this->assertEquals(MDB2_OK, $result,
                    "$action did not return MDB2_OK");

            $result = $this->db->manager->alterTable($this->table, $changes, false);
            $this->checkResultForErrors($result, $action);
            $this->assertEquals(MDB2_OK, $result,
                    "$action did not return MDB2_OK");

            switch ($type) {
                case 'add':
                    $altered_table_fields = $this->db->manager->listTableFields($this->table);
                    $this->checkResultForErrors($altered_table_fields, 'listTableFields');
                    foreach ($change as $newfield => $dummy) {
                        $this->assertContains($newfield, $altered_table_fields,
                                "Field '$newfield' was not added");
                    }
                    break;
                case 'rename':
                    $altered_table_fields = $this->db->manager->listTableFields($this->table);
                    $this->checkResultForErrors($altered_table_fields, 'listTableFields');
                    foreach ($change as $oldfield => $newfield) {
                        $this->assertNotContains($oldfield, $altered_table_fields,
                                "Field '$oldfield' was not renamed");

                        $this->assertContains($newfield['name'], $altered_table_fields,
                                "While '$oldfield' is gone, '{$newfield['name']}' is not there");
                    }
                    break;
                case 'change':
                    break;
                case 'remove':
                    $altered_table_fields = $this->db->manager->listTableFields($this->table);
                    $this->checkResultForErrors($altered_table_fields, 'listTableFields');
                    foreach ($change as $newfield => $dummy) {
                        $this->assertNotContains($newfield, $altered_table_fields,
                                "Field '$oldfield' was not removed");
                    }
                    break;
                case 'name':
                    if ($this->tableExists($newer)) {
                        $this->db->manager->dropTable($newer);
                    } else {
                        $this->fail('Error: table "'.$this->table.'" not renamed');
                    }
                    break;
            }
        }
    }

    /**
     * @dataProvider provider
     */
    public function testTruncateTable($ci) {
        $this->manualSetUp($ci);

        if (!$this->methodExists($this->db->manager, 'truncateTable')) {
            $this->markTestSkipped("Driver lacks truncateTable() method");
        }

        $query = 'INSERT INTO '.$this->table;
        $query.= ' (id, somename, somedescription)';
        $query.= ' VALUES (:id, :somename, :somedescription)';
        $stmt = $this->db->prepare($query, array('integer', 'text', 'text'), MDB2_PREPARE_MANIP);
        $this->checkResultForErrors($stmt, 'prepare');

        $rows = 5;
        for ($i=1; $i<=$rows; ++$i) {
            $values = array(
                'id' => $i,
                'somename' => 'foo'.$i,
                'somedescription' => 'bar'.$i,
            );
            $result = $stmt->execute($values);
            $this->checkResultForErrors($result, 'execute');
        }
        $stmt->free();
        $count = $this->db->queryOne('SELECT COUNT(*) FROM '.$this->table, 'integer');
        $this->checkResultForErrors($count, 'queryOne');
        $this->assertEquals($rows, $count, 'Error: invalid number of rows returned');

        $action = 'truncateTable';
        $result = $this->db->manager->truncateTable($this->table);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        $count = $this->db->queryOne('SELECT COUNT(*) FROM '.$this->table, 'integer');
        $this->checkResultForErrors($count, 'queryOne');
        $this->assertEquals(0, $count, 'Error: invalid number of rows returned');
    }

    /**
     * @dataProvider provider
     */
    public function testListTablesNoTable($ci) {
        $this->manualSetUp($ci);

        if (!$this->methodExists($this->db->manager, 'listTables')) {
            $this->markTestSkipped("Driver lacks listTables() method");
        }
        $result = $this->db->manager->dropTable($this->table);
        $this->assertFalse($this->tableExists($this->table), 'Error listing tables');
    }

    /**
     * @covers MDB2_Driver_Manager_Common::createSequence()
     * @covers MDB2_Driver_Manager_Common::listSequences()
     * @covers MDB2_Driver_Manager_Common::dropSequence()
     * @dataProvider provider
     */
    public function testSequences($ci) {
        $this->manualSetUp($ci);

        $name = 'testsequence';

        $action = 'createSequence';
        if (!$this->methodExists($this->db->manager, $action)) {
            $this->markTestSkipped("Driver lacks $action() method");
        }
        // Make sure it doesn't exist before trying to create it.
        $this->db->manager->dropSequence($name);
        $result = $this->db->manager->createSequence($name);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        $action = 'listSequences';
        $result = $this->db->manager->listSequences();
        $this->checkResultForErrors($result, $action);
        $this->assertContains($name, $result,
                "Result of $action() does not contain expected value");

        $action = 'dropSequence';
        $result = $this->db->manager->dropSequence($name);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        // Check that it's actually gone.
        $action = 'listSequences';
        $result = $this->db->manager->listSequences();
        $this->checkResultForErrors($result, $action);
        $this->assertNotContains($name, $result,
                "dropSequence() passed but the sequence is still there");
    }

    /**
     * @covers MDB2_Driver_Manager_Common::listTableTriggers()
     * @dataProvider provider
     */
    public function testListTableTriggers($ci) {
        $this->manualSetUp($ci);

        if (!$this->nonstd) {
            $this->markTestSkipped('No Nonstandard Helper for this phptype.');
        }

        $name = 'test_newtrigger';

        /*
         * Have test suite helper functions setup the environment.
         */
        $this->nonstd->dropTrigger($name, $this->table);
        $result = $this->nonstd->createTrigger($name, $this->table);
        $this->checkResultForErrors($result, 'create trigger helper');


        /*
         * The actual tests.
         */
        $action = 'listTableTriggers';
        $result = $this->db->manager->listTableTriggers($this->table);
        $this->checkResultForErrors($result, $action);
        $this->assertContains($name, $result,
                "Result of $action() does not contain expected value");

        $action = 'listTableTriggers on non-existant table';
        $result = $this->db->manager->listTableTriggers('fake_table');
        $this->checkResultForErrors($result, $action);
        $this->assertNotContains($name, $result,
                "$action should not contain this view");


        /*
         * Have test suite helper functions clean up the environment.
         */
        $result = $this->nonstd->dropTrigger($name, $this->table);
        $this->checkResultForErrors($result, 'drop trigger helper');
    }

    /**
     * @covers MDB2_Driver_Manager_Common::listTableViews()
     * @dataProvider provider
     */
    public function testListTableViews($ci) {
        $this->manualSetUp($ci);

        if (!$this->nonstd) {
            $this->markTestSkipped('No Nonstandard Helper for this phptype.');
        }

        $name = 'test_newview';

        /*
         * Have test suite helper functions setup the environment.
         */
        $this->nonstd->dropView($name);
        $result = $this->nonstd->createView($name, $this->table);
        $this->checkResultForErrors($result, 'create view helper');


        /*
         * The actual tests.
         */
        $action = 'listTableViews';
        $result = $this->db->manager->listTableViews($this->table);
        $this->checkResultForErrors($result, $action);
        $this->assertContains($name, $result,
                "Result of $action() does not contain expected value");

        $action = 'listTableViews on non-existant table';
        $result = $this->db->manager->listTableViews('fake_table');
        $this->checkResultForErrors($result, $action);
        $this->assertNotContains($name, $result,
                "$action should not contain this view");


        /*
         * Have test suite helper functions clean up the environment.
         */
        $result = $this->nonstd->dropView($name);
        $this->checkResultForErrors($result, 'drop view helper');
    }

    /**
     * Test listUsers()
     * @dataProvider provider
     */
    public function testListUsers($ci) {
        $this->manualSetUp($ci);

        $action = 'listUsers';
        $result = $this->db->manager->listUsers();
        $this->checkResultForErrors($result, $action);
        $result = array_map('strtolower', $result);
        $this->assertContains(strtolower($this->db->dsn['username']), $result,
                "Result of $action() does not contain expected value");
    }

    /**
     * @covers MDB2_Driver_Manager_Common::listFunctions()
     * @dataProvider provider
     */
    public function testFunctionActions($ci) {
        $this->manualSetUp($ci);

        if (!$this->nonstd) {
            $this->markTestSkipped('No Nonstandard Helper for this phptype.');
        }

        $name = 'test_add';

        /*
         * Have test suite helper functions setup the environment.
         */
        $this->nonstd->dropFunction($name);
        $this->db->pushErrorHandling(PEAR_ERROR_RETURN);
        $this->db->expectError('*');
        $result = $this->nonstd->createFunction($name);
        $this->db->popExpect();
        $this->db->popErrorHandling();
        $this->checkResultForErrors($result, 'crete function helper');


        /*
         * The actual tests.
         */
        $action = 'listFunctions';
        $result = $this->db->manager->listFunctions();
        $this->checkResultForErrors($result, $action);
        $this->assertContains($name, $result,
                "Result of $action() does not contain expected value");


        /*
         * Have test suite helper functions clean up the environment.
         */
        $result = $this->nonstd->dropFunction($name);
        $this->checkResultForErrors($result, 'drop function helper');
    }

    /**
     * @covers MDB2_Driver_Manager_Common::createDatabase()
     * @covers MDB2_Driver_Manager_Common::alterDatabase()
     * @covers MDB2_Driver_Manager_Common::listDatabases()
     * @covers MDB2_Driver_Manager_Common::dropDatabase()
     * @dataProvider provider
     */
    public function testCrudDatabase($ci) {
        $this->manualSetUp($ci);

        $name = 'mdb2_test_newdb';
        $rename = $name . '_renamed';
        $unlink = false;
        switch ($this->db->phptype) {
            case 'sqlite':
                $name = tempnam(sys_get_temp_dir(), $name);
                $rename = $name . '_renamed';
                unlink($name);
                $unlink = true;
                break;
        }

        $options = array(
            'charset' => 'UTF8',
            'collation' => 'utf8_bin',
        );
        $changes = array(
            'name' => $rename,
            'charset' => 'UTF8',
        );
        if ('pgsql' == substr($this->db->phptype, 0, 5)) {
            $options['charset'] = 'WIN1252';
        }
        if ('mssql' == substr($this->db->phptype, 0, 5)) {
            $options['collation'] = 'WIN1252';
            $options['collation'] = 'Latin1_General_BIN';
        }

        $action = 'createDatabase';
        $result = $this->db->manager->createDatabase($name, $options);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        $action = 'listDatabases';
        $result = $this->db->manager->listDatabases();
        $this->checkResultForErrors($result, $action);
        $this->assertContains($name, $result,
                "Result of $action() does not contain expected value");

        $action = 'alterDatabase';
        $result = $this->db->manager->alterDatabase($name, $changes);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        $action = 'listDatabases';
        $result = $this->db->manager->listDatabases();
        $this->checkResultForErrors($result, $action);
        if (!in_array($rename, $result)) {
            $this->db->manager->dropDatabase($name);
            $this->fail('Error: could not find renamed database');
        }

        $action = 'dropDatabase';
        $result = $this->db->manager->dropDatabase($rename);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        // Check that it's actually gone.
        $action = 'listDatabases';
        $result = $this->db->manager->listDatabases();
        $this->checkResultForErrors($result, $action);
        $this->assertNotContains($rename, $result,
                "dropDatabase() passed but the database is still there");
    }

    /**
     * Test vacuum
     * @dataProvider provider
     */
    public function testVacuum($ci) {
        $this->manualSetUp($ci);

        $action = 'vacuum table';
        $result = $this->db->manager->vacuum($this->table);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        $action = 'vacuum and analyze table';
        $options = array(
            'analyze' => true,
            'full'    => true,
            'freeze'  => true,
        );
        $result = $this->db->manager->vacuum($this->table, $options);
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");

        $action = 'vacuum all tables';
        $result = $this->db->manager->vacuum();
        $this->checkResultForErrors($result, $action);
        $this->assertEquals(MDB2_OK, $result,
                "$action did not return MDB2_OK");
    }
}
