<?php

require_once 'PEAR/PackageFileManager.php';

$version = '0.2.0';
$notes = <<<EOT
- do not fix case in listUsers()
- unified case fixing in the list*() methods
- fixed change and rename in alterTable()
- primary key must be called primary
- use getConnection() to access connection property
- split index and constraint handling
- quote identifiers where possible inside the manager methods
- refactored get*Declaration() methods to use getTypeDeclaration()
- setting in_transaction to false on disconnect
- hide constraints from indexes and vice versa in the list methods
- added new Function modules to handle difference in SQL functions
- force rollback() with open transactions on disconnect
- fixed table renaming
- escape floats to make sure they do not contain evil characters (bug #5608)
- support column length in create index (mysql only feature, but a nice touch,
  emulating it with substring is not feasible though)
- ensure that there is a connection in the escape() method
- split off manipulation queries into exec() method from the query() method *BC BREAK*
- added is_manip parameter to prepare() method which needs to be used for DML statements *BC BREAK*
- use a proper default value if a field is set to not null in _getDeclaration*() (bug #5930)
- added ability to determine unsigned in mapNativeDatatype()
  (only really implemented in the mysql(i) drivers) (bug #6054)
- use MDB2_ERROR_NOT_FOUND in getTableConstraintDefinition() and getTableIndexDefinition() (bug #6055)
- Sync lastInsertID with the mysqli implementation
- use lastInsertID() method in nextID()
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(
    array(
        'packagefile'       => 'package_mysql.xml',
        'package'           => 'MDB2_Driver_mysql',
        'summary'           => 'mysql MDB2 driver',
        'description'       => 'This is the MySQL MDB2 driver.',
        'version'           => $version,
        'state'             => 'beta',
        'license'           => 'BSD License',
        'filelistgenerator' => 'cvs',
        'ignore'            => array('*mysqli*', 'package_mysql.php'),
        'include'           => array('*mysql*'),
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
$package->addDependency('mysql', null, 'has', 'ext', false);

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
