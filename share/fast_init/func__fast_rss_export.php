<?php

// Fast throw prepared RSS page
function _fast_rss_export () {
	$feeds_cache_dir = INCLUDE_PATH.'uploads/rss_cache/';
	if ($_GET['object'] == 'category') {
		$feed_file_path = $feeds_cache_dir.'feed_latest_'.strtolower($_GET['page']).'_ads_in_cat_'.strtolower($_GET['id']).'.xml';
	}
	if (!file_exists($feed_file_path) || filemtime($feed_file_path) < (time() - 3600)) {
		return false;
	}
	readfile($feed_file_path);
	return true; // Means success
}
