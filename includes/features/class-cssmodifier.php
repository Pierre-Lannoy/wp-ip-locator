<?php
/**
 * IP Locator css modification handling
 *
 * Handles all css modification operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator\Plugin\Feature;

use IPLocator\System\Option;
use IPLocator\System\Logger;
use IPLocator\System\L10n;

/**
 * Define the css modification functionality.
 *
 * Handles all css modification operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class CSSModifier {

	/**
	 * Get example of all available selectors for a specifier.
	 *
	 * @return string   The selectors, ready to print.
	 * @since    1.0.0
	 */
	public static function get_example() {
		$desc      = [];
		$countries = [];
		$result    = '';
		foreach ( L10n::$countries as $key => $name ) {
			$id = '<code style="font-size: x-small">' . 'ip-locator-' . strtolower( $key ) . '</code>';
			switch ($name) {
				case '[unknown]':
					$desc[ esc_html__( 'Unknown / undetected: %s.', 'ip-locator') ][] = $id;
					break;
				case '[loopback]':
					$desc[ esc_html__( 'Loopback: %s.', 'ip-locator') ][] = $id;
					break;
				case '[private network]':
					$desc[ esc_html__( 'Private network: %s.', 'ip-locator') ][] = $id;
					break;
				case '[anonymous proxy]':
					$desc[ esc_html__( 'Anonymous proxy: %s.', 'ip-locator') ][] = $id;
					break;
				case '[satellite]':
					$desc[ esc_html__( 'Satellite: %s.', 'ip-locator') ][] = $id;
					break;
				case '[reserved]':
					$desc[ esc_html__( 'Reserved / unused: %s.', 'ip-locator') ][] = $id;
					break;
				default:
					$countries[] = $id;
			}
		}
		$result = sprintf( esc_html__( 'Countries: %s.', 'ip-locator' ), implode( ' ', $countries ) ) . '<br/>';
		foreach ( $desc as $key => $names ) {
			$result .= sprintf( $key, implode( ' ', $names ) ) . '<br/>';
		}
		return $result;
	}

	/**
	 * @since    1.0.0
	 */
	private static function get_current_classes() {
		return [ 'ip-locator-' . strtolower( iplocator_get_country_code() ) ];
	}

	/**
	 * @since    1.0.0
	 */
	public static function body_class( $classes ) {
		return array_merge( $classes, self::get_current_classes() );
	}

	/**
	 * @since    1.0.0
	 */
	public static function admin_body_class( $classes ) {
		return $classes . ' ' . implode( ' ', self::get_current_classes() ) . ' ';
	}

	/**
	 * Static initialization.
	 *
	 * @since  1.0.0
	 */
	public static function init() {
		if ( Option::network_get( 'css' ) ) {
			add_filter( 'body_class', [ 'IPLocator\Plugin\Feature\CSSModifier', 'body_class' ] );
			Logger::debug( 'Filter hooked: body_class.');
			add_filter( 'admin_body_class', [ 'IPLocator\Plugin\Feature\CSSModifier', 'admin_body_class' ] );
			Logger::debug( 'Filter hooked: admin_body_class.');
		}
	}

}
