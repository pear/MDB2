<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = '1.5.0b2';
$state = 'beta';
$notes = <<<EOT
- fixed bug #12117: disconnect() does not work as documented
- request #14639: automatically fetch CLOBs when retrieved with 'text' data type
- getTableFieldDefinition() now returns the charset and collation of the field, if defined
EOT;

$description = 'This is the Interbase/Firebird MDB2 driver.';
$packagefile = './package_ibase.xml';

$options = array(
    'filelistgenerator' => 'cvs',
    'changelogoldtonew' => false,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/',
    'packagedirectory'  => './',
    'packagefile'       => $packagefile,
    'clearcontents'     => false,
    'include'           => array('*ibase*'),
    'ignore'            => array('package_ibase.php'),
);

$package = &PEAR_PackageFileManager2::importOptions($packagefile, $options);
$package->setPackageType('php');

$package->clearDeps();
$package->setPhpDep('5.0.4');
$package->setPearInstallerDep('1.4.0b1');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.5.0b2');
$package->addExtensionDep('required', 'interbase');

$package->addRelease();
$package->generateContents();
$package->setReleaseVersion($version);
$package->setAPIVersion($version);
$package->setReleaseStability($state);
$package->setAPIStability($state);
$package->setNotes($notes);
$package->setDescription($description);
$package->addGlobalReplacement('package-info', '@package_version@', 'version');

if (isset($_GET['make']) || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
    $package->writePackageFile();
} else {
    $package->debugPackageFile();
}
