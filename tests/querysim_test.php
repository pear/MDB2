<?php

// uncomment this line if you want to run both MDB2 from a CVS checkout
#ini_set('include_path', '..'.PATH_SEPARATOR.ini_get('include_path'));

require_once 'MDB2.php';

$dsn = 'querysim';
$conn =& MDB2::factory($dsn);

if (PEAR::isError($conn)) {
    die ('Cannot connect: '.$conn->getMessage()."\n<br />\n<pre>".$conn->getUserInfo()."\n</pre>\n<br />");
}

test($conn, null, null);
test($conn, 2, 0);
test($conn, 2, 1);
test($conn, 2, 2);
test($conn, null, null);
$conn->disconnect();

$dsn = "querysim:///querysim.csv";
$conn = MDB2::factory($dsn, array('persistent'=> true, 'dataDelim'=>','));

if (PEAR::isError($conn)) {
    die ('Cannot connect: '.$conn->getMessage()."\n<br />\n<pre>".$conn->getUserInfo()."\n</pre>\n<br />");
}

test($conn, null, null);
test($conn, 2, 0);
test($conn, 2, 1);
test($conn, 2, 2);
test($conn, null, null);
$conn->disconnect();

function test(&$conn, $limit, $offset)
{
    $conn->setLimit($limit, $offset);

    $user = $conn->query('
        userID,firstName,lastName,userGroups
        100|Stan|Cox|33
        102|Hal|Helms|22
        103|Bert|Dawson|11
    ');

    if (PEAR::isError($user)) {
        die ('Database Error: '.$user->getMessage()."\n<br />\n<pre>".$user->getUserInfo()."\n</pre>\n<br />");
    }

    printf("Result contains %d rows and %d columns (using limit: %d and offset: %d)\n<br /><br />\n", $user->numRows(), $user->numCols(), $limit, $offset);

    //Note that you may return ordered or associative results, as well as specific single rows
    while (is_array($row = $user->fetchRow(MDB2_FETCHMODE_ASSOC))) {
        printf("%d, %s %s, %s\n<br />\n", $row['userid'], $row['firstname'], $row['lastname'], $row['usergroups']);
    }//end while

    $user->free();
}