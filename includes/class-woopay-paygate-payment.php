<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooPayPayGatePayment' ) ) {
	class WooPayPayGatePayment extends WooPayPayGate {
		public $title_default;
		public $desc_default;
		public $default_checkout_img;
		public $allowed_currency;
		public $allow_other_currency;
		public $allow_testmode;

		function __construct() {
			parent::__construct();

			$this->method_init();
			$this->init_settings();
			$this->init_form_fields();

			$this->get_woopay_settings();

			// Actions
			add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'show_virtual_information' ) );
			add_action( 'woocommerce_view_order', array( $this, 'get_virtual_information' ), 9 );

			if ( ! $this->is_valid_for_use( $this->allowed_currency ) ) {
				if ( ! $this->allow_other_currency ) {
					$this->enabled = 'no';
				}
			}

			if ( ! $this->testmode ) {
				if ( $this->mertid == '' || $this->hashkey == '' ) {
					$this->enabled = 'no';
				}
			} else {
				$this->title		= __( '[Test Mode]', $this->woopay_domain ) . " " . $this->title;
				$this->description	= __( '[Test Mode]', $this->woopay_domain ) . " " . $this->description;
			}
		}

		function method_init() {
		}

		public function show_virtual_information( $orderid ) {
			$script_url = 'https://api.paygate.net/ajax/common/OpenPayAPI.js';

			wp_register_script( 'paygate_script', $script_url, array( 'jquery' ), '1.0.0', false );
			wp_enqueue_script( 'paygate_script' );

			$tid			= get_post_meta( $orderid, '_' . $this->woopay_api_name . '_tid', true );
			?>
			<script language="javascript">
			function businessVerify() {
				setPGIOElement( 'apilog', '100' );
				setPGIOElement( 'tid', '<?php echo $tid;?>' );
				verifyReceived();
			}

			jQuery( document ).ready( function( $ ) {
				businessVerify();
			});
			</script>
			<form name='PGIOForm'></form>
			<?php
			$bankname		= get_post_meta( $orderid, '_' . $this->woopay_api_name . '_bankname', true );
			$bankaccount	= get_post_meta( $orderid, '_' . $this->woopay_api_name . '_bankaccount', true );
			$expirydate		= get_post_meta( $orderid, '_' . $this->woopay_api_name . '_expirydate', true );

			if ( $bankname != '' && $bankaccount != '' && $expirydate != '' ) {
				echo  '<h2>' . __( 'Virtual Account Information', $this->woopay_domain ) . '</h2>';
				echo  '<div class="clear"></div>';
				echo  '<ul class="order_details">';
				echo  '<li class="order">' . __( 'Bank Name', $this->woopay_domain ) . '<strong>' . $bankname . '</strong></li>';
				echo  '<li class="order">' . __( 'Account No', $this->woopay_domain ) . '<strong>' . $bankaccount . '</strong></li>';
				echo  '<li class="order">' . __( 'Due Date', $this->woopay_domain ) . '<strong>' . $this->get_dateformat( $expirydate, 'Y-m-d' ) . '</strong></li>';
				echo  '</ul>';
				echo  '<div class="clear"></div>';
			}
		}

		function receipt( $orderid ) {
			$order = new WC_Order( $orderid );

			$disable_payment = false;

			if ( ! $this->check_mobile() ) {
				if ( ! $this->check_ie() && ( $this->method == 'card' || $this->method == '4') ) {
					$disable_payment = true;
				}
			} else {
				if ( $this->method == '4' ) {
					$disable_payment = true;
				}
			}

			if ( $disable_payment ) {
				echo '<p>';
				echo __( 'Your selected payment method cannot be performed on this browser or device. Please try again using Internet Explorer, or try using a different payment method.', $this->woopay_domain );
				echo '</p>';
				echo '<p><a href="' . WC()->cart->get_checkout_url() . '"><button class="button">' . __( 'Return to Checkout', $this->woopay_domain ) . '</button></a></p>';
			} else {
				if ( $this->checkout_img ) {
					echo '<div class="p8-checkout-img"><img src="' . $this->checkout_img . '"></div>';
				}

				echo '<div class="p8-checkout-txt">' . str_replace( "\n", '<br>', $this->checkout_txt ) . '</div>';

				if ( $this->show_chrome_msg == 'yes' ) {
					if ( $this->get_chrome_version() >= 42 && $this->get_chrome_version() < 45 ) {
						echo '<div class="p8-chrome-msg">';
						echo __( 'If you continue seeing the message to install the plugin, please enable NPAPI settings by following these steps:', $this->woopay_domain );
						echo '<br>';
						echo __( '1. Enter <u>chrome://flags/#enable-npapi</u> on the address bar.', $this->woopay_domain );
						echo '<br>';
						echo __( '2. Enable NPAPI.', $this->woopay_domain );
						echo '<br>';
						echo __( '3. Restart Chrome and refresh this page.', $this->woopay_domain );
						echo '</div>';
					}
				}
				wp_register_script( $this->woopay_api_name . 'paygate_iframe', $this->woopay_plugin_url . 'assets/js/iframe.js', array( 'jquery' ), '1.0.0', false );

				$translation_array = array(
					'cart_url'				=> WC()->cart->get_cart_url(),
				);

				wp_localize_script( $this->woopay_api_name . 'paygate_iframe', 'woopay_string', $translation_array );

				wp_enqueue_script( $this->woopay_api_name . 'paygate_iframe' );

				$currency_check = $this->currency_check( $order, $this->allowed_currency );

				if ( $currency_check ) {
					echo $this->woopay_form( $orderid );
				} else {
					$currency_str = $this->get_currency_str( $this->allowed_currency );

					echo sprintf( __( 'Your currency (%s) is not supported by this payment method. This payment method only supports: %s.', $this->woopay_domain ), get_post_meta( $order->id, '_order_currency', true ), $currency_str );
				}
			}
		}

		function get_woopay_args( $order ) {
			$orderid = $order->id;

			$this->billing_phone = $order->billing_phone;

			if ( sizeof( $order->get_items() ) > 0 ) {
				foreach ( $order->get_items() as $item ) {
					if ( $item[ 'qty' ] ) {
						$item_name = $item[ 'name' ];
					}
				}
			}

			$currency = $this->get_currency();
			update_post_meta( $order->id, '_order_currency', $currency );

			if ( $currency == 'KRW' ) $currency = 'WON';

			$woopay_args =
				array(
					'mid'					=> $this->mertid,
					'langcode'				=> ( $this->langcode == 'auto' ) ? $this->get_langcode( get_locale() ) : $this->langcode,
					'paymethod'				=> $this->method,
					'unitprice'				=> $order->order_total,
					'goodcurrency'			=> $currency,
					'goodname'				=> sanitize_text_field( $item_name ),
					'receipttoname'			=> $this->get_name_lang( $order->billing_first_name, $order->billing_last_name ),
					'receipttoemail'		=> $order->billing_email,
					'receipttotel'			=> ( $this->method == '801' ) ? '' : $order->billing_phone,
					'receipttosocialnumber'	=> '',
					'socialnumber'			=> '',
					'carrier'				=> '',
					'cardauthcode'			=> '',
					'cardtype'				=> '',
					'cardnumber'			=> '',
					'cardsecretnumber'		=> '',
					'cardexpiremonth'		=> '',
					'cardexpireyear'		=> '',
					'cardownernumber'		=> '',
					'bankcode'				=> '',
					'bankexpyear'			=> ( $this->method == '7' ) ? $this->get_expirytime( $this->expiry_time, 'Y' ) : '',
					'bankexpmonth'			=> ( $this->method == '7' ) ? $this->get_expirytime( $this->expiry_time, 'm' ) : '',
					'bankexpday'			=> ( $this->method == '7' ) ? $this->get_expirytime( $this->expiry_time, 'd' ) : '',
					'bankaccount'			=> '',
					'mb_serial_no'			=> $order->id,
					'profile_no'			=> '',
					'hashresult'			=> '',
					'replycode'				=> '',
					'replyMsg'				=> '',
					'riskscore'				=> '',
					'redirecturl'			=> $this->get_api_url( 'return' ),
					'kindcss'				=> '2',
				);

			$woopay_args = apply_filters( 'woocommerce_woopay_args', $woopay_args );

			return $woopay_args;
		}

		function woopay_form( $orderid ) {
			$order = new WC_Order( $orderid );

			$woopay_args = $this->get_woopay_args( $order );

			$woopay_args_array = array();

			foreach ( $woopay_args as $key => $value ) {
				$woopay_args_array[] = '<input type="hidden" name="'.esc_attr( $key ).'" id="'.esc_attr( $key ).'" value="'.esc_attr( $value ).'" />';
			}

			$woopay_form = "<form method='post' id='PGIOForm' name='PGIOForm' action='https://service.paygate.net/openAPI.jsp'>" . implode( '', $woopay_args_array ) . "</form>";

			$woopay_script_url = $this->woopay_plugin_url . 'assets/js/woopay.js';

			wp_enqueue_style( 'wp-jquery-ui-dialog' );
			wp_register_script( $this->woopay_api_name . 'woopay_script', $woopay_script_url, array( 'jquery', 'jquery-ui-dialog' ), '1.0.0', true );

			$translation_array = array(
				'payment_title'			=> __( 'PayGate Payment', $this->woopay_domain ),
				'testmode_msg'			=> __( 'Test mode is enabled. Continue?', $this->woopay_domain ),
				'cancel_msg'			=> __( 'You have cancelled your transaction. Returning to cart.', $this->woopay_domain ),
				'vbank_error_msg'		=> __( 'Please select a bank first!', $this->woopay_domain ),
				'vbank_select_bank'		=> __( 'Please select your bank', $this->woopay_domain ),
				'vbank_03'				=> $this->get_bankname( '03' ),
				'vbank_04'				=> $this->get_bankname( '04' ),
				'vbank_11'				=> $this->get_bankname( '11' ),
				'vbank_20'				=> $this->get_bankname( '20' ),
				'vbank_26'				=> $this->get_bankname( '26' ),
				'vbank_71'				=> $this->get_bankname( '71' ),
				'vbank_81'				=> $this->get_bankname( '81' ),
				'start_payment'			=> __( 'Start Payment', $this->woopay_domain ),
				'checkout_url'			=> WC()->cart->get_checkout_url(),
				'response_url'			=> $this->get_api_url( 'response' ),
				'cart_url'				=> WC()->cart->get_cart_url(),
				'testmode'				=> $this->testmode,
				'window_type'			=> $this->window_type
			);

			wp_localize_script( $this->woopay_api_name . 'woopay_script', 'woopay_string', $translation_array );
			wp_enqueue_script( $this->woopay_api_name . 'woopay_script' );

			return $woopay_form;
		}

		public function process_payment( $orderid ) {
			$order = new WC_Order( $orderid );

			$this->woopay_start_payment( $orderid );

			if ( $this->testmode ) {
				wc_add_notice( __( '<strong>Test mode is enabled!</strong> Please disable test mode if you aren\'t testing anything.', $this->woopay_domain ), 'error' );
			}

			return array(
				'result' 	=> 'success',
				'redirect'	=> $order->get_checkout_payment_url( true )
			);
		}

		public function process_refund( $orderid, $amount = null, $reason = '' ) {
			$woopay_refund = new WooPayPayGateRefund();
			$return = $woopay_refund->do_refund( $orderid, $amount, $reason );

			if ( $return[ 'result' ] == 'success' ) {
				return true;
			} else {
				return false;
			}
		}

		function admin_options() {
			$currency_str = $this->get_currency_str( $this->allowed_currency );

			echo '<h3>' . $this->method_title . '</h3>';

			$this->get_woopay_settings();

			$hide_form = "";

			if ( ! $this->woopay_check_api() ) {
				echo '<div class="inline error"><p><strong>' . sprintf( __( 'Gateway Disabled', $this->woopay_domain ) . '</strong>: ' . __( 'Please check your permalink settings. You must use a permalink structure other than \'General\'. Click <a href="%s">here</a> to change your permalink settings.', $this->woopay_domain ), $this->get_url( 'admin', 'options-permalink.php' ) ) . '</p></div>';

				$hide_form = "display:none;";
			} else {
				if ( ! $this->testmode ) {
					if ( $this->mertid == '' ) {
						echo '<div class="inline error"><p><strong>' . __( 'Gateway Disabled', $this->woopay_domain ) . '</strong>: ' . __( 'Please enter your Merchant ID.', $this->woopay_domain ) . '</p></div>';
					} else if ( $this->hashkey == '' ) {
						echo '<div class="inline error"><p><strong>' . __( 'Gateway Disabled', $this->woopay_domain ) . '</strong>: ' . __( 'Please enter your Hash Key.', $this->woopay_domain ) . '</p></div>';
					}
				} else {
					echo '<div class="inline error"><p><strong>' . __( 'Test mode is enabled!', $this->woopay_domain ) . '</strong> ' . __( 'Please disable test mode if you aren\'t testing anything.', $this->woopay_domain ) . '</p></div>';
				}
			}

			if ( ! $this->is_valid_for_use( $this->allowed_currency ) ) {
				if ( ! $this->allow_other_currency ) {
					echo '<div class="inline error"><p><strong>' . __( 'Gateway Disabled', $this->woopay_domain ) .'</strong>: ' . sprintf( __( 'Your currency (%s) is not supported by this payment method. This payment method only supports: %s.', $this->woopay_domain ), $this->get_currency(), $currency_str ) . '</p></div>';
				} else {
					echo '<div class="inline notice notice-info"><p><strong>' . __( 'Please Note', $this->woopay_domain ) .'</strong>: ' . sprintf( __( 'Your currency (%s) is not recommended by this payment method. This payment method recommeds the following currency: %s.', $this->woopay_domain ), $this->get_currency(), $currency_str ) . '</p></div>';
				}
			}

			echo '<div id="' . $this->woopay_plugin_name . '" style="' . $hide_form . '">';
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
			echo '</div>';
		}

		function init_form_fields() {
			// General Settings
			$general_array = array(
				'general_title' => array(
					'title' => __( 'General Settings', $this->woopay_domain ),
					'type' => 'title',
				),
				'enabled' => array(
					'title' => __( 'Enable/Disable', $this->woopay_domain ),
					'type' => 'checkbox',
					'label' => __( 'Enable this method.', $this->woopay_domain ),
					'default' => 'yes'
				),
				'testmode' => array(
					'title' => __( 'Enable/Disable Test Mode', $this->woopay_domain ),
					'type' => 'checkbox',
					'label' => __( 'Enable test mode.', $this->woopay_domain ),
					'description' => '',
					'default' => 'no'
				),
				'log_enabled' => array(
					'title' => __( 'Enable/Disable Logs', $this->woopay_domain ),
					'type' => 'checkbox',
					'label' => __( 'Enable logging.', $this->woopay_domain ),
					'description' => __( 'Logs will be automatically created when in test mode.', $this->woopay_domain ),
					'default' => 'no'
				),
				'log_control' => array(
					'title' => __( 'View/Delete Log', $this->woopay_domain ),
					'type' => 'log_control',
					'description' => '',
					'desc_tip' => '',
					'default' => 'no'
				),
				'title' => array(
					'title' => __( 'Title', $this->woopay_domain ),
					'type' => 'text',
					'description' => __( 'Title that users will see during checkout.', $this->woopay_domain ),
					'default' => $this->title_default,
				),
				'description' => array(
					'title' => __( 'Description', $this->woopay_domain ),
					'type' => 'textarea',
					'description' => __( 'Description that users will see during checkout.', $this->woopay_domain ),
					'default' => $this->desc_default,
				),
				'mertid' => array(
					'title' => __( 'Merchant ID', $this->woopay_domain ),
					'type' => 'text',
					'class' => 'paygate_mid',
					'description' => __( 'Please enter your Merchant ID.', $this->woopay_domain ),
					'default' => ''
				),
				'hashkey' => array(
					'title' => __( 'Hash API Key', $this->woopay_domain ),
					'type' => 'text',
					'description' => __( 'Enter your Hash API Key for integrity check.', $this->woopay_domain ),
					'default' => '',
				),
				'expiry_time' => array(
					'title' => __( 'Expiry time in days', $this->woopay_domain ),
					'type'=> 'select',
					'class' => 'wc-enhanced-select',
					'description' => __( 'Select the virtual account transfer expiry time in days.', $this->woopay_domain ),
					'options'	=> array(
						'1'			=> __( '1 day', $this->woopay_domain ),
						'2'			=> __( '2 days', $this->woopay_domain ),
						'3'			=> __( '3 days', $this->woopay_domain ),
						'4'			=> __( '4 days', $this->woopay_domain ),
						'5'			=> __( '5 days', $this->woopay_domain ),
						'6'			=> __( '6 days', $this->woopay_domain ),
						'7'			=> __( '7 days', $this->woopay_domain ),
						'8'			=> __( '8 days', $this->woopay_domain ),
						'9'			=> __( '9 days', $this->woopay_domain ),
						'10'		=> __( '10 days', $this->woopay_domain ),
					),
					'default' => ( '5' ),
				),
				'escw_yn' => array(
					'title' => __( 'Escrow Settings', $this->woopay_domain ),
					'type' => 'checkbox',
					'description' => __( 'Force escrow settings.', $this->woopay_domain ),
					'default' => 'no',
				),
				'callback_url' => array(
					'title' => __( 'Callback URL', $this->woopay_domain ),
					'type' => 'txt_info',
					'txt' => $this->get_api_url( 'response' ),
					'description' => __( 'Callback URL used for payment notice from PayGate.', $this->woopay_domain )
				)
			);

			// Refund Settings
			$refund_array = array(
				'refund_title' => array(
					'title' => __( 'Refund Settings', $this->woopay_domain ),
					'type' => 'title',
				),
				'refund_btn_txt' => array(
					'title' => __( 'Refund Button Text', $this->woopay_domain ),
					'type' => 'text',
					'description' => __( 'Text for refund button that users will see.', $this->woopay_domain ),
					'default' => __( 'Refund', $this->woopay_domain ),
				),
				'customer_refund' => array (
					'title' => __( 'Refundable Satus for Customer', $this->woopay_domain ),
					'type' => 'multiselect',
					'class' => 'chosen_select',
					'description' => __( 'Select the order status for allowing refund.', $this->woopay_domain ),
					'options' => $this->get_status_array(),
				)
			);

			// Design Settings
			$design_array = array(
				'design_title' => array(
					'title' => __( 'Design Settings', $this->woopay_domain ),
					'type' => 'title',
				),
				'langcode' => array(
					'title' => __( 'Language', $this->woopay_domain ),
					'type' => 'select',
					'class' => 'wc-enhanced-select',
					'description' => __( 'Select the language for your PayGate form.', $this->woopay_domain ),
					'options' => array(
						'KR' => __( 'Korean', $this->woopay_domain ),
					),
					'default' => 'KR'
				),
				'window_type' => array(
					'title' => __( 'Form Type', $this->woopay_domain ),
					'type' => 'select',
					'class' => 'wc-enhanced-select',
					'description' => __( 'Select the form type for your PayGate form.', $this->woopay_domain ),
					'options' => array(
						'iframe' => __( 'IFrame', $this->woopay_domain ),
						'submit' => __( 'Submit', $this->woopay_domain ),
					),
					'default' => 'iframe'
				),
				'checkout_img' => array(
					'title' => __( 'Checkout Processing Image', $this->woopay_domain ),
					'type' => 'img_upload',
					'description' => __( 'Please select or upload your image for the checkout processing page. Leave blank to show no image.', $this->woopay_domain ),
					'default' => $this->woopay_plugin_url . 'assets/images/' . $this->default_checkout_img . '.png',
					'btn_name' => __( 'Select/Upload Image', $this->woopay_domain ),
					'remove_btn_name' => __( 'Remove Image', $this->woopay_domain ),
					'default_btn_name' => __( 'Use Default', $this->woopay_domain ),
					'default_btn_url' => $this->woopay_plugin_url . 'assets/images/' . $this->default_checkout_img . '.png',
				),	
				'checkout_txt' => array(
					'title' => __( 'Checkout Processing Text', $this->woopay_domain ),
					'type' => 'textarea',
					'description' => __( 'Text that users will see on the checkout processing page. You can use some HTML tags as well.', $this->woopay_domain ),
					'default' => __( "<strong>Please wait while your payment is being processed.</strong>\nIf you see this page for a long time, please try to refresh the page.", $this->woopay_domain )
				),
				'show_chrome_msg' => array(
					'title' => __( 'Chrome Message', $this->woopay_domain ),
					'type' => 'checkbox',
					'label' => __( 'Show steps to enable NPAPI for Chrome users using less than v45.', $this->woopay_domain ),
					'description' => '',
					'default' => 'yes'
				)
			);

			if ( $this->id == 'paygate_international' ) {
				$design_array[ 'langcode' ] = array(
					'title' => __( 'Language', $this->woopay_domain ),
					'type' => 'select',
					'class' => 'wc-enhanced-select',
					'description' => __( 'Select the language for your PayGate form.', $this->woopay_domain ),
					'options' => array(
						'US' => __( 'English', $this->woopay_domain ),
					),
					'default' => 'US'
				);
			}

			if ( ! $this->allow_testmode ) {
				$general_array[ 'testmode' ] = array(
					'title' => __( 'Enable/Disable Test Mode', $this->woopay_domain ),
					'type' => 'txt_info',
					'txt' => __( 'You cannot test this payment method.', $this->woopay_domain ),
					'description' => '',
				);
			}

			if ( ! in_array( $this->id, array( 'paygate_transfer', 'paygate_virtual' ) ) ) {
				unset( $general_array[ 'escw_yn' ] );
			}

			if ( $this->id != 'paygate_virtual' ) {
				unset( $general_array[ 'expiry_time' ] );
			}

			if ( ! in_array( 'refunds', $this->supports ) ) {
				unset( $refund_array[ 'refund_btn_txt' ] );
				unset( $refund_array[ 'customer_refund' ] );

				$refund_array[ 'refund_title' ][ 'description' ] = __( 'This payment method does not support refunds. You can refund each transaction using the merchant page.', $this->woopay_domain );
			}

			$form_array = array_merge( $general_array, $refund_array );
			$form_array = array_merge( $form_array, $design_array );

			$this->form_fields = $form_array;


			$paygate_mid_bad_msg = __( 'This Merchant ID is not from Planet8. Please visit the following page for more information: <a href="http://www.planet8.co/woopay-paygate-change-mid/" target="_blank">http://www.planet8.co/woopay-paygate-change-mid/</a>', $this->woopay_domain );

			if ( is_admin() ) {
				if ( $this->id != '' ) {
					wc_enqueue_js( "
						function checkPayGate( payment_id, mid ) {
							var bad_mid = '<span style=\"color:red;font-weight:bold;\">" . $paygate_mid_bad_msg . "</span>';
							var mids = [ \"00planet8m\", \"010990682m\", \"1dayhousem\", \"dromeda01m\", \"ezstreet1m\", \"forwardfcm\", \"gagaa0320m\", \"gaguckr01m\", \"hesedorkrm\", \"imagelab0m\", \"imean0820m\", \"inflab001m\", \"innofilm1m\", \"jiayou505m\", \"lounge105m\", \"masterkr1m\", \"milspec01m\", \"pipnice11m\", \"pting2014m\", \"seopshop1m\", \"urbout001m\" ];

							if ( mid == '' || mid == undefined ) {
								jQuery( '#woocommerce_' + payment_id + '_mertid' ).closest( 'tr' ).css( 'background-color', 'transparent' );
								jQuery( '#paygate_mid_bad_msg' ).html( '' );
							} else {
								if ( mid.substring( 0, 3 ) == 'pl8' ) {
									jQuery( '#woocommerce_' + payment_id + '_mertid' ).closest( 'tr' ).css( 'background-color', 'transparent' );
									jQuery( '#paygate_mid_bad_msg' ).html( '' );
								} else if ( jQuery.inArray( mid, mids ) > 0 ) {
									jQuery( '#woocommerce_' + payment_id + '_mertid' ).closest( 'tr' ).css( 'background-color', 'transparent' );
									jQuery( '#paygate_mid_bad_msg' ).html( '' );
								} else {
									jQuery( '#woocommerce_' + payment_id + '_mertid' ).closest( 'tr' ).css( 'background-color', '#FFC1C1' );
									jQuery( '#paygate_mid_bad_msg' ).html( bad_mid );
								}
							}
						}

						jQuery( '.paygate_mid' ).on( 'blur', function() {
							var val = jQuery( this ).val();

							checkPayGate( '" . $this->id . "', val );
						});

						jQuery( document ).ready( function() {
							jQuery( '#woocommerce_" . $this->id . "_mertid' ).closest( 'td' ).append( '<div id=\"paygate_mid_bad_msg\"></div>' );

							var val = jQuery( '.paygate_mid' ).val();

							checkPayGate( '" . $this->id . "', val );
						});
					" );
				}
			}
		}
	}

	return new WooPayPayGatePayment();
}