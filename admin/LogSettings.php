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
 * TODO - need something to flag file if growing too large
 * TODO - also to download and/or read recent log
 * TODO - move log file handler to plugin rather than vendor area
 */
namespace TIAAPlugin\Admin;

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
	 * @var array
	 * @since 0.0.3
	 */
	protected array $log_settings_options;

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
		$this->log_settings_options = $this->get_options_by_group( TIAA_LOGGING_GROUP );

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
			esc_html__( 'Log level', 'tiaa-wpplugin' ),
			'number',
			null,
			array( 'style' => 'width: 2em;' ,
                'min' => 3, 'max'=> 7)
		);
		$log_levels = TIAAFile::LOG_LEVELS;
		echo '<tr><th>' . esc_html__( 'Log Levels', 'tiaa-wpplugin' ) . '</th><td>';
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
			esc_html__( 'File Path', 'tiaa-wpplugin' ),
			'file_path',
            null,
            array( 'style' => 'width: 25em;' )
		);
	}
}