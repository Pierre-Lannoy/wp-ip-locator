<?php
/**
 * Plugin initialization handling.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator\Plugin;

use IPLocator\System\Cache;
use IPLocator\System\Logger;

/**
 * Fired after 'plugins_loaded' hook.
 *
 * This class defines all code necessary to run during the plugin's initialization.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Initializer {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function initialize() {
		\IPLocator\System\Sitehealth::init();
		\IPLocator\System\APCu::init();
		$this->action_schedule();
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 */
	public function late_initialize() {
		require_once IPLOCATOR_PLUGIN_DIR . 'perfopsone/init.php';
	}

	/**
	 * Initialize the shedulable actions.
	 *
	 * @since 1.0.0
	 */
	public function action_schedule() {
		add_action( 'ip-locator-init-v4', [ 'IPLocator\Plugin\Feature\IPData', 'init_v4' ], 10, 0 );
		add_action( 'ip-locator-init-v6', [ 'IPLocator\Plugin\Feature\IPData', 'init_v6' ], 10, 0 );
		add_action( 'ip-locator-update-v4', [ 'IPLocator\Plugin\Feature\IPData', 'update_v4' ], 10, 0 );
		add_action( 'ip-locator-update-v6', [ 'IPLocator\Plugin\Feature\IPData', 'update_v6' ], 10, 0 );
		$semaphore = Cache::get( 'update/v4/initsemaphore' );
		if ( 1 === $semaphore ) {
			Logger::debug('Scheduling ip-locator-init-v4 action.');
			as_enqueue_async_action( 'ip-locator-init-v4', [], IPLOCATOR_SLUG );
		}
	}

}
