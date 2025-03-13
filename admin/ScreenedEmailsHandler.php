<?php

/**
 * Handles the admin functionality for managing screened emails.
 *
 * @package TIAAPlugin
 * @subpackage admin
 * @author Lew Grothe, TIAA Admin Platform sub-team
 * @link https://tiaa-forum.org/contact
 * @since 0.0.3
 */

namespace TIAAPlugin\admin;

use TIAAPlugin\ScreenEmailsUtil;
use wpdb;

/**
 * Class ScreenedEmailsHandler
 *
 * Responsible for managing screened email functionalities within the admin dashboard.
 * Includes creating, listing, deleting, importing, and exporting screened emails.
 *
 * @since 0.0.3
 */
class ScreenedEmailsHandler {

	/**
	 * Holds the WordPress database instance.
	 *
	 * @var wpdb The global WordPress database object.
	 */
	protected wpdb $wpdb;

	/**
	 * Holds the name of the database table where screened emails are stored.
	 *
	 * @var string The name of the screened emails table.
	 */
	protected $table_name;

	/**
	 * Constructor for the ScreenedEmailsHandler class.
	 *
	 * Initializes the database connection and retrieves the name of the screened emails table
	 * from the utility class.
	 *
	 * @since 0.0.3
	 */
	public function __construct() {
		$screened_emails = new ScreenEmailsUtil();
		$this->wpdb = $screened_emails->getDBHandle();
		$this->table_name = $screened_emails->getTableName();
	}

	/**
	 * Renders the admin page for managing screened emails.
	 *
	 * This method ensures the current user has the required permissions,
	 * processes form submissions for email management, and displays the
	 * screened emails in the admin interface.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function render_screened_emails_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle form submissions for add, delete, export, or import actions.
		$this->handle_form_submissions();

		// Get the list of emails from the database.
		$emails = $this->wpdb->get_results( "SELECT * FROM {$this->table_name} ORDER BY date_time_added DESC" );

		add_settings_section(
			'screened_emails_list_section',
			'',
			array(
				$this,
				'screened_emails_list',
			),
			TIAA_SCREENED_EMAIL_GROUP
		);

		// Render the view.
		include_once plugin_dir_path( __FILE__ ) . '/views/screened-emails-view.php';

		register_setting(
			TIAA_SCREENED_EMAIL_GROUP,
			TIAA_SCREENED_EMAIL_GROUP,
			array(
				$this->validate_options(),
			)
		);
	}

	/**
	 * Validates the screened emails options.
	 *
	 * Adds validation errors to the WordPress settings API if the input data
	 * does not meet the required format or constraints.
	 *
	 * @since 0.0.3
	 * @return bool Always returns false to indicate validation is incomplete for now.
	 */
	private function validate_options()  {
		add_settings_error(
			'tiaa_wpplugin_options',
			'url',
			'The Discourse URL needs to be set to a valid URL that begins with either \'http:\' or \'https:\'.'
		);

		return false;
	}

	/**
	 * Displays the screened emails list section.
	 *
	 * Provides a brief description of the purpose of this section.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function screened_emails_list() : void {
		?>
        <p class="screened_emails_list_section_tab">
            These settings manage the emails to be screened.
        </p>
		<?php
	}
	/**
	 * Handles form submissions for adding, deleting, importing, or exporting emails.
	 */
	protected function handle_form_submissions(): void {
		// Add new email.
		if ( isset( $_POST['submit_email'] ) && ! empty( $_POST['email'] ) ) {
			$this->wpdb->insert(
				$this->table_name,
				array(
					'email'              => strtolower(sanitize_email( $_POST['email'] )),
					'hit_count'          => 0,
					'date_time_added'    => current_time( 'mysql' ),
					'date_time_last_access' => current_time( 'mysql' ),
					'notes'              => $_POST['notes'] ?? null,
				)
			);
		}

		// Delete email by ID.
		if ( isset( $_POST['delete_email_id'] ) ) {
			$this->wpdb->delete( $this->table_name, array( 'ID' => intval( $_POST['delete_email_id'] ) ) );
		}

		// Import from CSV.
		if ( isset( $_POST['import_csv'] ) && isset( $_FILES['csv_file'] ) ) {
			$file = $_FILES['csv_file']['tmp_name'];
			if ( $file ) {
				$handle = fopen( $file, 'r' );
				while ( ( $data = fgetcsv( $handle ) ) !== false ) {
					// Ensure the data array has the required number of elements
					$email = isset( $data[0] ) ? sanitize_email( $data[0] ) : null;
					// Skip invalid rows (e.g., missing required email field)
					if ( empty( $email ) ) {
						continue;
					}
					$hit_count = isset( $data[1] ) ? intval( $data[1] ) : 0; // Default to 0 if missing
					$date_time_added = $data[2] ?? current_time( 'mysql' );
					$date_time_last_access = $data[3] ?? current_time( 'mysql' );
					$notes = $data[4] ?? null;


					// Insert into database
					$this->wpdb->insert( $this->table_name, array(
						'email'              => $email,
						'hit_count'          => $hit_count,
						'date_time_added'    => $date_time_added,
						'date_time_last_access' => $date_time_last_access,
						'notes'              => $notes,
					) );
				}				fclose( $handle );
			}
		}

		// Export to CSV.
		if ( isset( $_POST['export_csv'] ) ) {
			// Verify the nonce for security
			check_admin_referer( 'export_emails_csv', '_wpnonce_export_csv' );
			// Try handling the file export process.

				// Get the file name from user input (if provided)
				$file_name = ! empty( $_POST['export_file_name'] )
					? $_POST['export_file_name']
					: '/tmp/screened_emails.csv';
				// Ensure the file has a .csv extension
				if ( pathinfo( $file_name, PATHINFO_EXTENSION ) !== 'csv' ) {
					$file_name .= '.csv';
				}
	//			$output = fopen( 'php://output', 'w' );
		try {
				$output = @fopen( $file_name, 'w' );
				if ($output === false) {
					throw new \Exception('Failed to open file for writing: ' . $file_name);
				}
				// Fetch email data from the database
				$emails = $this->wpdb->get_results( "SELECT * FROM {$this->table_name}", ARRAY_A );
				// Output CSV columns
				if (!empty($_POST['column_labels']) && $_POST['column_labels'] === 'on')
					fputcsv( $output, array( 'ID', 'Email', 'Hit Count', 'Date Added', 'Last Accessed', 'Notes' ) );

				// Output each row
				foreach ( $emails as $email ) {
					fputcsv( $output, $email );
				}

				fclose( $output );
				// If successful, show a success notice with the file name.
				// Add a success message
				add_settings_error(
					'tiaa_wpplugin_options', // Settings slug
					'screened_emails_csv_file',  // Unique ID
					'File '. $file_name . ' created', // Message text
					'updated' // 'updated' for success, 'error' for failure
				);
				settings_errors();
			} catch ( \Exception $e ) {
				// Handle error during file creation or saving and show a notice.
				add_settings_error(
					'tiaa_wpplugin_options', // Settings slug
					'screened_emails_csv_file',  // Unique ID
					'File '. $file_name . ' not created: ' . $e->getMessage(), // Message text
					'error' // 'updated' for success, 'error' for failure
				);
				settings_errors();
			}
		}
	}
}