<?php
/**
 * Sicoob API
 *
 * @package WC_Sicoob/Classes/API
 * @since   1.0.0
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sicoob Ticket gateway class.
 */
class WC_Sicoob_API {

	/**
	 * Bank Slip Code.
	 */
	const BANK_SLIP_CODE = '2';

	/**
	 * Bank slip code for reissued document.
	 */
	const BANK_SLIP_REISSUE_CODE = '21';

	/**
	 * Gateway class.
	 *
	 * @var WC_Payment_Gateway
	 */
	protected $gateway;

	/**
	 * Form URL.
	 *
	 * @var string
	 */
	protected $form_url = 'https://mpag.sicoob.com.br/site/mpag/';

	/**
	 * WC_Logger
	 *
	 * @var null
	 */
	protected $logger = null;

	/**
	 * Constructor.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 */
	public function __construct( $gateway = null ) {
		$this->gateway = $gateway;

		$this->set_logger();
	}

	/**
	 * Get form URL.
	 *
	 * @return string
	 */
	public function get_form_url() {
		return $this->form_url;
	}

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	public function using_supported_currency() {
		return 'BRL' === get_woocommerce_currency();
	}

	/**
	 * Set logger.
	 */
	protected function set_logger() {
		if ( 'yes' === $this->gateway->debug ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				$this->logger = wc_get_logger();
			} else {
				$this->logger = new WC_Logger();
			}
		}
	}

	/**
	 * Add to log.
	 *
	 * @param string $message Message to save.
	 * @param mixed  $var     Variable to print.
	 */
	public function add_log( $message, $var = null ) {
		if ( 'yes' === $this->gateway->debug ) {
			if ( ! is_null( $var ) ) {
				$message .= ': ' . print_r( $var, true );
			}

			$this->logger->add( $this->gateway->id, $message );
		}
	}

	/**
	 * Save expiry date in the database.
	 * Expiry date is used only for bank slip.
	 *
	 * @param  WC_Order $order Order data.
	 *
	 * @return int
	 */
	public function save_expiry_date( $order ) {
		$days   = absint( $this->gateway->days_to_pay );
		$now    = strtotime( current_time( 'mysql' ) );
		$expiry = strtotime( '+' . $days . ' days', $now );

		_wc_sicoob_update_order_meta( $order, 'wc_sicoob_bank_slip_expiry_date', $expiry );

		return $expiry;
	}

	/**
	 * Only numbers.
	 *
	 * @param  string|int $string
	 *
	 * @return string|int
	 */
	protected function only_numbers( $string ) {
		return preg_replace( '/\D/', '', $string );
	}

	/**
	 * Get transaction ID.
	 *
	 * @param  WC_Order $order Order data.
	 *
	 * @return string
	 */
	protected function get_transaction_id( $order ) {
		// Check for saved transaction ID.
		$id = $order->get_transaction_id();
		if ( ! empty( $id ) ) {
			return $id;
		}

		// Generate new transaction ID.
		$id = '';
		if ( ! empty( $this->gateway->collection_code ) ) {
			$id = $this->gateway->collection_code;
		}
		$id .= $order->get_order_number();
		$id = apply_filters( 'wc_sicoob_transaction_id', $id, $order );

		// Sanitize and normalize.
		$id = $this->only_numbers( $id );
		$id = str_pad( $id, 17, 0 );

		_wc_sicoob_update_order_meta( $order, 'transaction_id', $id );
		$this->add_log( sprintf( 'Generated transaction ID for order #%s', _wc_sicoob_get_order_meta( $order, 'id' ) ) );

		return $id;
	}

	/**
	 * Get bank slip URL.
	 *
	 * @param  WC_Order $order Order data.
	 *
	 * @return string
	 */
	public function get_bank_slip_url( $order ) {
		$args = array(
			'idConv'      => $this->gateway->agreement_code,
			'refTran'     => $this->get_transaction_id( $order ),
			'valor'       => $order->get_total() * 100,
			'qtdPontos'   => '0',
			'dtVenc'      => date( 'dmY', _wc_sicoob_get_order_meta( $order, 'wc_sicoob_bank_slip_expiry_date' ) ),
			'tpPagamento' => self::BANK_SLIP_CODE,
			'msgLoja'     => $this->gateway->instructions,
			'urlRetorno'  => str_replace( site_url(), '', $this->gateway->get_return_url( $order ) ),
			'nome'        => _wc_sicoob_get_order_meta( $order, 'billing_first_name' ) . ' ' . _wc_sicoob_get_order_meta( $order, 'billing_last_name' ),
			'endereco'    => implode( ', ', array_filter( array( _wc_sicoob_get_order_meta( $order, 'billing_address_line_1' ), _wc_sicoob_get_order_meta( $order, 'billing_number' ), _wc_sicoob_get_order_meta( $order, 'billing_address_line_2' ), _wc_sicoob_get_order_meta( $order, 'billing_neighborhood' ) ) ) ),
			'cidade'      => _wc_sicoob_get_order_meta( $order, 'billing_city' ),
			'uf'          => _wc_sicoob_get_order_meta( $order, 'billing_state' ),
			'cep'         => $this->only_numbers( _wc_sicoob_get_order_meta( $order, 'billing_postcode' ) ),
		);

		// Set CPF or CNPJ.
		$wcbcf_settings = get_option( 'wcbcf_settings' );
		$type           = (int) $wcbcf_settings['person_type'];
		$person_type    = (int) _wc_sicoob_get_order_meta( $order, 'billing_persontype' );
		if ( ( 1 === $type && 1 === $person_type ) || 2 === $type ) {
			$args['indicadorPessoa'] = '1';
			$args['cpfCnpj'] = $this->only_numbers( _wc_sicoob_get_order_meta( $order, 'billing_cpf' ) );
		} elseif ( ( 1 === $type && 2 === $person_type ) || 3 === $type ) {
			$args['nome']            = _wc_sicoob_get_order_meta( $order, 'billing_company' );
			$args['indicadorPessoa'] = '2';
			$args['cpfCnpj'] = $this->only_numbers( _wc_sicoob_get_order_meta( $order, 'billing_cnpj' ) );
		}

		$args = apply_filters( 'wc_sicoob_bank_slip_payment_args', $args, $order );

		$this->add_log( sprintf( 'Generated bank slip URL for order #%s with the follow args', _wc_sicoob_get_order_meta( $order, 'id' ) ), $args );

		return add_query_arg( $args, $this->get_form_url() );
	}
}
