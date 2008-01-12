<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = 'XXX';
$state = 'alpha';
$notes = <<<EOT
- fixed bug #12846: missing escape in getSequenceName() on PostgreSQL 8.2.4
  [thanks to Stephane Berthelot]
- request #12800: added alterDatabase() in the Manager module
  
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

$package->clearDeps();
$package->setPhpDep('4.3.0');
$package->setPearInstallerDep('1.4.0b1');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', 'XXX');
$package->addExtensionDep('required', 'pgsql');

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