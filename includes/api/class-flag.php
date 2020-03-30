<?php
/**
 * IP Locator flag renderer.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator;

use Flagiconcss\Flags;
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

}


