<?php

require_once 'PEAR/PackageFileManager.php';

$version = '2.0.0beta3';
$notes = <<<EOT
- affectedRows() now ensures that the last query was a manipulation query
- allowing limits of 0 in setLimit()
- setParamsArray() can now handle non ordered arrays
- performance tweak in fetchCol()
- fixed a large number of compatibility issues in the PEAR::DB wrapper
- added MDB2_FETCHMODE_OBJECT
- added MDB2_Driver_Result_Common::getRowCounter()
- explicitly specify colum name in sequence emulation queries
- added getBeforeId() and getAfterId()
- added new supported feature 'auto_increment'
- fixed Iterator module
- use __construct() and __destruct() (PHP4 BC hacks are provided)
- added 'disable_query' option to be able to disable the execution of all queries
 (this might be useful in conjuntion with a custom debug handler to be able to
 dump all queries into a file instead of executing them)
- added new 'clobfile' and 'blobfile' datatypes for prepare only. they serve to
  hint that a parameter is actually a filename
- removed requirement for LOB inserts to pass the parameters as an array
- placeholders are now numbered starting from 0 (BC break in setParam() !)
- queries inside the prepared_queries property now start counting at 1 (performance tweak)
- for PHP versions lower than 4 the transaction shutdown function is registered on load of MDB2.php (used to be a BC hack in the constructor of MDB_Driver_Common)
- allow errorInfo() to be called when no connection has been established yet
- cleaned up constructor handling
- fixed various typos
- updated raiseError method to be compatible with XML_Parser 1.1.x and return
  useful error message (fix bug #2055)
- improved handling of MDB2_PORTABILITY_LOWERCASE in all the reverse
  methods inside the mysql driver to work coherently
- fixed several issues in the listTablefields() method of manager drivers
- refactored MDB2_Manager::createDatabase() and related privat methods
- fixed error in MDB2_Manager::_escapeSpecialCharacter() that would lead to
  incorrect handling of integer values (this needs to be explored in more detail)
- several typo fixes and minor logic errors (among others a fix for bug #2057)
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
* RDBMS independent xml based schema definition management
* Altering of a DB from a changed xml schema
* Reverse engineering of xml schemas from an existing DB (currently only MySQL)
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
    'state'             => 'beta',
    'license'           => 'BSD License',
    'filelistgenerator' => 'cvs',
    'ignore'            => array('package.php', 'package.xml'),
    'notes'             => $notes,
    'changelogoldtonew' => false,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/',
    'packagedirectory'  => './',
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

$package->addDependency('php', '4.2.0', 'ge', 'php', false);
$package->addDependency('PEAR', '1.0b1', 'ge', 'pkg', false);
$package->addDependency('XML_Parser', true, 'has', 'pkg', false);

if (isset($_GET['make']) || (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'make')) {
    $result = $package->writePackageFile();
} else {
    $result = $package->debugPackageFile();
}

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}
