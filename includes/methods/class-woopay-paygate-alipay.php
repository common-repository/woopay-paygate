<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooPayPayGateAlipay' ) ) {
	class WooPayPayGateAlipay extends WooPayPayGatePayment {
		public function __construct() {
			parent::__construct();

			$this->method_init();
		}

		function method_init() {
			$this->id						= 'paygate_alipay';
			$this->section					= 'woopaypaygatealipay';
			$this->method 					= '106';
			$this->method_title 			= __( 'PayGate Alipay', $this->woopay_domain );
			$this->title_default 			= __( 'Alipay', $this->woopay_domain );
			$this->desc_default  			= __( 'Payment via Alipay.', $this->woopay_domain );
			$this->allowed_currency			= array( 'USD', 'CNY' );
			$this->default_checkout_img		= 'alipay';
			$this->supports					= array( 'products', 'refunds' );
			$this->has_fields				= false;
			$this->allow_testmode			= true;
		}

	}

	function add_paygate_alipay( $methods ) {
		$methods[] = 'WooPayPayGateAlipay';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_paygate_alipay' );
}