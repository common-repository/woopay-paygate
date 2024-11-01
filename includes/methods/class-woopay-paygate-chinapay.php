<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooPayPayGateChinapay' ) ) {
	class WooPayPayGateChinapay extends WooPayPayGatePayment {
		public function __construct() {
			parent::__construct();

			$this->method_init();
		}

		function method_init() {
			$this->id						= 'paygate_chinapay';
			$this->section					= 'woopaypaygatechinapay';
			$this->method 					= '113';
			$this->method_title 			= __( 'PayGate China Pay', $this->woopay_domain );
			$this->title_default 			= __( 'China Pay', $this->woopay_domain );
			$this->desc_default  			= __( 'Payment via China Pay.', $this->woopay_domain );
			$this->allowed_currency			= array( 'USD', 'CNY' );
			$this->default_checkout_img		= 'chinapay';
			$this->supports					= array( 'products', 'refunds' );
			$this->has_fields				= false;
			$this->allow_testmode			= true;
		}

	}

	function add_paygate_chinapay( $methods ) {
		$methods[] = 'WooPayPayGateChinapay';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_paygate_chinapay' );
}