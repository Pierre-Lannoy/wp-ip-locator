<?php
/**
 * IP Locator country detector.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.3.0
 */

namespace IPLocator;

use IPLocator\System\Environment;
use IPLocator\System\Option;
use IPLocator\System\Hosting;
use IPLocator\System\IP;
use IPLocator\System\Cache;

/**
 * IP Locator country detector class.
 *
 * This class defines all code necessary to detect country.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Country {

	/**
	 * The IP to detect from.
	 *
	 * @since  1.0.0
	 * @var    null|string    $ip    Maintains the ip address.
	 */
	private $ip = null;

	/**
	 * The detected country code (ISO 3166-1 alpha-2).
	 *
	 * @since  1.0.0
	 * @var    null|string    $cc    Maintains the country code.
	 */
	private $cc = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $ip Optional. The ip to detect from.
	 *                   If not specified, get the ip of the current request.
	 * @since 1.0.0
	 */
	public function __construct( $ip = null ) {
		$this->ip = $ip;
		$this->detect();
	}

	/**
	 * Set the IP to detect from.
	 *
	 * @param string $ip            Optional. The ip to detect from.
	 *                              If not specified, get the ip of the current request.
	 * @return \IPLocator\Country   The self instance.
	 * @since 1.0.0
	 */
	public function ip( $ip = null ) {
		$this->ip = $ip;
		$this->detect();
		return $this;
	}

	/**
	 * Detect the country code.
	 *
	 * @since 1.0.0
	 */
	private function detect() {
		$this->cc = null;
		$this->detect_ip();
		$this->detect_cc();
	}

	/**
	 * Detect the IP, regarding plugin settings.
	 *
	 * @since 1.0.0
	 */
	private function detect_ip() {
		if ( ! isset( $this->ip ) ) {
			$this->ip = Environment::current_ip();
		}
		if ( filter_var( $this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$this->ip = IP::expand_v6( $this->ip );
		}
		if ( filter_var( $this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$this->ip = IP::normalize_v4( $this->ip );
		}
	}

	/**
	 * Detect the IP, regarding plugin settings.
	 *
	 * @since 1.0.0
	 */
	private function detect_cc() {
		if ( ! (bool) Option::network_get( 'override' ) ) {
			$cc = '';
			if ( Hosting::is_cloudflare_geoip_enabled() ) {
				if ( array_key_exists( 'HTTP_CF_IPCOUNTRY', $_SERVER ) ) {
					$cc = filter_input( INPUT_SERVER, 'HTTP_CF_IPCOUNTRY' );
				} elseif ( array_key_exists( 'CF-IPCountry', $_SERVER ) ) {
					$cc = filter_input( INPUT_SERVER, 'CF-IPCountry' );
				}
			}
			if ( Hosting::is_cloudfront_geoip_enabled() ) {
				$cc = filter_input( INPUT_SERVER, 'CloudFront-Viewer-Country' );
			}
			if ( Hosting::is_apache_geoip_enabled() ) {
				$cc = filter_input( INPUT_SERVER, 'GEOIP_COUNTRY_CODE' );
			}
			$cc = strtoupper( $cc );
			if ( 2 === strlen( $cc ) ) {
				$this->cc = $cc;
				return;
			}
		}
		$id = Cache::id( $this->ip, 'fingerprint/' );
		$cc = Cache::get( $id );
		if ( ! isset( $cc ) ) {





		}
		if ( ! isset( $cc ) ) {
			// try fallback
		}
		$this->cc = substr( strtoupper( $cc ), 0, 2 ) ;
	}
}