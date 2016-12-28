<?php
/**
 * Sicoob Bank Slip payment gateway.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @package WC_Sicoob/Classes/Payment_Gateway
 * @since   1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sicoob Bank Slip gateway class.
 */
class WC_Sicoob_Bank_Slip_Gateway extends WC_Payment_Gateway {

	/**
	 * API instance.
	 *
	 * @var WC_Sicoob_API
	 */
	protected $api = null;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'sicoob-bank-slip';
		$this->method_title       = __( 'BB Com&eacute;rcio Eletr&ocirc;nico - Bank Slip', 'wc-sicoob' );
		$this->method_description = __( 'Accept payments by bank slip using your account on Sicoob Com&eacute;rcio Eletr&ocirc;nico.', 'wc-sicoob' );
		$this->icon               = apply_filters( 'wc_sicoob_bank_slip_icon', plugins_url( 'assets/images/bank-slip.png', plugin_dir_path( __FILE__ ) ) );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Options.
		$this->title           = $this->get_option( 'title' );
		$this->description     = $this->get_option( 'description' );
		$this->agreement_code  = $this->get_option( 'agreement_code' );
		$this->collection_code = $this->get_option( 'collection_code' );
		$this->days_to_pay     = $this->get_option( 'days_to_pay' );
		$this->instructions    = $this->get_option( 'instructions' );
		$this->debug           = $this->get_option( 'debug' );
		$this->api             = new WC_Sicoob_API( $this );

		// Actions.
		add_action( 'woocommerce_api_wc_sicoob_bank_slip_gateway', array( $this, 'payment_redirect' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Returns a value indicating the the Gateway is available or not.
	 *
	 * @return bool
	 */
	public function is_available() {
		return parent::is_available() && ! empty( $this->agreement_code ) && $this->api->using_supported_currency();
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'wc-sicoob' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Sicoob - Bank Slip', 'wc-sicoob' ),
				'default' => 'no',
			),
			'title' => array(
				'title'       => __( 'Title', 'wc-sicoob' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc-sicoob' ),
				'desc_tip'    => true,
				'default'     => __( 'Bank Slip', 'wc-sicoob' ),
			),
			'description' => array(
				'title'       => __( 'Description', 'wc-sicoob' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'wc-sicoob' ),
				'desc_tip'    => true,
				'default'     => __( 'Pay with bank slip.', 'wc-sicoob' ),
			),
			'integration' => array(
				'title'       => __( 'Integration Settings', 'wc-sicoob' ),
				'type'        => 'title',
				'description' => '',
			),
			'agreement_code' => array(
				'title'             => __( 'Agreement Code', 'wc-sicoob' ),
				'type'              => 'text',
				'description'       => __( 'Please enter your Agreement Code for eCommerce provided by the your bank. This is needed in order to take payment.', 'wc-sicoob' ),
				'desc_tip'          => true,
				'default'           => '',
				'custom_attributes' => array(
					'required' => 'required',
				),
			),
			'collection_code' => array(
				'title'             => __( 'Collection Agreement Code', 'wc-sicoob' ),
				'type'              => 'text',
				'description'       => __( 'Please enter your Collection Agreement Code if necessary. This may be needed in order to take payment.', 'wc-sicoob' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'behavior' => array(
				'title'       => __( 'Integration Behavior', 'wc-sicoob' ),
				'type'        => 'title',
				'description' => '',
			),
			'days_to_pay' => array(
				'title'             => __( 'Days to Pay', 'wc-sicoob' ),
				'type'              => 'number',
				'description'       => __( 'Please enter how many consecutive days customers will have to pay.', 'wc-sicoob' ),
				'desc_tip'          => true,
				'default'           => '1',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '1',
					'max'  => '30',
				),
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'wc-sicoob' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions for the customer, which will be displayed on the bank slip. Can not be more than 480 characters.', 'wc-sicoob' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'testing' => array(
				'title'       => __( 'Gateway Testing', 'wc-sicoob' ),
				'type'        => 'title',
				'description' => '',
			),
			'debug' => array(
				'title'       => __( 'Debug Log', 'wc-sicoob' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'wc-sicoob' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log events of this payment method, you can check this log in %s.', 'wc-sicoob' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '">' . __( 'System Status &gt; Logs', 'wc-sicoob' ) . '</a>' ),
			),
		);
	}

	/**
	 * Admin page.
	 */
	public function admin_options() {
		include dirname( __FILE__ ) . '/admin/views/html-admin-page.php';
	}

	/**
	 * Validate fields.
	 *
	 * @return bool
	 */
	public function validate_fields() {
		// Skip validation on Checkout pay page.
		if ( is_checkout_pay_page() ) {
			return true;
		}

		if ( empty( $_REQUEST['billing_cpf'] ) && empty( $_REQUEST['billing_cnpj'] ) ) {
			wc_add_notice( '<strong>' . $this->get_title() . ':</strong> ' . __( 'Missing CPF or CNPJ.', 'wc-sicoob' ), 'error' );

			return false;
		}

		return true;
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param  int $order_id Order ID.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Mark as on-hold (we're awaiting the payment).
		$order->update_status( 'on-hold', __( 'BB Com&eacute;rcio Eletr&ocirc;nico: Awaiting payment.', 'wc-sicoob' ) );

		// Remove cart.
		WC()->cart->empty_cart();

		// Set the expiry date.
		$this->api->save_expiry_date( $order );

		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Payment redirect.
	 */
	public function payment_redirect() {
		@ob_start();

		if ( isset( $_GET['key'] ) ) {
			$order_key = wc_clean( $_GET['key'] );
			$order_id  = wc_get_order_id_by_order_key( $order_key );
			$order     = wc_get_order( $order_id );

			if ( is_object( $order ) && $this->id === _wc_sicoob_get_order_meta( $order, 'payment_method' ) ) {
				if ( 'on-hold' !== _wc_sicoob_get_order_meta( $order, 'status' ) ) {
					$message = sprintf( __( 'You can no longer make the payment for order %s.', 'wc-sicoob' ), $order->get_order_number() );
					wp_die( $message, __( 'Payment method expired', 'wc-sicoob' ), array( 'response' => 200 ) );
				}

				$url = $this->api->get_bank_slip_url( $order );

				wp_redirect( esc_url_raw( $url ) );
				exit;
			}
		}

		wp_die( __( 'Invalid request!', 'wc-sicoob' ), __( 'Invalid request!', 'wc-sicoob' ), array( 'response' => 401 ) );
	}

	/**
	 * Thank you message.
	 * Displays the payment link.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );
		$key   = '';

		wc_get_template(
			'bank-slip/payment-instructions.php',
			array(
				'url' => wc_sicoob_get_bank_slip_url( _wc_sicoob_get_order_meta( $order, 'order_key' ) ),
			),
			'woocommerce/sicoob-comercio-eletronico/',
			WC_Sicoob::get_templates_path()
		);
	}

	/**
	 * Add payment instructions to order email.
	 *
	 * @param object $order         Order object.
	 * @param bool   $sent_to_admin Send to admin.
	 * @param bool   $plain_text    Plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $sent_to_admin || 'on-hold' !== $order->get_status() || $this->id !== $order->payment_method ) {
			return;
		}

		$url = wc_sicoob_get_bank_slip_url( _wc_sicoob_get_order_meta( $order, 'order_key' ) );

		if ( $plain_text ) {
			wc_get_template(
				'bank-slip/emails/plain-instructions.php',
				array(
					'url' => $url,
				),
				'woocommerce/sicoob-comercio-eletronico/',
				WC_Sicoob::get_templates_path()
			);
		} else {
			wc_get_template(
				'bank-slip/emails/html-instructions.php',
				array(
					'url' => $url,
				),
				'woocommerce/sicoob-comercio-eletronico/',
				WC_Sicoob::get_templates_path()
			);
		}
	}
}
