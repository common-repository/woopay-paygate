<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooPayPayGateActions' ) ) {
	class WooPayPayGateActions extends WooPayPayGate {
		function api_action( $type ) {
			@ob_clean();
			header( 'HTTP/1.1 200 OK' );
			switch ( $type ) {
				case 'check_api' :
					$this->do_check_api( $_REQUEST );
					exit;
					break;
				case 'return' :
					$this->do_return( $_REQUEST );
					exit;
					break;
				case 'response' :
					$this->do_response( $_REQUEST );
					exit;
					break;
				case 'refund_request' :
					$this->do_refund_request( $_REQUEST );
					exit;
					break;
				case 'escrow_request' :
					$this->do_escrow_request( $_REQUEST );
					exit;
					break;
				case 'delete_log' :
					$this->do_delete_log( $_REQUEST );
					exit;
					break;
				default :
					exit;
			}
		}

		private function do_check_api( $params ) {
			$result = array(
				'result'	=> 'success',
			);

			echo json_encode( $result );
		}

		private function do_return( $params ) {
			if ( empty( $params[ 'mb_serial_no' ] ) ) {
				wp_die( $this->woopay_plugin_nice_name . ' Failure' );
			}

			$orderid		= $params[ 'mb_serial_no' ];
			$order			= new WC_Order( $orderid );

			if ( $order == null ) {
				$message = __( 'Return received, but order does not exist.', $this->woopay_domain );
				$this->log( $message );
				wp_die( $this->woopay_plugin_nice_name . ' Failure' );
			}

			$this->id		= $this->get_payment_method( $orderid );
			$this->init_settings();

			$this->get_woopay_settings();

			$this->log( __( 'Starting return process.', $this->woopay_domain ), $orderid );

			if ( isset( $params[ 'replycode' ] ) && in_array( $params[ 'replycode' ], array( '9805', 'TEN003', 'CHI003', 'ali004' ) ) ) {
				$replycode = $params[ 'replycode' ];
				if ( isset( $params[ 'mb_serial_no' ] ) ) {
					$orderid = $params[ 'mb_serial_no' ];

					$replycode = '9805';
					$replyMsg = '';

					$this->woopay_user_cancelled( $orderid );

					$return_url = WC()->cart->get_cart_url();
				}
			} else {
				$hashkey		= $this->hashkey;

				$replycode		= isset( $params[ 'replycode' ] ) ? $params[ 'replycode' ] : '';
				$replyMsg		= isset( $params[ 'replyMsg' ] ) ? $params[ 'replyMsg' ] : '';

				$tid			= isset( $params[ 'tid' ] ) ? $params[ 'tid' ] : '';
				$paymethod		= isset( $params[ 'paymethod' ] ) ? $params[ 'paymethod' ] : '';
				$unitprice		= isset( $params[ 'unitprice' ] ) ? $params[ 'unitprice' ] : '';
				$goodcurrency	= isset( $params[ 'goodcurrency' ] ) ? $params[ 'goodcurrency' ] : '';
				$bankcode		= isset( $params[ 'bankcode' ] ) ? $params[ 'bankcode' ] : '';
				$bankaccount	= isset( $params[ 'bankaccount' ] ) ? $params[ 'bankaccount' ] : '';
				$bankexpyear	= isset( $params[ 'bankexpyear' ] ) ? $params[ 'bankexpyear' ] : '';
				$bankexpmonth	= isset( $params[ 'bankexpmonth' ] ) ? $params[ 'bankexpmonth' ] : '';
				$bankexpday		= isset( $params[ 'bankexpday' ] ) ? $params[ 'bankexpday' ] : '';

				$hashresult		= isset( $params[ 'hashresult' ] ) ? $params[ 'hashresult' ] : '';

				$paySuccess = false;

				if ( $replycode == '0000' ) $paySuccess = true;
				
				if ( $this->hashkey != '' ) {
					if ( $hashresult != '' ) {
						$check_hash =
							array(
								'replycode'				=> $replycode,
								'tid'					=> $tid,
								'PG_OID'				=> $orderid,
								'total'					=> $unitprice,
								'goodcurrency'			=> $goodcurrency,
								'orgtotal'				=> $order->order_total,
								'orgcurrency'			=> get_post_meta( $orderid, '_order_currency', true ),
							);

						$hashed = $this->get_hash( $this->hashkey, $check_hash );

						if ( $hashed ) {
							if ( $hashed != $hashresult ) {
								$paySuccess = false;

								$this->woopay_payment_integrity_failed( $orderid );

								$return_url = WC()->cart->get_cart_url();
							}
						} else {
							$paySuccess = false;

							$this->woopay_payment_integrity_failed( $orderid );

							$return_url = WC()->cart->get_cart_url();
						}
					}
				}

				if ( $paySuccess == true ) {
					if ( $paymethod == '7' ) {
						$this->woopay_payment_awaiting( $orderid, $tid, $paymethod, $this->get_bankname( $bankcode ), $bankaccount, $bankexpyear . $bankexpmonth . $bankexpday );
					} else {
						$this->woopay_payment_complete( $orderid, $tid, $paymethod );
					}

					WC()->cart->empty_cart();

					$return_url = $this->get_return_url( $order );
				} else {
					$this->woopay_payment_failed( $orderid );

					$return_url = WC()->cart->get_cart_url();
				}
			}

			if ( $this->window_type == 'submit' ) {
				wp_redirect( $return_url );
			}
			?>
			<html>
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
					<script type="text/javascript">
					function setPayGateResult() {
						try {
							opener.payment_return( '<?php echo $replycode; ?>','<?php echo $replyMsg; ?>', '<?php echo $return_url; ?>' );
						} catch (e) {
							try {
								parent.payment_return( '<?php echo $replycode; ?>','<?php echo $replyMsg; ?>', '<?php echo $return_url; ?>' );
							} catch (e) {
								try {
									payment_return( '<?php echo $replycode; ?>', '<?php echo $replyMsg; ?>', '<?php echo $return_url; ?>' );
								}
								catch (e)
								{
								}
							}
						}
					}
					</script>
				</head>
				<body onload='setPayGateResult();'>
				</body>
			</html>
			<?php
			exit;
		}

		private function do_response( $params ) {
			if ( empty( $params[ 'mb_serial_no' ] ) ) {
				echo '<PGTL><VERIFYRECEIVED>RCVD</VERIFYRECEIVED></PGTL>';
				exit;
			}

			$orderid		= $params[ 'mb_serial_no' ];
			$order			= new WC_Order( $orderid );

			if ( $order == null ) {
				$message = __( 'Response received, but order does not exist.', $this->woopay_domain );
				$this->log( $message );
				echo '<PGTL><VERIFYRECEIVED>RCVD</VERIFYRECEIVED></PGTL>';
				exit;
			}

			$this->id		= $this->get_payment_method( $orderid );
			$this->init_settings();

			$this->get_woopay_settings();

			$this->log( __( 'Starting response process.', $this->woopay_domain ), $orderid );

			$tid					= isset( $params[ 'tid' ] ) ? $params[ 'tid' ] : '';
			$mb_serial_no			= isset( $params[ 'mb_serial_no' ] ) ? $params[ 'mb_serial_no' ] : '';
			$receipttoname			= isset( $params[ 'receipttoname' ] ) ? $params[ 'receipttoname' ] : '';
			$totalprice				= isset( $params[ 'totalprice' ]  ) ? $params[ 'totalprice' ] : '';
			$replycode				= isset( $params[ 'replycode' ] ) ? $params[ 'replycode' ] : '';
			$paymethod				= isset( $params[ 'paymethod' ] ) ? $params[ 'paymethod' ] : '';
			$transactionstatus		= isset( $params[ 'transactionstatus' ] ) ? $params[ 'transactionstatus' ] : '';
			$time					= isset( $params[ 'time' ] ) ? $params[ 'time' ] : '';
			$hashresult				= isset( $params[ 'hashresult' ] ) ? $params[ 'hashresult' ] : '';

			if ( $this->hashkey != '' ) {
				if ( $hashresult != '' ) {
					$check_hash =
						array(
							'replycode'				=> $replycode,
							'tid'					=> $tid,
							'PG_OID'				=> $orderid,
							'total'					=> $unitprice,
							'goodcurrency'			=> $goodcurrency,
							'orgtotal'				=> $order->order_total,
							'orgcurrency'			=> get_post_meta( $orderid, '_order_currency', true ),
						);

					$hashed = $this->get_hash( $this->hashkey, $check_hash );

					if ( $hashed ) {
						if ( $hashed != $hashresult) {
							$paySuccess = false;

							$this->woopay_payment_integrity_failed( $orderid );

							echo '<PGTL><VERIFYRECEIVED>RCVD</VERIFYRECEIVED></PGTL>';
							exit;
						}
					} else {
						$paySuccess = false;

						$this->woopay_payment_integrity_failed( $orderid );

						echo '<PGTL><VERIFYRECEIVED>RCVD</VERIFYRECEIVED></PGTL>';
						exit;
					}
				}
			}

			if ( $replycode == '0000' ) $paySuccess = true;

			if ( $paySuccess ) {
				if ( $paymethod == '7' ) {
					$this->woopay_cas_payment_complete( $orderid, $tid, $paymethod );

					echo '<PGTL><VERIFYRECEIVED>RCVD</VERIFYRECEIVED><TID>'.$tid.'</TID></PGTL>';
					exit;
				} else {
					$this->woopay_payment_complete( $orderid, $tid, $paymethod );

					echo '<PGTL><VERIFYRECEIVED>RCVD</VERIFYRECEIVED><TID>'.$tid.'</TID></PGTL>';
					exit;
				}
			} else {
				echo '<PGTL><VERIFYRECEIVED>RCVD</VERIFYRECEIVED></PGTL>';
				exit;
			}
		}

		private function do_refund_request( $params ) {
			if ( ! isset( $params[ 'orderid' ] ) || ! isset( $params[ 'tid' ] ) || ! isset( $params[ 'type' ] ) ) {
				wp_die( $this->woopay_plugin_nice_name . ' Failure' );
			}

			$orderid		= $params[ 'orderid' ];
			$tid			= $params[ 'tid' ];

			$woopay_refund = new WooPayPayGateRefund();
			$return = $woopay_refund->do_refund( $orderid, null, __( 'Refund request by customer', $this->woopay_domain ), $tid, 'customer' );

			if ( $return[ 'result' ] == 'success' ) {
				wc_add_notice( $return[ 'message' ], 'notice' );
				wp_redirect( $params[ 'redirect' ] );
				exit;
			} else {
				wc_add_notice( $return[ 'message' ], 'error' );
				wp_redirect( $params[ 'redirect' ] );
				exit;
			}
			exit;
		}

		private function do_escrow_request( $params ) {
			exit;
		}

		private function do_delete_log( $params ) {
			if ( ! isset( $params[ 'file' ] ) ) {
				$return = array(
					'result' => 'failure',
				);
			} else {
				$file = trailingslashit( WC_LOG_DIR ) . $params[ 'file' ];

				if ( file_exists( $file ) ) {
					unlink( $file );
				}

				$return = array(
					'result' => 'success',
					'message' => __( 'Log file has been deleted.', $this->woopay_domain )
				);
			}

			echo json_encode( $return );

			exit;
		}
	}
}