<?php

require_once 'PEAR/PackageFileManager.php';

$version = '0.2.0';
$notes = <<<EOT
- unified case fixing in the list*() methods
- use getConnection() to access connection property
- split index and constraint handling
- quote identifiers where possible inside the manager methods depending on
  the new 'quote_identifier' option (defaults to off)
- refactored get*Declaration() methods to use getTypeDeclaration()
- setting in_transaction to false on disconnect
- added new Function modules to handle difference in SQL functions
- force rollback() with open transactions on disconnect
- escape floats to make sure they do not contain evil characters (bug #5608)
- split off manipulation queries into exec() method from the query() method *BC BREAK*
- only if result_types is set to false in prepare() method the query will be
  handled as a DML statement *BC BREAK*
- use lastInsertID() method in nextID()
- cleanup _checkSequence() method to not raise errors when no table was found
- added 'mdbtype' to tableInfo() output
- changed 'len' to 'length' in tableInfo() output *BC BREAK*
- explicitly pass if the module is phptype specific in all loadModule calls (bug #6226)
- fixed signature of quoteIdentifier() to make second param optional
- fixed signature of executeStoredProc()
- typo fixes in error handling of nextResult() and numRows() calls
- nextResult() returns false if there are no more result sets to read
- _fixIndexName() now just attempts to remove possible formatting
- renamed _isSequenceName() to _fixSequenceName()
- _fixSequenceName() now just attempts to remove possible formatting, and only
  returns a boolean if no formatting was applied when the new "check" parameter is set to true
- added support for length in decimal columns
- removed ugly hack for quote parameter in quote() since it was insufficient
  (escaping also needs to be prevented)
- handle null as resource when disable_query option is enabled in result object

open todo items:
- added missing index/contraint methods to the manager and reverse module methods
- ensure that all primary/unique/foreign key handling is only in the contraint methods
- fix alterTable()
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(
    array(
        'packagefile'       => 'package_mssql.xml',
        'package'           => 'MDB2_Driver_mssql',
        'summary'           => 'mssql MDB2 driver',
        'description'       => 'This is the Microsoft SQL Server MDB2 driver.',
        'version'           => $version,
        'state'             => 'alpha',
        'license'           => 'BSD License',
        'filelistgenerator' => 'cvs',
        'include'           => array('*mssql*'),
        'ignore'            => array('package_mssql.php'),
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

$package->addMaintainer('davidc', 'lead', 'David Coallier', 'david@jaws.com.mx');
$package->addMaintainer('lsmith', 'lead', 'Lukas Kahwe Smith', 'smith@pooteeweet.org');

$package->addDependency('php', '4.3.0', 'ge', 'php', false);
$package->addDependency('PEAR', '1.0b1', 'ge', 'pkg', false);
$package->addDependency('MDB2', '2.0.0', 'ge', 'pkg', false);
$package->addDependency('mssql', null, 'has', 'ext', false);

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
