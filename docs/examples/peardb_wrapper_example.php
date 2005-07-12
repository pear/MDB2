<?php
// $Id$
//
// MDB2 test script for the PEAR DB Wrapper.
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

    require_once 'MDB2.php';
    MDB2::loadFile('Wrapper/peardb');
    require_once 'Var_Dump.php';

    PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'handle_pear_error');
    function handle_pear_error ($error_obj)
    {
        print '<pre><b>PEAR-Error</b><br />';
        echo $error_obj->getMessage().': '.$error_obj->getUserinfo();
        print '</pre>';
    }

    // just for kicks you can mess up this part to see some pear error handling
    $user = 'root';
    $pass = '';
    //$pass = '';
    $host = 'localhost';
    $db_name = 'metapear_test_db';
    // Data Source Name: This is the universal connection string
    $dsn = "mysql://$user:$pass@$host/$db_name";
    // DB::connect will return a Pear DB object on success
    // or a Pear DB Error object on error
    // You can also set to true the second param
    // if you want a persistent connection:
    // $db = DB::connect($dsn, true);
    $db =& DB::connect($dsn);
    // With DB::isError you can differentiate between an error or
    // a valid connection.
    //echo(Var_Dump::display($db).'<br>');
    if (DB::isError($db)) {
        die (__LINE__.$db->getMessage());
    }

    // happy query
    $query ='SELECT * FROM test';
    echo('query for the following examples:'.$query.'<br>');
    echo('<br>field:<br>'.$db->getOne($query).'<br>');

    // run the query and get a result handler
    $result = $db->simpleQuery($query);
    echo('<br>tableInfo() ');
    Var_Dump::display($db->tableInfo($result));

    $result = $db->query($query);
    echo('<br>numCols() ');
    Var_Dump::display($result->numCols());
    $result->fetchInto($arr);
    echo('<br>fetchInto() ');
    Var_Dump::display($arr);
    echo('<br>free() ');
    Var_Dump::display($result->free());

    $result = $db->query($query);
    echo('<br>numRows() ');
    Var_Dump::display($result->numRows());
    echo('<br>fetchRow() ');
    Var_Dump::display($result->fetchRow());

    // lets create a sequence on demand
    echo('<br>get the next id using on demand:<br>');
    echo('<br>nextId:'.$db->nextId('real_funky_id_wrapper_2'));
    echo('<br>dropSequence:'.$db->dropSequence('real_funky_id_2'));

    echo('<br>get the next id:<br>');
    echo($db->nextId('real_funky_id_wrapper').'<br>');

    // lets try an prepare execute combo
    $alldata = array(  array(1, 'one', 'un'),
                       array(2, 'two', 'deux'),
                       array(3, 'three', 'trois'),
                       array(4, 'four', 'quatre'));
    $stmt = $db->prepare('INSERT INTO numbers VALUES(?,?,?)');
    foreach ($alldata as $row) {
        echo('running execute<br>');
        $db->execute($stmt, $row);
    }
    // lets try an prepare execute combo
    $alldata = array(  array(5, 'five', 'cinq'),
                       array(6, 'six', 'six'),
                       array(7, 'seven', 'sept'),
                       array(8, 'eight', 'huit'));
    $stmt = $db->prepare('INSERT INTO numbers VALUES(?,?,?)');
    $db->executeMultiple($stmt, $alldata);
    echo('running executeMultiple<br>');
    $array = array(4);
    echo('<br>see getOne in action:<br>');
    echo(Var_Dump::display($db->getOne('SELECT trans_en FROM numbers WHERE number = ?',$array)).'<br>');
    // You can disconnect from the database with:
    echo('<br>see getRow in action:<br>');
    echo(Var_Dump::display($db->getRow('SELECT * FROM numbers WHERE number = ?',$array)).'<br>');
    echo('<br>see getCol in action:<br>');
    echo(Var_Dump::display($db->getCol('SELECT * FROM numbers', 1)).'<br>');
    echo('<br>see getAll in action:<br>');
    echo(Var_Dump::display($db->getAll('SELECT * FROM test')).'<br>');
    echo('<br>see getAssoc in action:<br>');
    echo(Var_Dump::display($db->getAssoc('SELECT * FROM test', false, null, DB_FETCHMODE_ASSOC)).'<br>');
    echo('tableInfo on a string:<br>');
    echo(Var_Dump::display($db->tableInfo('numbers')).'<br>');
    echo('<br>just a simple delete query:<br>');
    echo(Var_Dump::display($db->query('UPDATE numbers set trans_en = 0')).'<br>');
    echo('<br>affected rows:<br>');
    echo($db->affectedRows().'<br>');
    echo('<br>just a simple delete query:<br>');
    echo(Var_Dump::display($db->query('DELETE FROM numbers')).'<br>');
    $db->disconnect();
?>
