<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

namespace TIAAPlugin\Analog\Handler;

/**
 * Translates log level codes to their names
 *
 *
 * Usage:
 *
 *     // The log level (3rd value) must be formatted as a string
 *     Analog::$format = "%s - %s - %s - %s\n";
 * 
 *     Analog::handler (Analog\Handler\LevelName::init (
 *         Analog\Handler\File::init ($file)
 *     ));
 */
class LevelName {
	/**
	 * Translation list for log levels.
	 */
	private static $log_levels = array (
		\TIAAPlugin\Analog\Analog::DEBUG    => 'DEBUG',
		\TIAAPlugin\Analog\Analog::INFO     => 'INFO',
		\TIAAPlugin\Analog\Analog::NOTICE   => 'NOTICE',
		\TIAAPlugin\Analog\Analog::WARNING  => 'WARNING',
		\TIAAPlugin\Analog\Analog::ERROR    => 'ERROR',
		\TIAAPlugin\Analog\Analog::CRITICAL => 'CRITICAL',
		\TIAAPlugin\Analog\Analog::ALERT    => 'ALERT',
		\TIAAPlugin\Analog\Analog::URGENT   => 'URGENT'
	);

	public static function init ($handler) {
		return new LevelName ($handler);
	}

	/**
	 * For use as a class instance
	 */
	private $_handler;

	public function __construct ($handler) {
		$this->_handler = $handler;
	}

	public function log ($info) {
		if (isset(self::$log_levels[$info['level']])) {
			$info['level'] = self::$log_levels[$info['level']];
		}
		call_user_func ($this->_handler, $info);
	}
}