<?php
/**
 * Admin View: Notice - Currency not supported.
 *
 * @package WC_Sicoob/Admin/Notices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="error inline">
	<p><strong><?php _e( 'Gateway Disabled', 'wc-sicoob' ); ?></strong>: <?php printf( __( 'Currency <code>%s</code> is not supported. Works only with Brazilian Real.', 'wc-sicoob' ), get_woocommerce_currency() ); ?>
	</p>
</div>
