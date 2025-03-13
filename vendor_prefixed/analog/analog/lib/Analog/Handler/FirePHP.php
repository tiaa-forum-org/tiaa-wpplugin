<?php
/* This file has been prefixed by <PHP-Prefixer> for "Logging and other libraries for TIAA WordPress plugin" */

namespace TIAAPlugin\Analog\Handler;

/**
 * Log via FirePHP using the Wildfire protocol (http://www.firephp.org/).
 * Based loosely on the Monolog FirePHP handler.
 *
 * Usage:
 *
 *     Analog::handler (Analog\Handler\FirePHP::init ());
 *     
 *     // send a debug message with file and line number
 *     Analog::log (array ('Log me', __FILE__, __LINE__), Analog::DEBUG);
 *
 *     // send an ordinary message
 *     Analog::log ('An error message');
 */
class FirePHP {
	/**
	 * Translation list for log levels.
	 */
	private static $log_levels = array (
		\TIAAPlugin\Analog\Analog::DEBUG    => 'LOG',
		\TIAAPlugin\Analog\Analog::INFO     => 'INFO',
		\TIAAPlugin\Analog\Analog::NOTICE   => 'INFO',
		\TIAAPlugin\Analog\Analog::WARNING  => 'WARN',
		\TIAAPlugin\Analog\Analog::ERROR    => 'ERROR',
		\TIAAPlugin\Analog\Analog::CRITICAL => 'ERROR',
		\TIAAPlugin\Analog\Analog::ALERT    => 'ERROR',
		\TIAAPlugin\Analog\Analog::URGENT   => 'ERROR'
	);

	/**
	 * Message index increases by 1 each time a message is sent.
	 */
	private static $message_index = 1;

	/**
	 * Formats a log header to be sent.
	 */
	public static function format_header ($info) {
		if (is_array ($info['message'])) {
			$extra = array (
				'Type' => self::$log_levels[$info['level']],
				'File' => $info['message'][1],
				'Line' => $info['message'][2]
			);
			$info['message'] = $info['message'][0];
		} else {
			$extra = array ('Type' => self::$log_levels[$info['level']]);
		}

		$json = json_encode (array ($extra, $info['message']));

		return sprintf ('X-Wf-1-1-1-%d: %s|%s|', self::$message_index++, strlen ($json), $json);
	}

	/**
	 * Sends the initial headers if FirePHP is available then returns a
	 * closure that handles sending log messages.
	 */
	public static function init () {
		if (! isset ($_SERVER['HTTP_USER_AGENT']) 
			|| preg_match ('{\bFirePHP/\d+\.\d+\b}', $_SERVER['HTTP_USER_AGENT'])
			|| isset ($_SERVER['HTTP_X_FIREPHP_VERSION'])) {

			header ('X-Wf-Protocol-1: http://meta.wildfirehq.org/Protocol/JsonStream/0.2');
			header ('X-Wf-1-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.3');
			header ('X-Wf-1-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');
		}

		return function ($info) {
			header (FirePHP::format_header ($info));
		};
	}
}