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

use IPLocator\System\Role;

wp_enqueue_script( 'iplocator-moment-with-locale' );
wp_enqueue_script( 'iplocator-daterangepicker' );
wp_enqueue_script( 'iplocator-chartist' );
wp_enqueue_script( 'iplocator-chartist-tooltip' );
wp_enqueue_script( 'iplocator-jvectormap' );
wp_enqueue_script( 'iplocator-jvectormap-world' );
wp_enqueue_script( IPLOCATOR_ASSETS_ID );
wp_enqueue_style( IPLOCATOR_ASSETS_ID );
wp_enqueue_style( 'iplocator-daterangepicker' );
wp_enqueue_style( 'iplocator-tooltip' );
wp_enqueue_style( 'iplocator-chartist' );
wp_enqueue_style( 'iplocator-chartist-tooltip' );
wp_enqueue_style( 'iplocator-jvectormap' );


?>

<div class="wrap">
    <div class="podd-dashboard">
        <div class="iplocator-row">
			<?php echo $analytics->get_title_bar() ?>
        </div>
        <div class="iplocator-row">
			<?php echo $analytics->get_kpi_bar() ?>
        </div>
        <div class="iplocator-row">
            <div class="iplocator-box iplocator-box-60-40-line">
	            <?php echo $analytics->get_map_box(); ?>
			    <?php echo $analytics->get_language_box(); ?>
            </div>
        </div>
        <div class="iplocator-row">
		    <?php echo $analytics->get_main_chart() ?>
        </div>
        <div class="iplocator-row">
		    <?php echo $analytics->get_channel_list() ?>
        </div>
	    <?php if ( class_exists( 'PODeviceDetector\API\Device' ) ) { ?>
            <div class="iplocator-row">
                <?php echo $analytics->get_client_list() ?>
            </div>
        <?php } ?>
    </div>
</div>
