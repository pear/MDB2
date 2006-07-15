<?php

require_once 'PEAR/PackageFileManager.php';

$version = 'XXX';
$notes = <<<EOT
- added setTransactionIsolation()

open todo items:
- fix pattern escaping using GLOB instead of LIKE or create an register own implementation of LIKE
- a number of the manager test cases fail because sqlite does not support adding
  primary keys to existing tables
- the alter table tests fails because this is unsupported in sqlite2
- the test replace test fails because sqlite reports an incorrect affected rows
  value when no existing data was replaced
- added debug() call at the end of a query/prepare/execute calling (Request #7933)
- added context array parameter to debug() and make use of it whereever sensible
- added optional method name parameter to raiseError() and use whereever possible
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(
    array(
        'packagefile'       => 'package_sqlite.xml',
        'package'           => 'MDB2_Driver_sqlite',
        'summary'           => 'sqlite MDB2 driver',
        'description'       => 'This is the SQLite MDB2 driver.',
        'version'           => $version,
        'state'             => 'stable',
        'license'           => 'BSD License',
        'filelistgenerator' => 'cvs',
        'include'           => array('*sqlite*'),
        'ignore'            => array('package_sqlite.php'),
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
$package->addDependency('sqlite', null, 'has', 'ext', false);

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
