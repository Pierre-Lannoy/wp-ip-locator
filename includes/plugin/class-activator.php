<?php
/**
 * Plugin activation handling.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator\Plugin;

use IPLocator\Plugin\Feature\Schema;
use IPLocator\System\Cache;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Activator {

	/**
	 * Activate the plugin.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		Cache::set( 'update/v4/initsemaphore', -1, 'infinite' );
		Cache::set( 'update/v6/initsemaphore', -1, 'infinite' );
		$schema = new Schema();
		$schema->initialize();
	}

}
