<?php
/**
 * TIAA Welcome Feature Settings
 *
 * Manages the configuration settings for the Welcome feature in the TIAA plugin.
 * This includes settings for sending welcome messages to new members in Discourse,
 * configuring thresholds, and managing excluded discourse groups.
 *
 * @package    TIAAPlugin
 * @subpackage Admin
 * @category   Settings
 * @author     Lew Grothe, TIAA Admin Platform sub-team
 * @license    GPL-2.0-or-later
 * @link       https://tiaa-forum.org/contact
 * @email      info@tiaa-forum.org
 *
 * @since      0.0.3
 */

namespace TIAAPlugin\Admin;

use TIAAPlugin\lib\PluginUtil;
use TIAAPlugin\lib\OptionsUtilities;

/**
 * Welcome Settings Manager
 *
 * Handles the setup and rendering of the settings page for the Welcome feature,
 * providing options for configuring welcome message delivery and related parameters.
 *
 * @package TIAAPlugin\Admin
 * @since   0.0.3
 */
class WelcomeSettings {
	use PluginUtil;

	/**
	 * Form helper instance.
	 *
	 * Used for generating and validating form fields on the settings page.
	 *
	 * @since   0.0.3
	 * @access  protected
	 * @var    FormHelper
	 */
	protected  FormHelper $form_helper;

	/**
	 * Stores the Welcome feature options.
	 *
	 * Holds values retrieved from the WordPress options table
	 * for the configured TIAA_WELCOME_GROUP.
	 *
	 * @since   0.0.3
	 * @access  private
	 * @var     array|null
	 */
	private ?array $options;

	/**
	 * Constructor for WelcomeSettings.
	 *
	 * Initializes the class, retrieves stored options, and hooks WordPress actions
	 * for rendering the settings page and handling form submission.
	 *
	 * @since  0.0.3
	 *
	 * @param  FormHelper $form_helper The form helper instance used for input validation.
	 */
	public function __construct(FormHelper $form_helper) {
		$this->form_helper = $form_helper;

		// Add admin menus and WordPress settings hooks.
		add_action('admin_menu', [$this, 'register_admin_menu']);
		add_action('admin_init', [$this, 'register_settings']);
        add_action( 'admin_init', [$this, 'setup_options'] );
    }

    public function setup_options(): void {
        $this->options = self::get_options_by_group( TIAA_WELCOME_GROUP );

    }

	/**
	 * Registers an admin menu page for the Welcome feature settings.
	 *
	 * Adds a settings page under the WordPress "Settings" menu
	 * for configuring the Welcome feature in the TIAA plugin.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function register_admin_menu(): void {
		add_options_page(
			'Welcome Settings',
			'Welcome Settings',
			'manage_options',
			'tiaa-welcome-settings',
			[$this, 'render_settings_page']
		);
	}

	/**
	 * Registers settings fields and sections for the Welcome feature configuration.
	 *
	 * Defines the settings for configuring the Welcome feature, such as Discourse URL,
	 * API key, group exclusions, scan rate, and welcome messages.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function register_settings(): void {
		add_settings_section(
			'welcome_settings_section',
			'Welcome Feature Settings',
			[$this, 'welcome_settings_description'],
			TIAA_WELCOME_GROUP
		);

		add_settings_field(
			'url',
			'Discourse URL',
			array($this, 'url_input'),
			TIAA_WELCOME_GROUP,
			'welcome_settings_section'
		);

		add_settings_field(
			'api_key',
			'API Key',
			array($this, 'api_key_input'),
			TIAA_WELCOME_GROUP,
			'welcome_settings_section'
		);

		add_settings_field(
			'username',
			'Discourse login name',
			array($this, 'username_input'),
			TIAA_WELCOME_GROUP,
			'welcome_settings_section'
		);

		add_settings_field(
			'days_since_joined_max',
			'Max Days Since Joined',
			[$this, 'render_max_field'],
			TIAA_WELCOME_GROUP,
			'welcome_settings_section'
		);

		add_settings_field(
			'days_since_joined_min',
			'Min Days Since Joined',
			[$this, 'render_min_field'],
			TIAA_WELCOME_GROUP,
			'welcome_settings_section'
		);

		add_settings_field(
			'scan_rate',
			'Scan Rate (in days)',
			[$this, 'render_scan_rate_field'],
			TIAA_WELCOME_GROUP,
			'welcome_settings_section'
		);

        add_settings_field(
                'cron_interval',
                'Cron Run Interval',
                [ $this, 'render_cron_interval_field' ],
                TIAA_WELCOME_GROUP,
                'welcome_settings_section'
        );

		add_settings_field(
			'group_list',
			'Excluded Discourse Groups',
			[$this, 'render_group_list_field'],
			TIAA_WELCOME_GROUP,
			'welcome_settings_section'
		);

		add_settings_field(
			'message_title',
			'Welcome Message Title',
			[$this, 'render_message_title_field'],
			TIAA_WELCOME_GROUP,
			'welcome_settings_section'
		);

		add_settings_field(
			'post_id',
			'Welcome Post ID',
			[$this, 'render_post_id_field'],
			TIAA_WELCOME_GROUP,
			'welcome_settings_section'
		);

		register_setting(
			TIAA_WELCOME_GROUP,
			TIAA_WELCOME_GROUP,
			array(
				$this->form_helper,
				'validate_options', // Note: Known bug described in README-known-bugs.md.
			)
		);

        // if option already exists, it will not change the value
        add_option( TIAA_WELCOME_GROUP_CRON, true );
        // Reschedule cron if interval setting changes.
        add_action( 'update_option_' . TIAA_WELCOME_GROUP, [ $this, 'reschedule_on_interval_change' ], 10, 2 );
	}

	/**
	 * Outputs the description for the Welcome feature settings section.
	 *
	 * Adds a note to provide guidance on configuring the Welcome feature.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function welcome_settings_description(): void {
		?>
        <p>
            The plugin will send a welcome message to new members of your Discourse site
            in <strong>X</strong> days, where <strong>X</strong> is the value you specify below.
        </p>
		<?php
	}

	/**
	 * Renders an input field for the Discourse URL.
	 *
	 * Used to specify the URL for the Discourse forum.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function url_input(): void {
		$this->form_helper->input(
			'url',
			TIAA_WELCOME_GROUP,
			'URL - must be set',
			'url'
		);
	}

	/**
	 * Renders an input field for the API key.
	 *
	 * Used to specify the API key for accessing Discourse.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function api_key_input() : void {
		$this->form_helper->input(
			'api_key',
			TIAA_WELCOME_GROUP,
			'API Key - must be set',
		);
	}
	/**
	 * Renders an input field for the Discourse login username.
	 *
	 * Used to specify the username required for authentication with Discourse.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function username_input(): void {
		$this->form_helper->input(
			'username',
			TIAA_WELCOME_GROUP,
			'Username - must be set',
			'text',
            null,
			 array( 'style' => 'width: 10em;' )
		);
		$hook_url = site_url() . '/wp-json/tiaa_wpplugin/v1/tiaa_discourse_ping?option_group=' . TIAA_WELCOME_GROUP;
		?>
        <div class="wrap tiaa-ping-discourse-class" id="tiaa-ping2">
            <a href='<?php echo esc_url( $hook_url ); ?>' id="tiaa-ping2-a">Ping test</a>
            <div id="tiaa-ping2-results" class="tiaa-ping-results">ping results</div>
        </div>
		<?php

	}
	/**
	 * Renders an input field for the scan rate in days.
	 *
	 * Specifies how often the plugin will scan the Discourse forum for new members
	 * to whom the welcome message should be sent.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function render_scan_rate_field() : void {
	    $this->form_helper->input(
		    'scan_rate',
		    TIAA_WELCOME_GROUP,
		    'number of days between scans to check for new members',
		    'number',
		    null,
		    (array( 'style' => 'width: 4em;' ))
	    );
   }

	/**
	 * Renders an input field for the maximum days since a member joined.
	 *
	 * Configures the maximum number of days since a user joined Discourse
	 * to be eligible to receive a welcome message.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function render_max_field(): void {
		$this->form_helper->input(
			'days_since_joined_max',
			TIAA_WELCOME_GROUP,
			'Maximum days since joined.',
			'number',
			null,
			(array( 'style' => 'width: 4em;' ))

		);
	}

	/**
	 * Renders an input field for the minimum days since a member joined.
	 *
	 * Configures the minimum number of days since a user joined Discourse
	 * to be eligible to receive a welcome message.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function render_min_field(): void {
		$this->form_helper->input(
			'days_since_joined_min',
			TIAA_WELCOME_GROUP,
			'Minimum days since joined.',
			'number',
			null,
			(array( 'style' => 'width: 4em;' ))

		);
	}
    /**
     * Renders a select field for the cron run interval.
     *
     * Allows switching between 'daily' and 'hourly' for testing purposes.
     * Changing this value requires saving settings — the cron will be
     * automatically rescheduled on the next page load.
     *
     * @since 0.0.4
     * @return void
     */
    public function render_cron_interval_field(): void {
        $options = self::get_options_by_group( TIAA_WELCOME_GROUP ); // ← fresh fetch, not $this->options
        $current = $options['cron_interval'] ?? 'daily';
        $name    = TIAA_WELCOME_GROUP . '[cron_interval]';
        ?>
        <select id="cron_interval" name="<?php echo esc_attr( $name ); ?>">
            <option value="daily"  <?php selected( $current, 'daily' );  ?>>Daily</option>
            <option value="hourly" <?php selected( $current, 'hourly' ); ?>>Hourly (testing only)</option>
            <option value="every_five_minutes" <?php selected( $current, 'every_five_minutes' ); ?>>
                Every 5 minutes (testing only)
            </option>
        </select>
        <p class="description">
            Use <strong>Hourly</strong> or <strong>Every 5 minutes</strong> for local testing only.
            After changing, save settings — the cron job will reschedule automatically.
        </p>
        <?php
    }
    /**
	 * Renders an input field for excluding Discourse groups from the Welcome feature.
	 *
	 * Specifies the list of groups that should not receive the welcome message,
	 * entered as a comma-separated list.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function render_group_list_field(): void {
		$this->form_helper->input(
			'group_list',
			TIAA_WELCOME_GROUP,
			'Comma-separated list of Discourse groups to exclude.',
			'array',
			null,
			(array( 'style' => 'width: 24em;' ))
		);
	}

	/**
	 * Renders an input field for the welcome message title.
	 *
	 * Allows the user to specify a custom title for the welcome message
	 * that is sent to eligible members.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function render_message_title_field(): void {
		$this->form_helper->input(
			'message_title',
			TIAA_WELCOME_GROUP,
			'Title of the welcome message.',
			'text',
			null,
			(array( 'style' => 'width: 40em;' ))

		);
	}

	/**
	 * Renders an input field for the Welcome Post ID.
	 *
	 * Specifies the ID of the welcome message post in Discourse
	 * that will be sent to new members as a part of the Welcome feature.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function render_post_id_field(): void {
		$this->form_helper->input(
			'post_id',
			TIAA_WELCOME_GROUP,
			'ID of the welcome message post.',
			'number',
			null,
			(array( 'style' => 'width: 5em;' ))

		);
		$options = self::get_options_by_group( TIAA_WELCOME_GROUP );
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
	/**
	 * Renders the main settings page for the Welcome feature.
	 *
	 * Displays the settings fields and sections defined for configuring
	 * the Welcome feature in the TIAA plugin.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function render_settings_page(): void {
		?>
        <div class="wrap">
            <h1>Welcome Settings</h1>
            <form  method="post">
				<?php
				settings_fields(TIAA_WELCOME_GROUP);
				do_settings_sections(TIAA_WELCOME_GROUP);
				submit_button();
				?>
            </form>
        </div>
		<?php
	}
    /**
     * Reschedules the cron job when the interval option changes.
     *
     * Hooked into update_option_{TIAA_WELCOME_GROUP} so it fires automatically
     * when settings are saved. Only acts if cron_interval actually changed.
     *
     * @param array $old_value Previous option values.
     * @param array $new_value Updated option values.
     * @since 0.0.4
     * @return void
     */
    public function reschedule_on_interval_change( array $old_value, array $new_value ): void {
        $old_interval = $old_value['cron_interval'] ?? 'daily';
        $new_interval = $new_value['cron_interval'] ?? 'daily';

        if ( $old_interval !== $new_interval ) {
            // Unschedule via a plain instance (options don't matter for unschedule)
            $util = new \TIAAPlugin\lib\WelcomeUtil();
            $util->unschedule_cron();

            // Re-init with new options explicitly merged in before scheduling
            OptionsUtilities::$option_groups[ TIAA_WELCOME_GROUP ]['cron_interval'] = $new_interval;
            $util2 = new \TIAAPlugin\lib\WelcomeUtil();
            $util2->schedule_cron();

            self::log_debug( "Cron rescheduled: $old_interval → $new_interval" );
        }
    }}
