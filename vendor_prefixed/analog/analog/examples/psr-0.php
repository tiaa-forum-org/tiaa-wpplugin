<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

require 'SplClassLoader.php';

$loader = new SplClassLoader ('Analog', '../lib');
$loader->register ();

use \TIAAPlugin\Analog\Analog;

$log = '';

Analog::handler (\Analog\Handler\Variable::init ($log));

Analog::log ('Test one');
Analog::log ('Test two');

echo $log;

?>