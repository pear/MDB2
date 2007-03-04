<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = 'YYY';
$state = 'stable';
$notes = <<<EOT
- propagate errors in getTableFieldDefinition() in the Reverse module
- implemented getTriggerDefinition() in the Reverse module [experimental]
- implemented guid() in the Function module [globally unique identifier]
- added missing error codes (including the one reported in bug #9780)
- fixed bug #10003: integer length problem in getTypeDeclaration() in the Datatype
  module (thanks to priyadi at priyadi dot net)
- implemented a fallback mechanism within getTableIndexDefinition() and
  getTableConstraintDefinition() in the Reverse module to ignore the 'idxname_format'
  option and use the index name as provided in case of failure before returning
  an error
- fixed bug #4854: Oracle Easy Connect syntax only works with array DSN
- added a 'nativetype_map_callback' option to map native data declarations back to
  custom data types (thanks to Andrew Hill).
- fixed bug #10215: selected the wrong field in the query in listTableConstraints()
  in the Manager module
- phpdoc fixes

note:
- please use the latest ext/oci8 version from pecl.php.net/oci8
 (binaries are available from snaps.php.net and pecl4win.php.net)
- by default this driver emulates the database concept other RDBMS have by this
  using the "database" instead of "username" in the DSN as the username name.
  behaviour can be disabled by setting the "emulate_database" option to false.
- the multi_query test failes because this is not supported by ext/oci8
- the null LOB test failes because this is not supported by Oracle

open todo items:
- enable use of read() for LOBs to read a LOB in chunks
- buffer LOB's when doing buffered queries
EOT;

$description = 'This is the Oracle OCI8 MDB2 driver.';
$packagefile = './package_oci8.xml';

$options = array(
    'filelistgenerator' => 'cvs',
    'changelogoldtonew' => false,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/',
    'packagedirectory'  => './',
    'packagefile'       => $packagefile,
    'clearcontents'     => false,
    'include'           => array('*oci8*'),
    'ignore'            => array('package_oci8.php'),
);

$package = &PEAR_PackageFileManager2::importOptions($packagefile, $options);
$package->setPackageType('php');

$package->clearDeps();
$package->setPhpDep('4.3.0');
$package->setPearInstallerDep('1.4.0b1');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', 'XXX');
$package->addExtensionDep('required', 'oci8');

$package->addRelease();
$package->generateContents();
$package->setReleaseVersion($version);
$package->setAPIVersion('XXX');
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