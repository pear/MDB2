<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = 'XXX';
$state = 'alpha';
$notes = <<<EOT
- fixed bug #11831: createTable() now supports tables with a multi-field PRIMARY KEY
  where one field is defined as AUTO_INCREMENT
- request #11204: support AUTO_INCREMENT for FLOAT data type and UNSIGNED option
  for FLOAT and DECIMAL data type [afz]
- fixed bug #11692: value of $db->supports('transactions') changes after query [afz]
- request #12731: added truncateTable() in the Manager module
- request #12732: added vacuum() in the Manager module for OPTIMIZE/VACUUM TABLE abstraction
- request #12800: added alterDatabase() in the Manager module [afz]
- fixed quoting in createDatabase() in the Manager module
- fixed bug #12924: correctly handle internal expected errors even with custom error handling
- added standaloneQuery() and databaseExists()
- request #13106: added unixtimestamp() in the Function module
- fixed regexp in listTableConstraints() in the Manager module to list FOREIGN KEY constraints
- fixed bug #13180: MySQL driver tells SAVEPOINT is supported for MyISAM tables
- fixed bug #13283: replace() doesn't respect quote_identifiers option
- request #13313: setCharSet() supports 'COLLATE' too
- fixed bug #13370: some capabilities depend on user options, so check them after
  a setOption() call
- when triggers are supported, two triggers are created to emulate ON UPDATE / ON DELETE actions
  for FOREIGN KEY constraints. Known limitation: since mysql doesn't support multiple triggers
  with the same action time and event for one table, if there are multiple table referencing
  the same table, only the first one will have the triggers created.

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