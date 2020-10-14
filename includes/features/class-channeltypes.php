<?php
/**
 * Channel types handling
 *
 * Handles all available channel types.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator\Plugin\Feature;

/**
 * Define the channel types functionality.
 *
 * Handles all available channel types.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class ChannelTypes {

	/**
	 * The list of available channels.
	 *
	 * @since  1.0.0
	 * @var    array    $channels    Maintains the channels definitions.
	 */
	public static $channels = [ 'OTHER', 'CLI', 'CRON', 'AJAX', 'XMLRPC', 'API', 'FEED', 'WBACK', 'WFRONT' ];

	/**
	 * The list of available channels names.
	 *
	 * @since  1.0.0
	 * @var    array    $channel_names    Maintains the channels names.
	 */
	public static $channel_names = [];

	/**
	 * Initialize the meta class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		self::$channel_names['OTHER']   = esc_html__( 'Unknown', 'ip-locator' );
		self::$channel_names['CLI']     = esc_html__( 'Command Line Interface', 'ip-locator' );
		self::$channel_names['CRON']    = esc_html__( 'Cron Job', 'ip-locator' );
		self::$channel_names['AJAX']    = esc_html__( 'Ajax Request', 'ip-locator' );
		self::$channel_names['XMLRPC']  = esc_html__( 'XML-RPC Request', 'ip-locator' );
		self::$channel_names['API']     = esc_html__( 'Rest API Request', 'ip-locator' );
		self::$channel_names['FEED']    = esc_html__( 'Atom/RDF/RSS Feed', 'ip-locator' );
		self::$channel_names['WBACK']   = esc_html__( 'Site Backend', 'ip-locator' );
		self::$channel_names['WFRONT']  = esc_html__( 'Site Frontend', 'ip-locator' );

	}

}

ChannelTypes::init();
