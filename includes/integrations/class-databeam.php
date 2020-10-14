<?php
/**
 * DataBeam integration
 *
 * Handles all DataBeam integration and queries.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.0.0
 */

namespace IPLocator\Plugin\Integration;

use IPLocator\System\Option;
use IPLocator\System\Role;
use IPLocator\Plugin\Core;

/**
 * Define the DataBeam integration.
 *
 * Handles all DataBeam integration and queries.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.1.1
 */
class Databeam {

	/**
	 * Init the class.
	 *
	 * @since    2.0.0
	 */
	public static function init() {
		add_filter( 'databeam_source_register', [ static::class, 'register_kpi' ] );
	}

	/**
	 * Register APCu kpis endpoints for DataBeam.
	 *
	 * @param   array   $integrations   The already registered integrations.
	 * @return  array   The new integrations.
	 * @since    2.0.0
	 */
	public static function register_kpi( $integrations ) {
		$integrations[ IPLOCATOR_SLUG . '::kpi' ] = [
			'name'         => IPLOCATOR_PRODUCT_NAME,
			'version'      => IPLOCATOR_VERSION,
			'subname'      => __( 'KPIs', 'ip-locator' ),
			'description'  => __( 'Allows to integrate, as a DataBeam source, all KPIs related to location and languages ip-based detection.', 'ip-locator' ),
			'instruction'  => __( 'Just add this and use it as source in your favorite visualizers and publishers.', 'ip-locator' ),
			'note'         => __( 'In multisite environments, this source is available for the whole network or per site.', 'ip-locator' ),
			'legal'        =>
				[
					'author'  => 'Pierre Lannoy',
					'url'     => 'https://github.com/Pierre-Lannoy',
					'license' => 'gpl3',
				],
			'icon'         =>
				[
					'static' => [
						'class'  => '\IPLocator\Plugin\Core',
						'method' => 'get_base64_logo',
					],
				],
			'type'         => 'collection::kpi',
			'restrictions' => [ ],
			'ttl'          => '0-3600:300',
			'caching'      => [ 'locale' ],
			'data_call'    =>
				[
					'static' => [
						'class'  => '\IPLocator\Plugin\Feature\Analytics',
						'method' => 'get_status_kpi_collection',
					],
				],
			'data_args'    =>
				[
					'site' => [
						'name' => __( 'Site scope', 'ip-locator' ),
						'type' => 'site_id',
				]
			],
		];
		return $integrations;
	}

	/**
	 * Returns a base64 svg resource for the banner.
	 *
	 * @return string The svg resource as a base64.
	 * @since 2.0.0
	 */
	public static function get_base64_banner() {
		$filename = __DIR__ . '/banner.svg';
		if ( file_exists( $filename ) ) {
			// phpcs:ignore
			$content = @file_get_contents( $filename );
		} else {
			$content = '';
		}
		if ( $content ) {
			// phpcs:ignore
			return 'data:image/svg+xml;base64,' . base64_encode( $content );
		}
		return '';
	}

	/**
	 * Register server infos endpoints for DataBeam.
	 *
	 * @param   array   $integrations   The already registered integrations.
	 * @return  array   The new integrations.
	 * @since    2.0.0
	 */
	public static function register_info( $integrations ) {
		return $integrations;
	}

}
