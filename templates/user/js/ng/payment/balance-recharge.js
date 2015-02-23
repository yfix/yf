(function () { 'use strict';

var __NS__ = 'payment.balance.recharge';
__ANGULAR_MODULES__.push( __NS__ );

angular.module( __NS__, [
	'payment.balance',
])

.value( 'PaymentBalanceRechargeConfig', { payment: {}, } )

.controller( 'payment.balance.recharge.ctrl',
[ '$log', '$scope', '$timeout', 'PaymentBalanceApi', 'PaymentBalance', 'PaymentBalanceRechargeConfig',
function( $log, $scope, $timeout, PaymentBalanceApi, PaymentBalance, PaymentBalanceRechargeConfig ) {
	$scope.payment = {};
	angular.extend( $scope.payment, PaymentBalanceRechargeConfig.payment );
	$scope.amount_init = function() {
		// min, step
		$scope.amount_min           = $scope.currency_min( false );
		$scope.amount_step          = $scope.currency_step( false );
		$scope.amount_currency_min  = $scope.currency_min( true );
		$scope.amount_currency_step = $scope.currency_step( true );
	};
	var CurrencyApi = {
		timer: {
			id      : null,
			timeout : 100,
			cancel  : function() {
				$timeout.cancel( this.id );
			},
			start : function( _function_ ) {
				this.cancel();
				this.id = $timeout( _function_, this.timeout );
			},
		},
		change: function() {
			var $this = this;
			var amount = $scope.amount;
			if( amount ) {
				$this.timer.start( function() {
					$scope.amount = amount;
					$scope.amount_change( false );
				});
			}
		},
	};
	$scope.currency_change = function() {
		$scope.amount_init();
		$scope.currency_id = $scope.currency_selected.currency_id;
		// update amount
		CurrencyApi.change();
	};
	// block
	$scope.show_balance_recharge = function( show ) {
		$scope.block_balance_recharge = !!show;
		$scope.block_operation = !show;
	};
	$scope.show_balance_recharge( false );
	$scope.provider_change = function( provider_id ) {
		$scope.provider_id = +provider_id;
		var provider = $scope.payment.providers[ provider_id ];
		$scope.provider_selected = provider;
		$scope.fee               = provider._fee || 0;
		$scope.provider_currency( provider );
		CurrencyApi.change();
	};
	$scope.provider_currency = function( provider ) {
		provider = provider || $scope.provider_selected;
		var currency_allow = provider._currency_allow || null;
		var index, currencies = {};
		if( currency_allow ) {
			angular.forEach( $scope.payment.currencies, function( item, id ) {
				if( currency_allow[ id ] ) {
					this[ id ] = item;
				}
			}, currencies );
		} else {
			for( index in $scope.payment.currencies ) break;
			currencies[ index ] = $scope.payment.currencies[ index ];
		}
		$scope.currencies = currencies;
		// select first
		if( !$scope.currencies[ $scope.currency_id ] ) {
			for( index in $scope.currencies ) break;
			$scope.currency_id       = index;
			$scope.currency_selected = $scope.currencies[ index ];
			$scope.currency_change();
		}
	};
	// currency change: min, step
	$scope.currency_min = function( is_currency ) {
		is_currency = is_currency || false;
		var currency = is_currency ? $scope.currency_selected : $scope.payment.currency;
		var round, rate = 1, value = 1, offset = 0;
		if( is_currency ) {
			// currency rate
			var currency_rate = $scope.currency_rate( currency );
			rate  = currency_rate.rate;
			value = currency_rate.value;
		}
		round = currency.minor_units;
		var result = +( rate / value ).toFixed( round );
		return( result );
	};
	$scope.currency_step = function( is_currency ) {
		return( $scope.currency_min( is_currency ) );
	};
	$scope.currency_rate = function( currency ) {
		// currency rate
		var currency_id = currency.currency_id;
		var rate = 1, value = 1;
		if( $scope.payment.currency_rate[ currency_id ] ) {
			rate  = $scope.payment.currency_rate[ currency_id ].rate;
			value = $scope.payment.currency_rate[ currency_id ].value;
		}
		return({ rate: rate, value: value });
	};
	// calc recharge amount in currency
	$scope.amount_change = function( is_currency ) {
		BalanceApi.timer.cancel();
		// init calc
		is_currency = is_currency || false;
		var form = $scope.form_payment__deposition;
		if( !angular.isObject( form ) ||
			(
				( is_currency && form.amount_currency.$error.number ) ||
				( !is_currency && form.amount.$error.number )
			)
		) { return( false ); }
		// currency rate
		var currency_rate = $scope.currency_rate( $scope.currency_selected );
		var rate  = currency_rate.rate;
		var value = currency_rate.value;
		var round           = +$scope.payment.currency.minor_units;
		var round_currency  = +$scope.currency_selected.minor_units;
		// get amount
		var amount          = +$scope.amount || 0;
		var amount_currency = +$scope.amount_currency || 0;
		if( is_currency ) {
			// to UNT
			amount = amount_currency / rate * value;
		}
		amount = +amount.toFixed( round );
		// to USD, etc
		amount_currency  = amount * rate / value;
		var amount_currency_round = +amount_currency.toFixed( round_currency );
		// fee
		var amount_currency_fee       = amount_currency_round * ( +$scope.fee / 100 );
		var amount_currency_fee_round = amount_currency_fee.toFixed( round_currency );
		// save amount
		$scope.amount           = amount;
		// total
		$scope._amount_currency = ( +amount_currency_round ) + ( +amount_currency_fee_round );
		if( !is_currency ) {
			$scope.amount_currency     = amount_currency_round;
			$scope.amount_currency_fee = amount_currency_fee_round;
		}
	};
	// payment out
	$scope.paymentout_provider_change = function( provider_id, method_id ) {
		angular.extend( $scope.action.payment, {
			provider_id : provider_id,
			method_id   : method_id,
		});
		$log.log( 'method', $scope.action );
	};
	var BalanceApi = {
		timer: {
			id      : null,
			timeout : 5000,
			cancel  : function() {
				$timeout.cancel( this.id );
			},
		},
		operation: function( options ) {
			var $this = this;
			$scope.block_wait     = true;
			$scope.status         = false;
			$scope.status_message = null;
			var result = PaymentBalanceApi.operation( options );
			result.$promise.then(
				function( r ) {
					$scope.block_wait = false;
					if( r.response && r.response.payment ) {
						if( r.response.payment ) {
							angular.extend( $scope.payment, r.response.payment );
							PaymentBalance.load({ account: r.response.payment.account });
						}
					} else {
						$scope.status_message = 'Ошибка при выполнении операции';
						$log.error( 'balance->operation is fail operation:', r );
					}
				},
				function( r ) {
					$scope.block_wait = false;
					if( r.status && r.status == 403 ) {
						$scope.status_message = 'Требуется авторизация';
						// reload page for login
						$this.timer.cancel();
						$this.timer.id = $timeout( function() {
							window.location.href = ( '{url( /login_form )}' );
						}, 3000 );
					} else {
						$scope.status_message = 'Ошибка при выполнении запроса';
						$log.error( 'balance->operation is fail transport:', r );
					}
				}
			);
		},
		recharge: function( options ) {
			var $this = this;
			$scope.block_wait     = true;
			$scope.status         = false;
			$scope.status_message = null;
			var result = PaymentBalanceApi.recharge( options );
			result.$promise.then(
				function( r ) {
					$scope.block_wait = false;
					if( r.response && r.response.balance ) {
						// provider request form
						if( r.response.balance.form ) {
							var form = r.response.balance.form;
							var $form = angular.element( form );
							$form.appendTo( document.body ).submit();
							return;
						}
						$scope.status            = r.response.balance.status;
						$scope.status_message    = r.response.balance.status_message;
						if( r.response.payment ) {
							angular.extend( $scope.payment, r.response.payment);
							PaymentBalance.load({ account: r.response.payment.account });
						}
						// hide block_balance_recharge
						$this.timer.cancel();
						$this.timer.id = $timeout( function() {
							$scope.show_balance_recharge( !$scope.status );
						}, $this.timer.timeout );
					} else {
						$scope.status_message = 'Ошибка при выполнении операции';
						$log.error( 'balance->recharge is fail operation:', r );
					}
				},
				function( r ) {
					$scope.block_wait = false;
					if( r.response && r.response.balance ) {
						$scope.status         = r.response.balance.status;
						$scope.status_message = r.response.balance.status_message;
						$log.warnig( 'balance->recharge is fail transport operation:', r );
					} else {
						if( r.status && r.status == 403 ) {
							$scope.status_message = 'Требуется авторизация';
							// reload page for login
							$timeout.cancel( $this.timer );
							$this.timer = $timeout( function() {
								window.location.href = ( '{url( /login_form )}' );
							}, 3000 );
						} else {
							$scope.status_message = 'Ошибка при выполнении запроса';
							$log.error( 'balance->recharge is fail transport:', r );
						}
					}
				}
			);
		},
	};
	$scope.balance_recharge = function() {
		var amount      = +$scope.amount;
		var provider_id = +$scope.provider_id;
		var currency_id = $scope.currency_id;
		BalanceApi.recharge({
			amount      : amount,
			currency_id : currency_id,
			provider_id : provider_id,
		});
	};
	$scope.balance_refresh = function() {
		BalanceApi.operation({ page: 1 });
	};
	$scope.operation_first = function() {
		BalanceApi.operation({ page: 1 });
	};
	$scope.operation_last = function() {
		BalanceApi.operation({ page: $scope.payment.operation_pagination.pages });
	};
	$scope.operation = function( direction ) {
		var payment      = $scope.payment;
		var count        = payment.operation.length;
		var page_per     = payment.operation_pagination.page_per;
		var page_current = payment.operation_pagination.page;
		// next
		if( direction > 0 && count < page_per ) { return( false ); }
		// previous
		if( direction < 0 && page_current <= 1 ) { return( false ); }
		// request
		var page = page_current + direction;
		BalanceApi.operation({ page: page });
	};
	// init
	$scope.block_wait = false;
	$scope.action = {
		'deposition' : {},
		'payment'    : {},
	};
	// select first provider
	if( $scope.payment.provider.deposition && $scope.payment.provider.deposition[ 0 ] ) {
		$scope.provider_change( $scope.payment.provider.deposition[ 0 ] );
		// amount
		$scope.amount_init();
	}
}])

;

})();
