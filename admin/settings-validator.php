<?php

/**
 * Provides validation for plugin settings.
 *
 * This class contains multiple validation functions used throughout the plugin's admin interface.
 * Each validation function ensures the provided input meets specific requirements, such as valid
 * URLs, API keys, usernames, and more.
 *
 * @package TIAAPlugin
 * @subpackage Admin
 * @author Lew Grothe, TIAA Admin Platform sub-team
 * @link https://tiaa-forum.org/contact
 * @since 0.0.3
 */

namespace TIAAPlugin\Admin;

use TIAAPlugin\lib\PluginUtil;

/**
 * Class SettingsValidator
 *
 * Responsible for validating TIAA plugin settings. Adds WordPress filters
 * for validating different types of inputs, such as URLs, API keys, usernames, emails, etc.
 *
 * @since 0.0.3
 */
class SettingsValidator {

	use PluginUtil;

	/**
	 * Contains the plugin options.
	 *
	 * Accessed during validation to read and validate any stored options.
	 *
	 * @var array|void $options The current plugin options.
	 * @since 0.0.3
	 */
	protected $options;

	/**
	 * SettingsValidator constructor.
	 *
	 * Hooks into WordPress actions and filters to add validation callbacks for the various settings.
	 *
	 * @since 0.0.3
	 */
	public function __construct() {
		// Initialize plugin options on the admin initialization hook.
		add_action( 'admin_init', array( $this, 'setup_options' ) );

		// Add validation filters for a variety of inputs.
		add_filter( 'tiaa_validate_url', array( $this, 'validate_url' ) );
		add_filter( 'tiaa_validate_api_key', array( $this, 'validate_api_key' ) );
		add_filter( 'tiaa_validate_post_id', array( $this, 'validate_post_id' ) );
		add_filter( 'tiaa_validate_username', array( $this, 'validate_username' ) );
		add_filter( 'tiaa_validate_url_blank_ok', array( $this, 'validate_url_blank_ok' ) );
		add_filter( 'tiaa_validate_api_blank_ok', array( $this, 'validate_api_key_blank_ok' ) );
		add_filter( 'tiaa_validate_username_blank_ok', array( $this, 'validate_username_blank_ok' ) );
		add_filter( 'tiaa_validate_post_id_blank_ok', array( $this, 'validate_post_id_blank_ok' ) );
		add_filter( 'tiaa_validate_group_list', array( $this, 'validate_group_list' ) );
		add_filter( 'tiaa_validate_group_list_blank_ok', array( $this, 'validate_group_list_blank_ok' ) );
		add_filter( 'tiaa_validate_email', array( $this, 'validate_email' ) );
		add_filter( 'tiaa_validate_email_list', array( $this, 'validate_email_list' ) );
		add_filter( 'tiaa_validate_screen_list', array( $this, 'validate_screen_list' ) );
		add_filter( 'tiaa_validate_file_path', array( $this, 'validate_file_path' ) );
	}

	/**
	 * Initializes the plugin options.
	 *
	 * This method retrieves all plugin options and stores them in the `$options` property
	 * for use during validation.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function setup_options() : void {
		$this->options = $this->get_all_options();
	}
	public function validate_file_path( string $input ) : string {
		if ( ! isset( $input ) || $input === '' ) {
			add_settings_error(
				'tiaa_wpplugin_options',
				'file_path',
				'File path must be set.'
			);
		} elseif ( is_writable($input) ) {
			return $input;
		} else {
			$dir = dirname($input);
			if ( ! is_writable($dir) ) {
				add_settings_error(
					'tiaa_wpplugin_options',
					'file_path',
					'Directory (' . $dir . ')  not a writeable directory.' );
				$input = '';
			} else {
				add_settings_error(
					'tiaa_wpplugin_options',
					'file_path',
					'File ' . $input . ' created.',
					'success' );
				@fopen($input, 'w');
			}
		}
		return $input;
	}

	/**
	 * Validates the Discourse URL.
	 *
	 * Ensures that the provided URL begins with `http:` or `https:`, is properly sanitized,
	 * and represents a valid URL format.
	 *
	 * @since 0.0.3
	 *
	 * @param string $input The input URL to be validated.
	 * @return string The sanitized URL or an empty string if validation fails.
	 */
	public function validate_url( string $input ) : string {
		$regex = '/^(http:|https:)/';

		// Ensure the URL starts with a valid protocol.
		if ( ! preg_match( $regex, $input ) ) {
			add_settings_error(
				'tiaa_wpplugin_options',
				'url',
				'The Discourse URL needs to be set to a valid URL that begins with either \'http:\' or \'https:\'.'
			);

			$url = '';
		} else {
			$url = untrailingslashit( esc_url_raw( $input ) );

			// Validate the URL's structure.
			if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				add_settings_error(
					'tiaa_wpplugin_options',
					'url',
					'The URL you provided is not a valid URL.'
				);
			}
		}

		return $url;
	}

	/**
	 * Validates an API key.
	 *
	 * Ensures that the API key contains only letters and numbers, trims surrounding whitespace,
	 * and provides feedback if the field is missing or invalid.
	 *
	 * @since 0.0.3
	 *
	 * @param string $input The input API key to be validated.
	 * @return string The sanitized API key or an empty string if validation fails.
	 */
	public function validate_api_key( string $input ) : string {
		$regex = '/^\s*([0-9]*[a-z]*|[a-z]*[0-9]*)*\s*$/';

		if ( empty( $input ) ) {
			add_settings_error(
				'tiaa_wpplugin_options',
				'api_key',
				'You must provide an API key.'
			);
			$api_key = '';
		} else {
			$api_key = trim( $input );

			// Validate the input format.
			if ( ! preg_match( $regex, $input ) ) {
				add_settings_error(
					'tiaa_wpplugin_options',
					'api_key',
					'The API key you provided is not valid.'
				);
				$api_key = '';
			}
		}
		return $api_key;
	}

	/**
	 * Validates a username.
	 *
	 * Ensures that a valid Discourse username is provided, sanitizes the input,
	 * and adds an error if the input is empty.
	 *
	 * @since 0.0.3
	 *
	 * @param string $input The input username to be validated.
	 * @return string The sanitized username or an empty string if validation fails.
	 */
	public function validate_username( string $input ) : string {
		if ( ! empty( $input ) ) {
			$username = sanitize_text_field( $input );
		} else {
			add_settings_error(
				'tiaa_wpplugin_options',
				'username',
				'You need to provide a Discourse username.'
			);

			$username = '';
		}

		return $username;
	}

	/**
	 * Validates the Discourse URL, allowing blank inputs.
	 *
	 * If the input is not blank, this method validates the URL format and ensures
	 * it starts with `http:` or `https:`.
	 *
	 * @since 0.0.3
	 *
	 * @param string $input The input URL to be validated.
	 * @return string The sanitized URL or an empty string if blank or validation fails.
	 */
	public function validate_url_blank_ok( string $input ) : string {
		$regex = '/^(http:|https:)/';

		if ( ! isset( $input ) || $input === '' ) {
			$url = '';
		} elseif ( ! preg_match( $regex, $input ) ) {
			add_settings_error(
				'tiaa_wpplugin_options',
				'url',
				'The Discourse URL needs to be set to a valid URL that begins with either \'http:\' or \'https:\'.'
			);
			$url = '';
		} else {
			$url = untrailingslashit( esc_url_raw( $input ) );
		}

		return $url;
	}

	/**
	 * Validates the API key, allowing blank values.
	 *
	 * Ensures the API key matches the expected pattern and trims unnecessary white spaces.
	 *
	 * @since 0.0.3
	 *
	 * @param string $input The input API key to validate.
	 * @return string The validated or sanitized API key, or an empty string if invalid/blank.
	 */
	public function validate_api_key_blank_ok( string $input ) : string {
		$regex = '/^\s*([0-9]*[a-z]*|[a-z]*[0-9]*)*\s*$/';
		$input = trim( $input );

		if ( empty( $input ) ) {
			$api_key = '';
		} else {
			$api_key = $input;

			if ( ! preg_match( $regex, $input ) ) {
				add_settings_error( 'tiaa_wpplugin_options', 'api_key', 'The API key you provided is not valid.' );
				$api_key = '';
			}
		}

		return $api_key;
	}

	/**
	 * Validates a username, allowing blank values.
	 *
	 * Sanitizes the provided username or returns an empty string if the input is blank.
	 *
	 * @since 0.0.3
	 *
	 * @param string $input The input username to validate.
	 * @return string Sanitized username or an empty string if blank.
	 */
	public function validate_username_blank_ok( string $input ) : string {
		if ( ! empty( $input ) ) {
			$username = sanitize_text_field( $input );
		} else {
			$username = '';
		}

		return $username;
	}

	/**
	 * Validates an integer input.
	 *
	 * Ensures the value is a valid integer and falls within the specified range, if provided.
	 * Adds error messages to the settings API if validation fails.
	 *
	 * @since 0.0.3
	 *
	 * @param int    $input         The input to be validated.
	 * @param string|null $option_id     The identifier for the setting being validated.
	 * @param int|null $min         Minimum allowed value.
	 * @param int|null $max         Maximum allowed value.
	 * @param string $error_message Error message to be displayed if validation fails.
	 * @param bool   $add_error     Whether to add an error message to the settings API.
	 * @return int|null The validated integer or null if invalid.
	 */
	protected function validate_int( int $input, ?string $option_id = null, ?int $min = null,
		?int $max = null, string $error_message = '', bool $add_error = false ) : ?int {
		$options = array();

		if ( isset( $min ) ) {
			$options['min_range'] = $min;
		}
		if ( isset( $max ) ) {
			$options['max_range'] = $max;
		}

		$input = filter_var(
			$input,
			FILTER_VALIDATE_INT,
			array(
				'options' => $options,
			)
		);

		if ( false === $input ) {
			if ( $add_error ) {
				add_settings_error( 'tiaa_options_page', $option_id, $error_message );
			}

			return null;
		}

		return $input;
	}

	/**
	 * Validates the post ID input (range: 10–1,000,000 by default).
	 *
	 * @since 0.0.3
	 *
	 * @param int|string $input The post ID input to validate.
	 * @return int|string The sanitized post ID or an empty string if invalid.
	 */
	public function validate_post_id( int|string $input ) : int|string {
		if ( isset( $input ) && $input != '' ) {
			return $this->validate_int(
				$input,
				'post_id',
				10,
				1000000,
				'The post ID number must be between 10 and 1,000,000.',
				true
			);
		} else {
			return '';
		}
	}

	/**
	 * Validates the post ID input, allowing blank values.
	 *
	 * The input is validated to ensure it's within the expected range or blank if not provided.
	 *
	 * @since 0.0.3
	 *
	 * @param int|null $input The post ID input to validate.
	 * @return int|string The validated post ID or an empty string if blank.
	 */
	public function validate_post_id_blank_ok(int|null $input ) : int|string {
		if ( isset( $input ) && $input != '' ) {
			return $this->validate_int(
				$input,
				'post_id',
				10,
				1000000,
				'The post ID number must be between 10 and 1,000,000.',
				true
			);
		} else {
			return '';
		}
	}

	/**
	 * Validates a group list input.
	 *
	 * Splits a comma-separated string into an array of trimmed values.
	 * Returns an empty array if the input is null.
	 * TODO - should probably make sure that it's a valid group with Discourse or invites might fail
	 * @since 0.0.3
	 *
	 * @param string|null $input The input string to validate.
	 * @return array The validated group list.
	 */
	public function validate_group_list( string|null $input ) : array {
		if ( !isset($input) || $input == null ) {
			return array();
		}

		$output = array_map( 'trim', explode( ',', $input ) );

		return $output;
	}

	/**
	 * Validates a group list input, allowing blank values.
	 *
	 * Uses the `validate_group_list` function to handle the input.
	 *
	 * @since 0.0.3
	 *
	 * @param string|null $input The input string to validate.
	 * @return array The validated group list.
	 */
	public function validate_group_list_blank_ok( string|null $input ) : array {
		return self::validate_group_list( $input );
	}

	/**
	 * Validates an email address.
	 *
	 * Checks if the input contains an '@' symbol and is not blank.
	 * Adds a validation error if the email is invalid.
	 *
	 * @since 0.0.3
	 *
	 * @param string $input The email address to validate.
	 * @return string The validated email or an empty string if invalid.
	 */
	public function validate_email( $input ) {
		if ( isset( $input ) && $input != '' && str_contains( $input, '@' ) ) {
			return $input;
		}

		add_settings_error(
			TIAA_SCREENED_EMAIL_GROUP,
			'email',
			'Not a valid email address. Please enter a valid email address.'
		);

		return '';
	}

	/**
	 * Validates a list of email addresses.
	 *
	 * Splits a comma-separated string into an array of trimmed email addresses.
	 * Ensures all items contain valid email addresses, adding a validation error if any are invalid.
	 *
	 * @since 0.0.3
	 *
	 * @param string|null $input The input string to validate.
	 * @return array The validated list of email addresses, or an empty array if invalid.
	 */
	public function validate_email_list( $input ) {
		if ( $input == null ) {
			return array();
		}

		$output = array();
		if ( isset( $input ) && $input != null ) {
			$output = array_map( 'trim', explode( ',', $input ) );

			foreach ( $output as $email ) {
				if ( ! str_contains( $email, '@' ) ) {
					add_settings_error(
						TIAA_SCREENED_EMAIL_GROUP,
						'email',
						'Not a valid email address. Please enter a valid email address.'
					);

					return array();
				}
			}
		}

		return $output;
	}

	/**
	 * Displays an error for the screen list validation.
	 *
	 * This function is not yet implemented and currently returns `true`.
	 *
	 * @since 0.0.3
	 *
	 * @param mixed $input The input to validate.
	 * @return bool Always returns true.
	 */
	public function validate_screen_list( $input ) {
		add_settings_error(
			'tiaa_options_page',
			'screen_list',
			'The screen list is not yet implemented.'
		);

		return true;
	}
}