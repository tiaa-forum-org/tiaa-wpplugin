<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

require '../lib/Analog.php';

$log_file = 'log.txt';

Analog::handler (Analog\Handler\File::init ($log_file));

Analog::log ('foo');
Analog::log ('bar');

echo file_get_contents ($log_file);
unlink ($log_file);

?>