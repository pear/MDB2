<?php

require_once 'PEAR/PackageFileManager.php';

$version = '0.2.0';
$notes = <<<EOT
- added listTableIndexes()
- fixed _dropAutoincrement()
- use _dropAutoincrement() in dropTable()
- support portability option in listTableIndexes()
- do not fix case in listUsers()
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
- added getServerVersion()
- added 'mdbtype' to tableInfo() output
- changed 'len' to 'length' in tableInfo() output *BC BREAK*

open todo items:
- handle autoincremement fields in alterTable()
- there are still a number of missing methods in the reverse and datatype module
- there are still severe stability issues due to ext/oci8, especially on windows
- ensure that all primary/unique/foreign key handling is only in the contraint methods
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
        'ignore'            => array('package_oci8.php'),
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
