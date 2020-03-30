<?php
/**
 * IP Locator Manager schema
 *
 * Handles all schema operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator\Plugin\Feature;

use IPLocator\System\APCu;

use IPLocator\System\Option;
use IPLocator\System\Database;
use IPLocator\System\Logger;
use IPLocator\System\Cache;
use IPLocator\Plugin\Feature\IPData;
use IPLocator\System\Infolog;

/**
 * Define the schema functionality.
 *
 * Handles all schema operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Schema {

	/**
	 * IPv4 table name.
	 *
	 * @since  1.0.0
	 * @var    string    $ipv4    The IPv4 table name.
	 */
	private static $ipv4 = IPLOCATOR_PRODUCT_ABBREVIATION . '_v4';

	/**
	 * IPv6 table name.
	 *
	 * @since  1.0.0
	 * @var    string    $ipv6    The IPv6 table name.
	 */
	private static $ipv6 = IPLOCATOR_PRODUCT_ABBREVIATION . '_v6';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

	}

	/**
	 * Get country code for an IP.
	 *
	 * @param string    $ip         The IP version.
	 * @param string    $version    The IP version. Must be 'v4' or 'v6'.
	 * @return  string The country code.
	 * @since 1.0.0
	 */
	public static function get_country( $ip, $version ) {
		global $wpdb;
		switch ( $version ) {
			case 'v4':
				$table = $wpdb->base_prefix . self::$ipv4;
				break;
			case 'v6':
				$table = $wpdb->base_prefix . self::$ipv6;
				break;
			default:
				return '00';
		}
		$result = '00';
		$sql    = "SELECT `country` FROM `" . $table . "` WHERE `from` <= INET6_ATON('" . $ip . "') AND `to` >= INET6_ATON('" . $ip . "') ;";
		// phpcs:ignore
		$country = $wpdb->get_results( $sql, ARRAY_A );
		if ( count( $country ) > 0 ) {
			if ( array_key_exists( 'country', $country[0] ) ) {
				$result = strtoupper( $country[0]['country'] );
			}
		}
		return $result;
	}

	/**
	 * Get ranges count.
	 *
	 * @param string    $version    The IP version. Must be 'v4' or 'v6'.
	 * @return  integer The number of ranges.
	 * @since 1.0.0
	 */
	public function count_ranges( $version ) {
		global $wpdb;
		switch ( $version ) {
			case 'v4':
				$table = $wpdb->base_prefix . self::$ipv4;
				break;
			case 'v6':
				$table = $wpdb->base_prefix . self::$ipv6;
				break;
			default:
				return 0;
		}
		$result = 0;
		$sql    = 'SELECT COUNT(*) as CNT FROM `' . $table . '`;';
		// phpcs:ignore
		$cnt = $wpdb->get_results( $sql, ARRAY_A );
		if ( count( $cnt ) > 0 ) {
			if ( array_key_exists( 'CNT', $cnt[0] ) ) {
				$result = $cnt[0]['CNT'];
			}
		}
		return $result;
	}

	/**
	 * Prepare a table for mass replaces.
	 *
	 * @param string    $version    The IP version. Must be 'v4' or 'v6'.
	 * @param string    $mode       The mode. Must be 'init' or 'update'.
	 * @since 1.0.0
	 */
	public function prepare_table( $version, $mode ) {
		if ( 'v4' !== $version && 'v6' !== $version ) {
			return;
		}
		if ( 'init' !== $mode && 'update' !== $mode ) {
			return;
		}
		if ( 'init' === $mode ) {
			return;
		}
		global $wpdb;
		switch ( $version ) {
			case 'v4':
				$table = $wpdb->base_prefix . self::$ipv4;
				break;
			case 'v6':
				$table = $wpdb->base_prefix . self::$ipv6;
				break;
			default:
				return;
		}
		$sql = "UPDATE `" . $table . "` SET `flag`='D';";
		// phpcs:ignore
		$wpdb->query( $sql );
	}

	/**
	 * Finalize a table after mass replaces.
	 *
	 * @param string    $version    The IP version. Must be 'v4' or 'v6'.
	 * @param string    $mode       The mode. Must be 'init' or 'update'.
	 * @since 1.0.0
	 */
	public function finalize_table( $version, $mode ) {
		if ( 'v4' !== $version && 'v6' !== $version ) {
			return;
		}
		if ( 'init' !== $mode && 'update' !== $mode ) {
			return;
		}
		if ( 'init' === $mode ) {
			return;
		}
		global $wpdb;
		switch ( $version ) {
			case 'v4':
				$table = $wpdb->base_prefix . self::$ipv4;
				break;
			case 'v6':
				$table = $wpdb->base_prefix . self::$ipv6;
				break;
			default:
				return;
		}
		$sql = "DELETE FROM `" . $table . "` WHERE `flag`='D';";
		// phpcs:ignore
		$wpdb->query( $sql );
	}

	/**
	 * Sets an IPv4 range.
	 *
	 * @param   string  $from     The start of the range.
	 * @param   string  $to       The end of the range.
	 * @param   string  $country  The country code.
	 * @return  string  The "value" item of the insert string.
	 * @since    1.0.0
	 **/
	public function get_for_multiple_range( $from, $to, $country ) {
		return "(INET6_ATON('" . $from . "'),INET6_ATON('" . $to . "'),'" . $country . "')";
	}

	/**
	 * Adds many IPv4 ranges.
	 *
	 * @param   array     $values     The values to add.
	 * @param   string    $version    The IP version. Must be 'v4' or 'v6'.
	 * @since    1.0.0
	 **/
	public function add_multiple_range( $values, $version ) {
		if ( count( $values ) > 0 ) {
			global $wpdb;
			switch ( $version ) {
				case 'v4':
					$table = $wpdb->base_prefix . self::$ipv4;
					break;
				case 'v6':
					$table = $wpdb->base_prefix . self::$ipv6;
					break;
				default:
					return;
			}
			$sql  = 'REPLACE INTO `' . $table . '` ';
			$sql .= '(`from`,`to`,`country`) ';
			$sql .= 'VALUES ' . implode( ',', $values ) . ';';
			// phpcs:ignore
			$wpdb->query( $sql );
		}
	}

	/**
	 * Initialize the schema.
	 *
	 * @since    1.0.0
	 */
	public function initialize() {
		global $wpdb;
		try {
			$this->create_tables();
			Logger::debug( sprintf( 'Table "%s" created.', $wpdb->base_prefix . self::$ipv4 ) );
			Logger::debug( sprintf( 'Table "%s" created.', $wpdb->base_prefix . self::$ipv6 ) );
			Logger::info( 'Schema installed.' );
			$this->init_data();
		} catch ( \Throwable $e ) {
			Logger::alert( sprintf( 'Unable to create "%s" and/or "%s" table: %s', $wpdb->base_prefix . self::$ipv4, $wpdb->base_prefix . self::$ipv6, $e->getMessage() ), $e->getCode() );
			Logger::alert( 'Schema not installed.', $e->getCode() );
		}
	}

	/**
	 * Finalize the schema.
	 *
	 * @since    1.0.0
	 */
	public function finalize() {
		global $wpdb;
		$sql = 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . self::$ipv4;
		// phpcs:ignore
		$wpdb->query( $sql );
		Logger::debug( sprintf( 'Table "%s" removed.', $wpdb->base_prefix . self::$ipv4 ) );
		$sql = 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . self::$ipv6;
		// phpcs:ignore
		$wpdb->query( $sql );
		Logger::debug( sprintf( 'Table "%s" removed.', $wpdb->base_prefix . self::$ipv6 ) );
		Logger::debug( 'Schema destroyed.' );
	}

	/**
	 * Update the schema.
	 *
	 * @since    1.0.0
	 */
	public function update() {
		global $wpdb;
		try {
			$this->create_tables();
			Logger::debug( sprintf( 'Table "%s" updated.', $wpdb->base_prefix . self::$ipv4 ) );
			Logger::debug( sprintf( 'Table "%s" updated.', $wpdb->base_prefix . self::$ipv6 ) );
			Logger::info( 'Schema updated.' );
			$this->init_data();
		} catch ( \Throwable $e ) {
			Logger::alert( sprintf( 'Unable to update "%s" and/or "%s" table: %s', $wpdb->base_prefix . self::$ipv4, $wpdb->base_prefix . self::$ipv6, $e->getMessage() ), $e->getCode() );
		}
	}

	/**
	 * Init data.
	 *
	 * @since    1.0.0
	 */
	public function init_data() {
		global $wpdb;
		$needed_v4 = true;
		$sql       = 'SELECT COUNT(*) as CNT FROM `' . $wpdb->base_prefix . self::$ipv4 . '`;';
		// phpcs:ignore
		$cnt = $wpdb->get_results( $sql, ARRAY_A );
		if ( count( $cnt ) > 0 ) {
			if ( array_key_exists( 'CNT', $cnt[0] ) ) {
				$needed_v4 = ( 0 === (int) $cnt[0]['CNT'] );
			}
		}
		$needed_v6 = true;
		$sql       = 'SELECT COUNT(*) as CNT FROM `' . $wpdb->base_prefix . self::$ipv6 . '`;';
		// phpcs:ignore
		$cnt = $wpdb->get_results( $sql, ARRAY_A );
		if ( count( $cnt ) > 0 ) {
			if ( array_key_exists( 'CNT', $cnt[0] ) ) {
				$needed_v6 = ( 0 === (int) $cnt[0]['CNT'] );
			}
		}
		if ( $needed_v4 ) {
			/* translators: %s can be "IPv4" or "IPv6" */
			Infolog::add( sprintf( esc_html__( '%s data need to be initialized. Initialization will start in some minutes; note it could take many time to complete…', 'ip-locator' ), 'IPv4' ) );
			$semaphore = Cache::get( 'update/v4/initsemaphore' );
			if ( ( 1 !== (int) $semaphore && 2 !== (int) $semaphore ) || false === $semaphore ) {
				if ( -1 === (int) $semaphore || false === $semaphore ) {
					Cache::set( 'update/v4/initsemaphore', 1, 'infinite' );
					Logger::info( 'IPv4 data initialization is needed. This initialization will start in some minutes.' );
				} else {
					if ( time() - (int) $semaphore > IPLOCATOR_INIT_TIMEOUT ) {
						Cache::set( 'update/v4/initsemaphore', -1, 'infinite' );
						Logger::info( 'Semaphore for IPv4 data initialization has been reset.' );
						Logger::warning( 'Incomplete IPv4 data initialization.' );
					}
				}
			}
		} else {
			Cache::set( 'update/v4/initsemaphore', -1, 'infinite' );
			Logger::info( 'No need to initialize IPv4 data.' );
		}
		if ( $needed_v6 ) {
			/* translators: %s can be "IPv4" or "IPv6" */
			Infolog::add( sprintf( esc_html__( '%s data need to be initialized. Initialization will start in some minutes; note it could take many time to complete…', 'ip-locator' ), 'IPv6' ) );
			$semaphore = Cache::get( 'update/v6/initsemaphore' );
			if ( ( 1 !== (int) $semaphore && 2 !== (int) $semaphore ) || false === $semaphore ) {
				if ( -1 === (int) $semaphore || false === $semaphore ) {
					Cache::set( 'update/v6/initsemaphore', 1, 'infinite' );
					Logger::info( 'IPv6 data initialization is needed. This initialization will start in some minutes.' );
				} else {
					if ( time() - (int) $semaphore > IPLOCATOR_INIT_TIMEOUT ) {
						Cache::set( 'update/v6/initsemaphore', -1, 'infinite' );
						Logger::info( 'Semaphore for IPv6 data initialization has been reset.' );
						Logger::warning( 'Incomplete IPv6 data initialization.' );
					}
				}
			}
		} else {
			Cache::set( 'update/v6/initsemaphore', -1, 'infinite' );
			Logger::info( 'No need to initialize IPv6 data.' );
		}
	}

	/**
	 * Create the table.
	 *
	 * @since    1.0.0
	 */
	private function create_tables() {
		global $wpdb;
		$charset_collate = 'DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci';
		$sql             = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->base_prefix . self::$ipv4;
		$sql            .= " (`from` VARBINARY(4) NOT NULL DEFAULT INET6_ATON('0.0.0.0'),";
		$sql            .= " `to` VARBINARY(4) NOT NULL DEFAULT INET6_ATON('0.0.0.0'),";
		$sql            .= " `country` VARCHAR(2) DEFAULT NULL DEFAULT 'XX',";
		$sql            .= " `flag` VARCHAR(1) DEFAULT NULL DEFAULT 'I',";
		$sql            .= " PRIMARY KEY (`from`,`to`)";
		$sql            .= ") $charset_collate;";
		// phpcs:ignore
		$wpdb->query( $sql );
		$charset_collate = 'DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci';
		$sql             = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->base_prefix . self::$ipv6;
		$sql            .= " (`from` VARBINARY(16) NOT NULL DEFAULT INET6_ATON('0000:0000:0000:0000:0000:0000:0000:0000'),";
		$sql            .= " `to` VARBINARY(16) NOT NULL DEFAULT INET6_ATON('0000:0000:0000:0000:0000:0000:0000:0000'),";
		$sql            .= " `country` VARCHAR(2) DEFAULT NULL DEFAULT 'XX',";
		$sql            .= " `flag` VARCHAR(1) DEFAULT NULL DEFAULT 'I',";
		$sql            .= " PRIMARY KEY (`from`,`to`)";
		$sql            .= ") $charset_collate;";
		// phpcs:ignore
		$wpdb->query( $sql );
	}
}