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
use IPLocator\System\Timezone;
use IPLocator\System\Blog;
use IPLocator\API\Country;
use IPLocator\System\Environment;
use IPLocator\Plugin\Feature\ChannelTypes;
use IPLocator\System\UserAgent;

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
	 * Statistics table name.
	 *
	 * @since  2.0.0
	 * @var    string    $statistics    The statistics table name.
	 */
	private static $statistics = IPLOCATOR_PRODUCT_ABBREVIATION . '_statistics';

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
	 * Initialize static properties and hooks.
	 *
	 * @since    1.0.0
	 */
	public static function init() {
		add_action( 'shutdown', [ 'IPLocator\Plugin\Feature\Schema', 'write' ], 90, 0 );
	}

	/**
	 * Write all buffers to database.
	 *
	 * @since    1.0.0
	 */
	public static function write() {
		if ( Option::network_get( 'analytics' ) ) {
			self::write_statistics();
		}
	}

	/**
	 * Write statistics.
	 *
	 * @since    1.0.0
	 */
	private static function write_statistics() {
		$ua     = UserAgent::get();
		$client = $ua->client_type;
		if ( ! $client || '' === $client || 'cli' === self::current_channel_tag() || $ua->class_is_bot ) {
			$client = 'other';
		}
		$country             = new Country();
		$record              = [];
		$datetime            = new \DateTime( 'now', Timezone::network_get() );
		$record['timestamp'] = $datetime->format( 'Y-m-d' );
		$record['site']      = Blog::get_current_blog_id( 1 );
		$record['channel']   = self::current_channel_tag();
		$record['client']    = $client;
		$record['hit']       = 1;
		$record['country']   = $country->code();
		$record['language']  = $country->lang()->code();
		$name                = $country->name();
		if ( 'A0' === $record['country'] ) {
			$class = 'private';
		} elseif ( '[satellite]' === $name ) {
			$class = 'satellite';
		} elseif ( '[' === substr( $name, 0, 1 ) ) {
			$class = 'other';
		} else {
			$class = 'public';
		}
		$record['class'] = $class;
		$field_insert    = [];
		$value_insert    = [];
		$value_update    = [];
		foreach ( $record as $k => $v ) {
			$field_insert[] = '`' . $k . '`';
			$value_insert[] = "'" . $v . "'";
			if ( 'hit' === $k ) {
				$value_update[] = '`hit`=hit + 1';
			}
		}
		if ( count( $field_insert ) > 0 ) {
			global $wpdb;
			$sql  = 'INSERT INTO `' . $wpdb->base_prefix . self::$statistics . '` ';
			$sql .= '(' . implode( ',', $field_insert ) . ') ';
			$sql .= 'VALUES (' . implode( ',', $value_insert ) . ') ';
			$sql .= 'ON DUPLICATE KEY UPDATE ' . implode( ',', $value_update ) . ';';
			// phpcs:ignore
			$wpdb->query( $sql );
		}
		self::purge();
	}

	/**
	 * Get the current channel tag.
	 *
	 * @return  string The current channel tag.
	 * @since 1.0.0
	 */
	private static function current_channel_tag() {
		return strtolower( self::channel_tag( Environment::exec_mode() ) );
	}

	/**
	 * Get the channel tag.
	 *
	 * @param   integer $id Optional. The channel id (execution mode).
	 * @return  string The channel tag.
	 * @since 1.0.0
	 */
	public static function channel_tag( $id = 0 ) {
		if ( $id >= count( ChannelTypes::$channels ) ) {
			$id = 0;
		}
		return ChannelTypes::$channels[ $id ];
	}

	/**
	 * Purge old records.
	 *
	 * @since    1.0.0
	 */
	private static function purge() {
		$days = (int) Option::network_get( 'history' );
		if ( ! is_numeric( $days ) || 30 > $days ) {
			$days = 30;
			Option::network_set( 'history', $days );
		}
		$database = new Database();
		$count    = $database->purge( self::$statistics, 'timestamp', 24 * $days );
		if ( 0 === $count ) {
			Logger::debug( 'No old records to delete.' );
		} elseif ( 1 === $count ) {
			Logger::debug( '1 old record deleted.' );
			Cache::delete_global( 'data/oldestdate' );
		} else {
			Logger::debug( sprintf( '%1$s old records deleted.', $count ) );
			Cache::delete_global( 'data/oldestdate' );
		}
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
			Logger::debug( sprintf( 'Table "%s" created.', $wpdb->base_prefix . self::$statistics ) );
			Logger::debug( sprintf( 'Table "%s" created.', $wpdb->base_prefix . self::$ipv4 ) );
			Logger::debug( sprintf( 'Table "%s" created.', $wpdb->base_prefix . self::$ipv6 ) );
			Logger::info( 'Schema installed.' );
			$this->init_data();
		} catch ( \Throwable $e ) {
			Logger::alert( sprintf( 'Unable to create a table: %s', $e->getMessage() ), $e->getCode() );
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
		$sql = 'DROP TABLE IF EXISTS ' . $wpdb->base_prefix . self::$statistics;
		// phpcs:ignore
		$wpdb->query( $sql );
		Logger::debug( sprintf( 'Table "%s" removed.', $wpdb->base_prefix . self::$statistics ) );
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
			Logger::alert( sprintf( 'Unable to update a table: %s', $e->getMessage() ), $e->getCode() );
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
			Logger::info( 'IPv4 data initialization is needed. This initialization will start in some minutes.' );
		} else {
			Logger::info( 'No need to initialize IPv4 data.' );
		}
		if ( $needed_v6 ) {
			/* translators: %s can be "IPv4" or "IPv6" */
			Infolog::add( sprintf( esc_html__( '%s data need to be initialized. Initialization will start in some minutes; note it could take many time to complete…', 'ip-locator' ), 'IPv6' ) );
			Logger::info( 'IPv6 data initialization is needed. This initialization will start in some minutes.' );
		} else {
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
		$sql            .= " (`from` VARBINARY(4) NOT NULL,";
		$sql            .= " `to` VARBINARY(4) NOT NULL,";
		$sql            .= " `country` VARCHAR(2) NOT NULL DEFAULT 'XX',";
		$sql            .= " `flag` VARCHAR(1) NOT NULL DEFAULT 'I',";
		$sql            .= " PRIMARY KEY (`from`,`to`)";
		$sql            .= ") $charset_collate;";
		// phpcs:ignore
		$wpdb->query( $sql );
		$charset_collate = 'DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci';
		$sql             = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->base_prefix . self::$ipv6;
		$sql            .= " (`from` VARBINARY(16) NOT NULL,";
		$sql            .= " `to` VARBINARY(16) NOT NULL,";
		$sql            .= " `country` VARCHAR(2) NOT NULL DEFAULT 'XX',";
		$sql            .= " `flag` VARCHAR(1) NOT NULL DEFAULT 'I',";
		$sql            .= " PRIMARY KEY (`from`,`to`)";
		$sql            .= ") $charset_collate;";
		// phpcs:ignore
		$wpdb->query( $sql );
		$charset_collate = 'DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci';
		$sql             = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->base_prefix . self::$statistics;
		$sql            .= " (`timestamp` date NOT NULL DEFAULT '0000-00-00',";
		$sql            .= " `site` bigint(20) NOT NULL DEFAULT '0',";
		$sql            .= " `class` enum('public','private','satellite','other') NOT NULL DEFAULT 'other',";
		$sql            .= " `channel` enum('cli','cron','ajax','xmlrpc','api','feed','wback','wfront','other') NOT NULL DEFAULT 'other',";
		$sql            .= " `client` enum('browser','feed-reader','library','media-player','mobile-app','pim','other') NOT NULL DEFAULT 'other',";
		$sql            .= " `hit` int(11) UNSIGNED NOT NULL DEFAULT '0',";
		$sql            .= " `country` varchar(2) DEFAULT '00',";
		$sql            .= " `language` varchar(2) DEFAULT 'en',";
		$sql            .= ' UNIQUE KEY u_stat (timestamp, site, class, channel, client, country)';
		$sql            .= ") $charset_collate;";
		// phpcs:ignore
		$wpdb->query( $sql );
	}

	/**
	 * Get "where" clause of a query.
	 *
	 * @param array $filters Optional. An array of filters.
	 * @return string The "where" clause.
	 * @since 1.0.0
	 */
	private static function get_where_clause( $filters = [] ) {
		$result = '';
		if ( 0 < count( $filters ) ) {
			$w = [];
			foreach ( $filters as $key => $filter ) {
				if ( is_array( $filter ) ) {
					$w[] = '`' . $key . '` IN (' . implode( ',', $filter ) . ')';
				} else {
					$w[] = '`' . $key . '`="' . $filter . '"';
				}
			}
			$result = 'WHERE (' . implode( ' AND ', $w ) . ')';
		}
		return $result;
	}

	/**
	 * Get the oldest date.
	 *
	 * @return  string   The oldest timestamp in the statistics table.
	 * @since    1.0.0
	 */
	public static function get_oldest_date() {
		$result = Cache::get_global( 'data/oldestdate' );
		if ( $result ) {
			return $result;
		}
		global $wpdb;
		$sql = 'SELECT * FROM ' . $wpdb->base_prefix . self::$statistics . ' ORDER BY `timestamp` ASC LIMIT 1';
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) && 0 < count( $result ) && array_key_exists( 'timestamp', $result[0] ) ) {
			Cache::set_global( 'data/oldestdate', $result[0]['timestamp'], 'infinite' );
			return $result[0]['timestamp'];
		}
		return '';
	}

	/**
	 * Get the standard KPIs.
	 *
	 * @param   array   $filter      The filter of the query.
	 * @param   string  $group       Optional. The group of the query.
	 * @param   boolean $cache       Optional. Has the query to be cached.
	 * @return  array   The grouped KPIs.
	 * @since    1.0.0
	 */
	public static function get_grouped_kpi( $filter, $group = '', $cache = true ) {
		// phpcs:ignore
		$id = Cache::id( __FUNCTION__ . serialize( $filter ) . $group );
		if ( $cache ) {
			$result = Cache::get_global( $id );
			if ( $result ) {
				return $result;
			}
		}
		if ( '' !== $group ) {
			$group = ' GROUP BY ' . $group;
		}
		global $wpdb;
		$sql = 'SELECT sum(hit) as sum_hit, class FROM ' . $wpdb->base_prefix . self::$statistics . ' WHERE (' . implode( ' AND ', $filter ) . ')' . $group;
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) ) {
			if ( $cache ) {
				Cache::set_global( $id, $result, 'infinite' );
			}
			return $result;
		}
		return [];
	}

	/**
	 * Get the standard KPIs.
	 *
	 * @param   array   $filter      The filter of the query.
	 * @param   array   $distinct    Optional. The distinct fields to query.
	 * @param   boolean $cache       Optional. Has the query to be cached.
	 * @return  array   The distinct KPIs.
	 * @since    1.0.0
	 */
	public static function get_distinct_kpi( $filter, $distinct = [], $cache = true ) {
		// phpcs:ignore
		$id = Cache::id( __FUNCTION__ . serialize( $filter ) . serialize( $distinct ) );
		if ( $cache ) {
			$result = Cache::get_global( $id );
			if ( $result ) {
				return $result;
			}
		}
		if ( 0 < count( $distinct ) ) {
			$select = ' DISTINCT ' . implode( ', ', $distinct );
		} else {
			$select = '*';
		}
		global $wpdb;
		$sql = 'SELECT ' . $select . ' FROM ' . $wpdb->base_prefix . self::$statistics . ' WHERE (' . implode( ' AND ', $filter ) . ')';
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) ) {
			if ( $cache ) {
				Cache::set_global( $id, $result, 'infinite' );
			}
			return $result;
		}
		return [];
	}

	/**
	 * Get a time series.
	 *
	 * @param   array   $filter      The filter of the query.
	 * @param   boolean $cache       Has the query to be cached.
	 * @param   string  $extra_field Optional. The extra field to filter.
	 * @param   array   $extras      Optional. The extra values to match.
	 * @param   boolean $not         Optional. Exclude extra filter.
	 * @param   integer $limit       Optional. The number of results to return.
	 * @return  array   The time series.
	 * @since    1.0.0
	 */
	public static function get_time_series( $filter, $cache = true, $extra_field = '', $extras = [], $not = false, $limit = 0 ) {
		$data   = self::get_grouped_list( $filter, 'timestamp', $cache, $extra_field, $extras, $not, 'ORDER BY timestamp ASC', $limit );
		$result = [];
		foreach ( $data as $datum ) {
			$result[ $datum['timestamp'] ] = $datum;
		}
		return $result;
	}

	/**
	 * Get a counted time series.
	 *
	 * @param   array   $filter      The filter of the query.
	 * @param   boolean $cache       Has the query to be cached.
	 * @param   string  $extra_field Optional. The extra field to filter.
	 * @param   array   $extras      Optional. The extra values to match.
	 * @param   boolean $not         Optional. Exclude extra filter.
	 * @param   integer $limit       Optional. The number of results to return.
	 * @return  array   The time series.
	 * @since    1.0.0
	 */
	public static function get_counted_time_series( $filter, $cache = true, $extra_field = '', $extras = [], $not = false, $limit = 0 ) {
		$data   = self::get_counted_list( $filter, 'timestamp', $cache, $extra_field, $extras, $not, 'ORDER BY timestamp ASC', $limit );
		$result = [];
		foreach ( $data as $datum ) {
			$result[ $datum['timestamp'] ] = $datum;
		}
		return $result;
	}

	/**
	 * Get the a grouped list.
	 *
	 * @param   array   $filter      The filter of the query.
	 * @param   string  $group       Optional. The group of the query.
	 * @param   boolean $cache       Optional. Has the query to be cached.
	 * @param   string  $extra_field Optional. The extra field to filter.
	 * @param   array   $extras      Optional. The extra values to match.
	 * @param   boolean $not         Optional. Exclude extra filter.
	 * @param   string  $order       Optional. The sort order of results.
	 * @param   integer $limit       Optional. The number of results to return.
	 * @return  array   The grouped list.
	 * @since    1.0.0
	 */
	public static function get_grouped_list( $filter, $group = '', $cache = true, $extra_field = '', $extras = [], $not = false, $order = '', $limit = 0 ) {
		// phpcs:ignore
		$id = Cache::id( __FUNCTION__ . serialize( $filter ) . $group . $extra_field . serialize( $extras ) . ( $not ? 'no' : 'yes') . $order . (string) $limit);
		if ( $cache ) {
			$result = Cache::get_global( $id );
			if ( $result ) {
				return $result;
			}
		}
		if ( '' !== $group ) {
			$group = ' GROUP BY ' . $group;
		}
		$where_extra = '';
		if ( 0 < count( $extras ) && '' !== $extra_field ) {
			$where_extra = ' AND ' . $extra_field . ( $not ? ' NOT' : '' ) . " IN ( '" . implode( "', '", $extras ) . "' )";
		}
		global $wpdb;
		$sql = 'SELECT `timestamp`, sum(hit) as sum_hit, site, class, channel, client, country, `language` FROM ' . $wpdb->base_prefix . self::$statistics . ' WHERE (' . implode( ' AND ', $filter ) . ')' . $where_extra . ' ' . $group . ' ' . $order . ( $limit > 0 ? ' LIMIT ' . $limit : '') .';';
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) ) {
			if ( $cache ) {
				Cache::set_global( $id, $result, 'infinite' );
			}
			return $result;
		}
		return [];
	}

	/**
	 * Get the a counted list.
	 *
	 * @param   array   $filter      The filter of the query.
	 * @param   string  $group       Optional. The group of the query.
	 * @param   boolean $cache       Optional. Has the query to be cached.
	 * @param   string  $extra_field Optional. The extra field to filter.
	 * @param   array   $extras      Optional. The extra values to match.
	 * @param   boolean $not         Optional. Exclude extra filter.
	 * @param   string  $order       Optional. The sort order of results.
	 * @param   integer $limit       Optional. The number of results to return.
	 * @return  array   The grouped list.
	 * @since    1.0.0
	 */
	public static function get_counted_list( $filter, $group = '', $cache = true, $extra_field = '', $extras = [], $not = false, $order = '', $limit = 0 ) {
		// phpcs:ignore
		$id = Cache::id( __FUNCTION__ . serialize( $filter ) . $group . $extra_field . serialize( $extras ) . ( $not ? 'no' : 'yes') . $order . (string) $limit);
		if ( $cache ) {
			$result = Cache::get_global( $id );
			if ( $result ) {
				return $result;
			}
		}
		if ( '' !== $group ) {
			$group = ' GROUP BY ' . $group;
		}
		$where_extra = '';
		if ( 0 < count( $extras ) && '' !== $extra_field ) {
			$where_extra = ' AND ' . $extra_field . ( $not ? ' NOT' : '' ) . " IN ( '" . implode( "', '", $extras ) . "' )";
		}
		global $wpdb;
		$sql = 'SELECT `timestamp`, count(distinct country) as cnt_country, count(distinct language) as cnt_language FROM ' . $wpdb->base_prefix . self::$statistics . ' WHERE (' . implode( ' AND ', $filter ) . ')' . $where_extra . ' ' . $group . ' ' . $order . ( $limit > 0 ? ' LIMIT ' . $limit : '') .';';
		Logger::critical(esc_textarea( $sql));
		// phpcs:ignore
		$result = $wpdb->get_results( $sql, ARRAY_A );
		if ( is_array( $result ) ) {
			if ( $cache ) {
				Cache::set_global( $id, $result, 'infinite' );
			}
			return $result;
		}
		return [];
	}
}

Schema::init();