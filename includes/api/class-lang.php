<?php
/**
 * IP Locator lang detector.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator;

use IPLocator\System\L10n;

/**
 * IP Locator country lang class.
 *
 * This class defines all code necessary to detect lang.
 *
 * @package API
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Lang {

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
	 * Get the country main language.
	 *
	 * @return string   The main language if found, '' otherwise.
	 * @since 1.0.0
	 */
	public function code() {
		return L10n::get_main_lang_code( $this->cc );
	}

	/**
	 * Get the country main language name.
	 *
	 * @param  string $locale   Optional. The locale.
	 * @return string           The country main language name, localized if possible.
	 * @since 1.0.0
	 */
	public function name( $locale = null ) {
		return L10n::get_main_lang_name( $this->cc, self::code(), $locale );
	}

}


