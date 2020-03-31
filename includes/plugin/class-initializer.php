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
use IPLocator\System\Option;

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
		require_once IPLOCATOR_PLUGIN_DIR . 'includes/api/functions.php';
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
			Cache::set( 'update/v4/initsemaphore', 2, 'infinite' );
			Logger::debug( 'Scheduling ip-locator-init-v4 action.' );
			as_enqueue_async_action( 'ip-locator-init-v4', [], IPLOCATOR_SLUG );
		}
		$semaphore = Cache::get( 'update/v6/initsemaphore' );
		if ( 1 === $semaphore ) {
			Cache::set( 'update/v6/initsemaphore', 2, 'infinite' );
			Logger::debug( 'Scheduling ip-locator-init-v6 action.' );
			as_enqueue_async_action( 'ip-locator-init-v6', [], IPLOCATOR_SLUG );
		}
		if ( false === as_next_scheduled_action( 'ip-locator-update-v4' ) && (bool) Option::network_get( 'autoupdate ' ) ) {
			$start = time() + 1 * DAY_IN_SECONDS;
			$recur = IPLOCATOR_UPDATE_CYCLE * DAY_IN_SECONDS + random_int( -12, 12 ) * HOUR_IN_SECONDS + random_int( 1, 59 ) * MINUTE_IN_SECONDS;
			as_schedule_recurring_action( $start, $recur, 'ip-locator-update-v4', [], IPLOCATOR_SLUG );
		}
		if ( as_next_scheduled_action( 'ip-locator-update-v4' ) && ! (bool) Option::network_get( 'autoupdate ' ) ) {
			as_unschedule_all_actions( 'ip-locator-update-v4', [], IPLOCATOR_SLUG );
		}
		if ( false === as_next_scheduled_action( 'ip-locator-update-v6' ) && (bool) Option::network_get( 'autoupdate ' ) ) {
			$start = time() + 2 * DAY_IN_SECONDS;
			$recur = IPLOCATOR_UPDATE_CYCLE * DAY_IN_SECONDS + random_int( -12, 12 ) * HOUR_IN_SECONDS + random_int( 1, 59 ) * MINUTE_IN_SECONDS;
			as_schedule_recurring_action( $start, $recur, 'ip-locator-update-v6', [], IPLOCATOR_SLUG );
		}
		if ( as_next_scheduled_action( 'ip-locator-update-v6' ) && ! (bool) Option::network_get( 'autoupdate ' ) ) {
			as_unschedule_all_actions( 'ip-locator-update-v6', [], IPLOCATOR_SLUG );
		}
	}

}
