<?php

require_once 'PEAR/PackageFileManager.php';

$version = '0.2.3';
$notes = <<<EOT
- explicitly pass if the module is phptype specific in all loadModule calls (bug #6226)
- properly handle PRIMARY keys in listTableConstraints()
- apply _isIndexName() on non primary keys in getTableConstraintDefinition()
- fixed signature of quoteIdentifier() to make second param optional
- fixed signature of executeStoredProc()
- typo fixes in error handling of nextResult() and numRows() calls
- _fixIndexName() now just attempts to remove possible formatting
- renamed _isSequenceName() to _fixSequenceName()
- _fixSequenceName() now just attempts to remove possible formatting, and only
  returns a boolean if no formatting was applied when the new "check" parameter is set to true
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(
    array(
        'packagefile'       => 'package_mysql.xml',
        'package'           => 'MDB2_Driver_mysql',
        'summary'           => 'mysql MDB2 driver',
        'description'       => 'This is the MySQL MDB2 driver.',
        'version'           => $version,
        'state'             => 'beta',
        'license'           => 'BSD License',
        'filelistgenerator' => 'cvs',
        'ignore'            => array('*mysqli*', 'package_mysql.php'),
        'include'           => array('*mysql*'),
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
$package->addDependency('mysql', null, 'has', 'ext', false);

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
