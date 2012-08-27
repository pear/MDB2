<?php

/**
 * A manual test for the querysim driver
 *
 * @package MDB2
 * @category Database
 * @author Lukas Smith <lsmith@php.net>
 */

/**
 * Establish the test suite's environment.
 */
require_once __DIR__ . '/autoload.inc';

$dsn = 'querysim';
$conn = MDB2::factory($dsn);

if (MDB2::isError($conn)) {
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

if (MDB2::isError($conn)) {
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

    if (MDB2::isError($user)) {
        die ('Database Error: '.$user->getMessage()."\n<br />\n<pre>".$user->getUserInfo()."\n</pre>\n<br />");
    }

    printf("Result contains %d rows and %d columns (using limit: %d and offset: %d)\n<br /><br />\n", $user->numRows(), $user->numCols(), $limit, $offset);

    //Note that you may return ordered or associative results, as well as specific single rows
    while (is_array($row = $user->fetchRow(MDB2_FETCHMODE_ASSOC))) {
        printf("%d, %s %s, %s\n<br />\n", $row['userid'], $row['firstname'], $row['lastname'], $row['usergroups']);
    }//end while

    $user->free();
}
