<?php

require_once 'PEAR/PackageFileManager.php';

$version = '0.2.0';
$notes = <<<EOT
- fixed _dropAutoincrement()
- use _dropAutoincrement() in dropTable()
- fixed mapNativeDatatype()
- implemented getTableFieldDefinition() and getTableIndexDefinition() in the Reverse module
- implemented listUsers() in the Manager module
- unified case fixing in the list*() methods
- use custom implementation of getConnection() to access connection property
- split index and constraint handling
- return "0" as default value for integer NOT NULL fields with no default value
- quoteIdentifier() is just returning an uppercase string, since quoted
  identifiers in ibase do more harm than good
- refactored get*Declaration() methods to use getTypeDeclaration()
- set in_transaction to false on disconnect
- fixed type changing in alterTable()
- added new Function modules to handle difference in SQL functions
- autoincrement emulation works correctly
- pass the correct connection resource to ibase_blob_import()
- force rollback() with open transactions on disconnect
- escape floats to make sure they do not contain evil characters (bug #5608)
- in listSequences(), skip system generators
- split off manipulation queries into exec() method from the query() method *BC BREAK*
- added is_manip parameter to prepare() method which needs to be used for DML statements *BC BREAK*
- added ability to determine unsigned in mapNativeDatatype()
  (only really implemented in the mysql(i) drivers) (bug #6054)
- use MDB2_ERROR_NOT_FOUND in getTableConstraintDefinition() and getTableIndexDefinition() (bug #6055)

open todo items:
- handle autoincremement fields in alterTable()
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
        'ignore'            => array('package_ibase.php'),
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
