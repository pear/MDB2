<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = 'XXX';
$state = 'alpha';
$notes = <<<EOT
- fixed bug #12697: MDB2_Driver_mssql fails to load with the sybase_ct extension
- fixed bug #12599: BLOB quoting incorrect (thanks to Ferdy Hanssen)
- request #12800: added alterDatabase() in the Manager module [afz]
- fixed bug #12924: correctly handle internal expected errors even with custom error handling
- extended alterTable() support in the Manager module [afz]
- fixed bug #12391: fixed lastInsertID() (thanks to Marius Toma)
- added standaloneQuery() and databaseExists()

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