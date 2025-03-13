<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

namespace TIAAPlugin\Analog\Handler;

/**
 * Send the output to STDERR.
 *
 * Usage:
 *
 *     Analog::handler (Analog\Handler\Stderr::init ());
 *     
 *     Analog::log ('Log me');
 *
 * Note: Uses Analog::$format for the appending format.
 */
class Stderr {
	public static function init () {
		return function ($info, $buffered = false) {
			file_put_contents ('php://stderr', ($buffered)
				? $info
				: vsprintf (\TIAAPlugin\Analog\Analog::$format, $info));
		};
	}
}