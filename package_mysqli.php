<?php

require_once 'PEAR/PackageFileManager.php';

$version = 'XXX';
$notes = <<<EOT
- tweaked handling of free() for prepared statements
- return error if a prepared statement is attempted to be freed twice
- removed use of addslashes() for BLOB quoting
  (this might result in SQL injection vulnerability)
- added setCharset()
- use setCharset() in connect()/_doConnect()
- set transaction enabled engine as default
- generalized quoteIdentifier() with a property
- use general implementation of quoteIdentifier() (Bug #7738)
- fixed warning in replace() if type is not set (Bug #7740)
- cosmetic performance tweak in getTableFieldDefinition()
- switched most array_key_exists() calls to !empty() to improve readability and performance
- fixed a few edge cases and potential warnings
- added ability to rewrite queries for query(), exec() and prepare() using a debug handler callback
- pass limit and offset to the result object constructor in _execute() for read statements
- check if result/connection has not yet been freed/dicsonnected before
  attempting to free a result set(Bug #7790)
- fixed unbuffered query support
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
$package->addDependency('MDB2', '2.0.3', 'ge', 'pkg', false);
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
