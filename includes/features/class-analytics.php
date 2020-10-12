<?php
/**
 * Device detector analytics
 *
 * Handles all analytics operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace IPLocator\Plugin\Feature;

use IPLocator\Plugin\Feature\Schema;
use IPLocator\System\Blog;
use IPLocator\System\Cache;
use IPLocator\System\Date;
use IPLocator\System\Conversion;
use IPLocator\System\Environment;
use IPLocator\System\IP;
use IPLocator\System\Role;
use IPLocator\System\Logger;
use IPLocator\System\L10n;
use IPLocator\System\Http;
use IPLocator\System\Favicon;
use IPLocator\System\Timezone;
use IPLocator\System\UserAgent;
use IPLocator\System\UUID;
use IPLocator\Plugin\Feature\ChannelTypes;
use UDD\DeviceDetector;
use UDD\Parser\Client\Browser;
use UDD\Parser\OperatingSystem;
use UDD\Parser\Device\DeviceParserAbstract;
use Feather;
use Flagiconcss;
use Morpheus;


/**
 * Define the analytics functionality.
 *
 * Handles all analytics operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Analytics {

	/**
	 * The queried site.
	 *
	 * @since  1.0.0
	 * @var    string    $site    The queried site.
	 */
	public $site = 'all';

	/**
	 * The start date.
	 *
	 * @since  1.0.0
	 * @var    string    $start    The start date.
	 */
	private $start = '';

	/**
	 * The end date.
	 *
	 * @since  1.0.0
	 * @var    string    $end    The end date.
	 */
	private $end = '';

	/**
	 * The period duration in seconds.
	 *
	 * @since  1.0.0
	 * @var    integer    $duration    The period duration in seconds.
	 */
	private $duration = 0;

	/**
	 * The timezone.
	 *
	 * @since  1.0.0
	 * @var    string    $timezone    The timezone.
	 */
	private $timezone = 'UTC';

	/**
	 * The main query filter.
	 *
	 * @since  1.0.0
	 * @var    array    $filter    The main query filter.
	 */
	private $filter = [];

	/**
	 * The human query filter.
	 *
	 * @since  1.0.0
	 * @var    array    $human_filter    The huma query filter.
	 */
	private $human_filter = [];

	/**
	 * The query filter fro the previous range.
	 *
	 * @since  1.0.0
	 * @var    array    $previous    The query filter fro the previous range.
	 */
	private $previous = [];

	/**
	 * Is the start date today's date.
	 *
	 * @since  1.0.0
	 * @var    boolean    $today    Is the start date today's date.
	 */
	private $is_today = false;

	/**
	 * Colors for graphs.
	 *
	 * @since  1.0.0
	 * @var    array    $colors    The colors array.
	 */
	private $colors = [ '#73879C', '#3398DB', '#9B59B6', '#b2c326', '#BDC3C6' ];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string  $site    The site to analyze (all or ID).
	 * @param   string  $start   The start date.
	 * @param   string  $end     The end date.
	 * @param   boolean $reload  Is it a reload of an already displayed analytics.
	 * @since    1.0.0
	 */
	public function __construct( $site, $start, $end, $reload ) {
		if ( Role::LOCAL_ADMIN === Role::admin_type() ) {
			$site = get_current_blog_id();
		}
		$this->site = $site;
		if ( 'all' !== $site ) {
			$this->filter[]   = "site='" . $site . "'";
			$this->previous[] = "site='" . $site . "'";
		}
		if ( $start === $end ) {
			$this->filter[] = "timestamp='" . $start . "'";
		} else {
			$this->filter[] = "timestamp>='" . $start . "' and timestamp<='" . $end . "'";
		}
		$this->start          = $start;
		$this->end            = $end;
		$this->human_filter[] = "class <> 'other'";
		if ( class_exists( 'PODeviceDetector\API\Device' ) ) {
			$this->human_filter[] = "client <> 'other'";
		}
		$this->timezone = Timezone::network_get();
		$datetime       = new \DateTime( 'now', $this->timezone );
		$this->is_today = ( $this->start === $datetime->format( 'Y-m-d' ) || $this->end === $datetime->format( 'Y-m-d' ) );
		$start          = new \DateTime( $this->start, $this->timezone );
		$end            = new \DateTime( $this->end, $this->timezone );
		$start->sub( new \DateInterval( 'P1D' ) );
		$end->sub( new \DateInterval( 'P1D' ) );
		$delta = $start->diff( $end, true );
		if ( $delta ) {
			$start->sub( $delta );
			$end->sub( $delta );
		}
		$this->duration = $delta->days + 1;
		if ( $start === $end ) {
			$this->previous[] = "timestamp='" . $start->format( 'Y-m-d' ) . "'";
		} else {
			$this->previous[] = "timestamp>='" . $start->format( 'Y-m-d' ) . "' and timestamp<='" . $end->format( 'Y-m-d' ) . "'";
		}
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $query   The query type.
	 * @param   mixed  $queried The query params.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	public function query( $query, $queried ) {
		switch ( $query ) {
			case 'kpi':
				return $this->query_kpi( $queried );
			case 'map':
				return $this->query_map();
			case 'languages':
				return $this->query_pie( $query, (int) $queried );



			case 'top-browsers':
				return $this->query_top( 'browsers', (int) $queried );
			case 'top-bots':
				return $this->query_top( 'bots', (int) $queried );
			case 'top-devices':
				return $this->query_top( 'devices', (int) $queried );
			case 'top-oses':
				return $this->query_top( 'oses', (int) $queried );
			case 'top-versions':
				return $this->query_top( 'versions', (int) $queried );
			case 'classes':
			case 'types':
			case 'clients':
			case 'libraries':
			case 'applications':
			case 'feeds':
			case 'medias':
				return $this->query_pie( $query, (int) $queried );
			case 'classes-list':
			case 'types-list':
			case 'clients-list':
			case 'libraries-list':
			case 'applications-list':
			case 'feeds-list':
			case 'medias-list':
			case 'sites':
				return $this->query_list( $query );
			case 'browsers-list':
			case 'bots-list':
			case 'devices-list':
			case 'oses-list':
				return $this->query_extended_list( $query );
			case 'main-chart':
				return $this->query_chart();
		}
		return [];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type    The type of pie.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_pie( $type, $limit ) {
		$uuid = UUID::generate_unique_id( 5 );
		switch ( $type ) {
			case 'languages':
				$data     = Schema::get_grouped_list( $this->filter, 'language', ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
				$selector = 'language';
				$names    = [];
				$size     = 220;
				break;
		}
		if ( 0 < count( $data ) ) {
			$total = 0;
			$other = 0;
			foreach ( $data as $key => $row ) {
				$total = $total + $row['sum_hit'];
				if ( $limit <= $key || 'other' === $row[ $selector ] ) {
					$other = $other + $row['sum_hit'];
				}
			}
			$cpt    = 0;
			$labels = [];
			$series = [];
			while ( $cpt < $limit && array_key_exists( $cpt, $data ) ) {
				if ( 'other' !== $data[ $cpt ][ $selector ] ) {
					if ( 0 < $total ) {
						$percent = round( 100 * $data[ $cpt ]['sum_hit'] / $total, 1 );
					} else {
						$percent = 100;
					}
					if ( 0.1 > $percent ) {
						$percent = 0.1;
					}
					if ( 0 < count( $names ) ) {
						$meta = $names[ $data[ $cpt ][ $selector ] ];
					} elseif ( 'language' === $selector ) {
						$meta = L10n::get_lang_name( $data[ $cpt ][ $selector ] );
					} else {
						$meta = $data[ $cpt ][ $selector ];
					}
					$labels[] = $meta;
					$series[] = [
						'meta'  => $meta,
						'value' => (float) $percent,
					];
				}
				++$cpt;
			}
			if ( 0 < $other ) {
				if ( 0 < $total ) {
					$percent = round( 100 * $other / $total, 1 );
				} else {
					$percent = 100;
				}
				if ( 0.1 > $percent ) {
					$percent = 0.1;
				}
				$labels[] = esc_html__( 'Other', 'ip-locator' );
				$series[] = [
					'meta'  => esc_html__( 'Other', 'ip-locator' ),
					'value' => (float) $percent,
				];
			}
			$result  = '<div class="iplocator-pie-box">';
			$result .= '<div class="iplocator-pie-graph">';
			$result .= '<div class="iplocator-pie-graph-handler-' . $size . '" id="iplocator-pie-' . $type . '"></div>';
			$result .= '</div>';
			$result .= '<div class="iplocator-pie-legend">';
			foreach ( $labels as $key => $label ) {
				$icon    = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'square', $this->colors[ $key ], $this->colors[ $key ] ) . '" />';
				$result .= '<div class="iplocator-pie-legend-item">' . $icon . '&nbsp;&nbsp;' . $label . '</div>';
			}
			$result .= '';
			$result .= '</div>';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var data' . $uuid . ' = ' . wp_json_encode(
					[
						'labels' => $labels,
						'series' => $series,
					]
				) . ';';
			$result .= ' var tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: true, appendToBody: true});';
			$result .= ' var option' . $uuid . ' = {width: ' . $size . ', height: ' . $size . ', showLabel: false, donut: true, donutWidth: "40%", startAngle: 270, plugins: [tooltip' . $uuid . ']};';
			$result .= ' new Chartist.Pie("#iplocator-pie-' . $type . '", data' . $uuid . ', option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
		} else {
			$result  = '<div class="iplocator-pie-box">';
			$result .= '<div class="iplocator-pie-graph" style="margin:0 !important;">';
			$result .= '<div class="iplocator-pie-graph-nodata-handler-' . $size . '" id="iplocator-pie-' . $type . '"><span style="position: relative; top: 37px;">-&nbsp;' . esc_html__( 'No Data', 'ip-locator' ) . '&nbsp;-</span></div>';
			$result .= '</div>';
			$result .= '';
			$result .= '</div>';
			$result .= '</div>';
		}
		return [ 'iplocator-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string  $type    The type of top.
	 * @param   integer $limit  The number to display.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_top( $type, $limit ) {
		switch ( $type ) {
			case 'browsers':
				$data = Schema::get_grouped_list( $this->filter, 'client_id', ! $this->is_today, 'client', [ 'browser' ], false, 'ORDER BY sum_hit DESC' );
				break;
			case 'bots':
				$data = Schema::get_grouped_list( $this->filter, 'name', ! $this->is_today, 'class', [ 'bot' ], false, 'ORDER BY sum_hit DESC' );
				break;
			case 'devices':
				$data = Schema::get_grouped_list( $this->filter, 'brand, model', ! $this->is_today, 'class', [ 'desktop', 'mobile' ], false, 'ORDER BY sum_hit DESC' );
				break;
			case 'oses':
				$data = Schema::get_grouped_list( $this->filter, 'os', ! $this->is_today, 'class', [ 'desktop', 'mobile' ], false, 'ORDER BY sum_hit DESC' );
				break;
			case 'versions':
				switch ( $this->type ) {
					case 'browser':
						$data = Schema::get_grouped_list( $this->filter, 'client_version', ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
						break;
				}
				switch ( $this->type ) {
					case 'os':
						$data = Schema::get_grouped_list( $this->filter, 'os_version', ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
						break;
				}
				break;
			default:
				$data = [];
				break;
		}
		$total = 0;
		$other = 0;
		foreach ( $data as $key => $row ) {
			$total = $total + $row['sum_hit'];
			if ( $limit <= $key ) {
				$other = $other + $row['sum_hit'];
			}
		}
		$result = '';
		$cpt    = 0;
		while ( $cpt < $limit && array_key_exists( $cpt, $data ) ) {
			if ( 0 < $total ) {
				$percent = round( 100 * $data[ $cpt ]['sum_hit'] / $total, 1 );
			} else {
				$percent = 100;
			}
			if ( 0.5 > $percent ) {
				$percent = 0.5;
			}
			switch ( $type ) {
				case 'browsers':
					$text = $data[ $cpt ]['name'];
					$icon = Morpheus\Icons::get_browser_base64( $data[ $cpt ]['client_id'] );
					$url  = $this->get_url(
						[],
						[
							'type' => 'browser',
							'id'   => $data[ $cpt ]['client_id'],
						]
					);
					break;
				case 'bots':
					$text = $data[ $cpt ]['name'];
					$icon = Favicon::get_base64( $data[ $cpt ]['url'] );
					$url  = $this->get_url(
						[],
						[
							'type' => 'bot',
							'id'   => $data[ $cpt ]['name'],
						]
					);
					break;
				case 'devices':
					$text = ( isset( $data[ $cpt ]['brand'] ) && '-' !== $data[ $cpt ]['brand'] ? $data[ $cpt ]['brand'] : esc_html__( 'Generic', 'ip-locator' ) ) . ( isset( $data[ $cpt ]['model'] ) && '-' !== $data[ $cpt ]['model'] ? ' ' . $data[ $cpt ]['model'] : '' );
					$icon = Morpheus\Icons::get_brand_base64( $data[ $cpt ]['brand'] );
					$url  = $this->get_url(
						[],
						[
							'type'     => 'device',
							'id'       => $data[ $cpt ]['brand_id'],
							'extended' => $data[ $cpt ]['model'],
						]
					);
					break;
				case 'oses':
					switch ( $this->type ) {
						case 'device':
							$text = $data[ $cpt ]['os'] . ' ' . $data[ $cpt ]['os_version'];
							$icon = Morpheus\Icons::get_os_base64( $data[ $cpt ]['os_id'] );
							$url  = '';
							break;
						default:
							$text = $data[ $cpt ]['os'];
							$icon = Morpheus\Icons::get_os_base64( $data[ $cpt ]['os_id'] );
							$url  = $this->get_url(
								[],
								[
									'type' => 'os',
									'id'   => $data[ $cpt ]['os_id'],
								]
							);
							break;
					}
					break;
				case 'versions':
					switch ( $this->type ) {
						case 'browser':
							$text = $data[ $cpt ]['name'] . ' ' . $data[ $cpt ]['client_version'];
							$icon = Morpheus\Icons::get_browser_base64( $data[ $cpt ]['client_id'] );
							$url  = '';
							break;
						case 'os':
							$text = $data[ $cpt ]['os'] . ' ' . $data[ $cpt ]['os_version'];
							$icon = Morpheus\Icons::get_os_base64( $data[ $cpt ]['os_id'] );
							$url  = '';
							break;
					}
					break;
			}
			if ( '' !== $url ) {
				$url = '<a href="' . $url . '">' . $text . '</a>';
			} else {
				$url = $text;
			}
			$result .= '<div class="iplocator-top-line">';
			$result .= '<div class="iplocator-top-line-title">';
			$result .= '<img style="width:16px;vertical-align:bottom;" src="' . $icon . '" />&nbsp;&nbsp;<span class="iplocator-top-line-title-text">' . $url . '</span>';
			$result .= '</div>';
			$result .= '<div class="iplocator-top-line-content">';
			$result .= '<div class="iplocator-bar-graph"><div class="iplocator-bar-graph-value" style="width:' . $percent . '%"></div></div>';
			$result .= '<div class="iplocator-bar-detail">' . Conversion::number_shorten( $data[ $cpt ]['sum_hit'], 2, false, '&nbsp;' ) . '</div>';
			$result .= '</div>';
			$result .= '</div>';
			++$cpt;
		}
		if ( 0 < $total ) {
			$percent = round( 100 * $other / $total, 1 );
		} else {
			$percent = 100;
		}
		$result .= '<div class="iplocator-top-line iplocator-minor-data">';
		$result .= '<div class="iplocator-top-line-title">';
		$result .= '<span class="iplocator-top-line-title-text">' . esc_html__( 'Other', 'ip-locator' ) . '</span>';
		$result .= '</div>';
		$result .= '<div class="iplocator-top-line-content">';
		$result .= '<div class="iplocator-bar-graph"><div class="iplocator-bar-graph-value" style="width:' . $percent . '%"></div></div>';
		$result .= '<div class="iplocator-bar-detail">' . Conversion::number_shorten( $other, 2, false, '&nbsp;' ) . '</div>';
		$result .= '</div>';
		$result .= '</div>';
		return [ 'iplocator-top-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @param   string $type    The type of list.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_list( $type ) {
		switch ( $type ) {
			case 'classes-list':
				$data     = Schema::get_grouped_list( $this->filter, 'class, channel', ! $this->is_today, '', [], false, 'ORDER BY class DESC' );
				$selector = 'class';
				break;
			case 'types-list':
				$data     = Schema::get_grouped_list( $this->filter, 'device, channel', ! $this->is_today, '', [], false, 'ORDER BY device DESC' );
				$selector = 'device';
				break;
			case 'clients-list':
				$data     = Schema::get_grouped_list( $this->filter, 'client, channel', ! $this->is_today, '', [], false, 'ORDER BY client DESC' );
				$selector = 'client';
				break;
			case 'libraries-list':
				$data     = Schema::get_grouped_list( $this->filter, 'name, channel', ! $this->is_today, 'client', [ 'library' ], false, 'ORDER BY name DESC' );
				$selector = 'name';
				break;
			case 'applications-list':
				$data     = Schema::get_grouped_list( $this->filter, 'name, channel', ! $this->is_today, 'client', [ 'mobile-app' ], false, 'ORDER BY name DESC' );
				$selector = 'name';
				break;
			case 'feeds-list':
				$data     = Schema::get_grouped_list( $this->filter, 'name, channel', ! $this->is_today, 'client', [ 'feed-reader' ], false, 'ORDER BY name DESC' );
				$selector = 'name';
				break;
			case 'medias-list':
				$data     = Schema::get_grouped_list( $this->filter, 'name, channel', ! $this->is_today, 'client', [ 'media-player' ], false, 'ORDER BY name DESC' );
				$selector = 'name';
				break;
			case 'sites':
				$data     = Schema::get_grouped_list( $this->filter, 'site, channel', ! $this->is_today, '', [], false, 'ORDER BY site DESC' );
				$selector = 'site';
				break;
		}
		if ( 0 < count( $data ) ) {
			$columns = [ 'wfront', 'wback', 'api', 'cron' ];
			$d       = [];
			$current = '';
			$total   = 0;
			foreach ( $data as $row ) {
				if ( $current !== $row[ $selector ] ) {
					$current = $row[ $selector ];
					foreach ( $columns as $column ) {
						$d[ $current ][ $column ] = 0;
					}
					$d[ $current ]['other'] = 0;
					$d[ $current ]['total'] = 0;
					$d[ $current ]['perct'] = 0.0;
				}
				if ( in_array( $row['channel'], $columns, true ) ) {
					$d[ $current ][ $row['channel'] ] = $row['sum_hit'];
				} else {
					$d[ $current ]['other'] += $row['sum_hit'];
				}
				$d[ $current ]['total'] += $row['sum_hit'];
				$total                  += $row['sum_hit'];
			}
			uasort( $d, function ( $a, $b ) { if ( $a['total'] === $b['total'] ) { return 0; } return ( $a['total'] > $b['total'] ) ? -1 : 1 ;} );
			$result  = '<table class="iplocator-table">';
			$result .= '<tr>';
			$result .= '<th>&nbsp;</th>';
			foreach ( $columns as $column ) {
				$result .= '<th>' . ChannelTypes::$channel_names[ strtoupper( $column ) ] . '</th>';
			}
			$result .= '<th>' . __( 'Other', 'ip-locator' ) . '</th>';
			$result .= '<th>' . __( 'TOTAL', 'ip-locator' ) . '</th>';
			$result .= '</tr>';
			foreach ( $d as $name => $item ) {
				$row_str = '<tr>';
				if ( 'classes-list' === $type ) {
					$name = ClassTypes::$class_names[ $name ];
				}
				if ( 'types-list' === $type ) {
					$name = DeviceTypes::$device_names[ $name ];
				}
				if ( 'clients-list' === $type ) {
					$name = ClientTypes::$client_names[ $name ];
				}
				$row_str .= '<td data-th="name">' . $name . '</td>';
				foreach ( $columns as $column ) {
					$row_str .= '<td data-th="' . $column . '">' . Conversion::number_shorten( $item[ $column ], 2, false, '&nbsp;' ) . '</td>';
				}
				$row_str .= '<td data-th="other">' . Conversion::number_shorten( $item['other'], 2, false, '&nbsp;' ) . '</td>';
				$row_str .= '<td data-th="total">' . Conversion::number_shorten( $item['total'], 2, false, '&nbsp;' ) . '</td>';
				$row_str .= '</tr>';
				$result  .= $row_str;
			}
			$result .= '</table>';
		} else {
			$result   = '<table class="iplocator-table">';
			$result  .= '<tr>';
			$result  .= '<th>&nbsp;</th>';
			$result  .= '</tr>';
			$row_str  = '<tr>';
			$row_str .= '<td data-th="" style="color:#73879C;text-align:center;">' . esc_html__( 'No Data', 'ip-locator' ) . '</td>';
			$row_str .= '</tr>';
			$result  .= $row_str;
			$result  .= '</table>';
		}
		return [ 'iplocator-' . $type => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @return array The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_chart() {
		$uuid       = UUID::generate_unique_id( 5 );
		$data_total = Schema::get_time_series( $this->filter, ! $this->is_today );
		$call_max   = 0;
		$hits       = [];
		$start      = '';
		if ( 0 < count( $data_total ) ) {
			foreach ( $data_total as $timestamp => $row ) {
				if ( '' === $start ) {
					$start = $timestamp;
				}
				$ts  = 'new Date(' . (string) strtotime( $timestamp ) . '000)';
				$val = $row['sum_hit'];
				if ( $val > $call_max ) {
					$call_max = $val;
				}
				$hits[] = [
					'x' => $ts,
					'y' => $val,
				];
			}
			$before = [
				'x' => 'new Date(' . (string) ( strtotime( $start ) - 86400 ) . '000)',
				'y' => 'null',
			];
			$after  = [
				'x' => 'new Date(' . (string) ( strtotime( $timestamp ) + 86400 ) . '000)',
				'y' => 'null',
			];
			// Hits.
			$short       = Conversion::number_shorten( $call_max, 2, true );
			$call_max    = 0.5 + floor( $call_max / $short['divisor'] );
			$call_abbr   = $short['abbreviation'];
			$series_hits = [];
			foreach ( $hits as $item ) {
				$item['y']     = $item['y'] / $short['divisor'];
				$series_hits[] = $item;
			}
			array_unshift( $series_hits, $before );
			$series_hits[] = $after;
			$json_call     = wp_json_encode(
				[
					'series' => [
						[
							'name' => esc_html__( 'Hits', 'ip-locator' ),
							'data' => $series_hits,
						],
					],
				]
			);
			$json_call     = str_replace( '"x":"new', '"x":new', $json_call );
			$json_call     = str_replace( ')","y"', '),"y"', $json_call );
			$json_call     = str_replace( '"null"', 'null', $json_call );

			// Rendering.
			if ( 4 < $this->duration ) {
				if ( 1 === $this->duration % 2 ) {
					$divisor = 6;
				} else {
					$divisor = 5;
				}
			} else {
				$divisor = $this->duration + 1;
			}
			$result  = '<div class="iplocator-multichart-handler">';
			$result .= '<div class="iplocator-multichart-item active" id="iplocator-chart-calls">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var call_data' . $uuid . ' = ' . $json_call . ';';
			$result .= ' var call_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var call_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: true,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [call_tooltip' . $uuid . '],';
			$result .= '  axisX: {scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
			$result .= '  axisY: {type: Chartist.AutoScaleAxis, low: 0, high: ' . $call_max . ', labelInterpolationFnc: function (value) {return value.toString() + " ' . $call_abbr . '";}},';
			$result .= ' };';
			$result .= ' new Chartist.Line("#iplocator-chart-calls", call_data' . $uuid . ', call_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '</div>';
		} else {
			$result  = '<div class="iplocator-multichart-handler">';
			$result .= '<div class="iplocator-multichart-item active" id="iplocator-chart-calls">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
		}
		return [ 'iplocator-main-chart' => $result ];
	}

	/**
	 * Query statistics table.
	 *
	 * @return array The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_map() {
		$uuid   = UUID::generate_unique_id( 5 );
		$data   = Schema::get_grouped_list( array_merge( $this->filter, [ "class='public'"] ), 'country', ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
		$series = [];
		foreach ( $data as $datum ) {
			if ( array_key_exists( 'country', $datum ) && ! empty( $datum['country'] ) ) {
				$series[ strtoupper( $datum['country'] ) ] = $datum['sum_hit'];
			}
		}
		$plus    = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'plus-square', 'none', '#73879C' ) . '"/>';
		$minus   = '<img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'minus-square', 'none', '#73879C' ) . '"/>';
		$result  = '<div class="iplocator-map-handler">';
		$result .= '</div>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' var mapdata' . $uuid . ' = ' . wp_json_encode( $series ) . ';';
		$result .= ' $(".iplocator-map-handler").vectorMap({';
		$result .= ' map: "world_mill",';
		$result .= ' backgroundColor: "#FFFFFF",';
		$result .= ' series: {';
		$result .= '  regions: [{';
		$result .= '   values: mapdata' . $uuid . ',';
		$result .= '   scale: ["#BDC7D1", "#73879C"],';
		$result .= '   normalizeFunction: "polynomial"';
		$result .= '  }]';
		$result .= ' },';
		$result .= '  regionStyle: {';
		$result .= '   initial: {fill: "#EEEEEE", "fill-opacity": 0.7},';
		$result .= '   hover: {"fill-opacity": 1,cursor: "default"},';
		$result .= '   selected: {},';
		$result .= '   selectedHover: {},';
		$result .= ' },';
		$result .= ' onRegionTipShow: function(e, el, code){if (mapdata' . $uuid . '[code]){el.html(el.html() + " (" + mapdata' . $uuid . '[code] + " ' . esc_html__( 'hits', 'iplocator' ) . ')")};},';
		$result .= ' });';
		$result .= ' $(".jvectormap-zoomin").html(\'' . $plus . '\');';
		$result .= ' $(".jvectormap-zoomout").html(\'' . $minus . '\');';
		$result .= '});';
		$result .= '</script>';
		return [ 'iplocator-map' => $result ];
	}

	/**
	 * Query all kpis in statistics table.
	 *
	 * @param   array   $args   Optional. The needed args.
	 * @return array  The KPIs ready to send.
	 * @since    1.0.0
	 */
	public static function get_status_kpi_collection( $args = [] ) {
		$result['meta'] = [
			'plugin' => IPLOCATOR_PRODUCT_NAME . ' ' . IPLOCATOR_VERSION,
			'period' => date( 'Y-m-d' ),
		];
		if ( Environment::is_wordpress_multisite() ) {
			if ( 0 === $args['site_id'] ) {
				$result['meta']['scope'] = 'Network';
			} else {
				$result['meta']['scope'] = Blog::get_full_blog_name( $args['site_id'] );
			}
		} else {
			$result['meta']['scope'] = Blog::get_full_blog_name( 1 );
		}
		if ( 0 === $args['site_id'] ) {
			$args['site_id'] = 'all';
		}
		$result['data'] = [];
		$kpi            = new static( $args['site_id'], date( 'Y-m-d' ), date( 'Y-m-d' ), false );
		foreach ( [ 'hit', 'mobile', 'desktop', 'bot', 'client', 'engine' ] as $query ) {
			$data = $kpi->query_kpi( $query, false );
			switch ( $query ) {
				case 'country':
					$val                    = Conversion::number_shorten( $data['kpi-main-' . $query ], 1, true );
					$result['data'][$query] = [
						'name'        => esc_html_x( 'Countries', 'Noun - Countries.', 'ip-locator' ),
						'short'       => esc_html_x( 'Cntr', 'Noun - Short (max 4 char) - Countries.', 'ip-locator' ),
						'description' => esc_html__( 'Accessing countries - real humans only (browsers or apps).', 'ip-locator' ),
						'dimension'   => 'none',
						'ratio'       => null,
						'variation'   => [
							'raw'      => round( $data['kpi-index-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-index-' . $query], 2 ),
							'permille' => round( $data['kpi-index-' . $query] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-main-' . $query],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'language':
					$val                    = Conversion::number_shorten( $data['kpi-main-' . $query ], 1, true );
					$result['data'][$query] = [
						'name'        => esc_html_x( 'Languages', 'Noun - Languages.', 'ip-locator' ),
					'short'       => esc_html_x( 'Lang', 'Noun - Short (max 4 char) - Countries.', 'ip-locator' ),
						'description' => esc_html__( 'Main languages of accessing countries - real humans only (browsers or apps).', 'ip-locator' ),
						'dimension'   => 'none',
						'ratio'       => null,
						'variation'   => [
					'raw'      => round( $data['kpi-index-' . $query] / 100, 6 ),
					'percent'  => round( $data['kpi-index-' . $query], 2 ),
					'permille' => round( $data['kpi-index-' . $query] * 10, 2 ),
				],
						'value'       => [
					'raw'   => $data['kpi-main-' . $query],
					'human' => $val['value'] . $val['abbreviation'],
				],
					];
					break;
				case 'public':
					$val                    = Conversion::number_shorten( $data['kpi-bottom-' . $query], 0, true );
					$result['data'][$query] = [
						'name'        => esc_html_x( 'Public', 'Noun - Percentage of hits from public IPs.', 'ip-locator' ),
						'short'       => esc_html_x( 'Pub.', 'Noun - Short (max 4 char) - Percentage of hits from public IPs.', 'ip-locator' ),
						'description' => esc_html__( 'Ratio of hits done from public IPs.', 'ip-locator' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-main-' . $query], 2 ),
							'permille' => round( $data['kpi-main-' . $query] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-index-' . $query], 2 ),
							'permille' => round( $data['kpi-index--' . $query] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-' . $query],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'private':
					$val                    = Conversion::number_shorten( $data['kpi-bottom-' . $query], 0, true );
					$result['data'][$query] = [
						'name'        => esc_html_x( 'Local', 'Noun - Percentage of hits from private IPs.', 'ip-locator' ),
						'short'       => esc_html_x( 'Loc.', 'Noun - Short (max 4 char) - Percentage of hits from private IPs.', 'ip-locator' ),
						'description' => esc_html__( 'Ratio of hits done from private IPs.', 'ip-locator' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-main-' . $query], 2 ),
							'permille' => round( $data['kpi-main-' . $query] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-index-' . $query], 2 ),
							'permille' => round( $data['kpi-index--' . $query] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-' . $query],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'satellite':
					$val                    = Conversion::number_shorten( $data['kpi-bottom-' . $query], 0, true );
					$result['data'][$query] = [
						'name'        => esc_html_x( 'Satellite', 'Noun - Percentage of hits from satellite IPs.', 'ip-locator' ),
						'short'       => esc_html_x( 'Sat.', 'Noun - Short (max 4 char) - Percentage of hits from satellite IPs.', 'ip-locator' ),
						'description' => esc_html__( 'Ratio of hits done from satellite IPs.', 'ip-locator' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-main-' . $query], 2 ),
							'permille' => round( $data['kpi-main-' . $query] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-index-' . $query], 2 ),
							'permille' => round( $data['kpi-index--' . $query] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-' . $query],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'detection':
					$val                    = Conversion::number_shorten( $data['kpi-bottom-' . $query], 0, true );
					$result['data'][$query] = [
						'name'        => esc_html_x( 'Detection', 'Noun - Percentage of detected IPs.', 'ip-locator' ),
						'short'       => esc_html_x( 'Dtc.', 'Noun - Short (max 4 char) - Percentage of detected IPs.', 'ip-locator' ),
						'description' => esc_html__( 'Ratio of detected IPs (eliminates reserved, unknown and behing anonymous proxies IPs).', 'ip-locator' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-main-' . $query], 2 ),
							'permille' => round( $data['kpi-main-' . $query] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-index-' . $query], 2 ),
							'permille' => round( $data['kpi-index--' . $query] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-' . $query],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;

			}
		}
		$result['assets'] = [];
		return $result;
	}

	/**
	 * Query statistics table.
	 *
	 * @param   mixed       $queried The query params.
	 * @param   boolean     $chart   Optional, return the chart if true, only the data if false;
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	public function query_kpi( $queried, $chart = true ) {
		$result = [];
		switch ( $queried ) {
			case 'public':
			case 'private':
			case 'satellite':
			case 'detection':
				$data  = Schema::get_grouped_kpi( $this->filter, 'country', ! $this->is_today );
				$pdata = Schema::get_grouped_kpi( $this->previous, 'country' );
				break;
			case 'country':
			case 'language':
				$data  = Schema::get_distinct_kpi( array_merge( $this->filter, $this->human_filter ), [ $queried ], ! $this->is_today );
				$pdata = Schema::get_distinct_kpi( array_merge( $this->previous, $this->human_filter ), [ $queried ] );
				break;
		}
		if ( 'country' === $queried || 'language' === $queried ) {
			$current                          = (int) count( $data );
			$previous                         = (int) count( $pdata );
			$result[ 'kpi-main-' . $queried ] = (int) round( $current, 0 );
			if ( ! $chart ) {
				if ( 0.0 !== $current && 0.0 !== $previous ) {
					$result[ 'kpi-index-' . $queried ] = round( 100 * ( $current - $previous ) / $previous, 4 );
				} else {
					$result[ 'kpi-index-' . $queried ] = null;
				}
				$result[ 'kpi-bottom-' . $queried ] = null;
				return $result;
			}
			$result[ 'kpi-main-' . $queried ] = Conversion::number_shorten( (int) $current, 1, false, '&nbsp;' );
			if ( 0 !== $current && 0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0 === $previous && 0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0 !== $previous && 100 !== $previous && 0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
		}
		if ( 'public' === $queried || 'private' === $queried || 'satellite' === $queried || 'detection' === $queried ) {
			$base_value  = 0.0;
			$pbase_value = 0.0;
			$data_value  = 0.0;
			$pdata_value = 0.0;
			$current     = 0.0;
			$previous    = 0.0;
			foreach ( $data as $row ) {
				$base_value = $base_value + (float) $row['sum_hit'];
				if ( $row['class'] === $queried && 'detection' !== $queried ) {
					$data_value = (float) $row['sum_hit'];
				}
				if ( 'other' !== $row['class'] && 'detection' === $queried ) {
					$data_value += (float) $row['sum_hit'];
				}
			}
			foreach ( $pdata as $row ) {
				$pbase_value = $pbase_value + (float) $row['sum_hit'];
				if ( $row['class'] === $queried && 'detection' !== $queried ) {
					$pdata_value = (float) $row['sum_hit'];
				}
				if ( 'other' !== $row['class'] && 'detection' === $queried ) {
					$pdata_value += (float) $row['sum_hit'];
				}
			}
			if ( 0.0 !== $base_value && 0.0 !== $data_value ) {
				$current                          = 100 * $data_value / $base_value;
				$result[ 'kpi-main-' . $queried ] = round( $current, $chart ? 1 : 4 );
			} else {
				if ( 0.0 !== $data_value ) {
					$result[ 'kpi-main-' . $queried ] = 100;
				} elseif ( 0.0 !== $base_value ) {
					$result[ 'kpi-main-' . $queried ] = 0;
				} else {
					$result[ 'kpi-main-' . $queried ] = null;
				}
			}
			if ( 0.0 !== $pbase_value && 0.0 !== $pdata_value ) {
				$previous = 100 * $pdata_value / $pbase_value;
			} else {
				if ( 0.0 !== $pdata_value ) {
					$previous = 100.0;
				}
			}
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$result[ 'kpi-index-' . $queried ] = round( 100 * ( $current - $previous ) / $previous, 4 );
			} else {
				$result[ 'kpi-index-' . $queried ] = null;
			}
			if ( ! $chart ) {
				$result[ 'kpi-bottom-' . $queried ] = round( $data_value, 0 );
				return $result;
			}
			if ( isset( $result[ 'kpi-main-' . $queried ] ) ) {
				$result[ 'kpi-main-' . $queried ] = $result[ 'kpi-main-' . $queried ] . '&nbsp;%';
			} else {
				$result[ 'kpi-main-' . $queried ] = '-';
			}
			if ( 0.0 !== $current && 0.0 !== $previous ) {
				$percent = round( 100 * ( $current - $previous ) / $previous, 1 );
				if ( 0.1 > abs( $percent ) ) {
					$percent = 0;
				}
				$result[ 'kpi-index-' . $queried ] = '<span style="color:' . ( 0 <= $percent ? '#18BB9C' : '#E74C3C' ) . ';">' . ( 0 < $percent ? '+' : '' ) . $percent . '&nbsp;%</span>';
			} elseif ( 0.0 === $previous && 0.0 !== $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#18BB9C;">+∞</span>';
			} elseif ( 0.0 !== $previous && 100 !== $previous && 0.0 === $current ) {
				$result[ 'kpi-index-' . $queried ] = '<span style="color:#E74C3C;">-∞</span>';
			}
			$result[ 'kpi-bottom-' . $queried ] = '<span class="iplocator-kpi-large-bottom-text">' . sprintf( esc_html__( '%s hits', 'ip-locator' ), Conversion::number_shorten( (int) $data_value, 2, false, '&nbsp;' ) ) . '</span>';
		}
		return $result;
	}

	/**
	 * Get the site selection bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_site_bar() {
		if ( Role::SINGLE_ADMIN === Role::admin_type() ) {
			return '';
		}
		if ( 'all' === $this->site ) {
			$result = '<span class="iplocator-site-text">' . esc_html__( 'All Sites', 'ip-locator' ) . '</span>';
		} else {
			if ( Role::SUPER_ADMIN === Role::admin_type() ) {
				$quit   = '<a href="' . $this->get_url( [ 'site' ] ) . '"><img style="width:12px;vertical-align:text-top;" src="' . Feather\Icons::get_base64( 'x-circle', 'none', '#FFFFFF' ) . '" /></a>';
				$result = '<span class="iplocator-site-button">' . sprintf( esc_html__( 'Site ID %s', 'ip-locator' ), $this->site ) . $quit . '</span>';
			} else {
				$result = '<span class="iplocator-site-text">' . sprintf( esc_html__( 'Site ID %s', 'ip-locator' ), $this->site ) . '</span>';
			}
		}
		return '<span class="iplocator-site">' . $result . '</span>';
	}

	/**
	 * Get the title bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_title_bar() {
		$result  = '<div class="iplocator-box iplocator-box-full-line">';
		$result .= $this->get_site_bar();
		$result .= '<span class="iplocator-title">' . esc_html__( 'Main Summary', 'ip-locator' ) . '</span>';
		$result .= '<span class="iplocator-subtitle"></span>';
		$result .= '<span class="iplocator-datepicker">' . $this->get_date_box() . '</span>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the box_title.
	 *
	 * @param string $id The box or page id.
	 * @return string  The box title.
	 * @since 1.0.0
	 */
	public function get_box_title( $id ) {
		$result = '';
		switch ( $id ) {
			case 'classes-list':
				$result = esc_html__( 'All Classes', 'ip-locator' );
				break;
			case 'types-list':
				$result = esc_html__( 'All Device Types', 'ip-locator' );
				break;
			case 'clients-list':
				$result = esc_html__( 'All Client Types', 'ip-locator' );
				break;
			case 'libraries-list':
				$result = esc_html__( 'All Libraries', 'ip-locator' );
				break;
			case 'applications-list':
				$result = esc_html__( 'All Mobile Applications', 'ip-locator' );
				break;
			case 'feeds-list':
				$result = esc_html__( 'All Feed Readers', 'ip-locator' );
				break;
			case 'medias-list':
				$result = esc_html__( 'All Media Players', 'ip-locator' );
				break;
			case 'browsers-list':
				$result = esc_html__( 'All Browsers', 'ip-locator' );
				break;
			case 'bots-list':
				$result = esc_html__( 'All Bots', 'ip-locator' );
				break;
			case 'devices-list':
				$result = esc_html__( 'All Devices', 'ip-locator' );
				break;
			case 'oses-list':
				$result = esc_html__( 'All Operating Systems', 'ip-locator' );
				break;
		}
		return $result;
	}

	/**
	 * Get the KPI bar.
	 *
	 * @return string  The bar ready to print.
	 * @since    1.0.0
	 */
	public function get_kpi_bar() {
		$result  = '<div class="iplocator-box iplocator-box-full-line">';
		$result .= '<div class="iplocator-kpi-bar">';
		$result .= '<div class="iplocator-kpi-large">' . $this->get_large_kpi( 'country' ) . '</div>';
		$result .= '<div class="iplocator-kpi-large">' . $this->get_large_kpi( 'language' ) . '</div>';
		$result .= '<div class="iplocator-kpi-large">' . $this->get_large_kpi( 'detection' ) . '</div>';
		$result .= '<div class="iplocator-kpi-large">' . $this->get_large_kpi( 'public' ) . '</div>';
		$result .= '<div class="iplocator-kpi-large">' . $this->get_large_kpi( 'private' ) . '</div>';
		$result .= '<div class="iplocator-kpi-large">' . $this->get_large_kpi( 'satellite' ) . '</div>';
		$result .= '</div>';
		$result .= '</div>';
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_map_box() {
		$result  = '<div class="iplocator-60-module">';
		$result .= '<div class="iplocator-module-title-bar"><span class="iplocator-module-title">' . esc_html__( 'Countries', 'iplocator' ) . '</span></div>';
		$result .= '<div class="iplocator-module-content" id="iplocator-map">' . $this->get_graph_placeholder( 310 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'map',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the language pie.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_language_box() {
		$result  = '<div class="iplocator-40-module">';
		$result .= '<div class="iplocator-module-title-bar"><span class="iplocator-module-title">' . esc_html__( 'Top Languages', 'ip-locator' ) . '</span></div>';
		$result .= '<div class="iplocator-module-content" id="iplocator-languages">' . $this->get_graph_placeholder( 310 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'languages',
				'queried' => 5,
			]
		);
		return $result;
	}

	/**
	 * Get a large kpi box.
	 *
	 * @param   string $kpi     The kpi to render.
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_large_kpi( $kpi ) {
		switch ( $kpi ) {
			case 'country':
				$icon  = Feather\Icons::get_base64( 'flag', 'none', '#73879C' );
				$title = esc_html_x( 'Countries', 'Noun - Countries.', 'ip-locator' );
				$help  = esc_html__( 'Accessing countries - real humans only (browsers or apps).', 'ip-locator' );
				break;
			case 'language':
				$icon  = Feather\Icons::get_base64( 'award', 'none', '#73879C' );
				$title = esc_html_x( 'Languages', 'Noun - Languages.', 'ip-locator' );
				$help  = esc_html__( 'Main languages of accessing countries - real humans only (browsers or apps).', 'ip-locator' );
				break;
			case 'public':
				$icon  = Feather\Icons::get_base64( 'globe', 'none', '#73879C' );
				$title = esc_html_x( 'Public', 'Noun - Percentage of hits from public IPs.', 'ip-locator' );
				$help  = esc_html__( 'Ratio of hits done from public IPs.', 'ip-locator' );
				break;
			case 'private':
				$icon  = Feather\Icons::get_base64( 'home', 'none', '#73879C' );
				$title = esc_html_x( 'Local', 'Noun - Percentage of hits from private IPs.', 'ip-locator' );
				$help  = esc_html__( 'Ratio of hits done from private IPs.', 'ip-locator' );
				break;
			case 'satellite':
				$icon  = Feather\Icons::get_base64( 'radio', 'none', '#73879C' );
				$title = esc_html_x( 'Satellite', 'Noun - Percentage of hits from satellite IPs.', 'ip-locator' );
				$help  = esc_html__( 'Ratio of hits done from satellite IPs.', 'ip-locator' );
				break;
			case 'detection':
				$icon  = Feather\Icons::get_base64( 'crosshair', 'none', '#73879C' );
				$title = esc_html_x( 'Detection', 'Noun - Percentage of detected IPs.', 'ip-locator' );
				$help  = esc_html__( 'Ratio of detected IPs (eliminates reserved, unknown and behing anonymous proxies IPs).', 'ip-locator' );
				break;
		}
		$top       = '<img style="width:12px;vertical-align:baseline;" src="' . $icon . '" />&nbsp;&nbsp;<span style="cursor:help;" class="iplocator-kpi-large-top-text bottom" data-position="bottom" data-tooltip="' . $help . '">' . $title . '</span>';
		$indicator = '&nbsp;';
		$bottom    = '<span class="iplocator-kpi-large-bottom-text">&nbsp;</span>';
		$result    = '<div class="iplocator-kpi-large-top">' . $top . '</div>';
		$result   .= '<div class="iplocator-kpi-large-middle"><div class="iplocator-kpi-large-middle-left" id="kpi-main-' . $kpi . '">' . $this->get_value_placeholder() . '</div><div class="iplocator-kpi-large-middle-right" id="kpi-index-' . $kpi . '">' . $indicator . '</div></div>';
		$result   .= '<div class="iplocator-kpi-large-bottom" id="kpi-bottom-' . $kpi . '">' . $bottom . '</div>';
		$result   .= $this->get_refresh_script(
			[
				'query'   => 'kpi',
				'queried' => $kpi,
			]
		);
		return $result;
	}

	/**
	 * Get a placeholder for graph.
	 *
	 * @param   integer $height The height of the placeholder.
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_graph_placeholder( $height ) {
		return '<p style="text-align:center;line-height:' . $height . 'px;"><img style="width:40px;vertical-align:middle;" src="' . IPLOCATOR_ADMIN_URL . 'medias/bars.svg" /></p>';
	}

	/**
	 * Get a placeholder for graph with no data.
	 *
	 * @param   integer $height The height of the placeholder.
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_graph_placeholder_nodata( $height ) {
		return '<p style="color:#73879C;text-align:center;line-height:' . $height . 'px;">' . esc_html__( 'No Data', 'ip-locator' ) . '</p>';
	}

	/**
	 * Get a placeholder for value.
	 *
	 * @return string  The placeholder, ready to print.
	 * @since    1.0.0
	 */
	private function get_value_placeholder() {
		return '<img style="width:26px;vertical-align:middle;" src="' . IPLOCATOR_ADMIN_URL . 'medias/three-dots.svg" />';
	}

	/**
	 * Get refresh script.
	 *
	 * @param   array $args Optional. The args for the ajax call.
	 * @return string  The script, ready to print.
	 * @since    1.0.0
	 */
	private function get_refresh_script( $args = [] ) {
		$result  = '<script>';
		$result .= 'jQuery(document).ready( function($) {';
		$result .= ' var data = {';
		$result .= '  action:"iplocator_get_stats",';
		$result .= '  nonce:"' . wp_create_nonce( 'ajax_iplocator' ) . '",';
		foreach ( $args as $key => $val ) {
			$s = '  ' . $key . ':';
			if ( is_string( $val ) ) {
				$s .= '"' . $val . '"';
			} elseif ( is_numeric( $val ) ) {
				$s .= $val;
			} elseif ( is_bool( $val ) ) {
				$s .= $val ? 'true' : 'false';
			}
			$result .= $s . ',';
		}
		$result .= '  site:"' . $this->site . '",';
		$result .= '  start:"' . $this->start . '",';
		$result .= '  end:"' . $this->end . '",';
		$result .= ' };';
		$result .= ' $.post(ajaxurl, data, function(response) {';
		$result .= ' var val = JSON.parse(response);';
		$result .= ' $.each(val, function(index, value) {$("#" + index).html(value);});';
		if ( array_key_exists( 'query', $args ) && 'main-chart' === $args['query'] ) {
			$result .= '$(".iplocator-chart-button").removeClass("not-ready");';
			$result .= '$("#iplocator-chart-button-calls").addClass("active");';
		}
		$result .= ' });';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

	/**
	 * Get the url.
	 *
	 * @param   array   $exclude Optional. The args to exclude.
	 * @param   array   $replace Optional. The args to replace or add.
	 * @param   boolean $escape  Optional. Forces url escaping.
	 * @return string  The url.
	 * @since    1.0.0
	 */
	private function get_url( $exclude = [], $replace = [], $escape = true ) {
		$params         = [];
		$params['site'] = $this->site;
		$params['start'] = $this->start;
		$params['end']   = $this->end;
		foreach ( $exclude as $arg ) {
			unset( $params[ $arg ] );
		}
		foreach ( $replace as $key => $arg ) {
			$params[ $key ] = $arg;
		}
		$url = admin_url( 'admin.php?page=iplocator-viewer' );
		foreach ( $params as $key => $arg ) {
			if ( '' !== $arg ) {
				$url .= '&' . $key . '=' . rawurlencode( $arg );
			}
		}
		$url = str_replace( '"', '\'\'', $url );
		if ( $escape ) {
			$url = esc_url( $url );
		}
		return $url;
	}

	/**
	 * Get a date picker box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	private function get_date_box() {
		$result  = '<img style="width:13px;vertical-align:middle;" src="' . Feather\Icons::get_base64( 'calendar', 'none', '#5A738E' ) . '" />&nbsp;&nbsp;<span class="iplocator-datepicker-value"></span>';
		$result .= '<script>';
		$result .= 'jQuery(function ($) {';
		$result .= ' moment.locale("' . L10n::get_display_locale() . '");';
		$result .= ' var start = moment("' . $this->start . '");';
		$result .= ' var end = moment("' . $this->end . '");';
		$result .= ' function changeDate(start, end) {';
		$result .= '  $("span.iplocator-datepicker-value").html(start.format("LL") + " - " + end.format("LL"));';
		$result .= ' }';
		$result .= ' $(".iplocator-datepicker").daterangepicker({';
		$result .= '  opens: "left",';
		$result .= '  startDate: start,';
		$result .= '  endDate: end,';
		$result .= '  minDate: moment("' . Schema::get_oldest_date() . '"),';
		$result .= '  maxDate: moment(),';
		$result .= '  showCustomRangeLabel: true,';
		$result .= '  alwaysShowCalendars: true,';
		$result .= '  locale: {customRangeLabel: "' . esc_html__( 'Custom Range', 'ip-locator' ) . '",cancelLabel: "' . esc_html__( 'Cancel', 'ip-locator' ) . '", applyLabel: "' . esc_html__( 'Apply', 'ip-locator' ) . '"},';
		$result .= '  ranges: {';
		$result .= '    "' . esc_html__( 'Today', 'ip-locator' ) . '": [moment(), moment()],';
		$result .= '    "' . esc_html__( 'Yesterday', 'ip-locator' ) . '": [moment().subtract(1, "days"), moment().subtract(1, "days")],';
		$result .= '    "' . esc_html__( 'This Month', 'ip-locator' ) . '": [moment().startOf("month"), moment().endOf("month")],';
		$result .= '    "' . esc_html__( 'Last Month', 'ip-locator' ) . '": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")],';
		$result .= '  }';
		$result .= ' }, changeDate);';
		$result .= ' changeDate(start, end);';
		$result .= ' $(".iplocator-datepicker").on("apply.daterangepicker", function(ev, picker) {';
		$result .= '  var url = "' . $this->get_url( [ 'start', 'end' ], [], false ) . '" + "&start=" + picker.startDate.format("YYYY-MM-DD") + "&end=" + picker.endDate.format("YYYY-MM-DD");';
		$result .= '  $(location).attr("href", url);';
		$result .= ' });';
		$result .= '});';
		$result .= '</script>';
		return $result;
	}

}
