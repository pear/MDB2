<?php

require_once 'PEAR/PackageFileManager.php';

$version = '1.0.1';
$notes = <<<EOT
- added listTableViews() (request #6773)
- explicitly set is_manip parameter to false for transaction debug calls
- pass parameter array as debug() all with scope "parameters" in every execute()
  call (bug #4119)
- typo fixes in phpdoc (thx Stoyan)
- added support for fixed and variable types for 'text' in declarations,
  as well as in reverse engineering (Request #1523)
- made _doQuery() return a reference
- added userinfo's to all raiseError calls that previously had none
- calling PREPARE is always a manip query
- added 'prepared_statements' supported meta data setting
- strip of file:// prefix in writeLOBToFile()
- typo fix ressource/resource in LOB array
- added missing supported parameter to prepare() signature
- properly handle explicit error codes in errorInfo()
- moved from OID to TEXT and BYTEA (OID handling was broken, its also deprecated)
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(
    array(
        'packagefile'       => 'package_pgsql.xml',
        'package'           => 'MDB2_Driver_pgsql',
        'summary'           => 'pgsql MDB2 driver',
        'description'       => 'This is the PostGreSQL MDB2 driver.',
        'version'           => $version,
        'state'             => 'stable',
        'license'           => 'BSD License',
        'filelistgenerator' => 'cvs',
        'include'           => array('*pgsql*'),
        'ignore'            => array('package_pgsql.php'),
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
$package->addDependency('pgsql', null, 'has', 'ext', false);

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
