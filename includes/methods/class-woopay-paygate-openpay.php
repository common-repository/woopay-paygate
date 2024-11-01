<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooPayPayGateOpenPay' ) ) {
	class WooPayPayGateOpenPay extends WooPayPayGatePayment {
		public function __construct() {
			parent::__construct();

			$this->method_init();
		}

		function method_init() {
			$this->id						= 'paygate_openpay';
			$this->section					= 'woopaypaygateopenpay';
			$this->method 					= '301';
			$this->method_title 			= __( 'PayGate OpenPay', $this->woopay_domain );
			$this->title_default 			= __( 'OpenPay', $this->woopay_domain );
			$this->desc_default  			= __( 'Payment via OpenPay.', $this->woopay_domain );
			$this->allowed_currency			= array( 'KRW' );
			$this->default_checkout_img		= 'openpay';
			$this->supports					= array( 'products', 'refunds' );
			$this->has_fields				= false;
			$this->allow_testmode			= true;
		}

	}

	function add_paygate_openpay( $methods ) {
		$methods[] = 'WooPayPayGateOpenPay';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_paygate_openpay' );
}