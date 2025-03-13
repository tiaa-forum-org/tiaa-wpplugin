<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

require '../lib/Analog.php';

Analog::handler (Analog\Handler\Mongo::init (
	'localhost:27017',
	'testing',
	'log'
));

Analog::log ('Error message');
Analog::log ('Debug info', Analog::DEBUG);

$m = new MongoClient ('mongodb://localhost:27017');
$cur = $m->testing->log->find ();
foreach ($cur as $doc) {
	print_r ($doc);
}
$m->testing->log->remove ();

?>
