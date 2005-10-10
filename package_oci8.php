<?php

require_once 'PEAR/PackageFileManager.php';

$version = '0.1.2';
$notes = <<<EOT
Warning: this release features numerous BC breaks!

There have been considerable improvements to the datatype, manager and reverse
modules. Furthermore preliminary support for auto increment and primary keys
has been added. Please note that making a field auto increment implies a single
column primary key on this field.

- alterTable now needs the full definition to work (use getTableFieldDefinition
 from Reverse module if you do not have a definition at hand) this eliminates the need
 of the declaration part in the alterTable array.
- fix PHP4.4 breakage
- fixed typo in unsigned integer declaration handling
- no need to close() the descriptor according to the docs (we do free() it though)
- use !empty() instead of isset() in fetchRow to determine if result cols were bound or result types were set
- expect keys in type arrays the same way as they are passed for the values in execute() and bindParamArray()
- add s pattern modifier to preg_replace() call for parameter searches in prepare() (bug #5362)
- moved all private fetch mode fix methods into _fixResultArrayValues() for performance reasons
- renamed MDB2_PORTABILITY_LOWERCASE to MDB2_PORTABILITY_FIX_CASE and use 'field_case' option to determine if to upper- or lowercase (CASE_LOWER/CASE_UPPER)
- use array_map() instead of array_flip(array_change_key_case(array_flip())) to fix case of array values
- use array_key_exists() instead of isset() where possible
- changed structure of field add/remove/change in alterTable() to match MDB2_Schema
- added createIndex()
- return 0 for manipulation queries when disable_query is enabled
- tweaked field changing in alterTable()
- getTypeDeclaration() in the datatype module is now a public method

open todo items:
- code to be able to list primary contraints inside listTableIndexes()/dropIndex()
- handle autoincremement fields in alterTable() and dropTable()
- using LOBs have a tendency to crash apache (even with the uptodate PECL oci8 ext)
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(
    array(
        'packagefile'       => 'package_oci8.xml',
        'package'           => 'MDB2_Driver_oci8',
        'summary'           => 'oci8 MDB2 driver',
        'description'       => 'This is the Oracle OCI8 MDB2 driver.',
        'version'           => $version,
        'state'             => 'alpha',
        'license'           => 'BSD License',
        'filelistgenerator' => 'cvs',
        'include'           => array('*oci8*'),
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
$package->addDependency('oci8', null, 'has', 'ext', false);

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
