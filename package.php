<?php

require_once 'PEAR/PackageFileManager.php';
require_once 'Console/Getopt.php';

$version = '2.0.0';
$notes = <<<EOT
        This is the first preview release of MDB2 2.0.
        It will not be available through the pear installer.

        MDB2 2.x breaks backwards compatibility in many ways in order to simplify
        the API for both users and drivers developers.

        Here follows a short list of the most important changes:
        - all code that is not necessary for basic operation is now separateed
          into separate modules which can be loaded with the loadModule() method
        - all datatype related methods have been moved to a dataype module with
          the notable exception of getValue() and the newly introduced getDeclaration()
        - added extended module for highlevel methods
        - all manager method are no longer available in the core class and or
          now only available in the manager module
        - all reverse engineering methods have been taken from the manager class
          and are now available through the reverse module
        - a new module has been added to allow the addition of methods with
          RDBMS specific functionality (like getting the last autoincrement ID)
        - LOB handling has been greatly simplified
        - several methods names have been shortend
        - the fetch.+() methods do not free the result set anymore
        - the Manager and the reverse_engineer_xml_schema have been moved into
          a Tools directory
        - all parameters are now lowercased with underscores as separators
        - all drivers now support all of the dsn options that PEAR DB supports
        - several methods have been removed because they offered redundant functionality
        - changed prepare API type is now passed to prepare and not to setParam*()
        - results are now wrapped inside objects and all methods which operate
          on resultsets have been moved into respecitive classes
        - there are two types of result object: buffered (default) and unbuffered
EOT;

$description =<<<EOT
    PEAR MDB2 is a merge of the PEAR DB and Metabase php database abstraction layers.

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
    * Full integration into the PEAR Framework
    * PHPDoc API documentation

    Currently supported RDBMS:
    MySQL
    PostGreSQL
    Oracle
    Frontbase
    Querysim
    Interbase/Firebird
    MSSQL
    SQLite
    Other soon to follow.
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(array(
    'package'           => 'MDB2',
    'summary'           => 'database abstraction layer',
    'description'       => $description,
    'version'           => $version,
    'state'             => 'devel',
    'license'           => 'BSD License',
    'filelistgenerator' => 'cvs',
    'ignore'            => array('package.php', 'package.xml'),
    'notes'             => $notes,
    'changelogoldtonew' => false,
    'baseinstalldir'    => '/',
    'packagedirectory'  => '',
    'dir_roles'         => array('docs' => 'doc',
                                 'examples' => 'doc',
                                 'tests' => 'test',
                                 'tests/templates' => 'test')
    ));

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}

$package->addMaintainer('lsmith', 'lead', 'Lukas Kahwe Smith', 'smith@backendmedia.com');
$package->addMaintainer('pgc', 'contributor', 'Paul Cooper', 'pgc@ucecom.com');
$package->addMaintainer('fmk', 'contributor', 'Frank M. Kromann', 'frank@kromann.info');
$package->addMaintainer('quipo', 'contributor', 'Lorenzo Alberton', 'l.alberton@quipo.it');

$package->addDependency('php', '4.1.0', 'ge', 'pkg', false);
$package->addDependency('PEAR', '1.0b1', 'ge', 'pkg', false);
$package->addDependency('XML_Parser', true, 'has', 'pkg', false);

if ($_SERVER['argv'][1] == 'commit') {
    $result = $package->writePackageFile();
} else {
    $result = $package->debugPackageFile();
}

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}
