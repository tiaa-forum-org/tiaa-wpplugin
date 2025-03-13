<?php
/**
 * Handles functions and interactions related to the Welcome Data feature.
 *
 * This class provides the management of forms and processes related to managing
 * welcome messages and related cron job operations for the Discourse integration.
 *
 * @package    TIAAPlugin
 * @subpackage TIAAPlugin\admin
 * @author     Lew Grothe, TIAA Admin Platform sub-team
 * @link       https://tiaa-forum.org/contact
 * @since      0.0.3
 */

namespace TIAAPlugin\admin;

use TIAAPlugin\lib\WelcomeUtil;

/**
 * Class WelcomeDataHandler
 *
 * Responsible for managing welcome message-related data and operations, including
 * permission checks and form processing for managing cron jobs.
 *
 * @since 0.0.3
 */
class WelcomeDataHandler {
	/**
	 * Utility class for handling welcome data operations, including cron management.
	 *
	 * @since 0.0.3
	 * @var WelcomeUtil $Util An instance of the WelcomeUtil class.
	 */
	public WelcomeUtil $Util;

	/**
	 * Constructor for the WelcomeDataHandler class.
	 *
	 * Initializes the WelcomeUtil instance for handling welcome-related utility operations.
	 *
	 * @since 0.0.3
	 */
	public function __construct() {

		$this->Util = new WelcomeUtil();
	}

	/**
	 * Renders the welcome data-related admin view.
	 *
	 * This method handles permissions, form submissions, and displaying the
	 * data needed to manage welcome-related functionalities in the WordPress admin area.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function renderWelcomeData(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$this->handle_form_submission();

		$cron_status = $this->Util->get_cron_status();

		include_once plugin_dir_path( __FILE__ ) . '/views/welcome-data-view.php';
	}

	/**
	 * Handles form submissions related to welcome data and cron management.
	 *
	 * Processes form data from the admin view to schedule, unschedule,
	 * run cron jobs, or retrieve cron job status.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	protected function handle_form_submission(): void {
		if ( preg_grep( '/^cron_/', array_keys( $_POST ) ) ) {
			if ( isset( $_POST['cron_start'] ) ) {
				$this->Util->schedule_cron();
			} elseif ( isset( $_POST['cron_stop'] ) ) {
				$this->Util->unschedule_cron();
			} elseif ( isset( $_POST['cron_do_run'] ) ) {
				$this->Util->run_cron();
			} elseif ( isset( $_POST['get_cron_status'] ) ) {
				echo( $this->Util->get_cron_status() );
			}
		}
	}
}