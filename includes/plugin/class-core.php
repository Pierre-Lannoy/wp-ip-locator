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
use IPLocator\Plugin\Feature\CSSModifier;
use IPLocator\System\Option;
use IPLocator\API\IPRoute;

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
		CSSModifier::init();
		// REST API
		$routes = new IPRoute();
		$this->loader->add_action( 'rest_api_init', $routes, 'register_routes' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new IP_Locator_Admin();
		$nag          = new Nag();
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'init_admin_menus' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'init_settings_sections' );
		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( IPLOCATOR_PLUGIN_DIR . IPLOCATOR_SLUG . '.php' ), $plugin_admin, 'add_actions_links', 10, 4 );
		$this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'add_row_meta', 10, 2 );
		$this->loader->add_action( 'admin_notices', $nag, 'display' );
		$this->loader->add_action( 'wp_ajax_hide_iplocator_nag', $nag, 'hide_callback' );
		$this->loader->add_action( 'wp_ajax_iplocator_get_stats', 'IPLocator\Plugin\Feature\AnalyticsFactory', 'get_stats_callback' );
		$this->loader->add_filter( 'myblogs_blog_actions', $plugin_admin, 'blog_action', 10, 2 );
		$this->loader->add_filter( 'manage_sites_action_links', $plugin_admin, 'site_action', 10, 3 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_public_hooks() {
		$plugin_public = new IP_Locator_Public();
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		if ( Option::network_get( 'shortcode' ) ) {
			add_shortcode( 'iplocator-ip', [ $plugin_public, 'sc_get_ip' ] );
			add_shortcode( 'iplocator-code', [ $plugin_public, 'sc_get_code' ] );
			add_shortcode( 'iplocator-country', [ $plugin_public, 'sc_get_country' ] );
			add_shortcode( 'iplocator-flag', [ $plugin_public, 'sc_get_flag' ] );
			add_shortcode( 'iplocator-lang', [ $plugin_public, 'sc_get_lang' ] );
			add_shortcode( 'iplocator-if', [ $plugin_public, 'sc_if' ] );
		}
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
		$source .= '<g id="IP-Locator" serif:id="IP Locator" transform="matrix(10.0067,0,0,10.0067,0,0)">';
		$source .= '<rect x="0" y="0" width="100" height="100" style="fill:none;"/>';
		$source .= '<g id="Earth" transform="matrix(1.89139,0,0,1.89139,-2.59794,-2.64791)">';
		$source .= '<g transform="matrix(0,72.0467,72.0467,0,25,-8.19368)"><circle cx="0.461" cy="0" r="0.264" style="fill:url(#_Linear1);"/></g>';
		$source .= '<g>';
		$source .= '<clipPath id="_clip2"><path d="M6,25C6,35.494 14.506,44 25,44C35.493,44 44,35.494 44,25C44,14.507 35.493,6 25,6C14.506,6 6,14.507 6,25Z" clip-rule="nonzero"/></clipPath>';
		$source .= '<g clip-path="url(#_clip2)">';
		$source .= '<g opacity="0.5">';
		$source .= '<g transform="matrix(1,0,0,1,9.1582,34.638)">';
		$source .= '<path d="M0,-20.41C0.325,-20.5 0.487,-20.802 0.812,-20.779C1.137,-20.756 1.276,-20.825 1.183,-21.08C1.09,-21.336 0.789,-21.753 0.58,-21.452C0.371,-21.15 0.023,-20.871 -0.255,-21.127C-0.534,-21.382 -0.464,-21.629 -0.65,-21.749C-0.835,-21.869 -0.882,-21.749 -0.882,-21.749C-0.928,-21.66 -1.091,-20.941 -0.696,-20.802C-0.302,-20.663 -0.325,-20.32 0,-20.41M-0.279,-19.177C0,-19.085 0.294,-18.688 0.263,-19.177C0.232,-19.665 0.526,-19.943 0.263,-20.059C0,-20.176 -0.07,-19.864 -0.07,-19.864C-0.139,-19.827 -0.557,-19.268 -0.279,-19.177M-1.652,-19.943C-1.771,-19.802 -1.838,-19.65 -1.694,-19.572C-1.393,-19.41 -1.213,-19.596 -0.955,-19.596C-0.696,-19.596 -0.534,-19.529 -0.604,-19.864C-0.625,-19.964 -0.695,-20.04 -0.785,-20.097C-1.065,-20.276 -1.439,-20.2 -1.652,-19.943M2.367,6.3C2.506,6.486 3.179,7.437 3.156,7.112C3.132,6.787 3.944,4.374 3.944,4.374C3.944,4.374 5.337,3.562 5.337,3.214C5.337,2.866 6.357,1.172 6.775,0.986C7.193,0.801 7.75,0.754 7.727,0.058C7.704,-0.638 8.817,-1.473 8.446,-1.914C8.075,-2.355 6.265,-2.169 6.125,-2.657C5.986,-3.144 6.288,-3.399 5.731,-3.585C5.174,-3.77 3.62,-4.443 3.249,-4.374C2.877,-4.304 2.738,-4.768 2.459,-4.606C2.181,-4.443 1.671,-4.011 1.787,-3.585C1.787,-3.585 2.065,-3.167 1.787,-3.005C1.508,-2.842 0.975,-2.494 1.207,-2.054C1.438,-1.613 1.81,-0.522 2.065,-0.499C2.32,-0.475 2.993,-0.104 2.923,0.267C2.854,0.638 2.228,6.114 2.367,6.3M4.2,-16.486C4.548,-16.672 4.13,-17.089 3.434,-17.414C2.738,-17.739 2.599,-18.296 2.343,-18.992C2.088,-19.688 1.62,-19.402 1.392,-19.433C0.881,-19.502 1.253,-20.129 0.812,-20.245C0.371,-20.361 0.742,-19.758 0.905,-19.155C1.067,-18.551 0.301,-18.922 0.132,-18.644C-0.038,-18.366 0.881,-18.226 1.438,-18.249C1.995,-18.273 2.274,-17.368 2.274,-17.368L2.297,-16.207C2.297,-16.207 3.225,-15.535 3.62,-15.419C4.014,-15.303 3.527,-16.022 3.364,-16.486C3.202,-16.95 3.852,-16.301 4.2,-16.486M34.539,2.298C34.655,2.402 34.797,2.576 34.911,2.725C35.036,2.888 35.241,2.968 35.443,2.929L35.594,2.901C35.594,2.901 35.85,3.678 36.07,3.69C36.291,3.701 37.334,3.736 37.451,3.458C37.566,3.179 37.984,1.984 37.996,1.52C38.007,1.056 37.659,1.044 37.114,0.418C36.569,-0.209 36.453,-0.475 36.313,-0.731C36.174,-0.986 36.058,-0.974 35.965,-0.87C35.965,-0.87 36.023,0.209 35.814,0.105C35.606,0 35.014,-0.475 35.014,-0.475C35.014,-0.475 35.304,-0.963 34.956,-0.87C34.608,-0.777 34.214,-0.731 34.109,-0.58C34.005,-0.429 33.773,-0.19 33.773,-0.385C33.773,-0.58 33.831,-0.951 33.541,-0.556C33.25,-0.162 33.1,0.267 32.589,0.418C32.079,0.569 31.8,0.673 31.708,0.882C31.615,1.091 31.858,1.45 31.858,1.81C31.858,2.17 31.835,2.878 31.835,2.878C31.835,2.878 32.891,2.901 33.286,2.599C33.68,2.298 34.307,2.089 34.539,2.298M38.032,-13.371C38.01,-13.291 38.006,-13.22 38.031,-13.168C38.11,-12.998 38.216,-12.796 38.305,-12.633C38.217,-12.881 38.127,-13.127 38.032,-13.371M36.743,4.281C36.639,4.223 36.504,4.176 36.511,4.304C36.511,4.304 36.511,4.803 36.673,4.711C36.835,4.618 37.23,4.223 37.068,4.165C36.905,4.107 36.847,4.339 36.743,4.281M3.358,-23.848C2.802,-23.308 3.132,-22.612 3.254,-22.02C3.376,-21.429 4.055,-21.585 4.403,-21.62C4.751,-21.655 5.847,-18.522 6.021,-18.279C6.195,-18.035 5.899,-17.478 5.586,-17.147C5.273,-16.817 6.491,-14.815 6.839,-14.676C7.187,-14.537 7.222,-15.581 7.396,-16.103C7.57,-16.625 7.831,-16.591 8.284,-16.66C8.736,-16.73 8.544,-17.478 8.927,-17.739C9.311,-18 10.232,-18.122 10.372,-18.522C10.511,-18.922 10.059,-19.288 9.833,-19.584C9.606,-19.88 10.494,-20.698 10.553,-21.063C10.612,-21.429 10.563,-21.602 10.372,-21.951C10.181,-22.299 10.772,-23.952 10.772,-23.952C10.772,-23.952 11.486,-25.466 11.625,-25.866C11.748,-26.219 10.118,-27.392 9.429,-27.847C8.016,-27.469 6.654,-26.966 5.356,-26.35C4.705,-25.484 3.719,-24.196 3.358,-23.848M-0.955,-18.78C-1.114,-18.737 -1.282,-18.203 -0.955,-18.157C-0.626,-18.11 -0.449,-18.11 -0.449,-18.11L-0.626,-18.811L-0.955,-18.78ZM-3.742,-18.82C-3.672,-18.639 -3.892,-18.475 -3.713,-18.064C-3.481,-17.53 -2.808,-17.971 -2.32,-17.833C-1.833,-17.695 -2.065,-18.017 -2.205,-18.319C-2.344,-18.621 -2.483,-18.714 -2.344,-18.992C-2.205,-19.27 -2.529,-19.456 -2.854,-19.596C-3.179,-19.735 -3.04,-19.131 -3.04,-19.131C-3.04,-19.131 -3.175,-19.234 -3.337,-19.357C-3.475,-19.18 -3.609,-19.001 -3.742,-18.82M21.077,-0.565C20.923,-0.367 20.734,-0.08 20.721,0.151C20.71,0.336 20.704,0.592 20.701,0.813C20.699,0.966 20.891,1.036 20.989,0.918L21.278,0.569C21.278,0.569 21.765,-0.197 21.718,-0.452C21.71,-0.5 21.689,-0.542 21.661,-0.579C21.516,-0.768 21.223,-0.753 21.077,-0.565M30.965,-1.81C30.768,-1.937 30.768,-1.833 30.594,-1.879C30.42,-1.926 30.199,-2.125 30.106,-2.037C30.014,-1.949 29.909,-1.885 30.048,-1.853C30.188,-1.821 30.432,-1.833 30.605,-1.705C30.779,-1.577 30.977,-1.473 31.127,-1.473C31.278,-1.473 31.429,-1.496 31.232,-1.647C31.232,-1.647 31.162,-1.682 30.965,-1.81M0.139,-22.055C0.232,-21.475 1.044,-22.032 1.461,-21.8C1.879,-21.568 1.717,-22.171 1.717,-22.937C1.717,-23.702 1.972,-23.795 2.645,-24.19C2.969,-24.38 3.249,-24.873 3.456,-25.34C2.546,-24.803 1.674,-24.209 0.847,-23.56C0.849,-23.476 0.854,-23.392 0.858,-23.308C0.881,-22.844 0.046,-22.635 0.139,-22.055M24.271,-21.521C24.549,-21.823 24.253,-22.234 23.76,-21.939C23.577,-21.829 22.624,-21.452 22.252,-20.454C21.881,-19.456 21.603,-19.27 22.09,-18.992C22.577,-18.714 22.832,-18.667 22.67,-19.108C22.507,-19.549 22.206,-19.618 22.786,-20.315C23.366,-21.011 23.992,-21.22 24.271,-21.521M15.778,-0.104C16.034,0.36 16.59,3.214 16.892,3.028C17.194,2.842 19.375,0.986 19.375,0.986C19.375,0.986 19.537,0.035 19.909,-0.174C20.28,-0.383 20.141,-1.821 20.141,-1.821C20.141,-1.821 22.252,-4.443 21.858,-4.64C21.696,-4.722 21.417,-4.808 21.144,-4.882C21.196,-5.577 21.858,-5.371 22.345,-5.464C22.832,-5.557 22.972,-6.276 22.832,-6.485C22.784,-6.558 22.713,-6.515 22.641,-6.429C22.389,-6.126 22.017,-5.951 21.625,-5.903C21.227,-5.853 20.779,-5.755 20.582,-5.557L20.579,-5.554C20.432,-5.407 20.187,-5.441 20.09,-5.625C19.622,-6.518 18.698,-8.171 18.308,-8.086C17.774,-7.97 16.219,-7.622 15.871,-8.179C15.523,-8.736 15.686,-8.991 15.082,-8.991C14.479,-8.991 13.481,-9.094 12.483,-7.877C12.43,-7.813 11.184,-6.532 11.3,-6.114C11.416,-5.696 12.251,-3.678 12.623,-3.724C12.994,-3.77 15.036,-4.374 15.268,-4.002C15.5,-3.631 14.943,-3.167 15.268,-2.842C15.593,-2.517 16.219,-1.914 16.08,-1.45C15.941,-0.986 15.523,-0.568 15.778,-0.104M-0.952,-5.279C-0.511,-5.441 -0.302,-4.954 0.093,-4.931C0.487,-4.908 0.719,-3.817 0.719,-4.281C0.719,-4.745 0.673,-5.348 0.417,-5.418C0.162,-5.488 -0.163,-5.65 -0.046,-5.929C0.069,-6.207 -0.023,-6.16 -0.209,-6.23C-0.395,-6.3 -0.673,-5.743 -0.905,-5.836C-1.137,-5.929 -1.253,-5.905 -1.346,-6.532C-1.439,-7.158 -1.299,-7.46 -0.604,-7.715C0.093,-7.97 1.021,-7.692 1.183,-7.901C1.346,-8.11 3.156,-10.384 3.388,-10.707C3.62,-11.03 2.947,-11.196 3.045,-11.59C3.144,-11.985 4.757,-12.217 4.965,-12.402C5.174,-12.588 3.944,-14.003 3.852,-13.98C3.759,-13.957 3.48,-13.91 3.271,-14.142C3.063,-14.374 3.109,-14.676 2.784,-14.885C2.459,-15.094 1.833,-15.349 1.879,-14.954C1.926,-14.56 2.019,-14.142 2.042,-13.864C2.065,-13.586 1.74,-12.889 1.74,-12.889C1.74,-12.889 1.74,-12.332 1.485,-12.17C1.229,-12.008 1.021,-12.472 0.975,-12.889C0.928,-13.307 -0.604,-13.957 -0.905,-14.421C-1.207,-14.885 0.093,-16.231 0.464,-16.556C0.835,-16.881 0.766,-17.228 0.464,-17.6C0.162,-17.971 -0.835,-17.461 -1.393,-17.252C-1.771,-17.11 -3.229,-17.46 -4.453,-17.794C-5.4,-16.35 -6.195,-14.798 -6.817,-13.16C-6.356,-12.495 -5.916,-11.797 -5.755,-11.613C-5.43,-11.242 -5.731,-10.778 -5.685,-9.989C-5.639,-9.2 -4.873,-8.318 -4.571,-8.04C-4.27,-7.762 -4.061,-6.81 -3.852,-6.787C-3.643,-6.764 -3.991,-7.483 -4.084,-7.854C-4.177,-8.225 -3.55,-7.483 -3.086,-6.972C-2.622,-6.462 -2.808,-6.183 -2.459,-5.929C-2.111,-5.673 -1.393,-5.116 -0.952,-5.279M15.175,-14.769C15.337,-14.142 15.732,-14.491 15.732,-13.818C15.732,-13.145 16.498,-13.493 16.637,-14.073C16.776,-14.653 16.173,-14.723 16.799,-15.488C17.426,-16.254 18.4,-16.834 18.076,-16.37C17.75,-15.906 16.892,-15.14 17.263,-14.792C17.635,-14.444 18.122,-14.398 18.122,-14.398C18.122,-14.398 17.701,-13.493 17.088,-13.238C16.475,-12.982 14.85,-12.727 14.27,-12.193C13.69,-11.66 13.713,-11.59 13.528,-10.847C13.342,-10.105 12.367,-9.363 12.808,-9.27C13.249,-9.177 14.665,-10.012 15.245,-10.384C15.825,-10.755 16.753,-10.291 17.379,-10.151C18.006,-10.012 18.423,-10.987 18.98,-10.917C19.537,-10.847 20.187,-9.78 19.885,-9.687C19.584,-9.595 18.586,-10.082 18.47,-9.641C18.354,-9.2 18.447,-8.806 19.166,-8.875C19.885,-8.945 19.003,-8.063 19.352,-8.133C19.7,-8.202 20.419,-9.27 20.744,-9.2L21.951,-7.344C21.951,-7.344 24.666,-7.158 24.897,-6.648C25.129,-6.137 25.733,-3.863 25.918,-4.141C26.104,-4.42 26.499,-4.815 26.452,-5.209C26.406,-5.603 27.659,-5.952 27.682,-6.323C27.705,-6.694 28.61,-6.091 28.749,-5.998C28.888,-5.905 28.332,-5.72 28.749,-5.534C29.167,-5.348 30.118,-5.418 30.142,-5.047C30.165,-4.675 29.956,-4.118 30.234,-4.304C30.513,-4.49 31,-4.931 30.791,-5.186C30.583,-5.441 30.583,-6.091 30.583,-6.091C30.583,-6.091 32.067,-5.975 32.276,-6.462C32.485,-6.95 33.088,-7.599 32.81,-8.063C32.531,-8.527 32.276,-9.2 32.276,-9.2L33.784,-9.827L35.177,-10.499C35.177,-10.499 36.36,-12.704 35.873,-12.82C35.385,-12.936 34.968,-12.912 35.107,-13.191C35.246,-13.47 35.965,-14.444 36.198,-14.351C36.373,-14.281 37.168,-14.238 37.628,-14.34C36.882,-16.027 35.949,-17.613 34.853,-19.069C34.756,-19.03 34.669,-18.975 34.597,-18.899C34.132,-18.412 33.808,-19.247 33.297,-19.549C32.787,-19.85 31.766,-20.129 31.278,-19.967C30.791,-19.804 30.977,-20.523 30.977,-20.523L31.023,-22.055C31.023,-22.055 26.962,-20.825 26.684,-20.199C26.406,-19.572 25.478,-18.226 25.478,-18.226C25.478,-18.226 25.373,-18.218 25.198,-18.199C25.151,-18.356 25.085,-18.516 24.99,-18.667C24.596,-19.294 25.129,-19.178 24.92,-19.711C24.712,-20.245 24.666,-19.596 24.317,-19.108C23.99,-18.65 24.401,-18.663 24.471,-18.107C23.6,-17.979 22.365,-17.738 21.603,-17.321C20.326,-16.625 19.537,-16.486 19.305,-16.95C19.073,-17.414 19.816,-16.927 19.955,-17.113C20.094,-17.298 19.816,-17.716 19.421,-17.971C19.027,-18.226 17.701,-18.412 17.088,-18.017C16.475,-17.623 16.521,-17.182 15.895,-16.231C15.268,-15.28 15.013,-15.395 15.175,-14.769M31.687,-2.297C31.731,-2.564 31.743,-2.738 31.928,-2.807C32.114,-2.877 32.288,-2.906 32.125,-2.978C31.963,-3.051 31.858,-3.121 31.905,-3.237C31.951,-3.353 32.229,-3.422 32.125,-3.492C32.021,-3.561 31.986,-3.527 31.905,-3.631C31.824,-3.736 31.687,-3.631 31.687,-3.631C31.685,-3.585 31.406,-3.515 31.278,-3.318C31.151,-3.121 30.872,-3.028 30.872,-3.028C30.872,-3.028 30.594,-3.248 30.559,-3.098C30.524,-2.947 30.559,-2.39 30.745,-2.344C30.93,-2.297 31.069,-2.308 31.232,-2.227C31.395,-2.146 31.642,-2.03 31.687,-2.297M29.085,-3.608C28.993,-3.782 28.989,-3.851 28.792,-3.863C28.595,-3.875 28.575,-3.91 28.494,-3.945C28.413,-3.979 28.563,-3.805 28.714,-3.597C28.865,-3.388 28.854,-3.446 29.004,-3.411C29.004,-3.411 29.178,-3.434 29.085,-3.608M29.283,-2.587C29.387,-2.308 29.562,-2.221 29.701,-2.131C29.84,-2.042 29.886,-1.926 29.921,-2.262C29.956,-2.598 29.898,-2.506 29.782,-2.668C29.666,-2.831 29.805,-2.836 29.643,-2.978C29.48,-3.121 29.562,-3.075 29.341,-3.121C29.121,-3.167 28.958,-3.156 29.016,-3.028C29.016,-3.028 29.178,-2.866 29.283,-2.587M34.701,-8.179C34.701,-8.179 35.095,-8.411 35.49,-8.469C35.884,-8.527 36.07,-8.457 36.035,-8.736C36,-9.014 35.861,-9.142 35.861,-9.142L35.536,-9.096C35.49,-9.073 35.525,-9.003 35.107,-8.806C34.689,-8.608 34.411,-8.666 34.376,-8.434C34.341,-8.202 34.701,-8.179 34.701,-8.179M32.67,-2.591C32.856,-2.514 33.111,-2.854 33.216,-2.97C33.32,-3.086 33.135,-3.075 32.938,-2.904C32.74,-2.734 32.589,-2.904 32.589,-2.904C32.394,-2.978 32.102,-2.494 32.125,-2.262C32.148,-2.03 32.044,-2.088 31.975,-2.037C31.905,-1.986 32.009,-1.914 32.19,-1.984C32.37,-2.054 32.427,-1.96 32.555,-1.937C32.682,-1.914 32.427,-2.239 32.543,-2.262C32.659,-2.285 32.578,-2.401 32.473,-2.483C32.369,-2.564 32.485,-2.668 32.67,-2.591M35.745,-1.473C35.745,-1.473 35.594,-1.415 35.455,-1.531C35.316,-1.647 35.13,-1.589 34.91,-1.577C34.689,-1.566 34.968,-1.728 35.014,-1.936C35.061,-2.144 34.597,-2.077 34.399,-2.131C34.202,-2.185 34.225,-2.216 34.202,-2.39C34.179,-2.564 34.074,-2.61 33.97,-2.715C33.866,-2.819 34.121,-2.773 34.295,-2.749C34.469,-2.726 34.341,-2.587 34.504,-2.413C34.666,-2.239 34.832,-2.558 34.91,-2.591C35.084,-2.665 35.49,-2.378 35.745,-2.32C36,-2.262 35.873,-2.158 35.85,-1.984C35.826,-1.81 36.372,-1.67 36.464,-1.601C36.557,-1.531 36.209,-1.566 35.954,-1.636C35.699,-1.705 35.745,-1.473 35.745,-1.473" style="fill:rgb(74,190,255);fill-rule:nonzero;"/>';
		$source .= '</g>';
		$source .= '</g>';
		$source .= '</g>';
		$source .= '</g>';
		$source .= '<g transform="matrix(0,72.0467,72.0467,0,25,-8.19368)"><circle cx="0.461" cy="0" r="0.264" style="fill:url(#_Linear3);"/></g>';
		$source .= '<g>';
		$source .= '<clipPath id="_clip4"><path d="M6,25C6,35.494 14.506,44 25,44C35.493,44 44,35.494 44,25C44,14.507 35.493,6 25,6C14.506,6 6,14.507 6,25Z" clip-rule="nonzero"/></clipPath>';
		$source .= '<g clip-path="url(#_clip4)">';
		$source .= '<g opacity="0.5">';
		$source .= '<g transform="matrix(1,0,0,1,9.1582,34.638)">';
		$source .= '<path d="M0,-20.41C0.325,-20.5 0.487,-20.802 0.812,-20.779C1.137,-20.756 1.276,-20.825 1.183,-21.08C1.09,-21.336 0.789,-21.753 0.58,-21.452C0.371,-21.15 0.023,-20.871 -0.255,-21.127C-0.534,-21.382 -0.464,-21.629 -0.65,-21.749C-0.835,-21.869 -0.882,-21.749 -0.882,-21.749C-0.928,-21.66 -1.091,-20.941 -0.696,-20.802C-0.302,-20.663 -0.325,-20.32 0,-20.41M-0.279,-19.177C0,-19.085 0.294,-18.688 0.263,-19.177C0.232,-19.665 0.526,-19.943 0.263,-20.059C0,-20.176 -0.07,-19.864 -0.07,-19.864C-0.139,-19.827 -0.557,-19.268 -0.279,-19.177M-1.652,-19.943C-1.771,-19.802 -1.838,-19.65 -1.694,-19.572C-1.393,-19.41 -1.213,-19.596 -0.955,-19.596C-0.696,-19.596 -0.534,-19.529 -0.604,-19.864C-0.625,-19.964 -0.695,-20.04 -0.785,-20.097C-1.065,-20.276 -1.439,-20.2 -1.652,-19.943M2.367,6.3C2.506,6.486 3.179,7.437 3.156,7.112C3.132,6.787 3.944,4.374 3.944,4.374C3.944,4.374 5.337,3.562 5.337,3.214C5.337,2.866 6.357,1.172 6.775,0.986C7.193,0.801 7.75,0.754 7.727,0.058C7.704,-0.638 8.817,-1.473 8.446,-1.914C8.075,-2.355 6.265,-2.169 6.125,-2.657C5.986,-3.144 6.288,-3.399 5.731,-3.585C5.174,-3.77 3.62,-4.443 3.249,-4.374C2.877,-4.304 2.738,-4.768 2.459,-4.606C2.181,-4.443 1.671,-4.011 1.787,-3.585C1.787,-3.585 2.065,-3.167 1.787,-3.005C1.508,-2.842 0.975,-2.494 1.207,-2.054C1.438,-1.613 1.81,-0.522 2.065,-0.499C2.32,-0.475 2.993,-0.104 2.923,0.267C2.854,0.638 2.228,6.114 2.367,6.3M4.2,-16.486C4.548,-16.672 4.13,-17.089 3.434,-17.414C2.738,-17.739 2.599,-18.296 2.343,-18.992C2.088,-19.688 1.62,-19.402 1.392,-19.433C0.881,-19.502 1.253,-20.129 0.812,-20.245C0.371,-20.361 0.742,-19.758 0.905,-19.155C1.067,-18.551 0.301,-18.922 0.132,-18.644C-0.038,-18.366 0.881,-18.226 1.438,-18.249C1.995,-18.273 2.274,-17.368 2.274,-17.368L2.297,-16.207C2.297,-16.207 3.225,-15.535 3.62,-15.419C4.014,-15.303 3.527,-16.022 3.364,-16.486C3.202,-16.95 3.852,-16.301 4.2,-16.486M34.539,2.298C34.655,2.402 34.797,2.576 34.911,2.725C35.036,2.888 35.241,2.968 35.443,2.929L35.594,2.901C35.594,2.901 35.85,3.678 36.07,3.69C36.291,3.701 37.334,3.736 37.451,3.458C37.566,3.179 37.984,1.984 37.996,1.52C38.007,1.056 37.659,1.044 37.114,0.418C36.569,-0.209 36.453,-0.475 36.313,-0.731C36.174,-0.986 36.058,-0.974 35.965,-0.87C35.965,-0.87 36.023,0.209 35.814,0.105C35.606,0 35.014,-0.475 35.014,-0.475C35.014,-0.475 35.304,-0.963 34.956,-0.87C34.608,-0.777 34.214,-0.731 34.109,-0.58C34.005,-0.429 33.773,-0.19 33.773,-0.385C33.773,-0.58 33.831,-0.951 33.541,-0.556C33.25,-0.162 33.1,0.267 32.589,0.418C32.079,0.569 31.8,0.673 31.708,0.882C31.615,1.091 31.858,1.45 31.858,1.81C31.858,2.17 31.835,2.878 31.835,2.878C31.835,2.878 32.891,2.901 33.286,2.599C33.68,2.298 34.307,2.089 34.539,2.298M38.032,-13.371C38.01,-13.291 38.006,-13.22 38.031,-13.168C38.11,-12.998 38.216,-12.796 38.305,-12.633C38.217,-12.881 38.127,-13.127 38.032,-13.371M36.743,4.281C36.639,4.223 36.504,4.176 36.511,4.304C36.511,4.304 36.511,4.803 36.673,4.711C36.835,4.618 37.23,4.223 37.068,4.165C36.905,4.107 36.847,4.339 36.743,4.281M3.358,-23.848C2.802,-23.308 3.132,-22.612 3.254,-22.02C3.376,-21.429 4.055,-21.585 4.403,-21.62C4.751,-21.655 5.847,-18.522 6.021,-18.279C6.195,-18.035 5.899,-17.478 5.586,-17.147C5.273,-16.817 6.491,-14.815 6.839,-14.676C7.187,-14.537 7.222,-15.581 7.396,-16.103C7.57,-16.625 7.831,-16.591 8.284,-16.66C8.736,-16.73 8.544,-17.478 8.927,-17.739C9.311,-18 10.232,-18.122 10.372,-18.522C10.511,-18.922 10.059,-19.288 9.833,-19.584C9.606,-19.88 10.494,-20.698 10.553,-21.063C10.612,-21.429 10.563,-21.602 10.372,-21.951C10.181,-22.299 10.772,-23.952 10.772,-23.952C10.772,-23.952 11.486,-25.466 11.625,-25.866C11.748,-26.219 10.118,-27.392 9.429,-27.847C8.016,-27.469 6.654,-26.966 5.356,-26.35C4.705,-25.484 3.719,-24.196 3.358,-23.848M-0.955,-18.78C-1.114,-18.737 -1.282,-18.203 -0.955,-18.157C-0.626,-18.11 -0.449,-18.11 -0.449,-18.11L-0.626,-18.811L-0.955,-18.78ZM-3.742,-18.82C-3.672,-18.639 -3.892,-18.475 -3.713,-18.064C-3.481,-17.53 -2.808,-17.971 -2.32,-17.833C-1.833,-17.695 -2.065,-18.017 -2.205,-18.319C-2.344,-18.621 -2.483,-18.714 -2.344,-18.992C-2.205,-19.27 -2.529,-19.456 -2.854,-19.596C-3.179,-19.735 -3.04,-19.131 -3.04,-19.131C-3.04,-19.131 -3.175,-19.234 -3.337,-19.357C-3.475,-19.18 -3.609,-19.001 -3.742,-18.82M21.077,-0.565C20.923,-0.367 20.734,-0.08 20.721,0.151C20.71,0.336 20.704,0.592 20.701,0.813C20.699,0.966 20.891,1.036 20.989,0.918L21.278,0.569C21.278,0.569 21.765,-0.197 21.718,-0.452C21.71,-0.5 21.689,-0.542 21.661,-0.579C21.516,-0.768 21.223,-0.753 21.077,-0.565M30.965,-1.81C30.768,-1.937 30.768,-1.833 30.594,-1.879C30.42,-1.926 30.199,-2.125 30.106,-2.037C30.014,-1.949 29.909,-1.885 30.048,-1.853C30.188,-1.821 30.432,-1.833 30.605,-1.705C30.779,-1.577 30.977,-1.473 31.127,-1.473C31.278,-1.473 31.429,-1.496 31.232,-1.647C31.232,-1.647 31.162,-1.682 30.965,-1.81M0.139,-22.055C0.232,-21.475 1.044,-22.032 1.461,-21.8C1.879,-21.568 1.717,-22.171 1.717,-22.937C1.717,-23.702 1.972,-23.795 2.645,-24.19C2.969,-24.38 3.249,-24.873 3.456,-25.34C2.546,-24.803 1.674,-24.209 0.847,-23.56C0.849,-23.476 0.854,-23.392 0.858,-23.308C0.881,-22.844 0.046,-22.635 0.139,-22.055M24.271,-21.521C24.549,-21.823 24.253,-22.234 23.76,-21.939C23.577,-21.829 22.624,-21.452 22.252,-20.454C21.881,-19.456 21.603,-19.27 22.09,-18.992C22.577,-18.714 22.832,-18.667 22.67,-19.108C22.507,-19.549 22.206,-19.618 22.786,-20.315C23.366,-21.011 23.992,-21.22 24.271,-21.521M15.778,-0.104C16.034,0.36 16.59,3.214 16.892,3.028C17.194,2.842 19.375,0.986 19.375,0.986C19.375,0.986 19.537,0.035 19.909,-0.174C20.28,-0.383 20.141,-1.821 20.141,-1.821C20.141,-1.821 22.252,-4.443 21.858,-4.64C21.696,-4.722 21.417,-4.808 21.144,-4.882C21.196,-5.577 21.858,-5.371 22.345,-5.464C22.832,-5.557 22.972,-6.276 22.832,-6.485C22.784,-6.558 22.713,-6.515 22.641,-6.429C22.389,-6.126 22.017,-5.951 21.625,-5.903C21.227,-5.853 20.779,-5.755 20.582,-5.557L20.579,-5.554C20.432,-5.407 20.187,-5.441 20.09,-5.625C19.622,-6.518 18.698,-8.171 18.308,-8.086C17.774,-7.97 16.219,-7.622 15.871,-8.179C15.523,-8.736 15.686,-8.991 15.082,-8.991C14.479,-8.991 13.481,-9.094 12.483,-7.877C12.43,-7.813 11.184,-6.532 11.3,-6.114C11.416,-5.696 12.251,-3.678 12.623,-3.724C12.994,-3.77 15.036,-4.374 15.268,-4.002C15.5,-3.631 14.943,-3.167 15.268,-2.842C15.593,-2.517 16.219,-1.914 16.08,-1.45C15.941,-0.986 15.523,-0.568 15.778,-0.104M-0.952,-5.279C-0.511,-5.441 -0.302,-4.954 0.093,-4.931C0.487,-4.908 0.719,-3.817 0.719,-4.281C0.719,-4.745 0.673,-5.348 0.417,-5.418C0.162,-5.488 -0.163,-5.65 -0.046,-5.929C0.069,-6.207 -0.023,-6.16 -0.209,-6.23C-0.395,-6.3 -0.673,-5.743 -0.905,-5.836C-1.137,-5.929 -1.253,-5.905 -1.346,-6.532C-1.439,-7.158 -1.299,-7.46 -0.604,-7.715C0.093,-7.97 1.021,-7.692 1.183,-7.901C1.346,-8.11 3.156,-10.384 3.388,-10.707C3.62,-11.03 2.947,-11.196 3.045,-11.59C3.144,-11.985 4.757,-12.217 4.965,-12.402C5.174,-12.588 3.944,-14.003 3.852,-13.98C3.759,-13.957 3.48,-13.91 3.271,-14.142C3.063,-14.374 3.109,-14.676 2.784,-14.885C2.459,-15.094 1.833,-15.349 1.879,-14.954C1.926,-14.56 2.019,-14.142 2.042,-13.864C2.065,-13.586 1.74,-12.889 1.74,-12.889C1.74,-12.889 1.74,-12.332 1.485,-12.17C1.229,-12.008 1.021,-12.472 0.975,-12.889C0.928,-13.307 -0.604,-13.957 -0.905,-14.421C-1.207,-14.885 0.093,-16.231 0.464,-16.556C0.835,-16.881 0.766,-17.228 0.464,-17.6C0.162,-17.971 -0.835,-17.461 -1.393,-17.252C-1.771,-17.11 -3.229,-17.46 -4.453,-17.794C-5.4,-16.35 -6.195,-14.798 -6.817,-13.16C-6.356,-12.495 -5.916,-11.797 -5.755,-11.613C-5.43,-11.242 -5.731,-10.778 -5.685,-9.989C-5.639,-9.2 -4.873,-8.318 -4.571,-8.04C-4.27,-7.762 -4.061,-6.81 -3.852,-6.787C-3.643,-6.764 -3.991,-7.483 -4.084,-7.854C-4.177,-8.225 -3.55,-7.483 -3.086,-6.972C-2.622,-6.462 -2.808,-6.183 -2.459,-5.929C-2.111,-5.673 -1.393,-5.116 -0.952,-5.279M15.175,-14.769C15.337,-14.142 15.732,-14.491 15.732,-13.818C15.732,-13.145 16.498,-13.493 16.637,-14.073C16.776,-14.653 16.173,-14.723 16.799,-15.488C17.426,-16.254 18.4,-16.834 18.076,-16.37C17.75,-15.906 16.892,-15.14 17.263,-14.792C17.635,-14.444 18.122,-14.398 18.122,-14.398C18.122,-14.398 17.701,-13.493 17.088,-13.238C16.475,-12.982 14.85,-12.727 14.27,-12.193C13.69,-11.66 13.713,-11.59 13.528,-10.847C13.342,-10.105 12.367,-9.363 12.808,-9.27C13.249,-9.177 14.665,-10.012 15.245,-10.384C15.825,-10.755 16.753,-10.291 17.379,-10.151C18.006,-10.012 18.423,-10.987 18.98,-10.917C19.537,-10.847 20.187,-9.78 19.885,-9.687C19.584,-9.595 18.586,-10.082 18.47,-9.641C18.354,-9.2 18.447,-8.806 19.166,-8.875C19.885,-8.945 19.003,-8.063 19.352,-8.133C19.7,-8.202 20.419,-9.27 20.744,-9.2L21.951,-7.344C21.951,-7.344 24.666,-7.158 24.897,-6.648C25.129,-6.137 25.733,-3.863 25.918,-4.141C26.104,-4.42 26.499,-4.815 26.452,-5.209C26.406,-5.603 27.659,-5.952 27.682,-6.323C27.705,-6.694 28.61,-6.091 28.749,-5.998C28.888,-5.905 28.332,-5.72 28.749,-5.534C29.167,-5.348 30.118,-5.418 30.142,-5.047C30.165,-4.675 29.956,-4.118 30.234,-4.304C30.513,-4.49 31,-4.931 30.791,-5.186C30.583,-5.441 30.583,-6.091 30.583,-6.091C30.583,-6.091 32.067,-5.975 32.276,-6.462C32.485,-6.95 33.088,-7.599 32.81,-8.063C32.531,-8.527 32.276,-9.2 32.276,-9.2L33.784,-9.827L35.177,-10.499C35.177,-10.499 36.36,-12.704 35.873,-12.82C35.385,-12.936 34.968,-12.912 35.107,-13.191C35.246,-13.47 35.965,-14.444 36.198,-14.351C36.373,-14.281 37.168,-14.238 37.628,-14.34C36.882,-16.027 35.949,-17.613 34.853,-19.069C34.756,-19.03 34.669,-18.975 34.597,-18.899C34.132,-18.412 33.808,-19.247 33.297,-19.549C32.787,-19.85 31.766,-20.129 31.278,-19.967C30.791,-19.804 30.977,-20.523 30.977,-20.523L31.023,-22.055C31.023,-22.055 26.962,-20.825 26.684,-20.199C26.406,-19.572 25.478,-18.226 25.478,-18.226C25.478,-18.226 25.373,-18.218 25.198,-18.199C25.151,-18.356 25.085,-18.516 24.99,-18.667C24.596,-19.294 25.129,-19.178 24.92,-19.711C24.712,-20.245 24.666,-19.596 24.317,-19.108C23.99,-18.65 24.401,-18.663 24.471,-18.107C23.6,-17.979 22.365,-17.738 21.603,-17.321C20.326,-16.625 19.537,-16.486 19.305,-16.95C19.073,-17.414 19.816,-16.927 19.955,-17.113C20.094,-17.298 19.816,-17.716 19.421,-17.971C19.027,-18.226 17.701,-18.412 17.088,-18.017C16.475,-17.623 16.521,-17.182 15.895,-16.231C15.268,-15.28 15.013,-15.395 15.175,-14.769M31.687,-2.297C31.731,-2.564 31.743,-2.738 31.928,-2.807C32.114,-2.877 32.288,-2.906 32.125,-2.978C31.963,-3.051 31.858,-3.121 31.905,-3.237C31.951,-3.353 32.229,-3.422 32.125,-3.492C32.021,-3.561 31.986,-3.527 31.905,-3.631C31.824,-3.736 31.687,-3.631 31.687,-3.631C31.685,-3.585 31.406,-3.515 31.278,-3.318C31.151,-3.121 30.872,-3.028 30.872,-3.028C30.872,-3.028 30.594,-3.248 30.559,-3.098C30.524,-2.947 30.559,-2.39 30.745,-2.344C30.93,-2.297 31.069,-2.308 31.232,-2.227C31.395,-2.146 31.642,-2.03 31.687,-2.297M29.085,-3.608C28.993,-3.782 28.989,-3.851 28.792,-3.863C28.595,-3.875 28.575,-3.91 28.494,-3.945C28.413,-3.979 28.563,-3.805 28.714,-3.597C28.865,-3.388 28.854,-3.446 29.004,-3.411C29.004,-3.411 29.178,-3.434 29.085,-3.608M29.283,-2.587C29.387,-2.308 29.562,-2.221 29.701,-2.131C29.84,-2.042 29.886,-1.926 29.921,-2.262C29.956,-2.598 29.898,-2.506 29.782,-2.668C29.666,-2.831 29.805,-2.836 29.643,-2.978C29.48,-3.121 29.562,-3.075 29.341,-3.121C29.121,-3.167 28.958,-3.156 29.016,-3.028C29.016,-3.028 29.178,-2.866 29.283,-2.587M34.701,-8.179C34.701,-8.179 35.095,-8.411 35.49,-8.469C35.884,-8.527 36.07,-8.457 36.035,-8.736C36,-9.014 35.861,-9.142 35.861,-9.142L35.536,-9.096C35.49,-9.073 35.525,-9.003 35.107,-8.806C34.689,-8.608 34.411,-8.666 34.376,-8.434C34.341,-8.202 34.701,-8.179 34.701,-8.179M32.67,-2.591C32.856,-2.514 33.111,-2.854 33.216,-2.97C33.32,-3.086 33.135,-3.075 32.938,-2.904C32.74,-2.734 32.589,-2.904 32.589,-2.904C32.394,-2.978 32.102,-2.494 32.125,-2.262C32.148,-2.03 32.044,-2.088 31.975,-2.037C31.905,-1.986 32.009,-1.914 32.19,-1.984C32.37,-2.054 32.427,-1.96 32.555,-1.937C32.682,-1.914 32.427,-2.239 32.543,-2.262C32.659,-2.285 32.578,-2.401 32.473,-2.483C32.369,-2.564 32.485,-2.668 32.67,-2.591M35.745,-1.473C35.745,-1.473 35.594,-1.415 35.455,-1.531C35.316,-1.647 35.13,-1.589 34.91,-1.577C34.689,-1.566 34.968,-1.728 35.014,-1.936C35.061,-2.144 34.597,-2.077 34.399,-2.131C34.202,-2.185 34.225,-2.216 34.202,-2.39C34.179,-2.564 34.074,-2.61 33.97,-2.715C33.866,-2.819 34.121,-2.773 34.295,-2.749C34.469,-2.726 34.341,-2.587 34.504,-2.413C34.666,-2.239 34.832,-2.558 34.91,-2.591C35.084,-2.665 35.49,-2.378 35.745,-2.32C36,-2.262 35.873,-2.158 35.85,-1.984C35.826,-1.81 36.372,-1.67 36.464,-1.601C36.557,-1.531 36.209,-1.566 35.954,-1.636C35.699,-1.705 35.745,-1.473 35.745,-1.473" style="fill:rgb(74,190,255);fill-rule:nonzero;"/>';
		$source .= '</g>';
		$source .= '</g>';
		$source .= '</g>';
		$source .= '</g>';
		$source .= '</g>';
		$source .= '<g id="Zoom" transform="matrix(1.86822,-1.33884,1.33884,1.86822,0.441197,51.233)">';
		$source .= '<g opacity="0.5"><g transform="matrix(-0.0212141,-0.999775,-0.999775,0.0212141,21.7026,12.0063)"><path d="M-6.999,-7.149C-10.864,-7.149 -13.999,-4.014 -13.999,-0.149C-13.999,3.717 -10.864,6.852 -6.999,6.852C-3.133,6.852 0.002,3.717 0.002,-0.149C0.002,-4.015 -3.133,-7.149 -6.999,-7.149" style="fill:rgb(255,195,33);fill-rule:nonzero;"/></g></g>';
		$source .= '<g transform="matrix(0.0211723,0.999776,0.999776,-0.0211723,23.5701,27.1095)"><path d="M-1.988,-2.031L2.074,-2.031" style="fill:none;fill-rule:nonzero;stroke:rgb(255,216,111);stroke-width:0.2px;"/></g>';
		$source .= '<g transform="matrix(1,0,0,1,21.7788,28.163)"><path d="M0,10.234C-0.639,10.247 -1.167,9.741 -1.181,9.102L-1.349,1.181C-1.362,0.543 -0.855,0.014 -0.217,0C0.421,-0.013 0.95,0.493 0.963,1.132L1.131,9.053C1.145,9.692 0.638,10.22 0,10.234" style="fill:rgb(237,246,253);fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(-4.90815,0.659563,0.659563,4.90815,24.469,32.9048)"><path d="M0.454,-1.027C0.583,-1.042 0.698,-0.949 0.713,-0.821L0.892,0.768C0.907,0.897 0.814,1.012 0.686,1.027C0.558,1.041 0.442,0.949 0.428,0.821L0.249,-0.769C0.234,-0.895 0.324,-1.01 0.449,-1.027C0.451,-1.027 0.453,-1.027 0.454,-1.027Z" style="fill:url(#_Linear5);fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(-0.0212141,-0.999775,-0.999775,0.0212141,21.7026,12.0063)"><circle cx="-6.999" cy="-0.148" r="7" style="fill:none;stroke:rgb(255,187,97);stroke-width:0.32px;"/></g>';
		$source .= '</g>';
		$source .= '</g>';
		$source .= '<defs>';
		$source .= '<linearGradient id="_Linear1" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,0)"><stop offset="0" style="stop-color:rgb(248,247,252);stop-opacity:1"/><stop offset="0.08" style="stop-color:rgb(248,247,252);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
		$source .= '<linearGradient id="_Linear3" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,0)"><stop offset="0" style="stop-color:rgb(248,247,252);stop-opacity:1"/><stop offset="0.08" style="stop-color:rgb(248,247,252);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
		$source .= '<linearGradient id="_Linear5" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,-0.000187447)"><stop offset="0" style="stop-color:rgb(255,216,111);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(255,147,8);stop-opacity:1"/></linearGradient>';
		$source .= '</defs>';
		$source .= '</svg>';
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

}
