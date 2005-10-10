<?php

require_once 'PEAR/PackageFileManager.php';

$version = '2.0.0beta6';
$notes = <<<EOT
Warning: this release features numerous BC breaks!

There have been considerable improvements to the datatype, manager and reverse
modules. Furthermore preliminary support for auto increment and primary keys
has been added. Please note that making a field auto increment implies a single
column primary key on this field.

- increased php dependency to 4.3.0 due to the usage of the streams API since beta5
- moved logic from MDB2::connect() to MDB2::factory(), the only difference is
  that MDB2::connect will immediatly try to connect to the database
- MDB2::singleton now uses MDB2::factory()
- added support for auto increment and primary key in schema. (mysql[i])
- alterTable now needs the full definition to work (use getTableFieldDefinition
 from Reverse module if you do not have a definition at hand) this eliminates the need
 of the declaration part in the alterTable array.
- nicer test chooser. Added some js magic to [un]select all the tests in a group
- fixed typo in _getTextDeclaration()
- fix PHP4.4 breakage
- ensure that types and result_types property in the statement class is an array (bug #4695)
- added support for fetchmode in the iterator class and for any other result wrapper class (bug #4685)
- moved getInsertID() into core as lastInsertID()
- moved getBeforeID() and getAfterID() from core into the extended module
- added base class for all modules (which provides getDBInstance())
- added free() method to remove an instance from the global instance array
- removed schema manager related error codes from MDB2::errorMessage()
- dont set the include path in test suite (people can do that in test_setup.php)
- added missing default numRows() method
- added hack into stream_eof() to handle the BC break in 5.0.x
- removed uncessary duplicate quoting in quote() in the peardb wrapper (bug #5195)
- warning fix in BC hack of connect() in the peardb wrapper
- tweaked error message in setResultTypes()
- removed PDO compatibility code in bindParam and bindCol, now using 0-index numeric keys again
- expect keys in type arrays the same way as they are passed for the values in execute() and bindParamArray()
- add s pattern modifier to preg_replace() call for parameter searches in prepare() (bug #5362)
- moved all private fetch mode fix methods into _fixResultArrayValues() for performance reasons
- added new portability fetch mode MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES (to remove database/table qualifiers from assoc indexes)
- renamed MDB2_PORTABILITY_LOWERCASE to MDB2_PORTABILITY_FIX_CASE and use 'field_case' option to determine if to upper- or lowercase (CASE_LOWER/CASE_UPPER)
- ensure that fetchAll always returns an array() even if the result set was empty
- use array_key_exists() instead of isset() where possible
- changed structure of field add/remove/change in alterTable() to match MDB2_Schema
- added default values for supported property
- reworked supports() to return the given value and also return errors for non existant support feature
- reworked subSelect() to use the 'emulated' supports() return value
- removed implementation of createIndex() (now every driver needs to implement it themselves)
- sync fileExists with the LiveUser one, explode instead of split and is_readable instead of file_exists.
- tweaked compare method family to better deal with optional properties

open todo items:
- add test cases for the various module methods
- add getServerVersion()
EOT;

$description =<<<EOT
PEAR MDB2 is a merge of the PEAR DB and Metabase php database abstraction layers.

Note that the API will be adapted to better fit with the new php5 only PDO
before the first stable release.

It provides a common API for all support RDBMS. The main difference to most
other DB abstraction packages is that MDB2 goes much further to ensure
portability. Among other things MDB2 features:
* An OO-style query API
* A DSN (data source name) or array format for specifying database servers
* Datatype abstraction and on demand datatype conversion
* Portable error codes
* Sequential and non sequential row fetching as well as bulk fetching
* Ability to make buffered and unbuffered queries
* Ordered array and associative array for the fetched rows
* Prepare/execute (bind) emulation
* Sequence emulation
* Replace emulation
* Limited Subselect emulation
* Row limit support
* Transactions support
* Large Object support
* Index/Unique support
* Module Framework to load advanced functionality on demand
* Table information interface
* RDBMS management methods (creating, dropping, altering)
* RDBMS independent xml based schema definition management
* Reverse engineering schemas from an existing DB
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
        'state'             => 'beta',
        'license'           => 'BSD License',
        'filelistgenerator' => 'cvs',
        'ignore'            => array('package*.php', 'package*.xml', 'sqlite*', 'mssql*', 'oci8*', 'pgsql*', 'mysqli*', 'mysql*', 'fbsql*', 'querysim*', 'ibase*'),
        'notes'             => $notes,
        'changelogoldtonew' => false,
        'simpleoutput'      => true,
        'baseinstalldir'    => '/',
        'packagedirectory'  => './',
        'dir_roles'         => array(
            'docs' => 'doc',
             'examples' => 'doc',
             'tests' => 'test',
             'tests/templates' => 'test',
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
