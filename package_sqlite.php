<?php

require_once 'PEAR/PackageFileManager.php';

$version = '0.2.0';
$notes = <<<EOT
- do not fix case in listUsers()
- unified case fixing in the list*() methods
- support sorting order in createIndex()
- tweaked lob and text handling in mapNativeDatatype()
- fixed getTableFieldDefinition()
- use getConnection() to access connection property
- split index and constraint handling
- quote identifiers where possible inside the manager methods depending on
  the new 'quote_identifier' option (defaults to off)
- refactored get*Declaration() methods to use getTypeDeclaration()
- setting in_transaction to false on disconnect
- added new Function modules to handle difference in SQL functions
- force rollback() with open transactions on disconnect
- added alterTable() (only does table name change and column adding)
- escape floats to make sure they do not contain evil characters (bug #5608)
- split off manipulation queries into exec() method from the query() method *BC BREAK*
- if result_types is set to false in prepare() method the query will be handled as a DML statement *BC BREAK*
- use a proper default value if a field is set to not null in _getDeclaration*() (bug #5930)
- added ability to determine unsigned in mapNativeDatatype()
  (only really implemented in the mysql(i) drivers) (bug #6054)
- use lastInsertID() method in nextID()
- added getServerVersion()
- unified array structure in mapNativeDatatype() *BC BREAK*
- added 'mdbtype' to tableInfo() output that is generated from mapNativeDatatype()
- changed 'len' to 'length' in tableInfo() output *BC BREAK*

open todo items:
- a number of the manager test cases fail because sqlite does not support adding
  primary keys to existing tables
- the alter table test fails because this is unsupported in sqlite2
- the test replace test fails because sqlite reports an incorrect affected rows
  value when no existing data was replaced
- the testnow and testsubstring tests fail
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(
    array(
        'packagefile'       => 'package_sqlite.xml',
        'package'           => 'MDB2_Driver_sqlite',
        'summary'           => 'sqlite MDB2 driver',
        'description'       => 'This is the SQLite MDB2 driver.',
        'version'           => $version,
        'state'             => 'beta',
        'license'           => 'BSD License',
        'filelistgenerator' => 'cvs',
        'include'           => array('*sqlite*'),
        'ignore'            => array('package_sqlite.php'),
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

$package->addDependency('php', '4.3.0', 'ge', 'php', false);
$package->addDependency('PEAR', '1.0b1', 'ge', 'pkg', false);
$package->addDependency('MDB2', '2.0.0RC1', 'ge', 'pkg', false);
$package->addDependency('sqlite', null, 'has', 'ext', false);

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
