<?php

require_once 'PEAR/PackageFileManager.php';

$version = 'XXX';
$notes = <<<EOT
- added "emulate_prepared" option to force prepared statement emulation
- tweaked handling of free() for prepared statements
- return error if a prepared statement is attempted to be freed twice
- use setCharset() in connect()/_doConnect()
- fixed number length handling when reverse engineering numeric types
- generalized quoteIdentifier() with a property
- cosmetic performance tweak in getTableFieldDefinition()
- switched most array_key_exists() calls to !empty() to improve readability and performance
- fixed a few edge cases and potential warnings
- added ability to rewrite queries for query(), exec() and prepare() using a debug handler callback
- pass limit and offset to the result object constructor in _execute() for read statements
- optmized limit queries without offset
- make sure no additional fields are added due to using limit/offset
- check if result/connection has not yet been freed/dicsonnected before
  attempting to free a result set(Bug #7790)
- fixed small typo with 'clob' reverse engineering

note: please use the latest ext/oci8 version from pecl.php.net/oci8
(binaries are available from snaps.php.net and pecl4win.php.net)

open todo items:
- fix issues with testcreateautoincrementtable (error on sequence creation)
- ensure that all primary/unique/foreign key handling is only in the contraint methods
- enable use of read() for LOBs to read a LOB in chunks
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(
    array(
        'packagefile'       => 'package_oci8.xml',
        'package'           => 'MDB2_Driver_oci8',
        'summary'           => 'oci8 MDB2 driver',
        'description'       => 'This is the Oracle OCI8 MDB2 driver.',
        'version'           => $version,
        'state'             => 'beta',
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
$package->addMaintainer('justinpatrin', 'developer', 'Justin Patrin', 'justinpatrin@php.net');

$package->addDependency('php', '4.3.0', 'ge', 'php', false);
$package->addDependency('PEAR', '1.0b1', 'ge', 'pkg', false);
$package->addDependency('MDB2', '2.0.3', 'ge', 'pkg', false);
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
