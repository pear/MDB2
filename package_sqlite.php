<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = 'XXX';
$state = 'stable';
$notes = <<<EOT
- initial support for FOREIGN KEY and CHECK constraints in the Reverse and Manager modules
  (on FK creation, some triggers are automatically created to enforce the FK constraint)
- in listTableConstraints() in the Reverse module, also search in table definition
  for PRIMARY KEYs and FOREIGN KEYs
- fixed bug #11428: propagate quote() errors with invalid data types
- fixed bug #11790: avoid array_diff() because it has a memory leak in PHP 5.1.x
- fixed bug #12083: createTable() in the Manager module now returns MDB2_OK on success,
  as documented
- fixed bug #12146: wrong regex in _getTableColumns($sql) in the Reverse module
- fixed bug #12269: tableInfo() in the Reverse module detect 'clob' data type
  as first option

note:
open todo items:
- fix pattern escaping using GLOB instead of LIKE or create an register own implementation of LIKE
EOT;

$description = 'This is the SQLite MDB2 driver.';
$packagefile = './package_sqlite.xml';

$options = array(
    'filelistgenerator' => 'cvs',
    'changelogoldtonew' => false,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/',
    'packagedirectory'  => './',
    'packagefile'       => $packagefile,
    'clearcontents'     => false,
    'include'           => array('*sqlite*'),
    'ignore'            => array('package_sqlite.php'),
);

$package = &PEAR_PackageFileManager2::importOptions($packagefile, $options);
$package->setPackageType('php');

$package->clearDeps();
$package->setPhpDep('4.3.0');
$package->setPearInstallerDep('1.4.0b1');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', 'XXX');
$package->addExtensionDep('required', 'sqlite');

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