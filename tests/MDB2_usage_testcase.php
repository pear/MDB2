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
// $Id$

class MDB2_Usage_TestCase extends PHPUnit_TestCase {
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

    function MDB2_Usage_TestCase($name) {
        $this->PHPUnit_TestCase($name);
    }

    function setUp() {
        $this->dsn = $GLOBALS['dsn'];
        $this->options  = $GLOBALS['options'];
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

    function supported($feature) {
        if (!$this->db->supports($feature)) {
            $this->assertTrue(false, 'This database does not support '.$feature);
            return false;
        }
        return true;
    }

    function insertTestValues($prepared_query, &$data) {
        for ($i = 0; $i < count($this->fields); $i++) {
            $this->db->setParam($prepared_query, ($i + 1), $data[$this->fields[$i]]);
        }
    }

    function verifyFetchedValues(&$result, $rownum, &$data) {
        $result->seek($rownum);
        $row = $result->fetchRow(MDB2_FETCHMODE_ORDERED);
        for ($i = 0; $i < count($this->fields); $i++) {
            $value = $row[$i];
            $field = $this->fields[$i];
            if ($this->types[$i] == 'float') {
                $delta = 0.0000000001;
            } else {
                $delta = 0;
            }

            $this->assertEquals($data[$field], $value, "the value retrieved for field \"$field\" ($value) doesn't match what was stored ($data[$field]) into the row $rownum", $delta);
        }
    }

    /**
     * Test typed data storage and retrieval
     *
     * This tests typed data storage and retrieval by executing a single
     * prepared query and then selecting the data back from the database
     * and comparing the results
     */
    function testStorage() {
        $row = 1234;
        $data = array();
        $data['user_name'] = "user_$row";
        $data['user_password'] = 'somepassword';
        $data['subscribed'] = $row % 2 ? true : false;
        $data['user_id'] = $row;
        $data['quota'] = strval($row/100);
        $data['weight'] = sqrt($row);
        $data['access_date'] = MDB2_Date::mdbToday();
        $data['access_time'] = MDB2_Date::mdbTime();
        $data['approved'] = MDB2_Date::mdbNow();

        $prepared_query = $this->db->prepare('INSERT INTO users (user_name, user_password, subscribed, user_id, quota, weight, access_date, access_time, approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $this->types);

        $this->insertTestValues($prepared_query, $data);

        $result = $this->db->execute($prepared_query);

        $this->db->freePrepared($prepared_query);

        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
        }

        $result =& $this->db->query('SELECT user_name, user_password, subscribed, user_id, quota, weight, access_date, access_time, approved FROM users', $this->types);

        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }

        $this->verifyFetchedValues($result, 0, $data);
    }

    /**
     * Test bulk fetch
     *
     * This test bulk fetching of result data by using a prepared query to
     * insert an number of rows of data and then retrieving the data columns
     * one by one
     */
    function testBulkFetch() {
        $data = array();
        $total_rows = 5;

        $prepared_query = $this->db->prepare('INSERT INTO users (user_name, user_password, subscribed, user_id, quota, weight, access_date, access_time, approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $this->types);

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row]['user_name'] = "user_$row";
            $data[$row]['user_password'] = 'somepassword';
            $data[$row]['subscribed'] = $row % 2 ? true : false;
            $data[$row]['user_id'] = $row;
            $data[$row]['quota'] = sprintf("%.2f",strval(1+($row+1)/100));
            $data[$row]['weight'] = sqrt($row);
            $data[$row]['access_date'] = MDB2_Date::mdbToday();
            $data[$row]['access_time'] = MDB2_Date::mdbTime();
            $data[$row]['approved'] = MDB2_Date::mdbNow();

            $this->insertTestValues($prepared_query, $data[$row]);

            $result = $this->db->execute($prepared_query);

            if (MDB2::isError($result)) {
                $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
            }
        }

        $this->db->freePrepared($prepared_query);

        $total_fields = count($this->fields);
        for ($i = 0; $i < $total_fields; $i++) {
            $field = $this->fields[$i];
            for ($row = 0; $row < $total_rows; $row++) {
                $result =& $this->db->query('SELECT '.$field.' FROM users WHERE user_id='.$row, $this->types[$i]);
                $value = $result->fetch();
                if (MDB2::isError($value)) {
                    $this->assertTrue(false, 'Error fetching row '.$row.' for field '.$field.' of type '.$this->types[$i]);
                } else {
                    $this->assertEquals(strval($data[$row][$field]), strval(trim($value)), 'the query field '.$field.' of type '.$this->types[$i].' for row '.$row.' was returned as "'.$value.'" unlike "'.$data[$row][$field].'" as expected');
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

        $prepared_query = $this->db->prepare('INSERT INTO users (user_name, user_password, subscribed, user_id, quota, weight, access_date, access_time, approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $this->types);

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row]['user_name'] = "user_$row";
            $data[$row]['user_password'] = 'somepassword';
            $data[$row]['subscribed'] = $row % 2 ? true : false;
            $data[$row]['user_id'] = $row;
            $data[$row]['quota'] = sprintf("%.2f",strval(1+($row+1)/100));
            $data[$row]['weight'] = sqrt($row);
            $data[$row]['access_date'] = MDB2_Date::mdbToday();
            $data[$row]['access_time'] = MDB2_Date::mdbTime();
            $data[$row]['approved'] = MDB2_Date::mdbNow();

            $this->insertTestValues($prepared_query, $data[$row]);

            $result = $this->db->execute($prepared_query);

            if (MDB2::isError($result)) {
                $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
            }
        }

        $this->db->freePrepared($prepared_query);

        $first_col = array();
        for ($row = 0; $row < $total_rows; $row++) {
            $first_col[$row] = "user_$row";
        }

        $second_col = array();
        for ($row = 0; $row < $total_rows; $row++) {
            $second_col[$row] = $row;
        }

        $result =& $this->db->query('SELECT user_name, user_id FROM users', array('text', 'integer'));
        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error during query');
        }
        $values = $result->fetchCol(0);
        if (MDB2::isError($values)) {
            $this->assertTrue(false, 'Error fetching first column');
        } else {
            $this->assertEquals($first_col, $values);
        }
        $result->free();

        $result =& $this->db->query('SELECT user_name, user_id FROM users', array('text', 'integer'));
        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error during query');
        }
        $values = $result->fetchCol(1);
        if (MDB2::isError($values)) {
            $this->assertTrue(false, 'Error fetching second column');
        } else {
            $this->assertEquals($second_col, $values);
        }
        $result->free();
    }

    /**
     * Test prepared queries
     *
     * Tests prepared queries, making sure they correctly deal with ?, !, and '
     */
    function testPreparedQueries() {
        $question_value = $this->db->quote('Does this work?', 'text');

        $prepared_query = $this->db->prepare("INSERT INTO users (user_name, user_password, user_id) VALUES (?, $question_value, 1)", array('text'));

        $this->db->setParam($prepared_query, 1, 'Sure!');

        $result = $this->db->execute($prepared_query);

        $this->db->freePrepared($prepared_query);

        if (MDB2::isError($result)) {
            $error = $result->getMessage();
        }

        $this->assertTrue(!MDB2::isError($result), 'Could not execute prepared query with a text value with a question mark. Error: ');

        $question_value = $this->db->quote("Wouldn't it be great if this worked too?", 'text');

        $prepared_query = $this->db->prepare("INSERT INTO users (user_name, user_password, user_id) VALUES (?, $question_value, 2)", array('text'));

        $this->db->setParam($prepared_query, 1, 'For Sure!');

        $result = $this->db->execute($prepared_query);

        $this->db->freePrepared($prepared_query);

        if (MDB2::isError($result)) {
            $error = $result->getMessage();
        }

        $this->assertTrue(!MDB2::isError($result), 'Could not execute prepared query with a text value with a quote character before a question mark. Error: ');

    }

    /**
     * Test retrieval of result metadata
     *
     * This tests the result metadata by executing a prepared_query and
     * select the data, and checking the result contains the correct
     * number of columns and that the column names are in the correct order
     */
    function testMetadata() {
        $row = 1234;
        $data = array();
        $data['user_name'] = "user_$row";
        $data['user_password'] = 'somepassword';
        $data['subscribed'] = $row % 2 ? true : false;
        $data['user_id'] = $row;
        $data['quota'] = strval($row/100);
        $data['weight'] = sqrt($row);
        $data['access_date'] = MDB2_Date::mdbToday();
        $data['access_time'] = MDB2_Date::mdbTime();
        $data['approved'] = MDB2_Date::mdbNow();

        $prepared_query = $this->db->prepare('INSERT INTO users (user_name, user_password, subscribed, user_id, quota, weight, access_date, access_time, approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $this->types);

        $this->insertTestValues($prepared_query, $data);

        $result = $this->db->execute($prepared_query);

        $this->db->freePrepared($prepared_query);

        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
        }

        $result =& $this->db->query('SELECT user_name, user_password, subscribed, user_id, quota, weight, access_date, access_time, approved FROM users', $this->types);

        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }

        $numcols = $result->numCols();

        $this->assertEquals(count($this->fields), $numcols, "The query result returned a number of $numcols columns unlike ".count($this->fields) .' as expected');

        $column_names = $result->getColumnNames();
        for ($column = 0; $column < $numcols; $column++) {
            $this->assertEquals($column, $column_names[$this->fields[$column]], "The query result column \"".$this->fields[$column]."\" was returned in position ".$column_names[$this->fields[$column]]." unlike $column as expected");
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

            $result =& $this->db->query("INSERT INTO users (user_name,user_password,user_id) VALUES ($value,$value,0)");

            if (MDB2::isError($result)) {
                $this->assertTrue(false, 'Error executing insert query'.$result->getMessage());
            }

            $result =& $this->db->query('SELECT user_name,user_password FROM users', array('text', 'text'));

            if (MDB2::isError($result)) {
                $this->assertTrue(false, 'Error executing select query'.$result->getMessage());
            }

            $this->assertTrue($result->valid(), 'The query result seems to have reached the end of result earlier than expected');

            if ($is_null) {
                $error_message = 'A query result column is not NULL unlike what was expected';
            } else {
                $error_message = 'A query result column is NULL even though it was expected to be "' . $test_values[$test_value][0] . '"';
            }

            $value = $result->resultIsNull(0, 0);
            $this->assertTrue(($value == $is_null), $error_message);

            $value = $result->resultIsNull(0, 1);
            $this->assertTrue(($value == $is_null), $error_message);

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

        for ($string = 0; $string < count($test_strings); $string++) {
            $this->clearTables();

            $value = $this->db->quote($test_strings[$string], 'text');

            $result =& $this->db->query("INSERT INTO users (user_name,user_password,user_id) VALUES ($value,$value,0)");

            if (MDB2::isError($result)) {
                $this->assertTrue(false, 'Error executing insert query'.$result->getMessage());
            }

            $result =& $this->db->query('SELECT user_name,user_password FROM users', array('text', 'text'));

            if (MDB2::isError($result)) {
                $this->assertTrue(false, 'Error executing select query'.$result->getMessage());
            }

            $this->assertTrue($result->valid(), 'The query result seems to have reached the end of result earlier than expected');

            $value = $result->fetch();
            $result->free();

            $this->assertEquals($test_strings[$string], rtrim($value), "the value retrieved for field \"user_name\" (\"$value\") doesn't match what was stored (".$test_strings[$string].')');

        }
    }

    /**
     * Test paged queries
     *
     * Test the use of setLimit to return paged queries
     */
    function testRanges() {
        if (!$this->supported('limit_queries')) {
            return;
        }

        $data = array();
        $total_rows = 5;

        $prepared_query = $this->db->prepare('INSERT INTO users (user_name, user_password, subscribed, user_id, quota, weight, access_date, access_time, approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $this->types);

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row]['user_name'] = "user_$row";
            $data[$row]['user_password'] = 'somepassword';
            $data[$row]['subscribed'] = $row % 2 ? true : false;
            $data[$row]['user_id'] = $row;
            $data[$row]['quota'] = sprintf("%.2f",strval(1+($row+1)/100));
            $data[$row]['weight'] = sqrt($row);
            $data[$row]['access_date'] = MDB2_Date::mdbToday();
            $data[$row]['access_time'] = MDB2_Date::mdbTime();
            $data[$row]['approved'] = MDB2_Date::mdbNow();

            $this->insertTestValues($prepared_query, $data[$row]);

            $result = $this->db->execute($prepared_query);

            if (MDB2::isError($result)) {
                $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
            }
        }

        $this->db->freePrepared($prepared_query);

        for ($rows = 2, $start_row = 0; $start_row < $total_rows; $start_row += $rows) {

            $this->db->setLimit($rows, $start_row);

            $result =& $this->db->query('SELECT user_name,user_password,subscribed,user_id,quota,weight,access_date,access_time,approved FROM users ORDER BY user_id', $this->types);

            if (MDB2::isError($result)) {
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

            $result =& $this->db->query('SELECT user_name, user_password, subscribed, user_id, quota, weight, access_date, access_time, approved FROM users ORDER BY user_id', $this->types);

            if (MDB2::isError($result)) {
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
            return;
        }

        $this->db->loadModule('manager');

        for ($start_value = 1; $start_value < 4; $start_value++) {
            $sequence_name = "test_sequence_$start_value";

            $result = $this->db->manager->createSequence($sequence_name, $start_value);
            $this->assertTrue(!MDB2::isError($result), "Error creating sequence $sequence_name with start value $start_value");

            for ($sequence_value = $start_value; $sequence_value < ($start_value + 4); $sequence_value++) {
                $value = $this->db->nextId($sequence_name, false);

                $this->assertEquals($value, $sequence_value, "The returned sequence value is $value and not $sequence_value as expected with sequence start value with $start_value");

            }

            $result = $this->db->manager->dropSequence($sequence_name);

            if (MDB2::isError($result)) {
                $this->assertTrue(false, "Error dropping sequence $sequence_name : ".$result->getMessage());
            }

        }

        // Test ondemand creation of sequences
        $sequence_name = 'test_ondemand';

        for ($sequence_value = 1; $sequence_value < 4; $sequence_value++) {
            $value = $this->db->nextId($sequence_name);

            $this->assertEquals($value, $sequence_value, "Error in ondemand sequences. The returned sequence value is $value and not $sequence_value as expected");

        }

        $result = $this->db->manager->dropSequence($sequence_name);

        if (MDB2::isError($result)) {
            $this->assertTrue(false, "Error dropping sequence $sequence_name : ".$result->getMessage());
        }

        // Test currId()
        $sequence_name = 'test_currid';

        $next = $this->db->nextId($sequence_name);
        $curr = $this->db->currId($sequence_name);

        if (MDB2::isError($curr)) {
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
        if (MDB2::isError($result)) {
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
            return;
        }

        $row = 1234;
        $data = array();
        $data['user_name'] = "user_$row";
        $data['user_password'] = 'somepassword';
        $data['subscribed'] = $row % 2 ? true : false;
        $data['user_id'] = $row;
        $data['quota'] = strval($row/100);
        $data['weight'] = sqrt($row);
        $data['access_date'] = MDB2_Date::mdbToday();
        $data['access_time'] = MDB2_Date::mdbTime();
        $data['approved'] = MDB2_Date::mdbNow();

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

        $support_affected_rows = $this->db->supports('affected_rows');

        $result = $this->db->replace('users', $fields);

        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Replace failed');
        }

        if ($support_affected_rows) {
            $affected_rows = $this->db->affectedRows();

            $this->assertEquals(1, $affected_rows, "replacing a row in an empty table returned $affected_rows unlike 1 as expected");
        }

        $result =& $this->db->query('SELECT user_name, user_password, subscribed, user_id, quota, weight, access_date, access_time, approved FROM users', $this->types);

        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }

        $this->verifyFetchedValues($result, 0, $data);

        $row = 4321;
        $fields['user_name']['value'] = $data['user_name'] = "user_$row";
        $fields['user_password']['value'] = $data['user_password'] = 'somepassword';
        $fields['subscribed']['value'] = $data['subscribed'] = $row % 2 ? true : false;
        $fields['quota']['value'] = $data['quota'] = strval($row/100);
        $fields['weight']['value'] = $data['weight'] = sqrt($row);
        $fields['access_date']['value'] = $data['access_date'] = MDB2_Date::mdbToday();
        $fields['access_time']['value'] = $data['access_time'] = MDB2_Date::mdbTime();
        $fields['approved']['value'] = $data['approved'] = MDB2_Date::mdbNow();

        $result = $this->db->replace('users', $fields);

        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Replace failed');
        }

        if ($support_affected_rows) {
            $affected_rows = $this->db->affectedRows();

            $this->assertEquals(2, $affected_rows, "replacing a row returned $affected_rows unlike 2 as expected");
        }

        $result =& $this->db->query('SELECT user_name, user_password, subscribed, user_id, quota, weight, access_date, access_time, approved FROM users', $this->types);

        if (MDB2::isError($result)) {
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
            return;
        }

        $data = array();
        $total_rows = 7;

        $prepared_query = $this->db->prepare('INSERT INTO users (user_name, user_password, subscribed, user_id, quota, weight, access_date, access_time, approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $this->types);

        for ($row = 0; $row < $total_rows; $row++) {
            $data[$row]['user_name'] = "user_$row";
            $data[$row]['user_password'] = 'somepassword';
            $data[$row]['subscribed'] = $row % 2 ? true : false;
            $data[$row]['user_id'] = $row;
            $data[$row]['quota'] = sprintf("%.2f",strval(1+($row+1)/100));
            $data[$row]['weight'] = sqrt($row);
            $data[$row]['access_date'] = MDB2_Date::mdbToday();
            $data[$row]['access_time'] = MDB2_Date::mdbTime();
            $data[$row]['approved'] = MDB2_Date::mdbNow();

            $this->insertTestValues($prepared_query, $data[$row]);

            $result = $this->db->execute($prepared_query);

            $affected_rows = $this->db->affectedRows();
            if (MDB2::isError($affected_rows)) {
                $this->assertTrue(false, 'Error in affectedRows(): '.$affected_rows->getMessage());
            } else {
                $this->assertEquals(1, $affected_rows, "Inserting the row $row returned $affected_rows affected row count instead of 1 as expected");
            }

            if (MDB2::isError($result)) {
                $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
            }
        }

        $this->db->freePrepared($prepared_query);

        $prepared_query = $this->db->prepare('UPDATE users SET user_password=? WHERE user_id < ?', array('text', 'integer'));

        for ($row = 0; $row < $total_rows; $row++) {
            $this->db->setParam($prepared_query, 1, "another_password_$row");
            $this->db->setParam($prepared_query, 2, $row);

            $result = $this->db->execute($prepared_query);

            if (MDB2::isError($result)) {
                $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
            }

            $affected_rows = $this->db->affectedRows();
            if (MDB2::isError($affected_rows)) {
                $this->assertTrue(false, 'Error in affectedRows(): '.$affected_rows->getMessage());
            } else {
                $this->assertEquals($row, $affected_rows, "Updating the $row rows returned $affected_rows affected row count");
            }
        }

        $this->db->freePrepared($prepared_query);

        $prepared_query = $this->db->prepare('DELETE FROM users WHERE user_id >= ?', array('integer'));

        for ($row = $total_rows; $total_rows; $total_rows = $row) {
            $this->db->setParam($prepared_query, 1, $row = intval($total_rows / 2));

            $result = $this->db->execute($prepared_query);

            if (MDB2::isError($result)) {
                $this->assertTrue(false, 'Error executing prepared query'.$result->getMessage());
            }

            $affected_rows = $this->db->affectedRows();

            if (MDB2::isError($affected_rows)) {
                $this->assertTrue(false, 'Error in affectedRows(): '.$affected_rows->getMessage());
            } else {
                $this->assertEquals(($total_rows - $row), $affected_rows, 'Deleting '.($total_rows - $row)." rows returned $affected_rows affected row count");
            }

        }

        $this->db->freePrepared($prepared_query);
    }

    /**
     * Testing transaction support
     */
    function testTransactions() {
        if (!$this->supported('transactions')) {
            return;
        }

        $this->db->autoCommit(0);

        $row = 0;
        $data = array();
        $data['user_name'] = "user_$row";
        $data['user_password'] = 'somepassword';
        $data['subscribed'] = $row % 2 ? true : false;
        $data['user_id'] = $row;
        $data['quota'] = strval($row/100);
        $data['weight'] = sqrt($row);
        $data['access_date'] = MDB2_Date::mdbToday();
        $data['access_time'] = MDB2_Date::mdbTime();
        $data['approved'] = MDB2_Date::mdbNow();

        $prepared_query = $this->db->prepare('INSERT INTO users (user_name, user_password, subscribed, user_id, quota, weight, access_date, access_time, approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', $this->types);

        $this->insertTestValues($prepared_query, $data);
        $result = $this->db->execute($prepared_query);
        $this->db->rollback();

        $result =& $this->db->query('SELECT * FROM users');
        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }

        $this->assertTrue(!$result->valid(), 'Transaction rollback did not revert the row that was inserted');
        $result->free();

        $this->insertTestValues($prepared_query, $data);
        $result = $this->db->execute($prepared_query);
        $this->db->commit();

        $result =& $this->db->query('SELECT * FROM users');
        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }

        $this->assertTrue($result->valid(), 'Transaction commit did not make permanent the row that was inserted');
        $result->free();

        $result =& $this->db->query('DELETE FROM users');
        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error deleting from users'.$result->getMessage());
            $this->db->rollback();
        }

        $autocommit = $this->db->autocommit(1);
        $this->assertTrue(!MDB2::isError($autocommit), 'Error autocommiting transactions');

        $this->db->freePrepared($prepared_query);

        $result =& $this->db->query('SELECT * FROM users');
        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error selecting from users'.$result->getMessage());
        }

        $this->assertTrue(!$result->valid(), 'Transaction end with implicit commit when re-enabling auto-commit did not make permanent the rows that were deleted');
        $result->free();
    }

    /**
     * Testing LOB storage
     */

    function testLOBStorage() {
        if (!$this->supported('LOBs')) {
            return;
        }

        $prepared_query = $this->db->prepare('INSERT INTO files (ID, document, picture) VALUES (1,?,?)', array('clob', 'blob'));

        $character_lob = array(
                            'data' => '',
                            'field' => 'document'

                              );
        for ($code = 32; $code <= 127; $code++) {
            $character_lob['data'] .= chr($code);
        }
        $binary_lob = array(
                            'data' => '',
                            'field' => 'picture'
                            );
        for ($code = 0; $code <= 255; $code++) {
            $binary_lob['data'] .= chr($code);
        }

        $this->db->setParam($prepared_query, 1, $character_lob);
        $this->db->setParam($prepared_query, 2, $binary_lob);

        $result = $this->db->execute($prepared_query);

        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error executing prepared query: '.$result->getUserInfo());
        }

        $this->db->freePrepared($prepared_query);

        $result =& $this->db->query('SELECT document, picture FROM files', array('clob', 'blob'));
        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error selecting from files'.$result->getMessage());
        }

        $this->assertTrue($result->valid(), 'The query result seem to have reached the end of result too soon.');

        $row = $result->fetchRow();
        $clob = $row[0];
        if (!MDB2::isError($clob)) {
            for ($value = ''; !$this->db->datatype->endOfLOB($clob);) {
                $this->assertTrue(($this->db->datatype->readLOB($clob, $data, 8192) >= 0), 'Could not read CLOB');
                $value .= $data;
            }
            $this->db->datatype->destroyLOB($clob);

            $this->assertEquals($character_lob['data'], $value, 'Retrieved character LOB value ("' . $value . '") is different from what was stored ("' . $character_lob['data'] . '")');
        } else {
            $this->assertTrue(false, 'Error retrieving CLOB result');
        }

        $blob = $row[1];
        if (!MDB2::isError($blob)) {
            for ($value = ''; !$this->db->datatype->endOfLOB($blob);) {
                $this->assertTrue(($this->db->datatype->readLOB($blob, $data, 8192) >= 0), 'Could not read BLOB');
                $value .= $data;
            }

            $this->db->datatype->destroyLOB($blob);

            $this->assertEquals($binary_lob['data'], $value, 'Retrieved binary LOB value ("'.$value.'") is different from what was stored ("'.$binary_lob['data'].'")');
        } else {
            $this->assertTrue(false, 'Error retrieving CLOB result');
        }
        $result->free();
    }

    /**
     * Test for lob storage from and to files
     */

    function testLOBFiles() {
        if (!$this->supported('LOBs')) {
            return;
        }

        $prepared_query = $this->db->prepare('INSERT INTO files (ID, document, picture) VALUES (1,?,?)', array('clob', 'blob'));

        $character_data_file = 'character_data';
        if (($file = fopen($character_data_file, 'w'))) {
            for ($character_data = '', $code = 32; $code <= 127; $code++) {
                $character_data .= chr($code);
            }
            $character_lob = array(
                                   'type' => 'inputfile',
                                   'field' => 'document',
                                   'file_name' => $character_data_file
                                   );
            $this->assertTrue((fwrite($file, $character_data, strlen($character_data)) == strlen($character_data)), 'Error creating clob file to read from');
            fclose($file);
        }

        $binary_data_file = 'binary_data';
        if (($file = fopen($binary_data_file, 'wb'))) {
            for ($binary_data = '', $code = 0; $code <= 255; $code++) {
                    $binary_data .= chr($code);
            }
            $binary_lob = array(
                                'type' => 'inputfile',
                                'field' => 'picture',
                                'file_name' => $binary_data_file
                                );
            $this->assertTrue((fwrite($file, $binary_data, strlen($binary_data)) == strlen($binary_data)), 'Error creating blob file to read from');
            fclose($file);
        }

        $this->db->setParam($prepared_query, 1, $character_lob);
        $this->db->setParam($prepared_query, 2, $binary_lob);

        $result = $this->db->execute($prepared_query);
        $this->assertTrue(!MDB2::isError($result), 'Error executing prepared query - inserting LOB from files');

        $this->db->freePrepared($prepared_query);

        $result =& $this->db->query('SELECT document, picture FROM files', array('clob', 'blob'));
        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error selecting from files'.$result->getMessage());
        }

        $this->assertTrue($result->valid(), 'The query result seem to have reached the end of result too soon.');

        $row = $result->fetchRow();
        $clob = $row[0];
        if (!MDB2::isError($clob)) {
            $clob = $this->db->datatype->setLOBFile($clob, $character_data_file);
            $this->assertTrue(($this->db->datatype->readLOB($clob, $data, 0) >= 0), 'Error reading CLOB ');
            $this->db->datatype->destroyLOB($clob);

            $this->assertTrue(($file = fopen($character_data_file, 'r')), "Error opening character data file: $character_data_file");
            $this->assertEquals('string', gettype($value = fread($file, filesize($character_data_file))), "Could not read from character LOB file: $character_data_file");
            fclose($file);

            $this->assertEquals($character_data, $value, "retrieved character LOB value (\"".$value."\") is different from what was stored (\"".$character_data."\")");
        } else {
            $this->assertTrue(false, 'Error creating character LOB in a file');
        }

        $blob = $row[1];
        if (!MDB2::isError($blob)) {
            $blob = $this->db->datatype->setLOBFile($blob, $binary_data_file);
            $this->assertTrue(($this->db->datatype->readLOB($blob, $data, 0) >= 0), 'Error reading BLOB ');
            $this->db->datatype->destroyLOB($blob);

            $this->assertTrue(($file = fopen($binary_data_file, 'rb')), "Error opening binary data file: $binary_data_file");
            $this->assertEquals('string', gettype($value = fread($file, filesize($binary_data_file))), "Could not read from binary LOB file: $binary_data_file");
            fclose($file);

            $this->assertEquals($binary_data, $value,
            "retrieved binary LOB value (\"".$value."\") is different from what was stored (\"".$binary_data."\")");
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
            return;
        }

        $prepared_query = $this->db->prepare('INSERT INTO files (ID, document,picture) VALUES (1,?,?)', array('clob', 'blob'));

        $this->db->setParam($prepared_query, 1, null);
        $this->db->setParam($prepared_query, 2, null);

        $result = $this->db->execute($prepared_query);
        $this->assertTrue(!MDB2::isError($result), 'Error executing prepared query - inserting NULL lobs');

        $this->db->freePrepared($prepared_query);

        $result =& $this->db->query('SELECT document, picture FROM files', array('clob', 'blob'));
        if (MDB2::isError($result)) {
            $this->assertTrue(false, 'Error selecting from files'.$result->getMessage());
        }

        $this->assertTrue($result->valid(), 'The query result seem to have reached the end of result too soon.');

        $this->assertTrue($result->resultIsNull(0, 'document'), 'A query result large object column document is not NULL unlike what was expected');
        $this->assertTrue($result->resultIsNull(0, 'picture'), 'A query result large object column picture is not NULL unlike what was expected');

        $result->free();
    }
}

?>