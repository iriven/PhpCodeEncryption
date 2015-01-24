<?php
require '/../IrivenPhpCodeEncryption.php';
$encryption = new IrivenPhpCodeEncryption();
/**
* from file to file
*
*/
$encryption->loadCode('source.php');
$encryption->compileDatas();
$encryption->save('encrypted.php');
// save with auto-filename
$encryption->save();
/**
* from file to memory
*
*/
$encryption->loadCode('source.php');
$encryption->compileDatas();
$encryption->getCode();
