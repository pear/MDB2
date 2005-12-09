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
- added is_manip parameter to prepare() method which needs to be used for DML statements *BC BREAK*
- use lastInsertID() method in nextID()
- cleanup _checkSequence() method to not raise errors when no table was found

open todo items:
- added missing createIndex() and reverse module methods
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
$package->addDependency('MDB2', '2.0.0beta7', 'ge', 'pkg', false);
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
