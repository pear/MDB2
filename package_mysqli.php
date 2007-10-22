<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = 'XXX';
$state = 'stable';
$notes = <<<EOT
- fixed bug #10024: Added new option 'lob_allow_url_include' (default false) to
  [dis]allow inserting a LOB from an url (file, http, ...).
- fixed bug #10986: Using more random statement names (request #11625)
- fixed bug #11055: Using placeholders with := variable assignment fails [bekarau]
- initial support for FOREIGN KEY constraints in the Manager and Reverse modules
- request #11389: added many new MySQL 5.1 error codes in errorInfo()
- fixed bug #11428: propagate quote() errors with invalid data types
- fixed bug in _modifyQuery() when using SELECT FOR UPDATE or similar queries
- fixed bug #11590: _getServerCapabilities() has to be called once per connection
- fixed bug #11790: avoid array_diff() because it has a memory leak in PHP 5.1.x
- fixed some E_STRICT errors with PHP5
- fixed bug #12010: MDB2_PORTABILITY_RTRIM option was ignored
- fixed bug #12083: createTable() in the Manager module now returns MDB2_OK on success,
  as documented
- request #12213: usage of mysqli_options 'MYSQLI_SET_CHARSET_NAME' and mysqli_set_charset()
  when available (patch by Carsten Wiedmann)
- fixed bug #12269: tableInfo() in the Reverse module detect 'clob' data type
  as first option

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
$package->setPhpDep('5.0.0');
$package->setPearInstallerDep('1.4.0b1');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', 'XXX');
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