<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooPayPayGateMobile' ) ) {
	class WooPayPayGateMobile extends WooPayPayGatePayment {
		public function __construct() {
			parent::__construct();

			$this->method_init();
		}

		function method_init() {
			$this->id						= 'paygate_mobile';
			$this->section					= 'woopaypaygatemobile';
			$this->method 					= '801';
			$this->method_title 			= __( 'PayGate Mobile Payment', $this->woopay_domain );
			$this->title_default 			= __( 'Mobile Payment', $this->woopay_domain );
			$this->desc_default  			= __( 'Payment via mobile payment.', $this->woopay_domain );
			$this->allowed_currency			= array( 'KRW' );
			$this->default_checkout_img		= 'mobile';
			$this->supports					= array( 'products' );
			$this->has_fields				= false;
			$this->allow_testmode			= false;
		}

	}

	function add_paygate_mobile( $methods ) {
		$methods[] = 'WooPayPayGateMobile';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_paygate_mobile' );
}