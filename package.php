<?php

require_once 'PEAR/PackageFileManager.php';

$version = 'XXX';
$notes = <<<EOT
- phpdoc and folding mark fixes (thx Laurent)
- added getTriggerDefinition() in the Reverse module
- added listTableViews()
- explicitly set is_manip parameter to false for transaction debug calls
- various minor tweaks to error messages, phpdoc and adding stub methods to the
  common driver
- phpdoc improvements to helper classes
- added row seeking support to fetchOne()
- added ability to hint that a constraint is a primary key in dropConstraint()
- since we no longer complain about primary keys not being called primary we can
  remove the necessary hacks in the test suite
- increased the size of the LOBs in the test suite
- do not require Manager module for core API tests
- typo fixes in phpdoc (thx Stoyan)
- added support for fixed and variable types for 'text' (Request #1523)
- updated MAINTAINERS to current status
- readded STATUS document, still needs to be filled with content
- tweaked handling of error objects inside raiseError()
- made _doQuery() return a reference
- added userinfo's to all raiseError calls that previously had none
- added bindValueArray()
- use bindValueArray() instead of bindParamArray() in all internal calls
- removed the skeleton drivers, since its too much work to maintain them
  and there are plenty of sample drivers to look at
- fixed example due to API change in MDB2_Schema (Bug #7575)
- added 'prepared_statements' supported meta data setting

open todo items:
- handle fixed column types in "text" reverse engineering
EOT;

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
