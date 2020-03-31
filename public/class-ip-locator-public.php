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
	 * @param   array $attributes  Unused.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public function sc_get_ip( $attributes ) {
		$this->init();
		return $this->country->source();
	}

	/**
	 * Get the current detected country code.
	 *
	 * @param   array $attributes  Unused.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public function sc_get_code( $attributes ) {
		$this->init();
		return $this->country->code();
	}

	/**
	 * Get the current detected country name.
	 *
	 * @param   array $attributes  'language' => 'fr' (optional).
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public function sc_get_country( $attributes ) {
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
	 * @param   array $attributes  'language' => 'fr' (optional).
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public function sc_get_lang( $attributes ) {
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
	 * @param   array $attributes  'type' => 'emoji'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public function sc_get_flag( $attributes ) {
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

}
