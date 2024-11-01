<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooPayPayGateTransfer' ) ) {
	class WooPayPayGateTransfer extends WooPayPayGatePayment {
		public function __construct() {
			parent::__construct();

			$this->method_init();
		}

		function method_init() {
			$this->id						= 'paygate_transfer';
			$this->section					= 'woopaypaygatetransfer';
			$this->method 					= '4';
			$this->method_title 			= __( 'PayGate Account Transfer', $this->woopay_domain );
			$this->title_default 			= __( 'Account Transfer', $this->woopay_domain );
			$this->desc_default  			= __( 'Payment via account transfer.', $this->woopay_domain );
			$this->allowed_currency			= array( 'KRW' );
			$this->default_checkout_img		= 'bank';
			$this->supports					= array( 'products' );
			$this->has_fields				= false;
			$this->allow_testmode			= false;
		}

	}

	function add_paygate_transfer( $methods ) {
		$methods[] = 'WooPayPayGateTransfer';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_paygate_transfer' );
}