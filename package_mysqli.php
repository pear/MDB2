<?php

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$version = '1.4.1';
$state = 'stable';
$notes = <<<EOT
- fixed bug #10378: incorrect query rewrite in setLimit() when using "FOR UPDATE"
  or "LOCK IN SHARE MODE" (thanks to priyadi) or "INTO OUTFILE" or "INTO DUMPFILE"
- fixed bug #10384: in setCharset(), use mysqli_real_escape_string() instead of
  mysql_real_escape_string()
- return length as "precision,scale" for NUMERIC and DECIMAL fields in mapNativeDatatype()
- in getTableIndexDefinition() and getTableConstraintDefinition() in the Reverse
  module, also return the field position in the index/constraint
- fixed bug #10636: transactions broken in release 2.4.0 because of some properties
  being reset (thanks to Conor Kerr)
- fixed bug #10748: failed connections don't return native error code [urkle]
- fixed bug #10895: setLimit() does not work properly when a subquery uses LIMIT

open todo items:
- use a trigger to emulate setting default now()
EOT;

$description = 'This is the MySQLi MDB2 driver.';
$packagefile = './package_mysqli.xml';

$options = array(
    'filelistgenerator' => 'cvs',
    'changelogoldtonew' => false,
    'simpleoutput'      => true,
    'baseinstalldir'    => '/',
    'packagedirectory'  => './',
    'packagefile'       => $packagefile,
    'clearcontents'     => false,
    'include'           => array('*mysqli*'),
    'ignore'            => array('package_mysqli.php'),
);

$package = &PEAR_PackageFileManager2::importOptions($packagefile, $options);
$package->setPackageType('php');

$package->clearDeps();
$package->setPhpDep('5.0.0');
$package->setPearInstallerDep('1.4.0b1');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.4.1');
$package->addExtensionDep('required', 'mysqli');

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