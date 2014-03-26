<?php

/**
* Gallery statistics handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_gallery_stats {
	
	/**
	* Display total gallery stats
	*/
	function _show_stats($MAIN_STPL = "", $ITEM_STPL = "", $NUM_ITEM = 0) {
		if(!empty($NUM_ITEM)){
			module('gallery')->STATS_NUM_LATEST = $NUM_ITEM;
		}
// TODO: decide what to here if HIDE_TOTAL_ID enabled
		if (empty($MAIN_STPL)) {
			$MAIN_STPL = 'gallery'."/stats_main";
		}
		if (empty($GLOBALS['user_info']) && !empty(main()->_user_info)) {
			$GLOBALS['user_info'] = main()->_user_info;
		}
		// Create sql code for the access checks
		// We want only clean, non-private, normal by content level photos on the main page
		if (MAIN_TYPE_USER) {
			$PHOTOS_ACCESS_SQL = " AND is_public = '1' ";
		}
		$sql = "SELECT * FROM ".db('gallery_photos')." WHERE 1 ".$PHOTOS_ACCESS_SQL;
		if (module('gallery')->LATEST_GROUP_BY_USER) {
			$sql .= " GROUP BY user_id ";
		}
		$order_by_sql = " ORDER BY add_date DESC ";
		$url = "./?object=".'gallery'."&action=latest";
		list($add_sql, $latest_pages, $latest_total) = common()->divide_pages($sql, $url, null, module('gallery')->STATS_NUM_LATEST * 2);
		// Get top latest photos
		$Q = db()->query($sql.$order_by_sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$latest_photos_array[$A["id"]] = $A;
			$latest_users_ids[$A["user_id"]] = $A["user_id"];
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		if (module('gallery')->ALLOW_GEO_FILTERING) {
			$geo_data = main()->_USER_GEO_DATA;
		}
		// Geo top galleries
		if (!empty($geo_data["country_code"])) {
			if (GEO_LIMIT_COUNTRY != "GEO_LIMIT_COUNTRY" && GEO_LIMIT_COUNTRY != "" && $geo_data["country_code"] != GEO_LIMIT_COUNTRY) {
				$_POST["country"]	= GEO_LIMIT_COUNTRY;
				$_POST["state"]		= "";
			//	$_POST["city"]		= "";
			} else {
				$_POST["country"]	= $geo_data["country_code"];
				if ($geo_data["country_code"] == "US") {
					$_POST["state"]		= $geo_data["region_code"];
				}
			//	$_POST["city"]		= $geo_data["city_name"];
			}
			$_POST["gender"]	= "Female";
			module('gallery')->save_filter(1);
			$geo_top_galleries = $this->show_all_galleries(array(
				"stpl_main"	=> 'gallery'."/stats_geo_top_main",
				"for_stats"	=> true,
			));
		}
		// Geo latest photos
		if (!empty($geo_data["country_code"])) {
			if (GEO_LIMIT_COUNTRY != "GEO_LIMIT_COUNTRY" && GEO_LIMIT_COUNTRY != "" && $geo_data["country_code"] != GEO_LIMIT_COUNTRY) {
				$geo_filter_sql = " AND geo_cc = '"._es(GEO_LIMIT_COUNTRY)."' ";
			} else {
				$geo_filter_sql = " AND geo_cc = '"._es($geo_data["country_code"])."' ";
				if (strlen($geo_data["region_code"])) {
					$geo_filter_sql .= " AND geo_rc = '"._es($geo_data["region_code"])."' ";
				}
			}
			if (module('gallery')->LATEST_GROUP_BY_USER) {
				$group_by_sql = " GROUP BY user_id ";
			}
			// Get top latest photos by geo
			$sql = "SELECT * FROM ".db('gallery_photos')." ".(module('gallery')->USE_SQL_FORCE_KEY ? "/*!40000 USE KEY (user_id) */" : "")." WHERE 1 "
				.$PHOTOS_ACCESS_SQL
				.$geo_filter_sql
				.$group_by_sql;
			$order_by_sql = " ORDER BY add_date DESC ";
			$url = "./?object=".'gallery'."&action=latest_geo";
			_class('divide_pages', 'classes/common/')->SQL_COUNT_REWRITE = false;
			list($add_sql, $geo_latest_pages, $geo_latest_total) = common()->divide_pages(str_replace("SELECT *", "SELECT id,user_id", $sql), $url, null, module('gallery')->STATS_NUM_LATEST * 2);
			// Get from db
			$Q = db()->query($sql.$order_by_sql.$add_sql);
			while ($A = db()->fetch_assoc($Q)) {
				$geo_latest_photos_array[$A["id"]]		= $A;
				$geo_latest_users_ids[$A["user_id"]]	= $A["user_id"];
				$users_ids[$A["user_id"]] = $A["user_id"];
			}
		}
		// Get users names
		unset($users_ids[""]);
		if (!empty($users_ids)) {
			$users_data = user(array_keys($users_ids), array("id","login","name","nick","profile_url","photo_verified"), array("WHERE" => array("active" => "1")));
			foreach ((array)$users_data as $A) {
				$users_names[$A["id"]] = _display_name($A);
			}
		}
		module('gallery')->_users_names = $users_names;
		// Prefetch folders infos
		module('gallery')->_get_user_folders_for_ids($users_ids);
		// Prepare ids
		foreach ((array)$latest_photos_array as $A) {
			$owners_ids[$A["id"]]	= $A["user_id"];
			$objects_ids[$A["id"]]	= $A["id"];
		}
		foreach ((array)$geo_latest_photos_array as $A) {
			$owners_ids[$A["id"]]	= $A["user_id"];
			$objects_ids[$A["id"]]	= $A["id"];
		}

		// Prepare tags block
		$this->_tags = module('gallery')->_show_tags($objects_ids, array("simple" => 1));

		// Process latest photos
		foreach ((array)$latest_photos_array as $A) {
			// Stop when number of users reached
			if (++$c1 > module('gallery')->STATS_NUM_LATEST) {
				break;
			}
			$latest_photos .= $this->_show_latest_item($A, $users_names[$A["user_id"]], $ITEM_STPL);
		}
		// Process geo latest photos
		$GLOBALS["_photo_items_counter"] = 0;
		foreach ((array)$geo_latest_photos_array as $A) {
			// Stop when number of users reached
			if (++$c2 > module('gallery')->STATS_NUM_LATEST) {
				break;
			}
			$geo_latest_photos .= $this->_show_latest_item($A, $users_names[$A["user_id"]]);
		}
		// Process template
		$replace = array(
			"is_logged_in"			=> intval((bool) main()->USER_ID),
			"show_own_gallery_link"	=> main()->USER_ID ? "./?object=".'gallery'."&action=show_gallery&id=".intval(main()->USER_ID)._add_get(array("page")) : "",
			"all_galleries_link"	=> "./?object=".'gallery'."&action=show_all_galleries"._add_get(array("page")),
			"latest_photos"			=> $latest_photos,
			"num_latest"			=> intval(module('gallery')->STATS_NUM_LATEST),
			"latest_pages"			=> $latest_pages,
			"latest_total"			=> intval($latest_total),
			"geo_latest_photos"		=> $geo_latest_photos,
			"geo_num_latest"		=> intval(module('gallery')->STATS_NUM_LATEST),
			"geo_latest_pages"		=> $geo_latest_pages,
			"geo_latest_total"		=> intval($geo_latest_total),
			"geo_country_name"		=> $geo_data["country_code"] ? _country_name($geo_data["country_code"]) : "",
			"geo_region_name"		=> $geo_data["region_code"]	? _region_name($geo_data["region_code"], $geo_data["country_code"]) : "",
			"filter"				=> module('gallery')->_show_filter(),
			"use_ajax"				=> 1,
			"geo_top_galleries"		=> $geo_top_galleries,
		);
		return tpl()->parse($MAIN_STPL, $replace);
	}

	/**
	* Show all galleries
	*/
	function show_all_galleries ($params = array()) {
// TODO: decide what to here if HIDE_TOTAL_ID enabled
		// Override page
		if (!empty($_GET["id"]) && empty($_GET["page"])) {
			$_GET["page"] = $_GET["id"];
		}
		$account_types	= main()->get_data("account_types");
		// Prepare params
		$STPL_MAIN = !empty($params["stpl_main"]) ? $params["stpl_main"] : 'gallery'."/all_galleries_main";
		// Swithc between search type
		if (!empty($_SESSION["gallery_filter"]["as_photos"])) {
			module('gallery')->_SEARCH_AS_PHOTOS = 1;
		}
		// Special search type
		if (module('gallery')->_SEARCH_AS_PHOTOS) {
			return $this->_search_as_photos($params["pager_url"]);
		}
		// Create sql code for the access checks
		// We want only clean, non-private, normal by content level photos on the main page
		if (MAIN_TYPE_USER) {
			$PHOTOS_ACCESS_SQL = " AND p.is_public = '1' ";
		}
		// Turn off count rewrite for "divide_pages"
//		$GLOBALS["PROJECT_CONF"]["divide_pages"]["SQL_COUNT_REWRITE"] = false;
		// Get unique galleries
		$sql = "SELECT p.user_id
					, COUNT(p.id) AS num_photos
				FROM ".db('gallery_photos')." AS p
					".(module('gallery')->USE_SQL_FORCE_KEY ? "/*!40000 USE KEY (user_id) */" : "")."
					,".db('user')." AS u
				WHERE p.active='1' 
					AND p.user_id = u.id"
					.$PHOTOS_ACCESS_SQL
					." /*__FILTER_SQL__*/ 
				GROUP BY p.user_id 
					/*__SORT_SQL__*/";
		if (module('gallery')->USE_FILTER) {
			$sql = module('gallery')->_create_filter_sql($sql);
		}
//		$order_by_sql = " ORDER BY p.priority DESC, num_photos DESC ";
		$path = "./?object=".'gallery'."&action=show_all_galleries&id=all";
		list($add_sql, $pages, $total) = common()->divide_pages(preg_replace("/ORDER BY .*?\$/ims", "ORDER BY NULL", $sql), $path, null, $params["for_stats"] ? module('gallery')->STATS_TOP_ON_PAGE : module('gallery')->VIEW_ALL_ON_PAGE);
		// Get contents from db
		$Q = db()->query($sql. /*$order_by_sql. */$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$gallery_users_num_photos[$A["user_id"]] = intval($A["num_photos"]);
		}
		// Get their info and sort by user name
		if (!empty($gallery_users_num_photos)) {
			$gallery_users_infos = user(array_keys($gallery_users_num_photos), array("id","group","name","login","email","nick","sex","country","state","city"), array("WHERE" => array("active" => "1")));
		}
		// Prepare folders (must be upper than fetch photos in code)
		if (!empty($gallery_users_num_photos)) {
			$folders_by_users = module('gallery')->_get_user_folders_for_ids(array_keys($gallery_users_num_photos));
		}
		// Prepare photos
		if (!empty($gallery_users_num_photos)) {
			$Q = db()->query(
				"SELECT p.* 
				FROM ".db('gallery_photos')." AS p
				WHERE p.user_id IN(".implode(",", array_keys($gallery_users_num_photos)).") "
					.$PHOTOS_ACCESS_SQL." 
				ORDER BY p.add_date DESC"
			);
			while ($A = db()->fetch_assoc($Q)) {
				$photos_by_users[$A["user_id"]][$A["id"]]	= $A;
			}
		}
		$cur_photo_type = "mini_thumbnail";
		$MAX_PHOTOS_PER_USER	= 5;
		// Display results
		foreach ((array)$gallery_users_num_photos as $user_id => $user_num_photos) {
			$user_info = $gallery_users_infos[$user_id];
			if (empty($user_info)) {
				continue;
			}
			$user_name = _display_name($user_info);
			// photos
			$c = 0;
			$photos = array();
			foreach ((array)$photos_by_users[$user_id] as $_photo_id => $_photo_info) {
				$_fs_thumb_name = module('gallery')->_photo_fs_path($_photo_info, $cur_photo_type);
				// Skip non-existed thumbs
				if (!file_exists($_fs_thumb_name) || !@filesize($_fs_thumb_name)) {
					continue;
				}
				if (++$c > $MAX_PHOTOS_PER_USER) {
					break;
				}
				$photos[$_photo_id] = array(
					"link"		=> "./?object=".'gallery'."&action=show_medium_size&id=".$_photo_id,
					"img_src"	=> module('gallery')->_photo_web_path($_photo_info, $cur_photo_type),
				);
			}
			// folders
			$folders = array();
			foreach ((array)$folders_by_users[$user_id] as $_folder_id => $_folder_info) {
				$folders[$_folder_id] = array(
					"title"	=> _prepare_html($_folder_info["title"]),
					"link"	=> "./?object=".'gallery'."&action=view_folder&id=".$_folder_info["id"],
				);
			}
			$view_gallery_link = "./?object=".'gallery'."&action=show_gallery&id=".$user_id._add_get(array("page"));
			// Process template
			$replace2 = array(
				"user_name"			=> _prepare_html($user_name),
				"user_profile_link"	=> _profile_link($user_id),
				"avatar"			=> _show_avatar($user_id, $user_name, 1, 0, 0, $view_gallery_link),
				"sex"				=> _prepare_html($user_info["sex"]),
				"account_type"		=> _prepare_html($account_types[$user_info["group"]]),
				"location"			=> _prepare_html(($user_info["city"] ? $user_info["city"].", " : "").($user_info["state"] ? _region_name($user_info["state"], $user_info["country"]).", " : "")._country_name($user_info["country"])),
				"view_gallery_link"	=> $view_gallery_link,
				"total_photos"		=> intval($gallery_users_num_photos[$user_id]),
				"photos"			=> !empty($photos)	? $photos : "",
				"folders"			=> !empty($folders)	? $folders : "",
			);
			$items .= tpl()->parse('gallery'."/all_galleries_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"		=> $items,
			"pages"		=> $pages,
			"total"		=> intval($total),
			"back_url"	=> "./?object=".'gallery'."&action=show"._add_get(array("page")),
			"filter"	=> module('gallery')->_show_filter(),
		);
		return tpl()->parse($STPL_MAIN, $replace);
	}

	/**
	* Show all galleries
	*/
	function _search_as_photos ($pager_url = "") {
// TODO: decide what to do here if HIDE_TOTAL_ID enabled
		// Override page
		if (!empty($_GET["id"]) && empty($_GET["page"])) {
			$_GET["page"] = $_GET["id"];
		}
		// Generate SQL for the access checks
		if (MAIN_TYPE_USER) {
			$PHOTOS_ACCESS_SQL = " AND p.is_public = '1' ";
		}
		$sql = "SELECT p.* 
				FROM ".db('gallery_photos')." AS p 
					,".db('user')." AS u
				WHERE 1 
					AND p.user_id = u.id 
					".$PHOTOS_ACCESS_SQL." 
					/*__FILTER_SQL__*/ 
					/*__SORT_SQL__*/";
		if (module('gallery')->USE_FILTER) {
			$sql = module('gallery')->_create_filter_sql($sql);
		}
		$url = $pager_url ? $pager_url : "./?object=".'gallery'."&action=show_all_galleries&id=all";
		// Turn off count rewrite for "divide_pages"
//		$GLOBALS["PROJECT_CONF"]["divide_pages"]["SQL_COUNT_REWRITE"] = false;
		list($add_sql, $latest_pages, $latest_total) = common()->divide_pages(preg_replace("/ORDER BY .*?\$/ims", "ORDER BY NULL", $sql), $url, null, module('gallery')->STATS_NUM_LATEST * 2);
		// Get top latest photos
		$Q = db()->query($sql. /*$order_by_sql.*/ $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$latest_photos_array[$A["id"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		// Get users names
		unset($users_ids[""]);
		if (is_array($users_ids)) {
			$users_datas = user(array_keys($users_ids), array("id","login","name","nick","profile_url","photo_verified"), array("WHERE" => array("active" => "1")));
			foreach ((array)$users_datas as $A) {
				$users_names[$A["id"]] = _display_name($A);
			}
		}
		module('gallery')->_users_names = $users_names;
		// Prefetch folders infos
		module('gallery')->_get_user_folders_for_ids($users_ids);
		// Prefetch tags array
		if (module('gallery')->ALLOW_TAGGING) {
			$_prefetched_tags = module('gallery')->_get_tags(array_keys((array)$latest_photos_array));
		}
		// Process latest photos
		foreach ((array)$latest_photos_array as $A) {
			if (empty($users_datas[$A["user_id"]])) {
				continue;
			}
			// Stop when number of users reached
			if (++$c1 > module('gallery')->STATS_NUM_LATEST) {
				break;
			}
			$latest_photos .= $this->_show_latest_item($A, $users_names[$A["user_id"]]);
		}
		// Prepare template
		$replace = array(
			"back_url"			=> "./?object=".'gallery'."&action=show"._add_get(array("page")),
			"filter"			=> module('gallery')->_show_filter(),
			"items"				=> $latest_photos,
			"pages"				=> $latest_pages,
			"total"				=> intval($latest_total),
			"is_geo"			=> intval((bool)$USE_GEO),
			"geo_country_name"	=> $geo_data["country_code"] ? _country_name($geo_data["country_code"]) : "",
			"geo_region_name"	=> $geo_data["region_code"]	? _region_name($geo_data["region_code"], $geo_data["country_code"]) : "",
			"use_ajax"			=> 1,
		);
		return tpl()->parse('gallery'."/as_photos_main", $replace);
	}

	/**
	* Show single photo item (for display in stats)
	*/
	function _show_latest_item ($photo_info = array(), $user_name = "", $ITEM_STPL = "") {
		$params = array(
			"stpl_full_path"	=> $ITEM_STPL,
			"user_name"			=> $user_name,
			"no_make_default"	=> 1,
		);
		return module('gallery')->_show_photo_item($photo_info, $params);
	}
	
	/**
	* Display latest photos for given user
	*/
	function _show_latest_user_photos($user_info = array(), $only_featured = false, $latest_photos_array = array()) {
		if (empty($user_info)) {
			return false;
		}
		// Get max privacy
		$max_privacy	= module('gallery')->_get_max_privacy($user_info["id"]);
		$max_level		= 1;
		if (!$latest_photos_array) {
			// Generate SQL for the access checks
			if (!module('gallery')->is_own_gallery) {
				$PHOTOS_ACCESS_SQL = 
					" AND folder_id IN( 
						SELECT id 
						FROM ".db('gallery_folders')." 
						WHERE privacy<=".intval($max_privacy)." 
							/*AND content_level<=".intval($max_level)."*/ 
							AND active='1' 
							AND password='' 
							AND user_id=".intval($user_info["id"])."
					)";
			}
			// Prepare SQL for featured photos
			$featured_sql = "";
			if ($only_featured) {
				$featured_sql = " AND is_featured = '1' ";
			}
			$_sort_id_field = $_GET["action"] == "view_folder" ? "folder_sort_id" : "general_sort_id";
			// Get top latest posts
			$Q = db()->query(
				"SELECT * 
				FROM ".db('gallery_photos')." 
				WHERE active=1 
					AND user_id=".intval($user_info["id"])." 
					".$featured_sql."
					".$PHOTOS_ACCESS_SQL." 
				ORDER BY ".$_sort_id_field." ASC /*,add_date DESC*/ 
				LIMIT ".intval(module('gallery')->STATS_NUM_LATEST * 2)
			);
			while ($A = db()->fetch_assoc($Q)) {
				$latest_photos_array[$A["id"]] = $A;
			}
		}
		// Process tags
		$this->_tags = module('gallery')->_show_tags(array_keys((array)$latest_photos_array));
		$user_name = _display_name($user_info);
		// Prefetch tags array
		if (module('gallery')->ALLOW_TAGGING) {
			$_prefetched_tags = module('gallery')->_get_tags(array_keys((array)$latest_photos_array));
		}
		// Process latest photos
		foreach ((array)$latest_photos_array as $A) {
			// Stop when number of users reached
			if (++$c1 > module('gallery')->STATS_NUM_LATEST) {
				break;
			}
			$latest_photos .= $this->_show_latest_item($A, $user_name);
		}
		return $latest_photos;
	}
	
	/**
	* Display latest photos in all galleries
	*/
	function latest($params = array()) {
// TODO: decide what to here if HIDE_TOTAL_ID enabled
		// Override page
		if (!empty($_GET["id"]) && empty($_GET["page"])) {
			$_GET["page"] = $_GET["id"];
		}
		// Generate SQL for the access checks
		if (MAIN_TYPE_USER) {
			$PHOTOS_ACCESS_SQL = " AND is_public = '1' ";
		}
		// Check if we need to show geo latest photos
		if (module('gallery')->ALLOW_GEO_FILTERING) {
			$geo_data = main()->_USER_GEO_DATA;
		}
		if (!empty($params["geo"]) && !empty($geo_data["country_code"])) {
			$USE_GEO = true;
		}
		// Geo filtering
		if ($USE_GEO) {
			if (GEO_LIMIT_COUNTRY != "GEO_LIMIT_COUNTRY" && GEO_LIMIT_COUNTRY != "" && $geo_data["country_code"] != GEO_LIMIT_COUNTRY) {
				$geo_filter_sql = " AND geo_cc = '"._es(GEO_LIMIT_COUNTRY)."' ";
			} else {
				$geo_filter_sql = " AND geo_cc = '"._es($geo_data["country_code"])."' ";
				if (strlen($geo_data["region_code"])) {
					$geo_filter_sql .= " AND geo_rc = '"._es($geo_data["region_code"])."' ";
				}
			}
		}
		if (module('gallery')->LATEST_GROUP_BY_USER) {
			$group_by_sql = " GROUP BY user_id ";
		}
		$sql = "SELECT * FROM ".db('gallery_photos')." "
			.($USE_GEO && module('gallery')->USE_SQL_FORCE_KEY ? "/*!40000 USE KEY (user_id) */" : "")
			." WHERE 1 "
			.$PHOTOS_ACCESS_SQL
			.$geo_filter_sql
			.$group_by_sql;
		$order_by_sql = " ORDER BY add_date DESC ";
		$url = "./?object=".'gallery'."&action=latest";
		list($add_sql, $latest_pages, $latest_total) = common()->divide_pages($sql, $url, null, module('gallery')->STATS_NUM_LATEST * 2);
		// Get top latest photos
		$Q = db()->query($sql.$order_by_sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$latest_photos_array[$A["id"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		// Get users names
		unset($users_ids[""]);
		if (is_array($users_ids)) {
			$users_datas = user(array_keys($users_ids), array("id","login","name","nick","profile_url","photo_verified"), array("WHERE" => array("active" => "1")));
			foreach ((array)$users_datas as $A) {
				$users_names[$A["id"]] = _display_name($A);
			}
		}
		module('gallery')->_users_names = $users_names;
		// Prefetch folders infos
		module('gallery')->_get_user_folders_for_ids($users_ids);
		// Prefetch tags array
		if (module('gallery')->ALLOW_TAGGING) {
			$_prefetched_tags = module('gallery')->_get_tags(array_keys((array)$latest_photos_array));
		}
		// Process latest photos
		foreach ((array)$latest_photos_array as $A) {
			// Stop when number of users reached
			if (++$c1 > module('gallery')->STATS_NUM_LATEST) {
				break;
			}
			$latest_photos .= $this->_show_latest_item($A, $users_names[$A["user_id"]]);
		}
		// Prepare template
		$replace = array(
			"items"				=> $latest_photos,
			"pages"				=> $latest_pages,
			"total"				=> intval($latest_total),
			"is_geo"			=> intval((bool)$USE_GEO),
			"geo_country_name"	=> $geo_data["country_code"] ? _country_name($geo_data["country_code"]) : "",
			"geo_region_name"	=> $geo_data["region_code"]	? _region_name($geo_data["region_code"], $geo_data["country_code"]) : "",
			"use_ajax"			=> 1,
		);
		return tpl()->parse('gallery'."/latest_main", $replace);
	}
}
