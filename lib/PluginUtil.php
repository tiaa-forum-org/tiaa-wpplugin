<?php
/**
 * Utility class for handling various plugin operations.
 *
 * Provides a collection of utilities used within the tiaa-wpplugin plugin,
 * including methods to retrieve options, ping the Discourse server, parse messages, etc.
 *
 * @package    TIAAPlugin
 * @subpackage Utilities
 * @version    1.0.0
 * @author     Lew Grothe, TIAA Admin Platform sub-team
 * @link       https://tiaa-forum.org/contact
 * @license    GPL-2.0-or-later
 */

namespace TIAAPlugin\lib;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use TIAAPlugin\Analog\Analog;

/**
 * Trait PluginUtil
 *
 * A collection of utility methods for plugin functionality.
 *
 * @package TIAAPlugin\lib
 */
trait PluginUtil {
	/**
	 * Static property to indicate initialization status of logging.
	 *
	 * @var bool
	 */
	private static bool $log_initialized = false;

	/**
	 * Static property to store log options.
	 *
	 * @var mixed
	 */
	private static array $log_options;

	/**
	 * Static property to define the log level.
	 *
	 * @var mixed
	 */
	private static int $log_level;

	/**
	 * Returns a single array of options from a given array of arrays.
	 *
	 * This method retrieves the `option_groups` reference from the `OptionsUtilities`
	 * class and returns it.
	 *
	 * @since 0.0.3
	 *
	 * @return ?array Reference to the `option_groups` array or null if not defined.
	 */
	protected function get_all_options(): ?array {
		$var = &OptionsUtilities::$option_groups;
		return ($var);
	}

	/**
	 * Retrieves the options by a specific group.
	 *
	 * This static method allows retrieving configuration options from a specified group
	 * stored within the singleton `OptionsUtilities`.
	 *
	 * @since 0.0.3
	 *
	 * @param string $option_group The group of options to retrieve.
	 * @return array|null The options for the specified group, or null if not found.
	 */
	public static function get_options_by_group(string $option_group): array|null {
		$var = &OptionsUtilities::$option_groups[$option_group];
		return ($var);
	}

	/**
	 * Creates the full option name for the form `name` fields.
	 *
	 * Prepends the option group to the option name with formatting suitable
	 * for usage in WordPress options APIs. Each group is stored as an array
	 * within the `wp_options` table.
	 *
	 * @since 0.0.3
	 *
	 * @param string $option       The name of the option.
	 * @param string $option_group The group to save the option to.
	 * @return string The formatted option name.
	 */
	protected function option_array_name(string $option, string $option_group): string {
		return $option_group . '[' . esc_attr($option) . ']';
	}

	/**
	 * Sends a ping request to the Discourse server.
	 *
	 * This method is used to send a ping request to the Discourse server,
	 * ensuring that the connection settings for a specified option group are correct.
	 * It validates input parameters and returns appropriate success or error responses.
	 *
	 * @since 0.0.3
	 *
	 * @param WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response The server's response on success or error details on failure.
	 */
	public function do_ping_discourse_server(WP_REST_Request $request) : WP_REST_Response {
		$params = $request->get_params();
		if (empty($params) || empty($params['option_group'])) {
			return new WP_REST_Response(
				array(
					'code'    => 'invalid_call',
					'message' => 'no option group specified',
				), 500
			);
		}
		$option_group = $params['option_group'];

		// Get connection settings
		$cs = Discourse::get_connection_options_by_group($option_group);
		if (is_wp_error($cs)) {
			self::log_wp_error('ping', $cs, __FUNCTION__, __CLASS__, __LINE__);
			$data = array(
				'message' => $cs->get_error_message(),
				'code'    => $cs->get_error_code(),
			);
			return new WP_REST_Response($data, 500);
		}

		if (
			empty($cs) ||
			empty($cs['url']) ||
			empty($cs['api_key']) ||
			empty($cs['username'])
		) {
			return new WP_REST_Response(
				array(
					'code'    => 'no_connections',
					'message' => 'Discourse connections not set',
				), 500
			);
		}

		return Discourse::ping_discourse_server($cs['url'], $cs['api_key'], $cs['username']);
	}

	/**
	 * Retrieves a post from the Discourse server.
	 *
	 * This method retrieves a specific post from the Discourse server using the provided
	 * post ID and connection settings for the specified option group.
	 *
	 * @since 0.0.3
	 *
	 * @param WP_REST_Request $request The REST API request object containing post ID and option group.
	 * @return string|WP_Error|WP_REST_Response|array The server's response, post content, or an error.
	 */
	public function get_discourse_post_by_id(WP_REST_Request $request): string|WP_Error|WP_REST_Response|array {
		$params = $request->get_params();
		$post_id = $params['post_id'];
		$option_group = $params['option_group'];

		return Discourse::get_discourse_post_by_id($post_id, $option_group);
	}

	/**
	 * Parses a message to extract a specific section of its content.
	 *
	 * This method searches for a marker (`BeginMessage ----`) in a given string
	 * and retrieves the text following the marker. Designed for processing structured
	 * messages that include distinct sections.
	 *
	 * @since 0.0.3
	 *
	 * @param ?string $text The input message string to parse.
	 * @return ?string The parsed message content, or null if the marker is not found.
	 */
	public function parse_message(?string $text): ?string {
		// Return null if the input is null
		if ($text === null) {
			return null;
		}

		// Pattern to match "BeginMessage ----" at the beginning of a line (multi-line supported)
		$pattern = '/\\nBeginMessage ----([\s\S]*)$/m';

		// Use preg_match to retrieve the line that starts with "BeginMessage ----"
		if (preg_match($pattern, $text, $matches)) {
			// Return the text after the marker, or null if nothing is captured
			return isset($matches[1]) ? trim($matches[1]) : null;
		}

		// Marker not found
		return null;
	}

	/**
	 * parse_return - makes error messages more useful
	 *
	 * @return string
	 */
/*	public static function parse_return($data) : string {
		if ($data instanceof WP_REST_Response) {
			return 'WP_REST_Response: ' . $data->get_data()['message'] . ' (' . $data->get_status() . ')';
		} elseif ($data instanceof WP_Error) {
			// Handle the case where $data is of type WP_Error, which also extends WP_Response in this context.
			return 'WP_Error: ' . $data->get_error_message() . ' (' . $data->get_error_code() . ')';
		} elseif (is_array($data)) {
			// Handle the case where $data is an array.
			if (isset($data['response']) && strlen($data['response']) > 40) {
				return ' data1: ' . substr($data['response'], 0, 40) . '...';
			} else {
				return ' data2:' . $data['response'];
			}
		} else {
			if (is_object($data)) {
				return 'data is: ' . get_class($data);
			} else {
				return 'data is: ' . gettype($data);
			}
		}
	}*/

	/**
	 * if logging is not initialized, set it up...
	 *
	 * @return bool
	 */
	public static function is_log_initialized() : bool {
		if (self::$log_initialized === true) {
			return true;
		}
		self::my_log_level(); // also initializes log if not

		return true;
	}
	/**
	 * Logs a message with 'urgent' level.
	 *
	 * @param string $message The message to log.
	 * @return void
	 */
	public static function log_urgent(string $message): void {
		if (self::is_log_initialized()) {
			Analog::urgent($message);
		}
	}

	/**
	 * Logs a message with 'alert' level
	 *
	 * @param $message
	 *
	 * @return void
	 */
	public static function log_alert($message): void {
		if (self::is_log_initialized()) {
			Analog::alert($message);
		}
	}

	/**
	 *  Logs a message with 'critical' level
	 *
	 * @param $message
	 *
	 * @return void
	 */
	public static function log_critical($message): void {
		if (self::is_log_initialized()) {
			Analog::log($message,\Analog::CRITICAL);
		}
	}

	/**
	 *  Logs a message with 'error' level
	 *
	 * @param $message
	 *
	 * @return void
	 */
	public static function log_error( $message): void {
		if (self::is_log_initialized()) {
			Analog::error($message);
		}
	}

	/**
	 *  Logs a message with 'warning' level
	 *
	 * @param $message
	 *
	 * @return void
	 */
	public static function warning( $message): void {
		if (self::my_log_level() <= Analog::WARNING && self::is_log_initialized()) {
			Analog::warning($message);
		}
	}
	/**
	 * Logs a message with 'notice' level.
	 *
	 * @param string $message The message to log.
	 * @return void
	 */
	public static function log_notice(string $message): void {
		if (self::my_log_level() <= Analog::NOTICE && self::is_log_initialized()) {
			Analog::notice($message);
		}
	}

	/**
	 *  Logs a message with 'info' level
	 *
	 * @param $message
	 *
	 * @return void
	 */
	public static function log_info( $message): void {
		if (self::my_log_level() <= Analog::INFO && self::is_log_initialized()) {
			Analog::info($message);
		}
	}

	/**
	 *  Logs a message with 'debug' level
	 *
	 * @param $message
	 *
	 * @return void
	 */
	public static function log_debug($message): void {
		if (self::my_log_level() <= Analog::DEBUG && self::is_log_initialized()) {
			Analog::debug($message);
		}
	}
	/**
	 * Retrieves and initializes the logging level for the plugin.
	 *
	 * This method ensures that the logging configuration, including log level, log file path,
	 * and logging format, is initialized if it hasn't already been set. It uses the defined
	 * logging group to fetch relevant options and sets up the handler and format for logging entries.
	 *
	 * @since 1.0.0
	 *
	 * @return int The integer value of the current logging level.
	 */
	private static function my_log_level() : int {
		if (empty(self::$log_level))  {
			self::$log_options = self::get_options_by_group(TIAA_LOGGING_GROUP);
			self::$log_level = self::$log_options['log_level'];
			$logfile = self::$log_options['file_path'];
			Analog::handler(\TIAAPlugin\Analog\Handler\TIAAFile::init($logfile));
			Analog::$format = "%2\$s: %3\$s - %4\$s\n";
			Analog::$date_format = 'Y-m-d H:i:s';
			Analog::$timezone = 'America/Denver';
			self::$log_initialized = true;
		}
		return self::$log_level;
     }

	private static function my_log_level() : int {
		if (empty(self::$log_level))  {
			self::$log_options = self::get_options_by_group(TIAA_LOGGING_GROUP);
			self::$log_level = self::$log_options['log_level'];
			$logfile = self::$log_options['file_path'];
			Analog::handler(\TIAAPlugin\Analog\Handler\TIAAFile::init($logfile));
			Analog::$format = "%2\$s: %3\$s - %4\$s\n";
			Analog::$date_format = 'Y-m-d H:i:s';
			Analog::$timezone = 'America/Denver';
			self::$log_initialized = true;
		}
		return self::$log_level;
     }

	/**
	 * Logs an error with details from WP_Error info
	 *
	 * @param string      $message
	 * @param WP_Error    $wp_error
	 * @param string|null $function
	 * @param string|null $class
	 * @param string|null $line
	 *
	 * @return void
	 */
	public static function log_wp_error( string $message, WP_Error $wp_error,
		?string $function = null, ?string $class = null, ?string $line = null) : void {
		$out = $message;
		if ($function != null ) {
			$out .= ' in ';
			$out .= ($class != null ) ? $class . '::': '';
			$out .= $function;
			$out .= ($line != null ) ? ':"' . $line: '';
		}
		$out .= " code: " . $wp_error->get_error_code() . " message: " . $wp_error->get_error_message();
		self::log_error($out);
	}
	/**
	 * Logs an error with data from a WP_REST_Response.
	 *
	 * @param string $message The initial message to log.
	 * @param WP_REST_Response|WP_Error $wp_rest_response The response that contains the error.
	 * @param string|null $function The function where the error occurred.
	 * @param string|null $class The class where the error occurred.
	 * @param string|null $line The line where the error occurred.
	 * @return void
	 */
	public static function log_wp_rest_response_error( string $message, WP_REST_Response|WP_Error $wp_rest_response,
		?string $function = null, ?string $class = null, ?string $line = null) : void {
		if (is_wp_error($wp_rest_response)) {
			self::log_wp_error(  'rest_response: '. $message, $wp_rest_response,
				$function, $class, $line);
			return;
		}
		$out = $message;
		if ($function != null ) {
			$out .= ' in ';
			$out .= ($class != null ) ? $class . '::' : '';
			$out .= $function;
			$out .= ($line != null ) ? ':'. $line : '';
		}
		$data = $wp_rest_response->get_data();
		if (empty($data['message'])) {
			if (!empty($data['body_response'])) {
				$json_data = json_decode($data['body_response'], true);
				if (isset($json_data['errors'])) {
					$dmessage = $json_data['errors'][0];
				} else {
					$dmessage = 'json no message';
				}
			} else {
				$dmessage = 'no message';
			}
		} else {
			$dmessage = $data['message'];
		}
		$out .= " status: " . $wp_rest_response->get_status() . " message: " . $dmessage;
		self::log_error($out);
	}

	/**
	 * used to provide useful information for debugging (logging)
	 *
	 * @param array $array_val
	 *
	 * @return string
	 */
	public static function array_to_string( array $array_val) :string {
		return implode(', ', array_map(
			fn($key, $value) => "$key => $value",
			array_keys($array_val),
			$array_val
		));
	}
}