<?php

    // $Id$
    //
    // MDB2 test script.
    //

    // BC hack to define PATH_SEPARATOR for version of PHP prior 4.3
    if (!defined('PATH_SEPARATOR')) {
        if (defined('DIRECTORY_SEPARATOR') && DIRECTORY_SEPARATOR == "\\") {
            define('PATH_SEPARATOR', ';');
        } else {
            define('PATH_SEPARATOR', ':');
        }
    }
    ini_set('include_path', '../..'.PATH_SEPARATOR.ini_get('include_path'));

    // MDB2.php doesnt have to be included since manager.php does that
    // manager.php is only necessary for handling xml schema files
    require_once 'MDB2.php';
    // only including this to output result data
    require_once 'Var_Dump.php';

    PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'handle_pear_error');
    function handle_pear_error ($error_obj)
    {
        print '<pre><b>PEAR-Error</b><br />';
        echo $error_obj->getMessage().': '.$error_obj->getUserinfo();
        print '</pre>';
    }

    // just for kicks you can mess up this part to see some pear error handling
    $user = 'metapear';
    $pass = 'funky';
    $host = 'localhost';
    $db_name = 'metapear_test_db';
    if (isset($_GET['db_type'])) {
        $db_type = $_GET['db_type'];
    } else {
        $db_type = 'mysql';
    }
    echo($db_type.'<br>');

    // Data Source Name: This is the universal connection string
    $dsn['username'] = $user;
    $dsn['password'] = $pass;
    $dsn['hostspec'] = $host;
    $dsn['phptype'] = $db_type;
    // MDB2::connect will return a Pear DB object on success
    // or a Pear MDB2 error object on error
    // You can also set to true the second param
    // if you want a persistent connection:
    // $db = MDB2::connect($dsn, true);
    // you can alternatively build a dsn here
   //$dsn = "$db_type://$user:$pass@$host/$db_name";
    Var_Dump::display($dsn);
    $db =& MDB2::connect($dsn);
    // With MDB2::isError you can differentiate between an error or
    // a valid connection.
    if (MDB2::isError($db)) {
        die (__LINE__.$db->getMessage());
    }

    MDB2::loadFile('Tools/Manager');
    $manager =& new MDB2_Tools_Manager;
    $input_file = 'metapear_test_db.schema';
    // you can either pass a dsn string, a dsn array or an exisiting db connection
    $manager->connect($db);
    // lets create the database using 'metapear_test_db.schema'
    // if you have allready run this script you should have 'metapear_test_db.schema.before'
    // in that case MDB2 will just compare the two schemas and make any necessary modifications to the existing DB
    echo(Var_Dump::display($manager->updateDatabase($input_file, $input_file.'.before')).'<br>');
    echo('updating database from xml schema file<br>');

    echo('switching to database: '.$db_name.'<br>');
    $db->setDatabase($db_name);
    // happy query
    $query ='SELECT * FROM test';
    echo('query for the following examples:'.$query.'<br>');
    // run the query and get a result handler
    $result = $db->query($query);
    // lets just get row:0 column:0 and free the result
    $field = $result->fetch();
    $result->free();
    echo('<br>field:<br>'.$field.'<br>');
    // run the query and get a result handler
    $result = $db->query($query);
    // lets just get row:0 and free the result
    $array = $result->fetchRow();
    $result->free();
    echo('<br>row:<br>');
    echo(Var_Dump::display($array).'<br>');
    // run the query and get a result handler
    $result = $db->query($query);
    // lets just get row:0 and free the result
    $array = $result->fetchRow();
    $result->free();
    echo('<br>row from object:<br>');
    echo(Var_Dump::display($array).'<br>');
    // run the query and get a result handler
    $result = $db->query($query);
    // lets just get column:0 and free the result
    $array = $result->fetchCol(2);
    $result->free();
    echo('<br>get column #2 (counting from 0):<br>');
    echo(Var_Dump::display($array).'<br>');
    // run the query and get a result handler
    $result = $db->query($query);
    Var_Dump::display($db->loadModule('reverse'));
    echo('tableInfo:<br>');
    echo(Var_Dump::display($db->reverse->tableInfo($result)).'<br>');
    $types = array('integer', 'text', 'timestamp');
    $result->setResultTypes($types);
    $array = $result->fetchAll(MDB2_FETCHMODE_FLIPPED);
    $result->free();
    echo('<br>all with result set flipped:<br>');
    echo(Var_Dump::display($array).'<br>');
    // save some time with this function
    // lets just get all and free the result
    $array = $db->queryAll($query);
    echo('<br>all with just one call:<br>');
    echo(Var_Dump::display($array).'<br>');
    // run the query with the offset 1 and count 1 and get a result handler
    Var_Dump::display($db->loadModule('extended'));
    $result = $db->extended->limitQuery($query, null, 1, 1);
    // lets just get everything but with an associative array and free the result
    $array = $result->fetchAll(MDB2_FETCHMODE_ASSOC);
    echo('<br>associative array with offset 1 and count 1:<br>');
    echo(Var_Dump::display($array).'<br>');
    // lets create a sequence
    echo(Var_Dump::display($db->loadModule('manager')));
    echo('<br>create a new seq with start 3 name real_funky_id<br>');
    $err = $db->manager->createSequence('real_funky_id', 3);
    if (MDB2::isError($err)) {
            echo('<br>could not create sequence again<br>');
    }
    echo('<br>get the next id:<br>');
    $value = $db->nextId('real_funky_id');
    echo($value.'<br>');
    // lets try an prepare execute combo
    $alldata = array(
                     array(1, 'one', 'un'),
                     array(2, 'two', 'deux'),
                     array(3, 'three', 'trois'),
                     array(4, 'four', 'quatre')
    );
    $prepared_query = $db->prepare('INSERT INTO numbers VALUES(?,?,?)', array('integer', 'text', 'text'));
    foreach ($alldata as $row) {
        echo('running execute<br>');
        $db->executeParams($prepared_query, null, $row);
    }
    // lets try an prepare execute combo
    $alldata = array(
                     array(5, 'five', 'cinq'),
                     array(6, 'six', 'six'),
                     array(7, 'seven', 'sept'),
                     array(8, 'eight', 'huit')
    );
    $prepared_query = $db->prepare('INSERT INTO numbers VALUES(?,?,?)', array('integer', 'text', 'text'));
    echo('running executeMultiple<br>');
    echo(Var_Dump::display($db->executeMultiple($prepared_query, null, $alldata)).'<br>');
    $array = array(4);
    echo('<br>see getOne in action:<br>');
    echo(Var_Dump::display($db->extended->getOne('SELECT trans_en FROM numbers WHERE number = ?',null,$array,'text')).'<br>');
    $db->setFetchmode(MDB2_FETCHMODE_ASSOC);
    echo('<br>default fetchmode ist now MDB2_FETCHMODE_ASSOC<br>');
    echo('<br>see getRow in action:<br>');
    echo(Var_Dump::display($db->extended->getRow('SELECT * FROM numbers WHERE number = ?',array('integer','text','text'),$array, 'integer')));
    echo('default fetchmode ist now MDB2_FETCHMODE_ORDERED<br>');
    $db->setFetchmode(MDB2_FETCHMODE_ORDERED);
    echo('<br>see getCol in action:<br>');
    echo(Var_Dump::display($db->extended->getCol('SELECT * FROM numbers WHERE number != ?',null,$array,'text', 1)).'<br>');
    echo('<br>see getAll in action:<br>');
    echo(Var_Dump::display($db->extended->getAll('SELECT * FROM test WHERE test_id != ?',array('integer','text','text'), $array, 'integer')).'<br>');
    echo('<br>see getAssoc in action:<br>');
    echo(Var_Dump::display($db->extended->getAssoc('SELECT * FROM test WHERE test_id != ?',array('integer','text','text'), $array, 'integer', MDB2_FETCHMODE_ASSOC)).'<br>');
    echo('tableInfo on a string:<br>');
    echo(Var_Dump::display($db->reverse->tableInfo('numbers')).'<br>');
    echo('<br>just a simple update query:<br>');
    echo(Var_Dump::display($db->query('UPDATE numbers set trans_en ='.$db->quote(0, 'integer'))).'<br>');
    echo('<br>affected rows:<br>');
    echo($db->affectedRows().'<br>');
    // subselect test
    $sub_select = $db->subSelect('SELECT test_name from test WHERE test_name = '.$db->quote('gummihuhn', 'text'), 'text');
    echo(Var_Dump::display($sub_select).'<br>');
    $query_with_subselect = 'SELECT * FROM test WHERE test_name IN ('.$sub_select.')';
    // run the query and get a result handler
    echo($query_with_subselect.'<br>');
    $result = $db->query($query_with_subselect);
    $array = $result->fetchAll();
    $result->free();
    echo('<br>all with subselect:<br>');
    echo('<br>drop index (will fail if the index was never created):<br>');
    echo(Var_Dump::display($db->manager->dropIndex('test', 'test_id_index')).'<br>');
    $index_def = array(
        'fields' => array(
            'test_id' => array(
                'sorting' => 'ascending'
            )
        )
    );
    echo('<br>create index:<br>');
    echo(Var_Dump::display($db->manager->createIndex('test', 'test_id_index', $index_def)).'<br>');

    if ($db_type == 'mysql') {
        $manager->db->setOption('debug', true);
        $manager->db->setOption('log_line_break', '<br>');
        // ok now lets create a new xml schema file from the existing DB
        // we will not use the 'metapear_test_db.schema' for this
        // this feature is especially interesting for people that have an existing Db and want to move to MDB2's xml schema management
        // you can also try MDB2_MANAGER_DUMP_ALL and MDB2_MANAGER_DUMP_CONTENT
        echo(Var_Dump::display($manager->dumpDatabase(
            array(
                'output_mode' => 'file',
                'output' => $db_name.'2.schema'
            ),
            MDB2_MANAGER_DUMP_STRUCTURE
        )).'<br>');
        if ($manager->db->getOption('debug') === true) {
            echo($manager->debugOutput().'<br>');
        }
        // this is the database definition as an array
        echo(Var_Dump::display($manager->database_definition).'<br>');
    }

    echo('<br>just a simple delete query:<br>');
    echo(Var_Dump::display($db->query('DELETE FROM numbers')).'<br>');
    // You can disconnect from the database with:
    $db->disconnect()
?>
