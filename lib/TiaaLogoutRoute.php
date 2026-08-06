<?php
/**
 * Class TiaaLogoutRoute
 *
 * Provides a `/tiaa-logout` endpoint that logs the user out without touching
 * `wp-login.php`.
 *
 * WHY THIS EXISTS
 * ----------------
 * The Elementor header's logout link normally points to
 * `/wp-login.php?action=logout`. Wordfence's Brute Force Protection locks
 * out `wp-login.php` after repeated failed login attempts — and since
 * logout goes through the same script, a locked-out visitor cannot log out
 * either. `/tiaa-logout` never touches `wp-login.php`, so it stays reachable
 * during a lockout.
 *
 * It still calls wp_logout(), which fires the same `clear_auth_cookie`
 * action WP-Discourse's SSO Client hooks to end the corresponding Discourse
 * session — so SSO logout continues to work identically. Verified by
 * instrumenting both this route and the original /wp-login.php?action=logout
 * flow: both hit the exact same clear_auth_cookie → logout_from_discourse()
 * path with identical results (see tiaa-wpplugin README for the local-dev
 * SSL caveat found during that verification).
 *
 * NO NONCE CHECK
 * ---------------
 * Deliberate: the logout link this replaces has never had a nonce either
 * (logout is non-destructive), and adding one here would introduce a
 * confirmation step that doesn't exist today. Not an oversight — do not
 * "fix" this by adding wp_verify_nonce().
 *
 * NO REWRITE RULE
 * -----------------
 * Matches directly off REQUEST_URI on `init` instead of registering a
 * rewrite rule + query var, so there's no flush_rewrite_rules() step to
 * remember on deploy.
 *
 * @package TIAAPlugin
 * @subpackage TIAAPlugin\lib
 * @since 0.0.12
 */

namespace TIAAPlugin\lib;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TiaaLogoutRoute {

	const ROUTE_PATH = '/tiaa-logout';

	public function __construct() {
		add_action( 'init', [ $this, 'maybe_handle_logout' ] );
	}

	/**
	 * Handles the /tiaa-logout route: logs the user out and redirects.
	 *
	 * Bails immediately unless the current request path matches
	 * /tiaa-logout. On match, logs the user out (firing wp_logout, and in
	 * turn clear_auth_cookie for WP-Discourse's SSO teardown) and redirects
	 * to the validated redirect_to param, or home if absent/invalid.
	 *
	 * @return void
	 */
	public function maybe_handle_logout(): void {
		if ( ! $this->is_logout_request() ) {
			return;
		}

		$fallback_url = home_url( '/' );
		$redirect_to  = $fallback_url;

		if ( ! empty( $_GET['redirect_to'] ) ) {
			$requested_url = esc_url_raw( wp_unslash( $_GET['redirect_to'] ) );
			$redirect_to   = wp_validate_redirect( $requested_url, $fallback_url );
		}

		wp_logout();
		wp_safe_redirect( $redirect_to );
		exit;
	}

	/**
	 * Returns true if the current request path is /tiaa-logout.
	 *
	 * Compares against home_url( '/tiaa-logout' ) rather than a hardcoded
	 * path so this still works if WP is installed in a subdirectory.
	 * Trailing-slash tolerant.
	 *
	 * @return bool
	 */
	private function is_logout_request(): bool {
		$request_path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
		if ( ! $request_path ) {
			return false;
		}

		$logout_path = wp_parse_url( home_url( self::ROUTE_PATH ), PHP_URL_PATH );

		return untrailingslashit( $request_path ) === untrailingslashit( $logout_path );
	}
}
