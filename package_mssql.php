<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = 'YYY';
$state = 'stable';
$notes = <<<EOT
- added ability to escape wildcard characters in escape() and quote()
- added setTransactionIsolation()
- added savepoint support to beginTransaction(), commit() and rollback()
- added debug() call at the end of a query/prepare/execute calling (Request #7933)
- added context array parameter to debug() and make use of it whereever sensible
- added optional method name parameter to raiseError() and use whereever possible
- added ability to escape wildcard characters in escape() and quote()
- added debug() call at the end of a query/prepare/execute calling (Request #7933)
- added 'nativetype' output to tableInfo() and getTableFieldDefinition()
- added 'mdb2type' output to getTableFieldDefinition()
- reworked tableInfo() to use a common implementation based on getTableFieldDefinition()
  when a table name is passed (Bug #8124)
- fixed incorrect regex in mapNativeDatatype() (Bug #8256) (thx ioz at ionosfera dot com)
- use old dsn when rolling back open transactions in disconnect()
- MSSQL requires making columns exlicitly NULLable (Bug #8359)
- do not list empty contraints and indexes
- added support for autoincrement via IDENTITY in getDeclaration()
- ALTER TABLE bug when adding more than 1 column (Bug #8373)
- fixed handling return values when disable_query is set in _doQuery() and _execute()
- added dropIndex() to the manager module
- increased MDB2 dependency too 2.2.2
- renamed valid_types property to valid_default_values in the Datatype module
- increased PHP dependency due to http://bugs.php.net/bug.php?id=31195
- using 'ADD COLUMN' syntax instead of just 'ADD' in alterTable()
- fixed bug #9024: typo in error checking
- fixed inheritance structure of convertResult()
- added support for new 'disable_iso_date' date DSN option (Request #8739)
- fix typos in error handling in a few places (bug #9024)
- do not skip id generation in nextId() when creating a sequence on demand
  becazse this prevents lastInsertID() from working

open todo items:
- explore fast limit/offset emulation (Request #4544)
EOT;

$description = 'This is the MS SQL Server MDB2 driver.';
$packagefile = './package_mssql.xml';

$options = array(
    'filelistgenerator' => 'cvs',
    'changelogoldtonew' => false,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/',
    'packagedirectory'  => './',
    'packagefile'       => $packagefile,
    'clearcontents'     => false,
    'include'           => array('*mssql*'),
    'ignore'            => array('package_mssql.php'),
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