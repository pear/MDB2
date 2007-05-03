<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = '1.4.1';
$state = 'stable';
$notes = <<<EOT
- return length as "precision,scale" for NUMERIC and DECIMAL fields in mapNativeDatatype()
- in getTableIndexDefinition() and getTableConstraintDefinition() in the Reverse
  module, also return the field position in the index/constraint
- fixed bug #10895: setLimit() does not work properly when a subquery uses LIMIT

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
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.4.1');
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