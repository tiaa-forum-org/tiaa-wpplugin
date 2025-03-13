<?php
/**
 * OptionsUtilities Class
 *
 * This file contains the OptionsUtilities class, which provides utilities for managing options groups in the TIAAPlugin namespace.
 *
 * @package TIAAPlugin\lib
 * @author Lew Grothe, TIAA Admin Platform sub-team
 * @link https://tiaa-forum.org/contact
 * @since 0.0.3
 */

// Define the namespace for the class.
namespace TIAAPlugin\lib;

/**
 * Class OptionsUtilities
 *
 * This class provides utilities for managing options groups in the TIAAPlugin namespace.
 *
 * @package TIAAPlugin\lib
 * @since 0.0.3
 */
class OptionsUtilities {
	/**
	 * The in-memory array of option groups.
	 *
	 * Each option group is represented as an associative array, with configurations for specific functionalities.
	 *
	 * @var array $option_groups The default option groups and their configurations.
	 */
	public static array $option_groups = array(
		TIAA_CONNECT_GROUP => // Needs to be admin privilege
			array(
				'url'       => '',
				'api_key'   => '',
				'username'  => '',
			),
		TIAA_INVITE_GROUP =>
			array(
				'url'             => '',
				'api_key'         => '',
				'username'        => '',
				'invite_post_id'  => '',
			),
		TIAA_GROUP_LIST_GROUP =>
			array(
				'group_list' => [],
			),
		TIAA_LOGGING_GROUP =>
			array(
				'file_path' => '/var/log/tiaa-wpplugin.log',
				'log_level' => 5,
			),
		TIAA_SCREENED_EMAIL_GROUP =>
			array(
				'email_list'          => array('<EMAIL>'),
				'max_hits_per_email'  => 100,
				'max_hits_per_day'    => 10,
				'max_total_hits'      => 1000,
			),
		TIAA_WELCOME_GROUP =>
			array(
				'url'                    => '',
				'api_key'                => '',
				'username'               => '',
				'scan_rate'              => 7,
				'day_of_week'            => 'Monday',
				'days_since_joined_min'  => 7,
				'days_since_joined_max'  => 30,
				'post_id'                => 19,
				'welcome_post_title'     => 'Welcome to the TIAA Forum',
				'group_list'             => [], // List of excluded groups
			),
	);

	/**
	 * Static array holding the configuration for group invite option groups.
	 *
	 * This configuration allows for group-specific invitations. For this to work, the group must be set as each member's
	 * "primary group," enabling special behavior on login, such as landing on that group's designated category.
	 *
	 * @var array $group_invite_groups Configuration for group invite option groups.
	 */
	public static array $group_invite_groups = array(
		TIAA_GROUP_INVITE_GROUP => // Actually "TIAA_GROUP_INVITE_GROUP" + "group_name"
			array(
				'group_name'     => '',
				'url'            => '',
				'api_key'        => '',
				'username'       => '',
				'invite_post_id' => '',
			),
	);

	/**
	 * Constructor for the OptionsUtilities class.
	 *
	 * Adds the `options_init` function to the `plugins_loaded` WordPress hook.
	 *
	 * @since 0.0.3
	 */
	public function __construct() {
		add_action('plugins_loaded', array(__CLASS__, 'options_init'));
	}

	/**
	 * Initialize the options groups.
	 *
	 * This function initializes all the defined options groups by retrieving their stored values from the database.
	 * If no value exists in the database, it uses the default configuration defined in `$option_groups`.
	 *
	 * - If the options group is `TIAA_GROUP_LIST_GROUP`, the function retrieves additional group-specific options.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public static function options_init(): void {
		// Iterate through and initialize each option group.
		foreach (self::$option_groups as $group_id => &$optary) {
			$optaryx = get_option($group_id); // Retrieve option group from the database.

			if (empty($optaryx)) {
				// Add option group to the database if not already present.
				add_option($group_id, self::$option_groups[$group_id]);
			} else {
				// Use stored configuration if it exists.
				$optary = $optaryx;
			}

			/*
			 * Handle the special `TIAA_GROUP_LIST_GROUP` options group.
			 * This group contains lists of groups to invite, which are processed further here.
			 */
			if ($group_id === TIAA_GROUP_LIST_GROUP) {
				$group_array = $optaryx;

				foreach ($group_array as $group_list) {
					foreach ($group_list as $group_name) {
						$real_group_name = TIAA_GROUP_INVITE_GROUP . $group_name; // Construct real group name.
						$group_optionx = get_option($real_group_name); // Retrieve group-specific option.

						if (empty($group_optionx)) {
							// Add group-specific option to the database if not already present.
							self::$group_invite_groups[TIAA_GROUP_INVITE_GROUP]['group_name'] = $group_name;
							add_option($real_group_name, self::$group_invite_groups[TIAA_GROUP_INVITE_GROUP]);
							$group_optionx = get_option($real_group_name);
						}

						// Add group-specific configuration to the main `$option_groups` array.
						self::$option_groups[$real_group_name] = $group_optionx;
					}
				}
			}
		}
	}
}