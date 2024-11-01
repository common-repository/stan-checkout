(function( $ ) {
	'use strict';

	const checkBtnUpdate = () => {
		$( '.stan-checkout--button' ).attr('disabled', $( '.woocommerce-variation-add-to-cart' ).hasClass( 'woocommerce-variation-add-to-cart-disabled' ));
	}

	$( window ).on( 'load', function() {
		setTimeout(checkBtnUpdate, 250);

		$( '.woocommerce-variation-add-to-cart' ).on( 'change', function() {
			setTimeout(checkBtnUpdate, 333);
		});

		$( '.stan-checkout--button' ).on( 'click', function() {
			if ( $( this ).is(':disabled') ) {
				return;
			}
			
			$( this ).toggleClass( 'stan-checkout--button-loading' );
			$( '.stan-checkout--error' ).css( 'visibility', 'hidden' );

			const variationsForm = $( '.variations_form.cart' );

			const body = {
				product_id: $( this ).data('product'),
				attributes: [],
			}

			if (variationsForm.length) {
				var variationsData = {};
	
				// Get all form elements within the variations form
				variationsForm.find('input, select').each(function() {
					var element = $(this);
					
					variationsData[element.attr('name')] = element.val();
				});

				for (const v in variationsData) {
					if (v.includes('attribute_')) {
						body.attributes.push({
							name: v,
							value: variationsData[v],
						});
					}
				}

				if (typeof variationsData['variation_id'] !== 'undefined') {
					body.variation_id = variationsData['variation_id'];
				}
			}

			fetch('/wp-json/stan/checkouts', {
				method: 'POST',
				body: JSON.stringify(body),
				credentials: 'include',
			})
			.then(async res => {
				const checkout = await res.json();
				if (checkout.checkout_url) {
					window.location = checkout.checkout_url;
					return;
				}

				$( this ).toggleClass( 'stan-checkout--button-loading' );
				$( '.stan-checkout--error' ).css( 'visibility', 'visible' );
			})
			.catch(() => {
				$( this ).toggleClass( 'stan-checkout--button-loading' );
				$( '.stan-checkout--error' ).css( 'visibility', 'visible' );
			});
		});
	});
})( jQuery );
