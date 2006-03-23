<?php

require_once 'PEAR/PackageFileManager.php';

$version = '1.0.1';
$notes = <<<EOT
- discard all constraints in MDB2_Driver_Reverse::getTableIndexDefinition(),
  leave them to MDB2_Driver_Reverse::getTableConstraintDefinition().
- return the constraint type in MDB2_Driver_Reverse::getTableConstraintDefinition().
- aligned _modifyQuery() signature and phpdoc
- added 'result_introspection' supported metadata support
- allow explicit definition of server_version
- some clean ups with determining if limit_queries needs to be emulated or not

open todo items:
- handle autoincremement fields in alterTable()
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(
    array(
        'packagefile'       => 'package_ibase.xml',
        'package'           => 'MDB2_Driver_ibase',
        'summary'           => 'ibase MDB2 driver',
        'description'       => 'This is the Firebird/Interbase MDB2 driver.',
        'version'           => $version,
        'state'             => 'stable',
        'license'           => 'BSD License',
        'filelistgenerator' => 'cvs',
        'include'           => array('*ibase*'),
        'ignore'            => array('package_ibase.php'),
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

$package->addMaintainer('quipo', 'lead', 'Lorenzo Alberton', 'l.alberton@quipo.it');
$package->addMaintainer('lsmith', 'lead', 'Lukas Kahwe Smith', 'smith@pooteeweet.org');

$package->addDependency('php', '5.0.4', 'ge', 'php', false);
$package->addDependency('PEAR', '1.0b1', 'ge', 'pkg', false);
$package->addDependency('MDB2', '2.0.0', 'ge', 'pkg', false);
$package->addDependency('interbase', null, 'has', 'ext', false);

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
