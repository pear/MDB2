<?php

require_once 'PEAR/PackageFileManager.php';

$version = '0.1.2';
$notes = <<<EOT
Warning: this release features numerous BC breaks!

There have been considerable improvements to the datatype, manager and reverse
modules. Furthermore preliminary support for auto increment and primary keys
has been added. Please note that making a field auto increment implies a single
column primary key on this field.

- added support for auto increment and primary key in schema.
- alterTable now needs the full definition to work (use getTableFieldDefinition
 from Reverse module if you do not have a definition at hand) this eliminates the need
 of the declaration part in the alterTable array.
- fix PHP4.4 breakage
- fixed several incorrect raiseError() calls in manager module
- use !empty() instead of isset() in fetchRow to determine if result cols were bound or result types were set
- expect keys in type arrays the same way as they are passed for the values in execute() and bindParamArray()
- add s pattern modifier to preg_replace() call for parameter searches in prepare() (bug #5362)
- moved all private fetch mode fix methods into _fixResultArrayValues() for performance reasons
- renamed MDB2_PORTABILITY_LOWERCASE to MDB2_PORTABILITY_FIX_CASE and use 'field_case' option to determine if to upper- or lowercase (CASE_LOWER/CASE_UPPER)
- use array_key_exists() instead of isset() where possible
- changed structure of field add/remove/change in alterTable() to match MDB2_Schema
- createIndex(): index_sorting is not a feature in the supported array, but is supported by interbase
- return 0 for manipulation queries when disable_query is enabled
- tweaked field changing in alterTable()
- getTypeDeclaration() in the datatype module is now a public method
- fixed alterTable() signature
- fixed listTableFields()
- added listTableIndexes()

open todo items:
- code to be able to list primary contraints inside listTableIndexes()/dropIndex()
- handle autoincremement fields in alterTable() and dropTable()
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(
    array(
        'packagefile'       => 'package_ibase.xml',
        'package'           => 'MDB2_Driver_ibase',
        'summary'           => 'ibase MDB2 driver',
        'description'       => 'This is the Firebird/Interbase MDB2 driver.',
        'version'           => $version,
        'state'             => 'beta',
        'license'           => 'BSD License',
        'filelistgenerator' => 'cvs',
        'include'           => array('*ibase*'),
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

$package->addMaintainer('quipo', 'lead', 'Lorenzo Alberton', 'l.alberton@quipo.it');
$package->addMaintainer('lsmith', 'lead', 'Lukas Kahwe Smith', 'smith@pooteeweet.org');

$package->addDependency('php', '5.0.4', 'ge', 'php', false);
$package->addDependency('PEAR', '1.0b1', 'ge', 'pkg', false);
$package->addDependency('MDB2', '2.0.0beta6', 'ge', 'pkg', false);
$package->addDependency('interbase', null, 'has', 'ext', false);

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
