<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator\Plugin;

use IPLocator\System\Loader;
use IPLocator\System\I18n;
use IPLocator\System\Assets;
use IPLocator\Library\Libraries;
use IPLocator\System\Nag;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->loader = new Loader();
		$this->set_locale();
		$this->define_global_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Adr_Sync_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function set_locale() {
		$plugin_i18n = new I18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the features of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_global_hooks() {
		$bootstrap = new Initializer();
		$assets    = new Assets();
		$updater   = new Updater();
		$libraries = new Libraries();
		$this->loader->add_filter( 'perfopsone_plugin_info', self::class, 'perfopsone_plugin_info' );
		$this->loader->add_action( 'init', $bootstrap, 'initialize' );
		$this->loader->add_action( 'init', $bootstrap, 'late_initialize', PHP_INT_MAX );
		$this->loader->add_action( 'wp_head', $assets, 'prefetch' );
		add_shortcode( 'iplocator-changelog', [ $updater, 'sc_get_changelog' ] );
		add_shortcode( 'iplocator-libraries', [ $libraries, 'sc_get_list' ] );
		add_shortcode( 'iplocator-statistics', [ 'IPLocator\System\Statistics', 'sc_get_raw' ] );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new IPLocator_Admin();
		$nag          = new Nag();
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'init_admin_menus' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'init_settings_sections' );
		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( IPLOCATOR_PLUGIN_DIR . IPLOCATOR_SLUG . '.php' ), $plugin_admin, 'add_actions_links', 10, 4 );
		$this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'add_row_meta', 10, 2 );
		$this->loader->add_action( 'admin_notices', $nag, 'display' );
		$this->loader->add_action( 'wp_ajax_hide_iplocator_nag', $nag, 'hide_callback' );
		//$this->loader->add_action( 'wp_ajax_iplocator_get_stats', 'IPLocator\Plugin\Feature\AnalyticsFactory', 'get_stats_callback' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_public_hooks() {
		$plugin_public = new IPLocator_Public();
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  1.0.0
	 * @return Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Adds full plugin identification.
	 *
	 * @param array $plugin The already set identification information.
	 * @return array The extended identification information.
	 * @since 1.0.0
	 */
	public static function perfopsone_plugin_info( $plugin ) {
		$plugin[ IPLOCATOR_SLUG ] = [
			'name'    => IPLOCATOR_PRODUCT_NAME,
			'code'    => IPLOCATOR_CODENAME,
			'version' => IPLOCATOR_VERSION,
			'url'     => IPLOCATOR_PRODUCT_URL,
			'icon'    => self::get_base64_logo(),
		];
		return $plugin;
	}

	/**
	 * Returns a base64 svg resource for the plugin logo.
	 *
	 * @return string The svg resource as a base64.
	 * @since 1.0.0
	 */
	public static function get_base64_logo() {
		$source  = '<svg width="100%" height="100%" viewBox="0 0 1001 1001" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;">';
		$source .= '<g id="Apache-Status-Info" serif:id="Apache Status &amp; Info" transform="matrix(10.0067,0,0,10.0067,0,0)">';
		$source .= '<rect x="0" y="0" width="100" height="100" style="fill:none;"/>';
		$source .= '<g id="Icons" transform="matrix(2.36049,0,0,1.89238,5.4056,2.76723)">';
		$source .= '<g transform="matrix(0,-66.5176,-66.5176,0,13.2781,58.7333)"><path d="M0.838,0.113C0.838,0.129 0.824,0.143 0.808,0.143L0.206,0.143C0.19,0.143 0.176,0.129 0.176,0.113L0.176,-0.113C0.176,-0.129 0.19,-0.143 0.206,-0.143L0.808,-0.143C0.824,-0.143 0.838,-0.129 0.838,-0.113L0.838,0.113Z" style="fill:url(#_Linear1);fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(1,0,0,1,7.27811,7.5)"><path d="M0,0L12,0" style="fill:none;fill-rule:nonzero;stroke:white;stroke-width:0.19px;"/></g>';
		$source .= '<g transform="matrix(1,0,0,1,7.27811,10.5)"><path d="M0,0L12,0" style="fill:none;fill-rule:nonzero;stroke:white;stroke-width:0.19px;"/></g>';
		$source .= '<g transform="matrix(1,0,0,1,7.27811,13.5)"><path d="M0,0L9,0" style="fill:none;fill-rule:nonzero;stroke:white;stroke-width:0.19px;"/></g>';
		$source .= '<g transform="matrix(1,0,0,1,7.27811,35.5)"><path d="M0,0L12,0" style="fill:none;fill-rule:nonzero;stroke:white;stroke-width:0.19px;"/></g>';
		$source .= '<g transform="matrix(1,0,0,1,7.27811,38.5)"><path d="M0,0L12,0" style="fill:none;fill-rule:nonzero;stroke:white;stroke-width:0.19px;"/></g>';
		$source .= '<g transform="matrix(1,0,0,1,7.27811,41.5)"><path d="M0,0L9,0" style="fill:none;fill-rule:nonzero;stroke:white;stroke-width:0.19px;"/></g>';
		$source .= '<g transform="matrix(-9.87164e-18,-1,-0.798905,-1.53595e-17,13.7066,22.5)"><circle cx="-2" cy="0" r="2" style="fill:none;stroke:white;stroke-width:0.22px;stroke-linecap:butt;stroke-linejoin:miter;"/></g>';
		$source .= '<g transform="matrix(1.68802,0,0,1.80656,5.39621,3.0264)"><path d="M15.826,0C15.683,0 15.544,0.04 15.422,0.114C15.046,0.336 14.42,0.965 13.674,1.877L13.518,2.069C13.32,2.315 13.115,2.581 12.904,2.862C12.186,3.821 11.514,4.812 10.888,5.833L10.822,5.941C10.208,6.943 9.638,7.971 9.113,9.022C8.7,9.854 8.333,10.707 8.013,11.579C7.922,11.83 7.839,12.072 7.763,12.305C7.701,12.504 7.641,12.703 7.585,12.901C7.452,13.368 7.337,13.835 7.243,14.299L7.244,14.301L7.231,14.362C7.119,14.921 7.041,15.486 6.998,16.054L6.993,16.113C6.713,15.664 5.964,15.227 5.965,15.231C6.502,16.009 6.909,16.781 6.969,17.539C6.682,17.598 6.288,17.513 5.833,17.345C6.307,17.781 6.663,17.901 6.802,17.933C6.366,17.96 5.913,18.26 5.456,18.604C6.124,18.331 6.664,18.224 7.051,18.311C6.394,20.193 5.779,22.089 5.208,23.999C5.381,23.95 5.518,23.817 5.572,23.645C5.682,23.276 6.409,20.859 7.549,17.681L7.647,17.408L7.675,17.332C7.795,16.999 7.92,16.659 8.049,16.312L8.138,16.075L8.14,16.07L7.536,14.877L8.14,16.069C8.259,15.752 8.38,15.431 8.505,15.106L8.557,14.97L8.61,14.833L8.651,14.729L8.61,14.834L8.557,14.97L8.505,15.106C8.38,15.431 8.259,15.752 8.14,16.069L8.246,16.277L8.34,16.267L8.35,16.239C8.503,15.822 8.654,15.415 8.804,15.019L8.809,15.005C8.654,15.415 8.501,15.827 8.351,16.239L8.341,16.267L8.274,16.45C8.156,16.775 8.037,17.105 7.918,17.443L7.913,17.458L7.862,17.601C7.782,17.829 7.712,18.034 7.553,18.5C7.816,18.62 8.027,18.936 8.227,19.294C8.208,18.916 8.04,18.56 7.761,18.305C9.057,18.363 10.174,18.036 10.752,17.088C10.804,17.003 10.851,16.915 10.893,16.82C10.631,17.153 10.305,17.294 9.693,17.259C10.595,16.855 11.047,16.468 11.447,15.826C11.548,15.662 11.642,15.495 11.728,15.323C10.939,16.133 10.026,16.363 9.063,16.188L9.061,16.188L9.02,16.181C9.883,16.074 11.03,15.429 11.772,14.633C12.114,14.266 12.424,13.833 12.711,13.327C12.925,12.95 13.125,12.532 13.316,12.069C13.483,11.665 13.643,11.227 13.798,10.752C13.544,10.882 13.27,10.97 12.988,11.013C12.942,11.021 12.896,11.028 12.85,11.035L12.855,11.033C12.9,11.026 12.944,11.02 12.988,11.012C13.033,11.004 13.078,10.996 13.122,10.986L12.989,11.01L12.857,11.033C13.659,10.723 14.165,10.126 14.533,9.396C14.239,9.596 13.911,9.74 13.565,9.82C13.508,9.832 13.451,9.842 13.394,9.851L13.351,9.857L13.352,9.856L13.361,9.855L13.393,9.85C13.446,9.842 13.498,9.832 13.55,9.821L13.564,9.818L13.548,9.821L13.354,9.854C13.632,9.737 13.867,9.607 14.071,9.453C14.221,9.337 14.358,9.206 14.482,9.062C14.562,8.967 14.637,8.864 14.707,8.752L14.771,8.648L14.848,8.496C14.985,8.219 15.108,7.935 15.216,7.645L15.247,7.557C15.275,7.472 15.299,7.396 15.317,7.33C15.344,7.231 15.361,7.152 15.37,7.094C15.34,7.118 15.309,7.139 15.276,7.158C15.033,7.303 14.616,7.435 14.28,7.497L14.181,7.508L14.18,7.509L11.913,7.758L11.901,7.782L11.824,7.94L11.589,8.428L11.589,8.428C11.668,8.262 11.746,8.099 11.824,7.94L11.901,7.782C11.905,7.773 11.91,7.765 11.913,7.756L11.829,7.765L11.762,7.633C11.634,7.886 11.508,8.141 11.384,8.396L11.18,8.82C10.801,9.615 10.437,10.417 10.088,11.226C9.716,12.089 9.355,12.956 9.007,13.829L9.093,13.613C9.414,12.813 9.746,12.017 10.088,11.226C10.437,10.417 10.802,9.615 11.18,8.819L11.384,8.395C11.502,8.151 11.621,7.91 11.742,7.671L11.762,7.633C11.953,7.255 12.146,6.883 12.341,6.517C12.549,6.127 12.761,5.746 12.974,5.376C13.191,4.999 13.415,4.626 13.646,4.257L13.686,4.193C13.91,3.839 14.136,3.498 14.363,3.172C14.824,2.503 15.332,1.868 15.885,1.273L15.827,1.335C15.667,1.511 15.183,2.076 14.452,3.198C15.156,3.163 16.237,3.019 17.119,2.868C17.381,1.398 16.862,0.726 16.862,0.726C16.862,0.726 16.418,0.007 15.83,0.001L15.826,0ZM14.178,7.507C14.838,7.203 15.134,6.928 15.42,6.531C15.496,6.421 15.573,6.307 15.649,6.188C15.882,5.825 16.11,5.424 16.314,5.026C16.511,4.642 16.685,4.261 16.818,3.917C16.896,3.721 16.962,3.521 17.018,3.317C17.059,3.16 17.092,3.01 17.117,2.868C16.234,3.018 15.153,3.163 14.449,3.197C14.209,3.566 13.977,3.94 13.753,4.319C13.549,4.664 13.331,5.042 13.103,5.456C12.686,6.212 12.288,6.979 11.91,7.755L14.177,7.506L14.178,7.507ZM17.806,2.198L17.806,2.264L17.962,2.264L17.962,2.703L18.034,2.703L18.034,2.264L18.191,2.264L18.191,2.198L17.806,2.198ZM18.269,2.198L18.269,2.704L18.335,2.704L18.335,2.303L18.507,2.651L18.553,2.651L18.725,2.303L18.725,2.704L18.791,2.704L18.791,2.198L18.704,2.198L18.53,2.551L18.355,2.198L18.269,2.198ZM14.261,7.499L14.159,7.515L14.16,7.514L14.26,7.498L14.261,7.499ZM14.171,7.511L14.171,7.511ZM11.431,8.753L11.325,8.98L11.192,9.267C10.875,9.957 10.568,10.653 10.271,11.352C9.927,12.163 9.594,12.978 9.272,13.798C9.124,14.176 8.974,14.563 8.823,14.961L8.818,14.975C9.533,13.041 10.325,11.137 11.192,9.266L11.325,8.979L11.431,8.753Z" style="fill:url(#_Linear2);fill-rule:nonzero;"/></g>';
		$source .= '</g>';
		$source .= '</g>';
		$source .= '<defs>';
		$source .= '<linearGradient id="_Linear1" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,0)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
		$source .= '<linearGradient id="_Linear2" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(20.4783,0,0,-67.7781,4.00985,11.9995)"><stop offset="0" style="stop-color:rgb(255,147,8);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(255,216,111);stop-opacity:1"/></linearGradient>';
		$source .= '</defs>';
		$source .= '</svg>';
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

}
