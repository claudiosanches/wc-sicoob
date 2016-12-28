<?php
/**
 * Payment instructions.
 *
 * @author  Claudio Sanches
 * @package WC_Sicoob/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<div class="woocommerce-message">
	<span>
		<a class="button" href="<?php echo esc_url( $url ); ?>" target="_blank" style="display: block !important; visibility: visible !important;"><?php esc_html_e( 'Print bank slip', 'wc-sicoob' ); ?></a>
		<?php esc_html_e( 'Please use the link below get your bank slip:', 'wc-sicoob' ); ?>
		<br />
		<?php esc_html_e( 'After we receive the payment confirmation, your order will be processed.', 'wc-sicoob' ); ?>
	</span>
</div>
