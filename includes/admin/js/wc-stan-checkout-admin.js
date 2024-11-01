(function( $ ) {
	'use strict';

	const settings = {
		client_id: 'woocommerce_wc_stan_payment_gateway_client_id',
		secret_key: 'woocommerce_wc_stan_payment_gateway_secret_key',
		test_connection_btn: 'woocommerce_wc_stan_payment_gateway_test_connection',
		test_connection_result: 'woocommerce_wc_stan_payment_gateway_test_connection_result',
		account_infos_btn: 'woocommerce_wc_stan_payment_gateway_stan_account_infos_btn',
		api_url: 'woocommerce_wc_stan_payment_gateway_stan_api_url',
		auth_url: 'woocommerce_wc_stan_payment_gateway_stan_api_auth_url',
	}

	const testConnection = {
		init: function() {
			const btnID = 'stan_test_connection_btn';
			$( '#' + settings.test_connection_btn ).replaceWith( function() {
				return '<button id="' + btnID + '" class="' + this.className + '">Tester mes identifiants Stan</button>';
			});

			$('#' + btnID).click( (event) => {
				event.preventDefault();
				testConnection.run();
			});

			$( '#' + settings.test_connection_result ).replaceWith( function() {
				return '<p id="' + settings.test_connection_result + '"></p>';
			});
		},
		run: function(displayResult = true) {
			const clientID = $( '#' + settings.client_id ).val();
			const clientSecret = $( '#' + settings.secret_key ).val();
			const api_url = $( '#' + settings.api_url ).val();

			$.ajax(api_url + '/v1/payments', {
				type: 'GET',
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json',
					'Authorization': 'Basic ' + btoa( clientID + ':' + clientSecret),
				}
			}).done((_data, _status, res) => {
				if (displayResult) {
					testConnection.displayResult(res.status);
				}

				// Good keys display update infos
				accountInfos.enable();

				return true;
			}).fail((res, _status, err) => {
				if (displayResult) {
					testConnection.displayResult(res.status, err);
				}
				accountInfos.disable();
				return false;
			});
		},
		displayResult: function(status, err = undefined) {
			var success = true;
			var msg = 'Connexion au service de paiement réussie !';
			if (status === 401) {
				msg = 'Woops, votre identifiant client ou votre clé secrète ne sont pas valides';
				success = false;
			} else if (status > 400 || typeof err !== 'undefined') {
				console.warn(`Stan Test connexion error status ${status}, error : ${err} `)
				msg = "La connexion au service de paiement a échoué, les Stanners se chargent de régler le soucis";
				success = false;
			}

			const result = $( '#' + settings.test_connection_result );
			result.removeClass();

			result.addClass(success ? 'result-success' : 'result-failed');

			result.text( function() {
				return msg;
			});

		}
	}

	const accountInfos = {
		msg_id: 'stan_account_infos_msg',
		link_id: 'stan_account_link',
		init: function() {
			$( '#' + settings.account_infos_btn ).replaceWith( function() {
				return `
					<button id="${this.id}" class="${this.className}">Accéder à mon compte Stan</button>
					<p id="stan_account_infos_msg"></p>
					<a href="#" id="stan_account_link" target="_blank"></a>
				`;
			});

			$('#' + settings.account_infos_btn).click( (event) => {
				event.preventDefault();
				this.go_to_account();
			});
		},
		go_to_account: function() {
			const clientID = $( '#' + settings.client_id ).val();
			const clientSecret = $( '#' + settings.secret_key ).val();
			const auth_url = $( '#' + settings.auth_url ).val();

			$.ajax(auth_url + '/v1/tokens', {
				type: 'GET',
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json',
					'Authorization': 'Basic ' + btoa( clientID + ':' + clientSecret),
				}
			}).done((data /* { token_type: string, token: string, refresh_token: string } */, _status, res) => {
				if (res.status >= 400) {
					this.displayError();
					return;
				}

				const link = $( '#' + this.link_id );

				const redirectURL = 'https://compte.stan-app.fr';

				link.attr( 'href', `${redirectURL}?t=${data.token}&rt=${data.refresh_token}` );
				link[0].click();
				link.text( 'Accéder à mon compte')

				$('#' + this.msg_id).text(`Votre lien secret pour accéder à votre compte sur ${redirectURL} est prêt. Vous allez être rédirigé automatiquement, si vous n'êtes pas redirigé vous pouvez cliquer sur votre lien secret ci-dessous`);
			}).fail((_res, _status, _err) => {
				this.displayError();
			});
		},
		displayError: function() {
			$('#' + this.msg_id).replaceWith(function() {
				return `
					<p id="${this.id}">Vérifiez que vos identifiants ci-dessus sont corrects. Si vous êtes sûr que ce sont les bons, venez nous contacter en direct sur <a href="https://compte.stan-app.fr/signup" target="_blank">Compte Stan</a></p>
				`
			});
		},
		disable: function() {
			$('#' + settings.account_infos_btn).prop( 'disabled', true );
			$('#' + this.msg_id).text(`Renseignez vos identifiants dans les champs ci-dessus et testez la connexion avec le bouton "Tester mes identifiants Stan" pour mettre à jour votre compte.`);
			$('#' + this.link_id).text('');
		},
		enable: function() {
			$('#' + settings.account_infos_btn).prop( 'disabled', false );
			$('#' + this.msg_id).text('');
			$('#' + this.link_id).text('');
		}
	}

	const stanPayAdmin = {
		init: function() {
			$( '#woocommerce_wc_stan_payment_gateway_secret_key' ).after(
				'<button class="wc-stan-checkout-gateway-toggle-secret" style="height: 30px; margin-left: 2px; cursor: pointer"><span class="toggle-text">Afficher</span></button>'
			);
			$( '#woocommerce_wc_stan_payment_gateway_test_secret_key' ).after(
				'<button class="wc-stan-checkout-gateway-toggle-secret" style="height: 30px; margin-left: 2px; cursor: pointer"><span class="toggle-text">Afficher</span></button>'
			);

			$( '.wc-stan-checkout-gateway-toggle-secret' ).on( 'click', function( event ) {
				event.preventDefault();

				var $btnText = $( this ).closest( 'button' ).find( '.toggle-text' );
				var $input = $( this ).closest( 'tr' ).find( '.input-text' );
				var inputType = $input.attr( 'type' );

				if ( 'text' == inputType ) {
					$input.attr( 'type', 'password' );
					$btnText.text( 'Afficher' );
				} else {
					$input.attr( 'type', 'text' );
					$btnText.text( 'Cacher' );
				}
			} );

			$( document.body ).on( 'change', '#woocommerce_wc_stan_payment_gateway_testmode', function() {
				var test_secret_key = $( '#woocommerce_wc_stan_payment_gateway_test_secret_key' ).parents( 'tr' ).eq( 0 ),
					test_client_id = $( '#woocommerce_wc_stan_payment_gateway_test_client_id' ).parents( 'tr' ).eq( 0 ),
					secret_key = $( '#woocommerce_wc_stan_payment_gateway_secret_key' ).parents( 'tr' ).eq( 0 ),
					client_id = $( '#woocommerce_wc_stan_payment_gateway_client_id' ).parents( 'tr' ).eq( 0 );

				if ( $( this ).is( ':checked' ) ) {
					test_secret_key.show();
					test_client_id.show();
					secret_key.hide();
					client_id.hide();
				} else {
					test_secret_key.hide();
					test_client_id.hide();
					secret_key.show();
					client_id.show();
				}
			} );

			$( '#woocommerce_wc_stan_payment_gateway_testmode' ).trigger( 'change' );
		}
	}

	const init = () => {
		testConnection.init();
		accountInfos.init();
		stanPayAdmin.init();
	}

	$( init );

})( jQuery );
