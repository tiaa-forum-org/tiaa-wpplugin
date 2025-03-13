<?php
/**
 * Class ConnectionSettings
 *
 * This class handles the connection settings for the Discourse integration.
 * It provides methods for rendering and registering the settings fields
 * in the WordPress admin panel.
 *
 * @package TIAAPlugin\Admin
 * @author Lew Grothe, TIAA Admin Platform sub-team
 * @license GPL-2.0+
 * @link https://tiaa-forum.org/contact
 */

namespace TIAAPlugin\Admin;

use TIAAPlugin\lib\PluginUtil;

class ConnectionSettings {
	/**
	 * Trait for helper methods from PluginUtil.
	 *
	 */
	use PluginUtil;

	/**
	 * The FormHelper instance used for rendering input fields.
	 *
	 * @var FormHelper
	 */
	protected FormHelper $form_helper;

	/**
	 * Stores the connection options retrieved from the group.
	 *
	 * @var array|null
	 */
	protected ?array $connect_options;

	/**
	 * ConnectionSettings constructor.
	 *
	 * @param FormHelper $form_helper An instance of the FormHelper class.
	 */
	public function __construct( FormHelper $form_helper ) {
		$this->form_helper = $form_helper;

		// Hook into the WordPress admin initialization to register connection settings.
		add_action( 'admin_init', array( $this, 'register_connection_settings' ) );
	}

	/**
	 * Registers connection settings for the Discourse integration.
	 *
	 * This method adds settings sections and fields to the admin panel
	 * for configuring the connection to the Discourse server.
	 *
	 * @return void
	 */
	public function register_connection_settings() : void {
		$this->connect_options = $this->get_options_by_group( TIAA_CONNECT_GROUP );

		// Add a settings section for Discourse connection settings.
		add_settings_section(
			'connection_settings_section',
			'Connecting With Discourse',
			array(
				$this,
				'connecting_discourse',
			),
			TIAA_CONNECT_GROUP
		);

		// Add fields for URL, API Key, and Username.
		add_settings_field(
			'url',
			'Discourse URL',
			array(
				$this,
				'url_input',
			),
			TIAA_CONNECT_GROUP,
			'connection_settings_section'
		);

		add_settings_field(
			'api_key',
			'API Key',
			array(
				$this,
				'api_key_input',
			),
			TIAA_CONNECT_GROUP,
			'connection_settings_section'
		);

		add_settings_field(
			'username',
			'Discourse login name',
			array(
				$this,
				'username_input',
			),
			TIAA_CONNECT_GROUP,
			'connection_settings_section'
		);

		// Register the TIAA connection group setting with validation.
		register_setting(
			TIAA_CONNECT_GROUP,
			TIAA_CONNECT_GROUP,
			array(
				$this->form_helper,
				'validate_options',
			)
		);
	}

	/**
	 * Callback function for rendering the 'Connecting With Discourse' section description.
	 *
	 * @return void
	 */
	public function connecting_discourse() {
		?>
        <p class="connecting_discourse_section_tab">
            <em>
                Must be a valid Discourse URL and credentials.
            </em>
            <br>
            To connect to Discourse, you must have a Discourse account and an API key.
            You can obtain API keys by logging into your Discourse account and going to:<br>
            <strong>&lt;your installation discourse url>/admin/api_keys</strong>.
            <br>
            These are the general, default connection settings for the Discourse integration.
            You can override these settings for each service by replacing the
            credentials in the service-specific settings.
        </p>
		<?php
	}

	/**
	 * Renders the API Key input field for Discourse connection settings.
	 *
	 * @return void
	 */
	public function api_key_input() : void {
		$this->form_helper->input(
			'api_key',
			TIAA_CONNECT_GROUP,
			'API Key'
		);
	}

	/**
	 * Renders the URL input field for Discourse connection settings.
	 *
	 * @return void
	 */
	public function url_input() : void {
		$this->form_helper->input(
			'url',
			TIAA_CONNECT_GROUP,
			'URL',
			'url'
		);
	}

	/**
	 * Renders the Username input field for Discourse connection settings.
	 *
	 * Also provides a "Ping Test" link to verify the connection.
	 *
	 * @return void
	 */
	public function username_input() : void {
		$this->form_helper->input(
			'username',
			TIAA_CONNECT_GROUP,
			'Username',
			null,
			null,
			array( 'style' => 'width: 10em;' )
		);

		// Generate a ping test URL.
		$hook_url = site_url() . '/wp-json/tiaa_wpplugin/v1/tiaa_discourse_ping?option_group=tiaa_connection';
		?>
        <div class="wrap tiaa-ping-discourse-class" id="tiaa-ping1">
            <a href='<?php echo $hook_url; ?>' id="tiaa-ping1-a">Ping test</a>
            <div id="tiaa-ping1-results" class="tiaa-ping-results">ping results</div>
        </div>
		<?php
	}
}