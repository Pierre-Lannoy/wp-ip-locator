<?php
/**
 * Hosting environment handling.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator\System;

/**
 * The class responsible to manage and detect hosting environment.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Hosting {


	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Check if Cloudflare Geoip is enabled.
	 *
	 * @return bool    True if Cloudflare Geoip is enabled.
	 * @since  1.0.0
	 */
	public static function is_cloudflare_geoip_enabled() {
		return array_key_exists( 'HTTP_CF_IPCOUNTRY', $_SERVER ) || array_key_exists( 'CF-IPCountry', $_SERVER );
	}

	/**
	 * Check if Cloudfront (AWS) Geoip is enabled.
	 *
	 * @return bool    True if Cloudfront Geoip is enabled.
	 * @since  1.0.0
	 */
	public static function is_cloudfront_geoip_enabled() {
		return array_key_exists( 'CloudFront-Viewer-Country', $_SERVER );
	}

	/**
	 * Check if Google LB Geoip is enabled.
	 *
	 * @return bool    True if Google Geoip is enabled.
	 * @since  2.3.0
	 */
	public static function is_googlelb_geoip_enabled() {
		return array_key_exists( 'X-Client-Geo-Location', $_SERVER );
	}

	/**
	 * Check if Apache Geoip is enabled.
	 *
	 * @return bool    True if Cloudfront Geoip is enabled.
	 * @since  1.0.0
	 */
	public static function is_apache_geoip_enabled() {
		return array_key_exists( 'GEOIP_COUNTRY_CODE', $_SERVER );
	}

	/**
	 * Check if the server config allows shell_exec().
	 *
	 * @return bool    True if shell_exec() can be used, false otherwise.
	 * @since  1.0.0
	 */
	private static function is_shell_enabled() {
		if ( function_exists( 'shell_exec' ) && ! in_array( 'shell_exec', array_map( 'trim', explode( ', ', ini_get( 'disable_functions' ) ) ), true ) && (int) strtolower( ini_get( 'safe_mode' ) ) !== 1 ) {
			// phpcs:ignore
			$return = shell_exec( 'cat /proc/cpuinfo' );
			if ( ! empty( $return ) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Get CPU count of the server.
	 *
	 * @return int|bool    The count of CPUs, false if it's not countable.
	 * @since  1.0.0
	 */
	public static function count_server_cpu() {
		$cpu_count = Cache::get_global( '/Hardware/CPU/Count' );
		if ( false === $cpu_count ) {
			if ( self::is_shell_enabled() ) {
				// phpcs:ignore
				$cpu_count = shell_exec( 'cat /proc/cpuinfo |grep "physical id" | sort | uniq | wc -l' );
				Cache::set_global( '/Hardware/CPU/Count', $cpu_count, 'diagnosis' );
			} else {
				return false;
			}
		}
		return (int) $cpu_count;
	}

	/**
	 * Get core count of the server.
	 *
	 * @return int|bool    The count of cores, false if it's not countable.
	 * @since  1.0.0
	 */
	public static function count_server_core() {
		$core_count = Cache::get_global( '/Hardware/Core/Count' );
		if ( false === $core_count ) {
			if ( self::is_shell_enabled() ) {
				// phpcs:ignore
				$core_count = shell_exec( "echo \"$((`cat /proc/cpuinfo | grep cores | grep -o '[0-9]' | uniq` * `cat /proc/cpuinfo |grep 'physical id' | sort | uniq | wc -l`))\"" );
				Cache::set_global( '/Hardware/Core/Count', $core_count, 'diagnosis' );
			} else {
				return false;
			}
		}
		return $core_count;
	}

}
