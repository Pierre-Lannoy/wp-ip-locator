<?php
/**
 * .htaccess rules
 *
 * Handles all rules processes.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator\Plugin\Feature;

use IPLocator\System\Logger;
use IPLocator\System\Option;

/**
 * Define the rules functionality.
 *
 * Handles all rules processes.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Rules {

	/**
	 * Initialization state.
	 *
	 * @since  1.0.0
	 * @var    boolean    $initialized    Maintain the initialization state.
	 */
	private static $initialized = false;

	/**
	 * Init the class.
	 *
	 * @param boolean $hooked   Optional. Add hook for mod_rewrite_rules filter.
	 * @since    1.0.0
	 */
	public static function init( $hooked = false ) {
		if ( $hooked ) {
			add_filter( 'mod_rewrite_rules', [ self::class, 'modify_rules' ], 10, 1 );
		}
		self::$initialized = true;
	}

	/**
	 * Shutdown (de-init) the class.
	 *
	 * @since    1.0.0
	 */
	public static function shutdown() {
		self::$initialized = false;
	}

	/**
	 * Modify rewrite rules if needed.
	 *
	 * @param string $rules mod_rewrite Rewrite rules formatted for .htaccess.
	 * @return string Modified (if needed) mod_rewrite Rewrite rules formatted for .htaccess.
	 * @since 1.0.0
	 */
	public static function modify_rules( $rules ) {
		if ( self::$initialized ) {
			$server = [];
			foreach ( [ 'status', 'info' ] as $type ) {
				if ( Option::network_get( $type ) ) {
					$server[] = 'server-' . $type;
				}
			}
			$rules = preg_replace( '/^(RewriteBase \/.*)$/miU', "$1\nRewriteRule ^(" . implode( '|', $server ) . ") - [L]", $rules, 1 );
			Logger::debug( 'Rewrite rules added.' );
		}
		return $rules;
	}

}
