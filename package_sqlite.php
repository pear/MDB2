<?php

require_once 'PEAR/PackageFileManager.php';

$version = 'XXX';
$notes = <<<EOT
- explicitly set is_manip parameter to false for transaction debug calls
- added ability to hint that a constraint is a primary key in dropConstraint()
- typo fixes in phpdoc (thx Stoyan)
- added support for fixed and variable types for 'text' (Request #1523)
- made _doQuery() return a reference
- added userinfo's to all raiseError calls that previously had none
- added 'prepared_statements' supported meta data setting

open todo items:
- a number of the manager test cases fail because sqlite does not support adding
  primary keys to existing tables
- the alter table tests fails because this is unsupported in sqlite2
- the test replace test fails because sqlite reports an incorrect affected rows
  value when no existing data was replaced
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
$package->addDependency('MDB2', '2.0.1', 'ge', 'pkg', false);
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
