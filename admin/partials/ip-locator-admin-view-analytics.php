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






		<?php /*if ( 'summary' === $analytics->type ) { ?>
            <div class="podd-row">
                <div class="podd-box podd-box-50-50-line">
					<?php echo $analytics->get_top_browser_box() ?>
					<?php echo $analytics->get_top_bot_box() ?>
                </div>
            </div>
            <div class="podd-row">
                <div class="podd-box podd-box-33-33-33-line">
					<?php echo $analytics->get_classes_box() ?>
					<?php echo $analytics->get_types_box() ?>
					<?php echo $analytics->get_clients_box() ?>
                </div>
            </div>
            <div class="podd-row">
                <div class="podd-box podd-box-50-50-line">
					<?php echo $analytics->get_top_device_box() ?>
					<?php echo $analytics->get_top_os_box() ?>
                </div>
            </div>
            <div class="podd-row">
                <div class="podd-box podd-box-25-25-25-25-line">
					<?php echo $analytics->get_libraries_box() ?>
					<?php echo $analytics->get_applications_box() ?>
					<?php echo $analytics->get_feeds_box() ?>
					<?php echo $analytics->get_medias_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'browser' === $analytics->type ) { ?>
            <div class="podd-row">
                <div class="podd-box podd-box-50-50-line">
					<?php echo $analytics->get_simpletop_version_box() ?>
					<?php echo $analytics->get_simpletop_os_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'os' === $analytics->type ) { ?>
            <div class="podd-row">
                <div class="podd-box podd-box-50-50-line">
					<?php echo $analytics->get_simpletop_version_box() ?>
					<?php echo $analytics->get_simpletop_browser_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'device' === $analytics->type ) { ?>
            <div class="podd-row">
                <div class="podd-box podd-box-50-50-line">
					<?php echo $analytics->get_simpletop_os_box() ?>
					<?php echo $analytics->get_simpletop_browser_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'browser' === $analytics->type || 'os' === $analytics->type || 'device' === $analytics->type || 'bot' === $analytics->type ) { ?>
			<?php echo $analytics->get_main_chart() ?>
		<?php } ?>
		<?php if ( 'summary' === $analytics->type && Role::SUPER_ADMIN === Role::admin_type() && 'all' === $analytics->site) { ?>
            <div class="podd-row last-row">
				<?php echo $analytics->get_sites_list() ?>
            </div>
		<?php } */?>
    </div>
</div>
