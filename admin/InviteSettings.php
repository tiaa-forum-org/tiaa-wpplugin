<?php
/**
 * TIAA Forum Invite Settings
 *
 * Handles the administration settings for TIAA Forum invitations, managing the interface
 * between WordPress and Discourse. Provides functionality for configuring API keys,
 * URLs, and other settings required for forum invitations.
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

use TIAAPlugin\lib\PluginUtil;

/**
 * Manages the WordPress admin settings interface for TIAA Forum invitations.
 *
 * Provides functionality for managing Discourse forum integration settings,
 * including API keys, URLs, and messaging configurations. This class handles
 * the registration and display of all forum invitation related settings.
 *
 * @package TIAAPlugin\Admin
 * @since   0.0.3
 * /
 * class InviteSettings {
 * use PluginUtil;/**
 * Handles the admin settings for TIAA Forum invitations.
 *
 * This class is responsible for creating and managing the settings page
 * for TIAA Forum Signup, including form fields for API keys, URLs, and
 * related configurations.
 *
 * @package TIAAPlugin\Admin
 */
class InviteSettings {
	use PluginUtil;

	/**
	 * FormHelper instance.
	 *
	 * Used to display and validate form fields.
	 *
	 * @var FormHelper
	 */
	protected FormHelper $form_helper;

	/**
	 * Stored invite options from the WordPress options table.
	 *
	 * Caches the invite settings retrieved from WordPress options table
	 * using the TIAA_INVITE_GROUP option group.
	 *
	 * @since 0.0.3
	 * @var array|null The stored invite configuration options.
	 */
	protected ?array $invite_options;
	/**
	 * InviteSettings constructor.
	 *
	 * Initializes the settings class, sets up form helper, and registers the
	 * admin settings for managing forum invitations.
	 *
	 * @param FormHelper $form_helper An instance of the FormHelper class.
	 */
	public function __construct( FormHelper $form_helper ) {
		$this->form_helper = $form_helper;

		add_action( 'admin_init', array( $this, 'register_invite_settings' ) );
	}

	/**
	 * Registers invite settings section and fields.
	 *
	 * Adds settings sections and their respective fields to the WordPress
	 * admin settings API for managing TIAA Forum Signup options.
	 *
	 * @return void
	 */
	public function register_invite_settings() : void {
		$this->invite_options = $this->get_options_by_group( TIAA_INVITE_GROUP );

		add_settings_section(
			'invite_settings_section',
			'Options for TIAA Forum Signup page',
			array( $this, 'invite_to_forum' ),
			TIAA_INVITE_GROUP
		);

		add_settings_field(
			'url',
			'Discourse URL',
			array( $this, 'url_input' ),
			TIAA_INVITE_GROUP,
			'invite_settings_section'
		);

		add_settings_field(
			'api_key',
			'API Key',
			array( $this, 'api_key_input' ),
			TIAA_INVITE_GROUP,
			'invite_settings_section'
		);

		add_settings_field(
			'username',
			'Discourse login name',
			array( $this, 'username_input' ),
			TIAA_INVITE_GROUP,
			'invite_settings_section'
		);

		add_settings_field(
			'post_id',
			'Discourse message post ID',
			array( $this, 'post_id_input' ),
			TIAA_INVITE_GROUP,
			'invite_settings_section'
		);

		register_setting(
			TIAA_INVITE_GROUP,
			TIAA_INVITE_GROUP,
			array(
				$this->form_helper,
				'validate_options_blank_ok',
			)
		);
	}

	/**
	 * Output instructions for the invite settings section.
	 *
	 * This displays additional information for users in the "Options for
	 * TIAA Forum Signup page" section.
	 *
	 * @return void
	 */
	public function invite_to_forum() {
		?>
        <p class="invite_to_forum_section_tab">
            This page controls the settings for the TIAA Forum Signup page. If there is no API Key/username,
            the default connection settings will be used and the invite will be sent from that account.</p>
        <p>
            If there is no Post ID, the default message will be used. If a Post ID is set, the message will be
            retrieved from the Discourse forum and added to the invitation email.
        </p>
		<?php
	}

	/**
	 * Render the input field for the API Key.
	 *
	 * This field allows the admin to input the API Key required for communication
	 * with the Discourse forum.
	 *
	 * @return void
	 */
	public function api_key_input(): void {
		$this->form_helper->input(
			'api_key',
			TIAA_INVITE_GROUP,
			'API Key - leave blank to use the connection default'
		);
	}

	/**
	 * Render the input field for the Discourse URL.
	 *
	 * This field allows the admin to set the Discourse URL for the forum.
	 *
	 * @return void
	 */
	public function url_input(): void {
		$this->form_helper->input(
			'url',
			TIAA_INVITE_GROUP,
			'URL - leave blank to use the connection default',
			'url'
		);
	}

	/**
	 * Render the input field for the Discourse login name.
	 *
	 * This field allows the admin to specify the login username for connecting
	 * to the Discourse forum.
	 *
	 * @return void
	 */
	public function username_input(): void {
		$this->form_helper->input(
			'username',
			TIAA_INVITE_GROUP,
			'Username  - leave blank to use the connection default',
			null,
			null,
			array( 'style' => 'width: 10em;' )
		);

		$hook_url = site_url() . '/wp-json/tiaa_wpplugin/v1/tiaa_discourse_ping?option_group=tiaa_invite';
		?>
        <div class="wrap tiaa-ping-discourse-class" id="tiaa-ping2">
            <a href='<?php echo esc_url( $hook_url ); ?>' id="tiaa-ping2-a">Ping test</a>
            <div id="tiaa-ping2-results" class="tiaa-ping-results">ping results</div>
        </div>
		<?php
	}

	/**
	 * Render the input field for the Discourse message post ID.
	 *
	 * This field allows the admin to set the post ID for inviting users
	 * or sending messages via the Discourse forum.
	 *
	 * @return void
	 */
	public function post_id_input(): void {
		$this->form_helper->input(
			'post_id',
			TIAA_INVITE_GROUP,
			'Invitation Post Message ID - leave blank if not used',
			'number',
			null,
			array( 'style' => 'width: 6em;' )
		);

		$options = self::get_options_by_group( TIAA_INVITE_GROUP );

		// Display additional options only if `post_id` is set.
		if ( isset( $options['post_id'] ) && $options['post_id'] > 1 ) {
			$hook_url = site_url() . "/wp-json/tiaa_wpplugin/v1/get_discourse_post/?post_id={$options['post_id']}&option_group=tiaa_invite";
			?>
            <div class="wrap tiaa-message-discourse-class" id="tiaa-message1">
                <a href='<?php echo esc_url( $hook_url ); ?>' id="tiaa-message1-a">Get Message</a>
                <div id="tiaa-message1-results" class="tiaa-message-results-off">Message div</div>
            </div>
			<?php
		}
	}
}