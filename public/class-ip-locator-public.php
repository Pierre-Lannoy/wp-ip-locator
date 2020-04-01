<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator\Plugin;

use IPLocator\System\Assets;
use IPLocator\API\Country;
use IPLocator\System\Logger;

/**
 * The class responsible for the public-facing functionality of the plugin.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class IP_Locator_Public {

	/**
	 * The assets manager that's responsible for handling all assets of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Assets    $assets    The plugin assets manager.
	 */
	protected $assets;

	/**
	 * The current instance of API.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    \IPLocator\API\Country    $country    The current instance of API.
	 */
	protected $country = null;

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->assets = new Assets();
	}

	/**
	 * Initializes the API.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		if ( ! isset( $this->country ) ) {
			$this->country = new Country();
		}
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		$this->assets->register_style( IPLOCATOR_ASSETS_ID, IPLOCATOR_PUBLIC_URL, 'css/ip-locator.min.css' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$this->assets->register_script( IPLOCATOR_ASSETS_ID, IPLOCATOR_PUBLIC_URL, 'js/ip-locator.min.js', [ 'jquery' ] );
	}

	/**
	 * Get the current detected IP.
	 *
	 * @param   array   $attributes     Optional. The shortcode attributes.
	 * @param   string  $content        Optional. The shortcode content.
	 * @param   string  $name           Optional. The name of the shortcode.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public function sc_get_ip( $attributes = [], $content = null, $name = '' ) {
		$this->init();
		return $this->country->source();
	}

	/**
	 * Get the current detected country code.
	 *
	 * @param   array   $attributes     Optional. The shortcode attributes.
	 * @param   string  $content        Optional. The shortcode content.
	 * @param   string  $name           Optional. The name of the shortcode.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public function sc_get_code( $attributes = [], $content = null, $name = '' ) {
		$this->init();
		return $this->country->code();
	}

	/**
	 * Get the current detected country name.
	 *
	 * @param   array   $attributes     Optional. The shortcode attributes.
	 * @param   string  $content        Optional. The shortcode content.
	 * @param   string  $name           Optional. The name of the shortcode.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public function sc_get_country( $attributes = [], $content = null, $name = '' ) {
		$lang = null;
		if ( is_array( $attributes ) && array_key_exists( 'language', $attributes ) ) {
			$lang = $attributes['language'];
		}
		$this->init();
		return $this->country->name( $lang );
	}

	/**
	 * Get the current detected country lang.
	 *
	 * @param   array   $attributes     Optional. The shortcode attributes.
	 * @param   string  $content        Optional. The shortcode content.
	 * @param   string  $name           Optional. The name of the shortcode.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public function sc_get_lang( $attributes = [], $content = null, $name = '' ) {
		$lang = null;
		if ( is_array( $attributes ) && array_key_exists( 'language', $attributes ) ) {
			$lang = $attributes['language'];
		}
		$this->init();
		return $this->country->lang()->name( $lang );
	}

	/**
	 * Get the current detected country flag.
	 *
	 * @param   array   $attributes     Optional. The shortcode attributes.
	 * @param   string  $content        Optional. The shortcode content.
	 * @param   string  $name           Optional. The name of the shortcode.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public function sc_get_flag( $attributes = [], $content = null, $name = '' ) {
		$_attributes = shortcode_atts(
			[
				'type'  => 'emoji',  // Can be 'emoji', 'image' or 'squared-image'.
				'class' => '',
				'style' => '',
				'id'    => '',
				'alt'   => '',
			],
			$attributes
		);
		$type        = $_attributes['type'];
		$class       = $_attributes['class'];
		$style       = $_attributes['style'];
		$id          = $_attributes['id'];
		$alt         = $_attributes['alt'];
		$squared     = 'squared-image' === $type;
		$this->init();
		if ( 'emoji' === $type ) {
			return $this->country->flag()->emoji();
		}
		return $this->country->flag()->image( $class, $style, $id, $alt, $squared );
	}

	/**
	 * Process the content, regarding attributes.
	 *
	 * @param   array   $attributes     Optional. The shortcode attributes.
	 * @param   string  $content        Optional. The shortcode content.
	 * @param   string  $name           Optional. The name of the shortcode.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public function sc_if( $attributes = [], $content = null, $name = '' ) {
		$_attributes = shortcode_atts(
			[
				'operation'   => 'show',  // Can be 'show' or 'hide'.
				'country'     => '',
				'not-country' => '',
				'lang'        => '',
				'not-lang'    => '',
			],
			$attributes
		);
		$operation   = $attributes['operation'];
		$country     = array_filter( array_map( function ( $s ) { return strtoupper( $s ); }, explode( ',', str_replace( ' ', '', $_attributes['country'] ) ) ) );
		$notcountry  = array_filter( array_map( function ( $s ) { return strtoupper( $s ); }, explode( ',', str_replace( ' ', '', $_attributes['not-country'] ) ) ) );
		$lang        = array_filter( array_map( function ( $s ) { return strtoupper( $s ); }, explode( ',', str_replace( ' ', '', $_attributes['lang'] ) ) ) );
		$notlang     = array_filter( array_map( function ( $s ) { return strtoupper( $s ); }, explode( ',', str_replace( ' ', '', $_attributes['not-lang'] ) ) ) );
		$condition   = true;
		$this->init();
		if ( 0 < count( $country ) ) {
			$condition &= in_array( (string) $this->country->code(), $country, true );
		}
		if ( 0 < count( $notcountry ) ) {
			$condition &= ! in_array( (string) $this->country->code(), $notcountry, true );
		}
		if ( 0 < count( $lang ) ) {
			$condition &= in_array( strtoupper( $this->country->lang()->code() ), $lang, true );
		}
		if ( 0 < count( $notlang ) ) {
			$condition &= ! in_array( strtoupper( $this->country->lang()->code() ), $notlang, true );
		}
		switch ( $operation ) {
			case 'show':
				return $condition ? do_shortcode( $content ) : '';
			case 'hide':
				return $condition ? '' : do_shortcode( $content );
			default:
				return __( 'Malformed Shortcode', 'ip-locator' );
		}
	}

}
