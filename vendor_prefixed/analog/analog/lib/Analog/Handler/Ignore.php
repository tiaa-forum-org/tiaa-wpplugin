<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

namespace TIAAPlugin\Analog\Handler;

/**
 * Ignores anything sent to it so you can disable logging.
 *
 * Usage:
 *
 *     Analog::handler (Analog\Handler\Ignore::init ());
 *     
 *     Analog::log ('Log me');
 */
class Ignore {
	public static function init () {
		return function ($info) {
			// do nothing
		};
	}
}