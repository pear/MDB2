<?php

require_once 'PEAR/PackageFileManager.php';

$version = '2.0.0beta3';
$notes = <<<EOT
Warning: this release features numerous BC breaks to make the MDB2 API be as
similar as possible as the ext/pdo API!
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
- removed requirement for LOB inserts to pass the parameters as an array
- placeholders are now numbered starting from 0 (BC break in setParam() !)
- queries inside the prepared_queries property now start counting at 1 (performance tweak)
- allow errorInfo() to be called when no connection has been established yet
- cleaned up constructor handling
- updated raiseError method in the Manager to be compatible with
  XML_Parser 1.1.x and return useful error message (fix bug #2055)
- improved handling of MDB2_PORTABILITY_LOWERCASE in all the reverse
  methods inside the mysql driver to work coherently
- fixed several issues in the listTablefields() method of manager drivers
- major refactoring of MDB2_Manager resulting in several new methods being available
- fixed error in MDB2_Manager::_escapeSpecialCharacter() that would lead to
  incorrect handling of integer values (this needs to be explored in more detail)
- several typo fixes and minor logic errors (among others a fix for bug #2057)
- added MDB2_Driver_Common::getDatabase();
- added default implementation for quoteCLOB() and quoteBLOB()
- moved prepare/execute API towards PDO (mysql, sqlite, pgsql and ibase tested only)
- use MDB2_ERROR_UNSUPPORTED instead of MDB2_ERROR_NOT_CAPABLE in common implementations
- reworked quote handling: moved all implementation details into the extension,
  made all quote methods private except for quote() itself, honor portability
  MDB2_PORTABILITY_EMPTY_TO_NULL in quote(), removed MDB2_TYPE_* constants
- reworked get*Declaration handling: moved all implementation details into the extension,
  made all quote methods private except for quote() itself
- ensure we are returning a reference in all relevant places
- reworked dsn default handling
- added ability to "xxx" out password in getDSN()
- "xxx" out password on connect error in MDB2::connect()
- removed affectedRows() method in favor of returning affectedRows() on query if relevant
- added generic implementation of query() and moved driver specific code into _doQuery()
- use _close() method in several places where they previously were not used
- removed redundant code in _close() that dealt with transaction closing already
  done in disconnect()
- added _modifyQuery() to any driver that did not yet have it yet
- added code in MDB2_Driver_mssql::connect() to better handle date values
  independant of ini and locale settings inside the server
- use comma, rather than colon, to delimit port in MDB2_driver_mssql::connect().
  Bug 2140. (danielc)
- use track_errors to capture error messages in MDB2_driver_pgsql::connect().
  Bug 2011. (danielc)
- add port to connect string when protocol is unix in MDB2_driver_pgsql::connect().
  Bug 1919. (danielc)
- accommodate changes made to PostgreSQL so "no such field" errors get properly
  indicated rather than being mislabeled as "no such table." (danielc)
- fixed typo in MDB2_Driver_Manager_oci8::listTables() (fix for bug #2434)
- added "permission denied" to error regex in pgsql driver.
  Bug 2417. (stewart_linux-org-au)
- added rownum handling to fetchRow()
- MDB2::isError now also optionally accepts and error code to check for
- readded MDB2_Error as the baseclass for all MDB2 error objects
- lazy load PEAR destructor emulation
- allow null values to be set for options
- added emulate_database option (default true) to the Oracle driver that handles
  if the database_name should be used for connections of the username
- removed fetch() and resultIsNull()
- refactored handling of filename LOB values (prefix with 'file://')
- standaloneQuery() now also supports SELECT querys
- added LOAD DATA (port from DB) and SET to MDB2::isManip()
- moved xml dumping in MDB2_Tools_Manager into separate Writer class
- completely revised ibase driver, now passing all tests under php5
- remove redundant call to commit() since setting autoCommit() already commits in MDB2::replace()
- refactored standaloneQuery(), query(), _doQuery(), _wrapResult(); the most important change are:
  result are only wrapped if it is explicitly requested
  standaloneQuery() now works just as query() does but with its own connection
- unified mssql standalone query with sqlite, mysql and others (not tested on
  mssql yet, but since mssql automatically reuses connections per dsn the old
  way could gurantee anything different from happening)
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
