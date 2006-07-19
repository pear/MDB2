<?php

require_once 'PEAR/PackageFileManager.php';

$version = 'XXX';
$notes = <<<EOT
- added MDB2_AUTOQUERY_SELECT (Request #7817)
- added nested transaction support (inspired by ADODB's smart transactions) but
  expanded to optionally use SAVEPOINTs *EXPERIMENTAL*
  beginNestedTransaction(), completeNestedTransaction(),
  failNestedTransaction(), getNestedTransactionError()
- inTransaction() will now return an integer with the nested transaction depth
  if a nested transaction has been started
- added setTransactionIsolation()
- added savepoint support to beginTransaction(), commit() and rollback()
- added Native base class for consistency
- added missing colnum parameter to queryOne() [used by getOne()]
- added new tests for get*() Extended module methods
- fixed missing db variable from getValidTypes()
- added testing of a prepared statement with no parameters
- added handling of empty result sets to result set verification in the test suite
- oci8 and ibase (and possibly other rdbms) do not like freeing the statement
  before reading the result set (Bug #8068):
  * moved statement freeing after reading the result set in get*() methods
  * by pass prepared statement API for queries without parameters in autoExecute()
  (this means you cannot use parameters with SELECT statements in autoExecute()
  on the above mentioned platforms)
- use data type callback in getValidTypes()
- fixed identifier quoting in buildManipSQL() for SELECT statements (thx Kailoran)
- phpdoc and cosmetic fixes in limitQuery()
- added matchPattern() and patternEscapeString(), escapePattern() *EXPERIMENTAL*
- added ability to escape wildcard characters in escape() and quote()
- added debug() call at the end of a query/prepare/execute calling (Request #7933)
- added context array parameter to debug() and make use of it whereever sensible
- added optional method name parameter to raiseError() and use whereever possible
- added a new option "debug_expanded_output" which needs to be set to true to
  get additional context information and to get "post" callback calls
- reworked tableInfo() to use a common implementation based on getTableFieldDefinition()
  when a table name is passed (Bug #8124)
- added 'nativetype' output to tableInfo() and getTableFieldDefinition()
- added 'mdb2type' output to getTableFieldDefinition()

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
- add support to generating "AS" keyword if required
- add support for database/table/row LOCKs
- add ActiveRecord implementation (probably as a separate package)
- add support for FOREIGN KEYs and CHECK (ENUM as possible mysql fallback) constraints
- extended to support for case insensitive matching via ILIKE/collate in matchPattern()
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
$package->addMaintainer('quipo', 'contributor', 'Lorenzo Alberton', 'l.alberton@quipo.it');
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
