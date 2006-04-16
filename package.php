<?php

require_once 'PEAR/PackageFileManager.php';

$version = '2.0.1';
$notes = <<<EOT
- added new comprehensive tests for the Reverse module
- fixed testcases to pass for mysql[i] (needs pk's to be called "primary") ..
  sqlite is probably severely broken for these tests
- added ability to specify port number when using unix sockets in
  MDB2::parseDSN() (bug #5982)
- added test for multi_query option
- typo fix in get constraint test
- use ugly fopen() hack in fileExists()
  http://marc.theaimsgroup.com/?l=pear-dev&m=114148949106207&w=2
- allow "." and "$" in sequence name (bug #7081)
- aligned _modifyQuery() signature and phpdoc
- added inTransaction() to determine if a transaction is currently open
- added support for tabe options in createTable() (bug ##7079)
- make it possible to overwrite the error code-message map
- added sample sqlite in memory dsn to php5 example
- added 'result_introspection' supported metadata support
- added bindValue() method
- use MDB2_PREPARE_MANIP where we previously were using false
- fixed default values for date and timestamp
- if MDB2_PORTABILITY_EMPTY_TO_NULL is set change '' to ' ' in _getDeclaration()
- refactored class loading into MDB2::loadClass()
- properly quote CURRENT_* for temporal types (bug #6416)
- added connected_server_info to cache server info in getServerInfo()
- reset all connection related properties in disconnect()
- separated result_buffering and prefetching by adding the new result_prefetching option
- set error code in all raiseError() calls
- added support for length in reverse engineering of integer fields
- improve test suite documentation

open todo items:
- handle autoincremement fields in alterTable()
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
* Sequence emulation
* Replace emulation
* Limited sub select emulation
* Row limit support
* Transactions support
* Large Object support
* Index/Unique Key/Primary Key support
* Autoincrement emulation
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
$package->addMaintainer('quipo', 'contributor', 'Lorenzo Alberton', 'l.alberton@quipo.it');
$package->addMaintainer('danielc', 'helper', 'Daniel Convissor', 'danielc@php.net');
$package->addMaintainer('davidc', 'helper', 'David Coallier', 'david@jaws.com.mx');

$package->addDependency('php', '4.3.0', 'ge', 'php', false);
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
