<?php
/**
 * Admin help message.
 *
 * @package WC_Sicoob/Admin/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( apply_filters( 'wc_sicoob_help_message', true ) ) : ?>
	<div class="updated inline woocommerce-message">
		<p><?php echo esc_html( sprintf( __( 'Help us keep the %s plugin free making a donation or rate %s on WordPress.org. Thank you in advance!', 'wc-sicoob' ), __( 'Sicoob Com&eacute;rcio Eletr&ocirc;nico for WooCommerce', 'wc-sicoob' ), '&#9733;&#9733;&#9733;&#9733;&#9733;' ) ); ?></p>
		<p><a href="https://claudiosmweb.com/doacoes/" target="_blank" class="button button-primary"><?php esc_html_e( 'Make a donation', 'wc-sicoob' ); ?></a> <a href="https://wordpress.org/support/view/plugin-reviews/wc-sicoob?filter=5#postform" target="_blank" class="button button-secondary"><?php esc_html_e( 'Make a review', 'wc-sicoob' ); ?></a></p>
	</div>
<?php endif;
