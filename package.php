<?php

require_once 'PEAR/PackageFileManager.php';

$version = '2.0.0beta6';
$notes = <<<EOT
Warning: this release features numerous BC breaks to make the MDB2 API be as
similar as possible as the ext/pdo API! The next release is likely to also break
BC for the same reason. Check php.net/pdo for information on the pdo API.

- increased php dependency to 4.3.0 due to the usage of the streams API since beta5
- moved logic from MDB2::connect() to MDB2::factory(), the only difference is
  that MDB2::connect will immediatly try to connect to the database
- MDB2::singleton now uses MDB2::factory()
- added support for auto increment and primary key in schema. (mysql[i])
- alterTable (Manager) now needs the full definition to work (use getTableFieldDefinition
 from Reverse module if you do not have a definition at hand) this eliminates the need
 of the declaration part in the alterTable array.
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
