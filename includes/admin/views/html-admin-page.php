<?php
/**
 * Admin options screen.
 *
 * @package WC_Sicoob/Admin/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h2><?php echo esc_html( $this->get_method_title() ); ?></h2>

<?php
	if ( 'yes' == $this->get_option( 'enabled' ) ) {
		if ( ! $this->api->using_supported_currency() && ! class_exists( 'woocommerce_wpml' ) ) {
			include dirname( __FILE__ ) . '/html-notice-currency-not-supported.php';
		}
	}
?>

<?php echo wp_kses_post( wpautop( $this->get_method_description() ) ); ?>

<?php include dirname( __FILE__ ) . '/html-admin-help-message.php'; ?>

<table class="form-table">
	<?php $this->generate_settings_html(); ?>
</table>
