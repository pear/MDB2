<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = 'YYY';
$state = 'stable';
$notes = <<<EOT
- fixed _modifyQuery() for manipulation queries
- fixed inheritance structure of convertResult()
- added support for new 'disable_iso_date' date DSN option (Request #8739)
- added support for getting the table name in tableInfo()
- added connection to pg_escape_*() calls
- fixed _modifyQuery() for manipulation queries
- enable detection of proper size of integers in mapNativeDatatype

open todo items:
- enable pg_execute() once issues with bytea column are resolved
- use pg_result_error_field() to handle localized error messages (Request #7059)
- add option to use unnamed prepared statements
  (see http://www.postgresql.org/docs/current/static/protocol-flow.html "Extended Query")
EOT;

$description = 'This is the PostgreSQL MDB2 driver.';
$packagefile = './package_pgsql.xml';

$options = array(
    'filelistgenerator' => 'cvs',
    'changelogoldtonew' => false,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/',
    'packagedirectory'  => './',
    'packagefile'       => $packagefile,
    'clearcontents'     => false,
    'include'           => array('*pgsql*'),
    'ignore'            => array('package_pgsql.php'),
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