<?php

require_once 'PEAR/PackageFileManager.php';

$version = '0.2.4';
$notes = <<<EOT
- added support for length in decimal columns
- removed ugly hack for quote parameter in quote() since it was insufficient
  (escaping also needs to be prevented)
- added support for out of order parameter binding in prepared queries
- tried to fix issues with lobs where the placeholder is not named like the field
- reset row_limit and row_offset after calling prepare() just like we do for query() and exec()
- rewrite queries for limit/offset (taken from ezc) instead of emulating
- cosmetic fix (removed "row_" prefix from "row_limit" and "row_offset")
- now using INTEGER(1) by default instead of CHAR(1) for the boolean datatype (BC BREAK!)

open todo items:
- fix issues with lobs where the placeholder is not named like the field
- fix crash in _makeAutoincrement()
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
$package->addDependency('MDB2', '2.0.0RC4', 'ge', 'pkg', false);
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
