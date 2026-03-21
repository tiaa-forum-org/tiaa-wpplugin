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
	const  TIAA_CRON_HOOK = 'tiaa_wp_welcome_cron_hook';

	/**
	 * The table name for storing welcome message logs.
	 *
	 * @since 0.0.3
	 * @var string
	 */
	const  TIAA_WELCOME_TABLE = 'tiaa_welcome_log';

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
		// Bug 1 Fixed: ALWAYS register the action on every page load,
		// unconditionally — registration is not the same as scheduling.
		add_action( self::TIAA_CRON_HOOK, [ __CLASS__, 'static_run_cron' ] );

		$tiaa_welcome_cron_status = get_option( TIAA_WELCOME_GROUP_CRON );
		$next_run = wp_next_scheduled( self::TIAA_CRON_HOOK );
		// Bug 2 Fixed: compare bool to bool, not bool to string 'true'.
		if ( ! $next_run && $tiaa_welcome_cron_status === true ) {
			self::log_debug( 'WP Cron hook registered for welcome feature: ' . current_action() );
		}
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

		if ( strlen($wpdb->last_error) > 2 ) {
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
		$scan_rate = $this->options['scan_rate'];
		$cron_interval = $this->options['cron_interval'] ?? 'daily';

		self::log_debug( "Scheduling welcome cron job — interval: $cron_interval, start offset: $scan_rate ..." );

		if ( ! wp_next_scheduled( self::TIAA_CRON_HOOK ) ) {
			$unit_seconds = $this->get_interval_seconds();
			$start_time   = time() + ( $scan_rate * $unit_seconds );

			wp_schedule_event( $start_time, $cron_interval, self::TIAA_CRON_HOOK );
			self::log_debug( "Event set: " . $this->get_cron_status() );
		}
		self::enable_cron();
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
		wp_clear_scheduled_hook( self::TIAA_CRON_HOOK );
		self::disable_cron();
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
		// Guard for direct invocation (e.g. WP-CLI) where the constructor
		// may not have run on this page load.
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
		$unit_seconds = $this->get_interval_seconds();

		$post_id = $this->options['post_id'];
		$group_list      = $this->options['group_list'];
		self::log_debug( 'Running welcome cron- > min days:' . $min_days . ' max days:' . $max_days  );
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
			if ( $this->is_within_days_range( $member['created_at'], $min_days, $max_days, $unit_seconds ) ) {
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
					if ( ! empty($group_name) && self::is_in_excluded_group( $group_name, $group_list ) ) {
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
	private function enable_cron() : void {
		update_option( TIAA_WELCOME_GROUP_CRON, true );
	}
	private function disable_cron() : void {
		update_option( TIAA_WELCOME_GROUP_CRON, false );
	}
	/**
	 * Determines if the time elapsed since a member joined falls within
	 * the configured min/max threshold range.
	 *
	 * The comparison unit is determined by the cron interval so that
	 * threshold values scale correctly during testing:
	 * - Daily (production): thresholds are in days
	 * - Hourly (testing):   thresholds are in hours
	 * - Every 5 min (testing): thresholds are in 5-minute periods
	 *
	 * @param string $created_at    Member join date as a parseable date string.
	 * @param int    $min_days      Minimum periods elapsed before sending.
	 * @param int    $max_days      Maximum periods elapsed — member is skipped after this.
	 * @param int    $unit_seconds  Seconds per period — pass result of get_interval_seconds().
	 *
	 * @return bool True if elapsed periods fall within range, false otherwise.
	 * @since  0.0.3
	 * @since  0.0.4 Added $unit_seconds parameter to support scaled testing intervals.
	 */
	private function is_within_days_range( string $created_at,
		int $min_days, int $max_days, int $unit_seconds = DAY_IN_SECONDS ): bool {
		$periods_elapsed = floor( ( time() - strtotime( $created_at ) ) / $unit_seconds );
		return $periods_elapsed >= $min_days && $periods_elapsed <= $max_days;
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
		return $this->options['message_title'] ?? '';
	}
	/**
	 * Get recent log entries from the welcome message log.
	 *
	 * Queries the database for the latest entries in the `tiaa_welcome_log`
	 * table, ordered by processing date.
	 *
	 * This method is specifically called from the PHP template file
	 * `admin/views/welcome-data-view.php`.
	 * Ensure that changes to the logic here are reflected in that template
	 * and any related functionality.
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
		if ( $this === null ) {
			return null;
		}
		$query = $this->wpdb->prepare(
			"SELECT member_id, username, email, group_name, date_created, date_processed, status 
        FROM {$this->table_name}
        ORDER BY date_processed DESC
        LIMIT %d",
			$limit
		);

		// Execute the SQL and fetch results.
		return $this->wpdb->get_results( $query );
	}
	/**
	 * Returns the number of seconds representing one "period" for the
	 * currently configured cron interval.
	 *
	 * Used to scale min/max threshold comparisons so that the same
	 * numeric values (e.g. min=7, max=30) work proportionally across
	 * all cron intervals — daily in production, hourly or every-5-minutes
	 * during testing.
	 *
	 * Production (daily):         7 periods = 7 days
	 * Testing (hourly):           7 periods = 7 hours
	 * Testing (every_5_minutes):  7 periods = 35 minutes
	 *
	 * ⚠️  The hourly and every_five_minutes cases exist solely to support
	 * accelerated testing. Remove those match arms when the test interval
	 * support is removed from TiaaHooks::register_test_cron_intervals().
	 *
	 * @since  0.0.4
	 * @return int Seconds per cron period.
	 *
	 * @see    TiaaHooks::register_test_cron_intervals()
	 * @todo   Remove non-daily match arms before production release.
	 */
	private function get_interval_seconds(): int {
		$interval = $this->options['cron_interval'] ?? 'daily';
		return match( $interval ) {
			'hourly'             => HOUR_IN_SECONDS,
			'every_five_minutes' => 300,
			default              => DAY_IN_SECONDS,
		};
	}
}