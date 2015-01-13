(function () { 'use strict';

var __NS__ = 'uix';

angular.module( __NS__ )

.directive( 'animateOnChange',
[ '$log', '$animate',
function( $log, $animate ) {
	return {
		restrict: 'A',
		scope: {
			content: '=ngBind'
		},
		link: function( scope, element, attrs ) {
			scope.$watch( 'content', function( item_new, item_old ) {
				$animate.removeClass( element, 'change' );
				$animate.addClass( element, 'change' );
			});
		},
	};
}])

;

})();
