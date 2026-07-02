<?php
/**
 * Class TiaaLoginRedirect
 *
 * Eliminates the "flash" of the WordPress SSO callback page after Discourse
 * authentication by intercepting at template_redirect and issuing a
 * server-side redirect to Discourse before any HTML is rendered.
 *
 * HOW IT WORKS
 * ------------
 * When Discourse completes SSO it redirects the user to the WordPress
 * callback URL (the "logged-in landing page") with ?sso=...&sig=... params.
 * WP-Discourse intercepts this early in the request lifecycle, validates the
 * payload, creates/updates the WordPress user, and logs them in. By the time
 * template_redirect fires, the user is already authenticated in WordPress.
 *
 * This class hooks into template_redirect at priority 20 (after WP-Discourse),
 * detects the SSO callback by the presence of the sso and sig query params,
 * confirms the user is logged in, and redirects to Discourse home. The
 * WordPress page never renders — the user sees no flash.
 *
 * CALLBACK URL
 * ------------
 * The WordPress SSO callback URL is:
 *   home_url() + Login Path (WP-Discourse SSO setting)
 * This class does not change that URL or its WP-Discourse configuration.
 *
 * DISCOURSE URL
 * -------------
 * Read from WP-Discourse's own stored options (wpdc_options → url) so we
 * don't duplicate configuration. Falls back to home_url() if not set.
 *
 * @package TIAAPlugin
 * @subpackage TIAAPlugin\lib
 * @since 0.0.6
 */

namespace TIAAPlugin\lib;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TiaaLoginRedirect {

	public function __construct() {
		// Priority 20 — after WP-Discourse's template_redirect processing (priority 10).
		add_action( 'template_redirect', [ $this, 'maybe_redirect_after_sso' ], 20 );
	}

	/**
	 * Redirects to Discourse after a successful SSO callback.
	 *
	 * Fires on template_redirect for every request. Bails immediately unless
	 * all three conditions are met:
	 *   1. SSO query params are present (this is the Discourse callback)
	 *   2. User is logged in (WP-Discourse has completed its processing)
	 *   3. Not in admin/AJAX/cron context
	 *
	 * @return void
	 */
	public function maybe_redirect_after_sso(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		// Only act on Discourse SSO callback requests.
		if ( ! $this->is_sso_callback() ) {
			return;
		}

		// WP-Discourse should have logged the user in by now.
		// If not, something went wrong — let the page render so errors are visible.
		if ( ! is_user_logged_in() ) {
			return;
		}

		// TiaaSiteSettings is the single point of access for the Discourse URL.
		// It reads from WP-Discourse options — no duplicated config.
		$discourse_url = TiaaSiteSettings::get_discourse_url();

		if ( empty( $discourse_url ) ) {
			// WP-Discourse not configured — fall back to WP home rather than
			// redirecting to an empty URL.
			$discourse_url = home_url( '/' );
		}

		wp_safe_redirect( $discourse_url, 302 );
		exit;
	}

	/**
	 * Returns true if the current request is a Discourse SSO callback.
	 *
	 * Detects by the presence of both 'sso' and 'sig' query parameters,
	 * which Discourse always appends to the return URL after authentication.
	 * This is more robust than matching the page slug, which can change.
	 *
	 * @return bool
	 */
	private function is_sso_callback(): bool {
		return ! empty( $_GET['sso'] ) && ! empty( $_GET['sig'] );
	}
}
