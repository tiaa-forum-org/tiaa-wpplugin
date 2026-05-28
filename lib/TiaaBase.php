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
		// Priority 3: must run before WP-Discourse SSO Client, which fires at priority 4–5.
		add_action( 'init', array( $this, 'initialize_plugin' ), 3 );
	}

	/**
	 * Initializes the plugin configuration.
	 *
	 * Runs once on `init` (priority 3, before WP-Discourse SSO at 4–5). Guards
	 * against double-initialization with the $hooks null check.
	 *
	 * Registers an `auth_cookie_expiration` filter to extend the WordPress
	 * authentication cookie lifetime to 30 days for all logged-in users.
	 * Discourse is the SSO authority — WP session length is secondary, and a
	 * shorter cookie would log members out of WP while they remain active on
	 * Discourse, causing confusing split-session states.
	 *
	 * @since 0.0.3
	 * @since 0.0.7 Added auth_cookie_expiration filter; removed inline class
	 *              instantiation of site settings and cookie helpers (now
	 *              self-registering via their own constructors).
	 *
	 * @return void
	 */
	public function initialize_plugin() : void {
		if ( ! $this->hooks ) {
			$this->hooks = new TiaaHooks();
			$this->options = $this->get_all_options();
			new WelcomeUtil();

			// Extend WP auth cookie to 30 days for members (Discourse is SSO authority)
			add_filter( 'auth_cookie_expiration', function( $length, $user_id, $remember ) {
				return 30 * DAY_IN_SECONDS;
			}, 10, 3 );
		}
	}
}