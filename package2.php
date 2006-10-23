<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = '2.3.0';
$notes = <<<EOT
- added charset and collation support to field declaration
- added SQL comments and quoted identifier handling inside prepared statement parser
- expanded length/scale support for numeric types (Request #7170)

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
- add a package2.xml and explore use of install groups (pear install MDB2#mysql)
- add support for full text index creation and querying
- add tests to check if the RDBMS specific handling with portability options
  disabled behaves as expected
- handle implicit commits (like for DDL) in any affected driver (mysql, sqlite..)
- add a getTableFieldsDefinitions() method to be used in tableInfo()
- drop ILIKE from matchPattern() and instead add a second parameter to
  handle case sensitivity with arbitrary operators
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

$p2 = new PEAR_PackageFileManager2();
$p2->setOptions(
    array(
        'filelistgenerator' => 'cvs',
        'ignore'            => array('package*.php', 'package*.xml', 'sqlite*', 'mssql*', 'oci8*', 'pgsql*', 'mysqli*', 'mysql*', 'fbsql*', 'querysim*', 'ibase*', 'peardb*'),
        'changelogoldtonew' => false,
        'packagedirectory'  => './',
        'baseinstalldir' => '/',
        'dir_roles'         => array(
            'docs' => 'doc',
             'examples' => 'doc',
             'tests' => 'test',
        ),
        'simpleoutput' => true,
    )
);
$p2->setPackageType('php');
$p2->addRelease();
$p2->generateContents();
$p2->setPackage('MDB2');
$p2->setChannel('pear.php.net');
$p2->setReleaseVersion($version);
$p2->setAPIVersion($version);
$p2->setReleaseStability('stable');
$p2->setAPIStability('stable');
$p2->setSummary('A database abstraction layer');
$p2->setDescription($description);
$p2->setNotes($notes);
$p2->setPhpDep('4.3.2');
$p2->setPearinstallerDep('1.3.6');
$p2->addMaintainer('lead', 'lsmith', 'Lukas Kahwe Smith', 'smith@pooteeweet.org');
$p2->addMaintainer('contributor', 'pgc', 'Paul Cooper', 'pgc@ucecom.com');
$p2->addMaintainer('developer', 'quipo', 'Lorenzo Alberton', 'l.alberton@quipo.it');
$p2->addMaintainer('helper', 'danielc', 'Daniel Convissor', 'danielc@php.net');
$p2->addMaintainer('helper', 'davidc', 'David Coallier', 'david@jaws.com.mx');
$p2->setLicense('BSD License', 'http://cvs.php.net/viewvc.cgi/pear/MDB2/LICENSE?content-type=text%2Fplain&view=co');
$p2->addGlobalReplacement('package-info', '@package_version@', 'version');
if (isset($_GET['make']) || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
    $p2->writePackageFile();
} else {
    $p2->debugPackageFile();
}
