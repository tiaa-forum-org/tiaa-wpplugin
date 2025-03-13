<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

require '../lib/Analog.php';

Analog::handler (Analog\Handler\Mail::init (
	'you@example.com',
	'Log message',
	'noreply@example.com'
));

Analog::log ('Error message');

?>