<?php

require_once 'PEAR/PackageFileManager.php';

$version = 'YYY';
$notes = <<<EOT
- flip positions property array in prepared statement objects to make it
  possible to optionally use the same named placeholder in multiple places
  inside a single prepared statement

open todo items:
- enable pg_execute() once issues with bytea column are resolved
- use pg_result_error_field() to handle localized error messages (Request #7059)
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
$package->addMaintainer('nrf', 'developer', 'Nathan Fredrickson', 'nathan@silverorange.com');

$package->addDependency('php', '4.3.0', 'ge', 'php', false);
$package->addDependency('PEAR', '1.0b1', 'ge', 'pkg', false);
$package->addDependency('MDB2', '2.2.1', 'ge', 'pkg', false);
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
