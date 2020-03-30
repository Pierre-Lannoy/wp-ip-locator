<?php
/**
 * IP Locator flag renderer.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator;

use Flagiconcss\Flag as SvgFlag;
use IPLocator\System\EmojiFlag;

/**
 * IP Locator country flag renderer.
 *
 * This class defines all code necessary to render flags.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Flag {

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
	 * @since 1.0.0
	 */
	public function __construct( $cc ) {
		$this->cc = $cc;
	}

	/**
	 * Get the emoji flag.
	 *
	 * @return string   The emoji flag, ready to print.
	 * @since 1.0.0
	 */
	public function emoji() {
		return EmojiFlag::get( $this->cc );
	}

	/**
	 * Get the svg flag base 64 encoded.
	 *
	 * @param boolean $squared Optional. The flag must be squared.
	 * @return  string   The svg flag base 64 encoded.
	 * @since 1.0.0
	 */
	public function svg( $squared = false ) {
		return SvgFlag::get_base64( $this->cc, $squared );
	}

	/**
	 * Get the image flag.
	 *
	 * @param   string    $class      Optional. The class(es) name(s).
	 * @param   string    $style      Optional. The style.
	 * @param   string    $id         Optional. The ID.
	 * @param   string    $alt        Optional. The alt text.
	 * @param   boolean   $squared    Optional. The flag must be squared.
	 * @return  string                The flag html image tag, ready to print.
	 * @since 1.0.0
	 */
	public function image( $class = '', $style = '', $id = '', $alt = '', $squared = false ) {
		$class = '' === $class ? ' class="iplocator-country-flag"' : ' class="iplocator-country-flag ' . $class . '"';
		$style = '' === $style ? '' : ' style="' . $style . '"';
		$id    = '' === $id ? '' : ' $id="' . $id . '"';
		$alt   = '' === $alt ? '' : ' alt="' . $alt . '"';
		return '<img' . $class . $style . $id . $alt . ' src="' . $this->svg( $squared ) . '" />';
	}

}


