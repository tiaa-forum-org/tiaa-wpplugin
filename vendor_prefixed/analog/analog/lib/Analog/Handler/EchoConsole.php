<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

namespace TIAAPlugin\Analog\Handler;

/**
 * Echo output directly to the console.
 *
 * Usage:
 *
 *     Analog::handler (Analog\Handler\EchoConsole::init ());
 *     
 *     Analog::log ('Log me');
 *
 * Note: Uses Analog::$format for the output format.
 */
class EchoConsole {
	public static function init () {
		return function ($info) {
			vprintf (\TIAAPlugin\Analog\Analog::$format, $info);
		};
	}
}
