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
- fixed bug #9748: Table name is not quoted in Extended.php buildManipSQL()
- fixed bug #9800: when the php extension for the driver fails to load, the
  error is not propagated correctly and the script dies
- propagate errors in the Datatype module
- implemented guid() in the Function module [globally unique identifier]
  (thanks to mario dot adam at schaeffler dot com)
- fixed bug #4854: Oracle Easy Connect syntax only works with array DSN
- fixed bug #10105: inTransaction() was returning an incorrect value after a call
  to disconnect() or __destruct()
- implemented a fallback mechanism within getTableIndexDefinition() and
  getTableConstraintDefinition() in the Reverse module to ignore the 'idxname_format'
  option and use the index name as provided in case of failure before returning
  an error
- added a 'nativetype_map_callback' option to map native data declarations back to
  custom data types (thanks to Andrew Hill).
- fixed bug #10234 and bug #10233: MDB2_Driver_Datatype_Common::mapNativeDatatype()
  must ensure that it returns the correct length value, or null
- phpdoc fixes
- fixed tests to be compatible with PHP4
- added new tests, including some MDB2 internals tests by Andrew Hill and Monique Szpak

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

$package->addDependencyGroup('fbsql-driver', 'Frontbase SQL driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'fbsql-driver', 'MDB2_Driver_fbsql', 'pear.php.net', '0.3.0');
$package->addDependencyGroup('ibase-driver', 'Interbase/Firebird driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'ibase-driver', 'MDB2_Driver_ibase', 'pear.php.net', '1.4.0');
$package->addDependencyGroup('mysql-driver', 'MySQL driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'mysql-driver', 'MDB2_Driver_mysql', 'pear.php.net', '1.4.0');
$package->addDependencyGroup('mysqli-driver', 'MySQLi driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'mysqli-driver', 'MDB2_Driver_mysqli', 'pear.php.net', '1.4.0');
$package->addDependencyGroup('mssql-driver', 'MS SQL Server driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'oci8-driver', 'MDB2_Driver_oci8', 'pear.php.net', '1.4.0');
$package->addDependencyGroup('oci8-driver', 'Oracle driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'mssql-driver', 'MDB2_Driver_mssql', 'pear.php.net', '1.4.0');
$package->addDependencyGroup('pgsql-driver', 'PostgreSQL driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'pgsql-driver', 'MDB2_Driver_pgsql', 'pear.php.net', '1.4.0');
$package->addDependencyGroup('querysim-driver', 'Querysim driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'querysim-driver', 'MDB2_Driver_querysim', 'pear.php.net', '0.6.0');
$package->addDependencyGroup('sqlite-driver', 'SQLite2 driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'sqlite-driver', 'MDB2_Driver_sqlite', 'pear.php.net', '1.4.0');

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