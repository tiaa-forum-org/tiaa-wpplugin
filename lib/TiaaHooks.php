<?php
/**
 * Handles hooks and REST routes between WordPress and Discourse.
 *
 * This class registers REST API endpoints and provides functionality
 * to manage communication with the Discourse instance.
 *
 * @package TIAAPlugin
 * @subpackage TIAAPlugin\lib
 * @author Lew Grothe, TIAA Admin Platform sub-team
 * @email info@tiaa-forum.org
 * @link https://tiaa-forum.org/contact
 */
namespace TIAAPlugin\lib;

use TIAAPlugin\ScreenEmailsUtil;
use WP_REST_Request;
use WP_REST_Response;
use Exception;
/**
 * Class TiaaHooks
 *
 * Contains implementations for REST API endpoints
 * to facilitate communication with Discourse and other utility handlers.
 *
 * @package TIAAPlugin\lib
 */
class TiaaHooks {
	use PluginUtil;

	/**
	 * Instance of ScreenEmailsUtil for handling email-related operations.
	 *
	 * @var ScreenEmailsUtil|null
	 */
	private ?ScreenEmailsUtil $screen = null;

	/**
	 * TiaaHooks constructor.
	 *
	 * Initializes the hooks used by this class, primarily registering
	 * REST API routes via the `rest_api_init` action.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'initialize_hooks' ) );
		add_filter( 'cron_schedules', array( $this, 'register_test_cron_intervals' ) );
		add_action( 'login_init', array( $this, 'skip_logout_confirmation' ) );
		add_filter( 'wpdc_sso_client_redirect_after_login', array( $this, 'redirect_after_sso_login' ) );
		add_filter( 'body_class', array( $this, 'add_member_body_class' ) );
	}

	/**
	 * Adds 'tiaa-member' to the WordPress body class list when the tiaa_member
	 * cookie is present.
	 *
	 * The tiaa_member cookie is written by TiaaMemberCookie on first logged-in
	 * page load and intentionally persists after logout — it marks a visitor as a
	 * known member regardless of current login state. This body class makes that
	 * state available to CSS and Elementor display conditions on every page load
	 * where the cookie exists, covering all three visitor states:
	 *
	 *   anonymous (no cookie)  →  class absent
	 *   returning member       →  class present, no WP session
	 *   logged-in member       →  class present, WP session active
	 *
	 * @since  0.0.8
	 * @param  string[] $classes Existing body classes.
	 * @return string[]          Modified body classes.
	 */
	public function add_member_body_class( array $classes ): array {
		if ( isset( $_COOKIE['tiaa_member'] ) && ! empty( $_COOKIE['tiaa_member'] ) ) {
			$classes[] = 'tiaa-member';
		}
		return $classes;
	}

	/**
	 * Skips the WordPress logout confirmation page for SSO-originated links.
	 *
	 * WordPress shows a "Do you really want to log out?" confirmation when the
	 * logout link is missing a `_wpnonce` parameter — a common side-effect of
	 * SSO-generated logout links, which Discourse mints without knowledge of a
	 * WP nonce. This hook detects that case and immediately redirects to the
	 * properly nonce'd logout URL produced by `wp_logout_url()`.
	 *
	 * Scoped to requests referred from the configured Discourse host only
	 * (SECURITY-REVIEW.md F5): without that check, any third-party page could
	 * link to `/wp-login.php?action=logout` and force-log-out a visiting
	 * member with no confirmation step at all. A page cannot forge the
	 * Referer WordPress sees for a request it triggers — that header always
	 * reflects the page the link actually lives on — so this narrows the
	 * auto-skip to links genuinely embedded in Discourse.
	 *
	 * NOTE this protection is specific to this route. `TiaaLogoutRoute`'s
	 * `/tiaa-logout` endpoint has no equivalent check (N4, follow-up scan,
	 * 2026-08) -- reviewed and accepted as a deliberate inconsistency, see
	 * that class's docblock for why.
	 *
	 * Security is preserved either way: the nonce is still generated and will
	 * be validated on the subsequent request. This only removes the manual
	 * confirmation step, and only for the Discourse-originated case.
	 *
	 * @since  0.0.6
	 * @return void
	 */
	public function skip_logout_confirmation(): void {

		if (
			isset( $_REQUEST['action'] ) &&
			$_REQUEST['action'] === 'logout' &&
			! isset( $_REQUEST['_wpnonce'] ) &&
			$this->referred_from_discourse()
		) {
			$redirect = isset( $_REQUEST['redirect_to'] )
				? esc_url_raw( $_REQUEST['redirect_to'] )
				: home_url( '/' );

			$logout_url = html_entity_decode( wp_logout_url( $redirect ) );
			wp_redirect( $logout_url );
			exit;
		}
	}

	/**
	 * Returns true if the current request's Referer host matches the
	 * configured Discourse URL's host.
	 *
	 * @since  0.0.13
	 * @return bool
	 */
	private function referred_from_discourse(): bool {
		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '';
		if ( ! $referer ) {
			return false;
		}
		$discourse_url = TiaaSiteSettings::get_discourse_url();
		if ( ! $discourse_url ) {
			return false;
		}
		$referer_host   = wp_parse_url( $referer, PHP_URL_HOST );
		$discourse_host = wp_parse_url( $discourse_url, PHP_URL_HOST );
		return $referer_host && $discourse_host && $referer_host === $discourse_host;
	}

	/**
	 * Redirects to Discourse home after a successful WP-Discourse SSO Client login.
	 *
	 * WP-Discourse SSO Client would normally return the user to the WP page that
	 * initiated the SSO flow. For TIAA, login is always initiated to reach Discourse,
	 * so we override the destination to Discourse home. WP is logged in as a
	 * side-effect; the tiaa_wp_return_url cookie lets the Discourse brand nav link
	 * back to the originating WP page if needed.
	 *
	 * @since  0.0.6
	 * @param  string $return_url The URL WP-Discourse would redirect to by default.
	 * @return string             Discourse home URL, or $return_url if not configured.
	 */
	public function redirect_after_sso_login( string $return_url ): string {
		$discourse_url = TiaaSiteSettings::get_discourse_url();
		return $discourse_url ?: $return_url;
	}

	/**
	 * Initializes various hooks and routes.
	 *
	 * Responsible for registering REST API routes required for operating
	 * with Discourse.
	 *
	 * @return void
	 */
	public function initialize_hooks(): void {
		$this->screen = new ScreenEmailsUtil();
		$this->register_invite_route();
		$this->register_discourse_ping_route();
		$this->register_get_discourse_post_by_id();
	}

	public function register_discourse_ping_route() :bool {
		$results = register_rest_route( TIAA_HOOK_NAMESPACE, '/tiaa_discourse_ping', array(
			'methods'  => 'GET',
			'permission_callback'  => function () {
				return current_user_can( 'manage_options' );
			},
			'args' => array (
				'option_group' => array(
					'validate_callback' => function ($param,
					$request, $key) {
						return is_string($param);

					}
				)
			),
			'callback' => array($this, 'do_ping_discourse_server')
		), true );
		return $results;
	}

	/**
	 * Registers the REST API route for inviting members to Discourse.
	 *
	 * The route allows users to invite others by sending a POST request
	 * with necessary data (e.g., name and email).
	 *
	 * @return bool True if the route was successfully registered, false otherwise.
	 */
	public function register_invite_route(): bool {
		$results = register_rest_route(
			TIAA_HOOK_NAMESPACE,
			'/invite',
			array(
				'methods'              => 'POST',
				// Intentionally public: hit by anonymous visitors via Elementor
				// invite forms. __return_true is passed as a callable reference,
				// not called here — a prior version accidentally called
				// __return_empty_string() instead, which happened to also grant
				// access, but only because WP core's dispatch check treats an
				// empty-string permission_callback as absent rather than denying.
				'permission_callback'  => '__return_true',
				'callback'             => array( $this, 'invite_to_discourse' ),
			),
			true
		);
		return $results;
	}

	/**
	 * Max invite requests allowed per IP within INVITE_RATE_LIMIT_WINDOW seconds.
	 *
	 * @since 0.0.13
	 */
	private const INVITE_RATE_LIMIT_MAX = 5;

	/**
	 * Rate-limit window, in seconds, for the invite endpoint.
	 *
	 * @since 0.0.13
	 */
	private const INVITE_RATE_LIMIT_WINDOW = 60;

	/**
	 * Returns true if the requesting IP has exceeded the invite rate limit.
	 *
	 * Basic per-IP throttle (SECURITY-REVIEW.md F6) against an anonymous
	 * caller driving the site's Discourse into sending invite-email spam.
	 * Uses REMOTE_ADDR directly rather than X-Forwarded-For, since the
	 * latter is trivially spoofable unless a trusted reverse proxy is
	 * configured to overwrite it — if this site is ever put behind a proxy
	 * that forwards the real client IP via a header, this will need updating
	 * to read that header instead, or every caller will share one bucket.
	 *
	 * Confirmed with the maintainer (N3, follow-up scan, 2026-08) that
	 * production has no reverse proxy in front of PHP — REMOTE_ADDR is the
	 * real client IP today, so this is correct as written. That's an
	 * infrastructure fact, not something this code can verify at runtime:
	 * if that ever changes (a CDN, load balancer, or reverse proxy gets put
	 * in front of PHP), this silently degrades to one shared bucket for
	 * every visitor with no error or warning. Re-check this assumption
	 * whenever the deployment topology changes.
	 *
	 * @since 0.0.13
	 * @return bool
	 */
	private function invite_rate_limit_exceeded(): bool {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		if ( empty( $ip ) ) {
			// No IP to key on — fail open rather than block legitimate submitters.
			return false;
		}
		$key   = 'tiaa_invite_rl_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count >= self::INVITE_RATE_LIMIT_MAX ) {
			return true;
		}
		set_transient( $key, $count + 1, self::INVITE_RATE_LIMIT_WINDOW );
		return false;
	}

	/**
	 * Handles the invitation of members to Discourse via a REST API request.
	 *
	 * This method processes the request payload (either JSON-encoded or form-encoded),
	 * validates required parameters (name and email), and sends an invitation
	 * to the Discourse platform. It also handles optional parameters such as
	 * group, message, or topic.
	 *
	 * @param WP_REST_Request $request The REST API request containing the invitation data.
	 *
	 * @return WP_REST_Response The response object, indicating success or error.
	 *
	 * @throws Exception If unable to fetch or process connection options for Discourse.
	 */
	public function invite_to_discourse( WP_REST_Request $request ): WP_REST_Response {
		if ( $this->invite_rate_limit_exceeded() ) {
			self::log_notice( 'invite rate limit exceeded for ' . ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) );
			return new WP_REST_Response(
				array(
					'success' => false,
					'code'    => 'rate_limited',
					'message' => 'Too many requests. Please try again shortly.',
				),
				429
			);
		}

		$content_type = $request->get_header('content-type');
		if ( empty($content_type) || str_contains( $content_type, 'application/json' ) ) {
		    // Handle JSON-encoded body
			$data_json = $request->get_json_params();
			// elementor + wp_fetch puts the form fields in a junked up json
			// parameter string like:'"form_fields[name]" : "John Smith"' so we have
			// to parse that
			$data = [];
			foreach ($data_json as $key => $value) {
				if (($key === 'form_fields') && is_array($value)) {
					$data += $value;
					// note: the \[ in the pattern escapes both brackets
				} elseif ( preg_match( '/form_fields\[(.*?)]/', $key, $matches ) ) {
					$field_name          = $matches[1];
					$data[ $field_name ] = $value;
				} else {
					$data[ $key ] = $value;
				}
			}
			self::log_debug("got invite_to_discourse1: " . self:: array_to_string($data) );
		} else {
			// Handle form-encoded body
			$data = $request->get_body_params();
			// if the advanced data option is set on the elementor pro form, need
			// to parse data values
			if (isset($data['fields']) && is_array($data['fields'])) {
				$data = array_reduce($data['fields'], callback: function ($carry, $field) {
					$carry[$field['title']] = $field['value'];
					return $carry;
				}, initial: []);
			}
			self::log_debug("got invite_to_discourse2: " . self::array_to_string($data) );
		}
	// Check if the required data (name and email) is present
		if ( empty( $data['name'] ) ||  ($data['name'] == '') ||
		     empty( $data['email'] ) ) {
			$msg =  print_r($data, true);
			$data['message'] = 'missing name or email data for invite';
			$return_err = new WP_REST_Response( data: $data, status: 500);
			self::log_wp_rest_response_error( $msg, $return_err, __FUNCTION__, __CLASS__, __LINE__ );
			return $return_err;
		} else {
			if ( $this->screen->is_screened_email($data['email']) === true) {
				self::log_debug($data['email'] . " is a screened email");
				// Uniform response (SECURITY-REVIEW.md F6, hardened per N2 in the
				// follow-up scan): must not be distinguishable from a genuine
				// successful invite response, or an anonymous caller could probe
				// this endpoint to test whether any given email is on the
				// screened list. A real success (Discourse::handle_discourse_response())
				// always includes a body_response key -- this branch was missing
				// it, which was itself a reliable "screened" signal (Object.keys()
				// diffing the two responses). Adding a synthetic one here closes
				// that. Note: this does NOT close the timing side-channel (this
				// branch returns immediately, a real invite makes a network call
				// to Discourse) -- that's accepted as a known residual for now,
				// not fixed by this change.
				$response = new WP_REST_Response(
					array(
						'success'       => true,
						'status'        => 200,
						'response'      => 'OK',
						'body_response' => '{}',
					), 200 );
				return rest_ensure_response( $response );
			}
			if (!empty($data['group'])) {
				// Elementor seems to not be able to pass a null value from a form
				if ($data['group'] === 'none') {
					unset ($data['group']);
					$option_group = TIAA_INVITE_GROUP;
				} else {
					$req_data['group_names'] = $data['group'];
					// if it's not a valid group, get_connection_options...() will fail
					// used to be str_tolower() but discourse group slugs are case sensitive
					$option_group = TIAA_GROUP_INVITE_GROUP . $data['group'];
				}
			} else {
				$option_group = TIAA_INVITE_GROUP;
			}
			$cs = Discourse::get_connection_options_by_group($option_group);
			if (is_wp_error($cs)) {
				self::log_wp_error(  'invite', $cs, __FUNCTION__, __CLASS__, __LINE__);
				$data = array('message' => $cs->get_error_message(), 'code' => $cs->get_error_code());
				$response = new WP_REST_Response( $data, 500);
				return rest_ensure_response( $response );
			}
			$req_data['name'] = $data['name'];
			$req_data['email'] = $data['email'];
			if (empty($cs) ||
			    empty($cs['url']) ||
			    empty($cs['api_key']) ||
			    empty($cs['username']) ){
				return new WP_REST_Response(array('code' =>'no_connections',
				                                  'message' => 'Discourse connections not set'), status: 500);
			}
			$req_data['url'] = $cs['url'];
			$req_data['username'] = $cs['username'];
			$req_data['api_key'] = $cs['api_key'];
			if (!empty($data['message'])) {
				$req_data['message'] = $data['message'];
			}
			if (!empty($data['topic'])) {
				$req_data['topic'] = $data['topic'];
			}
			self::log_info("got discourse invite: " . implode(":", $data) );
			$request = new WP_REST_Request;
			$request->set_body_params($req_data);
			return Discourse::send_discourse_invite($request);

		}

	}

	/**
	 * Registers a REST API route for retrieving Discourse posts by ID.
	 *
	 * This route allows fetching posts from Discourse, verifying input
	 * parameters before processing the request.
	 *
	 * The request to Discourse is made with the site's stored (privileged)
	 * API credentials, so this must not be publicly reachable — an anonymous
	 * caller could otherwise read any Discourse post by ID, including
	 * private/staff content, to whatever extent the configured key allows.
	 * The only current callers are "Get Message" / "Ping test" preview links
	 * on admin settings pages (GroupInviteSettings, InviteSettings,
	 * WelcomeSettings), all rendered behind manage_options — gating the route
	 * itself the same way just closes the anonymous-access gap without
	 * touching those callers.
	 *
	 * @return bool True if the route was successfully registered, false otherwise.
	 */
	public function register_get_discourse_post_by_id(): bool {
		$results = register_rest_route(
			TIAA_HOOK_NAMESPACE,
			'/get_discourse_post',
			array(
				'methods'              => 'GET',
				'permission_callback'  => function () {
					return current_user_can( 'manage_options' );
				},
				'callback'             => array( $this, 'get_discourse_post_by_id' ),
				'args'                 => array(
					'post_id'      => array(
						'required'          => true,
						'validate_callback' => function( $param, $request, $key ) {
							return is_numeric( $param );
						},
					),
					'option_group' => array(
						'required'          => true,
						'validate_callback' => function( $param, $request, $key ) {
							return is_string( $param );

						},
					),
				),
			),
			true
		);
		return $results;
	}

	/**
	 * Registers custom WP-Cron intervals for testing purposes.
	 *
	 * Adds a 5-minute interval to the available WP-Cron schedules, enabling
	 * faster feedback during local development and cron job testing.
	 *
	 * ⚠️  TEMPORARY — TESTING ONLY.
	 * This function exists solely to support the 'every_five_minutes' option
	 * in the Welcome Settings cron interval selector. It should be removed
	 * before production deployment, along with the corresponding select option
	 * in WelcomeSettings::render_cron_interval_field().
	 *
	 * Note: This filter must fire on every page load — not just in admin —
	 * because WP-Cron can be triggered by any front-end request. Registering
	 * it only in the admin context would cause WordPress to silently reject
	 * the interval when the scheduled event actually fires.
	 *
	 * @since  0.0.4
	 * @param  array $schedules Existing WP-Cron schedule definitions.
	 * @return array            Modified schedules array with test interval added.
	 *
	 * @see    WelcomeSettings::render_cron_interval_field()
	 * @todo   Remove this function and its constructor hook before production release.
	 */
	public function register_test_cron_intervals( array $schedules ): array {
		$schedules['every_five_minutes'] = array(
			'interval' => 300,
			'display'  => __( 'Every 5 Minutes (testing only)', 'tiaa-wpplugin' ),
		);
		return $schedules;
	}
}