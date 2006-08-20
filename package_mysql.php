<?php

require_once 'PEAR/PackageFileManager.php';

$version = 'YYY';
$notes = <<<EOT
- fixed issue in tableInfo() that originates in getTableFieldDefinition() which
  led to returning incorrect type values (Bug #8291)
- quote identifiers in the reverse module when 'quote_identifiers' is enabled (Bug #8309)
- use version_compare() to fix complex version comparisons (Bug #8355)
- do not use quote() in setCharset() since it is supposed to set the charset in
  the connection that was passed to it
- return an error if a named placeholder name is used twice inside a single statement
- do not list empty contraints and indexes
- added support for 'primary' option in createTable()
- fixed notnull reverse engineering on mysql 4.x (Bug #8415)
- do not set a default if type is a LOB (Request #8074)
- if a default value is set, then we need to use VARCHAR instead of TEXT
- removed _verifyTableType() since it just adds overhead, is hard to do reliably
  and you will get an error if the table type is not supported anyways
- fixed handling return values when disable_query is set in _doQuery() and _execute()
- only call RELEASE SAVEPOINT if the server version if 5.0.3 or higher
- increased MDB2 dependency too XXX

note:
- the multi_query test failes because this is not supported by ext/mysql
- use a trigger to emulate setting default now()
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
$package->addDependency('MDB2', 'XXX', 'ge', 'pkg', false);
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
