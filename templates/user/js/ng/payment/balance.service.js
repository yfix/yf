(function () { 'use strict';

var __NS__ = 'payment.balance';

angular.module( __NS__ )

.constant( 'PaymentBalanceConfig', {
	url: '/api/payment/balance',
})

.factory( 'PaymentBalanceApi',
[ '$log', '$resource', 'PaymentBalanceConfig',
function( $log, $resource, PaymentBalanceConfig ) {
	// private
	var config = {};
	angular.extend( config, PaymentBalanceConfig );
	// service
	var service = {};
	service.url = function( value ) {
		if( value && typeof value === 'string' ) {
			config.url = value;
			service.resource = service.create_resource();
		}
		return( config.url );
	};
	service.create_resource = function() {
		return( $resource( null, null, {
			refresh:   { method : 'GET',  url : service.url(), params : { operation : 'refresh'   } } ,
			recharge:  { method : 'POST', url : service.url(), params : { operation : 'recharge'  } } ,
			operation: { method : 'POST', url : service.url(), params : { operation : 'operation' } } ,
		}));
	};
	service.resource = service.create_resource();
	service.recharge = function( options ) {
		return( service.resource.recharge({ options: options }) );
	};
	service.operation = function( options ) {
		return( service.resource.operation({ options: options }) );
	};
	service.refresh = function( options ) {
		return( service.resource.refresh({ options: options }) );
	};
	return( service );
}])

.factory( 'PaymentBalance',
[ '$log', 'PaymentBalanceApi',
function( $log, PaymentBalanceApi ) {
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
		if( value && typeof value === 'object' ) {
			angular.extend( _data, value );
			service.balance( _data.account.balance );
			service.currency( _data.currency );
		}
		return( _currency );
	};
	service.refresh = function() {
		var result = PaymentBalanceApi.refresh();
		result.$promise.then(
			function( r ) {
				if( r.response && r.response.balance ) {
					service.load( r.response.balance );
				} else {
					$log.error( 'balance->refresh is fail data:', r );
				}
			},
			function( r ) {
				$log.error( 'balance->refresh is fail transport:', r );
			}
		);
		return( result );
	};
	return( service );
}])

.controller( 'payment.balance.ctrl',
[ '$log', '$scope', 'PaymentBalance',
function( $log, $scope, PaymentBalance ) {
	$scope.balance = function() {
		$scope.currency = PaymentBalance.currency();
		return( PaymentBalance.balance() );
	};
}])

.run(
[ '$log', 'PaymentBalance',
function( $log, PaymentBalance ) {
	// init
	PaymentBalance.refresh();
}])

;

})();
