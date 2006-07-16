<?php

require_once 'PEAR/PackageFileManager.php';

$version = 'XXX';
$notes = <<<EOT
- added matchPattern() and patternEscapeString(), escapePattern()
- added ability to escape wildcard characters in escape() and quote()
- added setTransactionIsolation()
- fixed testcreateautoincrementtable for oci8
  There is still a problem when dropping the sequence, it gets inserted with a
  seemingly random string as a name
- load datatype module in tableInfo() (Bug #8116)
- added savepoint support to beginTransaction(), commit() and rollback()
- added debug() call at the end of a query/prepare/execute calling (Request #7933)
- added context array parameter to debug() and make use of it whereever sensible
- added optional method name parameter to raiseError() and use whereever possible

note: please use the latest ext/oci8 version from pecl.php.net/oci8
(binaries are available from snaps.php.net and pecl4win.php.net)

open todo items:
- fix issues with testcreateautoincrementtable (error on sequence dropping)
  maybe this: http://halisway.blogspot.com/2005/10/auto-incremental-primary-keys-in.html
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
$package->addDependency('MDB2', '2.1.0', 'ge', 'pkg', false);
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
