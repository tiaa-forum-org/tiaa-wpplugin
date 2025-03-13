<?php
/**
 * TIAA Screened Emails Settings
 *
 * Manages settings for screened emails in the TIAA system, including configuration
 * options to manage warnings, hit limits, and screening logic. Provides an admin
 * interface for managing these settings.
 *
 * @package    TIAAPlugin
 * @subpackage Admin
 * @author     Lew Grothe, TIAA Admin Platform sub-team
 * @license    GPL-2.0-or-later
 * @link       https://tiaa-forum.org/contact
 * @email      info@tiaa-forum.org
 *
 * @since      0.0.3
 */

namespace TIAAPlugin\Admin;

use wpdb;
use TIAAPlugin\lib\PluginUtil;

/**
 * Manages the settings page and logic related to screened emails.
 *
 * Allows WordPress admins to configure and manage email screening logic,
 * setting thresholds for warnings and hit limits.
 *
 * @since 0.0.3
 */
class ScreenedEmailsSettings {
	use PluginUtil;

	/**
	 * The FormHelper instance used for rendering form fields.
	 *
	 * @since 0.0.3
	 * @access protected
	 * @var FormHelper
	 */
	protected FormHelper $form_helper;

	/**
	 * Stored settings for the screening log options.
	 *
	 * Stores settings related to screening email logs, retrieved
	 * for the specific option group.
	 *
	 * @since 0.0.3
	 * @access protected
	 * @var array
	 */
	protected array $screen_log_options;

	/**
	 * The WordPress database instance.
	 *
	 * Provides a reference to the global `$wpdb` object for database
	 * operations related to this class.
	 *
	 * @since 0.0.3
	 * @access protected
	 * @var wpdb
	 */
	protected wpdb $wpdb;

	/**
	 * The name of the table for screened emails.
	 *
	 * Holds the database table name where screened email data is stored.
	 *
	 * @since 0.0.3
	 * @access protected
	 * @var string
	 */
	protected string $table_name;

	/**
	 * Constructor for the ScreenedEmailsSettings class.
	 *
	 * Initializes the class with the required dependencies and sets up
	 * the admin settings page by hooking into WordPress actions.
	 *
	 * @since 0.0.3
	 *
	 * @param FormHelper $form_helper An instance of the FormHelper class.
	 */
	public function __construct(FormHelper $form_helper) {
		$this->form_helper = $form_helper;

		add_action('admin_init', array($this, 'register_screened_emails_settings'));
	}

	/**
	 * Registers the settings for screened emails.
	 *
	 * Adds settings sections and fields, and registers the settings options
	 * for managing screened email configurations.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function register_screened_emails_settings(): void {
		$this->screen_log_options = $this->get_options_by_group(TIAA_SCREENED_EMAIL_GROUP);

		add_settings_section(
			'screened_emails_settings_section',
			'Options for Screened Emails',
			array($this, 'screened_emails_options'),
			TIAA_SCREENED_EMAIL_GROUP
		);

		add_settings_field(
			'email_list',
			'Email(s) for Warnings',
			array($this, 'alarm_email_input'),
			TIAA_SCREENED_EMAIL_GROUP,
			'screened_emails_settings_section'
		);

		add_settings_field(
			'max_hits_per_email',
			'Max hits per email',
			function () {
				$this->number_input('max_hits_per_email', 'Max hits per email');
			},
			TIAA_SCREENED_EMAIL_GROUP,
			'screened_emails_settings_section'
		);

		add_settings_field(
			'max_total_hits',
			'Max total hits',
			function () {
				$this->number_input('max_total_hits', 'Max total hits');
			},
			TIAA_SCREENED_EMAIL_GROUP,
			'screened_emails_settings_section'
		);

		add_settings_field(
			'max_hits_per_day',
			'Max hits per day',
			function () {
				$this->number_input('max_hits_per_day', 'Max hits per day');
			},
			TIAA_SCREENED_EMAIL_GROUP,
			'screened_emails_settings_section'
		);

		register_setting(
			TIAA_SCREENED_EMAIL_GROUP,
			TIAA_SCREENED_EMAIL_GROUP,
			array($this->form_helper, 'validate_options')
		);
	}

	/**
	 * Outputs the description for the "Options for Screened Emails" settings section.
	 *
	 * Displays instructions or a note at the top of the section on the settings page.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function screened_emails_options(): void {
		?>
        <p class="screened_emails_options_section_tab">
            These settings manage the emails to be screened.
        </p>
		<?php
	}

	/**
	 * Renders the input field for the alarm email list.
	 *
	 * Provides a text input field for specifying the list of emails
	 * that should receive warnings. Multiple emails are separated by commas.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function alarm_email_input(): void {
		$this->form_helper->input(
			'email_list',
			TIAA_SCREENED_EMAIL_GROUP,
			'Alarm Email(s) for Warnings. Separate multiple emails with a comma.',
			'email_list',
			null,
			array('style' => 'width: 25em;')
		);
	}

	/**
	 * Renders a generic number input field for settings.
	 *
	 * Supports rendering number inputs for `max_hits_per_email`, `max_total_hits`,
	 * and `max_hits_per_day` settings.
	 *
	 * @since 0.0.3
	 *
	 * @param string $field       The name of the setting field.
	 * @param string $description A description of the field's purpose.
	 * @return void
	 */
	public function number_input(string $field, string $description): void {
		$this->form_helper->input(
			$field,
			TIAA_SCREENED_EMAIL_GROUP,
			$description,
			'number',
			null,
			array('style' => 'width: 4em;')
		);
	}
}