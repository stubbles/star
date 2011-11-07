<?php
$saveDir = getcwd();
chdir(dirname(__FILE__));
require 'src/main/php/org/stubbles/star/StarException.php';
require 'src/main/php/org/stubbles/star/StarFile.php';
require 'src/main/php/org/stubbles/star/StarCreator.php';
require 'src/main/php/org/stubbles/star/StarArchive.php';
require 'src/main/php/org/stubbles/star/StarConsole.php';
StarConsole::main();
chdir($saveDir);
?>