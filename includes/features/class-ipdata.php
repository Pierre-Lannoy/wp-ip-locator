<?php
/**
 * IP data
 *
 * Handles all ip data processes.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator\Plugin\Feature;

use IPLocator\System\Cache;
use IPLocator\System\Logger;
use IPLocator\System\Option;
use function GuzzleHttp\Psr7\str;

/**
 * Define the ip data functionality.
 *
 * Handles all ip data processes.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class IPData {

	/**
	 * Initialization state.
	 *
	 * @since  1.0.0
	 * @var    boolean    $initialized    Maintain the initialization state.
	 */
	private static $initialized = false;

	/**
	 * Init the class.
	 *
	 * @param boolean $hooked   Optional. Add hook for mod_rewrite_rules filter.
	 * @since    1.0.0
	 */
	public static function init( $hooked = false ) {
		if ( $hooked ) {
			add_filter( 'mod_rewrite_rules', [ self::class, 'modify_rules' ], 10, 1 );
		}
		self::$initialized = true;
	}

	/**
	 * Shutdown (de-init) the class.
	 *
	 * @since    1.0.0
	 */
	public static function shutdown() {
		self::$initialized = false;
	}

	/**
	 * Acquire a file.
	 *
	 * @param string $url The url to retrieve.
	 * @param string $md5 The url of the md5.
	 * @return bool|string  False if there's an error, file name otherwise.
	 * @since    1.0.0
	 */
	public static function acquire( $url, $md5 ) {
		global $wp_filesystem;
		$result  = false;
		$zipfile = '';
		$md5file = '';
		try {
			$zipfile = download_url( $url );
			Logger::debug( 'GZ file: ' . $zipfile );
		} catch ( \Throwable $e ) {
			Logger::warning( sprintf( 'Unable to download IP data file: %s.', $e->getMessage() ), $e->getCode() );
		}
		try {
			$md5file = download_url( $md5 );
			Logger::debug( 'MD5 file: ' . $md5file );
		} catch ( \Throwable $e ) {
			Logger::warning( sprintf( 'Unable to download IP data signature: %s.', $e->getMessage() ), $e->getCode() );
		}
		if ( $wp_filesystem->exists( $zipfile ) && $wp_filesystem->exists( $md5file ) ) {
			$unzipfile = get_temp_dir() . '/' . wp_unique_filename( get_temp_dir(), basename( $url ) . '.csv' );
			if ( $wp_filesystem->exists( $unzipfile ) ) {
				$wp_filesystem->delete( $unzipfile );
			}
			try {
				unzip_file( $zipfile, $unzipfile );
				Logger::debug( 'CSV file: ' . $unzipfile );
				// phpcs:ignore
				$md5 = $wp_filesystem->get_contents( $md5file );
				if ( true === verify_file_md5( $unzipfile, $md5 ) ) {
					Logger::debug( 'IP data file signature verified.' );
					$result = $unzipfile;
				} else {
					if ( 65 < strlen( $md5) ) {
						Logger::warning( 'Unable to download IP data file: quota exceeded.' );
					} else {
						Logger::warning( 'Unable to verify the IP data file signature.' );
					}

				}
			} catch ( \Throwable $e ) {
				Logger::warning( sprintf( 'Unable to decompress IP data file: %s.', $e->getMessage() ), $e->getCode() );
			}
		} else {
			Logger::warning( 'Unable to acquire IP data files.' );
		}
		if ( $wp_filesystem->exists( $zipfile ) ) {
			$wp_filesystem->delete( $zipfile );
		}
		if ( $wp_filesystem->exists( $md5file ) ) {
			$wp_filesystem->delete( $md5file );
		}
		return $result;
	}

	/**
	 * Init IPV4 data.
	 *
	 * @since 1.0.0
	 */
	public static function init_v4() {
		global $wp_filesystem;
		if ( is_null( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		Cache::init();
		Cache::set( 'update/v4/initsemaphore', time(), 'infinite' );
		$file = self::acquire( 'https://software77.net/geo-ip/?DL=1', 'https://software77.net/geo-ip/?DL=3' );
		if ( $wp_filesystem->exists( $file ) ) {
			$data  = $wp_filesystem->get_contents_array( $file );
			$cpt   = 0;
			$clean = [ '"' ];
			if ( 0 < count( $data ) ) {
				foreach ( $data as $datum ) {
					if ( is_string( $datum ) && 0 < strlen( $datum ) ) {
						$first = substr( $datum, 0, 1 );
						if ( '#' !== $first && '\n' !== $first ) {
							$tmp = explode( ',', rtrim( $datum ) );
							if ( 4 < count( $tmp ) ) {
								$from = long2ip( (int) str_replace( $clean, '', $tmp[0] ) );
								$to   = long2ip( (int) str_replace( $clean, '', $tmp[1] ) );
								$cc   = str_replace( $clean, '', $tmp[4] );
							}
						}
					}
				}
			} else {
				Logger::warning( 'The IP data file is empty.' );
			}
			// phpcs:ignore
			//$wp_filesystem->delete( $file );
		}
		Cache::set( 'update/v4/initsemaphore', -1, 'infinite' );
	}

	/**
	 * Init IPV6 data.
	 *
	 * @since 1.0.0
	 */
	public static function init_v6() {

	}

	/**
	 * Update IPV4 data.
	 *
	 * @since 1.0.0
	 */
	public static function update_v4() {

	}

	/**
	 * Update IPV6 data.
	 *
	 * @since 1.0.0
	 */
	public static function update_v6() {

	}

}
