<?php
/**
 * Plugin Name: TIAA WordPress Plugin
 * Plugin URI: https://tiaa-forum.org/
 * Description: WordPress plugin in support of various aspects of tiaa-forum.org. Supports Invites, Welcome messages, etc,
 * Version: 0.0.3
 * Requires at least: 6.5
 * Requires PHP:      8.2.0
 * Author:            Lew Grothe and TIAA Forum Admin Platform sub-team.
 * Author URI:        https://tiaa-forum.org
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 */
if( !defined('ABSPATH') )
	exit;

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('TIAA_PLUGIN_PATH', plugin_dir_path( __FILE__ ));
define('TIAA_PLUGIN_URL', plugin_dir_url( __FILE__ ));
// define( 'MIN_WP_VERSION', '6.0' );
// define( 'MIN_PHP_VERSION', '8.0' );
define( 'TIAA_PLUGIN_LOGO_URL', TIAA_PLUGIN_PATH . 'assets/black-logo.svg' );
$base64 = base64_encode( file_get_contents( TIAA_PLUGIN_LOGO_URL ) );
define( 'TIAA_PLUGIN_LOGO', "data:image/svg+xml;base64,$base64" );
// prefix added to option groups
const TIAA_WP_OPTION_PREFIX = 'tiaa_';
// Constants for option group names
const TIAA_CONNECT_GROUP = TIAA_WP_OPTION_PREFIX . 'connection';
const TIAA_INVITE_GROUP  = TIAA_WP_OPTION_PREFIX . 'invite';
const TIAA_GROUP_LIST_GROUP = TIAA_WP_OPTION_PREFIX . 'groups';
const TIAA_GROUP_INVITE_GROUP = TIAA_WP_OPTION_PREFIX . 'group-invite-'; // will have '-group_name' suffix
const TIAA_WELCOME_GROUP = TIAA_WP_OPTION_PREFIX . 'welcome';
const TIAA_LOGGING_GROUP = TIAA_WP_OPTION_PREFIX . 'logging';
const TIAA_SCREENED_EMAIL_GROUP = TIAA_WP_OPTION_PREFIX . 'screened-emails';

const TIAA_HOOK_NAMESPACE = 'tiaa_wpplugin/v1'; // namespace for all hooks

require_once TIAA_PLUGIN_PATH . "vendor_prefixed/autoload.php";

global $wpdb;
define('TIAA_SCREENED_EMAILS_TABLE', $wpdb->prefix . 'tiaa_screened_emails');

require_once TIAA_PLUGIN_PATH . 'lib/options-utilities.php';
require_once TIAA_PLUGIN_PATH . 'lib/PluginUtil.php';
require_once TIAA_PLUGIN_PATH . 'lib/Discourse.php';
// require_once TIAA_PLUGIN_PATH . 'lib/utilities.php';
require_once TIAA_PLUGIN_PATH . 'lib/TiaaBase.php';
require_once TIAA_PLUGIN_PATH . 'lib/tiaa-hooks.php';
require_once TIAA_PLUGIN_PATH . 'lib/ScreenEmailsUtil.php';
require_once TIAA_PLUGIN_PATH . 'lib/WelcomeUtil.php';

//initializes the options_array

new \TIAAPlugin\lib\OptionsUtilities();
require_once TIAA_PLUGIN_PATH . 'admin/admin.php';

new TIAAPlugin\lib\TiaaBase();
