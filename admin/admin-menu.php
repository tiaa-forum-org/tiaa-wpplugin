<?php
/**
 * Handles the admin menu pages and sub-menu pages for the TIAA plugin.
 *
 * This class provides functionality to create and manage the WordPress admin menu
 * and sub-menu pages for the plugin, handling options related to connections,
 * signup, group signup, screened emails, welcome messages, and logging.
 *
 * @package TIAAPlugin\Admin
 * @author Lew Grothe, TIAA Admin Platform sub-team
 * @link https://tiaa-forum.org/contact
 * @email info@tiaa-forum.org
 */

namespace TIAAPlugin\Admin;

/**
 * Class AdminMenu
 *
 * Manages the creation of admin menu and sub-menu pages for the TIAA plugin.
 * It initializes menu items, renders tabs, and attaches required callbacks for
 * displaying plugin information in the WordPress Dashboard.
 *
 * @package TIAAPlugin\Admin
 * @author Lew Grothe
 * @link https://tiaa-forum.org/contact
 * @email info@tiaa-forum.org
 */
class AdminMenu {
	/**
	 * An instance of the OptionsPage class.
	 *
	 * @access protected
	 * @var OptionsPage
	 */
	protected OptionsPage $options_page;

	/**
	 * An instance of the FormHelper class.
	 *
	 * @access protected
	 * @var FormHelper
	 */
	protected FormHelper $form_helper;

	/**
	 * AdminMenu constructor.
	 *
	 * Initializes the AdminMenu by injecting dependencies and binding the
	 * `add_menu_pages` function to the `admin_menu` WordPress action.
	 *
	 * @param OptionsPage $options_page An instance of the OptionsPage class.
	 * @param FormHelper  $form_helper  An instance of the FormHelper class.
	 */
	public function __construct(OptionsPage $options_page, FormHelper $form_helper ) {
		$this->options_page = $options_page;
		$this->form_helper  = $form_helper;

		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
	}

	/**
	 * Adds the main menu and sub-menu pages in the WordPress Dashboard.
	 *
	 * This method creates the main plugin page and sub-menu pages for plugin features
	 * such as connection settings, signup, group signup, screened emails management,
	 * welcome message settings, and logging. Notices related to the connection status
	 * are hooked into the load events of specific pages.
	 *
	 * @return void
	 */
	public function add_menu_pages() : void {
		$settings = add_menu_page(
			'TIAA Forum',
			'TIAA Forum',
			'manage_options',
			'tiaa_wpplugin_options',
			function () {
				$this->options_page->display( 'connection' );
			},
			TIAA_PLUGIN_LOGO,
			99
		);
		add_action( 'load-' . $settings, array( $this->form_helper, 'connection_status_notice' ) );

		$connection_settings = add_submenu_page(
			'tiaa_wpplugin_options',
			'Connection',
			'Connection',
			'manage_options',
			'connection',
			array( $this, 'connection_options_tab' )
		);
		add_action( 'load-' . $connection_settings, array( $this->form_helper, 'connection_status_notice' ) );

		$invite_settings = add_submenu_page(
			'tiaa_wpplugin_options',
			'Signup',
			'Signup',
			'manage_options',
			'signup',
			array( $this, 'connection_options_tab' )
		);
		add_action( 'load-' . $invite_settings, array( $this->form_helper, 'connection_status_notice' ) );

		$group_invite_settings = add_submenu_page(
			'tiaa_wpplugin_options',
			'Group Signup',
			'Group Signup',
			'manage_options',
			'group-signup',
			array( $this, 'group_signup_options_tab' )
		);
		add_action( 'load-' . $group_invite_settings, array( $this->form_helper, 'connection_status_notice' ) );

		/**
		 * Registers the submenu under the main plugin page.
		 */
		$screened_email_settings = add_submenu_page(
			'tiaa_wpplugin_options',
			'Manage Screened Emails',
			'Screened Emails',
			'manage_options',
			'screened_emails',
			array( $this, 'screened_emails_tab' )
		);
		add_action( 'load-' . $screened_email_settings, array( $this->form_helper, 'connection_status_notice' ) );

		$welcome_settings = add_submenu_page(
			'tiaa_wpplugin_options',
			'Welcome Welcome Message Settings',
			'Welcome',
			'manage_options',
			'welcome_message',
			array( $this, 'welcome_options_tab' )
		);
		add_action( 'load-' . $welcome_settings, array( $this->form_helper, 'connection_status_notice' ) );

		$logging_settings = add_submenu_page(
			'tiaa_wpplugin_options',
			'Logging',
			'Logging',
			'manage_options',
			'logging',
			array( $this, 'logging_options_tab' )
		);
		add_action( 'load-' . $logging_settings, array( $this->form_helper, 'connection_status_notice' ) );

	}

	/**
	 * Displays the 'Connection' tab in the admin interface.
	 *
	 * Renders the Connection Options tab, ensuring the current user
	 * has the 'manage_options' capability.
	 *
	 * @return void
	 */
	public function connection_options_tab() : void {
		if ( current_user_can( 'manage_options' ) ) {
			$this->options_page->display( 'connection' );
		}
	}
	/**
	 * Displays the 'Group Signup' tab in the admin interface.
	 *
	 * Renders the Group Signup Options tab, ensuring the current user
	 * has the 'manage_options' capability.
	 *
	 * @return void
	 */

	public function group_signup_options_tab() : void {
		if ( current_user_can( 'manage_options' ) ) {
			$this->options_page->display( 'group-signup' );
		}
	}
	/**
	 * Displays the 'Screened Emails' tab in the admin interface.
	 *
	 * Renders the Screened Emails tab, ensuring the current user
	 * has the 'manage_options' capability.
	 *
	 * @return void
	 */
	public function screened_emails_tab() : void {
		if ( current_user_can( 'manage_options' ) ) {
			$this->options_page->display( 'screened_emails' );
		}
	}
	/**
	 * Displays the 'Welcome Message Settings' tab in the admin interface.
	 *
	 * Renders the Welcome Message Settings tab, ensuring the current user
	 * has the 'manage_options' capability.
	 *
	 * @return void
	 */
	public function welcome_options_tab() : void {
		if ( current_user_can( 'manage_options' ) ) {
			$this->options_page->display( 'welcome_message' );
		}
	}
	/**
	 * Displays the 'Logging Settings' tab in the admin interface.
	 *
	 * Renders the Logging Settings tab, ensuring the current user
	 * has the 'manage_options' capability.
	 *
	 * @return void
	 */
	public function logging_options_tab() : void {
		if ( current_user_can( 'manage_options' ) ) {
			$this->options_page->display( 'logging' );
		}
	}
}