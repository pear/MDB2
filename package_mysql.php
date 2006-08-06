<?php

require_once 'PEAR/PackageFileManager.php';

$version = 'XXX';
$notes = <<<EOT
- fixed issue in tableInfo() that originates in getTableFieldDefinition() which
  led to returning incorrect type values (Bug #8291)
- quote identifiers in the reverse module when 'quote_identifiers' is enabled (Bug #8309)
- return an error if a name placeholder name is used twice inside a single statement
- use version_compare() to fix complex version comparisons (Bug #8355)
- some RDBMS default to NOT NULL (noteably MSSQL) and therefore we need to
  explictly default to NULL (Bug #8359)
- do not use quote() in setCharset() since it is supposed to set the charset in
  the connection that was passed to it
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
$package->addDependency('MDB2', '2.2.0', 'ge', 'pkg', false);
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
