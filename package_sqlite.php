<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = '1.4.0';
$state = 'stable';
$notes = <<<EOT
- propagate errors in getTableFieldDefinition() in the Reverse module
- fixed bug #9895: Error mapping broken since 5.2.0
- implemented a fallback mechanism within getTableIndexDefinition() and
  getTableConstraintDefinition() in the Reverse module to ignore the 'idxname_format'
  option and use the index name as provided in case of failure before returning
  an error
- added a 'nativetype_map_callback' option to map native data declarations back to
  custom data types (thanks to Andrew Hill).
- fixed bug #9693: execute statement again in case of a SQLITE_SCHEMA error
- fixed bug #10027: PHP4 compatibility in createConstraint() and dropConstraint()
- implemented listViews() in the Manager module
- implemented listTableViews() in the Manager module
- implemented listTableTriggers() in the Manager module
- implemented getTriggerDefinition() in the Reverse module [experimental]
- fixed bug #9828: propagate errors in getConnection()
- phpdoc fixes

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
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.4.0');
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