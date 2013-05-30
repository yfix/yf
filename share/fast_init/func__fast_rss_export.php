<?php

// Fast throw prepared RSS page
function _fast_rss_export () {
	$feeds_cache_dir = INCLUDE_PATH."uploads/rss_cache/";
	if ($_GET["object"] == "category") {
		$feed_file_path = $feeds_cache_dir."feed_latest_".strtolower($_GET["page"])."_ads_in_cat_".strtolower($_GET["id"]).".xml";
//	} elseif ($_GET["object"] == "forum") {
//	} elseif ($_GET["object"] == "blog") {
/*
	|| ($_GET["object"] == "forum"	&& in_array($_GET['action'], array("rss_board", "rss_forum", "rss_topic")))
	|| ($_GET["object"] == "blog"	&& in_array($_GET['action'], array("rss_for_all_blogs", "rss_for_single_blog", "rss_for_cat", "rss_for_friends_posts")))
*/
	}

	if (!file_exists($feed_file_path) || filemtime($feed_file_path) < (time() - 3600)) {
		return false;
	}
	readfile($feed_file_path);
//	echo file_get_contents($feed_file_path);

	return true; // Means success
}
