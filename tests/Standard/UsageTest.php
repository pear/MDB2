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

require_once dirname(__DIR__) . '/autoload.inc';

class Standard_UsageTest extends Standard_Abstract {
    /**
     * Test typed data storage and retrieval
     *
     * This tests typed data storage and retrieval by executing a single
     * prepared query and then selecting the data back from the database
     * and comparing the results
     *
     * @dataProvider provider
     */
    public function testStorage($ci) {
        $this->manualSetUp($ci);

        $data = $this->getSampleData(1234);

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }
        $result = $stmt->execute(array_values($data));
        $stmt->free();

        if (MDB2::isError($result)) {
            $this->fail('Error executing prepared query: ' . $result->getUserInfo());
        }

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users;
        $result = $this->db->query($query, $this->fields);
        if (MDB2::isError($result)) {
            $this->fail('Error selecting from users: ' . $result->getUserInfo());
        }

        $this->verifyFetchedValues($result, 0, $data);
        $result->free();
    }

    /**
     * Test fetchOne()
     *
     * This test bulk fetching of result data by using a prepared query to
     * insert an number of rows of data and then retrieving the data columns
     * one by one
     *
     * @dataProvider provider
     */
    public function testFetchOne($ci) {
        $this->manualSetUp($ci);

        $data = array();
        $total_rows = 5;

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);
            $result = $stmt->execute(array_values($data[$row]));

            if (MDB2::isError($result)) {
                @$stmt->free();
                $this->fail('Error executing prepared query: ' . $result->getUserInfo());
            }
        }

        $stmt->free();

        foreach ($this->fields as $field => $type) {
            for ($row = 0; $row < $total_rows; $row++) {
                $result = $this->db->query('SELECT '.$field.' FROM ' . $this->table_users . ' WHERE user_id='.$row, $type);
                $value = $result->fetchOne();
                $result->free();
                if (MDB2::isError($value)) {
                    $this->fail('Error fetching row '.$row.' for field '.$field.' of type '.$type);
                }
                $this->assertEquals(strval($data[$row][$field]), strval(trim($value)), 'the query field '.$field.' of type '.$type.' for row '.$row);
            }
        }
    }

    /**
     * Test fetchCol()
     *
     * Test fetching a column of result data. Two different columns are retrieved
     *
     * @dataProvider provider
     */
    public function testFetchCol($ci) {
        $this->manualSetUp($ci);

        $data = array();
        $total_rows = 5;

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);
            $result = $stmt->execute(array_values($data[$row]));
            if (MDB2::isError($result)) {
                @$stmt->free();
                $this->fail('Error executing prepared query: ' . $result->getUserInfo());
            }
        }

        $stmt->free();

        $first_col = array();
        for ($row = 0; $row < $total_rows; $row++) {
            $first_col[$row] = "user_$row";
        }

        $second_col = array();
        for ($row = 0; $row < $total_rows; $row++) {
            $second_col[$row] = $row;
        }

        $query = 'SELECT user_name, user_id FROM ' . $this->table_users . ' ORDER BY user_name';
        $result = $this->db->query($query, array('text', 'integer'));
        if (MDB2::isError($result)) {
            $this->fail('Error during query: ' . $result->getUserInfo());
        }
        $values = $result->fetchCol(0);
        $result->free();
        if (MDB2::isError($values)) {
            $this->fail('Error fetching first column');
        }
        $this->assertEquals($first_col, $values);

        $query = 'SELECT user_name, user_id FROM ' . $this->table_users . ' ORDER BY user_name';
        $result = $this->db->query($query, array('text', 'integer'));
        if (MDB2::isError($result)) {
            $this->fail('Error during query: ' . $result->getUserInfo());
        }
        $values = $result->fetchCol(1);
        $result->free();
        if (MDB2::isError($values)) {
            $this->fail('Error fetching second column');
        }
        $this->assertEquals($second_col, $values);
    }

    /**
     * Test fetchAll()
     *
     * Test fetching an entire result set in one shot.
     *
     * @dataProvider provider
     */
    public function testFetchAll($ci) {
        $this->manualSetUp($ci);

        $data = array();
        $total_rows = 5;

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);
            $result = $stmt->execute(array_values($data[$row]));
            if (MDB2::isError($result)) {
                @$stmt->free();
                $this->fail('Error executing prepared query: ' . $result->getUserInfo());
            }
        }
        $stmt->free();

        $fields = array_keys($data[0]);
        $query = 'SELECT '. implode (', ', $fields). ' FROM ' . $this->table_users . ' ORDER BY user_name';
        $result = $this->db->query($query, $this->fields);
        if (MDB2::isError($result)) {
            $this->fail('Error during query: '  . $result->getUserInfo());
        }
        $values = $result->fetchAll(MDB2_FETCHMODE_ASSOC);
        $result->free();
        if (MDB2::isError($values)) {
            $this->fail('Error fetching the result set');
        }
        for ($i=0; $i<$total_rows; $i++) {
            foreach ($data[$i] as $key => $val) {
                $this->assertEquals(strval($val), strval($values[$i][$key]), 'Row #'.$i.' ['.$key.']');
            }
        }

        //test $rekey=true
        $result = $this->db->query('SELECT user_id, user_name FROM ' . $this->table_users . ' ORDER BY user_id', $this->fields);
        if (MDB2::isError($result)) {
            $this->fail('Error during query: ' . $result->getUserInfo());
        }
        $values = $result->fetchAll(MDB2_FETCHMODE_ASSOC, true);
        $result->free();
        if (MDB2::isError($values)) {
            $this->fail('Error fetching the result set');
        }
        for ($i=0; $i<$total_rows; $i++) {
            list($id, $name) = each($values);
            $this->assertEquals($data[$i]['user_id'],   $id,   'Row #'.$i.' ["user_id"]');
            $this->assertEquals($data[$i]['user_name'], $name, 'Row #'.$i.' ["user_name"]');
        }


        //test $rekey=true, $force_array=true
        $result = $this->db->query('SELECT user_id, user_name FROM ' . $this->table_users . ' ORDER BY user_id', $this->fields);
        if (MDB2::isError($result)) {
            $this->fail('Error during query: ' . $result->getUserInfo());
        }
        $values = $result->fetchAll(MDB2_FETCHMODE_ASSOC, true, true);
        $result->free();
        if (MDB2::isError($values)) {
            $this->fail('Error fetching the result set');
        }
        for ($i=0; $i<$total_rows; $i++) {
            list($id, $value) = each($values);
            $this->assertEquals($data[$i]['user_id'],   $id,                 'Row #'.$i.' ["user_id"]');
            $this->assertEquals($data[$i]['user_name'], $value['user_name'], 'Row #'.$i.' ["user_name"]');
        }

        //test $rekey=true, $force_array=true, $group=true
        $result = $this->db->query('SELECT user_password, user_name FROM ' . $this->table_users . ' ORDER BY user_name', $this->fields);
        if (MDB2::isError($result)) {
            $this->fail('Error during query: ' . $result->getUserInfo());
        }
        $values = $result->fetchAll(MDB2_FETCHMODE_ASSOC, true, true, true);
        $result->free();
        if (MDB2::isError($values)) {
            $this->fail('Error fetching the result set');
        }
        //all the records have the same user_password value
        $this->assertEquals(1, count($values), 'Error: incorrect number of returned rows');
        $values = $values[$data[0]['user_password']];
        for ($i=0; $i<$total_rows; $i++) {
            $this->assertEquals($data[$i]['user_name'], $values[$i]['user_name'], 'Row #'.$i.' ["user_name"]');
        }

        //test $rekey=true, $force_array=true, $group=false (with non unique key)
        $result = $this->db->query('SELECT user_password, user_name FROM ' . $this->table_users . ' ORDER BY user_name', $this->fields);
        if (MDB2::isError($result)) {
            $this->fail('Error during query: ' . $result->getUserInfo());
        }
        $values = $result->fetchAll(MDB2_FETCHMODE_ASSOC, true, true, false);
        $result->free();
        if (MDB2::isError($values)) {
            $this->fail('Error fetching the result set');
        }
        //all the records have the same user_password value, they are overwritten
        $this->assertEquals(1, count($values), 'Error: incorrect number of returned rows');
        $key = $data[0]['user_password'];
        $this->assertEquals(1, count($values[$key]), 'Error: incorrect number of returned rows');
        $this->assertEquals($data[4]['user_name'], $values[$key]['user_name']);
    }

    /**
     * Test different fetch modes
     *
     * Test fetching results using different fetch modes
     * NOTE: several tests still missing
     *
     * @dataProvider provider
     */
    public function testFetchModes($ci) {
        $this->manualSetUp($ci);

        $data = array();
        $total_rows = 5;

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);
            $result = $stmt->execute(array_values($data[$row]));

            if (MDB2::isError($result)) {
                @$stmt->free();
                $this->fail('Error executing prepared query: ' . $result->getUserInfo());
            }
        }

        $stmt->free();

        // test ASSOC
        $query = 'SELECT A.user_name FROM ' . $this->table_users . ' A, ' . $this->table_users . ' B WHERE A.user_id = B.user_id';
        $value = $this->db->queryRow($query, array($this->fields['user_name']), MDB2_FETCHMODE_ASSOC);
        if (MDB2::isError($value)) {
            $this->fail('Error fetching the result set');
        }
        $this->assertTrue(!empty($value['user_name']), 'Error fetching the associative result set from join');
    }

    /**
     * Test multi_query option
     *
     * This test attempts to send multiple queries at once using the multi_query
     * option and then retrieves each result.
     *
     * @dataProvider provider
     */
    public function testMultiQuery($ci) {
        $this->manualSetUp($ci);

        $multi_query_orig = $this->db->getOption('multi_query');
        if (MDB2::isError($multi_query_orig) && ($multi_query_orig->getCode() == MDB2_ERROR_UNSUPPORTED)) {
            $this->markTestSkipped('Multi query not supported');
        }

        $this->db->setOption('multi_query', true);

        $data = array();
        $total_rows = 5;

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);
            $result = $stmt->execute(array_values($data[$row]));

            if (MDB2::isError($result)) {
                @$stmt->free();
                $this->fail('Error executing prepared query: ' . $result->getUserInfo());
            }
        }

        $stmt->free();

        $query = '';
        for ($row = 0; $row < $total_rows; $row++) {
            $query.= 'SELECT user_name FROM ' . $this->table_users . ' WHERE user_id='.$row.';';
        }
        $result = $this->db->query($query, 'text');

        for ($row = 0; $row < $total_rows; $row++) {
            $value = $result->fetchOne();
            if (MDB2::isError($value)) {
                $this->fail('Error fetching row '.$row);
            }
            $this->assertEquals(strval($data[$row]['user_name']), strval(trim($value)), 'the query field username of type "text" for row '.$row);
            if (MDB2::isError($result->nextResult())) {
                $this->fail('Error moving result pointer');
            }
        }

        $result->free();
    }

    /**
     * Test prepared queries
     *
     * Tests prepared queries, making sure they correctly deal with ?, !, and '
     *
     * @dataProvider provider
     */
    public function testPreparedQueries($ci) {
        $this->manualSetUp($ci);

        $data = array(
            array(
                'user_name' => 'Sure!',
                'user_password' => 'Do work?',
                'user_id' => 1,
            ),
            array(
                'user_name' => 'For Sure!',
                'user_password' => "Doesn't?",
                'user_id' => 2,
            ),
        );

        $query = "INSERT INTO $this->table_users (user_name, user_password, user_id) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($query, array('text', 'text', 'integer'), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $text = $data[0]['user_name'];
        $question = $data[0]['user_password'];
        $userid = $data[0]['user_id'];

        // bind out of order
        $stmt->bindParam(0, $text);
        $stmt->bindParam(2, $userid);
        $stmt->bindParam(1, $question);

        $result = $stmt->execute();
        if (MDB2::isError($result)) {
            @$stmt->free();
            $this->fail('Could not execute prepared query with question mark placeholders. Error: ' . $result->getUserInfo());
        }

        $text = $data[1]['user_name'];
        $question = $data[1]['user_password'];
        $userid = $data[1]['user_id'];

        $result = $stmt->execute();
        $stmt->free();
        if (MDB2::isError($result)) {
            $this->fail('Could not execute prepared query with bound parameters. Error: ' . $result->getUserInfo());
        }
        $this->clearTables();

        $query = "INSERT INTO $this->table_users (user_name, user_password, user_id) VALUES (:text, :question, :userid)";
        $stmt = $this->db->prepare($query, array('text', 'text', 'integer'), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $stmt->bindValue('text', $data[0]['user_name']);
        $stmt->bindValue('question', $data[0]['user_password']);
        $stmt->bindValue('userid', $data[0]['user_id']);

        $result = $stmt->execute();
        $stmt->free();
        if (MDB2::isError($result)) {
            $this->fail('Could not execute prepared query with named placeholders. Error: ' . $result->getUserInfo());
        }

        $query = "INSERT INTO $this->table_users (user_name, user_password, user_id) VALUES (".$this->db->quote($data[1]['user_name'], 'text').", :question, :userid)";
        $stmt = $this->db->prepare($query, array('text', 'integer'), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $stmt->bindValue('question', $data[1]['user_password']);
        $stmt->bindValue('userid', $data[1]['user_id']);

        $result = $stmt->execute();
        $stmt->free();
        if (MDB2::isError($result)) {
            $this->fail('Could not execute prepared query with named placeholders and a quoted text value in front. Error: ' . $result->getUserInfo());
        }

        $query = 'SELECT user_name, user_password, user_id FROM ' . $this->table_users . ' WHERE user_id=:user_id';
        $stmt = $this->db->prepare($query, array('integer'), array('text', 'text', 'integer'));
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }
        foreach ($data as $row_data) {
            $result = $stmt->execute(array('user_id' => $row_data['user_id']));
            if (MDB2::isError($result)) {
                @$stmt->free();
                $this->fail('Could not execute prepared. Error: '.$result->getUserinfo());
            }
            $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
            if (!is_array($row)) {
                @$result->free();
                @$stmt->free();
                $this->fail('Prepared SELECT failed');
            }
            $diff = (array)array_diff($row, $row_data);
            $this->assertTrue(empty($diff), 'Prepared SELECT failed for fields: '.implode(', ', array_keys($diff)));
        }
        $result->free();
        $stmt->free();

        $row_data = reset($data);
        $query = 'SELECT user_name, user_password, user_id FROM ' . $this->table_users . ' WHERE user_id='.$this->db->quote($row_data['user_id'], 'integer');
        $stmt = $this->db->prepare($query, null, array('text', 'text', 'integer'));
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }
        $result = $stmt->execute(array());
        $stmt->free();
        if (MDB2::isError($result)) {
            $this->fail('Could not execute prepared statement with no placeholders. Error: '.$result->getUserinfo());
        }
        $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        if (!is_array($row)) {
            @$result->free();
            $this->fail('Prepared SELECT failed');
        }
        $diff = (array)array_diff($row, $row_data);
        $this->assertTrue(empty($diff), 'Prepared SELECT failed for fields: '.implode(', ', array_keys($diff)));
        $stmt->free();

        $row_data = reset($data);
        $query = 'SELECT user_name, user_password, user_id FROM ' . $this->table_users . ' WHERE user_name='.$this->db->quote($row_data['user_name'], 'text').' AND user_id = ? AND user_password='.$this->db->quote($row_data['user_password'], 'text');
        $stmt = $this->db->prepare($query, array('integer'), array('text', 'text', 'integer'));
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }
        $result = $stmt->execute(array($row_data['user_id']));
        $stmt->free();
        if (MDB2::isError($result)) {
            $this->fail('Could not execute prepared with quoted text fields around a placeholder. Error: '.$result->getUserinfo());
        }
        $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        $result->free();
        if (!is_array($row)) {
            $this->fail('Prepared SELECT failed');
        }
        $diff = (array)array_diff($row, $row_data);
        $this->assertTrue(empty($diff), 'Prepared SELECT failed for fields: '.implode(', ', array_keys($diff)));

        foreach ($this->db->sql_comments as $comment) {
            $query = 'SELECT user_name, user_password, user_id FROM ' . $this->table_users . ' WHERE '.$comment['start'].' maps to class::foo() '.$comment['end'].' user_name=:username';
            $row_data = reset($data);
            $stmt = $this->db->prepare($query, array('text'), array('text', 'text', 'integer'));
            if (MDB2::isError($stmt)) {
                $this->fail('Error preparing query: ' . $stmt->getUserInfo());
            }
            $result = $stmt->execute(array('username' => $row_data['user_name']));
            $stmt->free();
            if (MDB2::isError($result)) {
                $this->fail('Could not execute prepared where a name parameter is contained in an SQL comment ('.$comment['start'].'). Error: '.$result->getUserinfo());
            }
            $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
            $result->free();
            if (!is_array($row)) {
                $this->fail('Prepared SELECT failed');
            }
            $diff = (array)array_diff($row, $row_data);
            $this->assertTrue(empty($diff), 'Prepared SELECT failed for fields: '.implode(', ', array_keys($diff)));
        }

        $row_data = reset($data);
        $query = 'SELECT user_name, user_password, user_id FROM ' . $this->table_users . ' WHERE user_name=:username OR user_password=:username';
        $stmt = $this->db->prepare($query, array('text'), array('text', 'text', 'integer'));
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }
        $result = $stmt->execute(array('username' => $row_data['user_name']));
        $stmt->free();
        if (MDB2::isError($result)) {
            $this->fail('Could not execute prepared where the same named parameter is used twice. Error: '.$result->getUserinfo());
        }
        $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        $result->free();
        if (!is_array($row)) {
            $this->fail('Prepared SELECT failed');
        }
        $diff = (array)array_diff($row, $row_data);
        $this->assertTrue(empty($diff), 'Prepared SELECT failed for fields: '.implode(', ', array_keys($diff)));
    }


// TODO:  go through rest of file fixing order of execution & freeing resources.


    /**
     * Test _skipDelimitedStrings(), used by prepare()
     *
     * If the placeholder is contained within a delimited string, it must be skipped,
     * and the cursor position must be advanced
     *
     * @dataProvider provider
     */
    public function testSkipDelimitedStrings($ci) {
        $this->manualSetUp($ci);

        //test correct placeholder
        $query = 'SELECT what FROM tbl WHERE x = ?';
        $position = 0;
        $p_position = strpos($query, '?');
        $this->assertEquals($position, $this->db->_skipDelimitedStrings($query, $position, $p_position), 'Error: the cursor position has changed');

        //test placeholder within a quoted string
        $query = 'SELECT what FROM tbl WHERE x = '. $this->db->string_quoting['start'] .'blah?blah'. $this->db->string_quoting['end'] .' AND y = ?';
        $position = 0;
        $p_position = strpos($query, '?');
        $new_pos = $this->db->_skipDelimitedStrings($query, $position, $p_position);
        $this->assertTrue($position !=$new_pos, 'Error: the cursor position was not advanced');

        //test placeholder within a comment
        foreach ($this->db->sql_comments as $comment) {
            $query = 'SELECT what FROM tbl WHERE x = '. $comment['start'] .'blah?blah'. $comment['end'] .' AND y = ?';
            $position = 0;
            $p_position = strpos($query, '?');
            $new_pos = $this->db->_skipDelimitedStrings($query, $position, $p_position);
            $this->assertTrue($position != $new_pos, 'Error: the cursor position was not advanced');
        }

        // bug 17039: http://pear.php.net/bugs/17039
        $query = "SELECT 'a\'b:+c'";
        $position = 0;
        $p_position = strpos($query, ':');
        $new_pos = $this->db->_skipDelimitedStrings($query, $position, $p_position);
        $this->assertTrue($position != $new_pos, 'Error: the cursor position was not advanced');

        // bug 16973: http://pear.php.net/bugs/16973
        if ($this->db->supports('prepared_statements') != 'emulated') {
            $this->db->expectError(MDB2_ERROR_SYNTAX);
            $query = " select '?\\' ";
            $stmt = $this->db->prepare($query);
            $this->assertTrue(MDB2::isError($stmt), 'Expected Exception query with an unterminated text string specified');
            $this->db->popExpect();

            $query = " select 'a\\'?\\'' ";
            $stmt = $this->db->prepare($query);
            $this->assertFalse(MDB2::isError($stmt));

            $query = " select concat('\\\\\\\\', ?) ";
            $stmt = $this->db->prepare($query);
            $this->assertFalse(MDB2::isError($stmt));

            $this->db->expectError(MDB2_ERROR_SYNTAX);
            $query = " select concat('\\\\\\\\\\', ?) ";
            $stmt = $this->db->prepare($query);
            $this->assertTrue(MDB2::isError($stmt), 'Expected Exception query with an unterminated text string specified');
            $this->db->popExpect();
        }

        //add some tests for named placeholders and for identifier_quoting
    }

    /**
     * Test retrieval of result metadata
     *
     * This tests the result metadata by executing a prepared query and
     * select the data, and checking the result contains the correct
     * number of columns and that the column names are in the correct order
     *
     * @dataProvider provider
     */
    public function testMetadata($ci) {
        $this->manualSetUp($ci);

        $data = $this->getSampleData(1234);

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $result = $stmt->execute(array_values($data));
        $stmt->free();

        if (MDB2::isError($result)) {
            $this->fail('Error executing prepared query: ' . $result->getUserInfo());
        }

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users;
        $result = $this->db->query($query, $this->fields);

        if (MDB2::isError($result)) {
            $this->fail('Error selecting from users: ' . $result->getUserInfo());
        }

        $numcols = $result->numCols();

        $this->assertEquals(count($this->fields), $numcols, "The query result returned an incorrect number of columns unlike expected");

        $column_names = $result->getColumnNames();
        $fields = array_keys($this->fields);
        for ($column = 0; $column < $numcols; $column++) {
            $this->assertEquals($column, $column_names[$fields[$column]], "The query result column \"".$fields[$column]."\" was returned in an incorrect position");
        }

    }

    /**
     * Test storage and retrieval of nulls
     *
     * This tests null storage and retrieval by successively inserting,
     * selecting, and testing a number of null / not null values
     *
     * @dataProvider provider
     */
    public function testNulls($ci) {
        $this->manualSetUp($ci);

        $portability = $this->db->getOption('portability');
        if ($portability & MDB2_PORTABILITY_EMPTY_TO_NULL) {
            $nullisempty = true;
        } else {
            $nullisempty = false;
        }
        $test_values = array(
            array('test', false),
            array('NULL', false),
            array('null', false),
            array('', $nullisempty),
            array(null, true)
        );

        for ($test_value = 0; $test_value <= count($test_values); $test_value++) {
            if ($test_value == count($test_values)) {
                $value = 'NULL';
                $is_null = true;
            } else {
                $value = $this->db->quote($test_values[$test_value][0], 'text');
                $is_null = $test_values[$test_value][1];
            }

            $this->clearTables();

            $result = $this->db->exec("INSERT INTO $this->table_users (user_name,user_password,user_id) VALUES ($value,$value,0)");
            if (MDB2::isError($result)) {
                $this->fail('Error executing insert query: ' . $result->getUserInfo());
            }

            $result = $this->db->query('SELECT user_name,user_password FROM ' . $this->table_users, array('text', 'text'));
            if (MDB2::isError($result)) {
                $this->fail('Error executing select query: ' . $result->getUserInfo());
            }

            if ($is_null) {
                $error_message = 'A query result column is not NULL unlike what was expected';
            } else {
                $error_message = 'A query result column is NULL even though it was expected to be different';
            }

            $row = $result->fetchRow();
            $result->free();
            $this->assertTrue((is_null($row[0]) == $is_null), $error_message);
            $this->assertTrue((is_null($row[1]) == $is_null), $error_message);
        }

        $methods = array('fetchOne', 'fetchRow');

        foreach ($methods as $method) {
            $result = $this->db->query('SELECT user_name FROM ' . $this->table_users . ' WHERE user_id=123', array('text'));
            $value = $result->$method();
            if (MDB2::isError($value)) {
                $this->fail('Error fetching non existent row');
            } else {
                $result->free();
                $this->assertNull($value, 'selecting non existent row with "'.$method.'()" did not return NULL');
            }
        }

        $methods = array('fetchCol', 'fetchAll');

        foreach ($methods as $method) {
            $result = $this->db->query('SELECT user_name FROM ' . $this->table_users . ' WHERE user_id=123', array('text'));
            $value = $result->$method();
            if (MDB2::isError($value)) {
                $this->fail('Error fetching non existent row');
            } else {
                $result->free();
                $this->assertTrue((is_array($value) && empty($value)), 'selecting non existent row with "'.$method.'()" did not return empty array');
            }
        }

        $methods = array('queryOne', 'queryRow');

        foreach ($methods as $method) {
            $value = $this->db->$method('SELECT user_name FROM ' . $this->table_users . ' WHERE user_id=123', array('text'));
            if (MDB2::isError($value)) {
                $this->fail('Error fetching non existent row');
            } else {
                $result->free();
                $this->assertNull($value, 'selecting non existent row with "'.$method.'()" did not return NULL');
            }
        }

        $methods = array('queryCol', 'queryAll');

        foreach ($methods as $method) {
            $value = $this->db->$method('SELECT user_name FROM ' . $this->table_users . ' WHERE user_id=123', array('text'));
            if (MDB2::isError($value)) {
                $this->fail('Error fetching non existent row');
            } else {
                $result->free();
                $this->assertTrue((is_array($value) && empty($value)), 'selecting non existent row with "'.$method.'()" did not return empty array');
            }
        }
    }

    /**
     * Test paged queries
     *
     * Test the use of setLimit to return paged queries
     *
     * @dataProvider provider
     */
    public function testRanges($ci) {
        $this->manualSetUp($ci);

        if (!$this->supported('limit_queries')) {
            $this->markTestSkipped('LIMIT not supported');
        }

        $data = array();
        $total_rows = 5;

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);
            $result = $stmt->execute(array_values($data[$row]));

            if (MDB2::isError($result)) {
                $this->fail('Error executing prepared query: ' . $result->getUserInfo());
            }
        }

        $stmt->free();

        for ($rows = 2, $start_row = 0; $start_row < $total_rows; $start_row += $rows) {

            $this->db->setLimit($rows, $start_row);

            $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users . ' ORDER BY user_name';
            $result = $this->db->query($query, $this->fields);

            if (MDB2::isError($result)) {
                $this->fail('Error executing select query: ' . $result->getUserInfo());
            }

            for ($row = 0; $row < $rows && ($row + $start_row < $total_rows); $row++) {
                $this->verifyFetchedValues($result, $row, $data[$row + $start_row]);
            }
        }

        $this->assertFalse($result->valid(), "The query result did not seem to have reached the end of result as expected starting row $start_row after fetching upto row $row");

        $result->free();

        for ($rows = 2, $start_row = 0; $start_row < $total_rows; $start_row += $rows) {

            $this->db->setLimit($rows, $start_row);

            $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users . ' ORDER BY user_name';
            $result = $this->db->query($query, $this->fields);

            if (MDB2::isError($result)) {
                $this->fail('Error executing select query: ' . $result->getUserInfo());
            }

            $result_rows = $result->numRows();

            $expected = ($start_row == ($total_rows-1)) ? 1 : $rows;
            $this->assertEquals($expected, $result_rows, 'invalid number of rows returned');
            $this->assertTrue(($result_rows <= $rows), 'expected a result of no more than '.$rows.' but the returned number of rows is '.$result_rows);

            for ($row = 0; $row < $result_rows; $row++) {
                $this->assertTrue($result->valid(), 'The query result seem to have reached the end of result at row '.$row.' that is before '.$result_rows.' as expected');
                $this->verifyFetchedValues($result, $row, $data[$row + $start_row]);
            }
        }

        $this->assertTrue(!$result->valid(), "The query result did not seem to have reached the end of result as expected starting row $start_row after fetching upto row $row");

        $result->free();
    }

    /**
     * Test the handling of sequences
     *
     * @dataProvider provider
     */
    public function testSequences($ci) {
        $this->manualSetUp($ci);

        if (!$this->supported('sequences')) {
           $this->markTestSkipped('SEQUENCEs not supported');
        }

        $this->db->loadModule('Manager', null, true);

        for ($start_value = 1; $start_value < 4; $start_value++) {
            $sequence_name = "test_sequence_{$start_value}";

            @$this->db->manager->dropSequence($sequence_name);
            $result = $this->db->manager->createSequence($sequence_name, $start_value);
            if (MDB2::isError($result)) {
                $this->fail("Error creating sequence $sequence_name with start value $start_value: " . $result->getUserInfo());
            } else {
                for ($sequence_value = $start_value; $sequence_value < ($start_value + 4); $sequence_value++) {
                    $value = $this->db->nextID($sequence_name, false);

                    $this->assertEquals($sequence_value, $value, "The returned sequence value for $sequence_name is not expected with sequence start value with $start_value");
                }

                $result = $this->db->manager->dropSequence($sequence_name);

                if (MDB2::isError($result)) {
                    $this->fail("Error dropping sequence $sequence_name : " . $result->getUserInfo());
                }
            }
        }

        // Test ondemand creation of sequences
        $sequence_name = 'test_ondemand';
        $this->db->pushErrorHandling(PEAR_ERROR_RETURN);
        $this->db->expectError(MDB2_ERROR_NOSUCHTABLE);
        $this->db->manager->dropSequence($sequence_name);
        $this->db->popExpect();
        $this->db->popErrorHandling();

        for ($sequence_value = 1; $sequence_value < 4; $sequence_value++) {
            $value = $this->db->nextID($sequence_name);

            if (MDB2::isError($result)) {
                $this->fail("Error creating with ondemand sequence: " . $result->getUserInfo());
            } else {
                $this->assertEquals($sequence_value, $value, "Error in ondemand sequences. The returned sequence value is not expected value");
            }
        }

        $result = $this->db->manager->dropSequence($sequence_name);
        if (MDB2::isError($result)) {
            $this->fail("Error dropping sequence $sequence_name : " . $result->getUserInfo());
        }

        // Test currId()
        $sequence_name = 'test_currid';

        $next = $this->db->nextID($sequence_name);
        $curr = $this->db->currID($sequence_name);

        if (MDB2::isError($curr)) {
            $this->fail("Error getting the current value of sequence $sequence_name : ".$curr->getMessage());
        } else {
            if ($next != $curr) {
                if ($next+1 != $curr) {
                    $this->assertEquals($next, $curr, "return value if currID() does not match the previous call to nextID()");
                }
            }
        }
        $result = $this->db->manager->dropSequence($sequence_name);
        if (MDB2::isError($result)) {
            $this->fail("Error dropping sequence $sequence_name : " . $result->getUserInfo());
        }

        // Test lastInsertid()
        if (!$this->db->supports('new_link')) {
           $this->markTestSkipped('Driver does not support new link.');
        }

        $sequence_name = 'test_lastinsertid';

        $dsn = MDB2::parseDSN($this->dsn);
        $dsn['new_link'] = true;
        $dsn['database'] = $this->database;
        $db = MDB2::connect($dsn, $this->options);

        $next = $this->db->nextID($sequence_name);
        $next2 = $db->nextID($sequence_name);
        $last = $this->db->lastInsertID($sequence_name);

        if (MDB2::isError($last)) {
            $this->fail("Error getting the last value of sequence $sequence_name : ".$last->getMessage());
        } else {
            $this->assertEquals($next, $last, "return value if lastInsertID() does not match the previous call to nextID()");
        }
        $result = $this->db->manager->dropSequence($sequence_name);
        if (MDB2::isError($result)) {
            $this->fail("Error dropping sequence $sequence_name : " . $result->getUserInfo());
        }
    }

    /**
     * Test replace query
     *
     * The replace method emulates the replace query of mysql
     *
     * @dataProvider provider
     */
    public function testReplace($ci) {
        $this->manualSetUp($ci);

        if (!$this->supported('replace')) {
            $this->markTestSkipped('REPLACE not supported');
        }

        $row = 1234;
        $data = $this->getSampleData($row);

        $fields = array(
            'user_name' => array(
                'value' => "user_$row",
                'type' => 'text'
            ),
            'user_password' => array(
                'value' => $data['user_password'],
                'type' => 'text'
            ),
            'subscribed' => array(
                'value' => $data['subscribed'],
                'type' => 'boolean'
            ),
            'user_id' => array(
                'value' => $data['user_id'],
                'type' => 'integer',
                'key' => 1
            ),
            'quota' => array(
                'value' => $data['quota'],
                'type' => 'decimal'
            ),
            'weight' => array(
                'value' => $data['weight'],
                'type' => 'float'
            ),
            'access_date' => array(
                'value' => $data['access_date'],
                'type' => 'date'
            ),
            'access_time' => array(
                'value' => $data['access_time'],
                'type' => 'time'
            ),
            'approved' => array(
                'value' => $data['approved'],
                'type' => 'timestamp'
            )
        );

        $result = $this->db->replace($this->table_users, $fields);

        if (MDB2::isError($result)) {
            $this->fail('Replace failed');
        }
        if ($this->db->supports('affected_rows')) {
            $this->assertEquals(1, $result, "replacing a row in an empty table returned incorrect value");
        }

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users;
        $result = $this->db->query($query, $this->fields);

        if (MDB2::isError($result)) {
            $this->fail('Error selecting from users' . $result->getUserInfo());
        }

        $this->verifyFetchedValues($result, 0, $data);

        $row = 4321;
        $fields['user_name']['value']     = $data['user_name']     = 'user_'.$row;
        $fields['user_password']['value'] = $data['user_password'] = 'somepass';
        $fields['subscribed']['value']    = $data['subscribed']    = $row % 2 ? true : false;
        $fields['quota']['value']         = $data['quota']         = strval($row/100);
        $fields['weight']['value']        = $data['weight']        = sqrt($row);
        $fields['access_date']['value']   = $data['access_date']   = MDB2_Date::mdbToday();
        $fields['access_time']['value']   = $data['access_time']   = MDB2_Date::mdbTime();
        $fields['approved']['value']      = $data['approved']      = MDB2_Date::mdbNow();

        $result = $this->db->replace($this->table_users, $fields);

        if (MDB2::isError($result)) {
            $this->fail('Replace failed');
        }

        if ($this->db->supports('affected_rows')) {
            switch ($this->db->phptype) {
                case 'sqlite':
                    $expect = 1;
                    break;
                default:
                    $expect = 2;
            }
            $this->assertEquals($expect, $result, "replacing a row returned incorrect result");
        }

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users;
        $result = $this->db->query($query, $this->fields);

        if (MDB2::isError($result)) {
            $this->fail('Error selecting from users' . $result->getUserInfo());
        }

        $this->verifyFetchedValues($result, 0, $data);

        $this->assertTrue(!$result->valid(), 'the query result did not seem to have reached the end of result as expected');

        $result->free();
    }

    /**
     * Test affected rows methods
     *
     * @dataProvider provider
     */
    public function testAffectedRows($ci) {
        $this->manualSetUp($ci);

        if (!$this->supported('affected_rows')) {
            $this->markTestSkipped('Affected rows not supported');
        }

        $data = array();
        $total_rows = 7;

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);
            $result = $stmt->execute(array_values($data[$row]));

            if (MDB2::isError($result)) {
                $this->fail('Error executing prepared query: ' . $result->getUserInfo());
            }

            $this->assertEquals(1, $result, "Inserting the row $row returned incorrect affected row count");
        }

        $stmt->free();

        $query = 'UPDATE ' . $this->table_users . ' SET user_password=? WHERE user_id < ?';
        $stmt = $this->db->prepare($query, array('text', 'integer'), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        for ($row = 0; $row < $total_rows; $row++) {
            $password = "pass_$row";
            if ($row == 0) {
                $stmt->bindParam(0, $password);
                $stmt->bindParam(1, $row);
            }

            $result = $stmt->execute();

            if (MDB2::isError($result)) {
                $this->fail('Error executing prepared query: ' . $result->getUserInfo());
            }

            $this->assertEquals($row, $result, "Updating the $row rows returned incorrect affected row count");
        }

        $stmt->free();

        $query = 'DELETE FROM ' . $this->table_users . ' WHERE user_id >= ?';
        $stmt = $this->db->prepare($query, array('integer'), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $row = intval($total_rows / 2);
        $stmt->bindParam(0, $row);
        for ($row = $total_rows; $total_rows; $total_rows = $row) {
            $row = intval($total_rows / 2);

            $result = $stmt->execute();

            if (MDB2::isError($result)) {
                $this->fail('Error executing prepared query: ' . $result->getUserInfo());
            }

            $this->assertEquals(($total_rows - $row), $result, 'Deleting rows returned incorrect affected row count');

        }

        $stmt->free();
    }

    /**
     * Testing transaction support - Test ROLLBACK
     *
     * @dataProvider provider
     */
    public function testTransactionsRollback($ci) {
        $this->manualSetUp($ci);

        if (!$this->supported('transactions')) {
            $this->markTestSkipped('Transactions not supported');
        }

        $data = $this->getSampleData(0);

        $this->db->beginTransaction();

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $result = $stmt->execute(array_values($data));
        $this->db->rollback();
        $stmt->free();

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users;
        $result = $this->db->query($query);
        if (MDB2::isError($result)) {
            $this->fail('Error selecting from users ' . $result->getUserInfo());
        }
        $this->assertTrue(!$result->valid(), 'Transaction rollback did not revert the row that was inserted');
        $result->free();
    }

    /**
     * Testing transaction support - Test COMMIT
     *
     * @dataProvider provider
     */
    public function testTransactionsCommit($ci) {
        $this->manualSetUp($ci);

        if (!$this->supported('transactions')) {
            $this->markTestSkipped('Transactions not supported');
        }

        $data = $this->getSampleData(1);

        $this->db->beginTransaction();

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $result = $stmt->execute(array_values($data));
        $this->db->commit();
        $stmt->free();

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users;
        $result = $this->db->query($query);
        if (MDB2::isError($result)) {
            $this->fail('Error selecting from users ' . $result->getUserInfo());
        }
        $this->assertTrue($result->valid(), 'Transaction commit did not make permanent the row that was inserted');
        $result->free();
    }

    /**
     * Testing transaction support - Test COMMIT and ROLLBACK
     *
     * @dataProvider provider
     */
    public function testTransactionsBoth($ci) {
        $this->manualSetUp($ci);

        if (!$this->supported('transactions')) {
            $this->markTestSkipped('Transactions not supported');
        }

        $data = $this->getSampleData(0);

        $this->db->beginTransaction();
        $result = $this->db->exec('DELETE FROM ' . $this->table_users);
        if (MDB2::isError($result)) {
            $this->fail('Error deleting from users: ' . $result->getUserInfo());
            $this->db->rollback();
        } else {
            $this->db->commit();
        }

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users;
        $result = $this->db->query($query);
        if (MDB2::isError($result)) {
            $this->fail('Error selecting from users: ' . $result->getUserInfo());
        }

        $this->assertTrue(!$result->valid(), 'Transaction end with implicit commit when re-enabling auto-commit did not make permanent the rows that were deleted');
        $result->free();
    }

    /**
     * Testing emulated nested transaction support
     *
     * @dataProvider provider
     */
    public function testNestedTransactions($ci) {
        $this->manualSetUp($ci);

        if (!$this->supported('transactions')) {
            $this->markTestSkipped('Transactions not supported');
        }

        $data = array(
            1 => $this->getSampleData(1234),
            2 => $this->getSampleData(4321),
        );

        $this->db->beginNestedTransaction();

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $result = $stmt->execute(array_values($data[1]));

        $this->db->beginNestedTransaction();

        $result = $stmt->execute(array_values($data[2]));
        $stmt->free();

        $result = $this->db->completeNestedTransaction();
        if (MDB2::isError($result)) {
            $this->fail('Inner transaction was not committed: ' . $result->getUserInfo());
        }

        $result = $this->db->completeNestedTransaction();
        if (MDB2::isError($result)) {
            $this->fail('Outer transaction was not committed: ' . $result->getUserInfo());
        }

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users;
        $result = $this->db->query($query);
        if (MDB2::isError($result)) {
            $this->fail('Error selecting from users: ' . $result->getUserInfo());
        }
        $this->assertTrue($result->valid(), 'Transaction commit did not make permanent the row that was inserted');
        $result->free();
    }

    /**
     * Testing savepoints
     *
     * @dataProvider provider
     */
    public function testSavepoint($ci) {
        $this->manualSetUp($ci);

        if (!$this->supported('savepoints')) {
            $this->markTestSkipped('SAVEPOINTs not supported');
        }

        $savepoint = 'test_savepoint';

        $data = array(
            1 => $this->getSampleData(1234),
            2 => $this->getSampleData(4321),
        );

        $this->db->beginTransaction();

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $result = $stmt->execute(array_values($data[1]));
        if (MDB2::isError($result)) {
            $this->fail('Error executing prepared query: ' . $result->getUserInfo());
        }

        $result = $this->db->beginTransaction($savepoint);
        if (MDB2::isError($result)) {
            $this->fail('Error setting savepoint: ' . $result->getUserInfo());
        }

        $result = $stmt->execute(array_values($data[2]));
        if (MDB2::isError($result)) {
            $this->fail('Error executing prepared query: ' . $result->getUserInfo());
        }
        $stmt->free();

        $result = $this->db->rollback($savepoint);
        if (MDB2::isError($result)) {
            $this->fail('Error rolling back to savepoint: ' . $result->getUserInfo());
        }

        $result = $this->db->commit();
        if (MDB2::isError($result)) {
            $this->fail('Transaction not committed: ' . $result->getUserInfo());
        }

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users;
        $result = $this->db->queryAll($query);
        if (MDB2::isError($result)) {
            $this->fail('Error selecting from users' . $result->getUserInfo());
        }
        $rows_inserted = count($result);
        $this->assertEquals(1, $rows_inserted, 'Error during transaction, invalid number of records inserted');

        // test release savepoint
        $this->db->beginTransaction();
        $result = $this->db->beginTransaction($savepoint);
        if (MDB2::isError($result)) {
            $this->fail('Error setting savepoint: ' . $result->getUserInfo());
        }
        $result = $this->db->commit($savepoint);
        if (MDB2::isError($result)) {
            $this->fail('Error setting savepoint: ' . $result->getUserInfo());
        }
        $result = $this->db->commit();
        if (MDB2::isError($result)) {
            $this->fail('Transaction not committed: ' . $result->getUserInfo());
        }
    }

    /**
     * Testing LOB storage
     *
     * MYSQL NOTE:  If this test fails with native code 1210,
     * "Incorrect arguments to mysqld_stmt_execute" upgrade to MySQL 5.1.57.
     * If that's not an option, set "general_log = 1" in my.cnf.
     *
     * MSSQL NOTE:  If this test fails, use an higher limit in these
     * two php.ini settings: "mssql.textlimit" and "mssql.textsize"
     *
     * @dataProvider provider
     */
    public function testLOBStorage($ci) {
        $this->manualSetUp($ci);

        if (!$this->supported('LOBs')) {
            $this->markTestSkipped('LOBs not supported');
        }

        $query = 'INSERT INTO ' . $this->table_files . ' (ID, document, picture) VALUES (1, ?, ?)';
        $stmt = $this->db->prepare($query, array('clob', 'blob'), MDB2_PREPARE_MANIP, array('document', 'picture'));
        if (MDB2::isError($stmt)) {
            $this->fail('Failed prepared statement to insert LOB values: '.$stmt->getUserInfo());
        }

        $character_lob = '';
        $binary_lob    = '';

        for ($i = 0; $i < 1000; $i++) {
            for ($code = 32; $code <= 127; $code++) {
                $character_lob.= chr($code);
            }
            for ($code = 0; $code <= 255; $code++) {
                $binary_lob.= chr($code);
            }
        }

        $stmt->bindValue(0, $character_lob);
        $stmt->bindValue(1, $binary_lob);

        $result = $stmt->execute();

        if (MDB2::isError($result)) {
            $this->fail('Error executing prepared query: '.$result->getUserInfo());
        }

        $stmt->free();

        $result = $this->db->query('SELECT document, picture FROM ' . $this->table_files . ' WHERE id = 1', array('clob', 'blob'));
        if (MDB2::isError($result)) {
            $this->fail('Error selecting from files' . $result->getUserInfo());
        }

        $this->assertTrue($result->valid(), 'The query result seem to have reached the end of result too soon.');

        $row = $result->fetchRow();
        $clob = $row[0];
        if (!MDB2::isError($clob) && is_resource($clob)) {
            $value = '';
            while (!feof($clob)) {
                $data = fread($clob, 8192);
                $this->assertTrue(strlen($data) >= 0, 'Could not read CLOB');
                $value.= $data;
            }
            $this->db->datatype->destroyLOB($clob);
            $this->assertEquals($character_lob, $value, 'Retrieved character LOB value is different from what was stored');
        } else {
            $this->fail('Error retrieving CLOB result');
        }

        $blob = $row[1];
        if (!MDB2::isError($blob) && is_resource($blob)) {
            $value = '';
            while (!feof($blob)) {
                $data = fread($blob, 8192);
                $this->assertTrue(strlen($data) >= 0, 'Could not read BLOB');
                $value.= $data;
            }

            $this->db->datatype->destroyLOB($blob);
            $this->assertEquals($binary_lob, $value, 'Retrieved binary LOB value is different from what was stored');
        } else {
            $this->fail('Error retrieving BLOB result');
        }
        $result->free();
    }

    /**
     * Test LOB reading of multiple records both buffered and unbuffered. See bug #8793 for why this must be tested.
     *
     * @dataProvider provider
     */
    public function testLOBRead($ci) {
        $this->manualSetUp($ci);

        if (!$this->supported('LOBs')) {
            $this->markTestSkipped('LOBs not supported');
        }

        for ($i = 20; $i < 30; ++$i) {
            $query = 'INSERT INTO ' . $this->table_files . ' (ID, document, picture) VALUES (?, ?, ?)';
            $stmt = $this->db->prepare($query, array('integer', 'clob', 'blob'), MDB2_PREPARE_MANIP, array(1 => 'document', 2 => 'picture'));
            if (MDB2::isError($stmt)) {
                $this->fail('Error preparing query: ' . $stmt->getUserInfo());
            }

            $character_lob = $binary_lob = $i;
            $stmt->bindValue(1, $character_lob);
            $stmt->bindValue(2, $binary_lob);

            $result = $stmt->execute(array($i));

            if (MDB2::isError($result)) {
                $this->fail('Error executing prepared query: '.$result->getUserInfo());
            }
            $stmt->free();
        }

        $oldBuffered = $this->db->getOption('result_buffering');
        foreach (array(true, false) as $buffered) {
            $this->db->setOption('result_buffering', $buffered);
            $msgPost = ' with result_buffering = '.($buffered ? 'true' : 'false');
            $result = $this->db->query('SELECT id, document, picture FROM ' . $this->table_files . ' WHERE id >= 20 AND id <= 30 ORDER BY id ASC', array('integer', 'clob', 'blob'));
            if (MDB2::isError($result)) {
                $this->fail('Error selecting from files ' . $msgPost . ': ' . $result->getMessage());
            } else {
                if ($buffered) {
                    $this->assertTrue($result->valid(), 'The query result seem to have reached the end of result too soon'.$msgPost);
                    $this->assertEquals('mdb2_bufferedresult_', strtolower(substr(get_class($result), 0, 20)), 'Error: not a buffered result');
                } else {
                    $this->assertEquals('mdb2_result_', strtolower(substr(get_class($result), 0, 12)), 'Error: an unbuffered result was expected');
                }
                for ($i = 1; $i <= ($buffered ? 2 : 1); ++$i) {
                    $result->seek(0);
                    while ($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
                        foreach (array('document' => 'clob', 'picture' => 'blob') as $field => $type) {
                            $lob = $row[$field];
                            if (is_object($lob) && is_a($lob, 'oci-lob')) {
                                $lob = $lob->load();
                            } elseif (is_resource($lob)) {
                                $lob = fread($lob, 1000);
                            }
                            $this->assertEquals($lob, $row['id'], 'LOB ('.$type.') field ('.$field.') not equal to expected value ('.$row['id'].')'.$msgPost.' on run-through '.$i);
                        }
                    }
                }
                $result->free();
            }
        }
        $this->db->setOption('result_buffering', $oldBuffered);
    }

    /**
     * Test for lob storage from and to files
     *
     * @dataProvider provider
     */
    public function testLOBFiles($ci) {
        $this->manualSetUp($ci);

        if (!$this->supported('LOBs')) {
            $this->markTestSkipped('LOBs not supported');
        }

        // Create character data file.
        $character_data_file = tempnam(sys_get_temp_dir(), 'mdb2_clob_data_');
        $file = fopen($character_data_file, 'w');
        $this->assertTrue(((bool)$file), 'Error creating clob file to read from');
        $character_data = '';
        for ($i = 0; $i < 1000; $i++) {
            for ($code = 32; $code <= 127; $code++) {
                $character_data.= chr($code);
            }
        }
        if (fwrite($file, $character_data, strlen($character_data)) != strlen($character_data)) {
            @fclose($file);
            @unlink($character_data_file);
            $this->fail('Error writing to clob file: ' . $character_data_file);
        }
        fclose($file);

        // Create binary data file.
        $binary_data_file = tempnam(sys_get_temp_dir(), 'mdb2_blob_data_');
        $file = fopen($binary_data_file, 'wb');
        $this->assertTrue(((bool)$file), 'Error creating blob file to read from');
        $binary_data = '';
        for ($i = 0; $i < 1000; $i++) {
            for ($code = 0; $code <= 255; $code++) {
                $binary_data.= chr($code);
            }
        }
        if (fwrite($file, $binary_data, strlen($binary_data)) != strlen($binary_data)) {
            @fclose($file);
            @unlink($binary_data_file);
            $this->fail('Error writing to blob file: ' . $binary_data_file);
        }
        fclose($file);


        // Insert data files into database.

        $this->db->setOption('lob_allow_url_include', true);

        $query = 'INSERT INTO ' . $this->table_files . ' (ID, document, picture) VALUES (1, :document, :picture)';
        $stmt = $this->db->prepare($query, array('document' => 'clob', 'picture' => 'blob'), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $character_data_file_tmp = 'file://'.$character_data_file;
        $stmt->bindParam('document', $character_data_file_tmp);
        $binary_data_file_tmp = 'file://'.$binary_data_file;
        $stmt->bindParam('picture', $binary_data_file_tmp);

        $result = $stmt->execute();
        if (MDB2::isError($result)) {
            @$stmt->free();
            $this->fail('Error executing prepared query - inserting LOB from files: ' . $result->getUserInfo());
        }

        $stmt->free();
        @unlink($character_data_file);
        @unlink($binary_data_file);


        // Query the newly created record.
        $result = $this->db->query('SELECT document, picture FROM ' . $this->table_files . ' WHERE id = 1', array('clob', 'blob'));
        if (MDB2::isError($result)) {
            $this->fail('Error selecting from files: ' . $result->getUserInfo());
        }
        $this->assertTrue($result->valid(), 'The query result seem to have reached the end of result too soon.');
        $row = $result->fetchRow();

        $clob = $row[0];
        if (MDB2::isError($clob) || !is_resource($clob)) {
            $result->free();
            $this->fail('Error reading CLOB from database.');
        }

        $blob = $row[1];
        if (MDB2::isError($blob) || !is_resource($blob)) {
            $result->free();
            $this->fail('Error reading BLOB from database.');
        }


        // Write CLOB to file and verify file contents.
        $res = $this->db->datatype->writeLOBToFile($clob, $character_data_file);
        $this->db->datatype->destroyLOB($clob);
        if (MDB2::isError($res)) {
            @unlink($character_data_file);
            $this->fail('Error writing CLOB to file.');
        }

        $value = file_get_contents($character_data_file);
        @unlink($character_data_file);
        if (false === $value) {
            $this->fail("Error opening CLOB file: $character_data_file");
        }
        $this->assertEquals($character_data, $value, "retrieved character LOB value is different from what was stored");


        // Write BLOB to file and verify file contents.
        $res = $this->db->datatype->writeLOBToFile($blob, $binary_data_file);
        $this->db->datatype->destroyLOB($blob);
        if (MDB2::isError($res)) {
            @unlink($binary_data_file);
            $this->fail('Error writing BLOB to file.');
        }

        $value = file_get_contents($binary_data_file);
        @unlink($binary_data_file);
        if (false === $value) {
            $this->fail("Error opening BLOB file: $binary_data_file");
        }
        $this->assertEquals($binary_data, $value, "retrieved binary LOB value is different from what was stored");


        // Clean up.
        $result->free();
    }

    /**
     * Test for lob storage from and to files
     *
     * @dataProvider provider
     */
    public function testQuoteLOBFilesNoUrlInclude($ci) {
        $this->manualSetUp($ci);

        if (!$this->supported('LOBs')) {
            $this->markTestSkipped('LOBs not supported');
        }

        $this->db->setOption('lob_allow_url_include', false);

        $character_data_file = tempnam(sys_get_temp_dir(), 'mdb2_clob_data_');
        $character_data_file_tmp = 'file://'.$character_data_file;
        $file = fopen($character_data_file, 'w');
        $this->assertTrue(((bool)$file), 'Error creating clob file to read from');
        $character_data = '';
        for ($code = 65; $code <= 80; $code++) {
            $character_data.= chr($code);
        }
        if (fwrite($file, $character_data, strlen($character_data)) != strlen($character_data)) {
            @fclose($file);
            @unlink($character_data_file);
            $this->fail('Error writing to clob file: ' . $character_data_file);
        }
        fclose($file);

        $expected = ($this->dsn['phptype'] == 'oci8') ? 'EMPTY_CLOB()' : "'".$character_data_file_tmp."'";
        $quoted = $this->db->quote($character_data_file_tmp,  'clob');
        if ($expected != $quoted) {
            // Wipe out file before test fails and rest of method gets skipped.
            @unlink($character_data_file);
        }
        $this->assertEquals($expected, $quoted, 'clob data did not match');

        $expected = ($this->dsn['phptype'] == 'oci8') ? 'EMPTY_BLOB()' : "'".$character_data_file_tmp."'";
        $quoted = $this->db->quote($character_data_file_tmp,  'blob');
        if ($expected != $quoted) {
            // Wipe out file before test fails and rest of method gets skipped.
            @unlink($character_data_file);
        }
        $this->assertEquals($expected, $quoted, 'blob data did not match');

        @unlink($character_data_file);
    }

    /**
     * Test for lob storage from and to files
     *
     * @dataProvider provider
     */
    public function testQuoteLOBFilesUrlInclude($ci) {
        $this->manualSetUp($ci);

        if (!$this->supported('LOBs')) {
            $this->markTestSkipped('LOBs not supported');
        }

        $this->db->setOption('lob_allow_url_include', true);

        $character_data_file = tempnam(sys_get_temp_dir(), 'mdb2_clob_data_');
        $character_data_file_tmp = 'file://'.$character_data_file;
        $file = fopen($character_data_file, 'w');
        $this->assertTrue(((bool)$file), 'Error creating clob file to read from');
        $character_data = '';
        for ($code = 65; $code <= 80; $code++) {
            $character_data.= chr($code);
        }
        if (fwrite($file, $character_data, strlen($character_data)) != strlen($character_data)) {
            @fclose($file);
            @unlink($character_data_file);
            $this->fail('Error writing to clob file: ' . $character_data_file);
        }
        fclose($file);

        $expected = ($this->dsn['phptype'] == 'oci8') ? 'EMPTY_CLOB()' : "'".$character_data."'";
        $quoted = $this->db->quote($character_data_file_tmp,  'clob');
        $this->assertEquals($expected, $quoted);

        switch ($this->dsn['phptype']) {
            case 'oci8':
                $expected = 'EMPTY_BLOB()';
                break;
            case 'sqlsrv':
                $expected = "'0x".bin2hex($character_data)."'";
                break;
            default:
                $expected = "'".$character_data."'";
                break;
        }
        $quoted = $this->db->quote($character_data_file_tmp,  'blob');
        $this->assertEquals($expected, $quoted);

        @unlink($character_data_file);
    }

    /**
     * Test handling of lob nulls
     *
     * @dataProvider provider
     */
    public function testLOBNulls($ci) {
        $this->manualSetUp($ci);

        if (!$this->supported('LOBs')) {
            $this->markTestSkipped('LOBs not supported');
        }

        $query = 'INSERT INTO ' . $this->table_files . ' (ID, document, picture) VALUES (1, :document, :picture)';
        $stmt = $this->db->prepare($query, array('document' => 'clob', 'picture' => 'blob'), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $null = null;
        $stmt->bindParam('document', $null);
        $stmt->bindParam('picture', $null);

        $result = $stmt->execute();
        if (MDB2::isError($result)) {
            @$stmt->free();
            $this->fail('Error executing prepared query - inserting NULL lobs: ' . $result->getUserInfo());
        }
        $stmt->free();

        $result = $this->db->query('SELECT document, picture FROM ' . $this->table_files, array('clob', 'blob'));
        if (MDB2::isError($result)) {
            $this->fail('Error selecting from files: ' . $result->getUserInfo());
        }

        $this->assertTrue($result->valid(), 'The query result seem to have reached the end of result too soon.');

        $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        $this->assertTrue(is_null($row['document']), 'A query result large object column document is not NULL unlike what was expected');
        $this->assertTrue(is_null($row['picture']), 'A query result large object column picture is not NULL unlike what was expected');

        $result->free();
    }

    /**
     * @dataProvider provider
     */
    public function testLOBUpdate($ci) {
        $this->manualSetUp($ci);

        if (!$this->supported('LOBs')) {
            $this->markTestSkipped('LOBs not supported');
        }

        $query = 'INSERT INTO ' . $this->table_files . ' (ID, document, picture) VALUES (1, ?, ?)';
        $stmt = $this->db->prepare($query, array('clob', 'blob'), MDB2_PREPARE_MANIP, array('document', 'picture'));
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $character_lob = '';
        $binary_lob = '';

        for ($i = 0; $i < 1000; $i++) {
            for ($code = 32; $code <= 127; ++$code) {
                $character_lob .= chr($code);
            }
            for ($code = 0; $code <= 255; ++$code) {
                $binary_lob .= chr($code);
            }
        }

        $stmt->bindValue(0, $character_lob);
        $stmt->bindValue(1, $binary_lob);

        $result = $stmt->execute();

        if (MDB2::isError($result)) {
            $this->fail('Error executing prepared query: '.$result->getUserInfo());
        }

        $stmt->free();

        $query = 'UPDATE ' . $this->table_files . ' SET document = ?, picture = ? WHERE ID = 1';
        $stmt = $this->db->prepare($query, array('clob', 'blob'), MDB2_PREPARE_MANIP, array('document', 'picture'));
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $character_lob = '';
        $binary_lob = '';

        for ($i = 0; $i < 999; $i++) {
            for ($code = 127; $code >= 32; --$code) {
                $character_lob .= chr($code);
            }
            for ($code = 255; $code >= 0; --$code) {
                $binary_lob .= chr($code);
            }
        }

        $stmt->bindValue(0, $character_lob);
        $stmt->bindValue(1, $binary_lob);

        $result = $stmt->execute();

        if (MDB2::isError($result)) {
            $this->fail('Error executing prepared query: '.$result->getUserInfo());
        }

        $stmt->free();

        $result = $this->db->query('SELECT document, picture FROM ' . $this->table_files . ' WHERE id = 1', array('clob', 'blob'));
        if (MDB2::isError($result)) {
            $this->fail('Error selecting from files: ' . $result->getUserInfo());
        }

        $this->assertTrue($result->valid(), 'The query result seem to have reached the end of result too soon.');

        $row = $result->fetchRow();
        $clob = $row[0];
        if (!MDB2::isError($clob) && is_resource($clob)) {
            $value = '';
            while (!feof($clob)) {
                $data = fread($clob, 8192);
                $this->assertTrue(strlen($data) >= 0, 'Could not read CLOB');
                $value.= $data;
            }
            $this->db->datatype->destroyLOB($clob);
            $this->assertEquals($character_lob, $value, 'Retrieved character LOB value is different from what was stored');
        } else {
            $this->fail('Error retrieving CLOB result');
        }

        $blob = $row[1];
        if (!MDB2::isError($blob) && is_resource($blob)) {
            $value = '';
            while (!feof($blob)) {
                $data = fread($blob, 8192);
                $this->assertTrue(strlen($data) >= 0, 'Could not read BLOB');
                $value.= $data;
            }

            $this->db->datatype->destroyLOB($blob);
            $this->assertEquals($binary_lob, $value, 'Retrieved binary LOB value is different from what was stored');
        } else {
            $this->fail('Error retrieving BLOB result');
        }
        $result->free();
    }

    /**
     * Test retrieval of result metadata
     *
     * This tests the result metadata by executing a prepared query and
     * select the data, and checking the result contains the correct
     * number of columns and that the column names are in the correct order
     *
     * @dataProvider provider
     */
    public function testConvertEmpty2Null($ci) {
        $this->manualSetUp($ci);

#$this->db->setOption('portability', MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL);

        $data = $this->getSampleData(1234);
        $data['user_password'] = '';

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $result = $stmt->execute(array_values($data));
        $stmt->free();

        if (MDB2::isError($result)) {
            $this->fail('Error executing prepared query: ' . $result->getUserInfo());
        }

        $row = $this->db->queryRow('SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users . ' WHERE user_password IS NULL', $this->fields, MDB2_FETCHMODE_ORDERED);

        if (MDB2::isError($row)) {
            $this->fail('Error selecting from users: ' . $result->getUserInfo());
        }

        $expected = count($this->fields);
        $actual   = count($row);
        $this->assertEquals($expected, $actual, "The query result returned a number of columns ({$actual}) unlike {$expected} as expected");
    }

    /** @dataProvider provider */
    public function testPortabilityOptions($ci) {
        $this->manualSetUp($ci);

        // MDB2_PORTABILITY_DELETE_COUNT
        $data = array();
        $total_rows = 5;

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);
            $result = $stmt->execute(array_values($data[$row]));
            if (MDB2::isError($result)) {
                $this->fail('Error executing prepared query: ' . $result->getUserInfo());
            }
        }
        $stmt->free();

        $this->db->setOption('portability', MDB2_PORTABILITY_NONE | MDB2_PORTABILITY_DELETE_COUNT);
        $affected_rows = $this->db->exec('DELETE FROM ' . $this->table_users);
        if (MDB2::isError($affected_rows)) {
            $this->fail('Error executing query: '.$affected_rows->getMessage());
        }
        $this->assertEquals($total_rows, $affected_rows, 'MDB2_PORTABILITY_DELETE_COUNT not working');

        // MDB2_PORTABILITY_FIX_CASE
        $fields = array_keys($this->fields);
        $this->db->setOption('portability', MDB2_PORTABILITY_NONE | MDB2_PORTABILITY_FIX_CASE);
        $this->db->setOption('field_case', CASE_UPPER);

        $data = $this->getSampleData(1234);
        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $result = $stmt->execute(array_values($data));
        $stmt->free();

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users;
        $result = $this->db->queryRow($query, $this->fields, MDB2_FETCHMODE_ASSOC);
        if (MDB2::isError($result)) {
            $this->fail('Error selecting from users: ' . $result->getUserInfo());
        }
        $field = reset($fields);
        foreach (array_keys($result) as $fieldname) {
            $this->assertEquals(strtoupper($field), $fieldname, 'MDB2_PORTABILITY_FIX_CASE CASE_UPPER not working');
            $field = next($fields);
        }

        $this->db->setOption('field_case', CASE_LOWER);
        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM ' . $this->table_users;
        $result = $this->db->queryRow($query, $this->fields, MDB2_FETCHMODE_ASSOC);
        if (MDB2::isError($result)) {
            $this->fail('Error selecting from users: ' . $result->getUserInfo());
        }
        $field = reset($fields);
        foreach (array_keys($result) as $fieldname) {
            $this->assertEquals(strtolower($field), $fieldname, 'MDB2_PORTABILITY_FIX_CASE CASE_LOWER not working');
            $field = next($fields);
        }

        // MDB2_PORTABILITY_RTRIM
        $this->db->setOption('portability', MDB2_PORTABILITY_NONE | MDB2_PORTABILITY_RTRIM);
        $value = 'rtrim   ';
        $query = 'INSERT INTO ' . $this->table_users . ' (user_id, user_password) VALUES (1, ' . $this->db->quote($value, 'text') .')';
        $res = $this->db->exec($query);
        if (MDB2::isError($res)) {
            $this->fail('Error executing query: '.$res->getMessage());
        }
        $query = 'SELECT user_password FROM ' . $this->table_users . ' WHERE user_id = 1';
        $result = $this->db->queryOne($query, array('text'));
        if (MDB2::isError($result)) {
            $this->fail('Error selecting from users: ' . $result->getUserInfo());
        }
        $this->assertEquals(rtrim($value), $result, '"MDB2_PORTABILITY_RTRIM = on" not working');

        if (!$this->supported('LOBs')) {
            return;
        }

        $query = 'INSERT INTO ' . $this->table_files . ' (ID, document, picture) VALUES (1, ?, ?)';
        $stmt = $this->db->prepare($query, array('clob', 'blob'), MDB2_PREPARE_MANIP, array('document', 'picture'));
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $character_lob = '';
        $binary_lob = '';

        for ($i = 0; $i < 999; $i++) {
            for ($code = 127; $code >= 32; --$code) {
                $character_lob .= chr($code);
            }
            for ($code = 255; $code >= 0; --$code) {
                $binary_lob .= chr($code);
            }
        }

        $stmt->bindValue(0, $character_lob);
        $stmt->bindValue(1, $binary_lob);

        $result = $stmt->execute();

        if (MDB2::isError($result)) {
            $this->fail('Error executing prepared query: '.$result->getUserInfo());
        }

        $stmt->free();

        $result = $this->db->query('SELECT document, picture FROM ' . $this->table_files . ' WHERE id = 1', array('clob', 'blob'));
        if (MDB2::isError($result)) {
            $this->fail('Error selecting from files: ' . $result->getUserInfo());
        }

        $this->assertTrue($result->valid(), 'The query result seem to have reached the end of result too soon.');

        $row = $result->fetchRow();
        $clob = $row[0];
        if (!MDB2::isError($clob) && is_resource($clob)) {
            $value = '';
            while (!feof($clob)) {
                $data = fread($clob, 8192);
                $this->assertTrue(strlen($data) >= 0, 'Could not read CLOB');
                $value.= $data;
            }
            $this->db->datatype->destroyLOB($clob);
            $this->assertEquals($character_lob, $value, '"MDB2_PORTABILITY_RTRIM = on" Retrieved character LOB value is different from what was stored');
        } else {
            $this->fail('Error retrieving CLOB result');
        }

        $blob = $row[1];
        if (!MDB2::isError($blob) && is_resource($blob)) {
            $value = '';
            while (!feof($blob)) {
                $data = fread($blob, 8192);
                $this->assertTrue(strlen($data) >= 0, 'Could not read BLOB');
                $value.= $data;
            }

            $this->db->datatype->destroyLOB($blob);
            $this->assertEquals($binary_lob, $value, '"MDB2_PORTABILITY_RTRIM = on" Retrieved binary LOB value is different from what was stored');
        } else {
            $this->fail('Error retrieving BLOB result');
        }
        $result->free();
    }

    /**
     * Test getAsKeyword()
     *
     * @dataProvider provider
     */
    public function testgetAsKeyword($ci) {
        $this->manualSetUp($ci);

        $query = 'INSERT INTO ' . $this->table_users . ' (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        if (MDB2::isError($stmt)) {
            $this->fail('Error preparing query: ' . $stmt->getUserInfo());
        }

        $data = $this->getSampleData(1);
        $result = $stmt->execute(array_values($data));
        if (MDB2::isError($result)) {
            $this->fail('Error executing prepared query: ' . $result->getUserInfo());
        }
        $stmt->free();

        $query = 'SELECT user_id'.$this->db->getAsKeyword().'foo FROM ' . $this->table_users;
        $result = $this->db->queryRow($query, array('integer'), MDB2_FETCHMODE_ASSOC);
        if (MDB2::isError($result)) {
            $this->fail('Error getting alias column: '. $result->getMessage());
        } else {
            $this->assertTrue((array_key_exists('foo', $result)), 'Error: could not alias "user_id" with "foo" : '.var_export($result, true));
        }
    }
}
