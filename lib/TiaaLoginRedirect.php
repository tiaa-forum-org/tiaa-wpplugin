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
 * callback URL with ?sso=...&sig=... params. WP-Discourse intercepts this at
 * init (priority 5), validates the payload, and logs the user in on success.
 * By the time template_redirect fires, the outcome is determined.
 *
 * SUCCESS PATH
 * After successful SSO: user is logged in → redirect to Discourse home (302).
 * The WordPress callback page never renders.
 *
 * FAILURE PATH
 * If SSO fails (bad credentials, cancelled login, invalid nonce) WP-Discourse
 * cannot log the user in. This class detects that (SSO params present but user
 * NOT logged in) and redirects to the Discourse login page with ?sso_failed=1.
 * The Discourse brand header component reads that param and injects a styled
 * error notice into the login form.
 *
 * ALLOWED REDIRECT HOSTS
 * wp_safe_redirect() only allows same-domain redirects by default. The
 * allow_discourse_host() method adds the Discourse hostname to the allowed
 * list via the allowed_redirect_hosts filter.
 *
 * DISCOURSE URL
 * TiaaSiteSettings::get_discourse_url() is the single source of truth —
 * reads from WP-Discourse options, no duplicated config.
 *
 * HOOK TIMING
 * Priority 20 on template_redirect — after WP-Discourse (default priority 10).
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
		add_action( 'template_redirect',    [ $this, 'maybe_redirect_after_sso' ], 20 );
		add_filter( 'allowed_redirect_hosts', [ $this, 'allow_discourse_host' ] );
	}

	/**
	 * Intercepts the Discourse SSO callback and redirects appropriately.
	 *
	 * Fires on template_redirect for every request. Bails immediately if
	 * SSO query params are absent or if running in admin/AJAX/cron context.
	 * On SSO callback: success → Discourse home; failure → Discourse login
	 * page with ?sso_failed=1.
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

		$discourse_url = TiaaSiteSettings::get_discourse_url();
		$fallback_url  = home_url( '/' );

		if ( is_user_logged_in() ) {
			// SSO succeeded — go to Discourse home.
			wp_safe_redirect( $discourse_url ?: $fallback_url, 302 );
			exit;
		}

		// SSO callback received but user not logged in — authentication failed.
		// Redirect to the Discourse login page with ?sso_failed=1 so the brand
		// header component can display a meaningful error notice.
		$login_url = add_query_arg(
			'sso_failed', '1',
			trailingslashit( $discourse_url ) . 'login'
		);
		wp_safe_redirect( $login_url ?: $fallback_url, 302 );
		exit;
	}

	/**
	 * Adds the Discourse hostname to WordPress's allowed redirect hosts.
	 * Required for wp_safe_redirect() to permit cross-subdomain redirects
	 * to Discourse on both the success and failure paths.
	 *
	 * @param  array $hosts Allowed hostnames.
	 * @return array
	 */
	public function allow_discourse_host( array $hosts ): array {
		$discourse_url = TiaaSiteSettings::get_discourse_url();
		if ( $discourse_url ) {
			$host = wp_parse_url( $discourse_url, PHP_URL_HOST );
			if ( $host ) {
				$hosts[] = $host;
			}
		}
		return $hosts;
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
