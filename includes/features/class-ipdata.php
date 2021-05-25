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

use IPLocator\System\Option;
use IPLocator\System\Infolog;
use IPLocator\Plugin\Feature\Schema;
use IPLocator\System\IP;
use IPLocator\System\Http;

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
	 * Insert batch sizes.
	 *
	 * @since  1.0.0
	 * @var    integer    $batchsizes    Maintain the insert batch sizes.
	 */
	private static $batchsizes = [
		'v4' => 300,
		'v6' => 200,
	];

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
			$out_file = \fopen( $target, 'wb' );
			$in_file  = \gzopen( $source, 'rb' );
			while ( ! \gzeof( $in_file ) ) {
				// phpcs:ignore
				\fwrite( $out_file, \gzread( $in_file, $buffer_size ) );
			}
			// phpcs:ignore
			\fclose( $out_file );
			\gzclose( $in_file );
			$result = true;
		} catch ( \Throwable $e ) {
			\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->warning( sprintf( 'Unable to decompress IP data file: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
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
	private static function acquire( $url, $md5 ) {
		global $wp_filesystem;
		$result         = false;
		$zipfile        = '';
		$md5file        = '';
		$download_error = false;
		try {
			$zipfile = iplocator_download_url( $url, 1000, false, Http::user_agent() );
			if ( $zipfile instanceof \WP_Error ) {
				throw new \Exception( (string) $zipfile->get_error_message(), (int) $zipfile->get_error_code() );
			} else {
				\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->debug( 'GZ file: ' . $zipfile );
			}
		} catch ( \Throwable $e ) {
			$download_error = true;
			\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->warning( sprintf( 'Unable to download IP data file: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
		}
		try {
			$md5file = iplocator_download_url( $md5, 1000, false, Http::user_agent() );
			if ( $md5file instanceof \WP_Error ) {
				throw new \Exception( (string) $md5file->get_error_message(), (int) $md5file->get_error_code() );
			} else {
				\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->debug( 'MD5 file: ' . $md5file );
			}
		} catch ( \Throwable $e ) {
			$download_error = true;
			\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->warning( sprintf( 'Unable to download IP data signature: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
		}
		if ( ! $download_error && $wp_filesystem->exists( $zipfile ) && $wp_filesystem->exists( $md5file ) ) {
			if ( $wp_filesystem->size( $zipfile ) !== $wp_filesystem->size( $md5file ) ) {
				$unzipfile = get_temp_dir() . '/' . wp_unique_filename( get_temp_dir(), basename( $url ) . '.csv' );
				if ( $wp_filesystem->exists( $unzipfile ) ) {
					$wp_filesystem->delete( $unzipfile );
				}
				try {
					if ( self::ungzip_file( $zipfile, $unzipfile ) ) {
						\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->debug( 'CSV file: ' . $unzipfile );
						$m = explode( ' ', (string) $wp_filesystem->get_contents( $md5file ) );
						if ( 0 < count( $m ) ) {
							$md5 = (string) $m[0];
						} else {
							$md5 = '';
						}
						$ver = verify_file_md5( $unzipfile, $md5 );
						if ( true === $ver ) {
							\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->debug( 'IP data file signature verified.' );
							$result = $unzipfile;
						} else {
							if ( is_wp_error( $ver ) ) {
								\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->warning( 'Unable to verify the IP data file signature: ' . $ver->get_error_message(), [ 'code' => $ver->get_error_code() ] );
							} else {
								\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->warning( 'Unable to verify the IP data file signature.' );
							}
						}
					}
				} catch ( \Throwable $e ) {
					\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->warning( sprintf( 'Unable to decompress IP data file: %s.', $e->getMessage() ), [ 'code' => $e->getCode() ] );
				}
			} else {
				\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->warning( 'Unable to download IP data file: quota exceeded.', [ 'code' => 429 ] );
			}
		} else {
			\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->warning( 'Unable to acquire IP data files.', [ 'code' => 404 ] );
		}
		if ( ! $download_error && $wp_filesystem->exists( $zipfile ) ) {
			$wp_filesystem->delete( $zipfile );
		}
		if ( ! $download_error && $wp_filesystem->exists( $md5file ) ) {
			$wp_filesystem->delete( $md5file );
		}
		return $result;
	}

	/**
	 * Import data in database.
	 *
	 * @param string    $version    The IP version. Must be 'v4' or 'v6'.
	 * @param string    $mode       The mode. Must be 'init' or 'update'.
	 * @since 1.0.0
	 */
	private static function import_data( $version, $mode ) {
		if ( 'v4' !== $version && 'v6' !== $version ) {
			\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->error( 'Wrong IP version.', [ 'code' => 500 ] );
			return;
		}
		if ( 'init' !== $mode && 'update' !== $mode ) {
			\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->error( 'Wrong import mode.', [ 'code' => 500 ] );
			return;
		}
		$spanroot = \DecaLog\Engine::tracesLogger( IPLOCATOR_SLUG )->start_span( 'IP' . $version . ' data ' . $mode );
		global $wp_filesystem;
		if ( is_null( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		Cache::set( 'update/' . $version . '/' . $mode . 'semaphore', time(), 'infinite' );
		$span = \DecaLog\Engine::tracesLogger( IPLOCATOR_SLUG )->start_span( 'File download', $spanroot );
		$file = self::acquire( 'https://assets.perfops.one/geoip/iplocator_' . $version . '_db.csv.gz', 'https://assets.perfops.one/geoip/iplocator_' . $version . '_db.csv.sig' );
		\DecaLog\Engine::tracesLogger( IPLOCATOR_SLUG )->end_span( $span );
		if ( false !== $file && $wp_filesystem->exists( $file ) ) {
			$span  = \DecaLog\Engine::tracesLogger( IPLOCATOR_SLUG )->start_span( 'Database ' . $mode, $spanroot );
			$data  = $wp_filesystem->get_contents_array( $file );
			$cpt   = 0;
			$ins   = [];
			$clean = [ '"' ];
			$db    = new Schema();
			if ( 0 < count( $data ) ) {
				$db->prepare_table( $version, $mode );
				foreach ( $data as $datum ) {
					if ( is_string( $datum ) && 0 < strlen( $datum ) ) {
						$first = substr( $datum, 0, 1 );
						if ( '#' !== $first && '\n' !== $first ) {
							$tmp = explode( ',', rtrim( $datum ) );
							if ( 3 === count( $tmp ) ) {
								if ( 'v4' === $version ) {
									$from = IP::normalize_v4( $tmp[0] );
									$to   = IP::normalize_v4( $tmp[1] );
								} else {
									$from = IP::expand_v6( $tmp[0] );
									$to   = IP::expand_v6( $tmp[1] );
								}
								$cc = strtoupper( $tmp[2] );
								if ( '' !== $from && '' !== $to && '' !== $cc && 2 === strlen( $cc ) ) {
									if ( count( $ins ) < self::$batchsizes[ $version ] ) {
										$ins[] = $db->get_for_multiple_range( $from, $to, $cc );
										++$cpt;
									} else {
										$db->add_multiple_range( $ins, $version );
										$ins = [];
									}
								}
							}
						}
					}
				}
				if ( 0 < count( $ins ) ) {
					$db->add_multiple_range( $ins, $version );
					$cpt = $cpt + count( $ins );
				}
				$db->finalize_table( $version, $mode );
				Option::network_set( 'dbversion_' . $version, time() );
				$time = time() - (int) Cache::get( 'update/' . $version . '/' . $mode . 'semaphore' );
				if ( 'init' === $mode ) {
					\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->info( sprintf( 'IP' . $version . ' initialization completed in %d seconds: %d IP ranges have been added.', $time, $cpt ) );
					/* translators: %1$s can be "IPv4" or "IPv6" */
					Infolog::add( sprintf( esc_html__( '%1$s initialization completed in %2$d seconds: %3$d IP ranges have been added.', 'ip-locator' ), 'IP' . $version, $time, $cpt ) );
				} else {
					\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->info( sprintf( 'IP' . $version . ' update completed in %d seconds: %d IP ranges have been added, updated or deleted.', $time, $cpt ) );
					/* translators: %1$s can be "IPv4" or "IPv6" */
					Infolog::add( sprintf( esc_html__( '%1$s update completed in %2$d seconds: %3$d IP ranges have been added, updated or deleted.', 'ip-locator' ), 'IP' . $version, $time, $cpt ) );
				}
			} else {
				\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->error( 'IP' . $version . ' data files are corrupted or empty.', [ 'code' => 404 ] );
				/* translators: %s can be "IPv4" or "IPv6" */
				Infolog::add( sprintf( esc_html__( '%s data files are corrupted or empty. Retry will be done next cycle.', 'ip-locator' ), 'IP' . $version ) );
			}
			$wp_filesystem->delete( $file );
			\DecaLog\Engine::tracesLogger( IPLOCATOR_SLUG )->end_span( $span );
		} else {
			\DecaLog\Engine::eventsLogger( IPLOCATOR_SLUG )->error( 'Unable to acquire IP' . $version . ' data files.', [ 'code' => 404 ] );
			/* translators: %s can be "IPv4" or "IPv6" */
			Infolog::add( sprintf( esc_html__( 'Unable to acquire %s data files. Retry will be done next cycle.', 'ip-locator' ), 'IP' . $version ) );
		}
		Cache::set( 'update/' . $version . '/' . $mode . 'semaphore', -1, 'infinite' );
		\DecaLog\Engine::tracesLogger( IPLOCATOR_SLUG )->end_span( $spanroot );
	}

	/**
	 * Init IPV4 data.
	 *
	 * @since 1.0.0
	 */
	public static function init_v4() {
		self::import_data( 'v4', 'init' );
	}

	/**
	 * Init IPV6 data.
	 *
	 * @since 1.0.0
	 */
	public static function init_v6() {
		self::import_data( 'v6', 'init' );
	}

	/**
	 * Init IPV4 data.
	 *
	 * @since 1.0.0
	 */
	public static function update_v4() {
		self::import_data( 'v4', 'update' );
	}

	/**
	 * Update IPV6 data.
	 *
	 * @since 1.0.0
	 */
	public static function update_v6() {
		self::import_data( 'v6', 'update' );
	}

}
