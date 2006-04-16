<?php

require_once 'PEAR/PackageFileManager.php';

$version = '0.2.5';
$notes = <<<EOT
- handle null as resource when disable_query option is enabled in result object
- added missing methods in the reverse and datatype module
- aligned _modifyQuery() signature and phpdoc
- shorten autoincrement emulation related identifiers (bug #7109)
- added 'result_introspection' supported metadata support
- use NUMBER(1) for booleans (partial fix for bug #7170)
- use mapNativeDatatype() in tableInfo() where necessary
- properly quote table names in tableInfo() (related to bug #6573)
- use connected_server_info in getServerVersion() as a cache cache
- use parent::disconnect() in disconnect()
- separated result_buffering and prefetching by adding the new result_prefetching option
- support optional dsn item "port" in connect (bug #7216)
- added support for length in integer declarations
- some fixes regarding boolean reverse engineering
- protect against sql injection in the reverse and manager module
- improve handling for quoted identifiers in the reverse and manager module
- fixed queries in getTableConstraintDefinition()

open todo items:
- fix issues with lobs (where the placeholder is not named like the field)
- fix crash/issues in _makeAutoincrement()
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
$package->addMaintainer('justinpatrin', 'developer', 'Justin Patrin', 'justinpatrin@php.net');

$package->addDependency('php', '4.3.0', 'ge', 'php', false);
$package->addDependency('PEAR', '1.0b1', 'ge', 'pkg', false);
$package->addDependency('MDB2', '2.0.1', 'ge', 'pkg', false);
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
