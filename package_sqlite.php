<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = '1.5.0b4';
$state = 'beta';
$notes = <<<EOT
- fixed bug #16275: split() is deprecated in PHP 5.3

note:
open todo items:
- fix pattern escaping using GLOB instead of LIKE or create and register own implementation of LIKE
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
$package->setPhpDep('5.2.0');
$package->setPearInstallerDep('1.9.1');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.5.0b4');
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