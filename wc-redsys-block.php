<?php

/**
 * Dummy Payments Blocks integration
 *
 * @since 1.0.3
 */
final class WC_Gateway_Redsys_Block extends Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name;

	private $gateway;

	public function __construct( $name ) {
		$this->name = $name;
	}

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_' . $this->name . '_settings', array() );
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[ $this->name ];
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return true;
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$blockJsURL = REDSYSPUR_URL . '/pages/assets/js/block_' . $this->name . '.js';
		wp_register_script(
			'wc-payment-method-' . $this->name,
			$blockJsURL,
			array(
				'wc-blocks-registry', 
				'wc-settings', 
				'wp-element', 
				'wp-html-entities', 
				'wp-i18n',
				'jquery',
				'jquery-ui-core',
				'jquery-ui-dialog'
			),
			MODULE_VERSION,
			array(
				'strategy'  => 'defer', 
				'in_footer' => true,
			),
		);
		return [ 'wc-payment-method-' . $this->name ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => $this->gateway->title,
			'description' => $this->gateway->description,
//			'buttonLabel' => $this->gateway->buttonLabel,
			'supports'    => $this->get_supported_features(),
		);
	}
}

