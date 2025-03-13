<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

namespace TIAAPlugin\Analog\Handler;

/**
 * Append the log info to a variable passed in as a reference.
 *
 * Usage:
 *
 *     $my_log = '';
 *     Analog::handler (Analog\Handler\Variable::init ($my_log));
 *     
 *     Analog::log ('Log me');
 *     echo $my_log;
 *
 * Note: Uses Analog::$format for the appending format.
 */
class Variable {
	public static function init (&$log) {
		return function ($info, $buffered = false) use (&$log) {
			$log .= ($buffered)
				? $info
				: vsprintf (\TIAAPlugin\Analog\Analog::$format, $info);
		};
	}
}