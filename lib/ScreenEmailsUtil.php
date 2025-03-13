<?php
/**
 * ScreenEmailsUtil Class.
 *
 * This file contains the class `ScreenEmailsUtil` that provides utility functions
 * for managing a table of screened emails, including creating the table, checking
 * emails, and updating records.
 *
 * @package TIAAPlugin
 * @author Lew Grothe, TIAA Admin Platform sub-team
 * @link https://tiaa-forum.org/contact
 *
 */

namespace TIAAPlugin;

use wpdb;

/**
 * Class ScreenEmailsUtil
 *
 * Provides methods to manage a database table that tracks screened emails.
 * Includes operations to create the table, check email existence, update records,
 * and retrieve relevant information.
 *
 * @since 0.0.3
 */
class ScreenEmailsUtil {

	/**
	 * The WordPress database instance.
	 *
	 * @since 0.0.3
	 * @var wpdb $wpdb WordPress database instance.
	 */
	protected wpdb $wpdb;

	/**
	 * The name of the table for screened emails.
	 *
	 * @since 0.0.3
	 * @var string $table_name Name of the screened emails table.
	 */
	protected string $table_name;

	/**
	 * Constructor for the ScreenEmailsUtil class.
	 *
	 * Initializes the WordPress database instance and table name, and creates
	 * the `tiaa_screened_emails` table if it does not already exist.
	 *
	 * @since 0.0.3
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->table_name = TIAA_SCREENED_EMAILS_TABLE;
		$this->create_table();
	}

	/**
	 * Creates the `tiaa_screened_emails` table if it doesn't already exist.
	 *
	 * This function checks if the screened emails table exists in the database.
	 * If the table is not found, it creates it using the `dbDelta` function.
	 *
	 * Table Structure:
	 * - `ID`: Primary key, auto-incrementing.
	 * - `email`: Screened email address.
	 * - `hit_count`: Number of times this email has been screened.
	 * - `date_time_added`: Timestamp when this record was added.
	 * - `date_time_last_access`: Timestamp when this record was last accessed.
	 * - `notes`: Optional notes about the email.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	protected function create_table() {
		if ( $this->wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name}'" ) != $this->table_name ) {
			$sql = "CREATE TABLE {$this->table_name} (
				ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				email VARCHAR(255) NOT NULL,
				hit_count INT UNSIGNED DEFAULT 0,
				date_time_added DATETIME DEFAULT CURRENT_TIMESTAMP,
				date_time_last_access DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				notes VARCHAR(255) DEFAULT NULL,
				PRIMARY KEY (ID)
			) " . $this->wpdb->get_charset_collate() . ';';

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/**
	 * Retrieves the WordPress database handle.
	 *
	 * @since 0.0.3
	 * @return wpdb The WordPress database instance.
	 */
	public function getDBHandle() : wpdb {
		return $this->wpdb;
	}

	/**
	 * Retrieves the name of the screened emails table.
	 *
	 * @since 0.0.3
	 * @return string The name of the table.
	 */
	public function getTableName() : string {
		return $this->table_name;
	}

	/**
	 * Checks whether the given email exists in the screened emails table.
	 *
	 * If the email exists:
	 * - Increments the `hit_count` column for the email.
	 * - Updates the `date_time_last_access` column to the current time.
	 * If the email does not exist:
	 * - Returns false.
	 *
	 * All email input is sanitized and converted to lowercase for consistency.
	 *
	 * @since 0.0.3
	 * @param string $email The email address to screen for existence.
	 * @return bool True if the email is found and updated, false otherwise.
	 */
	public function is_screened_email(string $email): bool {
		// Sanitize the email input
		$email = sanitize_email($email);

		// Convert the email to lowercase for consistency
		$email = strtolower($email);

		// Check if the email exists in the table
		$email_record = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT ID, hit_count FROM {$this->table_name} WHERE email = %s;",
				$email
			)
		);

		if ($email_record) {
			// If found, increment the hit_count
			// date_time_last_accessed is automatically set to current
			$this->wpdb->update(
				$this->table_name,
				['hit_count' => $email_record->hit_count + 1],
				['ID' => $email_record->ID],
				['%d'],
				['%d']
			);

			return true; // Email is screened and updated
		}

		return false; // Email not screened
	}
}