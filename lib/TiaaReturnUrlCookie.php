<?php
/**
 * Class TiaaReturnUrlCookie
 *
 * Writes a tiaa_wp_return_url cookie before the SSO redirect to Discourse.
 * The Discourse brand header reads it to link back to the originating WP page.
 * Cookie domain read from TiaaSiteSettings::get_cookie_domain().
 *
 * ELEMENTOR: Add CSS class 'tiaa-sso-trigger' to the Join/Sign In button
 * (Advanced tab → CSS Classes). That is what TRIGGER_SELECTOR targets.
 *
 * @package TIAAPlugin
 * @subpackage TIAAPlugin\lib
 * @since 0.0.5
 */

namespace TIAAPlugin\lib;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TiaaReturnUrlCookie {

	const TRIGGER_SELECTOR = '.tiaa-sso-trigger';
	const COOKIE_TTL       = 3600; // 1 hour

	public function __construct() {
		add_action( 'wp_footer', [ $this, 'output_script' ] );
	}

	public function output_script(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || wp_is_json_request() ) {
			return;
		}
		if ( is_user_logged_in() ) {
			return;
		}

		$selector = esc_js( self::TRIGGER_SELECTOR );
		$ttl      = (int) self::COOKIE_TTL;
		$domain   = esc_js( TiaaSiteSettings::get_cookie_domain() );
		?>
		<script id="tiaa-return-url-cookie">
		(function () {
			'use strict';
			var SELECTOR = '<?php echo $selector; ?>';
			var TTL      = <?php echo $ttl; ?>;
			var DOMAIN   = '<?php echo $domain; ?>';

			function setReturnCookie() {
				var expires = new Date( Date.now() + TTL * 1000 ).toUTCString();
				document.cookie = [
					'tiaa_wp_return_url=' + encodeURIComponent( window.location.href ),
					'domain=' + DOMAIN,
					'path=/',
					'expires=' + expires,
					'SameSite=Lax'
				].join( '; ' );
			}

			document.querySelectorAll( SELECTOR ).forEach( function ( el ) {
				el.addEventListener( 'click', setReturnCookie );
			} );
		})();
		</script>
		<?php
	}
}