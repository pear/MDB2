<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = 'YYY';
$state = 'alpha';
$notes = <<<EOT
- explicitly set is_manip parameter to false for transaction debug calls
- various minor tweaks to error messages, phpdoc and adding stub methods to the
  common driver
- typo fixes in phpdoc (thx Stoyan)
- added support for fixed and variable types for 'text' in declarations,
  as well as in reverse engineering (Request #1523)
- made _doQuery() return a reference
- added userinfo's to all raiseError calls that previously had none
- added 'prepared_statements' supported meta data setting
- marked primary key as supported
- use setCharset() in connect()/_doConnect()
- generalized quoteIdentifier() with a property
- switched most array_key_exists() calls to !empty() to improve readability and performance
- fixed a few edge cases and potential warnings
- added ability to rewrite queries for query(), exec() and prepare() using a debug handler callback
- check if result/connection has not yet been freed/dicsonnected before
  attempting to free a result set(Bug #7790)
- added ability to escape wildcard characters in escape() and quote()
- added debug() call at the end of a query/prepare/execute calling (Request #7933)
- added context array parameter to debug() and make use of it whereever sensible
- added optional method name parameter to raiseError() and use whereever possible
- added ability to escape wildcard characters in escape() and quote()
- added debug() call at the end of a query/prepare/execute calling (Request #7933)
- added 'nativetype' output to tableInfo()
- reworked tableInfo() to use a common implementation based on getTableFieldDefinition()
  when a table name is passed (Bug #8124)
- fixed incorrect regex in mapNativeDatatype() (Bug #8256) (thx ioz at ionosfera dot com)
- use old dsn when rolling back open transactions in disconnect()
- do not list empty contraints and indexes
- fixed handling return values when disable_query is set in _doQuery() and _execute()
- increased MDB2 dependency too 2.2.1
- do not skip id generation in nextId() when creating a sequence on demand
  becazse this prevents lastInsertID() from working

note:
- this driver needs a serious update as it's currently unmaintained/untested

open todo items:
- ensure that all primary/unique/foreign key handling is only in the contraint methods
EOT;

$description = 'This is the Frontbase SQL MDB2 driver.';
$packagefile = './package_fbsql.xml';

$options = array(
    'filelistgenerator' => 'cvs',
    'changelogoldtonew' => false,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/',
    'packagedirectory'  => './',
    'packagefile'       => $packagefile,
    'clearcontents'     => false,
    'include'           => array('*fbsql*'),
    'ignore'            => array('package_fbsql.php'),
);

$package = &PEAR_PackageFileManager2::importOptions($packagefile, $options);
$package->setPackageType('php');
$package->addRelease();
$package->generateContents();
$package->setReleaseVersion($version);
$package->setAPIVersion('XXX');
$package->setReleaseStability($state);
$package->setAPIStability($state);
$package->setNotes($notes);
$package->setDescription($description);
$package->addGlobalReplacement('package-info', '@package_version@', 'version');

if (isset($_GET['make']) || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
    $package->writePackageFile();
} else {
    $package->debugPackageFile();
}