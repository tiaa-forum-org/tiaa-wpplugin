<?php
/**
 * Welcome Utility Class
 *
 * Provides utility functions for handling welcome messages, cron job scheduling,
 * database operations, and interaction with the Discourse API.
 *
 * @package    TIAAPlugin
 * @subpackage TIAAPlugin\lib
 * @author     Lew Grothe, TIAA Admin Platform sub-team
 * @link       https://tiaa-forum.org/contact
 * @since      0.0.3
 */

namespace TIAAPlugin\lib;

use wpdb;
/**
 * Class WelcomeUtil
 *
 * This class provides methods to manage the database table for welcome messages (`tiaa_welcome_log`),
 * schedule and unschedule WordPress cron jobs for processing welcome messages, and handle integrations
 * with the Discourse API for retrieving member data and sending messages.
 *
 * @since 0.0.3
 */
class WelcomeUtil {
	use PluginUtil;

	/**
	 * allows us to have a static run_cron()
	 *
	 * @var null|WelcomeUtil instance
	 */
	 protected static ?WelcomeUtil $instance = null;
	/**
	 * Cron hook identifier for scheduling welcome message jobs.
	 *
	 * @since 0.0.3
	 * @var string
	 */
	const TIAA_CRON_HOOK = 'tiaa_wp_welcome_cron_hook';

	/**
	 * The table name for storing welcome message logs.
	 *
	 * @since 0.0.3
	 * @var string
	 */
	const TIAA_WELCOME_TABLE = 'tiaa_welcome_log';

	/**
	 * The WordPress database instance.
	 *
	 * @since 0.0.3
	 * @var wpdb $wpdb WordPress database.
	 */
	protected wpdb $wpdb;

	/**
	 * Utility instance for enhanced database and cron operations.
	 *
	 * @since 0.0.3
	 * @var WelcomeUtil|null
	 */
	protected ?WelcomeUtil $Util = null;

	/**
	 * The name of the table for storing welcome logs.
	 *
	 * @since 0.0.3
	 * @var string
	 */
	protected string $table_name;

	/**
	 * Options for the welcome feature.
	 *
	 * Retrieves settings and configurations for the plugin.
	 *
	 * @since 0.0.3
	 * @var array
	 */
	protected array $options;

	/**
	 * Constructor for the WelcomeUtil class.
	 *
	 * Initializes the database, table name, plugin options, and sets up
	 * necessary actions like registering cron hooks and creating database tables.
	 *
	 * @since 0.0.3
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb       = $wpdb;
		$this->table_name = $this->wpdb->prefix . self::TIAA_WELCOME_TABLE;

		// Fetch settings options.
		$this->options = self::get_options_by_group( TIAA_WELCOME_GROUP );

		// Register cron jobs.
		add_action( self::TIAA_CRON_HOOK, [ __CLASS__, 'static_run_cron' ] );
		self::log_debug( 'WP Cron hook registered for welcome feature: ' . current_action() );

		// Ensure the database table exists.
		$this->create_log_table( $wpdb );
		
		if (self::$instance === null) {
			self::$instance = $this;
		}
	}

	/**
	 * Creates or updates the `tiaa_welcome_log` database table.
	 *
	 * This method ensures that the table required for logging welcome messages
	 * exists and has the correct structure.
	 *
	 * @param wpdb $wpdb WordPress database instance.
	 *
	 * @since 0.0.3
	 */
	private function create_log_table( wpdb $wpdb ): ?array {
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            member_id BIGINT(20) NOT NULL,
            username VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            group_name VARCHAR(255) DEFAULT NULL,
            date_created DATETIME NOT NULL,
            date_processed DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status ENUM('sent','skipped','error1','error2') NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$result = dbDelta( $sql );

		if ( false === $result ) {
			error_log( 'Table creation failed: ' . $wpdb->last_error );
		}

		return $result;
	}

	/**
	 * Schedules the WP Cron for the welcome feature.
	 *
	 * Checks if the cron job is already scheduled; if not, it schedules it
	 * to run hourly starting from the next full hour.
	 *
	 * @since 0.0.3
	 */
	public function schedule_cron(): void {
		self::log_debug( 'Scheduling welcome cron job for every hour...' );
		if ( ! wp_next_scheduled( self::TIAA_CRON_HOOK ) ) {
			$start_time = strtotime( '+1 hour', time() );
			$start_time = strtotime( date( 'Y-m-d H:00:00', $start_time ) ); // Round to the next hour.
			wp_schedule_event( $start_time, 'hourly', self::TIAA_CRON_HOOK );
		}
	}

	/**
	 * Unschedule the WP Cron hook for the welcome feature.
	 *
	 * Removes the scheduled cron job if it is currently active.
	 *
	 * @since 0.0.3
	 */
	public function unschedule_cron(): void {
		self::log_debug( 'Unscheduled welcome cron job...' );
		$timestamp = wp_next_scheduled( self::TIAA_CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::TIAA_CRON_HOOK );
		}
	}

	/**
	 * Retrieves the current status of the welcome cron job.
	 *
	 * If the cron job is scheduled, the next scheduled time is returned.
	 * Otherwise, it indicates that the cron job is unscheduled.
	 *
	 * @return string The status of the welcome cron job.
	 * @since 0.0.3
	 */
	public function get_cron_status(): string {
		$timestamp = wp_next_scheduled( self::TIAA_CRON_HOOK );
		if ( $timestamp ) {
			$date_time = date( 'Y-m-d H:i:s', $timestamp );
			self::log_debug( 'Welcome cron status - scheduled at: ' . $date_time );

			return 'scheduled at: ' . $date_time;
		} else {
			self::log_debug( 'Welcome cron job status - not scheduled...' );

			return 'unscheduled';
		}
	}

	 /**
	 * Static method to execute the welcome cron job.
	 *
	 * Ensures that the singleton instance is initialized and triggers the `run_cron` method.
	 * This method is designed to be the cron callback for the scheduled event.
	 *
	 * @return void
	 * @since 0.0.3
	 */
	public static function static_run_cron( ): void {
		if (self::$instance === null) {
			self::$instance = new WelcomeUtil();
		}
		self::$instance->run_cron();
	}

	/**
	 * Executes the welcome cron job.
	 *
	 * Runs the periodic logic to fetch recent Discourse members, retrieve the
	 * welcome message, and send the message to the appropriate users.
	 *
	 * @since 0.0.3
	 */
	public function run_cron() : void {
		self::log_debug( 'Running welcome cron job...' );

		$min_days        = $this->options['days_since_joined_min'];
		$max_days        = $this->options['days_since_joined_max'];
		$post_id = $this->options['post_id'];
		$group_list      = $this->options['group_list'];
		// Fetch recent members from Discourse API
		$recent_members = Discourse::get_recent_members( $max_days );
		if ( ! $recent_members ) {
			self::log_debug( 'No recent members found.' );

			return;
		} else {
			self::log_debug( 'Found ' . count( $recent_members ) . ' recent members.' );
		}

		$result = Discourse::get_discourse_post_by_id( $post_id, TIAA_WELCOME_GROUP );
		if ( is_wp_error( $result ) || $result->get_status() !== 200 ) {
			self::log_wp_rest_response_error( 'Getting welcome post: ', $result,
				__FUNCTION__, __CLASS__, __LINE__ );

			return;
		}
		$data    = $result->get_data();
		$xdata   = json_decode( $data['body_response'], true );
		$message = self::parse_message( $xdata['raw'] );
		if ( ! $message ) {
			self::log_error( 'No welcome post found - post_id:' . $post_id );

			return;
		}
		$title = self::tiaa_get_title();
		foreach ( $recent_members as $member ) {
			if ( self::already_messaged( $member ) ) {
				self::log_debug( 'Already messaged member: ' . $member['username'] );
				continue;
			}
			// Check join date
			if ( $this->is_within_days_range( $member['created_at'], $min_days, $max_days ) ) {
				// we only need to get the user info if there are excluded group members
				$group_name = '';
				if ( $group_list ) {
					$response = Discourse::get_user_byname( $member['username'], TIAA_WELCOME_GROUP );
					if ( is_wp_error( $response ) || $response->get_status() !== 200 ) {
						self::log_wp_rest_response_error( 'Getting user info: ', $response,
							__FUNCTION__, __CLASS__, __LINE__ );
						self::log_to_database( $member, '', 'error1' );
						continue;
					}
					$users = json_decode( $response->get_data()['body_response'], true );
					if ( ! isset( $users ) || ! array_key_exists( 'user', $users ) ||
					     ! array_key_exists( 'primary_group_name', $users['user'] ) ) {
						self::log_error( 'No user group info parsed.' );
						self::log_to_database( $member, $group_name, 'error2' );
						continue;
					}
					$user_info  = $users['user'];
					$group_name = $user_info['primary_group_name'];
					// Check if member is in excluded group
					if ( self::is_in_excluded_group( $group_name, $group_list ) ) {
						self::log_to_database( $member, $group_name, 'skipped' );
						self::log_info( 'Skipped welcome message for member: ' . $member['name'] .
						                ' group: ' . $group_name );
						continue;
					}
				}
				// Send welcome message
				$response = Discourse::send_personal_message( $member['username'], $title, $message,
					TIAA_WELCOME_GROUP );
				// Log processing
				if ( is_wp_error( $response ) || $response->get_status() !== 200 ) {
					$this->log_to_database( $member, $group_name, 'error' );
					self::log_wp_rest_response_error( '', $response,
						__FUNCTION__, __CLASS__, __LINE__ );
				} else {
					$this->log_to_database( $member, $group_name, 'sent' );
					self::log_info( 'Sent welcome message for member: ' . $member['username'] );
				}
			} else {
				self::log_debug( 'Date out of range for member: ' . $member['username'] .
				                 ' join date: ' . $member['created_at'] );
			}
		}
		self::log_debug( 'Finished welcome cron job.' );
	}

	/**
	 * Helper to determine if a member's date is within the range.
	 *
	 * Determines if the number of days since a member joined falls within
	 * the specified minimum and maximum range.
	 *
	 * @param string $created_at The member's join date in string representation.
	 * @param int    $min_days The minimum number of days since joining.
	 * @param int    $max_days The maximum number of days since joining.
	 *
	 * @return bool True if the date is within range, false otherwise.
	 * @since 0.0.3
	 *
	 */
	private function is_within_days_range( string $created_at,
		int $min_days, int $max_days ) : bool {
		$days_since_joining = floor( ( time() - strtotime( $created_at ) ) / DAY_IN_SECONDS );

		return $days_since_joining >= $min_days && $days_since_joining <= $max_days;
	}

	/**
	 * Helper to log processed members to the database.
	 *
	 * This method records information about members who have been processed
	 * (e.g., sent a welcome message) into the `tiaa_welcome_log` database table.
	 *
	 * @param array       $member An array containing member details (ID, username, email, etc.).
	 * @param string|null $group_name The group name associated with the member (nullable).
	 * @param string      $status The processing status of the member's welcome message
	 *                               (e.g., `sent`, `skipped`, 'error', etc.).
	 *
	 * @return void
	 * @since 0.0.3
	 *
	 */
	private function log_to_database( array $member, ?string $group_name, string $status ) : void {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TIAA_WELCOME_TABLE;

		$wpdb->insert( $table_name, [
			'member_id'      => $member['id'],
			'username'       => $member['username'],
			'email'          => $member['email'],
			'group_name'     => $group_name,
			'date_created'   => $member['created_at'],
			'date_processed' => current_time( 'mysql' ),
			'status'         => $status,
		] );
	}

	/**
	 * Helper to check if a member belongs to an excluded group.
	 *
	 * Determines if the given group name is part of a predefined list of excluded groups.
	 *
	 * @param string $group_name The name of the group to check.
	 * @param array  $group_list An array of excluded group names.
	 *
	 * @return bool True if the group is excluded, false otherwise.
	 * @since 0.0.3
	 *
	 */
	private function is_in_excluded_group( string $group_name, array $group_list ) : bool {
		if ( in_array( $group_name, $group_list ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a message has already been sent to a member.
	 *
	 * Queries the database log to check if the member has already been
	 * successfully sent a welcome message.
	 *
	 * @param array $member An array containing the member ID and username.
	 *
	 * @return bool True if the member has already been messaged, false otherwise.
	 * @since 0.0.3
	 *
	 */
	private function already_messaged( array $member ) : bool {
		global $wpdb;
		$member_id  = $member['id'];
		$username   = $member['username'];
		$table_name = $wpdb->prefix . self::TIAA_WELCOME_TABLE;
		$result     = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE " .
				"member_id = %d AND username = %s ;",
				$member_id, $username
				)
			);
		if ( $result &&
		     ( $result->status === 'sent' || $result->status === 'skipped' )) {
			return true;
		}
		return false;
	}

	/**
	 * Retrieve the welcome message title.
	 *
	 * Fetches the message post title from the plugin options.
	 *
	 * @return string The welcome message post title.
	 * @since 0.0.3
	 *
	 */
	private function tiaa_get_title() : string {
		return $this->options['post_title'];
	}

	/**
	 * Get recent log entries from the welcome message log.
	 *
	 * Queries the database for the latest entries in the `tiaa_welcome_log`
	 * table, ordered by processing date.
	 *
	 *  This function is directly called in a PHP template file and may not
	 *  appear as "used" in some IDEs due to the dynamic nature of template inclusion.
	 *  Ensure to update both the logic here and any relevant template if changes are made.
	 *
	 * @param int $limit The maximum number of entries to retrieve.
	 *
	 * @return array|null An array of recent log entries or null if no results are found.
	 * @since 0.0.3
	 *
	 * @see ../admin/views/welcome-data-view.php
	 */
	public function get_recent_log_entries( int $limit ) : ?array {
		// Query the database table for the latest entries.
		$query = $this->wpdb->prepare(
			"SELECT member_id, username, email, group_name, date_created, date_processed, status 
        FROM {$this->table_name}
        ORDER BY date_processed DESC
        LIMIT %d",
			$limit
		);

		// Execute the SQL and fetch results.
		$results = $this->wpdb->get_results( $query );

		// Return results.
		return $results;
	}
}