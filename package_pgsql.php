<?php

require_once 'PEAR/PackageFileManager.php';

$version = '1.1.0';
$notes = <<<EOT
- added setCharset()
- use setCharset() in connect()/_doConnect()
- generalized quoteIdentifier() with a property
- switched most array_key_exists() calls to !empty() to improve readability and performance
- fixed a few edge cases and potential warnings
- return error when changing datatypes in alterTable() on pgsql version lower than 8.x (Bug #7731)
- added ability to rewrite queries for query(), exec() and prepare() using a debug handler callback
- fixed missing error handling in getTableFieldDefinition() (Bug #7791)
- pass limit and offset to the result object constructor in _execute() for read statements
- use pg_prepare() if available so that we do not need to define the types anymore (Request #7797)
- added code to use pg_execute() but disabled due to issues with bytea fields
- check if result/connection has not yet been freed/dicsonnected before
  attempting to free a result set(Bug #7790)
- revert change that would prefer 'clob' over 'text' for TEXT fields
  (this was breaking runtime instrospection)

open todo items:
- enable pg_execute() once issues with bytea column are resolved
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(
    array(
        'packagefile'       => 'package_pgsql.xml',
        'package'           => 'MDB2_Driver_pgsql',
        'summary'           => 'pgsql MDB2 driver',
        'description'       => 'This is the PostGreSQL MDB2 driver.',
        'version'           => $version,
        'state'             => 'stable',
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
$package->addDependency('MDB2', '2.1.0', 'ge', 'pkg', false);
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
