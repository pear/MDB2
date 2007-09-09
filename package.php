<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version_release = 'XXX';
$version_api = $version_release;
$state = 'stable';
$notes = <<<EOT
- fixed bug #10024: Security fix for LOBs. Added an option to turn lob_allow_url_include off by default
- fixed bug #11179: prepared statements with named placeholders fail if extra values are provided
- added support for "schema.table" (or "owner.table") notation in the Reverse module (request #11297)
- initial support for FOREIGN KEY and CHECK constraints in the Reverse and Manager modules
- fixed bug #11428: propagate quote() errors with invalid data types
- added new test cases in the test suite
- added LENGTH() function in the Function module
- fixed bug #11906: quoteIdentifier fails for names with dots

open todo items:
- handle autoincrement fields in alterTable()
- add length handling to LOB reverse engineering
- add EXPLAIN abstraction
- add cursor support along the lines of PDO (Request #3660 etc.)
- add PDO based drivers, especially a driver to support SQLite 3 (Request #6907)
- add support to export/import in CSV format
- add more functions to the Function module (MD5(), IFNULL(), etc.)
- add support for database/table/row LOCKs
- add support for CHECK (ENUM as possible mysql fallback) constraints
- generate STATUS file from test suite results and allow users to submit test results
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

$package->addDependencyGroup('fbsql', 'Frontbase SQL driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'fbsql', 'MDB2_Driver_fbsql', 'pear.php.net', '0.3.0');
$package->addDependencyGroup('ibase', 'Interbase/Firebird driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'ibase', 'MDB2_Driver_ibase', 'pear.php.net', 'XXX');
$package->addDependencyGroup('mysql', 'MySQL driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'mysql', 'MDB2_Driver_mysql', 'pear.php.net', 'XXX');
$package->addDependencyGroup('mysqli', 'MySQLi driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'mysqli', 'MDB2_Driver_mysqli', 'pear.php.net', 'XXX');
$package->addDependencyGroup('mssql', 'MS SQL Server driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'mssql', 'MDB2_Driver_mssql', 'pear.php.net', 'XXX');
$package->addDependencyGroup('oci8', 'Oracle driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'oci8', 'MDB2_Driver_oci8', 'pear.php.net', 'XXX');
$package->addDependencyGroup('pgsql', 'PostgreSQL driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'pgsql', 'MDB2_Driver_pgsql', 'pear.php.net', 'XXX');
$package->addDependencyGroup('querysim', 'Querysim driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'querysim', 'MDB2_Driver_querysim', 'pear.php.net', '0.6.0');
$package->addDependencyGroup('sqlite', 'SQLite2 driver for MDB2');
$package->addGroupPackageDepWithChannel('subpackage', 'sqlite', 'MDB2_Driver_sqlite', 'pear.php.net', 'XXX');

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
