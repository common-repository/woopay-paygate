<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooPayPayGateVirtual' ) ) {
	class WooPayPayGateVirtual extends WooPayPayGatePayment {
		public function __construct() {
			parent::__construct();

			$this->method_init();
		}

		function method_init() {
			$this->id						= 'paygate_virtual';
			$this->section					= 'woopaypaygatevirtual';
			$this->method 					= '7';
			$this->method_title 			= __( 'PayGate Virtual Account', $this->woopay_domain );
			$this->title_default 			= __( 'Virtual Account', $this->woopay_domain );
			$this->desc_default  			= __( 'Payment via virtual account.', $this->woopay_domain );
			$this->allowed_currency			= array( 'KRW' );
			$this->default_checkout_img		= 'bank';
			$this->supports					= array( 'products' );
			$this->has_fields				= false;
			$this->allow_testmode			= false;
		}

	}

	function add_paygate_virtual( $methods ) {
		$methods[] = 'WooPayPayGateVirtual';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_paygate_virtual' );
}