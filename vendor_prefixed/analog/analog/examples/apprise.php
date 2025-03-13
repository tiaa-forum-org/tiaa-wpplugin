<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

require '../lib/Analog.php';

$command = '/usr/local/bin/apprise';
$service = 'slack://tokenA/tokenB/tokenC/#slack-channel';
Analog::handler (Analog\Handler\Apprise::init ($command, $service));

Analog::log ('Output to apprise command');

?>