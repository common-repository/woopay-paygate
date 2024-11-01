<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooPayPayGateApi' ) ) {
	class WooPayPayGateApi extends WooPayPayGate {
		public function __construct() {
			parent::__construct();

			$this->api_init();
		}

		private function api_init() {
			// WC-API Hook
			$api_events = array(
				'check_api',
				'return',
				'response',
				'refund_request',
				'escrow_request',
				'delete_log'
			);

			foreach ( $api_events as $key => $api_event ) {
				add_action( 'woocommerce_api_' . $this->woopay_api_name . '_' . $api_event, array( $this, 'api_' . $api_event ) );
			}

			$this->payment = new WooPayPayGateActions();
		}

		function api_check_api() {
			$this->payment->api_action( 'check_api' );
			exit;
		}

		function api_return() {
			$this->payment->api_action( 'return' );
			exit;
		}

		function api_response() {
			$this->payment->api_action( 'response' );
			exit;
		}

		function api_refund_request() {
			$this->payment->api_action( 'refund_request' );
			exit;
		}

		function api_escrow_request() {
			$this->payment->api_action( 'escrow_request' );
			exit;
		}

		function api_delete_log() {
			$this->payment->api_action( 'delete_log' );
			exit;
		}
	}

	return new WooPayPayGateApi();
}