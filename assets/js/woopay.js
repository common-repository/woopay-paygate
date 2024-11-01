var testmode = woopay_string.testmode;
var checkoutURL = woopay_string.checkout_url;
var responseURL = woopay_string.response_url;
var cartURL = woopay_string.cart_url;
var window_type = woopay_string.window_type;

var payForm = document.PGIOForm;

function payment_return( replycode, replyMsg, replyURL ) {
	document.getElementById( 'replycode' ).value = replycode;
	document.getElementById( 'replyMsg' ).value = replyMsg;

	if ( window_type == 'iframe' ) {
		jQuery( '#PG_PAYMENTWINDOW' ).dialog( 'close' );
	}

	if ( replycode == '9805' ) {
		alert( woopay_string.cancel_msg );
	}

	payForm.action = replyURL;
	payForm.target = '_self';
	payForm.submit();
}

function returnToCheckout() {
	payForm.target = '_self';
	payForm.action = checkoutURL;
	payForm.submit();
}

function startPayGate() {
	if ( payForm.paymethod.value == '7' ) {
		if ( payForm.bankcode.value == '' ) {
			alert( woopay_string.vbank_error_msg );
			return false;
		} else {
			payForm.target = 'PG_PAYMENTWINDOW_IFRAME';
			payForm.action = 'https://service.paygate.net/openAPI.jsp';
			setTimeout( 'payForm.submit();', 500 );
		}
	} else {
		payForm.target = 'PG_PAYMENTWINDOW_IFRAME';
		payForm.action = 'https://service.paygate.net/openAPI.jsp';
		setTimeout( 'payForm.submit();', 500 );
	}
}

function startWooPay() {
	if ( testmode ) {
		if ( ! confirm( woopay_string.testmode_msg ) ) {
			payForm.target = '_self';
			payForm.action = checkoutURL;
			payForm.submit();
			return false;
		}
	}

	if ( window_type == 'iframe' ) {
		doIframe();
	} else {
		payForm.target = '_self';
		payForm.submit();
	}
}

jQuery( document ).ready(function() {
	setTimeout( 'startWooPay();', 500 );
});