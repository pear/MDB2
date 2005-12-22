<?php

require_once 'PEAR/PackageFileManager.php';

$version = '0.2.0';
$notes = <<<EOT
- do not fix case in listUsers()
- unified case fixing in the list*() methods
- tweaked text handling in mapNativeDatatype()
- use getConnection() to access connection property
- split index and constraint handling
- quote identifiers where possible inside the manager methods depending on
  the new 'quote_identifier' option (defaults to off)
- refactored get*Declaration() methods to use getTypeDeclaration()
- added support for table and column renaming as well as default and nullability
  changing in alterTable()
- setting in_transaction to false on disconnect
- added new Function modules to handle difference in SQL functions
- force rollback() with open transactions on disconnect
- escape floats to make sure they do not contain evil characters (bug #5608)
- split off manipulation queries into exec() method from the query() method *BC BREAK*
- if result_types is set to false in prepare() method the query will be handled as a DML statement *BC BREAK*
- use a proper default value if a field is set to not null in _getDeclaration*() (bug #5930)
- added ability to determine unsigned in mapNativeDatatype()
  (only really implemented in the mysql(i) drivers) (bug #6054)
- use MDB2_ERROR_NOT_FOUND in getTableConstraintDefinition() and getTableIndexDefinition() (bug #6055)
- added getServerVersion()
- unified array structure in mapNativeDatatype() *BC BREAK*
- added 'mdbtype' to tableInfo() output that is generated from mapNativeDatatype()
- changed 'len' to 'length' in tableInfo() output *BC BREAK*

open todo items:
- implement native prepared queries
- migrate away from OID's to bytea, since this is encourage since version 8
  and is also what PDO expects
- testgettablefielddefinition test case fails
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(
    array(
        'packagefile'       => 'package_pgsql.xml',
        'package'           => 'MDB2_Driver_pgsql',
        'summary'           => 'pgsql MDB2 driver',
        'description'       => 'This is the PostGreSQL MDB2 driver.',
        'version'           => $version,
        'state'             => 'beta',
        'license'           => 'BSD License',
        'filelistgenerator' => 'cvs',
        'include'           => array('*pgsql*'),
        'ignore'            => array('package_pgsql.php'),
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
$package->addDependency('pgsql', null, 'has', 'ext', false);

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
