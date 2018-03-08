(function () { 'use strict';

var __NS__ = 'uix';

angular.module( __NS__ )

.directive( 'animateOnChange',
[ '$log', '$animate',
function( $log, $animate ) {
	return {
		restrict: 'A',
		scope: {
			content1: '=ngBind',
			content2: '=animateOnChange',
		},
		link: function( scope, element, attrs ) {
			scope.$watch( 'content1', function( item_new, item_old ) {
				$animate.addClass( element, 'change' );
				$animate.removeClass( element, 'change' );
			});
			scope.$watch( 'content2', function( item_new, item_old ) {
				$animate.addClass( element, 'change' );
				$animate.removeClass( element, 'change' );
			});
		},
	};
}])

;

})();
