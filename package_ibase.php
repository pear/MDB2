<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = '1.5.0b1';
$state = 'alpha';
$notes = <<<EOT
- request #12731: added truncateTable() in the Manager module
- request #12732: added vacuum() in the Manager module for OPTIMIZE/VACUUM TABLE abstraction
- fixed bug #12924: correctly handle internal expected errors even with custom error handling
- fixed bug #12948: removed setCharset(), since "SET NAMES" is only supported in ISQL
- fixed bug #12958: support multi autoincrement fields in _makeAutoincrement() [patch by Ali Fazelzade]
- fixed listSequences() and listFunctions() in the Manager module with Firebird 2.x
- fixed getServerVersion() with Firebird 2.x
- feature #12962: in getServerVersion(), fallback to the username/password of the
  connected user if DBA_username/DBA_password options are not provided [thanks to Ali Fazelzade]
- added standaloneQuery() and databaseExists()
- added length() function in the Function module (use STRLEN in the std UDF functions)
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
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.5.0b1');
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
