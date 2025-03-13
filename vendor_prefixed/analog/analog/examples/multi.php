<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

require '../lib/Analog.php';

$errors = "Errors:\n";
$warnings = "Warnings:\n";
$debug = "Debug:\n";

Analog::handler (Analog\Handler\Multi::init (array (
	Analog::ERROR   => TIAAPlugin\Analog\Handler\Variable::init ($errors),
	Analog::WARNING => TIAAPlugin\Analog\Handler\Variable::init ($warnings),
	Analog::DEBUG   => TIAAPlugin\Analog\Handler\Variable::init ($debug)
)));

Analog::log ('First error');
Analog::log ('Emergency!', Analog::URGENT);
Analog::log ('A warning...', Analog::WARNING);
Analog::log ('Some info', Analog::INFO);
Analog::log ('Debugging output', Analog::DEBUG);

echo $errors;
echo "-----\n";
echo $warnings;
echo "-----\n";
echo $debug;

?>