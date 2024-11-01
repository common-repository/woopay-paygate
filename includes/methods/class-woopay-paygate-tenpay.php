<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooPayPayGateTenpay' ) ) {
	class WooPayPayGateTenpay extends WooPayPayGatePayment {
		public function __construct() {
			parent::__construct();

			$this->method_init();
		}

		function method_init() {
			$this->id						= 'paygate_tenpay';
			$this->section					= 'woopaypaygatetenpay';
			$this->method 					= '111';
			$this->method_title 			= __( 'PayGate Tenpay', $this->woopay_domain );
			$this->title_default 			= __( 'Tenpay', $this->woopay_domain );
			$this->desc_default  			= __( 'Payment via Tenpay.', $this->woopay_domain );
			$this->allowed_currency			= array( 'USD', 'CNY' );
			$this->default_checkout_img		= 'tenpay';
			$this->supports					= array( 'products', 'refunds' );
			$this->has_fields				= false;
			$this->allow_testmode			= true;
		}

	}

	function add_paygate_tenpay( $methods ) {
		$methods[] = 'WooPayPayGateTenpay';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_paygate_tenpay' );
}