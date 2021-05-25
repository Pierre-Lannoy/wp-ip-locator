<?php
/**
 * Provide a admin-facing tools for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

use IPLocator\System\IP;
use IPLocator\System\L10n;

wp_localize_script(
	IPLOCATOR_ASSETS_ID,
	'describer',
	[
		'restUrl'   => esc_url_raw( rest_url() . 'ip-locator/v' . IPLOCATOR_API_VERSION . '/describe' ),
		'restNonce' => wp_create_nonce( 'wp_rest' ),
        'locale'    => L10n::get_display_locale(),
	]
);

wp_enqueue_script( IPLOCATOR_ASSETS_ID );
wp_enqueue_style( IPLOCATOR_ASSETS_ID );

$img = '<img id="iplocator_test_ip_wait" style="display:none;width:22px;vertical-align:middle;" src="' . IPLOCATOR_ADMIN_URL . 'medias/three-dots.svg" />'

?>

<div class="wrap">
	<h2><?php echo esc_html__( 'IP Test', 'ip-locator' ); ?></h2>
    <div class="iplocator_test_ip_container">
        <input class="regular-text" id="iplocator_test_ip_value" placeholder="" type="text" value="<?php echo IP::get_current(); ?>">
        <button id="iplocator_test_ip_action" class="button button-primary"><span id="iplocator_test_ip_text"><?php echo esc_html__( 'Test Now', 'ip-locator' ); ?></span><?php echo $img; ?></button>
    </div>
    <div id="iplocator_test_ip_cdescriber"><div id="iplocator_test_ip_describer" style="display:none"></div></div>
</div>
