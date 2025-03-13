<?php
/**
 * Handles form-related helper functions for the TIAA WordPress plugin.
 *
 * This class includes methods for rendering input fields, setting up plugin options, and validating options.
 *
 * @package TIAAPlugin\Admin
 * @author  Lew Grothe, TIAA Admin Platform sub-team
 * @link    https://tiaa-forum.org/contact
 * @since   0.0.3
 */

namespace TIAAPlugin\Admin;
use TIAAPlugin\lib\PluginUtil;

/**
 * The FormHelper class provides utility methods for managing forms and options in the admin area.
 *
 * @since 0.0.3
 */
class FormHelper {
	use PluginUtil;

	/**
	 * A single instance of the FormHelper class to ensure it's used as a singleton.
	 *
	 * @access protected
	 * @since  0.0.3
	 * @var    FormHelper|null
	 */
	protected static ?FormHelper $instance = null;

	/**
	 * Stores all the plugin options.
	 *
	 * @access protected
	 * @since  0.0.3
	 * @var    mixed|void
	 */
	protected $options;

	/**
	 * Returns a single instance of the FormHelper class.
	 *
	 * Ensures that only one instance of FormHelper is created during execution.
	 *
	 * @since 0.0.3
	 * @return FormHelper The singleton instance of the class.
	 */
	public static function get_instance() : FormHelper {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Class constructor.
	 *
	 * Adds hooks required for initializing the plugin's settings.
	 *
	 * @access protected
	 * @since 0.0.3
	 */
	protected function __construct() {
		add_action( 'admin_init', array( $this, 'setup_options' ) );
	}

	/**
	 * Sets or updates the plugin options, optionally accepts test settings.
	 *
	 * @since 0.0.3
	 * @param object $extra_options Extra options passed for testing; optional.
	 * @return void
	 */
	public function setup_options(  $extra_options = null ) : void {
		$this->options = $this->get_all_options();

		if ( ! empty( $extra_options ) ) {
			foreach ( $extra_options as $key => $value ) {
				$this->options[ $key ] = $value;
			}
		}
	}

	/**
	 * Displays a connection status notice, based on the current page or tab.
	 *
	 * Currently, the implementation within this method is incomplete.
	 *
	 * @since 0.0.3
	 * @return void
	 */
	public function connection_status_notice() : void {
		if ( ! empty( $_GET['tab'] ) ) { // Input var okay.
			$current_page = sanitize_key( wp_unslash( $_GET['tab'] ) ); // Input var okay.
		} elseif ( ! empty( $_GET['page'] ) ) { // Input var okay.
			$current_page = sanitize_key( wp_unslash( $_GET['page'] ) ); // Input var okay.
		} else {
			$current_page = null;
		}
	}

	/**
	 * Renders an input field for the WordPress admin settings.
	 *
	 * @since 0.0.3
	 * @param string      $option        The name of the option.
	 * @param string      $option_group  The option group for the field to be saved to.
	 * @param ?string      $description   The description displayed below the input.
	 * @param null|string $type          The type of input (e.g., 'text', 'number', 'email', etc.), defaults to text.
	 * @param null|string $default       The default value of the input.
	 * @param ?array       $args          Additional HTML attributes for the input element.
	 * @return void
	 */
	public function input( string $option, string $option_group, ?string $description,
        ?string $type = null, ?string $default = null, ?array $args = array() ) : void {
		$options = $this->get_options_by_group( $option_group );
		$allowed = array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			),
		);

		if ( ($type === 'array' || $type === 'email_list') && ! empty( $options[ $option ])) {
			$value = implode(', ', $options[ $option ]);
		} elseif ( ! empty( $options[ $option ] ) || ( isset( $options[ $option ] ) && 0 === $options[ $option ] ) ) {
			$value = $options[ $option ];
		} elseif ( ! empty( $default ) ) {
			$value = $default;
		} else {
			$value = '';
		}
		?>
        <input id="-<?php echo esc_attr( $option ); ?>"
               name='<?php echo esc_attr( $this->option_array_name( $option, $option_group ) ); ?>'
               type="<?php echo isset( $type ) ? esc_attr( $type ) : 'text'; ?>"
               value='<?php echo esc_attr( $value ); ?>' class="regular-text ltr"
			<?php if (isset($args)) {
				foreach ($args as $key => $arg_value) {
					echo ' ' . $key . '="' . esc_attr($arg_value) . '"';
				}
			}?>>
        <p class="description"><?php echo wp_kses( $description, $allowed ); ?></p>
		<?php
	}

	/**
	 * Validates and sanitizes options provided by the admin user.
	 *
	 * Uses dedicated filters for input validation for each option.
	 *
	 * @since 0.0.3
	 * @param array $inputs Array of options to validate.
	 * @return array Returns the sanitized options.
	 */
	public function validate_options( array $inputs ) : array {
		$output = array();

		if ( ! empty( $inputs ) ) {
			foreach ( $inputs as $key => $input ) {
				$filter         = 'tiaa_validate_' . $key;
				$output[ $key ] = apply_filters( $filter, $input );
			}
		}
		return $output;
	}
}