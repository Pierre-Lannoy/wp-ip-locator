<?php
/**
 * Plugin updates handling.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator\Plugin;

use IPLocator\System\Nag;
use IPLocator\System\Option;
use IPLocator\System\Environment;

use IPLocator\System\Role;
use Exception;
use IPLocator\Plugin\Feature\Schema;
use IPLocator\System\Cache;
use IPLocator\System\Markdown;

/**
 * Plugin updates handling.
 *
 * This class defines all code necessary to handle the plugin's updates.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Updater {

	/**
	 * Initializes the class, set its properties and performs
	 * post-update processes if needed.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$old = Option::network_get( 'version' );
		Option::network_set( 'version', IPLOCATOR_VERSION );
		if ( IPLOCATOR_VERSION !== $old ) {
			if ( '0.0.0' === $old ) {
				$this->install();
				// phpcs:ignore
				$message = sprintf( esc_html__( '%1$s has been correctly installed.', 'ip-locator' ), IPLOCATOR_PRODUCT_NAME );
			} else {
				$this->update( $old );
				// phpcs:ignore
				$message  = sprintf( esc_html__( '%1$s has been correctly updated from version %2$s to version %3$s.', 'ip-locator' ), IPLOCATOR_PRODUCT_NAME, $old, IPLOCATOR_VERSION );
				\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->notice( $message );
				// phpcs:ignore
				$message .= ' ' . sprintf( __( 'See <a href="%s">what\'s new</a>.', 'ip-locator' ), admin_url( 'admin.php?page=iplocator-settings&tab=about' ) );
			}
			Nag::add( 'update', 'info', $message );
		}
	}

	/**
	 * Performs post-installation processes.
	 *
	 * @since 1.0.0
	 */
	private function install() {

	}

	/**
	 * Performs post-update processes.
	 *
	 * @param   string $from   The version from which the plugin is updated.
	 * @since 1.0.0
	 */
	private function update( $from ) {
		$schema = new Schema();
		$schema->update();
	}

	/**
	 * Get the changelog.
	 *
	 * @param   array $attributes  'style' => 'markdown', 'html'.
	 *                             'mode'  => 'raw', 'clean'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 1.0.0
	 */
	public function sc_get_changelog( $attributes ) {
		$md = new Markdown();
		return $md->get_shortcode(  'CHANGELOG.md', $attributes  );
	}
}
