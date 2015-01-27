<?php
require '/../IrivenPhpCodeEncryption.php';
$encryption = new IrivenPhpCodeEncryption();
/**
* from file to file
*
*/
==> SPECIFIC FILE NAME
$encryption->loadCode('source.php');
$encryption->compileDatas();
$encryption->save('encrypted.php');

//chained command method
$encryption->loadCode('source.php')->compileDatas()->save('encrypted.php');

==> SAVE WITH AUTO-FILENAME
$encryption->loadCode('source.php');
$encryption->compileDatas();
$encryption->save();

//chained command method
$encryption->loadCode('source.php')->compileDatas()->save();

/**
* from file to memory
*
*/
$encryption->loadCode('source.php');
$encryption->compileDatas();
$encryption->getCode();
