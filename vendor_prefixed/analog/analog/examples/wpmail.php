<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

require '../lib/Analog.php';

// Note: This example needs to be copied into a Wordpress installation to work
Analog::handler (Analog\Handler\WPMail::init (
	'you@example.com',
	'Log message',
	'noreply@example.com',
	'log-email-template.php'
));

Analog::log ('Error message');

?>