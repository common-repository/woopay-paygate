var cartURL = woopay_string.cart_url;
var fluidInterval = '';

var payForm = document.getElementById( 'PGIOForm' );

function fluidDialog() {
    var $visible = jQuery( '.ui-dialog:visible' );

    $visible.each( function () {
        var $this = jQuery( this );
        var dialog = $this.find( '.ui-dialog-content' ).data( 'ui-dialog' );

		if ( dialog.options.fluid ) {
            var wWidth = jQuery( window ).width();
            var wHeight = jQuery( window ).height();

			wWidth = window.innerWidth;
			wHeight = window.innerHeight;
			var tHeight = parseInt( jQuery( '.ui-dialog-titlebar' ).height() ) + 5;
			var cHeight = parseInt( jQuery( '.ui-dialog' ).height() );

			if ( wWidth < ( parseInt( dialog.options.maxWidth ) + 200 ) || wHeight < ( parseInt( dialog.options.maxHeight ) ) ) {
                $this.css( 'max-width', '100%' );
				$this.css( 'max-height', '100%' );
				$this.css( 'width', '100%' );
				$this.css( 'height', '100%' );

				$this.addClass( 'p8-woopay-fixed-top' );

				jQuery( '#PG_PAYMENTWINDOW' ).css( 'height', wHeight - tHeight + 'px' );
				jQuery( '#PG_PAYMENTWINDOW_IFRAME' ).css( 'height', wHeight - tHeight + 'px' );
            } else {
				$this.css( 'height', dialog.options.maxHeight + 'px' );
				$this.css( 'width', dialog.options.maxWidth + 'px' );
                $this.css( 'max-width', dialog.options.maxWidth + 'px' );

				$this.removeClass( 'p8-woopay-fixed-top' );

				jQuery( '#PG_PAYMENTWINDOW' ).css( 'height', cHeight - tHeight + 'px' );
				jQuery( '#PG_PAYMENTWINDOW_IFRAME' ).css( 'height', cHeight - tHeight + 'px' );
            }
            dialog.option( 'position', dialog.options.position );
        }
    });
}

jQuery( window ).resize( function() {
	fluidDialog();
});

jQuery( document ).on( 'dialogopen', '.ui-dialog', function ( event, ui ) {
	jQuery( 'head' ).append( '<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">' );

	fluidDialog();
	fluidInterval = setInterval( 'fluidDialog();', 200 );
});

jQuery( document ).on( 'dialogclose', function ( event, ui ) {
	if ( jQuery( '#replycode' ).val() == '' ) {
		payment_return( '9805', '', cartURL );
	}

	clearInterval( fluidInterval );
});

(function ($) {
	$.topZIndex = function (selector) {
		return Math.max(0, Math.max.apply(null, $.map(((selector || "*") === "*")? $.makeArray(document.getElementsByTagName("*")) : $(selector),
			function (v) {
				return parseFloat($(v).css("z-index")) || null;
			}
		)));
	};

	$.fn.topZIndex = function (opt) {
		if (this.length === 0) {
			return this;
		}
		
		opt = $.extend({increment: 1}, opt);

		var zmax = $.topZIndex(opt.selector),
			inc = opt.increment;

		return this.each(function () {
			this.style.zIndex = (zmax += inc);
		});
	};
})(jQuery);

function doIframe() {
	var topIndex = jQuery.topZIndex();

	jQuery( 'body' ).prepend( '<div id="PG_PAYMENTWINDOW" name="PG_PAYMENTWINDOW" style="display:none;"><iframe id="PG_PAYMENTWINDOW_IFRAME" name="PG_PAYMENTWINDOW_IFRAME" class="p8-modal-iframe" width="100%" height="99%" frameborder="0"></iframe></div>' );

	jQuery( '#PG_PAYMENTWINDOW' ).dialog({
		autoOpen: false,
		autoResize: true,
		modal: true,
		fluid: true,
		resizable: false,
		draggable: false,
		height: 630,
		maxHeight: 630,
		width: 'auto',
		maxWidth: 425,
		title: woopay_string.payment_title,
		open: function( event, ui ) {
			jQuery( '.ui-dialog' ).css( 'z-index', topIndex + 2 );
			jQuery( '.ui-widget-overlay' ).css( 'z-index', topIndex + 1 );
		}
	});

	jQuery( '#PG_PAYMENTWINDOW' ).dialog( 'open' );
	fluidDialog();
	jQuery( '#PG_PAYMENTWINDOW' ).focus();

	jQuery( 'iframe' ).wrap( function() {
		var jQuerythis = jQuery( this );

		return jQuery('<div></div>').css({
			width: jQuery( this ).attr( 'width' ),
			height: jQuery( this ).attr( 'height' ),
			'overflow-y': 'hidden',
			'-webkit-overflow-scrolling': 'touch'
		});
	});

	if ( payForm.paymethod.value == 7 ) {
		jQuery( '#PG_PAYMENTWINDOW_IFRAME' ).hide();

		vbank_html = '<div class="pg-bank-select-container"><div class="pg-bank-select-form"><select id="bank-select" name="bank-select"><option value="">' + woopay_string.vbank_select_bank + '</option><option value="03">' + woopay_string.vbank_03 + '</option><option value="04">' + woopay_string.vbank_04 + '</option><option value="11">' + woopay_string.vbank_11 + '</option><option value="20">' + woopay_string.vbank_20 + '</option><option value="26">' + woopay_string.vbank_26 + '</option><option value="71">' + woopay_string.vbank_71 + '</option><option value="81">' + woopay_string.vbank_81 + '</option></select><br/><br/><input type="button" class="button" value="' + woopay_string.start_payment + '" onclick="javascript:startPayGate();"></div></div>';

		jQuery( '#PG_PAYMENTWINDOW' ).html( vbank_html );

		jQuery( '#bank-select' ).on( 'change', function(e) {
			payForm.bankcode.value = this.value;

			jQuery( '#PG_PAYMENTWINDOW' ).html( '<iframe id="PG_PAYMENTWINDOW_IFRAME" name="PG_PAYMENTWINDOW_IFRAME" class="p8-modal-iframe" width="100%" height="99%" frameborder="0"></iframe>' );

			setTimeout( 'startPayGate();', 500 );
		});
	} else {
		startPayGate();
	}
}