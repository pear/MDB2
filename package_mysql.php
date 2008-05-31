<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = 'XXX';
$state = 'beta';
$notes = <<<EOT
- fixed bug #13412: sometimes getTableConstraintDefinition() fails for FOREIGN KEYs
- fixed bug #13581: wrong query in beginTransaction() for certain MySQL versions
- request #13657: in setCharset(), use mysql_set_charset() if available [cwiedmann]
- fixed bug #13928: Invalid triggers created for 'ON UPDATE'

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
$package->setPhpDep('4.3.0');
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