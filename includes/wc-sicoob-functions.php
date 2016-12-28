<?php
/**
 * Sicoob functions.
 *
 * @package WC_Sicoob/Functions
 * @since   1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get bank slip url.
 *
 * @param  string $order_key Order Key.
 *
 * @return string
 */
function wc_sicoob_get_bank_slip_url( $order_key ) {
	$url = WC()->api_request_url( 'WC_Sicoob_Bank_Slip_Gateway' );

	return add_query_arg( array( 'key' => $order_key ), $url );
}

/**
 * Get order meta.
 *
 * @private
 * @param  WC_Order $order Order data.
 * @param  string $key     Meta key.
 * @return mixed
 */
function _wc_sicoob_get_order_meta( $order, $key ) {
	$value = '';

	if ( method_exists( $order, 'get_id' ) ) {
		if ( is_callable( array( $order, "get_{$key}" ) ) ) {
			$value = $order->{"get_{$key}"}();
		} else {
			$value = $order->get_meta( '_' . $key );
		}
	} else {
		$value = $order->$key;
	}

	return $value;
}

/**
 * Update order meta.
 *
 * @private
 * @param WC_Order $order Order data.
 * @param string   $key   Meta key.
 * @param string   $value Meta value.
 */
function _wc_sicoob_update_order_meta( $order, $key, $value ) {
	if ( method_exists( $order, 'update_meta_data' ) ) {
		$order->update_meta_data( '_' . $key, $value );
	} else {
		update_post_meta( $order->id, '_' . $key, $value );
	}
}
