<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooPayPayGateBase' ) ) {
	class WooPayPayGateBase extends WooPayPayGate {
		public function __construct() {
			parent::__construct();

			$this->woopay_init();
		}

		private function woopay_init() {
			// Settings Link
			add_filter( 'plugin_action_links_' . $this->woopay_plugin_basename, array( $this, 'generate_settings_link' ) );

			// Actions
			add_action( 'admin_print_scripts', array( $this, 'woopay_admin_scripts' ) );
			add_action( 'admin_print_styles', array( $this, 'woopay_admin_styles' ) );
			add_action( 'wp_head', array( $this, 'woopay_frontend_scripts' ) );
			add_action( 'wp_head', array( $this, 'woopay_frontend_styles' ) );
			add_action( 'init', array( $this, 'woopay_register_order_status' ) );
			add_action( 'add_woopay_paygate_virtual_bank_information', array( $this, 'add_virtual_information' ), 10, 2 );
			add_action( 'woopay_paygate_virtual_bank_schedule', array( $this, 'woopay_cancel_unpaid_virtual_bank_orders' ) );

			// Filters
			add_filter( 'wc_order_statuses', array( $this, 'woopay_add_order_statuses' ) );
			add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'woopay_add_order_statuses_for_payment_complete' ), 10, 2 );
			add_filter( 'woocommerce_email_actions', array( $this, 'woopay_add_email_action' ) );
			add_filter( 'woocommerce_email_classes', array( $this, 'woopay_add_email_class' ) );

			// Classes
			include_once( 'class-woopay-paygate-actions.php' );
			include_once( 'class-woopay-paygate-payment.php' );
			include_once( 'class-woopay-paygate-refund.php' );

			// API
			include_once( 'class-woopay-paygate-api.php' );

			// Payment Methods
			include_once( 'methods/class-woopay-paygate-card.php' );
			include_once( 'methods/class-woopay-paygate-transfer.php' );
			include_once( 'methods/class-woopay-paygate-virtual.php' );
			include_once( 'methods/class-woopay-paygate-mobile.php' );
			include_once( 'methods/class-woopay-paygate-international.php' );
			include_once( 'methods/class-woopay-paygate-openpay.php' );
			include_once( 'methods/class-woopay-paygate-alipay.php' );
			include_once( 'methods/class-woopay-paygate-chinapay.php' );
			include_once( 'methods/class-woopay-paygate-tenpay.php' );
		}


		public function woopay_add_email_action( $array ) {
			$array[] = 'woocommerce_order_status_pending_to_awaiting';

			return $array;
		}

		public function woopay_add_email_class( $emails ) {			
			$emails[ 'WooPayPayGateEmail' ] = include( 'class-woopay-paygate-email.php' );

			return $emails;
		}

		public function woopay_cancel_unpaid_virtual_bank_orders() {
			global $wpdb;

			$virtual_bank_settings = get_option( 'woocommerce_paygate_virtual_settings', null );

			$expire_days = isset( $virtual_bank_settings[ 'expiry_time' ] ) ? $virtual_bank_settings[ 'expiry_time' ] : 0;

			if ( $expire_days < 1 ) {
				return;
			}

			$unpaid_orders = $wpdb->get_col( $wpdb->prepare( "
				SELECT posts.ID
				FROM	{$wpdb->posts} AS posts
				WHERE 	posts.post_type   IN ('" . implode( "','", wc_get_order_types() ) . "')
				AND		posts.post_status = 'wc-awaiting'
			", $date ) );

			$limit_date = strtotime( date( "Y-m-d", strtotime( '-' . absint( $expire_days ) + 1 . ' DAYS', strtotime( current_time( "Y-m-d" ) ) ) ) );

			if ( $unpaid_orders ) {
				foreach ( $unpaid_orders as $unpaid_order ) {
					$order = wc_get_order( $unpaid_order );

					$expiry_date = get_post_meta( $unpaid_order, '_woopay_paygate_expirydate', true );
					$expiry_date = strtotime( $this->get_dateformat( $expiry_date, 'Y-m-d' ) );

					if ( $expiry_date <= $limit_date ) {
						if ( apply_filters( 'woocommerce_cancel_unpaid_order', 'checkout' === get_post_meta( $unpaid_order, '_created_via', true ), $order ) ) {
							$order->update_status( 'cancelled', __( 'Unpaid order has been cancelled because the time limit has been reached.', $this->woopay_domain ) );
						}
					}
				}
			}

			// Cron Events
			$duration = 120;
			wp_clear_scheduled_hook( 'woopay_paygate_virtual_bank_schedule' );
			wp_schedule_single_event( time() + ( absint( $duration ) * 60 ), 'woopay_paygate_virtual_bank_schedule' );
		}

		public function for_translation() {
			array(
				__( 'Korean Payment Gateway integrated with PayGate for WooCommerce.', $this->woopay_domain )
			);
		}
	}

	return new WooPayPayGateBase();
}