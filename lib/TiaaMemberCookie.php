<?php
/**
 * Class TiaaMemberCookie
 *
 * Sets a long-lived tiaa_member cookie on the first logged-in page load.
 * Persists after logout — intentional. Adds 'tiaa-returning-member' body
 * class whenever cookie is present so Elementor can target returning members.
 * Cookie domain read from TiaaSiteSettings::get_cookie_domain().
 *
 * LOGOUT: Verify WP-Discourse → SSO → "Enable SSO Provider Logout" is ON
 * for bidirectional session termination. tiaa_member is not cleared on logout.
 *
 * @package TIAAPlugin
 * @subpackage TIAAPlugin\lib
 * @since 0.0.5
 */

namespace TIAAPlugin\lib;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TiaaMemberCookie {

	const COOKIE_NAME  = 'tiaa_member';
	const COOKIE_VALUE = '1';
	const COOKIE_TTL   = YEAR_IN_SECONDS;
	const BODY_CLASS   = 'tiaa-returning-member';

	public function __construct() {
		add_action( 'init',       [ $this, 'maybe_set_member_cookie' ], 20 );
		add_filter( 'body_class', [ $this, 'add_returning_member_class' ] );
	}

	public function maybe_set_member_cookie(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || wp_is_json_request() ) {
			return;
		}
		if ( ! is_user_logged_in() || ! empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return;
		}

		setcookie( self::COOKIE_NAME, self::COOKIE_VALUE, [
			'expires'  => time() + self::COOKIE_TTL,
			'path'     => '/',
			'domain'   => TiaaSiteSettings::get_cookie_domain(),
			'secure'   => is_ssl(),
			'httponly' => false,
			'samesite' => 'Lax',
		] );

		// Backfill so body_class filter sees the cookie in this same request.
		$_COOKIE[ self::COOKIE_NAME ] = self::COOKIE_VALUE;
	}

	public function add_returning_member_class( array $classes ): array {
		if ( ! empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			$classes[] = self::BODY_CLASS;
		}
		return $classes;
	}
}