<?php
/**
 * IP Locator country detector.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator\API;

use IPLocator\System\Environment;
use IPLocator\System\Logger;
use IPLocator\System\Option;
use IPLocator\System\Hosting;
use IPLocator\System\IP;
use IPLocator\System\Cache;
use IPLocator\Plugin\Feature\Schema;
use IPLocator\System\L10n;
use IPLocator\API\Lang;
use IPLocator\API\Flag;

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
	 * Is the IP v4.
	 *
	 * @since  1.0.0
	 * @var    boolean    $ip_v4    Is the IP v4.
	 */
	private $ip_v4 = false;

	/**
	 * Is the IP v6.
	 *
	 * @since  1.0.0
	 * @var    boolean    $ip_v6    Is the IP v6.
	 */
	private $ip_v6 = false;

	/**
	 * Is the IP private.
	 *
	 * @since  1.0.0
	 * @var    boolean    $ip_private    Is the IP private.
	 */
	private $ip_private = false;

	/**
	 * Is the IP public.
	 *
	 * @since  1.0.0
	 * @var    boolean    $ip_public    Is the IP public.
	 */
	private $ip_public = false;

	/**
	 * Is the IP reserved.
	 *
	 * @since  1.0.0
	 * @var    boolean    $ip_reserved    Is the IP reserved.
	 */
	private $ip_reserved = false;

	/**
	 * The detected country code (ISO 3166-1 alpha-2).
	 *
	 * @since  1.0.0
	 * @var    string    $cc    Maintains the country code.
	 */
	private $cc = '00';

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
	 * @param string $ip                Optional. The ip to detect from.
	 *                                  If not specified, get the ip of the current request.
	 * @return \IPLocator\API\Country   The self instance.
	 * @since 1.0.0
	 */
	public function ip( $ip = null ) {
		$this->ip = $ip;
		$this->detect();
		return $this;
	}

	/**
	 * Get the current IP.
	 *
	 * @return string   The current IP.
	 * @since 1.0.0
	 */
	public function source() {
		if ( ! isset( $this->ip ) ) {
			return 'none';
		}
		return $this->ip;
	}

	/**
	 * Get the country code (ISO 3166-1 alpha-2).
	 *
	 * @return string   The country code.
	 * @since 1.0.0
	 */
	public function code() {
		return $this->cc;
	}

	/**
	 * Get the country name.
	 *
	 * @param  string $locale   Optional. The locale.
	 * @return string           The country name, localized if possible.
	 * @since 1.0.0
	 */
	public function name( $locale = null ) {
		return L10n::get_country_name( $this->cc, $locale );
	}

	/**
	 * Get the language object.
	 *
	 * @return \IPLocator\API\Lang  The language object.
	 * @since 1.0.0
	 */
	public function lang() {
		return new Lang( $this->cc );
	}

	/**
	 * Get the flag object.
	 *
	 * @return \IPLocator\API\Flag  The flag object.
	 * @since 1.0.0
	 */
	public function flag() {
		return new Flag( $this->cc );
	}

	/**
	 * Detect the country code.
	 *
	 * @since 1.0.0
	 */
	private function detect() {
		$this->cc = '00';
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
			$this->ip = IP::get_current();
		}
		$this->ip_v4       = false;
		$this->ip_v6       = false;
		$this->ip_private  = false;
		$this->ip_reserved = false;
		$this->ip_public   = false;
		if ( ! filter_var( $this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_IPV4 ) ) {
			$this->ip = null;
			return;
		}
		if ( filter_var( $this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$this->ip    = IP::expand_v6( $this->ip );
			$this->ip_v6 = true;
		}
		if ( filter_var( $this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$this->ip    = IP::expand_v4( $this->ip );
			$this->ip_v4 = true;
		}
		if ( filter_var( $this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE ) ) {
			$this->ip_public = true;
		}
		if ( ! filter_var( $this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE ) ) {
			$this->ip_private = true;
		}
		if ( ! filter_var( $this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE ) ) {
			$this->ip_reserved = true;
		}
	}

	/**
	 * Detect the IP, regarding plugin settings.
	 *
	 * @since 1.0.0
	 */
	private function detect_cc() {
		if ( ! isset( $this->ip ) ) {
			return;
		}
		if ( '127.0.0.1' === $this->ip || IP::expand_v6( '::1' ) === $this->ip ) {
			$this->cc = '01';
			return;
		}
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
			if ( Hosting::is_googlelb_geoip_enabled() ) {
				$cc = filter_input( INPUT_SERVER, 'X-Client-Geo-Location' );
				if ( strpos( $cc, ',' ) ) {
					$cc = explode( ',', $cc )[0];
				}
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
			$cc = '00';
			if ( $this->ip_reserved ) {
				$cc = 'ZZ';
			}
			if ( $this->ip_private ) {
				$cc = 'A0';
			}
			if ( $this->ip_public ) {
				if ( $this->ip_v4 ) {
					$cc = Schema::get_country( $this->ip, 'v4' );
				}
				if ( $this->ip_v6 ) {
					$cc = Schema::get_country( $this->ip, 'v6' );
				}
			}
			if ( '00' === $cc && function_exists( 'geoip_country_code_by_name' ) ) {
				$tmp = geoip_country_code_by_name( $this->ip );
				if ( isset( $tmp ) && is_string( $tmp ) ) {
					$cc = strtoupper( $tmp );
				}
			}
			if ( 2 !== strlen( $cc ) ) {
				$cc = '00';
			}
			Cache::set( $id, $cc, 'ip' );
		}
		$this->cc = substr( strtoupper( $cc ), 0, 2 );
	}
}