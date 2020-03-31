<?php
/**
 * IP Locator functions.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */


use IPLocator\API\Country;

if ( ! function_exists( 'iplocator_get_ip' ) ) {
	/**
	 * Get the current IP.
	 *
	 * @param   string  $ip Optional. The ip to detect from.
	 *                      If not specified, get the ip of the current request.
	 * @return  string      The current IP.
	 * @since 1.0.0
	 */
	function iplocator_get_ip( $ip = null ) {
		$country = new Country( $ip );
		return $country->get_ip();
	}
}

if ( ! function_exists( 'iplocator_get_country_code' ) ) {
	/**
	 * Get the country code (ISO 3166-1 alpha-2).
	 *
	 * @param  string   $ip Optional. The ip to detect from.
	 *                      If not specified, get the ip of the current request.
	 * @return string       The country code.
	 * @since 1.0.0
	 */
	function iplocator_get_country_code( $ip = null ) {
		$country = new Country( $ip );
		return $country->code();
	}
}

if ( ! function_exists( 'iplocator_get_country_name' ) ) {
	/**
	 * Get the country name.
	 *
	 * @param  string $ip       Optional. The ip to detect from.
	 *                          If not specified, get the ip of the current request.
	 * @param  string $locale   Optional. The locale.
	 * @return string           The country name, localized if possible.
	 * @since 1.0.0
	 */
	function iplocator_get_country_name( $ip = null, $locale = null ) {
		$country = new Country( $ip );
		return $country->name( $locale );
	}
}

if ( ! function_exists( 'iplocator_get_language_code' ) ) {
	/**
	 * Get the country main language code.
	 *
	 * @param  string   $ip Optional. The ip to detect from.
	 *                      If not specified, get the ip of the current request.
	 * @return string       The main language code if found, '' otherwise.
	 * @since 1.0.0
	 */
	function iplocator_get_language_code( $ip = null ) {
		$country = new Country( $ip );
		return $country->lang()->code();
	}
}

if ( ! function_exists( 'iplocator_get_language_name' ) ) {
	/**
	 * Get the country main language name.
	 *
	 * @param  string $ip       Optional. The ip to detect from.
	 *                          If not specified, get the ip of the current request.
	 * @param  string $locale   Optional. The locale.
	 * @return string           The country main language name, localized if possible.
	 * @since 1.0.0
	 */
	function iplocator_get_language_name( $ip = null, $locale = null ) {
		$country = new Country( $ip );
		return $country->lang()->name( $locale );
	}
}

if ( ! function_exists( 'iplocator_get_flag_emoji' ) ) {
	/**
	 * Get the emoji flag.
	 *
	 * @param  string $ip   Optional. The ip to detect from.
	 *                      If not specified, get the ip of the current request.
	 * @return string       The emoji flag, ready to print.
	 * @since 1.0.0
	 */
	function iplocator_get_flag_emoji( $ip = null ) {
		$country = new Country( $ip );
		return $country->flag()->emoji();
	}
}

if ( ! function_exists( 'iplocator_get_flag_svg' ) ) {
	/**
	 * Get the svg flag base 64 encoded.
	 *
	 * @param  string    $ip         Optional. The ip to detect from.
	 *                               If not specified, get the ip of the current request.
	 * @param  boolean   $squared    Optional. The flag must be squared.
	 * @return string                The svg flag base 64 encoded.
	 * @since 1.0.0
	 */
	function iplocator_get_flag_svg( $ip = null, $squared = false ) {
		$country = new Country( $ip );
		return $country->flag()->svg( $squared );
	}
}

if ( ! function_exists( 'iplocator_get_flag_image' ) ) {
	/**
	 * Get the image flag.
	 *
	 * @param   string    $ip         Optional. The ip to detect from.
	 *                                If not specified, get the ip of the current request.
	 * @param   string    $class      Optional. The class(es) name(s).
	 * @param   string    $style      Optional. The style.
	 * @param   string    $id         Optional. The ID.
	 * @param   string    $alt        Optional. The alt text.
	 * @param   boolean   $squared    Optional. The flag must be squared.
	 * @return  string                The svg flag base 64 encoded.
	 * @since 1.0.0
	 */
	function iplocator_get_flag_image( $ip = null, $class = '', $style = '', $id = '', $alt = '', $squared = false ) {
		$country = new Country( $ip );
		return $country->flag()->image( $class, $style, $id, $alt, $squared );
	}
}

