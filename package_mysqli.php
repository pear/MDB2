<?php

require_once 'PEAR/PackageFileManager.php';

$version = '1.0.2';
$notes = <<<EOT
- optimized listTables() and listViews()
- optimized show related queries
- merged in some features and fixes from the mysql driver
- explicitly set is_manip parameter to false for transaction debug calls
- pass parameter array as debug() all with scope "parameters" in every execute()
  call (bug #4119)
- silently change name of primary key contraints to PRIMARY
- added ability to hint that a constraint is a primary key in dropConstraint()
- typo fixes in phpdoc (thx Stoyan)
- added support for fixed and variable types for 'text' in declarations,
  as well as in reverse engineering (Request #1523)
- made _doQuery() return a reference
- added userinfo's to all raiseError calls that previously had none
- use native prepared queries for prepared SELECT statements
- only use native prepared queries of mysql 4.1 or higher
- added 'prepared_statements' supported meta data setting
- fixed issue with free() returning void
- added missing supported parameter to prepare() signature
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(
    array(
        'packagefile'       => 'package_mysqli.xml',
        'package'           => 'MDB2_Driver_mysqli',
        'summary'           => 'mysqli MDB2 driver',
        'description'       => 'This is the MySQLi MDB2 driver.',
        'version'           => $version,
        'state'             => 'stable',
        'license'           => 'BSD License',
        'filelistgenerator' => 'cvs',
        'include'           => array('*mysqli*'),
        'ignore'            => array('package_mysqli.php'),
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

$package->addDependency('php', '5.0.0', 'ge', 'php', false);
$package->addDependency('PEAR', '1.0b1', 'ge', 'pkg', false);
$package->addDependency('MDB2', '2.0.1', 'ge', 'pkg', false);
$package->addDependency('mysqli', null, 'has', 'ext', false);

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
