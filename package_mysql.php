<?php

require_once 'PEAR/PackageFileManager.php';

$version = 'XXX';
$notes = <<<EOT
- added matchPattern() and patternEscapeString(), escapePattern()
- added ability to escape wildcard characters in escape() and quote()
- added setTransactionIsolation()
- added savepoint support to beginTransaction(), commit() and rollback()
- added debug() call at the end of a query/prepare/execute calling (Request #7933)
- added context array parameter to debug() and make use of it whereever sensible
- added optional method name parameter to raiseError() and use whereever possible
EOT;

$package = new PEAR_PackageFileManager();

$result = $package->setOptions(
    array(
        'packagefile'       => 'package_mysql.xml',
        'package'           => 'MDB2_Driver_mysql',
        'summary'           => 'mysql MDB2 driver',
        'description'       => 'This is the MySQL MDB2 driver.',
        'version'           => $version,
        'state'             => 'stable',
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
$package->addDependency('MDB2', '2.1.0', 'ge', 'pkg', false);
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
