<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooPayPayGateInternational' ) ) {
	class WooPayPayGateInternational extends WooPayPayGatePayment {
		public function __construct() {
			parent::__construct();

			$this->method_init();
		}

		function method_init() {
			$this->id						= 'paygate_international';
			$this->section					= 'woopaypaygateinternational';
			$this->method 					= '104';
			$this->method_title 			= __( 'PayGate International Card', $this->woopay_domain );
			$this->title_default 			= __( 'International Card', $this->woopay_domain );
			$this->desc_default  			= __( 'Payment via International Card.', $this->woopay_domain );
			$this->allowed_currency			= array( 'USD' );
			$this->default_checkout_img		= 'international';
			$this->supports					= array( 'products', 'refunds' );
			$this->has_fields				= false;
			$this->allow_testmode			= true;
		}

	}

	function add_paygate_international( $methods ) {
		$methods[] = 'WooPayPayGateInternational';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_paygate_international' );
}