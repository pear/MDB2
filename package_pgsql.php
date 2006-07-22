<?php

require_once 'PEAR/PackageFileManager.php';

$version = '1.2.0';
$notes = <<<EOT
- performance tweaks for pg_prepare() enabled installations
- MDB2_PORTABILITY_RTRIM ignored by driver (Bug #8239)
- added ability to escape wildcard characters in escape() and quote()
- added setTransactionIsolation()
- added savepoint support to beginTransaction(), commit() and rollback()
- added debug() call at the end of a query/prepare/execute calling (Request #7933)
- added context array parameter to debug() and make use of it whereever sensible
- added optional method name parameter to raiseError() and use whereever possible
- added ability to escape wildcard characters in escape() and quote()
- added debug() call at the end of a query/prepare/execute calling (Request #7933)
- added 'nativetype' output to tableInfo() and getTableFieldDefinition()
- added 'mdb2type' output to getTableFieldDefinition()
- reworked tableInfo() to use a common implementation based on getTableFieldDefinition()
  when a table name is passed (Bug #8124)
- fixed incorrect regex in mapNativeDatatype() (Bug #8256) (thx ioz at ionosfera dot com)
- disconnect after changing database/DSN (otherwise transactions may be left open)
- use old dsn when rolling back open transactions in disconnect()

open todo items:
- enable pg_execute() once issues with bytea column are resolved
- add schema support to _pgFieldFlags() in pgsql driver (Request #5796)
- add optional use of pg_result_error_field()to handle localized error messages (Request #7059)
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
$package->addMaintainer('nrf', 'developer', 'Nathan Fredrickson', 'nathan@silverorange.com');

$package->addDependency('php', '4.3.0', 'ge', 'php', false);
$package->addDependency('PEAR', '1.0b1', 'ge', 'pkg', false);
$package->addDependency('MDB2', '2.2.0', 'ge', 'pkg', false);
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
