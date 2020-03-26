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
use IPLocator\System\Infolog;
use IPLocator\Plugin\Feature\Schema;

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
	 * Insert batch size.
	 *
	 * @since  1.0.0
	 * @var    integer    $batchsize    Maintain the insert batch size.
	 */
	private static $batchsize = 100;

	/**
	 * Decompress a gz file. We can't do that with WP_Filesystem() and unzip_file().
	 *
	 * @param string $source The gzipped source.
	 * @param string $target The ungzipped source.
	 * @return bool  True if ok, false otherwise.
	 * @since    1.0.0
	 */
	private static function ungzip_file( $source, $target ) {
		$result = false;
		try {
			$buffer_size = 1024 * 16; // read 16kb at a time
			// phpcs:ignore
			$out_file = fopen( $target, 'wb' );
			$in_file  = gzopen( $source, 'rb' );
			while ( ! gzeof( $in_file ) ) {
				// phpcs:ignore
				fwrite( $out_file, gzread( $in_file, $buffer_size ) );
			}
			// phpcs:ignore
			fclose( $out_file );
			gzclose( $in_file );
			$result = true;
		} catch ( \Throwable $e ) {
			Logger::warning( sprintf( 'Unable to decompress IP data file: %s.', $e->getMessage() ), $e->getCode() );
		}

		return $result;
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
			//$zipfile = download_url( $url );
			$zipfile = '/var/services/tmp/geo-ip-pIFyrv.tmp';
			Logger::debug( 'GZ file: ' . $zipfile );
		} catch ( \Throwable $e ) {
			Logger::warning( sprintf( 'Unable to download IP data file: %s.', $e->getMessage() ), $e->getCode() );
		}
		try {
			//$md5file = download_url( $md5 );
			$md5file = '/var/services/tmp/geo-ip-T4Mly9.tmp';
			Logger::debug( 'MD5 file: ' . $md5file );
		} catch ( \Throwable $e ) {
			Logger::warning( sprintf( 'Unable to download IP data signature: %s.', $e->getMessage() ), $e->getCode() );
		}
		if ( $wp_filesystem->exists( $zipfile ) && $wp_filesystem->exists( $md5file ) ) {
			if ( $wp_filesystem->size( $zipfile ) !== $wp_filesystem->size( $md5file ) ) {
				$unzipfile = get_temp_dir() . '/' . wp_unique_filename( get_temp_dir(), basename( $url ) . '.csv' );
				if ( $wp_filesystem->exists( $unzipfile ) ) {
					$wp_filesystem->delete( $unzipfile );
				}
				try {
					if ( self::ungzip_file( $zipfile, $unzipfile ) ) {
						Logger::debug( 'CSV file: ' . $unzipfile );
						$md5 = $wp_filesystem->get_contents( $md5file );
						$ver = verify_file_md5( $unzipfile, $md5 );
						if ( true === $ver ) {
							Logger::debug( 'IP data file signature verified.' );
							$result = $unzipfile;
						} else {
							if ( is_wp_error( $ver ) ) {
								Logger::warning( 'Unable to verify the IP data file signature: ' . $ver->get_error_message(), $ver->get_error_code() );
							} else {
								Logger::warning( 'Unable to verify the IP data file signature.' );
							}
						}
					}
				} catch ( \Throwable $e ) {
					Logger::warning( sprintf( 'Unable to decompress IP data file: %s.', $e->getMessage() ), $e->getCode() );
				}
			} else {
				Logger::warning( 'Unable to download IP data file: quota exceeded.', 429 );
			}
		} else {
			Logger::warning( 'Unable to acquire IP data files.', 404 );
		}
		if ( $wp_filesystem->exists( $zipfile ) ) {
			//$wp_filesystem->delete( $zipfile );
		}
		if ( $wp_filesystem->exists( $md5file ) ) {
			//$wp_filesystem->delete( $md5file );
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
		Cache::set( 'update/v4/initsemaphore', time(), 'infinite' );
		$file = self::acquire( 'https://software77.net/geo-ip/?DL=1', 'https://software77.net/geo-ip/?DL=3' );
		if ( false !== $file && $wp_filesystem->exists( $file ) ) {
			$data  = $wp_filesystem->get_contents_array( $file );
			$cpt   = 0;
			$ins   = [];
			$clean = [ '"' ];
			$db    = new Schema();
			if ( 0 < count( $data ) ) {
				foreach ( $data as $datum ) {
					if ( is_string( $datum ) && 0 < strlen( $datum ) ) {
						$first = substr( $datum, 0, 1 );
						if ( '#' !== $first && '\n' !== $first ) {
							$tmp = explode( ',', rtrim( $datum ) );
							if ( 4 < count( $tmp ) ) {
								$from = long2ip( (int) str_replace( $clean, '', $tmp[0] ) );
								$to   = long2ip( (int) str_replace( $clean, '', $tmp[1] ) );
								$cc   = substr( strtoupper( str_replace( $clean, '', $tmp[4] ) ), 0, 2 );
								if ( count( $ins ) < self::$batchsize ) {
									$ins[] = $db->get_for_multiple_range( $from, $to, $cc );
									++$cpt;
								} else {
									$db->add_multiple_v4( $ins );
									$ins = [];
								}
							}
						}
					}
				}
				if ( 0 < count( $ins ) ) {
					$db->add_multiple_v4( $ins );
				}
				Logger::info( sprintf( 'IPv4 initialization completed: %d IP ranges have been added.', $cpt ) );
				/* translators: %s can be "IPv4" or "IPv6" */
				Infolog::add( sprintf( esc_html__( '%s initialization completed: %d IP ranges have been added.', 'ip-locator' ), 'IPv4', $cpt ) );
			} else {
				Logger::error( 'IPv4 data files are corrupted or empty.', 404 );
				/* translators: %s can be "IPv4" or "IPv6" */
				Infolog::add( sprintf( esc_html__( '%s data files are corrupted or empty. Retry will be done next cycle.', 'ip-locator' ), 'IPv4' ) );
			}
			$wp_filesystem->delete( $file );
		} else {
			Logger::error( 'Unable to acquire IPv4 data files.', 404 );
			/* translators: %s can be "IPv4" or "IPv6" */
			Infolog::add( sprintf( esc_html__( 'Unable to acquire %s data files. Retry will be done next cycle.', 'ip-locator' ), 'IPv4' ) );
		}
		Cache::set( 'update/v4/initsemaphore', -1, 'infinite' );
	}

	/**
	 * Init IPV4 data.
	 *
	 * @since 1.0.0
	 */
	public static function bup_init_v4() {
		global $wp_filesystem;
		if ( is_null( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		Cache::set( 'update/v4/initsemaphore', time(), 'infinite' );
		$file = self::acquire( 'https://software77.net/geo-ip/?DL=1', 'https://software77.net/geo-ip/?DL=3' );
		if ( false !== $file && $wp_filesystem->exists( $file ) ) {
			$data  = $wp_filesystem->get_contents_array( $file );
			$cpt   = 0;
			$sub   = 0;
			$ins   = '';
			$clean = [ '"' ];
			$db    = new Schema();
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
								if ( $sub < self::$batchsize ) {
									$ins .= $db->get_add_v4( $to, $from, $cc );
									++$sub;
									++$cpt;
								} else {
									$db->execute_query( $ins );
									$sub = 0;
									$ins = '';
								}
							}
						}
					}
				}
				if ( 0 < strlen( $ins ) ) {
					$db->execute_query( $ins );
				}
				Logger::info( sprintf( 'IPv4 initialization completed: %d IP ranges have been added.', $cpt ) );
				/* translators: %s can be "IPv4" or "IPv6" */
				Infolog::add( sprintf( esc_html__( '%s initialization completed: %d IP ranges have been added.', 'ip-locator' ), 'IPv4', $cpt ) );
			} else {
				Logger::error( 'IPv4 data files are corrupted or empty.', 404 );
				/* translators: %s can be "IPv4" or "IPv6" */
				Infolog::add( sprintf( esc_html__( '%s data files are corrupted or empty. Retry will be done next cycle.', 'ip-locator' ), 'IPv4' ) );
			}
			$wp_filesystem->delete( $file );
		} else {
			Logger::error( 'Unable to acquire IPv4 data files.', 404 );
			/* translators: %s can be "IPv4" or "IPv6" */
			Infolog::add( sprintf( esc_html__( 'Unable to acquire %s data files. Retry will be done next cycle.', 'ip-locator' ), 'IPv4' ) );
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
