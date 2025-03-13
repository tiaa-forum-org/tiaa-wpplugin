<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

require '../lib/Analog.php';

Analog::log ('foo');
Analog::log ('bar');

echo file_get_contents (Analog::handler ());
unlink (Analog::handler ());

?>