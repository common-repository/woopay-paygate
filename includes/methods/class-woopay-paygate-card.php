<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooPayPayGateCard' ) ) {
	class WooPayPayGateCard extends WooPayPayGatePayment {
		public function __construct() {
			parent::__construct();

			$this->method_init();
		}

		function method_init() {
			$this->id						= 'paygate_card';
			$this->section					= 'woopaypaygatecard';
			$this->method 					= 'card';
			$this->method_title 			= __( 'PayGate Credit Card', $this->woopay_domain );
			$this->title_default 			= __( 'Credit Card', $this->woopay_domain );
			$this->desc_default  			= __( 'Payment via credit card.', $this->woopay_domain );
			$this->allowed_currency			= array( 'KRW', 'USD' );
			$this->default_checkout_img		= 'card';
			$this->supports					= array( 'products', 'refunds' );
			$this->has_fields				= false;
			$this->allow_testmode			= true;
		}

	}

	function add_paygate_card( $methods ) {
		$methods[] = 'WooPayPayGateCard';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_paygate_card' );
}