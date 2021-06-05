<?php
/**
 * Main plugin file.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       IP Locator
 * Plugin URI:        https://github.com/Pierre-Lannoy/wp-ip-locator
 * Description:       Country and language IP-based detection.
 * Version:           3.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Pierre Lannoy
 * Author URI:        https://pierre.lannoy.fr
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       ip-locator
 * Domain Path:       /languages
 * Network:           true
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/system/class-option.php';
require_once __DIR__ . '/includes/system/class-environment.php';
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/includes/libraries/class-libraries.php';
require_once __DIR__ . '/includes/libraries/autoload.php';
require_once __DIR__ . '/includes/features/class-schema.php';
require_once __DIR__ . '/includes/features/class-wpcli.php';

// Automattic action scheduler
add_filter( 'action_scheduler_migration_dependencies_met', '__return_false' );
require_once __DIR__ . '/includes/libraries/action-scheduler/action-scheduler.php';


/**
 * The code that runs during plugin activation.
 *
 * @since 1.0.0
 */
function iplocator_activate() {
	IPLocator\Plugin\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since 1.0.0
 */
function iplocator_deactivate() {
	IPLocator\Plugin\Deactivator::deactivate();
}

/**
 * The code that runs during plugin uninstallation.
 *
 * @since 1.0.0
 */
function iplocator_uninstall() {
	IPLocator\Plugin\Uninstaller::uninstall();
}

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function iplocator_run() {
	\DecaLog\Engine::initPlugin( IPLOCATOR_SLUG, IPLOCATOR_PRODUCT_NAME, IPLOCATOR_VERSION, \IPLocator\Plugin\Core::get_base64_logo() );
	\IPLocator\System\Cache::init();
	$plugin = new IPLocator\Plugin\Core();
	$plugin->run();
}

register_activation_hook( __FILE__, 'iplocator_activate' );
register_deactivation_hook( __FILE__, 'iplocator_deactivate' );
register_uninstall_hook( __FILE__, 'iplocator_uninstall' );
iplocator_run();
