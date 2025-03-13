<?php
/**
 * Discourse Integration Library for TIAA Plugin.
 *
 * Provides functionality to connect with and interact with Discourse servers,
 * including pinging the server, sending invites, and more.
 *
 * @package TIAAPlugin
 * @subpackage lib
 * @author Lew Grothe, TIAA Admin Platform sub-team
 * @link https://tiaa-forum.org/contact
 * @license GPL-2.0+
 */

namespace TIAAPlugin\lib;

/**
 * Namespace containing the library functionality for the TIAA Plugin.
 */
use WP_Error;
use WP_REST_Response;
use WP_REST_Request;

/**
 * Class Discourse
 *
 * Handles the communication and functionalities related to the Discourse server.
 */
class Discourse {
	use PluginUtil;

	/**
	 * The API key query parameter for Discourse requests.
	 *
	 * @var string
	 */
	private const API_KEY_QUERY_PARAM = 'Api-Key';

	/**
	 * The API username query parameter for Discourse requests.
	 *
	 * @var string
	 */
	private const API_USERNAME_QUERY_PARAM = 'Api-Username';

	/**
	 * Constructor for the Discourse class.
	 *
	 * Initializes the Discourse class and attaches the necessary WordPress actions.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_action('init', array($this, 'discourse_init'));
	}

	/**
	 * Initializes Discourse-related functionality.
	 *
	 * This method acts as a setup routine for plugin utilities related to Discourse.
	 *
	 * @return void
	 */
	public static function discourse_init() {
		/**
		 * Placeholder for initializing plugin utilities.
		 * Future functionality may include instantiating helper classes.
		 */
		// self::$util = new PublicPluginUtil(); (Potential future functionality)
	}

	/**
	 * Pings the Discourse server to retrieve basic information.
	 *
	 * This method sends a GET request to the Discourse server's `/site/basic-info.json` endpoint
	 * to fetch general information.
	 *
	 * @param string $url The base URL of the Discourse server.
	 * @param string $api_key The API key for authentication.
	 * @param string $username The username for authentication.
	 *
	 * @return WP_REST_Response|WP_Error The response from the Discourse server or an error.
	 */
	public static function ping_discourse_server(string $url, string $api_key, string $username) : WP_REST_Response|WP_Error {
		self::log_info("ping url: " . $url . " username: " . $username);

		if (empty($url) || empty($api_key) || empty($username)) {
			return new WP_Error(
				'not_initialized',
				"ping_discourse_server missing required parameters."
			);
		}

		return self::getApiResponse($url, '/site/basic-info.json', $api_key, $username, 'GET');
	}
	/**
	 * Sends an invite to the Discourse server.
	 *
	 * This method uses a POST request to send an invite to a given email address.
	 * Optional parameters include a custom message, group name, and topic ID.
	 * If the invite fails due to an existing user, it may inform the client accordingly.
	 *
	 * @TODO Improve documentation for additional behavior.
	 * @TODO Consider moving this method outside the class if needed.
	 *
	 * @param WP_REST_Request $request The REST request containing the invite details,
	 *                                 such as email, custom message, group, and topic.
	 *
	 * @return WP_REST_Response|WP_Error The response from the Discourse server or an error object.
	 */
	/**
	 * Sends an invite to the Discourse server.
	 *
	 * This method uses a POST request to send an invite to a given email address.
	 * Optional parameters include a custom message, group name, and topic ID.
	 * If the invite fails due to an existing user, it may inform the client accordingly.
	 *
	 * @TODO Improve documentation for additional behavior.
	 * @TODO Consider moving this method outside the class if needed.
	 *
	 * @param WP_REST_Request $request The REST request containing the invite details,
	 *                                 such as email, custom message, group, and topic.
	 *
	 * @return WP_REST_Response|WP_Error The response from the Discourse server or an error object.
	 */
	public static function send_discourse_invite(WP_REST_Request $request) : WP_REST_Response | WP_Error {
		$params = $request->get_body_params();
		$url      = $params[ 'url' ];
    	$api_key  = $params[ 'api_key' ];
    	$username = $params[ 'username' ];
		if ( empty( $url ) || empty( $api_key ) || empty( $username ) ) {
			return new WP_Error(
				'not_initialized',
				"send_discourse_invite missing required parameters." );
		}
		if(isset($params['email'])) {
			$data['email']    = $params[ 'email' ];
			$log_i = " username: " . $username . " email: " . $data['email'];
		} else {
			return new WP_Error(
				'no_email',
				"send_discourse invite missing email." );
		}
		if (isset($params['message'])) {
			$data['custom_message'] = $params[ 'message' ];
		}
		if (isset($params['group_names'])) {
			$data['group_names'] = array($params[ 'group_names' ]);
			$log_i .= " group: " . $params[ 'group_names' ];
		}
		if (isset($params['topic'])) {
			$data['topic_id'] = $params[ 'topic' ];
			$log_i .= " topic: " . $data[ 'topic_id' ];
		}
		self::log_info("send_discourse invite: " . $log_i);
		$response = self::getApiResponse($url, '/invites.json', $api_key, $username, 'POST', $data);
		// if status == 422, check if it's an already active user - if so, indicate to client via message
		if ($response->get_status() === 422) {
			// see if we get a notification from Discourse that this email is already registered
			$rdata = $response->get_data();
			if (!empty($rdata['body_response'])){
				$errors = json_decode($rdata['body_response'], true);
			}
			if (!empty($errors['errors']) && str_contains($errors['errors'][0],"There's no need to invite")) {
				$mod_data = $response->get_data();
				$mod_data['message'] = "already a member";
				$response->set_data($mod_data);
				self::log_info('parsed duplicate invite for: ' . $data['email']);
			} else {
				// The url checking if the email is already registered must have admin privileges
				$cs = self::get_connection_options_by_group( TIAA_CONNECT_GROUP );
				if ($cs['url'] === $url) {
					$response2 = self::getApiResponse( $url, '/admin/users/list/all.json?email=' . $data['email'],
						$cs['api_key'], $cs['username'], 'GET' );
					// if response2 is valid and a user, then mark email as already a member
					if ( $response2->get_status() == 200 ) {
						$data2 = $response2->get_data();
						if ( empty( $data2 ) ) {
							self::log_error( 'invite failed: ' . $data['email'] );
						} else {
							$mod_data            = $response->get_data();
							$mod_data['message'] = "already a member";
							$response->set_data( $mod_data );
							self::log_info( 'found duplicate invite for: ' . $data['email'] );
						}
					}
				}
			}
		}
		return $response;
	}

	/**
	 * Retrieves a post from the Discourse server given its ID.
	 *
	 * This method fetches a specific post by ID from the Discourse server
	 * using a GET request to the corresponding API endpoint.
	 *
	 * @param string $url The base URL of the Discourse server.
	 * @param string $post_id The ID of the post to retrieve.
	 * @param string $api_key The API key for authentication.
	 * @param string $username The username for authentication.
	 *
	 * @return WP_REST_Response|WP_Error The retrieved post or an error object on failure.
	 */
	public static function get_discourse_post_by_id(int $post_id, string $option_group) : WP_Error | WP_REST_Response {
		self::log_debug('get_discourse_post_by_id: '  . $option_group. ': ' . $post_id);
		if ( ! $post_id ) {
			return new WP_Error( "Missing post ID." );
		}
		$cs = self::get_connection_options_by_group( $option_group );
		if (is_wp_error($cs)) {
			return $cs;
		}
		$apiEndPoint = '/posts/' . $post_id .'.json';

		return self::getApiResponse($cs['url'],$apiEndPoint, $cs['api_key'], $cs['username'], 'GET' );
	}

	/**
	 * Retrieves a list of recently added members from the Discourse server.
	 *
	 * Fetches a list of recent members, including optional filters or parameters,
	 * by sending a GET request to the appropriate Discourse endpoint.
	 *
	 * @param string $url The base URL of the Discourse server.
	 * @param int    $limit Optional. The maximum number of members to retrieve. Default is 50.
	 * @param string $api_key The API key for authentication.
	 * @param string $username The username for authentication.
	 *
	 * @return WP_REST_Response|WP_Error A list of recent members or an error object on failure.
	 */
	public static function get_recent_members(int $max_days) : null|array  {
		self::log_debug( "get welcome message: " . $max_days );

		$cs = self::get_connection_options_by_group( TIAA_WELCOME_GROUP );
		if (is_wp_error($cs)) {
			self::log_error( "get_recent_members failed: " . $cs->get_error_message() );
			return null;
		}
		if ( empty( $cs['url'] ) ||  empty($cs['api_key'] ) || empty($cs['username'])) {
			self::log_error( "get_recent_members missing required parameters.");
			return null;
		}
		$return_ary = array();
		$oldest_date = date('Y-m-d', strtotime('-' . $max_days . ' days'));
		$cutoff_date = strtotime($oldest_date);
		$page = 1;
		// unlikely since we get 100 users per page
		while ($page < 3) {
			$apiEndPoint = '/admin/users/list/new.json?show_emails=true&page=' . $page ;
			$response  = self::getApiResponse($cs['url'],$apiEndPoint, $cs['api_key'], $cs['username'], 'GET' );
			if ( $response->get_status() !== 200 ) {
				self::log_error( "get_recent_members failed: " . $response->get_status() );
				break;
			} else {
				$response_data = $response->get_data();

				if (!empty($response_data['body_response'])) {
					// Decode the JSON string into a PHP array
					$users_array = json_decode($response_data['body_response'], true);

					if (json_last_error() === JSON_ERROR_NONE && is_array($users_array)) {
						$selected_users = array();

						// Iterate through each user and apply your selection criteria
						foreach ($users_array as $user) {
							// Example: Add users who meet a specific condition
							if ( isset( $user['created_at'] ) && strtotime( $user['created_at'] ) > $cutoff_date ) {
								$return_ary[] = $user;
							} else {
								$page = 999;
								break;
							}
						}
						$page++;

					} else {
						// JSON Decoding Error
						self::log_error('Failed to decode body_response: ' . json_last_error_msg());
					}
				} else {
					// Handle the case where body_response is missing or empty
					self::log_error('No body_response found in the response.');
				}
			}
		}
		return $return_ary;
	}

	/**
	 * Retrieves a Discourse user's details by username.
	 *
	 * Sends a GET request to fetch details about a specific user by their username.
	 *
	 * @param string $url The base URL of the Discourse server.
	 * @param string $username The username of the user to retrieve.
	 * @param string $api_key The API key for authentication.
	 *
	 * @return WP_REST_Response|WP_Error The user details or an error object.
	 */
	public static function get_user_byname(string $user_name, string $option_group) : WP_Error | WP_REST_Response {
		self::log_debug( "get user info: " . $user_name );
		$cs = self::get_connection_options_by_group( $option_group );
		if (is_wp_error($cs)) {
			return $cs;
		}
		$apiEndPoint = '/users/' . $user_name .'.json';
		$response  = self::getApiResponse($cs['url'],$apiEndPoint, $cs['api_key'], $cs['username'],
			'GET', null );
		if ($response->get_status() !== 200) {
			self::log_wp_rest_response_error( '', $response, __FUNCTION__,
				__CLASS__, __LINE__ );
		}
		$rdata = $response->get_data()['body_response'];
		$data = json_decode($rdata, true);
		self::log_debug( "get user status: " . $response->get_status());
		return $response;
	}

	/**
	 * Sends a personal message to a Discourse user.
	 *
	 * This method uses a POST request to send a private message to a specific user
	 * with an optional subject and content body.
	 *
	 * @param string $url The base URL of the Discourse server.
	 * @param string $recipient The username of the message recipient.
	 * @param string $subject Optional. The subject of the message.
	 * @param string $content The content/body of the message.
	 * @param string $api_key The API key for authentication.
	 * @param string $username The username for authentication.
	 *
	 * @return WP_REST_Response|WP_Error The response from the Discourse server or an error.
	 */
	public static function send_personal_message(string $user_name, string $title, string $message,
		string $option_group) : WP_Error | WP_REST_Response {
		self::log_debug( "post personal message: " . $user_name );
		$cs = self::get_connection_options_by_group( $option_group );
		if (is_wp_error($cs)) {
			return $cs;
		}
		$data = array(
			'target_recipients' => $user_name,
			'title' => $title,
			'raw' => $message,
			'archetype' => 'private_message'
		);
		$apiEndPoint = '/posts.json';
		$response  = self::getApiResponse($cs['url'],$apiEndPoint, $cs['api_key'], $cs['username'],
			'POST', $data );
		if ($response->get_status() !== 200) {
			self::log_wp_rest_response_error( '', $response,
				__FUNCTION__, __CLASS__, __LINE__ );
			return ($response);
		}
		self::log_debug( "post personal message response: " . $response->get_status() );
		return $response;
	}
	/**
	 * Executes an API call to the Discourse server.
	 *
	 * Handles GET or POST requests to the Discourse API endpoints, using the provided
	 * API key and username for authentication.
	 *
	 * @param string $base_url The base URL of the Discourse server.
	 * @param string $endpoint The API endpoint to call.
	 * @param string $api_key The API key for authentication.
	 * @param string $username The username for authentication.
	 * @param string $method The HTTP method for the request (e.g., 'GET', 'POST').
	 * @param array  $body Optional. The request body for POST requests.
	 *
	 * @return WP_REST_Response|WP_Error The API response on success or an error object on failure.
	 *
	 * @throws Exception Thrown if unsupported HTTP method is provided.
	 */
	private static function getApiResponse( string $baseUrl, string $apiEndpoint, string $apiKey, string $apiUser,
		string $method, array $data = null ) : WP_REST_Response {
		if ($method === 'GET') {
			$args     = self::createGetApiArgs( $apiKey, $apiUser, $data );
			$response = wp_remote_get( $baseUrl . $apiEndpoint, $args );
		} elseif ($method === 'POST') {
			$args     = self::createPostApiArgs( $apiKey, $apiUser, $data );
			$response = wp_remote_post( $baseUrl . $apiEndpoint, $args );
		} else {
			$response = new WP_REST_Response([
					'success'       => false,
					'response'      => 'internal error',
					'message'       =>  'called with invalid method: ' . $method ,
					'code'          => 'internal error',
					'status'        => 500
				], 500 );
			self::log_wp_rest_response_error( '', $response,
				__FUNCTION__, __CLASS__, __LINE__ );
			return ($response);
		}
		return self::handle_discourse_response( $response );
	}

	/**
	 * Constructs HTTP arguments for a GET request to the Discourse API.
	 *
	 * Builds the headers and other parameters required to authenticate and execute
	 * a GET request.
	 *
	 * @param string $api_key The API key for authentication.
	 * @param string $username The username for authentication.
	 *
	 * @return array An array containing the prepared arguments for a GET request.
	 */
	private static function createGetApiArgs( string $apiKey, string $apiUser, array $data=null ): array {
		$header = array('headers' => array(
			self::API_KEY_QUERY_PARAM      => $apiKey,
 		  self::API_USERNAME_QUERY_PARAM => $apiUser
		));
		if ($data) {
			return array_merge($header, $data);
		}
		return $header;
	}

	/**
	 * Constructs HTTP arguments for a POST request to the Discourse API.
	 *
	 * Builds the headers, body, and other parameters needed to authenticate and execute
	 * a POST request.
	 *
	 * @param string $api_key The API key for authentication.
	 * @param string $username The username for authentication.
	 * @param array  $body The body of the POST request.
	 *
	 * @return array An array containing the prepared arguments for a POST request.
	 */
	private static function createPostApiArgs( string $apiKey, string $apiUser, array $data=null ): array {
		$headers = array('headers' => array(
			self::API_KEY_QUERY_PARAM      => $apiKey,
			self::API_USERNAME_QUERY_PARAM => $apiUser,
			'Content-Type' => 'application/json'
		));
		return array_merge($headers,array( 'body' => json_encode($data)));
	}

	/**
	 * Processes the response received from the Discourse API.
	 *
	 * Inspects the response to ensure its validity and extracts useful information.
	 * Returns a WordPress-compatible response or an error object in case of issues.
	 *
	 * @param array|WP_Error $response The raw response or WP_Error created by wp_remote_request.
	 * @param string         $endpoint Optional. The API endpoint called for logging/debugging purposes.
	 *
	 * @return WP_REST_Response|WP_Error A processed response object or an error.
	 */
	protected static function handle_discourse_response($response) : WP_REST_Response {
		self::log_debug("into discourse response");
		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );
		$response_body    = wp_remote_retrieve_body( $response );

		if ( is_wp_error( $response ) ) {
			// Convert the WP_Error into a WP_REST_Response object
			if ( ! $response_code && ! $response_message && ! $response_body ) {
				// this seems to be the case with curl errors...
				$message      = $response->get_error_message();
				$message      = $message ?: 'unknown error';
				$err_response = json_encode( array( 'errors' => $message ) );
				$restResponse  = new WP_REST_Response( [
					'success'       => false,
					'response'      => 'unknown error',
					'body_response' => $err_response,
					'message'       =>   $message,
					'code'          => 'unknown error',
					'status'        => 500
				], 500 );
			} else {
				$error_data = $response->get_error_data();
				if ( is_array( $error_data ) && isset( $error_data['status'] ) ) {
					$status = $error_data['status'];
				} else {
					$status = 500; // or use any code suitable for the error
				}
				$restResponse = new WP_REST_Response( [
					'success'       => false,
					'code'    => $response->get_error_code(),
					'message' => $response->get_error_message(),
					'data'    => $response->get_error_data(),
					'status'  => $status
				], $status );
			}
			self::log_wp_rest_response_error( 'bad1', $restResponse,
				__FUNCTION__, __CLASS__, __LINE__ );
		} elseif ( $response_code != 200 ) {
			// The request was not successful
			$status = ($response_code) ? : 500;
			$restResponse = new WP_REST_Response(
				array(
					'success'       => false,
					'status'        => $response_code,
					'response'      => $response_message,
					'body_response' => $response_body
				), $status );
			self::log_wp_rest_response_error( 'bad2', $restResponse,
				__FUNCTION__, __CLASS__, __LINE__ );
		} else {
			// The request was successful (200)
			$restResponse = new WP_REST_Response(
				array(
					'success'       => true,
					'status'        => $response_code,
					'response'      => $response_message,
					'body_response' => $response_body
				), $response_code );
			self::log_debug( "discourse response success: " . $response_message );
		}
		return $restResponse;
	}
	/**
	 * Retrieves Discourse connection options based on a specific group.
	 *
	 * Fetches connection-related settings such as API key, username, and endpoint
	 * based on the associated WordPress group configuration.
	 *
	 * @param string $group The group identifier used to fetch connection settings.
	 *
	 * @return array|WP_Error An associative array of connection options, or an error object on failure.
	 */
	public static function get_connection_options_by_group($option_group) : array | WP_Error {
		if (empty($option_group)) {
			return new WP_Error( 'No option group',
				"Option group not set in Discourse::get_connection_options" );
		}
		$options = self::get_options_by_group($option_group);
		if (empty($options)) {
			return new WP_Error( 'No options',
				"error finding options in Discourse::get_connection_options for: " . $option_group );
		}
		if ($option_group === TIAA_CONNECT_GROUP) {
			$cs = $options;
		} else {
			$connection_options = self::get_options_by_group( TIAA_CONNECT_GROUP );
			foreach ( array( 'url', 'api_key', 'username' ) as $key ) {
				$cs[ $key ] = $options[ $key ] ?: $connection_options[ $key ];
				if (empty( $cs[ $key ] ) ) {
					return new WP_Error( 'Not Initialized',
						"must set a connection for " . $option_group . " first" );
				}
			}
		}
		return $cs;
	}
}