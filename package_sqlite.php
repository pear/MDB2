<?php

require_once 'PEAR/PackageFileManager.php';

$version = '0.1.1';
$notes = <<<EOT
Warning: this release features numerous BC breaks!

There have been considerable improvements to the datatype, manager and reverse
modules. Furthermore preliminary support for auto increment and primary keys
has been added. Please note that making a field auto increment implies a single
column primary key on this field.

- increased php dependency to 4.3.0 due to the usage of the streams API since beta5
- fix PHP4.4 breakage
- moved getInsertID() into core as lastInsertID()
- use !empty() instead of isset() in fetchRow to determine if result cols were bound or result types were set
- moved all private fetch mode fix methods into _fixResultArrayValues() for performance reasons
- added new portability fetch mode MDB2_PORTABILITY_FIX_ASSOC_FIELD_NAMES (to remove database/table qualifiers from assoc indexes)
- renamed MDB2_PORTABILITY_LOWERCASE to MDB2_PORTABILITY_FIX_CASE and use 'field_case' option to determine if to upper- or lowercase (CASE_LOWER/CASE_UPPER)
- use array_key_exists() instead of isset() where possible
- changed structure of field add/remove/change in alterTable() to match MDB2_Schema
- return 0 for manipulation queries when disable_query is enabled
- tweaked getTableFieldDefinition() in reverse module (still broken however)
- fixed listTableIndexes()

open todo items:
- primary key support is missing from createIndex()
- getTableFieldDefinition() in reverse module is broken
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
$package->addDependency('MDB2', '2.0.0beta6', 'ge', 'pkg', false);
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
