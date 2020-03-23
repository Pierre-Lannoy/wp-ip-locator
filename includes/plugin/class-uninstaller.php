<?php
/**
 * Plugin deletion handling.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator\Plugin;

use IPLocator\System\Option;
use IPLocator\System\User;
use IPLocator\Plugin\Feature\Schema;

/**
 * Fired during plugin deletion.
 *
 * This class defines all code necessary to run during the plugin's deletion.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Uninstaller {

	/**
	 * Delete the plugin.
	 *
	 * @since 1.0.0
	 */
	public static function uninstall() {
		Option::site_delete_all();
		User::delete_all_meta();
		$schema = new Schema();
		$schema->finalize();
	}

}
