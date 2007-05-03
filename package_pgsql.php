<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = '1.4.1';
$state = 'stable';
$notes = <<<EOT
- return length as "precision,scale" for NUMERIC and DECIMAL fields in mapNativeDatatype()
- more accurate field size information in getTableFieldDefinition() in the
  Reverse module
- in getTableIndexDefinition() and getTableConstraintDefinition() in the Reverse
  module, also return the field position in the index/constraint
- Request #9106: more accurate sequence name retrieval routine in getSequenceName()
- fixed bug #10895: setLimit() does not work properly when a subquery uses LIMIT

open todo items:
- enable pg_execute() once issues with bytea column are resolved
- use pg_result_error_field() to handle localized error messages (Request #7059)
- add option to use unnamed prepared statements
  (see http://www.postgresql.org/docs/current/static/protocol-flow.html "Extended Query")
EOT;

$description = 'This is the PostgreSQL MDB2 driver.';
$packagefile = './package_pgsql.xml';

$options = array(
    'filelistgenerator' => 'cvs',
    'changelogoldtonew' => false,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/',
    'packagedirectory'  => './',
    'packagefile'       => $packagefile,
    'clearcontents'     => false,
    'include'           => array('*pgsql*'),
    'ignore'            => array('package_pgsql.php'),
);

$package = &PEAR_PackageFileManager2::importOptions($packagefile, $options);
$package->setPackageType('php');

$package->clearDeps();
$package->setPhpDep('4.3.0');
$package->setPearInstallerDep('1.4.0b1');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.4.1');
$package->addExtensionDep('required', 'pgsql');

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