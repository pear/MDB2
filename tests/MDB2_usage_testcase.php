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

require_once 'MDB2_testcase.php';

class MDB2_Usage_TestCase extends MDB2_TestCase {
    /**
     * Test typed data storage and retrieval
     *
     * This tests typed data storage and retrieval by executing a single
     * prepared query and then selecting the data back from the database
     * and comparing the results
     */
    function testStorage() {
        $data = $this->getSampleData(1234);

        $query = 'INSERT INTO users (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        $result = $stmt->execute(array_values($data));
        $stmt->free();

        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
        }

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM users';
        $result =& $this->db->query($query, $this->fields);

        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }

        $this->verifyFetchedValues($result, 0, $data);
    }

    /**
     * Test fetchOne()
     *
     * This test bulk fetching of result data by using a prepared query to
     * insert an number of rows of data and then retrieving the data columns
     * one by one
     */
    function testFetchOne() {
        $data = array();
        $total_rows = 5;

        $query = 'INSERT INTO users (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);
            $result = $stmt->execute(array_values($data[$row]));

            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
            }
        }

        $stmt->free();

        foreach ($this->fields as $field => $type) {
            for ($row = 0; $row < $total_rows; $row++) {
                $result =& $this->db->query('SELECT '.$field.' FROM users WHERE user_id='.$row, $type);
                $value = $result->fetchOne();
                if (PEAR::isError($value)) {
                    $this->assertTrue(false, 'Error fetching row '.$row.' for field '.$field.' of type '.$type);
                } else {
                    $this->assertEquals(strval($data[$row][$field]), strval(trim($value)), 'the query field '.$field.' of type '.$type.' for row '.$row);
                    $result->free();
                }
            }
        }
    }

    /**
     * Test fetchCol()
     *
     * Test fetching a column of result data. Two different columns are retrieved
     */
    function testFetchCol() {
        $data = array();
        $total_rows = 5;

        $query = 'INSERT INTO users (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);
            $result = $stmt->execute(array_values($data[$row]));

            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
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

        $query = 'SELECT user_name, user_id FROM users ORDER BY user_name';
        $result =& $this->db->query($query, array('text', 'integer'));
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error during query');
        }
        $values = $result->fetchCol(0);
        if (PEAR::isError($values)) {
            $this->assertTrue(false, 'Error fetching first column');
        } else {
            $this->assertEquals($first_col, $values);
        }
        $result->free();

        $query = 'SELECT user_name, user_id FROM users ORDER BY user_name';
        $result =& $this->db->query($query, array('text', 'integer'));
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error during query');
        }
        $values = $result->fetchCol(1);
        if (PEAR::isError($values)) {
            $this->assertTrue(false, 'Error fetching second column');
        } else {
            $this->assertEquals($second_col, $values);
        }
        $result->free();
    }

    /**
     * Test fetchAll()
     *
     * Test fetching an entire result set in one shot.
     */
    function testFetchAll() {
        $data = array();
        $total_rows = 5;

        $query = 'INSERT INTO users (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);
            $result = $stmt->execute(array_values($data[$row]));

            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
            }
        }
        $fields = array_keys($data[0]);
        $query = 'SELECT '. implode (', ', $fields). ' FROM users ORDER BY user_name';

        $stmt->free();

        $result =& $this->db->query($query, $this->fields);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error during query');
        }
        $values = $result->fetchAll(MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($values)) {
            $this->assertTrue(false, 'Error fetching the result set');
        } else {
            for ($i=0; $i<$total_rows; $i++) {
                foreach ($data[$i] as $key => $val) {
                    $this->assertEquals(strval($values[$i][$key]), strval($val), 'Row #'.$i.' ['.$key.']');
                }
            }
        }
        $result->free();
    }

    /**
     * Test different fetch modes
     *
     * Test fetching results using different fetch modes
     * NOTE: several tests still missing
     */
    function testFetchModes() {
        $data = array();
        $total_rows = 5;

        $query = 'INSERT INTO users (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);
            $result = $stmt->execute(array_values($data[$row]));

            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
            }
        }

        $stmt->free();

        // test ASSOC
        $query = 'SELECT A.user_name FROM users A, users B WHERE A.user_id = B.user_id';
        $value = $this->db->queryRow($query, array($this->fields['user_name']), MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($value)) {
            $this->assertTrue(false, 'Error fetching the result set');
        } else {
            $this->assertTrue(!empty($value['user_name']), 'Error fetching the associative result set from join');
        }
    }

    /**
     * Test multi_query option
     *
     * This test attempts to send multiple queries at once using the multi_query
     * option and then retrieves each result.
     */
    function testMultiQuery() {
        $multi_query_orig = $this->db->getOption('multi_query');
        if (PEAR::isError($multi_query_orig)) {
            $this->assertTrue(false, 'Error getting multi_query option value: '.$multi_query_orig->getMessage());
            return;
        }

        $this->db->setOption('multi_query', true);

        $data = array();
        $total_rows = 5;

        $query = 'INSERT INTO users (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);
            $result = $stmt->execute(array_values($data[$row]));

            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
            }
        }

        $stmt->free();

        $query = '';
        for ($row = 0; $row < $total_rows; $row++) {
            $query.= 'SELECT user_name FROM users WHERE user_id='.$row.';';
        }
        $result =& $this->db->query($query, 'text');

        for ($row = 0; $row < $total_rows; $row++) {
            $value = $result->fetchOne();
            if (PEAR::isError($value)) {
                $this->assertTrue(false, 'Error fetching row '.$row);
            } else {
                $this->assertEquals(strval($data[$row]['user_name']), strval(trim($value)), 'the query field username of type "text" for row '.$row);
            }
            if (PEAR::isError($result->nextResult())) {
                $this->assertTrue(false, 'Error moving result pointer');
            }
        }

        $result->free();
        $this->db->setOption('multi_query', $multi_query_orig);
    }

    /**
     * Test prepared queries
     *
     * Tests prepared queries, making sure they correctly deal with ?, !, and '
     */
    function testPreparedQueries() {
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

        $query = "INSERT INTO users (user_name, user_password, user_id) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($query, array('text', 'text', 'integer'), MDB2_PREPARE_MANIP);

        $stmt->bindParam(0, $data[0]['user_name']);
        $stmt->bindParam(2, $data[0]['user_id']);
        $stmt->bindParam(1, $data[0]['user_password']);

        $result = $stmt->execute();

        $stmt->free();

        if (PEAR::isError($result)) {
            $error = $result->getMessage();
        }

        $this->assertTrue(!PEAR::isError($result), 'Could not execute prepared query with a text value with a question mark. Error: ');

        $query = "INSERT INTO users (user_name, user_password, user_id) VALUES (:text, :question, ".$data[1]['user_id'].")";
        $stmt = $this->db->prepare($query, array('text', 'text'), MDB2_PREPARE_MANIP);

        $stmt->bindParam('question', $data[1]['user_password']);
        $stmt->bindParam('text', $data[1]['user_name']);

        $result = $stmt->execute();

        $stmt->free();

        if (PEAR::isError($result)) {
            $error = $result->getMessage();
        }

        $this->assertTrue(!PEAR::isError($result), 'Could not execute prepared query with a text value with a quote character before a question mark. Error: ');

        $query = 'SELECT user_name, user_password, user_id FROM users WHERE user_id=:user_id';
        $stmt = $this->db->prepare($query, array('integer'), array('text', 'text', 'integer'));
        foreach ($data as $row_data) {
            $result =& $stmt->execute(array('user_id' => $row_data['user_id']));
            if (PEAR::isError($result)) {
                $this->assertTrue(!PEAR::isError($result), 'Could not execute prepared. Error: '.$result->getUserinfo());
                break;
            }
            $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
            if (!is_array($row)) {
                $this->assertTrue(false, 'Prepared SELECT failed');
            } else {
                $diff = (array)array_diff($row, $row_data);
                $this->assertTrue(empty($diff), 'Prepared SELECT failed for fields: '.implode(', ', array_keys($diff)));
            }
        }
        $stmt->free();

        $row_data = reset($data);
        $query = 'SELECT user_name, user_password, user_id FROM users WHERE user_id='.$this->db->quote($row_data['user_id'], 'integer');
        $stmt = $this->db->prepare($query, null, array('text', 'text', 'integer'));
        $result =& $stmt->execute(array());
        if (PEAR::isError($result)) {
            $this->assertTrue(!PEAR::isError($result), 'Could not execute prepared. Error: '.$result->getUserinfo());
            break;
        }
        $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        if (!is_array($row)) {
            $this->assertTrue(false, 'Prepared SELECT failed');
        } else {
            $diff = (array)array_diff($row, $row_data);
            $this->assertTrue(empty($diff), 'Prepared SELECT failed for fields: '.implode(', ', array_keys($diff)));
        }
        $stmt->free();
    }

    /**
     * Test retrieval of result metadata
     *
     * This tests the result metadata by executing a prepared query and
     * select the data, and checking the result contains the correct
     * number of columns and that the column names are in the correct order
     */
    function testMetadata() {
        $data = $this->getSampleData(1234);

        $query = 'INSERT INTO users (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);

        $result = $stmt->execute(array_values($data));
        $stmt->free();

        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
        }

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM users';
        $result =& $this->db->query($query, $this->fields);

        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
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
     */
    function testNulls() {
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

            $result = $this->db->exec("INSERT INTO users (user_name,user_password,user_id) VALUES ($value,$value,0)");

            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error executing insert query'.$result->getMessage());
            }

            $result =& $this->db->query('SELECT user_name,user_password FROM users', array('text', 'text'));

            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error executing select query'.$result->getMessage());
            }

            $this->assertTrue($result->valid(), 'The query result seems to have reached the end of result earlier than expected');

            if ($is_null) {
                $error_message = 'A query result column is not NULL unlike what was expected';
            } else {
                $error_message = 'A query result column is NULL even though it was expected to be differnt';
            }

            $row = $result->fetchRow();
            $this->assertTrue((is_null($row[0]) == $is_null), $error_message);
            $this->assertTrue((is_null($row[1]) == $is_null), $error_message);

            $result->free();
        }
    }

    /**
     * Tests escaping of text values with special characters
     *
     */
    function testEscapeSequences() {
        $test_strings = array(
            "'",
            "\"",
            "\\",
            "%",
            "_",
            "''",
            "\"\"",
            "\\\\",
            "\\'\\'",
            "\\\"\\\""
        );

        $this->clearTables();
        foreach($test_strings as $key => $string) {
            $value = $this->db->quote($string, 'text');
            $query = "INSERT INTO users (user_name,user_id) VALUES ($value, $key)";
            $result = $this->db->exec($query);

            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error executing insert query'.$result->getMessage());
            }

            $query = 'SELECT user_name FROM users WHERE user_id = '.$key;
            $value = $this->db->queryOne($query, 'text');

            if (PEAR::isError($value)) {
                $this->assertTrue(false, 'Error executing select query'.$value->getMessage());
            }

            $this->assertEquals($string, $value, "the value retrieved for field \"user_name\" doesn't match what was stored");
        }
    }

    /**
     * Tests escaping of text pattern strings with special characters
     *
     */
    function testEscapePatternSequences() {
        if (!$this->supported('pattern_escaping')) {
            $this->assertTrue(false, '"pattern_escaping" is not supported');
            return;
        }

        $test_strings = array(
            "%",
            "_",
            "%_",
            "_%",
            "%foo%",
            "%foo_",
            "foo%123",
            "foo_123",
            "_foo%",
            "_foo_",
            "%'",
            "_'",
            "'%",
            "'_",
            "'%'",
            "'_'",
        );

        $this->clearTables();
        foreach($test_strings as $key => $string) {
            $value = $this->db->quote($string, 'text');
            $query = "INSERT INTO users (user_name,user_id) VALUES ($value, $key)";
            $result = $this->db->exec($query);
            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error executing insert query'.$result->getMessage());
            }

            $query = 'SELECT user_name FROM users WHERE user_name LIKE '.$this->db->quote($string, 'text', true, true);
            $value = $this->db->queryOne($query, 'text');
            if (PEAR::isError($value)) {
                $this->assertTrue(false, 'Error executing select query'.$value->getMessage());
            }

            $this->assertEquals($string, $value, "the value retrieved for field \"user_name\" doesn't match what was stored");
        }

        $this->db->loadModule('Datatype', null, true);
        $query = 'SELECT user_name FROM users WHERE user_name LIKE '.$this->db->datatype->matchPattern(array('foo%', '_', '23'));
        $value = $this->db->queryOne($query, 'text');
        $this->assertEquals('foo%123', $value, "the value retrieved for field \"user_name\" doesn't match what was stored");

        $query = 'SELECT user_name FROM users WHERE user_name LIKE '.$this->db->datatype->matchPattern(array(1 => '_', 'oo', '%'));
        $value = $this->db->queryOne($query, 'text');
        $this->assertEquals('foo', substr($value, 0, 3), "the value retrieved for field \"user_name\" doesn't match what was stored");
    }

    /**
     * Test paged queries
     *
     * Test the use of setLimit to return paged queries
     */
    function testRanges() {
        if (!$this->supported('limit_queries')) {
            $this->assertTrue(false, '"limit_queries" is not supported');
            return;
        }

        $data = array();
        $total_rows = 5;

        $query = 'INSERT INTO users (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);
            $result = $stmt->execute(array_values($data[$row]));

            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
            }
        }

        $stmt->free();

        for ($rows = 2, $start_row = 0; $start_row < $total_rows; $start_row += $rows) {

            $this->db->setLimit($rows, $start_row);

            $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM users ORDER BY user_name';
            $result =& $this->db->query($query, $this->fields);

            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error executing select query'.$result->getMessage());
            }

            for ($row = 0; $row < $rows && ($row + $start_row < $total_rows); $row++) {
                $this->verifyFetchedValues($result, $row, $data[$row + $start_row]);
            }
        }

        $this->assertTrue(!$result->valid(), "The query result did not seem to have reached the end of result as expected starting row $start_row after fetching upto row $row");

        $result->free();

        for ($rows = 2, $start_row = 0; $start_row < $total_rows; $start_row += $rows) {

            $this->db->setLimit($rows, $start_row);

            $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM users ORDER BY user_name';
            $result =& $this->db->query($query, $this->fields);

            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error executing select query'.$result->getMessage());
            }

            $result_rows = $result->numRows();

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
     */
    function testSequences() {
        if (!$this->supported('sequences')) {
            $this->assertTrue(false, '"sequences" is not supported');
            return;
        }

        $this->db->loadModule('Manager', null, true);

        for ($start_value = 1; $start_value < 4; $start_value++) {
            $sequence_name = "test_sequence_$start_value";

            $result = $this->db->manager->createSequence($sequence_name, $start_value);
            if (PEAR::isError($result)) {
                $this->assertTrue(false, "Error creating sequence $sequence_name with start value $start_value: ".$result->getMessage());
            } else {
                for ($sequence_value = $start_value; $sequence_value < ($start_value + 4); $sequence_value++) {
                    $value = $this->db->nextId($sequence_name, false);

                    $this->assertEquals($sequence_value, $value, "The returned sequence value is not expected with sequence start value with $start_value");
                }

                $result = $this->db->manager->dropSequence($sequence_name);

                if (PEAR::isError($result)) {
                    $this->assertTrue(false, "Error dropping sequence $sequence_name : ".$result->getMessage());
                }
            }
        }

        // Test ondemand creation of sequences
        $sequence_name = 'test_ondemand';
        $this->db->expectError(MDB2_ERROR_NOSUCHTABLE);
        $this->db->manager->dropSequence($sequence_name);
        $this->db->popExpect();

        for ($sequence_value = 1; $sequence_value < 4; $sequence_value++) {
            $value = $this->db->nextId($sequence_name);

            if (PEAR::isError($result)) {
                $this->assertTrue(false, "Error creating with ondemand sequence: ".$result->getMessage());
            } else {
                $this->assertEquals($sequence_value, $value, "Error in ondemand sequences. The returned sequence value is not expected value");
            }
        }

        $result = $this->db->manager->dropSequence($sequence_name);

        if (PEAR::isError($result)) {
            $this->assertTrue(false, "Error dropping sequence $sequence_name : ".$result->getMessage());
        }

        // Test currId()
        $sequence_name = 'test_currid';

        $next = $this->db->nextId($sequence_name);
        $curr = $this->db->currId($sequence_name);

        if (PEAR::isError($curr)) {
            $this->assertTrue(false, "Error getting the current value of sequence $sequence_name : ".$curr->getMessage());
        } else {
            if ($next != $curr) {
                if ($next+1 == $curr) {
                    $this->assertTrue(false, "Warning: currId() is using nextId() instead of a native implementation");
                } else {
                    $this->assertEquals($next, $curr, "return value if currId() does not match the previous call to nextId()");
                }
            }
        }
        $result = $this->db->manager->dropSequence($sequence_name);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, "Error dropping sequence $sequence_name : ".$result->getMessage());
        }
    }


    /**
     * Test replace query
     *
     * The replace method emulates the replace query of mysql
     */
    function testReplace() {
        if (!$this->supported('replace')) {
            $this->assertTrue(false, '"replace" is not supported');
            return;
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

        $result = $this->db->replace('users', $fields);

        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Replace failed');
        }

        if ($this->db->supports('affected_rows')) {
            $affected_rows = $result;

            $this->assertEquals(1, $result, "replacing a row in an empty table returned incorrect value");
        } else {
            $this->assertTrue(false, '"affected_rows" is not supported');
        }

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM users';
        $result =& $this->db->query($query, $this->fields);

        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
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

        $result = $this->db->replace('users', $fields);

        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Replace failed');
        }
        if ($this->db->supports('affected_rows')) {
            $this->assertEquals(2, $result, "replacing a row returned incorrect result");
        }

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM users';
        $result =& $this->db->query($query, $this->fields);

        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }

        $this->verifyFetchedValues($result, 0, $data);

        $this->assertTrue(!$result->valid(), 'the query result did not seem to have reached the end of result as expected');

        $result->free();
    }

    /**
     * Test affected rows methods
     */
    function testAffectedRows() {
        if (!$this->supported('affected_rows')) {
            $this->assertTrue(false, '"affected_rows" is not supported');
            return;
        }

        $data = array();
        $total_rows = 7;

        $query = 'INSERT INTO users (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);
            $result = $stmt->execute(array_values($data[$row]));

            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
            }

            $this->assertEquals(1, $result, "Inserting the row $row returned incorrect affected row count");
        }

        $stmt->free();

        $query = 'UPDATE users SET user_password=? WHERE user_id < ?';
        $stmt = $this->db->prepare($query, array('text', 'integer'), MDB2_PREPARE_MANIP);

        for ($row = 0; $row < $total_rows; $row++) {
            $password = "pass_$row";
            if ($row == 0) {
                $stmt->bindParam(0, $password);
                $stmt->bindParam(1, $row);
            }

            $result = $stmt->execute();

            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
            }

            $this->assertEquals($row, $result, "Updating the $row rows returned incorrect affected row count");
        }

        $stmt->free();

        $query = 'DELETE FROM users WHERE user_id >= ?';
        $stmt = $this->db->prepare($query, array('integer'), MDB2_PREPARE_MANIP);

        $row = intval($total_rows / 2);
        $stmt->bindParam(0, $row);
        for ($row = $total_rows; $total_rows; $total_rows = $row) {
            $row = intval($total_rows / 2);

            $result = $stmt->execute();

            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
            }

            $this->assertEquals(($total_rows - $row), $result, 'Deleting rows returned incorrect affected row count');

        }

        $stmt->free();
    }

    /**
     * Testing transaction support - Test ROLLBACK
     */
    function testTransactionsRollback() {
        if (!$this->supported('transactions')) {
            $this->assertTrue(false, '"transactions" is not supported');
            return;
        }

        $data = $this->getSampleData(0);

        $this->db->beginTransaction();

        $query = 'INSERT INTO users (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);

        $result = $stmt->execute(array_values($data));
        $this->db->rollback();
        $stmt->free();

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM users';
        $result =& $this->db->query($query);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }
        $this->assertTrue(!$result->valid(), 'Transaction rollback did not revert the row that was inserted');
        $result->free();
    }

    /**
     * Testing transaction support - Test COMMIT
     */
    function testTransactionsCommit() {
        if (!$this->supported('transactions')) {
            $this->assertTrue(false, '"transactions" is not supported');
            return;
        }

        $data = $this->getSampleData(1);

        $this->db->beginTransaction();

        $query = 'INSERT INTO users (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);

        $result = $stmt->execute(array_values($data));
        $this->db->commit();
        $stmt->free();

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM users';
        $result =& $this->db->query($query);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }
        $this->assertTrue($result->valid(), 'Transaction commit did not make permanent the row that was inserted');
        $result->free();
    }

    /**
     * Testing transaction support - Test COMMIT and ROLLBACK
     */
    function testTransactionsBoth()
    {
        if (!$this->supported('transactions')) {
            $this->assertTrue(false, '"transactions" is not supported');
            return;
        }

        $data = $this->getSampleData(0);

        $this->db->beginTransaction();
        $result = $this->db->exec('DELETE FROM users');
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error deleting from users'.$result->getMessage());
            $this->db->rollback();
        } else {
            $this->db->commit();
        }

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM users';
        $result =& $this->db->query($query);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }

        $this->assertTrue(!$result->valid(), 'Transaction end with implicit commit when re-enabling auto-commit did not make permanent the rows that were deleted');
        $result->free();
    }

    /**
     * Testing emulated nested transaction support
     */
    function testNestedTransactions() {
        if (!$this->supported('transactions')) {
            $this->assertTrue(false, '"transactions" is not supported');
            return;
        }

        $data = array(
            1 => $this->getSampleData(1234),
            2 => $this->getSampleData(4321),
        );

        $this->db->beginNestedTransaction();

        $query = 'INSERT INTO users (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);

        $result = $stmt->execute(array_values($data[1]));

        $this->db->beginNestedTransaction();

        $result = $stmt->execute(array_values($data[2]));
        $stmt->free();

        $result = $this->db->completeNestedTransaction();
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Inner transaction was not committed: '.$result->getMessage());
        }

        $result = $this->db->completeNestedTransaction();
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Outer transaction was not committed: '.$result->getMessage());
        }

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM users';
        $result =& $this->db->query($query);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }
        $this->assertTrue($result->valid(), 'Transaction commit did not make permanent the row that was inserted');
        $result->free();
    }

    /**
     * Testing savepoints
     */
    function testSavepoint() {
        if (!$this->supported('savepoints')) {
            $this->assertTrue(false, '"savepoints" is not supported');
            return;
        }

        $savepoint = 'test_savepoint';

        $data = array(
            1 => $this->getSampleData(1234),
            2 => $this->getSampleData(4321),
        );

        $this->db->beginTransaction();

        $query = 'INSERT INTO users (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);

        $result = $stmt->execute(array_values($data[1]));
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error executing prepared query: '.$result->getMessage());
        }

        $result = $this->db->beginTransaction($savepoint);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error setting savepoint: '.$result->getMessage());
        }

        $result = $stmt->execute(array_values($data[2]));
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error executing prepared query: '.$result->getMessage());
        }
        $stmt->free();

        $result = $this->db->rollback($savepoint);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error rolling back to savepoint: '.$result->getMessage());
        }

        $result = $this->db->commit();
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Transaction not committed: '.$result->getMessage());
        }

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM users';
        $result = $this->db->queryAll($query);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }
        $rows_inserted = count($result);
        $this->assertEquals(1, $rows_inserted, 'Error during transaction, invalid number of records inserted');

        // test release savepoint
        $this->db->beginTransaction();
        $result = $this->db->beginTransaction($savepoint);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error setting savepoint: '.$result->getMessage());
        }
        $result = $this->db->commit($savepoint);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error setting savepoint: '.$result->getMessage());
        }
        $result = $this->db->commit();
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Transaction not committed: '.$result->getMessage());
        }
    }

    /**
     * Testing LOB storage
     */
    function testLOBStorage() {
        if (!$this->supported('LOBs')) {
            $this->assertTrue(false, '"LOBs" is not supported');
            return;
        }

        $query = 'INSERT INTO files (ID, document, picture) VALUES (1, ?, ?)';
        $stmt = $this->db->prepare($query, array('clob', 'blob'), MDB2_PREPARE_MANIP, array('document', 'picture'));

        $character_lob = '';
        $binary_lob = '';

        for ($i = 0; $i < 1000; $i++) {
            for ($code = 32; $code <= 127; $code++) {
                $character_lob.= chr($code);
            }
            for ($code = 0; $code <= 255; $code++) {
                $binary_lob.= chr($code);
            }
        }

        $stmt->bindParam(0, $character_lob);
        $stmt->bindParam(1, $binary_lob);

        $result = $stmt->execute();

        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error executing prepared query: '.$result->getUserInfo());
        }

        $stmt->free();

        $result =& $this->db->query('SELECT document, picture FROM files WHERE id = 1', array('clob', 'blob'));
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from files'.$result->getMessage());
        }

        $this->assertTrue($result->valid(), 'The query result seem to have reached the end of result too soon.');

        $row = $result->fetchRow();
        $clob = $row[0];
        if (!PEAR::isError($clob) && is_resource($clob)) {
            $value = '';
            while (!feof($clob)) {
                $data = fread($clob, 8192);
                $this->assertTrue(strlen($data) >= 0, 'Could not read CLOB');
                $value.= $data;
            }
            $this->db->datatype->destroyLOB($clob);
            $this->assertEquals($character_lob, $value, 'Retrieved character LOB value is different from what was stored');
        } else {
            $this->assertTrue(false, 'Error retrieving CLOB result');
        }

        $blob = $row[1];
        if (!PEAR::isError($blob) && is_resource($blob)) {
            $value = '';
            while (!feof($blob)) {
                $data = fread($blob, 8192);
                $this->assertTrue(strlen($data) >= 0, 'Could not read BLOB');
                $value.= $data;
            }

            $this->db->datatype->destroyLOB($blob);
            $this->assertEquals($binary_lob, $value, 'Retrieved binary LOB value is different from what was stored');
        } else {
            $this->assertTrue(false, 'Error retrieving BLOB result');
        }
        $result->free();
    }

    /**
     * Test for lob storage from and to files
     */
    function testLOBFiles() {
        if (!$this->supported('LOBs')) {
            $this->assertTrue(false, '"LOBs" is not supported');
            return;
        }

        $query = 'INSERT INTO files (ID, document, picture) VALUES (1, :document, :picture)';
        $stmt = $this->db->prepare($query, array('document' => 'clob', 'picture' => 'blob'), MDB2_PREPARE_MANIP);

        $character_data_file = 'character_data';
        $file = fopen($character_data_file, 'w');
        $this->assertTrue(((bool)$file), 'Error creating clob file to read from');
        $character_data = '';
        for ($i = 0; $i < 1000; $i++) {
            for ($code = 32; $code <= 127; $code++) {
                $character_data.= chr($code);
            }
        }
        $this->assertTrue((fwrite($file, $character_data, strlen($character_data)) == strlen($character_data)), 'Error creating clob file to read from');
        fclose($file);

        $binary_data_file = 'binary_data';
        $file = fopen($binary_data_file, 'wb');
        $this->assertTrue(((bool)$file), 'Error creating blob file to read from');
        $binary_data = '';
        for ($i = 0; $i < 1000; $i++) {
            for ($code = 0; $code <= 255; $code++) {
                $binary_data.= chr($code);
            }
        }
        $this->assertTrue((fwrite($file, $binary_data, strlen($binary_data)) == strlen($binary_data)), 'Error creating blob file to read from');
        fclose($file);

        $character_data_file_tmp = 'file://'.$character_data_file;
        $stmt->bindParam('document', $character_data_file_tmp);
        $binary_data_file_tmp = 'file://'.$binary_data_file;
        $stmt->bindParam('picture', $binary_data_file_tmp);

        $result = $stmt->execute();
        $this->assertTrue(!PEAR::isError($result), 'Error executing prepared query - inserting LOB from files');

        $stmt->free();

        $result =& $this->db->query('SELECT document, picture FROM files WHERE id = 1', array('clob', 'blob'));
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from files'.$result->getMessage());
        }

        $this->assertTrue($result->valid(), 'The query result seem to have reached the end of result too soon.');

        $row = $result->fetchRow();
        $clob = $row[0];
        if (!PEAR::isError($clob) && is_resource($clob)) {
            unlink($character_data_file);
            $res = $this->db->datatype->writeLOBToFile($clob, $character_data_file);
            $this->db->datatype->destroyLOB($clob);

            if (PEAR::isError($res)) {
                $this->assertTrue(false, 'Error writing character LOB in a file');
            } else {
                $file = fopen($character_data_file, 'r');
                $this->assertTrue($file, "Error opening character data file: $character_data_file");
                $value = '';
                while (!feof($file)) {
                    $value.= fread($file, 8192);
                }
                $this->assertEquals('string', gettype($value), "Could not read from character LOB file: $character_data_file");
                fclose($file);

                $this->assertEquals($character_data, $value, "retrieved character LOB value is different from what was stored");
            }
        } else {
            $this->assertTrue(false, 'Error creating character LOB in a file');
        }

        $blob = $row[1];
        if (!PEAR::isError($blob) && is_resource($blob)) {
            unlink($binary_data_file);
            $res = $this->db->datatype->writeLOBToFile($blob, $binary_data_file);
            $this->db->datatype->destroyLOB($blob);

            if (PEAR::isError($res)) {
                $this->assertTrue(false, 'Error writing binary LOB in a file');
            } else {
                $file = fopen($binary_data_file, 'rb');
                $this->assertTrue($file, "Error opening binary data file: $binary_data_file");
                $value = '';
                while (!feof($file)) {
                    $value.= fread($file, 8192);
                }
                $this->assertEquals('string', gettype($value), "Could not read from binary LOB file: $binary_data_file");
                fclose($file);

                $this->assertEquals($binary_data, $value, "retrieved binary LOB value is different from what was stored");
            }
        } else {
            $this->assertTrue(false, 'Error creating binary LOB in a file');
        }

        $result->free();
    }

    /**
     * Test handling of lob nulls
     */
    function testLOBNulls() {
        if (!$this->supported('LOBs')) {
            $this->assertTrue(false, '"LOBs" is not supported');
            return;
        }

        $query = 'INSERT INTO files (ID, document, picture) VALUES (1, :document, :picture)';
        $stmt = $this->db->prepare($query, array('document' => 'clob', 'picture' => 'blob'), MDB2_PREPARE_MANIP);

        $null = null;
        $stmt->bindParam('document', $null);
        $stmt->bindParam('picture', $null);

        $result = $stmt->execute();
        $this->assertTrue(!PEAR::isError($result), 'Error executing prepared query - inserting NULL lobs');

        $stmt->free();

        $result =& $this->db->query('SELECT document, picture FROM files', array('clob', 'blob'));
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from files'.$result->getMessage());
        }

        $this->assertTrue($result->valid(), 'The query result seem to have reached the end of result too soon.');

        $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
        $this->assertTrue(is_null($row['document']), 'A query result large object column document is not NULL unlike what was expected');
        $this->assertTrue(is_null($row['picture']), 'A query result large object column picture is not NULL unlike what was expected');

        $result->free();
    }

    /**
     * Test retrieval of result metadata
     *
     * This tests the result metadata by executing a prepared query and
     * select the data, and checking the result contains the correct
     * number of columns and that the column names are in the correct order
     */
    function testConvertEmpty2Null() {
#$this->db->setOption('portability', MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL);

        $data = $this->getSampleData(1234);
        $data['user_password'] = '';

        $query = 'INSERT INTO users (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        $result = $stmt->execute(array_values($data));
        $stmt->free();

        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
        }

        $row = $this->db->queryRow('SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM users WHERE user_password IS NULL', $this->fields);

        if (PEAR::isError($row)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }

        $this->assertEquals(count($this->fields), count($row), "The query result returned a number of columns unlike ".count($this->fields) .' as expected');
    }

    function testPortabilityOptions() {
        // MDB2_PORTABILITY_DELETE_COUNT
        $data = array();
        $total_rows = 5;

        $query = 'INSERT INTO users (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row] = $this->getSampleData($row);
            $result = $stmt->execute(array_values($data[$row]));
            if (PEAR::isError($result)) {
                $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
            }
        }
        $stmt->free();

        $this->db->setOption('portability', MDB2_PORTABILITY_NONE | MDB2_PORTABILITY_DELETE_COUNT);
        $affected_rows = $this->db->exec('DELETE FROM users');
        if (PEAR::isError($affected_rows)) {
            $this->assertTrue(false, 'Error executing query'.$affected_rows->getMessage());
        }
        $this->assertEquals($total_rows, $affected_rows, 'MDB2_PORTABILITY_DELETE_COUNT not working');

        // MDB2_PORTABILITY_FIX_CASE
        $fields = array_keys($this->fields);
        $this->db->setOption('portability', MDB2_PORTABILITY_NONE | MDB2_PORTABILITY_FIX_CASE);
        $this->db->setOption('field_case', CASE_UPPER);

        $data = $this->getSampleData(1234);
        $query = 'INSERT INTO users (' . implode(', ', array_keys($this->fields)) . ') VALUES ('.implode(', ', array_fill(0, count($this->fields), '?')).')';
        $stmt = $this->db->prepare($query, array_values($this->fields), MDB2_PREPARE_MANIP);
        $result = $stmt->execute(array_values($data));
        $stmt->free();

        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM users';
        $result =& $this->db->queryRow($query, $this->fields, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }
        $field = reset($fields);
        foreach (array_keys($result) as $fieldname) {
            $this->assertEquals(strtoupper($field), $fieldname, 'MDB2_PORTABILITY_FIX_CASE CASE_UPPER not working');
            $field = next($fields);
        }

        $this->db->setOption('field_case', CASE_LOWER);
        $query = 'SELECT ' . implode(', ', array_keys($this->fields)) . ' FROM users';
        $result =& $this->db->queryRow($query, $this->fields, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }
        $field = reset($fields);
        foreach (array_keys($result) as $fieldname) {
            $this->assertEquals(strtolower($field), $fieldname, 'MDB2_PORTABILITY_FIX_CASE CASE_LOWER not working');
            $field = next($fields);
        }
        /*
        // leave the case as-is
        $this->db->setOption('portability', MDB2_PORTABILITY_NONE);
        $fields = array('User_Name', 'UseR_PassWord');
        $query = 'SELECT '. implode(',', $fields).' FROM users';
        $result =& $this->db->queryRow($query, null, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }
        $field = reset($fields);
        foreach (array_keys($result) as $fieldname) {
            $this->assertEquals($field, $fieldname, '"MDB2_PORTABILITY_FIX_CASE = false" not working');
            $field = next($fields);
        }
        */

        // MDB2_PORTABILITY_RTRIM
        $this->db->setOption('portability', MDB2_PORTABILITY_NONE | MDB2_PORTABILITY_RTRIM);
        $value = 'rtrim   ';
        $query = 'INSERT INTO users (user_id, user_password) VALUES (1, ' . $this->db->quote($value, 'text') .')';
        $res = $this->db->exec($query);
        if (PEAR::isError($res)) {
            $this->assertTrue(false, 'Error executing query'.$res->getMessage());
        }
        $query = 'SELECT user_password FROM users WHERE user_id = 1';
        $result = $this->db->queryOne($query, array('text'));
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }
        $this->assertEquals(rtrim($value), $result, '"MDB2_PORTABILITY_RTRIM = on" not working');

        $this->db->setOption('portability', MDB2_PORTABILITY_NONE);
        $value = 'rtrim   ';
        $query = 'INSERT INTO users (user_id, user_password) VALUES (2, ' . $this->db->quote($value, 'text') .')';
        $res = $this->db->exec($query);
        if (PEAR::isError($res)) {
            $this->assertTrue(false, 'Error executing query'.$res->getMessage());
        }
        $query = 'SELECT user_password FROM users WHERE user_id = 2';
        $result = $this->db->queryOne($query, array('text'));
        if (PEAR::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }
        $this->assertEquals($value, $result, '"MDB2_PORTABILITY_RTRIM = off" not working');
    }
}

?>