<?php
/**
 * Plain email instructions.
 *
 * @author  Claudio Sanches
 * @package WC_Sicoob/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

esc_html_e( 'Payment', 'wc-sicoob' );

echo "\n\n";

esc_html_e( 'Please use the link below get your bank slip:', 'wc-sicoob' );

echo "\n";

echo esc_url( $url );

echo "\n";

esc_html_e( 'After we receive the payment confirmation, your order will be processed.', 'wc-sicoob' );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
