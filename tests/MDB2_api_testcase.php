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

class MDB2_Api_TestCase extends PHPUnit_TestCase {
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

    function MDB2_Api_Test($name) {
        $this->PHPUnit_TestCase($name);
    }

    function setUp() {
        $this->dsn = $GLOBALS['dsn'];
        $this->options = $GLOBALS['options'];
        $this->database = $GLOBALS['database'];
        $this->db =& MDB2::connect($this->dsn, $this->options);
        if (MDB2::isError($this->db)) {
            $this->assertTrue(false, 'Could not connect to database in setUp - ' .$this->db->getMessage() . ' - ' .$this->db->getUserInfo());
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
    }

    function tearDown() {
        unset($this->dsn);
        if (!MDB2::isError($this->db)) {
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

    //test stuff in common.php
    function testConnect() {
        $db =& MDB2::connect($this->dsn, $this->options);
        if (MDB2::isError($db)) {
            $this->assertTrue(false, 'Connect failed bailing out - ' .$db->getMessage() . ' - ' .$db->getUserInfo());
        }
        if (MDB2::isError($this->db)) {
            exit;
        }
    }

    function testGetOption() {
        if (!$this->methodExists($this->db, 'getOption')) {
            return;
        }
        $option = $this->db->getOption('persistent');
        $this->assertEquals($option, $this->db->options['persistent']);
    }

    function testSetOption() {
        if (!$this->methodExists($this->db, 'setOption')) {
            return;
        }
        $option = $this->db->getOption('persistent');
        $this->db->setOption('persistent', !$option);
        $this->assertEquals(!$option, $this->db->getOption('persistent'));
        $this->db->setOption('persistent', $option);
    }

    function testLoadModule() {
        if (!$this->methodExists($this->db, 'loadModule')) {
            return;
        }
        $this->assertTrue(!MDB2::isError($this->db->loadModule('manager')));
    }

    // test of the driver
    // helper function so that we don't have to write out a query a million times
    function standardQuery() {
        $query = 'SELECT * FROM users';
        // run the query and get a result handler
        if (!MDB2::isError($this->db)) {
            return $this->db->query($query);
        }
        return false;
    }

    function testQuery() {
        if (!$this->methodExists($this->db, 'query')) {
            return;
        }
        $result = $this->standardQuery();

        $this->assertTrue(MDB2::isResult($result), 'query: $result returned is not a resource');
    }

    function testFetchRow() {
        $result = $this->standardQuery();
        if (!$this->methodExists($result, 'fetchRow')) {
            return;
        }
        $err = $result->fetchRow();
        $result->free();

        if (MDB2::isError($err)) {
            $this->assertTrue(false, 'Error testFetch: '.$err->getMessage().' - '.$err->getUserInfo());
        }
    }

    function testNumRows() {
        $result = $this->standardQuery();
        if (!$this->methodExists($result, 'numRows')) {
            return;
        }
        $numrows = $result->numRows();
        $this->assertTrue(!MDB2::isError($numrows) && is_int($numrows));
        $result->free();
    }

    function testNumCols() {
        $result = $this->standardQuery();
        if (!$this->methodExists($result, 'numCols')) {
            return;
        }
        $numcols = $result->numCols();
        $this->assertTrue(!MDB2::isError($numcols) && $numcols > 0);
        $result->free();
    }

    function testSingleton() {
        $mdb =& MDB2::singleton();
        $this->assertTrue(MDB2::isConnection($mdb));

        // should have a different database name set
        $mdb =& MDB2::singleton($this->dsn, $this->options);

        $this->assertTrue($mdb->db_index != $this->db->db_index);
    }
}

?>