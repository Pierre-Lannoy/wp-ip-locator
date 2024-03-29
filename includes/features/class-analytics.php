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
use Morpheus;
use IPLocator\API\Flag;


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
	private $colors = [ '#73879C', '#3398DB', '#9B59B6', '#B2C326', '#FFA5A5', '#A5F8D3', '#FEE440', '#BDC3C6' ];

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
		$this->human_filter[] = "class <> 'cron'";
		$this->human_filter[] = "class <> 'cli'";
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
			case 'channel-list':
			case 'client-list':
				return $this->query_list( $query );
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
				$data     = Schema::get_grouped_list( array_merge( $this->filter, [ "class='public'"], $this->human_filter ), 'language', ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
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
	 * @param   string $type    The type of list.
	 * @return array  The result of the query, ready to encode.
	 * @since    1.0.0
	 */
	private function query_list( $type ) {
		switch ( $type ) {
			case 'channel-list':
				$data     = Schema::get_grouped_list( $this->filter, 'country, channel', ! $this->is_today, '', [], false, 'ORDER BY country DESC' );
				$selector = 'country';
				$field    = 'channel';
				$columns  = [ 'wfront', 'wback', 'api', 'cron' ];
				break;
			case 'client-list':
				$data     = Schema::get_grouped_list( $this->filter, 'country, client', ! $this->is_today, '', [], false, 'ORDER BY country DESC' );
				$selector = 'country';
				$field    = 'client';
				$columns  = [ 'browser', 'mobile-app', 'library', 'media-player' ];
				break;
		}
		if ( 0 < count( $data ) ) {

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
				if ( in_array( $row[ $field ], $columns, true ) ) {
					$d[ $current ][ $row[ $field ] ] = $row['sum_hit'];
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
				if ( 'channel-list' === $type ) {
					$result .= '<th>' . ChannelTypes::$channel_names[ strtoupper( $column ) ] . '</th>';
				}
				if ( 'client-list' === $type ) {
					if ( class_exists( 'PODeviceDetector\Plugin\Feature\ClientTypes' ) ) {
						$result .= '<th>' . \PODeviceDetector\Plugin\Feature\ClientTypes::$client_names[ $column ] . '</th>';
					} else {
						$result .= '<th>' . $column . '</th>';
					}
				}
			}
			$result .= '<th>' . __( 'Other', 'ip-locator' ) . '</th>';
			$result .= '<th>' . __( 'TOTAL', 'ip-locator' ) . '</th>';
			$result .= '</tr>';
			foreach ( $d as $name => $item ) {
				$row_str = '<tr>';
				if ( 'channel-list' === $type || 'client-list' === $type ) {
					$f    = new Flag( $name );
					$name = $f->image( '', 'width:14px;padding-left:4px;padding-right:4px;vertical-align:baseline;' ) . '&nbsp;' . L10n::get_country_name( $name );
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
		$uuid             = UUID::generate_unique_id( 5 );
		$query            = [];
		$item             = [];
		$item['country']  = [ 'country' ];
		$item['language'] = [ 'language' ];
		$item['access']   = [ 'public', 'private', 'satellite', 'other' ];
		foreach ( Schema::get_counted_time_series( array_merge( $this->filter, [ "class='public'"], $this->human_filter ), ! $this->is_today, '', [], false ) as $row ) {
			$i                        = [];
			$i['timestamp']           = $row['timestamp'];
			$i['country']             = $row['cnt_country'];
			$i['language']            = $row['cnt_language'];
			$i['public']              = 0;
			$i['private']             = 0;
			$i['satellite']           = 0;
			$i['other']               = 0;
			$query[ $i['timestamp'] ] = $i;
		}
		foreach ( $item['access'] as $key ) {
			foreach ( Schema::get_time_series( array_merge( $this->filter, [ "class='" . $key . "'"] ), ! $this->is_today, '', [], false ) as $row ) {
				$i              = [];
				$i['timestamp'] = $row['timestamp'];
				$i['country']   = 0;
				$i['language']  = 0;
				$i['public']    = 0;
				$i['private']   = 0;
				$i['satellite'] = 0;
				$i['other']     = 0;
				$i[ $key ]      = $row['sum_hit'];
				if ( array_key_exists( $i['timestamp'], $query ) ) {
					$query[ $i['timestamp'] ][ $key ] = $row['sum_hit'];
				} else {
					$query[ $i['timestamp'] ] = $i;
				}
			}
		}
		$data       = [];
		$series     = [];
		$labels     = [];
		$boundaries = [];
		$json       = [];
		foreach ( $item as $selector => $array ) {
			$boundaries[ $selector ] = [
				'max'    => 0,
				'factor' => 1,
				'order'  => $item[ $selector ],
			];
		}
		// Data normalization.
		if ( 0 !== count( $query ) ) {
			foreach ( $query as  $row ) {
				$datetime = new \DateTime( $row['timestamp'], new \DateTimeZone( 'UTC' ) );
				$datetime->setTimezone( $this->timezone );
				$record = [];
				foreach ( $row as $k => $v ) {
					if ( 'timestamp' !== $k ) {
						$record[ $k ] = (int) $v;
					}
				}
				$data[ strtotime( $datetime->format( 'Y-m-d' ) . ' 12:00:00' ) ] = $record;
			}
			// Boundaries computation.
			foreach ( $data as $datum ) {
				foreach ( array_merge( $item['country'], $item['language'], $item['access'] ) as $field ) {
					foreach ( $item as $selector => $array ) {
						if ( in_array( $field, $array, true ) ) {
							if ( $boundaries[ $selector ]['max'] < $datum[ $field ] ) {
								$boundaries[ $selector ]['max'] = $datum[ $field ];
								if ( 1100 < $datum[ $field ] ) {
									$boundaries[ $selector ]['factor'] = 1000;
								}
								if ( 1100000 < $datum[ $field ] ) {
									$boundaries[ $selector ]['factor'] = 1000000;
								}
								$boundaries[ $selector ]['order'] = array_diff( $boundaries[ $selector ]['order'], [ $field ] );
								array_unshift( $boundaries[ $selector ]['order'], $field );
							}
							continue 2;
						}
					}
				}
			}
			// Series computation.
			foreach ( $data as $timestamp => $datum ) {
				// Series.
				$ts = 'new Date(' . (string) $timestamp . '000)';
				foreach ( array_merge( $item['country'], $item['language'], $item['access'] ) as $key ) {
					foreach ( $item as $selector => $array ) {
						if ( in_array( $key, $array, true ) ) {
							$series[ $key ][] = [
								'x' => $ts,
								'y' => round( $datum[ $key ] / $boundaries[ $selector ]['factor'], ( 1 === $boundaries[ $selector ]['factor'] ? 0 : 2 ) ),
							];
							continue 2;
						}
					}
				}
				// Labels.
				$labels[] = 'moment(' . $timestamp . '000).format("ll")';
			}
			// Result encoding.
			$shift    = 86400;
			$datetime = new \DateTime( $this->start . ' 00:00:00', $this->timezone );
			$offset   = $this->timezone->getOffset( $datetime );
			$datetime = $datetime->getTimestamp() + $offset;
			array_unshift( $labels, 'moment(' . (string) ( $datetime - $shift ) . '000).format("ll")' );
			$before   = [
				'x' => 'new Date(' . (string) ( $datetime - $shift ) . '000)',
				'y' => 'null',
			];
			$datetime = new \DateTime( $this->end . ' 23:59:59', $this->timezone );
			$offset   = $this->timezone->getOffset( $datetime );
			$datetime = $datetime->getTimestamp() + $offset;
			$after    = [
				'x' => 'new Date(' . (string) ( $datetime + $shift ) . '000)',
				'y' => 'null',
			];
			foreach ( array_merge( $item['country'], $item['language'], $item['access'] ) as $key ) {
				array_unshift( $series[ $key ], $before );
				$series[ $key ][] = $after;
			}
			// Users.
			foreach ( $item as $selector => $array ) {
				$serie = [];
				foreach ( $boundaries[ $selector ]['order'] as $field ) {
					switch ( $field ) {
						case 'country':
							$name = esc_html__( 'Countries', 'ip-locator' );
							break;
						case 'language':
							$name = esc_html__( 'Languages', 'ip-locator' );
							break;
						case 'public':
							$name = esc_html__( 'Public', 'ip-locator' );
							break;
						case 'private':
							$name = esc_html__( 'Local', 'ip-locator' );
							break;
						case 'other':
							$name = esc_html__( 'Other', 'ip-locator' );
							break;
						case 'satellite':
							$name = esc_html__( 'Satellite', 'ip-locator' );
							break;
						default:
							$name = esc_html__( 'Unknown', 'ip-locator' );
					}
					$serie[] = [
						'name' => $name,
						'data' => $series[ $field ],
					];
				}
				if ( 'turnover' === $selector || 'log' === $selector ) {
					$json[ $selector ] = wp_json_encode(
						[
							'labels' => $labels,
							'series' => $serie,
						]
					);
				} else {
					$json[ $selector ] = wp_json_encode( [ 'series' => $serie ] );
				}
				$json[ $selector ] = str_replace( '"x":"new', '"x":new', $json[ $selector ] );
				$json[ $selector ] = str_replace( ')","y"', '),"y"', $json[ $selector ] );
				$json[ $selector ] = str_replace( '"null"', 'null', $json[ $selector ] );
				$json[ $selector ] = str_replace( '"labels":["moment', '"labels":[moment', $json[ $selector ] );
				$json[ $selector ] = str_replace( '","moment', ',moment', $json[ $selector ] );
				$json[ $selector ] = str_replace( '"],"series":', '],"series":', $json[ $selector ] );
				$json[ $selector ] = str_replace( '\\"', '"', $json[ $selector ] );
			}

			// Rendering.
			$divisor = $this->duration + 1;
			while ( 11 < $divisor ) {
				foreach ( [ 2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47, 53, 59, 61, 67, 71, 73, 79, 83, 89, 97, 101, 103, 107, 109, 113, 127, 131, 137, 139, 149, 151, 157, 163, 167, 173, 179, 181, 191, 193, 197, 199, 211, 223, 227, 229, 233, 239, 241, 251, 257, 263, 269, 271, 277, 281, 283, 293, 307, 311, 313, 317, 331, 337, 347, 349, 353, 359, 367, 373, 379, 383, 389, 397 ] as $divider ) {
					if ( 0 === $divisor % $divider ) {
						$divisor = $divisor / $divider;
						break;
					}
				}
			}
			$result  = '<div class="iplocator-multichart-handler">';
			$result .= '<div class="iplocator-multichart-item active" id="iplocator-chart-country">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var country_data' . $uuid . ' = ' . $json['country'] . ';';
			$result .= ' var country_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var country_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: true,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [country_tooltip' . $uuid . '],';
			$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
			$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString() + " ' . Conversion::number_shorten( $boundaries['country']['factor'], 0, true )['abbreviation'] . '";}},';
			$result .= ' };';
			$result .= ' new Chartist.Line("#iplocator-chart-country", country_data' . $uuid . ', country_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="iplocator-multichart-item" id="iplocator-chart-language">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var language_data' . $uuid . ' = ' . $json['language'] . ';';
			$result .= ' var language_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var language_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: true,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [language_tooltip' . $uuid . '],';
			$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
			$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString() + " ' . Conversion::number_shorten( $boundaries['language']['factor'], 0, true )['abbreviation'] . '";}},';
			$result .= ' };';
			$result .= ' new Chartist.Line("#iplocator-chart-language", language_data' . $uuid . ', language_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
			$result .= '<div class="iplocator-multichart-item" id="iplocator-chart-access">';
			$result .= '</div>';
			$result .= '<script>';
			$result .= 'jQuery(function ($) {';
			$result .= ' var access_data' . $uuid . ' = ' . $json['access'] . ';';
			$result .= ' var access_tooltip' . $uuid . ' = Chartist.plugins.tooltip({percentage: false, appendToBody: true});';
			$result .= ' var access_option' . $uuid . ' = {';
			$result .= '  height: 300,';
			$result .= '  fullWidth: true,';
			$result .= '  showArea: true,';
			$result .= '  showLine: true,';
			$result .= '  showPoint: false,';
			$result .= '  plugins: [access_tooltip' . $uuid . '],';
			$result .= '  axisX: {labelOffset: {x: 3,y: 0},scaleMinSpace: 100, type: Chartist.FixedScaleAxis, divisor:' . $divisor . ', labelInterpolationFnc: function (value) {return moment(value).format("YYYY-MM-DD");}},';
			$result .= '  axisY: {type: Chartist.AutoScaleAxis, labelInterpolationFnc: function (value) {return value.toString() + " ' . Conversion::number_shorten( $boundaries['access']['factor'], 0, true )['abbreviation'] . '";}},';
			$result .= ' };';
			$result .= ' new Chartist.Line("#iplocator-chart-access", access_data' . $uuid . ', access_option' . $uuid . ');';
			$result .= '});';
			$result .= '</script>';
		} else {
			$result  = '<div class="iplocator-multichart-handler">';
			$result .= '<div class="iplocator-multichart-item active" id="iplocator-chart-country">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="iplocator-multichart-item" id="iplocator-chart-language">';
			$result .= $this->get_graph_placeholder_nodata( 274 );
			$result .= '</div>';
			$result .= '<div class="iplocator-multichart-item" id="iplocator-chart-access">';
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
		$data   = Schema::get_grouped_list( array_merge( $this->filter, [ "class='public'"], $this->human_filter ), 'country', ! $this->is_today, '', [], false, 'ORDER BY sum_hit DESC' );
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
			if ( ! isset( $args['site_id'] ) ) {
				$args['site_id'] = 0;
			}
			if ( 0 === $args['site_id'] ) {
				$result['meta']['scope']['site'] = 'Network';
			} else {
				$result['meta']['scope']['site'] = Blog::get_full_blog_name( $args['site_id'] );
			}
		} else {
			if ( ! isset( $args['site_id'] ) ) {
				$args['site_id'] = 1;
			}
			$result['meta']['scope']['site'] = Blog::get_full_blog_name( 1 );
		}
		if ( 0 === $args['site_id'] ) {
			$args['site_id'] = 'all';
		}
		$result['data'] = [];
		$kpi            = new static( $args['site_id'], date( 'Y-m-d' ), date( 'Y-m-d' ), false );
		foreach ( [ 'country', 'language', 'public', 'private', 'satellite', 'detection' ] as $query ) {
			$data = $kpi->query_kpi( $query, false );
			switch ( $query ) {
				case 'country':
					$val                    = Conversion::number_shorten( $data['kpi-main-' . $query ], 0, true );
					$result['data'][$query] = [
						'name'        => esc_html_x( 'Countries', 'Noun - Countries.', 'ip-locator' ),
						'short'       => esc_html_x( 'Cntr', 'Noun - Short (max 4 char) - Countries.', 'ip-locator' ),
						'description' => esc_html__( 'Accessing countries.', 'ip-locator' ),
						'dimension'   => 'none',
						'ratio'       => null,
						'variation'   => [
							'raw'      => round( $data['kpi-index-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-index-' . $query] ?? 0, 2 ),
							'permille' => round( $data['kpi-index-' . $query] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-main-' . $query],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'language':
					$val                    = Conversion::number_shorten( $data['kpi-main-' . $query ], 0, true );
					$result['data'][$query] = [
						'name'        => esc_html_x( 'Languages', 'Noun - Languages.', 'ip-locator' ),
					    'short'       => esc_html_x( 'Lang', 'Noun - Short (max 4 char) - Countries.', 'ip-locator' ),
						'description' => esc_html__( 'Main languages of accessing countries.', 'ip-locator' ),
						'dimension'   => 'none',
						'ratio'       => null,
						'variation'   => [
					'raw'      => round( $data['kpi-index-' . $query] / 100, 6 ),
					'percent'  => round( $data['kpi-index-' . $query] ?? 0, 2 ),
					'permille' => round( $data['kpi-index-' . $query] * 10, 2 ),
				],
						'value'       => [
					'raw'   => $data['kpi-main-' . $query],
					'human' => $val['value'] . $val['abbreviation'],
				],
					];
					break;
				case 'public':
					$val                    = Conversion::number_shorten( $data['kpi-bottom-' . $query], 1, true );
					$result['data'][$query] = [
						'name'        => esc_html_x( 'Public', 'Noun - Hits from public IPs.', 'ip-locator' ),
						'short'       => esc_html_x( 'Pub.', 'Noun - Short (max 4 char) - Hits from public IPs.', 'ip-locator' ),
						'description' => esc_html__( 'Hits from public IPs.', 'ip-locator' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-main-' . $query] ?? 0, 2 ),
							'permille' => round( $data['kpi-main-' . $query] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-index-' . $query] ?? 0, 2 ),
							'permille' => round( $data['kpi-index-' . $query] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-' . $query],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'private':
					$val                    = Conversion::number_shorten( $data['kpi-bottom-' . $query], 1, true );
					$result['data'][$query] = [
						'name'        => esc_html_x( 'Local', 'Noun - Hits from private IPs.', 'ip-locator' ),
						'short'       => esc_html_x( 'Loc.', 'Noun - Short (max 4 char) - Hits from private IPs.', 'ip-locator' ),
						'description' => esc_html__( 'Hits from private IPs.', 'ip-locator' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-main-' . $query] ?? 0, 2 ),
							'permille' => round( $data['kpi-main-' . $query] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-index-' . $query] ?? 0, 2 ),
							'permille' => round( $data['kpi-index-' . $query] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-' . $query],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'satellite':
					$val                    = Conversion::number_shorten( $data['kpi-bottom-' . $query], 1, true );
					$result['data'][$query] = [
						'name'        => esc_html_x( 'Satellite', 'Noun - Hits from satellite IPs.', 'ip-locator' ),
						'short'       => esc_html_x( 'Sat.', 'Noun - Short (max 4 char) - Hits from satellite IPs.', 'ip-locator' ),
						'description' => esc_html__( 'Hits from satellite IPs.', 'ip-locator' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-main-' . $query] ?? 0, 2 ),
							'permille' => round( $data['kpi-main-' . $query] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-index-' . $query] ?? 0, 2 ),
							'permille' => round( $data['kpi-index-' . $query] * 10, 2 ),
						],
						'value'       => [
							'raw'   => $data['kpi-bottom-' . $query],
							'human' => $val['value'] . $val['abbreviation'],
						],
					];
					break;
				case 'detection':
					$val                    = Conversion::number_shorten( $data['kpi-bottom-' . $query], 1, true );
					$result['data'][$query] = [
						'name'        => esc_html_x( 'Detection', 'Noun - Hits from detected IPs.', 'ip-locator' ),
						'short'       => esc_html_x( 'Dtc.', 'Noun - Short (max 4 char) - Hits from detected IPs.', 'ip-locator' ),
						'description' => esc_html__( 'Hits from detected IPs.', 'ip-locator' ),
						'dimension'   => 'none',
						'ratio'       => [
							'raw'      => round( $data['kpi-main-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-main-' . $query] ?? 0, 2 ),
							'permille' => round( $data['kpi-main-' . $query] * 10, 2 ),
						],
						'variation'   => [
							'raw'      => round( $data['kpi-index-' . $query] / 100, 6 ),
							'percent'  => round( $data['kpi-index-' . $query] ?? 0, 2 ),
							'permille' => round( $data['kpi-index-' . $query] * 10, 2 ),
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
				$data  = Schema::get_grouped_kpi( $this->filter, 'class', ! $this->is_today );
				$pdata = Schema::get_grouped_kpi( $this->previous, 'class' );
				break;
			case 'country':
			case 'language':
				$data  = Schema::get_distinct_kpi( array_merge( $this->filter, [ "class='public'"], $this->human_filter ), [ $queried ], ! $this->is_today );
				$pdata = Schema::get_distinct_kpi( array_merge( $this->previous, [ "class='public'"], $this->human_filter ), [ $queried ] );
				break;
		}
		if ( 'country' === $queried || 'language' === $queried ) {
			$current                          = (int) count( $data );
			$previous                         = (int) count( $pdata );
			$result[ 'kpi-main-' . $queried ] = (int) round( $current, 0 );
			if ( ! $chart ) {
				if ( 0 !== $current && 0 !== $previous ) {
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
	 * Get the main chart.
	 *
	 * @return string  The main chart ready to print.
	 * @since    1.0.0
	 */
	public function get_main_chart() {
		if ( 1 < $this->duration ) {
			$help_country   = esc_html__( 'Countries variation.', 'ip-locator' );
			$help_language  = esc_html__( 'Languages variation.', 'ip-locator' );
			$help_access    = esc_html__( 'Access breakdown.', 'ip-locator' );
			$detail         = '<span class="iplocator-chart-button not-ready left" id="iplocator-chart-button-country" data-position="left" data-tooltip="' . $help_country . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'flag', 'none', '#73879C' ) . '" /></span>';
			$detail        .= '&nbsp;&nbsp;&nbsp;<span class="iplocator-chart-button not-ready left" id="iplocator-chart-button-language" data-position="left" data-tooltip="' . $help_language . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'award', 'none', '#73879C' ) . '" /></span>';
			$detail        .= '&nbsp;&nbsp;&nbsp;<span class="iplocator-chart-button not-ready left" id="iplocator-chart-button-access" data-position="left" data-tooltip="' . $help_access . '"><img style="width:12px;vertical-align:baseline;" src="' . Feather\Icons::get_base64( 'crosshair', 'none', '#73879C' ) . '" /></span>';
			$result         = '<div class="iplocator-row">';
			$result        .= '<div class="iplocator-box iplocator-box-full-line">';
			$result        .= '<div class="iplocator-module-title-bar"><span class="iplocator-module-title">' . esc_html__( 'Metrics Variations', 'ip-locator' ) . '<span class="iplocator-module-more">' . $detail . '</span></span></div>';
			$result        .= '<div class="iplocator-module-content" id="iplocator-main-chart">' . $this->get_graph_placeholder( 274 ) . '</div>';
			$result        .= '</div>';
			$result        .= '</div>';
			$result        .= $this->get_refresh_script(
				[
					'query'   => 'main-chart',
					'queried' => 0,
				]
			);
			return $result;
		} else {
			return '';
		}
	}

	/**
	 * Get the channels list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_channel_list() {
		$result  = '<div class="iplocator-box iplocator-box-full-line">';
		$result .= '<div class="iplocator-module-title-bar"><span class="iplocator-module-title">' . esc_html__( 'Channels Breakdown', 'ip-locator' ) . '</span></div>';
		$result .= '<div class="iplocator-module-content" id="iplocator-channel-list">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'channel-list',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the clients list.
	 *
	 * @return string  The table ready to print.
	 * @since    1.0.0
	 */
	public function get_client_list() {
		$result  = '<div class="iplocator-box iplocator-box-full-line">';
		$result .= '<div class="iplocator-module-title-bar"><span class="iplocator-module-title">' . esc_html__( 'Clients Breakdown', 'ip-locator' ) . '</span></div>';
		$result .= '<div class="iplocator-module-content" id="iplocator-client-list">' . $this->get_graph_placeholder( 200 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'client-list',
				'queried' => 0,
			]
		);
		return $result;
	}

	/**
	 * Get the map box.
	 *
	 * @return string  The box ready to print.
	 * @since    1.0.0
	 */
	public function get_map_box() {
		if ( class_exists( 'PODeviceDetector\API\Device' ) ) {
			$title = esc_html__( 'Countries', 'iplocator' ) . ' ' . esc_html__( '(real humans from public IPs only)', 'iplocator' );
		} else {
			$title = esc_html__( 'Countries', 'iplocator' ) . ' ' . esc_html__( '(public IPs only)', 'iplocator' );
		}
		$result  = '<div class="iplocator-60-module">';
		$result .= '<div class="iplocator-module-title-bar"><span class="iplocator-module-title">' . $title . '</span></div>';
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
		if ( class_exists( 'PODeviceDetector\API\Device' ) ) {
			$title = esc_html__( 'Languages', 'iplocator' ) . ' ' . esc_html__( '(real humans from public IPs only)', 'iplocator' );
		} else {
			$title = esc_html__( 'Languages', 'iplocator' ) . ' ' . esc_html__( '(public IPs only)', 'iplocator' );
		}
		$result  = '<div class="iplocator-40-module">';
		$result .= '<div class="iplocator-module-title-bar"><span class="iplocator-module-title">' . $title . '</span></div>';
		$result .= '<div class="iplocator-module-content" id="iplocator-languages">' . $this->get_graph_placeholder( 310 ) . '</div>';
		$result .= '</div>';
		$result .= $this->get_refresh_script(
			[
				'query'   => 'languages',
				'queried' => 7,
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
				if ( class_exists( 'PODeviceDetector\API\Device' ) ) {
					$help = esc_html__( 'Accessing countries - real humans from public IPs only (browsers or apps).', 'ip-locator' );
				} else {
					$help = esc_html__( 'Accessing countries - public IPs only.', 'ip-locator' );
				}
				break;
			case 'language':
				$icon  = Feather\Icons::get_base64( 'award', 'none', '#73879C' );
				$title = esc_html_x( 'Languages', 'Noun - Languages.', 'ip-locator' );
				if ( class_exists( 'PODeviceDetector\API\Device' ) ) {
					$help = esc_html__( 'Main languages of accessing countries - real humans from public IPs only (browsers or apps).', 'ip-locator' );
				} else {
					$help = esc_html__( 'Main languages of accessing countries - public IPs only.', 'ip-locator' );
				}
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
				$icon  = Feather\Icons::get_base64( 'activity', 'none', '#73879C' );
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
			$result .= '$("#iplocator-chart-button-country").addClass("active");';
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
		$params          = [];
		$params['site']  = $this->site;
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
