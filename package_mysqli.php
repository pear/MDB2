<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = '1.5.0b4';
$state = 'beta';
$notes = <<<EOT
- fixed bug #17892: removed debug message [pdt256]
- fixed bug #17984: Error is not reported when mysqli_stmt_bind_param() fails [dennylin93]
- fixed bug #18057: Result of getDeclaration() can have invalid syntax [hschletz]
- request #18068: mapNativeDatatype() returns decimal places also for 'float' mdb2type

open todo items:
- use a trigger to emulate setting default now()
EOT;

$description = 'This is the MySQLi MDB2 driver.';
$packagefile = './package_mysqli.xml';

$options = array(
    'filelistgenerator' => 'cvs',
    'changelogoldtonew' => false,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/',
    'packagedirectory'  => './',
    'packagefile'       => $packagefile,
    'clearcontents'     => false,
    'include'           => array('*mysqli*'),
    'ignore'            => array('package_mysqli.php'),
);

$package = &PEAR_PackageFileManager2::importOptions($packagefile, $options);
$package->setPackageType('php');

$package->clearDeps();
$package->setPhpDep('5.2.0');
$package->setPearInstallerDep('1.9.1');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '1.5.0b4');
$package->addExtensionDep('required', 'mysqli');

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