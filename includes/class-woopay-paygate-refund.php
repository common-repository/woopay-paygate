<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooPayPayGateRefund' ) ) {
	class WooPayPayGateRefund extends WooPayPayGate {
		public function __construct() {
			parent::__construct();

			$this->init_refund();
		}

		function init_refund() {
			// For Customer Refund
			add_filter( 'woocommerce_my_account_my_orders_actions',  array( $this, 'add_customer_refund' ), 10, 2 );
		}

		public function do_refund( $orderid, $amount = null, $reason = '', $rcvtid = null, $type = null, $acctname = null, $bankcode= null, $banknum = null ) {
			$order			= wc_get_order( $orderid );

			if ( $order == null ) {
				$message = __( 'Refund request received, but order does not exist.', $this->woopay_domain );
				$this->log( $message );

				return array(
					'result' 	=> 'failure',
					'message'	=> $message
				);
			}

			$this->id		= $this->get_payment_method( $orderid );
			$this->init_settings();

			$this->get_woopay_settings();

			$this->log( __( 'Starting refund process.', $this->woopay_domain ), $orderid );

			if ( $amount == null ) {
				$amount = $order->get_total();
			}

			$tid = get_post_meta( $orderid, '_' . $this->woopay_api_name . '_tid', true );

			if ( $tid == '' ) {
				$message = __( 'No TID found.', $this->woopay_domain );
				$this->log( $message, $orderid );

				return array(
					'result' 	=> 'failure',
					'message'	=> $message
				);
			}

			require_once $this->woopay_plugin_basedir . '/bin/lib/Aes.php';
			require_once $this->woopay_plugin_basedir . '/bin/lib/AesCtr.php';

			$mid			= ( $this->testmode ) ? $this->get_test_mid( $this->id ) : $this->mertid;
			$hashkey		= ( $this->testmode ) ? $this->get_test_hashkey( $this->id ) : $this->hashkey;

			$apiSecretKey = $hashkey;
			$key256 = hash ( 'sha256', $apiSecretKey );
			 
			$aes = new AesCtr();
			$aes = $aes->encrypt ( $tid, $key256, 256 );
			$tidEncrypted = 'AES256' . $aes;
			 
			$fields_string = 'mid=' . $mid . '&tid=' . $tidEncrypted . '&amount=F';

			$apiurl = 'https://service.paygate.net/service/cancelAPI.json?callback=callback';

			$ch = curl_init( $apiurl );
			curl_setopt( $ch, CURLOPT_URL, $apiurl );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
			curl_setopt( $ch, CURLOPT_HEADER, 0 );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER,1 );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 120 );
			$result = curl_exec( $ch );
			curl_close( $ch );

			$result = str_replace( ')', '', str_replace( 'callback(', '', $result ) );
			$result = json_decode( $result );

			$resultCode		= $result->replyCode;
			$resultMsg		= $result->replyMessage;

			if ( $type == 'customer' ) {
				$refunder = __( 'Customer', $this->woopay_domain );
			} else {
				$refunder = __( 'Administrator', $this->woopay_domain );
			}

			if ( $resultCode == '0000' ) {
				$message = sprintf( __( 'Refund process complete. Refunded by %s. Reason: %s.', $this->woopay_domain ), $refunder, $reason );

				$this->log( $message, $orderid );

				$message = sprintf( __( '%s Timestamp: %s.', $this->woopay_domain ), $message, $this->get_timestamp() );

				$order->update_status( 'refunded', $message );

				return array(
					'result' 	=> 'success',
					'message'	=> __( 'Your refund request has been processed.', $this->woopay_domain )
				);
			} else {
				$message = __( 'An error occurred while processing the refund.', $this->woopay_domain );

				$this->log( $message, $orderid );
				$this->log( __( 'Result Code: ', $this->woopay_domain ) . $resultCode, $orderid );
				$this->log( __( 'Result Message: ', $this->woopay_domain ) . $resultMsg, $orderid );

				$order->add_order_note( sprintf( __( '%s Code: %s. Message: %s. Timestamp: %s.', $this->woopay_domain ), $message, $resultCode, $resultMsg, $this->get_timestamp() ) );

				return array(
					'result' 	=> 'failure',
					'message'	=> $message
				);
			}
		}
	}

	return new WooPayPayGateRefund();
}