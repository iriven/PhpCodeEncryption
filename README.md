IrivenPhpCodeEncryption
=======================
Classe de cryptographie entièrement developpée en PHP destinée  tant  aux developpeurs amateurs que confirmés. 






EXAMPLE
========
require '/../IrivenPhpCodeEncryption.php';

$encryption = new IrivenPhpCodeEncryption();

<<<<<<< HEAD
/**
=======
>>>>>>> origin/master
* from file to file

$encryption->loadCode('source.php');

$encryption->compileDatas();

$encryption->save('encrypted.php');

* save with auto-filename

$encryption->save();

* from file to memory

$encryption->loadCode('source.php');

$encryption->compileDatas();

$encryption->getCode();

