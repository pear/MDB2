<?php

require_once 'PEAR/PackageFileManager.php';

$version = '0.2.3';
$notes = <<<EOT
- explicitly pass if the module is phptype specific in all loadModule calls (bug #6226)
- since we do not store the last statement in mdb2 we cannot check it
  (maybe this needs to be ported more completely from DB)
- fixed signature of executeStoredProc()
- _fixIndexName() now just attempts to remove possible formatting
- renamed _isSequenceName() to _fixSequenceName()
- _fixSequenceName() now just attempts to remove possible formatting, and only
  returns a boolean if no formatting was applied when the new "check" parameter is set to true

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
