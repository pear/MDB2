<?php

require_once 'PEAR/PackageFileManager.php';

$version = '1.0.2';
$notes = <<<EOT
- implemented listTableTriggers(), listTableViews() and listFunctions()
  in the Manager module
- implemented getTriggerDefinition() in the Reverse module
- explicitly set is_manip parameter to false for transaction debug calls
- pass parameter array as debug() all with scope "parameters" in every execute()
  call (bug #4119)
- typo fixes in phpdoc (thx Stoyan)
- added support for fixed and variable types for 'text' in declarations,
  as well as in reverse engineering (Request #1523)
- made _doQuery() return a reference
- added userinfo's to all raiseError calls that previously had none
- added 'prepared_statements' supported meta data setting
- typo fix ressource/resource in LOB array
- added missing unset() to _destroyLOB()
- fixed _destroyLOB() API to match other private LOB methods
- fixed phpdoc comments of all private LOB methods
- added missing supported parameter to prepare() signature
- fix default field value in getTableFieldDefinition() [Reverse module]
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
$package->addDependency('MDB2', '2.0.2', 'ge', 'pkg', false);
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
