(function () { 'use strict';

var __NS__ = 'uix';

angular.module( __NS__ )

.directive( 'wait',
[ '$log', '$timeout',
function( $log, $timeout ) {
	return {
		restrict: 'AE',
		link: function( $scope, element, attrs ) {
			var timer;
			$scope.$watch( 'active', function( value ) {
				var timeout;
				if( value ) {
					timeout = $scope.timeoutIn || 2000;
				} else {
					timeout = $scope.timeoutIn || 0;
				}
				$timeout.cancel( timer );
				timer = $timeout( function() {
					$scope.is_active = value;
				}, timeout );
			});
		},
		scope: {
			'active'     : '=',
			'timeoutIn'  : '=',
			'timeoutOut' : '=',
		},
		template: [
			'<style>',
			'.ngwd-wait {',
				'position   : fixed;',
				'display    : table;',
				'top        : 0;',
				'left       : 0;',
				'width      : 100%;',
				'height     : 100%;',
				'background : RGBA( 0,0,0, 0.5 );',
				'z-index    : +111;',
				'font-size  : 5em;',
				'text-align : center;',
			'}',
			'.ngwd-wait .ngwd-content {',
				'vertical-align : middle;',
				'display        : table-cell;',
			'}',
			'</style>',
			'<div class="ngwd-wait" ng-show="is_active"><div class="ngwd-content"><i class="fa fa-refresh fa-spin test-success"></i></div></div>',
		].join(''),
	};
}])

;

})();
