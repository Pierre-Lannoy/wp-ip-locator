<?php
/**
 * Infolog handling
 *
 * Handles all info log operations.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator\System;


use IPLocator\System\Option;

/**
 * Define the info log functionality.
 *
 * Handles all info operations and detection.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Infolog {

	/**
	 * The number of infos to keep.
	 *
	 * @since  1.0.0
	 * @var    integer    $keep    Maintains the number of infos to keep.
	 */
	private static $keep = 10;

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

	}

	/**
	 * Records the log.
	 *
	 * @param array    $infos   The infos to record.
	 * @since   1.0.0
	 */
	private static function record( $infos ) {
		uasort(
			$infos,
			function ( $a, $b ) {
				if ( $a['timestamp'] === $b['timestamp'] ) {
					return 0;
				} return ( $a['timestamp'] < $b['timestamp'] ) ? 1 : -1;
			}
		);
		$infos = array_slice( $infos, 0, self::$keep );
		Option::network_set( 'infolog', $infos );
	}

	/**
	 * Adds an info.
	 *
	 * @param string    $info   The info string to add.
	 * @since   1.0.0
	 */
	public static function add( $info ) {
		$infos   = Option::network_get( 'infolog' );
		$infos[] = [ 'timestamp' => time(), 'message' => $info ];
		self::record( $infos );
	}

}
