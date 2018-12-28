<?php

// from: http://stackoverflow.com/questions/3034874/sequencing-ajax-requests/3035268#3035268
// and fixes from: https://github.com/adrian-fernandez/jquery-ajaxQueue/blob/master/src/jQuery.ajaxQueue.js
return ['versions' => ['master' => ['js' => '
(function($) {

// jQuery on an empty object, we are going to use this as our Queue
var ajaxQueue = $({ });

$.ajaxQueue = function( ajaxOpts ) {
	var jqXHR,
		dfd = $.Deferred(),
		promise = dfd.promise();

	//  If there is no ajax request return an empty 200 code
	if (typeof ajaxOpts == "undefined"){
		return $.Deferred(function() {
				this.resolve(["", "200", jqXHR]); 
			}).promise();
	}

	// run the actual query
	function doRequest( next ) {
		jqXHR = $.ajax( ajaxOpts );
		jqXHR.done( dfd.resolve )
			.fail( dfd.reject )
			.then( next, next );
	}

	// queue our ajax request
	ajaxQueue.queue( doRequest );

	// add the abort method
	promise.abort = function( statusText ) {

		// proxy abort to the jqXHR if it is active
		if ( jqXHR ) {
			return jqXHR.abort( statusText );
		}

		// if there wasn"t already a jqXHR we need to remove from queue
		var queue = ajaxQueue.queue(),
			index = $.inArray( doRequest, queue );

		if ( index > -1 ) {
			queue.splice( index, 1 );
		}

		// and then reject the deferred
		dfd.rejectWith( ajaxOpts.context || ajaxOpts, [ promise, statusText, "" ] );
		return promise;
	};

	return promise;
};

})(jQuery);
	']],
    'require' => [
        'asset' => 'jquery',
    ],
];
