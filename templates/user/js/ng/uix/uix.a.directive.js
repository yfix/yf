(function () { 'use strict';

var __NS__ = 'uix';

angular.module( __NS__ )

.directive( 'a', function() {
	return {
		restrict : 'E',
		link     : function( scope, element, attrs ) {
			if( attrs.ngClick || attrs.href === '' || attrs.href === '#' ) {
				element.on( 'click', function( event ){
					event.preventDefault();
				});
			}
		}
	};
})

;

})();
