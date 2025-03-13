<?php

/**
 * Base Class for TIAA WordPress Plugin.
 *
 * This class provides the foundational functionality for the TIAA plugin,
 * including initializing plugin options and hooks. It serves as a central class
 * for managing key features of the plugin.
 *
 * @package TIAAPlugin
 * @author Lew Grothe, TIAA Admin Platform sub-team
 * @link    https://tiaa-forum.org/contact
 * @license GPL-2.0-or-later
 * @since   0.0.3
 */

namespace TIAAPlugin\lib;

use TIAAPlugin\Analog\Analog;

/**
 * Class TiaaBase
 *
 * Represents the base class of the TIAA Plugin. It provides access
 * to plugin options, hooks, and initialization routines essential
 * for the plugin's operation.
 *
 * @since 0.0.3
 */
class TiaaBase {
	use PluginUtil;

	/**
	 * Plugin options.
	 *
	 * Provides access to all stored options for the plugin.
	 *
	 * @access protected
	 * @var mixed|void $options Plugin configuration options.
	 */
	protected $options;

	/**
	 * Plugin hooks.
	 *
	 * Manages the hooks registered for the plugin.
	 *
	 * @access private
	 * @var TiaaHooks|null $hooks Registered hooks for the plugin.
	 */
	private ?TiaaHooks $hooks = null;

	/**
	 * Constructor.
	 *
	 * Sets up the necessary actions and initializes the plugin.
	 *
	 * @since 0.0.3
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'initialize_plugin' ) );
	}

	/**
	 * Initializes the plugin configuration.
	 *
	 * This function checks whether the hooks have been set, initializes
	 * plugin options, and starts logging debug information for the plugin.
	 *
	 * @since 0.0.3
	 *
	 * @return void
	 */
	public function initialize_plugin() : void {
		if ( ! $this->hooks ) {
			$this->hooks = new TiaaHooks();
			$this->options = $this->get_all_options();
			self::log_debug( "log start" );
		}
	}
}