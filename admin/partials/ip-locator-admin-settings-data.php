<?php
/**
 * Provide a admin-facing view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

use IPLocator\System\Environment;
use IPLocator\System\Option;
use IPLocator\Plugin\Feature\Schema;
use IPLocator\System\Timezone;
use IPLocator\System\Hosting;

$schema = new Schema();
$ipv4   = sprintf( esc_html__( '%d IP ranges updated on %s.', 'ip-locator' ), $schema->count_ranges( 'v4' ) , wp_date( get_option( 'date_format' ), Option::network_get( 'dbversion_v4' ), Timezone::network_get() ) );
$ipv6   = sprintf( esc_html__( '%d IP ranges updated on %s.', 'ip-locator' ), $schema->count_ranges( 'v6' ) , wp_date( get_option( 'date_format' ), Option::network_get( 'dbversion_v6' ), Timezone::network_get() ) );

$transcript = '';
foreach ( Option::network_get( 'infolog' ) as $s ) {
    if ( 0 < strlen( $transcript ) ) {
        $transcript = $transcript . "\n";
    }
    $transcript = $transcript . wp_date( 'Y-m-d H:i:s', $s['timestamp'], Timezone::network_get() ) . ' / ' . $s['message'];
}

?>
<h2><?php echo esc_html__( 'IP detection' ); ?></h2>
<p>
    <?php echo sprintf( esc_html__( '%s main data: ', 'ip-locator' ), 'IPv4' ); ?><strong><?php echo sprintf( esc_html__( '%s / donationware.', 'ip-locator' ), '<a href="http://software77.net/geo-ip/?license">WebNet77</a>' ); ?></strong>
    <br/>
	<?php echo esc_html__( 'Content', 'ip-locator' ) . ': '; ?><strong><?php echo $ipv4; ?></strong>
</p>
<p>
	<?php echo sprintf( esc_html__( '%s main data: ', 'ip-locator' ), 'IPv6' ); ?><strong><?php echo sprintf( esc_html__( '%s / donationware.', 'ip-locator' ), '<a href="http://software77.net/geo-ip/?license">WebNet77</a>' ); ?></strong>
    <br/>
	<?php echo esc_html__( 'Content', 'ip-locator' ) . ': '; ?><strong><?php echo $ipv6; ?></strong>
</p>

<?php if (Environment::has_phpgeoip_installed()) { ?>
    <p><?php echo sprintf( esc_html__( '%s fallback data: ', 'ip-locator' ), 'IPv4/IPv6' ); ?><strong><?php echo Environment::phpgeoip_version_text(); ?></strong></p>
<?php } ?>
<p><?php echo esc_html__( 'Apache mod_geoip reception: ', 'ip-locator' ); ?><strong><?php echo Hosting::is_apache_geoip_enabled() ? esc_html__( 'active', 'ip-locator' ) : esc_html__( 'inactive', 'ip-locator' ); ?></strong></p>
<p><?php echo esc_html__( 'AWS CloudFront reception: ', 'ip-locator' ); ?><strong><?php echo Hosting::is_cloudfront_geoip_enabled() ? esc_html__( 'active', 'ip-locator' ) : esc_html__( 'inactive', 'ip-locator' ); ?></strong></p>
<p><?php echo esc_html__( 'Cloudflare IP Geolocation reception: ', 'ip-locator' ); ?><strong><?php echo Hosting::is_cloudflare_geoip_enabled() ? esc_html__( 'active', 'ip-locator' ) : esc_html__( 'inactive', 'ip-locator' ); ?></strong></p>
<p><?php echo esc_html__( 'Google Cloud-LB reception: ', 'ip-locator' ); ?><strong><?php echo Hosting::is_googlelb_geoip_enabled() ? esc_html__( 'active', 'ip-locator' ) : esc_html__( 'inactive', 'ip-locator' ); ?></strong></p>

<h2><?php esc_html_e( 'Last operations transcript', 'ip-locator' ); ?></h2>
<textarea style="width:100%;resize:none;white-space: pre;overflow-wrap:normal;overflow-x:scroll;line-height:1.6em;font-size:smaller;font-family:'Courier New', Courier, monospace" rows="10" readonly>
<?php echo $transcript; ?>
</textarea>


