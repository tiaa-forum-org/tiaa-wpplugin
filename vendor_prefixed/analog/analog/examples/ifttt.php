<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

require '../lib/Analog.php';

$event_name = 'test_event'; // From the webhook you setup
$secret_key = 'secret_key'; // From your Maker settings

Analog::handler (Analog\Handler\IFTTT::init ($event_name, $secret_key));

Analog::log ('foo');

?>