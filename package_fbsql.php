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
- check if result/connection has not yet been freed/disconnected before
  attempting to free a result set(Bug #7790)
- added ability to escape wildcard characters in escape() and quote()
- added debug() call at the end of a query/prepare/execute calling (Request #7933)
- added context array parameter to debug() and make use of it wherever sensible
- added optional method name parameter to raiseError() and use wherever possible
- added ability to escape wildcard characters in escape() and quote()
- added debug() call at the end of a query/prepare/execute calling (Request #7933)
- added 'nativetype' output to tableInfo()
- reworked tableInfo() to use a common implementation based on getTableFieldDefinition()
  when a table name is passed (Bug #8124)
- fixed incorrect regex in mapNativeDatatype() (Bug #8256) (thx ioz at ionosfera dot com)
- use old dsn when rolling back open transactions in disconnect()
- do not list empty contraints and indexes
- fixed handling return values when disable_query is set in _doQuery() and _execute()
- increased MDB2 dependency to XXX
- do not skip id generation in nextId() when creating a sequence on demand
  because this prevents lastInsertID() from working
- migrated to package.xml version 2
- added _mapNativeDatatype() in the Datatype module
- fixed bug #11790: avoid array_diff() because it has a memory leak in PHP 5.1.x
- fixed bug #12083: createTable() in the Manager module now returns MDB2_OK on success,
  as documented
- fixed bug #12269: tableInfo() in the Reverse module detect 'clob' data type
  as first option
- fixed bug #12924: correctly handle internal expected errors even with custom error handling
- added standaloneQuery()

note:
- this driver needs a serious update as it's currently unmaintained/untested

open todo items:
- ensure that all primary/unique/foreign key handling is only in the constraint methods
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

$package->clearDeps();
$package->setPhpDep('5.0.4');
$package->setPearInstallerDep('1.4.0b1');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', 'XXX');
$package->addExtensionDep('required', 'fbsql');

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