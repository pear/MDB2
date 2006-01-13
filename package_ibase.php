<?php

require_once 'PEAR/PackageFileManager.php';

$version = '0.2.1';
$notes = <<<EOT
- removed unnecessary if statement in getTableFieldDefinition()
- no need to register a shutdown function since te ibase driver is php5 only
  and therefore always uses the destructor
- fixed typo in _execute() that would lead to using the wrong types inside the result object
- use proper error code in alterTable()
- explicitly pass if the module is phptype specific in all loadModule calls (bug #6226)
- added error handling in prepare()
- fixed signature of quoteIdentifier() to make second param optional
- req #6464: add the extension only if neither '.gdb' nor '.fdb' is given in the
  database name, for compatibility with PEAR::DB DNS (Mark Wiesemann)
- fixed bug #6465: possible mismatch in MDB2_Reverse_ibase due to parentheses
  in the returned datatype (Mark Wiesemann)
- fixed bug #6468: possible NOTICE in Driver/Datatype/ibase.php (Mark Wiesemann)
- fixed bug #6475: listTableIndexes() should only list indices, not constraints,
  and listTableConstraints() should return the user-defined names when available
- _fixIndexName() now just attempts to remove possible formatting
- renamed _isSequenceName() to _fixSequenceName()
- _fixSequenceName() now just attempts to remove possible formatting, and only
  returns a boolean if no formatting was applied when the new "check" parameter is set to true

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
        'state'             => 'beta',
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
$package->addDependency('MDB2', '2.0.0RC4', 'ge', 'pkg', false);
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
