<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = 'YYY';
$state = 'stable';
$notes = <<<EOT
- fixed bug #9283: missing support for BINARY/VARBINARY data types (thanks to Tom Hendrikx)
- propagate errors in getTableFieldDefinition() in the Reverse module
- implemented getTriggerDefinition() in the Reverse module (mysql > 5.0.2) [experimental]
- implemented listTableTriggers() in the Manager module (mysql > 5.0.2)
- setCharset() now uses "SET NAMES" instead of "SET character_set_client"
- select the mysql database in listUsers() in the Manager module
- added error codes for MySQL 5 (patch by Adam Harvey)
- implemented guid() in the Function module [globally unique identifier]
- fixed bug #10033: beginTransaction() does not know server capabilities
- fixed bug #10057: createConstraint() returns an error when the definition is incomplete
- request #9451: you can set charset, collation, engine and comments in createSequence()
- added a new boolean parameter to getTableIndexDefinition() and
  getTableConstraintDefinition() in the Reverse module to disable the 'idxname_format'
  option and use the index name as provided
- phpdoc fixes

note:
- the multi_query test failes because this is not supported by ext/mysql

open todo items:
- use a trigger to emulate setting default now()
EOT;

$description = 'This is the MySQL MDB2 driver.';
$packagefile = './package_mysql.xml';

$options = array(
    'filelistgenerator' => 'cvs',
    'changelogoldtonew' => false,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/',
    'packagedirectory'  => './',
    'packagefile'       => $packagefile,
    'clearcontents'     => false,
    'include'           => array('*mysql*'),
    'ignore'            => array('package_mysql.php', '*mysqli*'),
);

$package = &PEAR_PackageFileManager2::importOptions($packagefile, $options);
$package->setPackageType('php');

$package->clearDeps();
$package->setPhpDep('4.3.0');
$package->setPearInstallerDep('1.4.0b1');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', 'XXX');
$package->addExtensionDep('required', 'mysql');

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