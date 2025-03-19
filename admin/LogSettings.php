<?php
/**
 * LogSettings Class for managing log-related settings in the admin area.
 *
 * This class handles creating and rendering the fields and settings
 * for managing log file paths, logging levels, and other related options
 * as part of the TIAA Plugin.
 *
 * @package TIAAPlugin\Admin
 * @author Lew Grothe
 * @author TIAA Admin Platform sub-team
 * @link https://tiaa-forum.org/contact
 * @since 0.0.3
 */

/**
 * TODO - need to check if file is writeable before saving
 * TODO - move log file handler to plugin rather than vendor area
 */
namespace TIAAPlugin\Admin;

use JetBrains\PhpStorm\NoReturn;
use TIAAPlugin\Analog\Handler\TIAAFile;
use TIAAPlugin\lib\PluginUtil;

/**
 * Class LogSettings
 *
 * A class for managing log settings for the TIAA Plugin in the WordPress admin.
 *
 * @since 0.0.3
 */
class LogSettings {

	use PluginUtil;

	/**
	 * Helper object to manage form-related actions.
	 *
	 * @var FormHelper
	 * @since 0.0.3
	 */
	protected FormHelper $form_helper;

	/**
	 * Stores the options for log settings grouped by the logging group.
	 *
	 * @var ?array
	 * @since 0.0.3
	 */
	protected static ?array $log_settings_options = null;

	/**
	 * LogSettings class constructor.
	 *
	 * @param FormHelper $form_helper An instance of the FormHelper class.
	 *
	 * @since 0.0.3
	 */
	public function __construct( FormHelper $form_helper ) {
		$this->form_helper = $form_helper;

		add_action( 'admin_init', array( $this, 'register_log_settings' ) );
		// Add action to handle the log file download request.
		add_action( 'admin_post_download_log_file', array( __CLASS__, 'handle_download_log_file' ) );
	}

	/**
	 * Registers log settings in the WordPress admin via the Settings API.
	 *
	 * This method adds a section, fields, and registers the related
	 * settings under the specified logging group.
	 *
	 * @since 0.0.3
	 */
	public function register_log_settings() : void {
		self::$log_settings_options = $this->get_options_by_group( TIAA_LOGGING_GROUP );

		add_settings_section(
			'log_settings_section',
			esc_html__( 'Options for TIAA Plugin Log', 'tiaa-wpplugin' ),
			array( $this, 'logging_options' ),
			TIAA_LOGGING_GROUP
		);

		add_settings_field(
			'file_path',
			esc_html__( 'Path to log file', 'tiaa-wpplugin' ),
			array( $this, 'file_path_input' ),
			TIAA_LOGGING_GROUP,
			'log_settings_section'
		);

		add_settings_field(
			'log_level',
			esc_html__( 'Logging level', 'tiaa-wpplugin' ),
			array( $this, 'log_level_input' ),
			TIAA_LOGGING_GROUP,
			'log_settings_section'
		);

		register_setting(
			TIAA_LOGGING_GROUP,
			TIAA_LOGGING_GROUP,
			array( $this->form_helper, 'validate_options' )
		);
	}

	/**
	 * Displays information about logging options.
	 *
	 * Rendered when the settings section is defined for the log settings.
	 * Outputs a simple paragraph describing log settings.
	 *
	 * @return void
	 * @since 0.0.3
	 */
	public function logging_options() : void {
		?>
        <p class="log_options_section_tab">
			<?php esc_html_e( 'These settings set the location of the log file and level to log at.', 'tiaa-wpplugin' ); ?>
        </p>
		<?php
	}

	/**
	 * Renders the input field for the log level setting.
	 *
	 * Includes a number input with a predefined width and lists available
	 * log levels as a reference.
	 *
	 * @return void
	 * @since 0.0.3
	 */
	public function log_level_input() : void {
		$this->form_helper->input(
			'log_level',
			TIAA_LOGGING_GROUP,
			esc_html( 'Log level'),
			'number',
			null,
			array( 'style' => 'width: 2em;' ,
                'min' => 3, 'max'=> 7)
		);
		$log_levels = TIAAFile::LOG_LEVELS;
		echo '<tr><th>' . esc_html( 'Log Levels') . '</th><td>';
		foreach ( $log_levels as $key => $level ) {
			printf( '%s => %s<br>', esc_html( $key ), esc_html( $level ) );
		}
		echo '</td></tr>';
	}

	/**
	 * Renders the input field for the log file path setting.
	 *
	 * Provides an input field to specify the file path where logs will be stored.
	 *
	 * @return void
	 * @since 0.0.3
	 */
	public function file_path_input() : void {
		$this->form_helper->input(
			'file_path',
			TIAA_LOGGING_GROUP,
			esc_html( 'File Path'),
			'file_path',
            null,
            array( 'style' => 'width: 25em;' )
		);
		/**
		 * button to download the log file
		 */
		self::download_log_button();
	}
	/**
	 * Renders the "Download Log File" button and nonce.
	 *
	 * @since 0.0.3
	 */
	public function download_log_button() : void {
		$download_url = add_query_arg(
			array(
				'action' => 'download_log_file',
				'_wpnonce' => wp_create_nonce( 'download_log_file' ),
			),
			admin_url( 'admin-post.php' )
		);
        ?>
            <a href="<?php echo esc_url( $download_url ); ?>" class="button button-primary">
				<?php echo esc_html( 'Download Log File'); ?>
            </a>
		<?php
        echo "current file size: " . number_format(filesize(self::get_log_file()),0) . "<br>";
	}

	/**
	 * Handles log file downloading securely.
	 *
	 * @since 0.0.3
	 */
	#[NoReturn] public static function handle_download_log_file() : void {
		// Validate nonce and capability.
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'download_log_file' ) ) {
			wp_die( esc_html( 'You are not allowed to access this file.'), 403 );
		}
		$file_path = self::get_log_file();
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			wp_die( esc_html( 'The log file does not exist or is not readable.'), 404 );
		}

		// Set headers and deliver the file.
		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="' . basename( $file_path ) . '"' );
		header( 'Content-Length: ' . filesize( $file_path ) );
		readfile( $file_path );

		// Terminate to ensure WordPress outputs nothing else.
		exit;
	}
	/**
	 *
	 *
	 */
	public static function get_log_file() : string {
		if ( self::$log_settings_options === null ) {
			self::$log_settings_options = PluginUtil::get_options_by_group( TIAA_LOGGING_GROUP );
		}

		return ( self::$log_settings_options['file_path'] );
	}
}
