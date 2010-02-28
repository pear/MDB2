<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = 'XXX';
$state = 'beta';
$notes = <<<EOT
- fixed bug #15650: mysqli function used in setCharset()
- fixed bug #16003: incorrect check for error after mysql_store_result
- fixed bug #16147: first prepared statement is emulated when using factory with mysql
- fixed bug #16669: hostspec is ignored when protocol is unix
- fixed bug #17037: 'on update' not mentioned in tableInfo()
- There is no NEW row in on DELETE trigger (fix error in FK constraint triggers)
- return ON UPDATE|DELETE action in getTableConstraintDefinition()

note:
- the multi_query test failes because this is not supported by ext/mysql

open todo items:
- use a trigger to emulate setting default now()
EOT;

$description = 'This is the MySQL MDB2 driver.';
$packagefile = './package_mysql.xml';

$options = array(
    'filelistgenerator' => 'cvs',
    'changelogoldtonew' => false,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/',
    'packagedirectory'  => './',
    'packagefile'       => $packagefile,
    'clearcontents'     => false,
    'include'           => array('*mysql*'),
    'ignore'            => array('package_mysql.php', '*mysqli*'),
);

$package = &PEAR_PackageFileManager2::importOptions($packagefile, $options);
$package->setPackageType('php');

$package->clearDeps();
$package->setPhpDep('5.0.4');
$package->setPearInstallerDep('1.4.0b1');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', 'XXX');
$package->addExtensionDep('required', 'mysql');

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