<?php

require_once 'PEAR/PackageFileManager.php';

$version = '2.0.0beta3';
$notes = <<<EOT
Warning: this release features numerous BC breaks to make the MDB2 API be as
similar as possible as the ext/pdo API!

MDB2 static class:
- "xxx" out password on connect error in MDB2::connect()
- MDB2::isError now also optionally accepts and error code to check for
- added LOAD DATA (port from DB) and SET to MDB2::isManip()

All drivers:
- use __construct() (PHP4 BC hacks are provided)
- allow null values to be set for options
- ensure we are returning a reference in all relevant places

- allow errorInfo() to be called when no connection has been established yet
- use MDB2_ERROR_UNSUPPORTED instead of MDB2_ERROR_NOT_CAPABLE in common implementations
- readded MDB2_Error as the baseclass for all MDB2 error objects
- updated error mappings from DB

- added MDB2_Driver_Common::getDatabase();
- reworked dsn default handling
- added ability to "xxx" out password in getDSN()

- use _close() method in several places where they previously were not used
- removed redundant code in _close() that dealt with transaction closing already
  done in disconnect()
- if the dbsyntax is set in the dsn it will be set in the dbsyntax property
- only disconnect persistant connections if disconnect() has been explicitly
  called by the user
- instead of having a generic implemention of disconnect() we will rename
  _close() to disconnect() to overwrite the generic implementation
- added support for 'new_link' dsn option for all supported drivers (mysql, oci8, pgsql)

- transaction API moved over to PDO: removed autoCommit(), added beginTransaction()
  and refactored commit() (it doesn't start a new transaction automatically anymore)
- reworked handling of uncommited transaction for persistant connections when
  a given connection is no longer in use

- added 'disable_query' option to be able to disable the execution of all queries
 (this might be useful in conjuntion with a custom debug handler to be able to
 dump all queries into a file instead of executing them)
- removed affectedRows() method in favor of returning affectedRows() on query if relevant
- added generic implementation of query() and moved driver specific code into _doQuery()
- added _modifyQuery() to any driver that did not yet have it yet
- standaloneQuery() now also supports SELECT querys
- remove redundant call to commit() since setting autoCommit() already commits in MDB2::replace()
- refactored standaloneQuery(), query(), _doQuery(), _wrapResult(); the most important change are:
  result are only wrapped if it is explicitly requested
  standaloneQuery() now works just as query() does but with its own connection
- allowing limits of 0 in setLimit()

- explicitly specify colum name in sequence emulation queries
- added getBeforeId() and getAfterId()
- added new supported feature 'auto_increment'

- added default implementation for quoteCLOB() and quoteBLOB()
- reworked quote handling: moved all implementation details into the extension,
  made all quote methods private except for quote() itself, honor portability
  MDB2_PORTABILITY_EMPTY_TO_NULL in quote(), removed MDB2_TYPE_* constants
- reworked get*Declaration handling: moved all implementation details into the extension,
  made all quote methods private except for quote() itself
- placed convert methods after the portability conversions to ensure that the
  proper type is maintained after the conversion methods
- dont convert fetched null values in the Datatype module

- removed executeParams() and moved executeMultiple() from extended module

- updated tableInfo() code from DB

- made LIMIT handling more robust by taking some code from DB

All drivers result:
- performance tweak in fetchCol()
- added MDB2_FETCHMODE_OBJECT
- added MDB2_Driver_Result_Common::getRowCounter()
- added rownum handling to fetchRow()
- removed fetch() and resultIsNull()

All drivers prepared statements
- moved prepare/execute API towards PDO
- setParamsArray() can now handle non ordered arrays
- removed requirement for LOB inserts to pass the parameters as an array
- placeholders are now numbered starting from 0 (BC break in setParam() !)
- queries inside the prepared_queries property now start counting at 1 (performance tweak)
- refactored handling of filename LOB values (prefix with 'file://')
- removed _executePrepared(), drivers need to overwrite execute() for now on
- add support for oracle style named parameters and modified test suite accordingly

MySQL driver:
- improved handling of MDB2_PORTABILITY_LOWERCASE in all the reverse
  methods inside the mysql driver to work coherently
- fixed several issues in the listTablefields() method of manager drivers

MSSQL driver:
- added code in MDB2_Driver_mssql::connect() to better handle date values
  independant of ini and locale settings inside the server
- use comma, rather than colon, to delimit port in MDB2_driver_mssql::connect().
  Bug 2140. (danielc)
- unified mssql standalone query with sqlite, mysql and others (not tested on
  mssql yet, but since mssql automatically reuses connections per dsn the old
  way could gurantee anything different from happening)

PgSQL driver:
- use track_errors to capture error messages in MDB2_driver_pgsql::connect().
  Bug 2011. (danielc)
- add port to connect string when protocol is unix in MDB2_driver_pgsql::connect().
  Bug 1919. (danielc)
- accommodate changes made to PostgreSQL so "no such field" errors get properly
  indicated rather than being mislabeled as "no such table." (danielc)
- added "permission denied" to error regex in pgsql driver.
  Bug 2417. (stewart_linux-org-au)

OCI8 driver:
- fixed typo in MDB2_Driver_Manager_oci8::listTables() (fix for bug #2434)
- added emulate_database option (default true) to the Oracle driver that handles
  if the database_name should be used for connections of the username
- oci8 driver now uses native bind support for all types in prepare()/execute()

Interbase driver:
- completely revised ibase driver, now passing all tests under php5

Frontbase driver:
- fbsql: use correct error codes. Was using MySQL's codes by mistake.

MySQLi driver:
- added mysqli driver (passes all tests, but doesnt use native prepare yet)

DB wrapper
- fixed a large number of compatibility issues in the PEAR::DB wrapper

Iterator
- fixed several bugs and updated the interface to match the final php5 iterator API
- buffered result sets now implements seekable
- removed unnecessary returns
- throw pear error on rewind in unbuffered result set
- renamed size() to count() to match the upcoming Countable interface

Extended module:
- modified the signature of the auto*() methods to be compatible with DB (bug #3720)
- tweaked buildManipSQL() to not use loops (bug #3721)

MDB_Tools_Manager
- updated raiseError method in the Manager to be compatible with
  XML_Parser 1.1.x and return useful error message (fix bug #2055)
- major refactoring of MDB2_Manager resulting in several new methods being available
- fixed error in MDB2_Manager::_escapeSpecialCharacter() that would lead to
  incorrect handling of integer values (this needs to be explored in more detail)
- several typo fixes and minor logic errors (among others a fix for bug #2057)
- moved xml dumping in MDB2_Tools_Manager into separate Writer class
- fixed bugs in start value handling in create sequence (bug #3077)
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
* Altering of a DB from a changed xml schema
* Reverse engineering of xml schemas from an existing DB (currently only MySQL)
* Full integration into the PEAR Framework
* PHPDoc API documentation

Currently supported RDBMS:
MySQL (mysql and mysqli extension)
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
