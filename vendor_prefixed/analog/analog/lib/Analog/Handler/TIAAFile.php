<?php
/*
 * TODO - this file should probably be removed from the vendor directory and put in the plugin library
 * with links back to vendor for support
 * Same as the Analog file handler except that it uses log level names instead of
 * numbers.
 */
namespace TIAAPlugin\Analog\Handler;
/**
 * Append to the specified log file. Does the same thing as the default
 * handling except uses the string for log level instead of integer.
 *
 * Usage:
 *
 *     $log_file = 'log.txt';
 *     Analog::handler (Analog\Handler\File::init ($log_file));
 *
 *     Analog::log ('Log me');
 *
 * Note: Uses Analog::$format for the appending format.
 */

class TIAAFile {
	const LOG_LEVELS = [
		\TIAAPlugin\Analog\Analog::URGENT => 'URGENT',
		\TIAAPlugin\Analog\Analog::ALERT => 'ALERT',
		\TIAAPlugin\Analog\Analog::CRITICAL => 'CRITICAL',
		\TIAAPlugin\Analog\Analog::ERROR => 'ERROR',
		\TIAAPlugin\Analog\Analog::WARNING => 'WARNING',
		\TIAAPlugin\Analog\Analog::NOTICE => 'NOTICE',
		\TIAAPlugin\Analog\Analog::INFO => 'INFO',
		\TIAAPlugin\Analog\Analog::DEBUG => 'DEBUG'
	];
	public static function init ($file) {
		return function ($info, $buffered = false) use ($file) {
			static $f = null;

			if ($f == null) {
				$f = fopen ($file, 'a+');

				if (! $f) {
					throw new \LogicException ('Could not open file for writing');
				}

				register_shutdown_function (function () use ($f) {
					if ($f != null) {
						fclose ($f);
						$f = null;
					}
				});
			}

			if (! flock ($f, LOCK_EX)) {
				throw new \RuntimeException ('Could not lock file');
			}
			$info['level'] = self::LOG_LEVELS[$info['level']] ??
			                 'UNKNOWN-' . $info['level'];
			fwrite ($f, ($buffered)
				? $info
				: vsprintf (\TIAAPlugin\Analog\Analog::$format, $info));
			flock ($f, LOCK_UN);
		};

	}



}