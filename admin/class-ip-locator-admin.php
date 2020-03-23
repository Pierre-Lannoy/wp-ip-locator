<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator\Plugin;

use IPLocator\Plugin\Feature\Analytics;
use IPLocator\Plugin\Feature\AnalyticsFactory;
use IPLocator\System\Assets;
use IPLocator\System\Logger;
use IPLocator\System\Role;
use IPLocator\System\Option;
use IPLocator\System\Form;
use IPLocator\System\Blog;
use IPLocator\System\Date;
use IPLocator\System\Timezone;
use IPLocator\System\GeoIP;
use IPLocator\System\Environment;
use PerfOpsOne\AdminMenus;

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class IPLocator_Admin {

	/**
	 * The assets manager that's responsible for handling all assets of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Assets    $assets    The plugin assets manager.
	 */
	protected $assets;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->assets = new Assets();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		$this->assets->register_style( IPLOCATOR_ASSETS_ID, IPLOCATOR_ADMIN_URL, 'css/ip-locator.min.css' );
		$this->assets->register_style( 'iplocator-daterangepicker', IPLOCATOR_ADMIN_URL, 'css/daterangepicker.min.css' );
		$this->assets->register_style( 'iplocator-switchery', IPLOCATOR_ADMIN_URL, 'css/switchery.min.css' );
		$this->assets->register_style( 'iplocator-tooltip', IPLOCATOR_ADMIN_URL, 'css/tooltip.min.css' );
		$this->assets->register_style( 'iplocator-chartist', IPLOCATOR_ADMIN_URL, 'css/chartist.min.css' );
		$this->assets->register_style( 'iplocator-chartist-tooltip', IPLOCATOR_ADMIN_URL, 'css/chartist-plugin-tooltip.min.css' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$this->assets->register_script( IPLOCATOR_ASSETS_ID, IPLOCATOR_ADMIN_URL, 'js/ip-locator.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'iplocator-moment-with-locale', IPLOCATOR_ADMIN_URL, 'js/moment-with-locales.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'iplocator-daterangepicker', IPLOCATOR_ADMIN_URL, 'js/daterangepicker.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'iplocator-switchery', IPLOCATOR_ADMIN_URL, 'js/switchery.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'iplocator-chartist', IPLOCATOR_ADMIN_URL, 'js/chartist.min.js', [ 'jquery' ] );
		$this->assets->register_script( 'iplocator-chartist-tooltip', IPLOCATOR_ADMIN_URL, 'js/chartist-plugin-tooltip.min.js', [ 'iplocator-chartist' ] );
	}

	/**
	 * Init PerfOps admin menus.
	 *
	 * @param array $perfops    The already declared menus.
	 * @return array    The completed menus array.
	 * @since 1.0.0
	 */
	public function init_perfops_admin_menus( $perfops ) {
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
			$perfops['settings'][] = [
				'name'          => IPLOCATOR_PRODUCT_NAME,
				'description'   => '',
				'icon_callback' => [ \IPLocator\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'iplocator-settings',
				/* translators: as in the sentence "IP Locator Settings" or "WordPress Settings" */
				'page_title'    => sprintf( esc_html__( '%s Settings', 'ip-locator' ), IPLOCATOR_PRODUCT_NAME ),
				'menu_title'    => IPLOCATOR_PRODUCT_NAME,
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_settings_page' ],
				'position'      => 50,
				'plugin'        => IPLOCATOR_SLUG,
				'version'       => IPLOCATOR_VERSION,
				'activated'     => true,
				'remedy'        => '',
				'statistics'    => [ '\IPLocator\System\Statistics', 'sc_get_raw' ],
			];
		}
		return $perfops;
	}

	/**
	 * Set the items in the settings menu.
	 *
	 * @since 1.0.0
	 */
	public function init_admin_menus() {
		add_filter( 'init_perfops_admin_menus', [ $this, 'init_perfops_admin_menus' ] );
		AdminMenus::initialize();
	}

	/**
	 * Initializes settings sections.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_sections() {
		add_settings_section( 'iplocator_plugin_features_section', esc_html__( 'Plugin features', 'ip-locator' ), [ $this, 'plugin_features_section_callback' ], 'iplocator_plugin_features_section' );
		add_settings_section( 'iplocator_plugin_options_section', esc_html__( 'Plugin options', 'ip-locator' ), [ $this, 'plugin_options_section_callback' ], 'iplocator_plugin_options_section' );
	}

	/**
	 * Add links in the "Actions" column on the plugins view page.
	 *
	 * @param string[] $actions     An array of plugin action links. By default this can include 'activate',
	 *                              'deactivate', and 'delete'.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array    $plugin_data An array of plugin data. See `get_plugin_data()`.
	 * @param string   $context     The plugin context. By default this can include 'all', 'active', 'inactive',
	 *                              'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
	 * @return array Extended list of links to print in the "Actions" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_actions_links( $actions, $plugin_file, $plugin_data, $context ) {
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=iplocator-settings' ) ), esc_html__( 'Settings', 'ip-locator' ) );
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=iplocator-viewer' ) ), esc_html__( 'Statistics', 'ip-locator' ) );
		return $actions;
	}

	/**
	 * Add links in the "Description" column on the plugins view page.
	 *
	 * @param array  $links List of links to print in the "Description" column on the Plugins page.
	 * @param string $file Path to the plugin file relative to the plugins directory.
	 * @return array Extended list of links to print in the "Description" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_row_meta( $links, $file ) {
		if ( 0 === strpos( $file, IPLOCATOR_SLUG . '/' ) ) {
			$links[] = '<a href="https://wordpress.org/support/plugin/' . IPLOCATOR_SLUG . '/">' . __( 'Support', 'ip-locator' ) . '</a>';
			$links[] = '<a href="https://github.com/Pierre-Lannoy/wp-ip-locator">' . __( 'GitHub repository', 'ip-locator' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Get the content of the tools page.
	 *
	 * @since 1.0.0
	 */
	public function get_viewer_page() {
		$analytics = AnalyticsFactory::get_analytics();
		include IPLOCATOR_ADMIN_DIR . 'partials/ip-locator-admin-view-analytics.php';
	}

	/**
	 * Get the content of the settings page.
	 *
	 * @since 1.0.0
	 */
	public function get_settings_page() {
		if ( ! ( $tab = filter_input( INPUT_GET, 'tab' ) ) ) {
			$tab = filter_input( INPUT_POST, 'tab' );
		}
		if ( ! ( $action = filter_input( INPUT_GET, 'action' ) ) ) {
			$action = filter_input( INPUT_POST, 'action' );
		}
		if ( $action && $tab ) {
			switch ( $tab ) {
				case 'misc':
					switch ( $action ) {
						case 'do-save':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( ! empty( $_POST ) && array_key_exists( 'submit', $_POST ) ) {
									$this->save_options();
								} elseif ( ! empty( $_POST ) && array_key_exists( 'reset-to-defaults', $_POST ) ) {
									$this->reset_options();
								}
							}
							break;
					}
					break;
			}
		}
		include IPLOCATOR_ADMIN_DIR . 'partials/ip-locator-admin-settings-main.php';
	}

	/**
	 * Save the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function save_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'iplocator-plugin-options' ) ) {
				Option::network_set( 'use_cdn', array_key_exists( 'iplocator_plugin_options_usecdn', $_POST ) ? (bool) filter_input( INPUT_POST, 'iplocator_plugin_options_usecdn' ) : false );
				Option::network_set( 'display_nag', array_key_exists( 'iplocator_plugin_options_nag', $_POST ) ? (bool) filter_input( INPUT_POST, 'iplocator_plugin_options_nag' ) : false );
				Option::network_set( 'status', array_key_exists( 'iplocator_plugin_features_status', $_POST ) ? (bool) filter_input( INPUT_POST, 'iplocator_plugin_features_status' ) : false );
				Option::network_set( 'info', array_key_exists( 'iplocator_plugin_features_info', $_POST ) ? (bool) filter_input( INPUT_POST, 'iplocator_plugin_features_info' ) : false );
				flush_rewrite_rules();
				$message = esc_html__( 'Plugin settings have been saved.', 'ip-locator' );
				$code    = 0;
				add_settings_error( 'iplocator_no_error', $code, $message, 'updated' );
				Logger::info( 'Plugin settings updated.', $code );
			} else {
				$message = esc_html__( 'Plugin settings have not been saved. Please try again.', 'ip-locator' );
				$code    = 2;
				add_settings_error( 'iplocator_nonce_error', $code, $message, 'error' );
				Logger::warning( 'Plugin settings not updated.', $code );
			}
		}
	}

	/**
	 * Reset the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function reset_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'iplocator-plugin-options' ) ) {
				Option::reset_to_defaults();
				$message = esc_html__( 'Plugin settings have been reset to defaults.', 'ip-locator' );
				$code    = 0;
				add_settings_error( 'iplocator_no_error', $code, $message, 'updated' );
				Logger::info( 'Plugin settings reset to defaults.', $code );
			} else {
				$message = esc_html__( 'Plugin settings have not been reset to defaults. Please try again.', 'ip-locator' );
				$code    = 2;
				add_settings_error( 'iplocator_nonce_error', $code, $message, 'error' );
				Logger::warning( 'Plugin settings not reset to defaults.', $code );
			}
		}
	}

	/**
	 * Callback for plugin options section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_options_section_callback() {
		$form = new Form();
		if ( defined( 'DECALOG_VERSION' ) ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site is currently using %s.', 'ip-locator' ), '<em>DecaLog v' . DECALOG_VERSION .'</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site does not use any logging plugin. To log all events triggered in IP Locator, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'ip-locator' ), '<a href="https://wordpress.org/plugins/decalog/">DecaLog</a>' );
		}
		add_settings_field(
			'iplocator_plugin_options_logger',
			__( 'Logging', 'ip-locator' ),
			[ $form, 'echo_field_simple_text' ],
			'iplocator_plugin_options_section',
			'iplocator_plugin_options_section',
			[
				'text' => $help
			]
		);
		register_setting( 'iplocator_plugin_options_section', 'iplocator_plugin_options_logger' );
		add_settings_field(
			'iplocator_plugin_options_usecdn',
			__( 'Resources', 'ip-locator' ),
			[ $form, 'echo_field_checkbox' ],
			'iplocator_plugin_options_section',
			'iplocator_plugin_options_section',
			[
				'text'        => esc_html__( 'Use public CDN', 'ip-locator' ),
				'id'          => 'iplocator_plugin_options_usecdn',
				'checked'     => Option::network_get( 'use_cdn' ),
				'description' => esc_html__( 'If checked, IP Locator will use a public CDN (jsDelivr) to serve scripts and stylesheets.', 'ip-locator' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'iplocator_plugin_options_section', 'iplocator_plugin_options_usecdn' );
		add_settings_field(
			'iplocator_plugin_options_nag',
			__( 'Admin notices', 'ip-locator' ),
			[ $form, 'echo_field_checkbox' ],
			'iplocator_plugin_options_section',
			'iplocator_plugin_options_section',
			[
				'text'        => esc_html__( 'Display', 'ip-locator' ),
				'id'          => 'iplocator_plugin_options_nag',
				'checked'     => Option::network_get( 'display_nag' ),
				'description' => esc_html__( 'Allows IP Locator to display admin notices throughout the admin dashboard.', 'ip-locator' ) . '<br/>' . esc_html__( 'Note: IP Locator respects DISABLE_NAG_NOTICES flag.', 'ip-locator' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'iplocator_plugin_options_section', 'iplocator_plugin_options_nag' );
	}

	/**
	 * Callback for plugin features section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_features_section_callback() {
		$form = new Form();
		add_settings_field(
			'iplocator_plugin_features_status',
			__( 'Server status', 'ip-locator' ),
			[ $form, 'echo_field_checkbox' ],
			'iplocator_plugin_features_section',
			'iplocator_plugin_features_section',
			[
				'text'        => sprintf( esc_html__( 'Activate .htaccess rule for %s', 'ip-locator' ), 'mod_status' ),
				'id'          => 'iplocator_plugin_features_status',
				'checked'     => Option::network_get( 'status' ),
				'description' => sprintf( esc_html__( 'If checked, Apache server status will be served via the url %s.', 'ip-locator' ), site_url( 'server-status') ) . '<br/>' . esc_html__( 'Note: this only sets up your .htaccess file. For this to work, the module must be activated in your Apache configuration.', 'ip-locator' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'iplocator_plugin_features_section', 'iplocator_plugin_features_status' );
		add_settings_field(
			'iplocator_plugin_features_info',
			__( 'Server info', 'htaccess-server-info-server-info' ),
			[ $form, 'echo_field_checkbox' ],
			'iplocator_plugin_features_section',
			'iplocator_plugin_features_section',
			[
				'text'        => sprintf( esc_html__( 'Activate .htaccess rule for %s', 'ip-locator' ), 'mod_info' ),
				'id'          => 'iplocator_plugin_features_info',
				'checked'     => Option::network_get( 'info' ),
				'description' => sprintf( esc_html__( 'If checked, Apache server info will be served via the url %s.', 'ip-locator' ), site_url( 'server-info') ) . '<br/>' . esc_html__( 'Note: this only sets up your .htaccess file. For this to work, the module must be activated in your Apache configuration.', 'ip-locator' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'iplocator_plugin_features_section', 'iplocator_plugin_features_info' );
	}

}
