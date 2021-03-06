(function () { 'use strict';

var __NS__ = 'payment.balance';

angular.module( __NS__ )

// .value( 'payment.balance.config', {
	// url       : '/api/payment/balance',
	// url_login : '/login_form/login',
// })

.factory( 'PaymentApiConfig',
[ '$log',
function( $log ) {
	var _config_default = {
		url       : '/api/payment/balance',
		url_login : '/login_form/login',
	};
	var _config = _config_default;
	var service = {};
	service.config = function( config ) {
		if( config && angular.isObject( config ) ) {
			angular.extend( _config, config  );
		}
		return( _config );
	};
	return( service );
}])

.factory( 'PaymentApi',
[ '$log', '$resource', 'payment.balance.config',
function( $log, $resource, _config ) {
	// private
	// var config = PaymentApiConfig.config();
	var config = {};
	angular.extend( config, _config  );
	// service
	var service = {};
	service.config = function() {
		return( config );
	};
	service.url = function( value ) {
		if( value && typeof value === 'string' ) {
			config.url = value;
			service.resource = service.create_resource();
		}
		return( config.url );
	};
	service.create_resource = function() {
		return( $resource( null, null, {
			refresh   : { method : 'GET' , url : service.url(), params : { operation : 'refresh'   } },
			recharge  : { method : 'POST', url : service.url(), params : { operation : 'recharge'  } },
			payin     : { method : 'POST', url : service.url(), params : { operation : 'payin'     } },
			payout    : { method : 'POST', url : service.url(), params : { operation : 'payout'    } },
			operation : { method : 'POST', url : service.url(), params : { operation : 'operation' } },
			cancel    : { method : 'POST', url : service.url(), params : { operation : 'cancel'    } },
		}));
	};
	service.resource = service.create_resource();
	service.refresh   = function( options ) { return( service.resource.refresh({ options: options }) ); };
	service.recharge  = function( options ) { return( service.resource.recharge({ options: options }) ); };
	service.payin     = function( options ) { return( service.resource.payin({ options: options }) ); };
	service.payout    = function( options ) { return( service.resource.payout({ options: options }) ); };
	service.operation = function( options ) { return( service.resource.operation({ options: options }) ); };
	service.cancel    = function( options ) { return( service.resource.cancel({ options: options }) ); };
	return( service );
}])

.factory( 'PaymentBalance',
[ '$log', '$timeout', 'PaymentApi',
function( $log, $timeout, PaymentApi ) {
	// private
	var _data     = {};
	var _balance  = 0;
	var _currency = {};
	// handler
	var service = {};
	service.balance = function( value ) {
		if( value ) {
			_balance = +value;
		}
		return( _balance );
	};
	service.currency = function( value ) {
		if( value && typeof value === 'object' ) {
			_currency = value;
		}
		return( _currency );
	};
	service.load = function( value ) {
		if( value && typeof value === 'object' && value.account ) {
			angular.extend( _data, value );
			service.balance( _data.account.balance );
			service.currency( _data.currency );
		}
		return( _currency );
	};
	service.is_load = function() {
		var result = false;
		var value = _data;
		if( value && typeof value === 'object' && value.account ) {
			result = true;
		}
		return( result );
	};
	service.refresh = function() {
		var result = PaymentApi.refresh();
		var $this = this;
		result.$promise.then(
			function( r ) {
				if( r.response && r.response.balance ) {
					service.load( r.response.balance );
				} else {
					$log.error( 'balance->refresh is fail data:', r );
				}
			},
			function( r ) {
				if( r.status && r.status == 403 ) {
					$log.log( 'Требуется авторизация' );
					$timeout.cancel( $this.timer );
					$this.timer = $timeout( function() {
						// window.location.href = PaymentApi.config().url_login;
						window.location.reload();
					}, 3000 );
					return( false );
				}
				$log.error( 'balance->refresh is fail transport:', r );
			}
		);
		return( result );
	};
	return( service );
}])

.controller( 'payment.balance.ctrl',
[ '$log', '$scope', '$timeout', 'PaymentBalance',
function( $log, $scope, $timeout, PaymentBalance ) {
	$scope.is_load = function() {
		var is_load = PaymentBalance.is_load();
		if( is_load ) {
			$scope.currency = PaymentBalance.currency();
			$scope.balance  = PaymentBalance.balance();
		} else {
			$scope.currency = null;
			$scope.balance  = null;
		}
		return( is_load );
	};
	var timer = null;
	var timeout = 1000 * 60 * 5; // 5 min
	// timeout = 2000; // debug 2 sec
	var refresh = function() {
		var result = PaymentBalance.refresh();
		result.$promise.then( function( r ) {
			if( $scope.is_load ) { autorefresh(); }
		});
	};
	var autorefresh = function() {
		$timeout.cancel( timer );
		timer = $timeout( function() {
			refresh();
		}, timeout );
	};
	refresh();
}])

;

})();
