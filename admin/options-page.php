<?php
/**
 * Handles the TIAA Plugin Options Page functionality within the WordPress Admin dashboard.
 *
 * This page allows administrators to configure plugin settings, including connection setup,
 * signup options, group signups, screened emails, welcome messages, and logging.
 *
 * @package TIAA_WPPlugin
 * @author Lew Grothe, TIAA Admin Platform sub-team
 * @link https://tiaa-forum.org/contact
 * @since 0.0.3
 */

namespace TIAAPlugin\Admin;

use TIAAPlugin\lib\PluginUtil;
// use TIAAPlugin\admin\ScreenedEmailsHandler;

/**
 * Class OptionsPage
 *
 * Responsible for displaying and managing the TIAA Plugin options in the WordPress admin panel.
 * Includes handling for tabs, forms, and specific page settings.
 *
 * @since 0.0.3
 */
class OptionsPage {
	use PluginUtil;

	/**
	 * The ScreenedEmailsHandler instance for managing screened email-related settings.
	 *
	 * @since 0.0.3
	 * @access protected
	 * @var null|ScreenedEmailsHandler $screened_emails_handler
	 */
	protected static ?ScreenedEmailsHandler $screened_emails_handler = null;

	/**
	 * The WelcomeDataHandler instance for managing welcome message settings.
	 *
	 * @since 0.0.3
	 * @access protected
	 * @var null|WelcomeDataHandler $welcome_data_handler
	 */
	protected static ?WelcomeDataHandler $welcome_data_handler = null;

	/**
	 * Singleton instance of the OptionsPage class.
	 *
	 * Ensures only one instance of this class is used throughout the request lifecycle.
	 *
	 * @since 0.0.3
	 * @access protected
	 * @var null|OptionsPage $instance
	 */
	protected static ?OptionsPage $instance = null;

	/**
	 * Retrieves the singleton instance of the OptionsPage class.
	 *
	 * @since 0.0.3
	 *
	 * @return OptionsPage An instance of the OptionsPage class.
	 */
	public static function get_instance(): OptionsPage {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor for the OptionsPage class.
	 *
	 * Protected to prevent direct instantiation outside of the get_instance() method.
	 *
	 * @since 0.0.3
	 */
	protected function __construct() {
		// Empty constructor.
	}

	/**
	 * Displays the Options Page in the WordPress Admin, including tabs and optional forms.
	 *
	 * Handles the display of settings and navigation tabs in the admin interface.
	 * Dynamically renders settings pages based on the active or selected tab.
	 *
	 * @param string      $active_tab The current tab; defaults to connection tab if `$_GET['tab']` is not set.
	 * @param string|null $parent_tab Optional parent tab. Useful for hierarchical menu structure.
	 * @param bool $form Whether to render a settings form on the page. Defaults to true.
	 *
	 *@since 0.0.3
	 *
	 */
	public function display( string $active_tab = '', string $parent_tab = null, bool $form = true ): void {
		?>
        <div class="wrap tiaa-options-page">
            <h2>
                <img
                        src="<?php echo esc_attr( TIAA_PLUGIN_LOGO ); ?>"
                        alt="TIAA-forum.org logo" class="tiaa-wpplugin-logo">
                TIAA Plugin
            </h2>
			<?php settings_errors(); ?>

			<?php
			$tab = null;

			// Handle tab selection from the menu.
			if ( isset( $_GET['page'] ) && $_GET['page'] !== 'tiaa_wpplugin_options' ) { // Input var okay.
				$tab = sanitize_key( wp_unslash( $_GET['page'] ) ); // Input var okay.
			}

			if ( ( $tab === null ) && isset( $_GET['tab'] ) ) { // Input var okay.
				$tab = sanitize_key( wp_unslash( $_GET['tab'] ) ); // Input var okay.
			} elseif ( $active_tab && isset( $_GET['page'] ) &&
			           $_GET['page'] === 'tiaa_wpplugin_options' ) {
				$tab = $active_tab;
			}
			?>

            <h2 class="nav-tab-wrapper nav-tab-first-level">
                <a href="?page=tiaa_wpplugin_options&tab=connection"
                   class="nav-tab <?php echo 'connection' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Connection' ); ?>
                </a>
                <a href="?page=tiaa_wpplugin_options&tab=Signup"
                   class="nav-tab <?php echo 'signup' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Signup' ); ?>
                </a>
                <a href="?page=tiaa_wpplugin_options&tab=Group-Signup"
                   class="nav-tab <?php echo 'group-signup' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Group Signup' ); ?>
                </a>
                <a href="?page=tiaa_wpplugin_options&tab=screened_emails"
                   class="nav-tab <?php echo 'screened_emails' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Email Screen' ); ?>
                </a>
                <a href="?page=tiaa_wpplugin_options&tab=welcome_message"
                   class="nav-tab <?php echo 'welcome_message' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Welcome' ); ?>
                </a>
                <a href="?page=tiaa_wpplugin_options&tab=Logging"
                   class="nav-tab <?php echo 'logging' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Logging' ); ?>
                </a>
            </h2>

			<?php
			/**
			 * Fires after the settings tabs display.
			 *
			 * @since 0.0.3
			 *
			 * @param string      $tab    The current active tab.
			 * @param null|string $parent The parent tab, if any.
			 */
			if ( $form ) :
				// 'group-signup', 'email-screen' have multiple forms on one page, handled differently.
                self::log_debug("options page: tab is: $tab");
                if ( 'group-signup' != $tab ) {
				?>
				<form action="options.php" method="post" class="tiaa-wpplugin-options-form">
					<?php
					if ( 'connection' === $tab ) {
						settings_fields( TIAA_CONNECT_GROUP );
						do_settings_sections(  TIAA_CONNECT_GROUP);
					}
					if ( 'signup' === $tab ) {
						settings_fields( TIAA_INVITE_GROUP );
						do_settings_sections( TIAA_INVITE_GROUP );
					}
					if ( 'logging' === $tab ) {
						settings_fields( TIAA_LOGGING_GROUP );
						do_settings_sections( TIAA_LOGGING_GROUP );
					}
					if ( 'screened_emails' === $tab ) {
						settings_fields( TIAA_SCREENED_EMAIL_GROUP );
						do_settings_sections( TIAA_SCREENED_EMAIL_GROUP );
					}
					if ( 'welcome_message' === $tab ) {
						settings_fields( TIAA_WELCOME_GROUP );
						do_settings_sections( TIAA_WELCOME_GROUP );
					}
					submit_button( 'Save Options', 'primary', 'tiaa_save_options', false );
                    ?>
				</form>
            <?php
				    if ( 'screened_emails' === $tab ) {
                        if (self::$screened_emails_handler === null) {
	                        self::$screened_emails_handler = new ScreenedEmailsHandler();
                        }
				        self::$screened_emails_handler->render_screened_emails_page();
  				    } elseif ('welcome_message' === $tab) {
                        if (self::$welcome_data_handler === null) {
                            self::$welcome_data_handler = new WelcomeDataHandler();
                        }
                        self::$welcome_data_handler->renderWelcomeData();
                    }
				} //if ( 'group-signup' != $tab )
                if ( 'group-signup' === $tab ) {
                    ?>
                    <form action="options.php" method="post" class="tiaa-wpplugin-options-form">
            <?php
                    settings_fields( TIAA_GROUP_LIST_GROUP );
                    do_settings_sections( TIAA_GROUP_LIST_GROUP );
                    submit_button( 'Save Options', 'primary', 'tiaa_save_options', false );
                        ?>
                    </form>
            <?php
                    $group_list = $this->get_options_by_group(TIAA_GROUP_LIST_GROUP);
                    if ($group_list && is_array($group_list)) {
                            foreach ($group_list as $group_array) {
                                foreach( $group_array as $group) {
                                    echo '<hr><h3>Group: ' . $group . '</h3>';
                                    $option_group = TIAA_GROUP_INVITE_GROUP . $group;
                                    ?>
                                    <form action="options.php" method="post" class="tiaa-wpplugin-options-form">
                                        <?php
                                    settings_fields( $option_group );
                                        do_settings_sections( $option_group );
                                        submit_button( 'Save Options', 'primary', 'tiaa_save_options', false );
                                        ?>
                                    </form>
                                    <?php
                                }
                            }
                        }
                } // if ( 'group-signup' === $tab )
?>
			<?php endif; // if ($form) ?>

        </div>
		<?php
	}
}
