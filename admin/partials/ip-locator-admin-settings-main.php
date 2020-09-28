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

// phpcs:ignore
$active_tab = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'misc' );
$url        = esc_url(
	add_query_arg(
		[
			'page' => 'iplocator-viewer',
		],
		admin_url( 'admin.php' )
	)
);

?>

<div class="wrap">

	<h2><?php echo esc_html( sprintf( esc_html__( '%s Settings', 'ip-locator' ), IPLOCATOR_PRODUCT_NAME ) ); ?></h2>
	<?php settings_errors(); ?>

	<h2 class="nav-tab-wrapper">
		<a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'iplocator-settings',
					'tab'  => 'misc',
				),
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'misc' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Options', 'ip-locator' ); ?></a>
		<a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'iplocator-settings',
					'tab'  => 'about',
				),
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'about' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;"><?php esc_html_e( 'About', 'ip-locator' ); ?></a>
		<?php if ( class_exists( 'IPLocator\Plugin\Feature\Wpcli' ) ) { ?>
            <a href="
            <?php
			echo esc_url(
				add_query_arg(
					array(
						'page' => 'iplocator-settings',
						'tab'  => 'wpcli',
					),
					admin_url( 'admin.php' )
				)
			);
			?>
            " class="nav-tab <?php echo 'wpcli' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;">WP-CLI</a>
		<?php } ?>
        <a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'iplocator-settings',
					'tab'  => 'data',
				),
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'data' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;"><?php esc_html_e( 'IP Data', 'ip-locator' ); ?></a>
	</h2>
    
	<?php if ( 'misc' === $active_tab ) { ?>
		<?php include __DIR__ . '/ip-locator-admin-settings-options.php'; ?>
	<?php } ?>
	<?php if ( 'about' === $active_tab ) { ?>
		<?php include __DIR__ . '/ip-locator-admin-settings-about.php'; ?>
	<?php } ?>
	<?php if ( 'wpcli' === $active_tab ) { ?>
		<?php wp_enqueue_style( IPLOCATOR_ASSETS_ID ); ?>
		<?php echo do_shortcode( '[iplocator-wpcli]' ); ?>
	<?php } ?>
	<?php if ( 'data' === $active_tab ) { ?>
		<?php include __DIR__ . '/ip-locator-admin-settings-data.php'; ?>
	<?php } ?>

</div>
