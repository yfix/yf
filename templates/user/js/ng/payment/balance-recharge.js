(function () { 'use strict';

var __NS__ = 'payment.balance.recharge';
__ANGULAR_MODULES__.push( __NS__ );

angular.module( __NS__, [
	'payment.balance',
	'ngSanitize',
	'mgcrea.ngStrap',
])

// .value( 'payment.balance.recharge.config', { payment: {}, } )

.controller( 'payment.balance.recharge.ctrl',
[ '$log', '$scope', '$timeout', 'PaymentApi', 'PaymentBalance', 'payment.balance.config', 'payment.balance.recharge.config',
function( $log, $scope, $timeout, PaymentApi, PaymentBalance, _config_balance, _config_recharge ) {
	// var config = PaymentApiConfig.config();
	var config = {};
	angular.extend( config, _config_balance  );
	$scope.payment = {};
	$scope.payout  = {};
	angular.extend( $scope.payment, _config_recharge.payment );
	$scope.amount_init = function() {
		// min, step
		$scope.amount_min           = $scope.currency_min( false );
		$scope.amount_max           = $scope.currency_max( false );
		$scope.amount_step          = $scope.currency_step( false );
		$scope.amount_payout_min    = $scope.currency_min( false, true );
		$scope.amount_payout_max    = $scope.currency_max( false, true );
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
	$scope.provider_change = function( provider, method ) {
		$scope.provider_id = +provider.provider_id;
		$scope.provider_selected = provider;
		$scope.fee               = provider._fee || 0;
		$scope.provider_currency( provider, method );
		CurrencyApi.change();
	};
	$scope.provider_currency = function( provider, method ) {
		provider = provider || $scope.provider_selected;
		var currency_allow = ( method && method.currency_allow ) || provider._currency_allow || null;
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
	$scope.currency_min = function( is_currency, is_payout ) {
		is_currency = is_currency || false;
		is_payout   = is_payout   || false;
		var currency = is_currency ? $scope.currency_selected : $scope.payment.currency;
		var round, rate = 1, value = 1, offset = 0;
		if( is_payout ) {
			value = $scope.payment.payout_limit_min || value;
		}
		if( is_currency ) {
			// currency rate
			var currency_rate = $scope.currency_rate( currency );
			rate  = currency_rate.rate;
			value = currency_rate.value;
			value = rate / value;
		}
		round = currency.minor_units;
		var result = +( +value ).toFixed( round );
		return( result );
	};
	$scope.currency_max = function( is_currency, is_payout ) {
		var max = $scope.payment.account.balance || null;
		is_currency = is_currency || false;
		is_payout   = is_payout   || false;
		var currency = is_currency ? $scope.currency_selected : $scope.payment.currency;
		var round, rate = 1, value = +max, offset = 0;
		if( is_payout ) {
			var min = $scope.payment.balance_limit_lower || 0;
			value -= +min;
		}
		if( is_currency ) {
			// currency rate
			var currency_rate = $scope.currency_rate( currency );
			rate  = currency_rate.rate;
			value = currency_rate.value;
			value = rate / value;
		}
		round = currency.minor_units;
		var result = +( +value ).toFixed( round );
		return( result );
	};
	$scope.currency_step = function( is_currency ) {
		var result = $scope.currency_min( is_currency );
		// is_currency = is_currency || false;
		// var currency = is_currency ? $scope.currency_selected : $scope.payment.currency;
		// var result = 1 / Math.pow( 10, currency.minor_units );
		return( result );
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
		var form = $scope.form_payment__payin;
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
	// payin
	$scope.payin_provider_change = function( $event, provider_id, method_id ) {
		if( $event ) { $event.stopPropagation(); }
		var action = $scope.action.payin;
		if( action.provider_id == provider_id && action.method_id == method_id ) {
			// $scope.payin_provider_init();
			return( false );
		}
		var provider = $scope.payment.providers[ provider_id ];
		var method   = method_id && provider._method_allow.payin[ method_id ] || method_id;
		var option   = method && method.option || method;
		$scope.action.payin = {
			provider_id : provider_id,
			method_id   : method_id,
			provider    : provider,
			method      : method,
			option      : option,
		};
		$scope.provider_change( provider, method );
		// amount
		$scope.amount_init();
		$scope.block_payin_provider_show = false;
		return( true );
	};
	$scope.payin_provider_init = function() {
		$scope.block_payin_provider_show = true;
		$scope.action.payin = {};
		// select first provider, method
		if(
			$scope.payment.provider.payin &&
			$scope.payment.provider.payin[ 0 ] )
		{
			var provider_id = $scope.payment.provider.payin[ 0 ];
			var provider    = $scope.payment.providers[ provider_id ];
			var method_id   = null;
			if( provider._method_allow && provider._method_allow.payin ) {
				for( method_id in provider._method_allow.payin ) break;
			}
			var method      = method_id && provider._method_allow.payin.method_id || null;
			$scope.payin_provider_change( null, provider_id, method_id );
			$scope.provider_change( provider, method );
			// amount
			$scope.amount_init();
		}
	};
	$scope.action_payin = function() {
		var payment     = $scope.action.payin;
		var currency_id = $scope.currency_id;
		var options = {
			amount      : $scope.amount,
			currency_id : currency_id,
			provider_id : payment.provider_id,
			method_id   : payment.method_id,
		};
		// angular.extend( options, payment.options );
		BalanceApi.payin( options );
	};
	// payout
	$scope.payout_provider_change = function( $event, provider_id, method_id ) {
		$event.stopPropagation();
		var action = $scope.action.payout;
		if( action.provider_id == provider_id && action.method_id == method_id ) {
			$scope.payout_provider_init();
			return( false );
		}
		var provider = $scope.payment.providers[ provider_id ];
		var method   = provider._method_allow.payout[ method_id ];
		var option   = method.option;
		var options  = null;
		// default options
		if( method.option_default ) {
			options = angular.extend( {}, method.option );
			angular.forEach( options, function( item, id ) {
				this[ id ] = null;
			}, options );
			angular.extend( options, method.option_default );
			angular.forEach( options, function( item, id ) {
				if( method.option_validation_js &&
					method.option_validation_js[ id ] &&
					method.option_validation_js[ id ].type == 'date'
				) {
					if( !item || item == '0000-00-00' ) {
						this[ id ] = null;
					} else {
						this[ id ] = new Date().from_mysql( item );
					}
				}
			}, options );
		}
		$scope.action.payout = {
			provider_id : provider_id,
			method_id   : method_id,
			provider    : provider,
			method      : method,
			option      : option,
			options     : options,
		};
		$scope.block_payout_provider_show = false;
		return( true );
	};
	$scope.payout_currency_selected = null;
	$scope.payout_provider_init = function() {
		$scope.block_payout_provider_show = true;
		$scope.action.payout = {};
		if( $scope.payment.payout_currency_allow && !$scope.payout_currency_selected ) {
			$scope.payout_currency_selected = $scope.payment.payout_currency_allow[ 0 ];
		}
	};
	$scope.payout_currency_allow_change = function( currency_id ) {
		$scope.payout_provider_init();
	};
	$scope.action_payout = function() {
		var payout = $scope.action.payout;
		var options = {
			amount      : $scope.amount,
			provider_id : payout.provider_id,
			method_id   : payout.method_id,
		};
		angular.extend( options, payout.options );
		// date
		var method = payout.method;
		angular.forEach( options, function( item, id ) {
			if( method.option_validation_js &&
				method.option_validation_js[ id ] &&
				method.option_validation_js[ id ].type == 'date'
			) {
				this[ id ] = item.to_mysql_date();
			}
		}, options );
		BalanceApi.payout( options );
	};
	// balance api
	var BalanceApi = {
		_timer : null,
		timer  : {
			id      : null,
			timeout : 5000,
			cancel  : function() {
				$timeout.cancel( this.id );
			},
		},
		_update: function( r ) {
			angular.extend( $scope.payment, r.response.payment );
			PaymentBalance.load({ account: r.response.payment.account });
			$scope.amount_init();
		},
		operation: function( options ) {
			var $this             = this;
			$scope.block_wait     = true;
			$scope.is_submitted   = true;
			$scope.status         = false;
			$scope.status_message = null;
			$timeout.cancel( $this._timer );
			$this._timer = $timeout( function() {
				var result = PaymentApi.operation( options );
				result.$promise.then(
					function( r ) {
						$scope.block_wait   = false;
						$scope.is_submitted = false;
						if( r.response && r.response.payment ) {
							$this._update( r );
						} else {
							$scope.status_message = config.message.error.operation;
							$log.error( 'balance->operation is fail operation:', r );
						}
					},
					function( r ) {
						$scope.block_wait   = false;
						$scope.is_submitted = false;
						if( r.status && r.status == 403 ) {
							$scope.status_message = config.message.error.authentication;
							// reload page for login
							$this.timer.cancel();
							$this.timer.id = $timeout( function() {
								window.location.href = config.url_login;
							}, 3000 );
						} else {
							$scope.status_message = config.message.error.request;
							$log.error( 'balance->operation is fail transport:', r );
						}
					}
				);
			}, 500 );
		},
		payin: function( options ) {
			var $this = this;
			$scope.block_wait     = true;
			$scope.is_submitted   = true;
			$scope.status         = false;
			$scope.status_message = null;
			var result = PaymentApi.payin( options );
			result.$promise.then(
				function( r ) {
					$scope.block_wait   = false;
					$scope.is_submitted = false;
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
							$this._update( r );
						}
						// hide block_balance_recharge
						$this.timer.cancel();
						$this.timer.id = $timeout( function() {
							$scope.show_balance_recharge( !$scope.status );
						}, $this.timer.timeout );
					} else {
						$scope.status_message = config.message.error.operation;
						$log.error( 'balance->payin is fail operation:', r );
					}
				},
				function( r ) {
					$scope.block_wait   = false;
					$scope.is_submitted = false;
					if( r.response && r.response.balance ) {
						$scope.status         = r.response.balance.status;
						$scope.status_message = r.response.balance.status_message;
						$log.warnig( 'balance->payin is fail transport operation:', r );
					} else {
						if( r.status && r.status == 403 ) {
							$scope.status_message = config.message.error.authentication;
							// reload page for login
							$timeout.cancel( $this.timer );
							$this.timer = $timeout( function() {
								window.location.href = config.url_login;
							}, 3000 );
						} else {
							$scope.status_message = config.message.error.request;
							$log.error( 'balance->payin is fail transport:', r );
						}
					}
				}
			);
		},
		on_payout_success: function() {
			$( '.payment__modal.payout' ).modal( 'hide' );
		},
		on_payout_fail: function() {
		},
		on_payout_validation: function() {
		},
		payout: function( options ) {
			var $this = this;
			$scope.block_wait     = true;
			$scope.is_submitted   = true;
			$scope.status         = false;
			$scope.status_message = null;
			var result = PaymentApi.payout( options );
			result.$promise.then(
				function( r ) {
					$scope.block_wait   = false;
					$scope.is_submitted = false;
					if( r.response && r.response.payout ) {
						$scope.status            = r.response.payout.status;
						$scope.status_message    = r.response.payout.status_message;
						$scope.payout.validation = r.response.payout.options || null;
						if( BalanceApi.on_payout_validation && $scope.payout.validation ) {
							BalanceApi.on_payout_validation();
						} else if( BalanceApi.on_payout_success ) {
							BalanceApi.on_payout_success();
						}
						if( r.response.payment ) {
							$this._update( r );
						}
					} else {
						$scope.status_message = config.message.error.operation;
						$log.error( 'balance->payout is fail operation:', r );
					}
				},
				function( r ) {
					$scope.block_wait   = false;
					$scope.is_submitted = false;
					if( r.response && r.response.payout ) {
						$scope.status            = r.response.payout.status;
						$scope.status_message    = r.response.payout.status_message;
						$log.warnig( 'balance->payout is fail transport operation:', r );
					} else {
						if( r.status && r.status == 403 ) {
							$scope.status_message = config.message.error.authentication;
							// reload page for login
							$timeout.cancel( $this.timer );
							$this.timer = $timeout( function() {
								window.location.href = ( '{url( /login_form )}' );
							}, 3000 );
						} else {
							$scope.status_message = config.message.error.request;
							$log.error( 'balance->payout is fail transport:', r );
						}
					}
				}
			);
		},
		cancel: function( options ) {
			var $this = this;
			$scope.block_wait     = true;
			$scope.is_submitted   = true;
			$scope.status         = false;
			$scope.status_message = null;
			var result = PaymentApi.cancel( options );
			result.$promise.then(
				function( r ) {
					$scope.block_wait   = false;
					$scope.is_submitted = false;
					if( r.response && r.response.cancel ) {
						$scope.status            = r.response.cancel.status;
						$scope.status_message    = r.response.cancel.status_message;
						if( r.response.payment ) {
							$this._update( r );
						}
					} else {
						$scope.status_message = config.message.error.operation;
						$log.error( 'balance->cancel is fail operation:', r );
					}
				},
				function( r ) {
					$scope.block_wait   = false;
					$scope.is_submitted = false;
					if( r.response && r.response.payout ) {
						$scope.status            = r.response.cancel.status;
						$scope.status_message    = r.response.cancel.status_message;
						$log.warnig( 'balance->cancel is fail transport operation:', r );
					} else {
						if( r.status && r.status == 403 ) {
							$scope.status_message = config.message.error.authentication;
							// reload page for login
							$timeout.cancel( $this.timer );
							$this.timer = $timeout( function() {
								window.location.href = ( '{url( /login_form )}' );
							}, 3000 );
						} else {
							$scope.status_message = config.message.error.request;
							$log.error( 'balance->cancel is fail transport:', r );
						}
					}
				}
			);
		},
	};
	$scope.cancel = function( options ) {
		BalanceApi.cancel( options );
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
		var payment  = $scope.payment;
		var page     = +payment.operation_pagination.page;
		$scope.operation_page_change( page + direction );
	};
	$scope.operation_page_change = function( value ) {
		var $this   = this;
		var payment = $scope.payment;
		var pages   = +payment.operation_pagination.pages;
		var page   = +value;
		if( page < 1 || page > pages ) { return( false ); }
		BalanceApi.operation({ page: page });
		return( true );
	};
	// init
	$scope.block_wait   = false;
	$scope.is_submitted = false;
	$scope.action = {
		'deposition' : {},
		'payment'    : {},
	};
	$scope.payin_provider_init();
	$scope.payout_provider_init();
}])

;

Date.prototype.from_mysql = function( str ) {
	if( typeof str === 'string' ) {
		var is_422 = false;
		var is_224 = false;
		if( /^\d{4}[\.\-]\d{1,2}[\.\-]\d{1,2}/.test( str ) ) {
			is_422 = true;
		} else if( /\d{1,2}[\.\-]\d{1,2}[\.\-]\d{4}$/.test( str ) ) {
			is_224 = true;
		} else {
			return( null );
		}
		var t = str.split(/[-. :]/);
		var result = null;
		if( is_422 ) {
			if( t.length == 3 ) {
				result = new Date( Date.UTC( t[0], t[1] - 1, t[2] ) );
			} else if( t.length == 6 ) {
				result = new Date( Date.UTC( t[0], t[1] - 1, t[2], t[3] || 0, t[4] || 0, t[5] || 0 ) );
			}
		} else if( is_224 ) {
			if( t.length == 3 ) {
				result = new Date( Date.UTC( t[2], t[1] - 1, t[0] ) );
			} else if( t.length == 6 ) {
				result = new Date( Date.UTC( t[3], t[4] - 1, t[5], t[0] || 0, t[1] || 0, t[2] || 0 ) );
			}
		}
		return( result );
	}
	return( null );
};

Date.prototype.to_mysql_date = function() {
	function twoDigits(d) {
		if(0 <= d && d < 10) return "0" + d.toString();
		if(-10 < d && d < 0) return "-0" + (-1*d).toString();
		return d.toString();
	}
	return( this.getUTCFullYear() + "-" + twoDigits(1 + this.getUTCMonth()) + "-" + twoDigits(this.getUTCDate()) );
};

Date.prototype.to_mysql_datetime = function() {
	function twoDigits(d) {
		if(0 <= d && d < 10) return "0" + d.toString();
		if(-10 < d && d < 0) return "-0" + (-1*d).toString();
		return d.toString();
	}
	return this.getUTCFullYear() + "-" + twoDigits(1 + this.getUTCMonth()) + "-" + twoDigits(this.getUTCDate()) + " " + twoDigits(this.getUTCHours()) + ":" + twoDigits(this.getUTCMinutes()) + ":" + twoDigits(this.getUTCSeconds());
};

})();
