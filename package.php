<?php

require_once 'PEAR/PackageFileManager.php';

$version = '2.0.0beta4';
$notes = <<<EOT
Warning: this release features numerous BC breaks to make the MDB2 API be as
similar as possible as the ext/pdo API! The next release is likely to also break
BC for the same reason. Check php.net/pdo for information on the pdo API.

- fixed bugs in MDB2_Extended::buildManipSQL() introduced in latest tweaks (bug #3725)
- fixed index alteration in the Manager (bug #3710)
- mysqli has connection objects instead of resources
- fix mssql tableInfo() so flags are returned (bug #3691)
- fixed bug in handling of force_array when 2 or less columns are fetched in fetchAll()
- added map error message for sqlite multi-column unique constraints.
- added listUsers(), listViews(), listFunctions() to oracle manager
- added listFunctions() to pgsql manager
- updated listViews() in pgsql manager
- fixed bug in the Manager when upgrading a database that doesnt exist
- added __call() support for module handling
- mysql driver now uses mysqli implementations where feasible
- ensure that internal calls to query dont wrap the result
- for some reason mysqli didnt like SELECT LAST_INSERT_ID()
- fixed bug in table alteration when only an index was added
- updated pgsql API calls to 4.2.0 recommended names (bug #3904)
- moved logic to compareDefinitions from the Manager into the Datatype module
  to increase flexibility
- removed default_values property from the Manager
  (the user will now need to set the proper defaults himself)
- extended MDB2::isError() to be able to handle an array or codes
- added error handling into autoPrepare() and autoExecute()
- migrade all MDB2::isError calls that dont check for specific errors codes to PEAR::isError
- don't pass new_link to mysql_pconnect() (bug #3993)
- use MDB2::raiseError() instead of MDB2_Driver_Common::raiseError()
- do not require that not null fields have a default set in the Manager (bug #3997)
- do not disable result wrapping when doing internal calls to query() (bug #3997)
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

$package->addglobalreplacement('package-info', '@package_version@', 'version');

if (isset($_GET['make']) || (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'make')) {
    $result = $package->writePackageFile();
} else {
    $result = $package->debugPackageFile();
}

if (PEAR::isError($result)) {
    echo $result->getMessage();
    die();
}
