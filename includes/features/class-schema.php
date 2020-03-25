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
	 * IPV4 table name.
	 *
	 * @since  1.0.0
	 * @var    string    $ipv4    The IPV4 table name.
	 */
	private static $ipv4 = IPLOCATOR_PRODUCT_ABBREVIATION . '_v4';

	/**
	 * IPV6 table name.
	 *
	 * @since  1.0.0
	 * @var    string    $ipv6    The IPV4 table name.
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
	 * Effectively write a record in the database.
	 *
	 * @param   array  $record    The record to write.
	 * @param   string $table     The table to write in.
	 * @since    1.0.0
	 **/
	private function write_to_database( $record, $table ) {
		/*$field_insert = [];
		$value_insert = [];
		$value_update = [];
		foreach ( $record as $k => $v ) {
			$field_insert[] = '`' . $k . '`';
			$value_insert[] = "'" . $v . "'";
			$value_update[] = '`' . $k . '`=' . "'" . $v . "'";
		}
		if ( count( $field_insert ) > 0 ) {
			global $wpdb;
			$sql  = 'INSERT INTO `' . $wpdb->base_prefix . $table . '` ';
			$sql .= '(' . implode( ',', $field_insert ) . ') ';
			$sql .= 'VALUES (' . implode( ',', $value_insert ) . ') ';
			$sql .= 'ON DUPLICATE KEY UPDATE ' . implode( ',', $value_update ) . ';';
			// phpcs:ignore
			$wpdb->query( $sql );
		}*/
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
			$semaphore = Cache::get( 'update/v4/initsemaphore' );
			if ( 1 !== (int) $semaphore || false === $semaphore ) {
				if ( -1 === (int) $semaphore || false === $semaphore ) {
					Cache::set( 'update/v4/initsemaphore', 1 );
					Logger::info( 'IPV4 data initialization is needed.' );
				} else {
					if ( time() - (int) $semaphore > IPLOCATOR_INIT_TIMEOUT ) {
						Cache::set( 'update/v4/initsemaphore', -1, 'infinite' );
						Logger::info( 'Semaphore for IPV4 data initialization has been reset.' );
						Logger::warning( 'Incomplete IPV4 data initialization.' );
					}
				}
			}
		} else {
			Cache::set( 'update/v4/initsemaphore', -1, 'infinite' );
			Logger::info( 'No need to initialize IPV4 data.' );
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
		$sql            .= " PRIMARY KEY (`from`,`to`)";
		$sql            .= ") $charset_collate;";
		// phpcs:ignore
		$wpdb->query( $sql );
		$charset_collate = 'DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci';
		$sql             = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->base_prefix . self::$ipv6;
		$sql            .= " (`from` VARBINARY(16) NOT NULL DEFAULT INET6_ATON('0000:0000:0000:0000:0000:0000:0000:0000'),";
		$sql            .= " `to` VARBINARY(16) NOT NULL DEFAULT INET6_ATON('0000:0000:0000:0000:0000:0000:0000:0000'),";
		$sql            .= " `country` VARCHAR(2) DEFAULT NULL DEFAULT 'XX',";
		$sql            .= " PRIMARY KEY (`from`,`to`)";
		$sql            .= ") $charset_collate;";
		// phpcs:ignore
		$wpdb->query( $sql );
	}
}