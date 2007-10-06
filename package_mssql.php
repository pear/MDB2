<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = 'XXX';
$state = 'stable';
$notes = <<<EOT
- initial support for FOREIGN KEY and CHECK constraints in the Reverse and Manager modules
- fixed bug #9735, #11175, #11228: two instances fail to keep different databases selected
- request #11297: added support for "schema.table" notation in the Reverse module
- request #11445: added support for unicode datatypes (nchar, nvarchar, ntext)
- fixed bug #11479: wrong query in nextID()
- fixed bug #11790: avoid array_diff() because it has a memory leak in PHP 5.1.x
- request #11797: don't use mssql_field_name() in listTableFields() in the Manager module
  since it only returns the first 30 chars
- fixed bug #12010: MDB2_PORTABILITY_RTRIM option was ignored
- fixed bug #12083: createTable() in the Manager module now returns MDB2_OK on success,
  as documented

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

$package->clearDeps();
$package->setPhpDep('4.3.0');
$package->setPearInstallerDep('1.4.0b1');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', 'XXX');
$package->addExtensionDep('required', 'mssql');

$package->addRelease();
$package->generateContents();
$package->setReleaseVersion($version);
$package->setAPIVersion($version);
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