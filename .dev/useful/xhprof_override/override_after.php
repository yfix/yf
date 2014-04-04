<?php

if( defined( 'XHPROF_ENABLE' ) && XHPROF_ENABLE ) {
	// stop profiler
	$xhprof_data = xhprof_disable();
	include_once XHPROF_PATH . '/xhprof_lib/utils/xhprof_lib.php';
	include_once XHPROF_PATH . '/xhprof_lib/utils/xhprof_runs.php';
	// save raw data for this profiler run using default
	// implementation of iXHProfRuns.
	$xhprof_runs = new XHProfRuns_Default();
	// save the run under a namespace "xhprof_foo"
	// $xhprof_namespace = md5( $_GLOBAL[ 'XHPROF_NAMESPACE' ] );
/*
	$xhprof_namespace = str_replace(
		  array( '.', '/', '&' )
		, array( '_', '~', '__' ),
		$_SERVER[ 'HTTP_HOST' ]
		. $_SERVER[ 'REQUEST_URI' ]
	);
*/
	$run_id = $xhprof_runs->save_run( $xhprof_data, $xhprof_namespace );

	// compile url
	$xhprof_namespace_uri = urlencode( $xhprof_namespace );
	// $xhprof_web           = XHPROF_WEB;
	// $xhprof_url           = "<a href='http://$xhprof_web/index.php?run=$run_id&source=$xhprof_namespace_uri'>XHProf</a>";
	// echo "$xhprof_url\n";

#	$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_foo");
	$xhprof_url = '<div style="position:fixed;bottom:0;right:10px;"><a class="btn btn-mini btn-xs" href="http://'.XHPROF_WEB.'/?run='.$run_id.'&source='.$xhprof_namespace_uri.'" target="_blank">XHProf</a></div>';
	if (!conf('IS_AJAX') && !main()->NO_GRAPHICS) {
		echo $xhprof_url;
	}
}
