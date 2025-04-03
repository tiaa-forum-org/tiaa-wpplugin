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
				'permission_callback'  => __return_empty_string(),
				'callback'             => array( $this, 'invite_to_discourse' ),
			),
			true
		);
		return $results;
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
				$response = new WP_REST_Response(
					array('success' => true,'status' => 200,'code' =>'dropped_email'), 200);
				self:self::log_debug($data['email'] . " is a screened email");
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
					$option_group = TIAA_GROUP_INVITE_GROUP . strtolower($data['group']);
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
	 * @return bool True if the route was successfully registered, false otherwise.
	 */
	public function register_get_discourse_post_by_id(): bool {
		$results = register_rest_route(
			TIAA_HOOK_NAMESPACE,
			'/get_discourse_post',
			array(
				'methods'              => 'GET',
				'permission_callback'  => '__return_true',
				'callback'             => array( $this, 'get_discourse_post_by_id' ),
				'args'                 => array(
					'post_id'      => array(
						'validate_callback' => function( $param, $request, $key ) {
							return is_numeric( $param );
						},
					),
					'option_group' => array(
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
}