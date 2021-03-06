<?php
/**
 * DecaLog logger read handler
 *
 * Handles all logger reads.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace IPLocator\API;


use IPLocator\System\IP;

/**
 * Define the item operations functionality.
 *
 * Handles all item operations.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */
class IPRoute extends \WP_REST_Controller {

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since  2.0.0
	 */
	public function register_routes() {
		$this->register_route_describe();
	}

	/**
	 * Register the routes for description.
	 *
	 * @since  2.0.0
	 */
	public function register_route_describe() {
		register_rest_route(
			IPLOCATOR_REST_NAMESPACE,
			'describe',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_describe' ],
					'permission_callback' => [ $this, 'get_describe_permissions_check' ],
					'args'                => array_merge( $this->arg_schema_describe() ),
					'schema'              => [ $this, 'get_schema' ],
				],
			]
		);
	}

	/**
	 * Get the query params for description.
	 *
	 * @return array    The schema fragment.
	 * @since  2.0.0
	 */
	public function arg_schema_describe() {
		return [
			'ip'     => [
				'description'       => 'The IP from which to extract informations.',
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => [ $this, 'sanitize_ip' ],
			],
			'locale' => [
				'description'       => 'The locale in which displaying informations.',
				'type'              => 'string',
				'required'          => false,
				'default'           => 'en_US',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}

	/**
	 * Check if a given request has access to get description
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_Error|bool
	 */
	public function get_describe_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->warning( 'Unauthenticated API call.', [ 'code' => 401 ] );
			return new \WP_Error( 'rest_not_logged_in', 'You must be logged in to access live logs.', [ 'status' => 401 ] );
		}
		return true;
	}

	/**
	 * Sanitization callback for ip.
	 *
	 * @param   mixed             $value      Value of the arg.
	 * @param   \WP_REST_Request  $request    Current request object.
	 * @param   string            $param      Name of the arg.
	 * @return  string  The ip sanitized.
	 * @since  2.0.0
	 */
	public function sanitize_ip( $value, $request = null, $param = null ) {
		$result = '127.0.0.1';
		if ( filter_var( $value, FILTER_VALIDATE_IP ) ) {
			$result = (string) IP::expand( $value );
		}
		return $result;
	}

	/**
	 * Get a collection of items
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_describe( $request ) {
		try {
			$result = iplocator_describe( $request['ip'], $request['locale'] );
		} catch ( \Throwable $t ) {
			\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->error( sprintf( 'Unable to analyze IP "%s".', $request['ip'] ), [ 'code' => 500 ] );
			return new \WP_Error( 'rest_internal_server_error', 'Unable to analyze this User-Agent.', [ 'status' => 500 ] );
		}
		return new \WP_REST_Response( $result, 200 );
	}

}