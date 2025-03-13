<?php
/**
 * Admin Initialization
 *
 * This file handles the initialization of the admin menu and settings for the TIAAPlugin.
 * It sets up various admin pages, settings validation, and script enqueueing.
 *
 * @package TIAAPlugin\Admin
 * @author Lew Grothe, TIAA Admin Platform sub-team
 * @link https://tiaa-forum.org/contact
 * @since    0.0.3
 */

namespace TIAAPlugin\Admin;

// Check for admin-specific environment and include necessary files.
if ( is_admin() ) {
	// Require all admin-related files for configuration and settings management.
	require_once __DIR__ . '/admin-menu.php';
	require_once __DIR__ . '/ConnectionSettings.php';
	require_once __DIR__ . '/FormHelper.php';
	require_once __DIR__ . '/InviteSettings.php';
	require_once __DIR__ . '/options-page.php';
	require_once __DIR__ . '/settings-validator.php';
	require_once __DIR__ . '/LogSettings.php';
	require_once __DIR__ . '/GroupInviteSettings.php';
	require_once __DIR__ . '/ScreenedEmailsSettings.php';
	require_once __DIR__ . '/ScreenedEmailsHandler.php';
	require_once __DIR__ . '/WelcomeSettings.php';
	require_once __DIR__ . '/WelcomeDataHandler.php';

	// Initialize key components for the admin interface.
	$form_helper  = FormHelper::get_instance();
	$options_page = OptionsPage::get_instance();

	new AdminMenu( $options_page, $form_helper );
	new ConnectionSettings( $form_helper );
	new InviteSettings( $form_helper );
	new LogSettings( $form_helper );
	new GroupInviteSettings( $form_helper );
	new ScreenedEmailsSettings( $form_helper );
	new WelcomeSettings( $form_helper );
	new SettingsValidator();

	// Enqueue admin-specific scripts and styles.
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\tiaa_enqueue_admin_scripts' );
} // End if ( is_admin() )

/**
 * Enqueue Admin Styles and Scripts
 *
 * Registers and enqueues styles and JavaScript files required for the admin interface.
 *
 * @since 0.0.3
 * @return void
 */
function tiaa_enqueue_admin_scripts() {
	// Define the path to the admin CSS file.
	$style_path = '/assets/css/tiaa-admin-styles.css';

	// Register and enqueue the admin styles.
	wp_register_style(
		'tiaa_admin_styles',
		plugins_url( $style_path, __FILE__ ),
		array(),
		filemtime( plugin_dir_path( __FILE__ ) . $style_path )
	);
	wp_enqueue_style( 'tiaa_admin_styles' );

	// Define the path to the admin JavaScript file.
	$script_path = '/assets/js/tiaa-admin.js';

	// Register and enqueue the admin JavaScript file with dependencies.
	wp_register_script(
		'tiaa_admin_js',
		plugins_url( $script_path, __FILE__ ),
		array( 'jquery', 'tags-box' ),
		filemtime( plugin_dir_path( __FILE__ ) . $script_path ),
		true
	);
	wp_enqueue_script( 'tiaa_admin_js' );
}