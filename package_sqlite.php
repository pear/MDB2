<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = '1.5.0b1';
$state = 'alpha';
$notes = <<<EOT
- request #12731: added truncateTable() in the Manager module
- request #12732: added vacuum() in the Manager module for OPTIMIZE/VACUUM TABLE abstraction
- fixed bug #12924: correctly handle internal expected errors even with custom error handling
- added standaloneQuery() and databaseExists()
- request #13106: added unixtimestamp() in the Function module
- fixed bug #13201: better regexp in errorInfo()
- fixed bug #13283: replace() doesn't respect quote_identifiers option
- fixed bug #13303: PRIMARY keys are not always returned in listTableConstraints()
  and in getTableConstraintDefinition()

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
$package->setPhpDep('4.3.0');
$package->setPearInstallerDep('1.4.0b1');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.5.0b1');
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