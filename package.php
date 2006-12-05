<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version_release = 'XXX';
$version_api = $version_release;
$state = 'stable';
$notes = <<<EOT
- propagate errors in getTableFieldDefinition() in the Reverse module
- internally use MDB2::classExists() wrapper instead of directly calling class_exists()
- fixed bug #9502: query result misbehaves when the number of returned columns
  is greater than the number of passed types
- phpdoc fixes
- added tests

open todo items:
- handle autoincrement fields in alterTable()
- add length handling to LOB reverse engineering
- expand charset support in schema management and result set handling (Request #4666)
- add EXPLAIN abstraction
- add cursor support along the lines of PDO (Request #3660 etc.)
- add PDO based drivers, especially a driver to support SQLite 3 (Request #6907)
- add support to export/import in CSV format
- add more functions to the Function module (MD5(), IFNULL(), LENGTH() etc.)
- add support for database/table/row LOCKs
- add support for FOREIGN KEYs and CHECK (ENUM as possible mysql fallback) constraints
- generate STATUS file from test suite results and allow users to submit test results
- explore use of install groups (pear install MDB2#mysql)
- add support for full text index creation and querying
- add tests to check if the RDBMS specific handling with portability options
  disabled behaves as expected
- handle implicit commits (like for DDL) in any affected driver (mysql, sqlite..)
- add a getTableFieldsDefinitions() method to be used in tableInfo()
- drop ILIKE from matchPattern() and instead add a second parameter to
  handle case sensitivity with arbitrary operators
- add charset and collation support to field declaration in all drivers
- handle LOBs in buffered result sets (Request #8793)
EOT;

$description =<<<EOT
PEAR MDB2 is a merge of the PEAR DB and Metabase php database abstraction layers.

It provides a common API for all supported RDBMS. The main difference to most
other DB abstraction packages is that MDB2 goes much further to ensure
portability. MDB2 provides most of its many features optionally that
can be used to construct portable SQL statements:
* Object-Oriented API
* A DSN (data source name) or array format for specifying database servers
* Datatype abstraction and on demand datatype conversion
* Various optional fetch modes to fix portability issues
* Portable error codes
* Sequential and non sequential row fetching as well as bulk fetching
* Ability to make buffered and unbuffered queries
* Ordered array and associative array for the fetched rows
* Prepare/execute (bind) named and unnamed placeholder emulation
* Sequence/autoincrement emulation
* Replace emulation
* Limited sub select emulation
* Row limit emulation
* Transactions/savepoint support
* Large Object support
* Index/Unique Key/Primary Key support
* Pattern matching abstraction
* Module framework to load advanced functionality on demand
* Ability to read the information schema
* RDBMS management methods (creating, dropping, altering)
* Reverse engineering schemas from an existing database
* SQL function call abstraction
* Full integration into the PEAR Framework
* PHPDoc API documentation
EOT;

$packagefile = './package.xml';

$options = array(
    'filelistgenerator' => 'cvs',
    'changelogoldtonew' => false,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/',
    'packagedirectory'  => './',
    'packagefile'       => $packagefile,
    'clearcontents'     => false,
    'ignore'            => array('package*.php', 'package*.xml', 'sqlite*', 'mssql*', 'oci8*', 'pgsql*', 'mysqli*', 'mysql*', 'fbsql*', 'querysim*', 'ibase*', 'peardb*'),
    'dir_roles'         => array(
        'docs'      => 'doc',
         'examples' => 'doc',
         'tests'    => 'test',
    ),
);

$package = &PEAR_PackageFileManager2::importOptions($packagefile, $options);
$package->setPackageType('php');
$package->setExtends('MDB');

$package->clearDeps();
$package->setPhpDep('4.3.2');
$package->setPearInstallerDep('1.4.0b1');
$package->addPackageDepWithChannel('required', 'PEAR', 'pear.php.net', '1.3.6');

$package->addRelease();
$package->generateContents();
$package->setReleaseVersion($version_release);
$package->setAPIVersion($version_api);
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