<?php

/* Some utility functions for the test scripts */

/**
 * this is used (with array filter) to filter out the test*
 * methods from a PHPUnit testcase
 */
function grepForTest($var) {
    return preg_match('/\btest.*/', $var);
}

/**
 * given a class name it returns an array of test* methods
 *
 * @param $class text classname
 * @return array of methods beginning with test
 */
function getTests($class) {
    $methods = array_flip(array_change_key_case(array_flip(get_class_methods($class))));
    return array_filter($methods, 'grepForTest');
}

/**
 * Little helper function that outputs check for boxes with suitable names
 */
function testCheck($testcase, $testmethod) {
    return "<input type=\"checkbox\" name=\"testmethods[$testcase][$testmethod]\" value=\"1\">$testmethod <br>\n";
}
?>