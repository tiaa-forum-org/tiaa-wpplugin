<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

namespace TIAAPlugin\Analog\Handler;

require_once __DIR__ . '/../../ChromePhp.php';

/**
 * Log to the [Chrome Logger](http://craig.is/writing/chrome-logger).
 * Based on the [ChromePhp library](https://github.com/ccampbell/chromephp).
 *
 * Usage:
 *
 *     Analog::handler (Analog\Handler\ChromeLogger::init ());
 *     
 *     // send a debug message
 *     Analog::debug ($an_object);
 *
 *     // send an ordinary message
 *     Analog::info ('An error message');
 */
class ChromeLogger {
	public static function init () {
		return function ($info) {
			switch ($info['level']) {
				case \TIAAPlugin\Analog\Analog::DEBUG:
					\ChromePhp::log ($info['message']);
					break;
				case \TIAAPlugin\Analog\Analog::INFO:
				case \TIAAPlugin\Analog\Analog::NOTICE:
					\ChromePhp::info ($info['message']);
					break;
				case \TIAAPlugin\Analog\Analog::WARNING:
					\ChromePhp::warn ($info['message']);
					break;
				case \TIAAPlugin\Analog\Analog::ERROR:
				case \TIAAPlugin\Analog\Analog::CRITICAL:
				case \TIAAPlugin\Analog\Analog::ALERT:
				case \TIAAPlugin\Analog\Analog::URGENT:
					\ChromePhp::error ($info['message']);
					break;
			}
		};
	}
}