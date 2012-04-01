<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = '1.5.0b4';
$state = 'beta';
$notes = <<<EOT
QA release
- Added SQLSRV_SQLTYPE_TIMESTAMP database attribute support [davidc] [genericbob]
- Fixed bug #17413: Infinite loop on connection error
- Bug #19314	typo in _checkSequence()
EOT;

$description = 'This is the SQL Server MDB2 driver.';
$packagefile = './package_sqlsrv.xml';

$options = array(
    'filelistgenerator' => 'svn',
    'changelogoldtonew' => false,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/',
    'packagedirectory'  => './',
    'packagefile'       => $packagefile,
    'clearcontents'     => false,
    'include'           => array('*sqlsrv*'),
    'ignore'            => array('package_sqlsrv.php'),
);

$package = PEAR_PackageFileManager2::importOptions($packagefile, $options);
$package->setPackageType('php');

$package->clearDeps();
$package->setPhpDep('5.2.0');
$package->setPearInstallerDep('1.9.1');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.5.0b4');
$package->addExtensionDep('required', 'sqlsrv');

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
