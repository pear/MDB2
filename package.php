<?php

require_once 'PEAR/PackageFileManager.php';

$version = 'XXX';
$notes = <<<EOT
- flip positions property array in prepared statement objects to make it
  possible to optionally use the same named placeholder in multiple places
  inside a single prepared statement
- expanded prepare tests to cover more edge cases
- renamed valid_types property to valid_default_values in the Datatype module

open todo items:
- handle autoincrement fields in alterTable()
- add length handling to LOB reverse engineering
- expand charset support in schema management and result set handling (Request #4666)
- add EXPLAIN abstraction
- add cursor support along the lines of PDO (Request #3660 etc.)
- expand length/scale support for numeric types (Request #7170)
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
EOT;

$description =<<<EOT
PEAR MDB2 is a merge of the PEAR DB and Metabase php database abstraction layers.

It provides a common API for all supported RDBMS. The main difference to most
other DB abstraction packages is that MDB2 goes much further to ensure
portability. Among other things MDB2 features:
* An OO-style query API
* A DSN (data source name) or array format for specifying database servers
* Datatype abstraction and on demand datatype conversion
* Various optional fetch modes to fix portability issues
* Portable error codes
* Sequential and non sequential row fetching as well as bulk fetching
* Ability to make buffered and unbuffered queries
* Ordered array and associative array for the fetched rows
* Prepare/execute (bind) emulation
* Sequence/autoincrement emulation
* Replace emulation
* Limited sub select emulation
* Row limit support
* Transactions/savepoint support
* Large Object support
* Index/Unique Key/Primary Key support
* Pattern matching abstraction
* Module framework to load advanced functionality on demand
* Ability to read the information schema
* RDBMS management methods (creating, dropping, altering)
* Reverse engineering schemas from an existing DB
* SQL function call abstraction
* Full integration into the PEAR Framework
* PHPDoc API documentation
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(
    array(
        'package'           => 'MDB2',
        'summary'           => 'database abstraction layer',
        'description'       => $description,
        'version'           => $version,
        'state'             => 'stable',
        'license'           => 'BSD License',
        'filelistgenerator' => 'cvs',
        'ignore'            => array('package*.php', 'package*.xml', 'sqlite*', 'mssql*', 'oci8*', 'pgsql*', 'mysqli*', 'mysql*', 'fbsql*', 'querysim*', 'ibase*', 'peardb*'),
        'notes'             => $notes,
        'changelogoldtonew' => false,
        'simpleoutput'      => true,
        'baseinstalldir'    => '/',
        'packagedirectory'  => './',
        'dir_roles'         => array(
            'docs' => 'doc',
             'examples' => 'doc',
             'tests' => 'test',
        ),
    )
);

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}

$package->addMaintainer('lsmith', 'lead', 'Lukas Kahwe Smith', 'smith@pooteeweet.org');
$package->addMaintainer('pgc', 'contributor', 'Paul Cooper', 'pgc@ucecom.com');
$package->addMaintainer('quipo', 'developer', 'Lorenzo Alberton', 'l.alberton@quipo.it');
$package->addMaintainer('danielc', 'helper', 'Daniel Convissor', 'danielc@php.net');
$package->addMaintainer('davidc', 'helper', 'David Coallier', 'david@jaws.com.mx');

$package->addDependency('php', '4.3.2', 'ge', 'php', false);
$package->addDependency('PEAR', '1.3.6', 'ge', 'pkg', false);

$package->addglobalreplacement('package-info', '@package_version@', 'version');

if (array_key_exists('make', $_GET) || (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'make')) {
    $result = $package->writePackageFile();
} else {
    $result = $package->debugPackageFile();
}

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}
