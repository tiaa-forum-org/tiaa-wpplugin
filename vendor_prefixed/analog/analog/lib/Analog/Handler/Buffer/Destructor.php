<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

namespace TIAAPlugin\Analog\Handler\Buffer;

/**
 * A destructor object to call close() for us at the end of the request.
 */
class Destructor {
	public function __destruct () {
		\TIAAPlugin\Analog\Handler\Buffer::close ();
	}
}