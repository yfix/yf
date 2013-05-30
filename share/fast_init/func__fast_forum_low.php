<?php

// Fast process forum low skin
function _fast_forum_low () {

	// Internal function
	function _get_cache ($cache_name = "") {
		// Reference to the categories array
		$cache_file = INCLUDE_PATH."core_cache/cache_".$cache_name.".php";
		if (!file_exists($cache_file)) {
			return false;
		}
		// Delete expired cache files
		$last_modified = filemtime($cache_file);
		$TTL = 86400;
		$cache_ttl = module_conf('cache', 'FILES_TTL');
		if (!empty($cache_ttl)) {
			$TTL = intval($cache_ttl);
		}
		if ($last_modified < (time() - $TTL)) {
			return false;
		}
		// Get data from file
		return eval("return ".substr(file_get_contents($cache_file), 7, -4).";");
	}

	// Get type of display
	$TYPE	= "main";
	$_type	= $_GET["id"]{0};
	if (strlen($_GET["id"]) && in_array($_type, array("f","t"))) {
		if ($_type == "f") {
			$TYPE	= "forum";
		} elseif ($_type == "t") {
			$TYPE	= "topic";
		}
	}
	$ID = intval(substr($_GET["id"], 1));
	// Default low page
	$body = "";
	if ($TYPE == "main" || empty($ID)) {
		$cat_id = $ID;

		$cats_array		= _get_cache("forum_categories");
		$forums_array	= _get_cache("forum_forums");
		if (empty($cats_array) || empty($forums_array)) {
			return false;
		}

		$body = "<a href='./?object=forum&action=show".($cat_id ? "&id=".$cat_id : "")."'><b>Full version</b></a><br />\r\n";
		$body .= "<ul>\r\n";
		// Process categories
		foreach ((array)$cats_array as $cat_info) {
			$body .= "<li><a href='./?object=forum&action=low&id=".$cat_info["id"]."'>".htmlspecialchars($cat_info["name"])."</a></li>\r\n";
			$body .= "<ul>\r\n";
			// Filter category if specified one
			if (!empty($cat_id) && $cat_info["id"] != $cat_id) {
				continue;
			}
			// Process forums
			foreach ((array)$forums_array as $_forum_info) {
				// Skip forums from other categories
				if ($_forum_info["category"] != $cat_info["id"]) {
					continue;
				}
				// Skip sub-forums here
				if (!empty($_forum_info["parent"])) {
					continue;
				}
				$body .= "<li>&nbsp;&nbsp;<a href='./?object=forum&action=low&id=f".$_forum_info["id"]."'>".htmlspecialchars($_forum_info["name"])."</a> <small>(".$_forum_info["num_posts"]." posts)</small></li>\r\n";
			}
			$body .= "</ul>\r\n";
		}
		$body .= "</ul>\r\n";
	} elseif ($TYPE == "forum")	{
		// NOT DONE HERE YET
		return false;
	} elseif ($TYPE == "topic")	{
		// NOT DONE HERE YET
		return false;
	}
// TODO: need to move to the framework
	$TPL_PATH = "templates/new_1/";
	$body = str_replace(array("{body}","{css_path}"), array($body, WEB_PATH.$TPL_PATH), file_get_contents(INCLUDE_PATH.$TPL_PATH."forum/low/main.stpl"));
	// Replace relative links to their full paths
	$images_path	= WEB_PATH. $TPL_PATH. "images/";
	// Array of pairs "match->replace" for str_replace
	$to_replace = array(
		"\"images/"			=> "\"".$images_path,
		"'images/"			=> "'".$images_path,
		"src=\"uploads/"	=> "src=\"".WEB_PATH."uploads/",
		"'./?"				=> "'".WEB_PATH."?",
	);
	$body = str_replace(array_keys($to_replace), array_values($to_replace), $body);

	echo $body;

	return true; // Means success
}
