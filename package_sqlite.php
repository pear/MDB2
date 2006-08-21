<?php

require_once 'PEAR/PackageFileManager.php';

$version = '1.2.1';
$notes = <<<EOT
- fixed issue in tableInfo() that originates in getTableFieldDefinition() which
  led to returning incorrect type values (Bug #8291)
- added support for NULL columns in getTableFieldDefinition()
- added full support for alterTable() via emulation
- added support for primary key creation and dropping
- do not list empty contraints and indexes
- use information_schema in listTableFields() instead of selecting from the
  table since that does not work if the table is empty
- fixed handling of contraints in _getTableColumns()
- fixed primary key handling in alterTable(), createConstraint() and dropConstraint()
- do not set a default if type is a LOB (Request #8074)
- fixed handling return values when disable_query is set in _doQuery() and _execute()
- increased MDB2 dependency too 2.2.1

note:
- this driver only supports SQLite version 2.x databases
- the replace test fails because sqlite reports an incorrect affected rows
  value when no existing data was replaced
- the multi_query test failes because this is not supported by ext/sqlite
- the savepoint test failes because this is not supported by sqlite
- the case sensitive search test fails because this is not supported by SQLite
- the pattern escaping test fails because this is not supported by SQLite

open todo items:
- fix pattern escaping using GLOB instead of LIKE or create an register own implementation of LIKE
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(
    array(
        'packagefile'       => 'package_sqlite.xml',
        'package'           => 'MDB2_Driver_sqlite',
        'summary'           => 'sqlite MDB2 driver',
        'description'       => 'This is the SQLite MDB2 driver.',
        'version'           => $version,
        'state'             => 'stable',
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
$package->addDependency('MDB2', '2.2.1', 'ge', 'pkg', false);
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
