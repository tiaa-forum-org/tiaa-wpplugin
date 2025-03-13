<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

require '../lib/Analog.php';

Analog::handler (Analog\Handler\Slackbot::init ('teamname', 'token', 'channel'));

Analog::log ('Error message', Analog::WARNING);

?>