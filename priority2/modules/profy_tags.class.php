<?php

/**
* Tags
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_tags {

	/** @var string @conf_skip
	*	Allow here only these below \x7F == 127 (ASCII) :
	*		\x0A == 13 (CR), 
	*		\x20 == 32 (Space), 
	*		\x30-\x39 (0-9), 
	*		\x41-\x5A (A-Z),
	*		\x61-\x7A (a-z)
	*/
	var $REGEXP_ALLOWED		= '/[\x00-\x09\x0B-\x1F\x21-\x2F\x3A-\x40\x5B-\x60\x7B-\x7F]/ims';
	/** @var int Min keyword length */
	var $MIN_KEYWORD_LENGTH	= 3;
	/** @var int Max keyword length */
	var $MAX_KEYWORD_LENGTH	= 30;
	/** @var bool Use utf-8 */
	var $UTF8_MODE			= false;
	/** @var int Allowed group number */
	var $ALLOWED_GROUP = 0;
	/** @var int Number of tags per object */
	var $TAGS_PER_OBJ = 10;
	/** @var int Maximum number of tags that shows in a cloud */
	var $CLOUD_TAGS_LIMIT = 20;
	/** @var enum('num','text') Default value. Cloud creates in alphabetic text order*/
	var $CLOUD_ORDER = "text";
	/** @var int Maximum fontsize for cloud (in 'em') */
	var $CLOUD_MAX_FSIZE = 2;
	/** @var int Minimum fontsize for cloud (in 'em') */
	var $CLOUD_MIN_FSIZE = 0.9;
	/** @var array */
	var $GROUPS = array(
		"0" => "author",
		"1"	=> "friends",
		"2" => "members",
		"3"	=> "all",
	);
	/** @var array Objects in which tagging is available */
	var $avail_objects = array(
		"gallery",
		"blog",
		"articles",
	);
	/** @var array Corresponding of objects [keys] and their dbtables [values] */
	var $_db_tables_for_object = array(
		"gallery"	=> "gallery_photos",
		"blog"		=> "blog_posts",
		"articles"	=> "articles_texts",
	);

	/**
	* Constructor
	*/
	function _init () {
		$active_modules = (array)main()->get_data("user_modules");
		
		foreach ((array)$this->avail_objects as $key => $object){
			if (!in_array($object, $active_modules)){
				unset($this->avail_objects[$key]);
			}
		}
	
		// Assign cloud creation order and order direction
		if ($this->CLOUD_ORDER == "num") {
			$this->CLOUD_ORDER_DIR = "DESC";
		} else {
			$this->CLOUD_ORDER = "text";
			$this->CLOUD_ORDER_DIR = "ASC";
		}
		// Get settings for the curent user
		if (!empty($this->USER_ID)) {
			$A = db()->query_fetch("SELECT * FROM `".db('tags_settings')."` WHERE `user_id`=".$this->USER_ID);
			$this->ALLOWED_GROUP = $A["allowed_group"];
			if (!isset($this->ALLOWED_GROUP)) {
				// Set default settings
				db()->INSERT("tags_settings", array(
					"user_id"		=> $this->USER_ID,
					"allowed_group"	=> 0,
				));
			}
		}
	}

	/**
	* Default method. Shows tags cloud, tags main page
	*/
	function show () {
		$items = $this->_tags_cloud();

		// Create search categories multi check box
		$search_cats = common()->multi_check_box("search_cats", $this->avail_objects, $this->avail_objects, true,"","",1);

		$replace = array(
			"items"							=> $items,
			"form_action"					=> "./?object=".$_GET["object"]."&action=search",
			"search_categories_multibox"	=> $search_cats,
			"tags_settings_url"				=> "./?object=".$_GET["object"]."&action=settings",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Shows tags
	*/
	function _show ($obj_id = array(), $params = array()) {
		$OBJECT_NAME = !empty($params["object_name"]) ? $params["object_name"] : $_GET["object"];
		if (!in_array($OBJECT_NAME, $this->avail_objects)) {
			return false;
		}
		if (is_numeric($obj_id)) {
			$obj_id = array($obj_id);
		}
		// Prefetch tags
		$this->_prefetch_tags($OBJECT_NAME, $obj_id);
		// Check rights
		foreach ((array)$obj_id as $_obj) {
			$_tags[$_obj]["show_edit_button"] = $GLOBALS['_tags_cache'][$OBJECT_NAME][$_obj]["allowed"];
		} 
		
		foreach ((array)$obj_id as $_obj) {
			foreach ((array)$GLOBALS['_tags_cache'][$OBJECT_NAME][$_obj] as $k => $A) {
				if (!is_array($A)) {
					continue;
				}
				$replace2 = array(
					"tag_text"				=> $A["text"],
					"tag_search_url"		=> "./?object=".$OBJECT_NAME."&action=tag&id=".urlencode($A["text"]),
					"tag_search_url_by_user"=> process_url("./?object=".$OBJECT_NAME."&action=tag&id=".urlencode($A["text"])."-".$A["user_id"]),
					"div_id"				=> $OBJECT_NAME."_".$A["object_id"],
					"is_simple"				=> isset($params["simple"]) ? 1 : 0,
				);
				$_tags[$A["object_id"]]["text"][$A["text"]] = tpl()->parse("tags/tags_item", $replace2);
			}
		}
		foreach ((array)$_tags as $_id => $v) {
			$replace = array (
				"items"				=> implode(" ", (array)$v["text"]),
				"show_edit_button"	=> $v["show_edit_button"],
				"edit_url"			=> "./?object=".$OBJECT_NAME."&action=edit_tag&id=".$_id,
				"div_id"			=> $OBJECT_NAME."_".$_id,
				"is_simple"			=> isset($params["simple"]) ? 1 : 0,
			);
			$processed_tags[$_id] = tpl()->parse("tags/tags_main", $replace);
		}
		return $processed_tags;
	}

	/**
	* Manage tags
	*/
	function _edit_tags ($obj_id = 0) {
// TODO: make this compatible with HIDE_TOTAL_ID
		if (empty($obj_id)) {
			$obj_id = intval($_GET["id"]);
		}
		// Do nothing if no object_id provided
		if (empty($obj_id)) {
			return "";
		}
		$this->_tags = array();
		// Get current tags
		$Q = db()->query("SELECT * FROM `".db('tags')."` WHERE `object_name`='".$_GET["object"]."' AND `object_id`=".intval($obj_id));
		while ($A = db()->fetch_assoc($Q)) {
			$this->_tags[$A["id"]] = $A["text"];
		}

		// Save data
		if (!empty($_POST["tags"])) {
			$this->_save_tags($_POST["tags"], $obj_id);
			return js_redirect("./?object=".$_GET["object"]."&action=".$_GET["action"]. ($_GET["id"] ? "&id=".$_GET["id"] : ""));
		}

		// Delete all tags if empty form submitted
		if ($_POST["tags_edited"] && empty($_POST["tags"])) {
			db()->query("DELETE FROM `".db('tags')."` WHERE `object_name`='".$_GET["object"]."' AND `object_id`=".intval($obj_id));	
			return js_redirect("./?object=".$_GET["object"]."&action=".$_GET["action"]. ($_GET["id"] ? "&id=".$_GET["id"] : ""));
		}

		// Check rights
		$allow_array = $this->_check_rights($obj_id, $OBJECT_NAME);
		$allow_edit = $allow_array[$obj_id]["allowed"];

		// Check for owner or for the member
		if (!$allow_edit /*|| count($_tags) >= $this->TAGS_PER_OBJ*/) {
			return "";
		}

		// Prepare for edit
		$tags_to_edit = $this->_collect_tags($obj_id, $OBJECT_NAME);

		// Prepare template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]. ($_GET["id"] ? "&id=".$_GET["id"] : ""),
			"tags_to_edit"	=> $tags_to_edit,
			"max_num_tags"	=> intval($this->TAGS_PER_OBJ),
			"min_tag_len"	=> $this->MIN_KEYWORD_LENGTH,
			"max_tag_len"	=> $this->MAX_KEYWORD_LENGTH,
			"tags"			=> !empty($tags) ? $tags : "",
		);
		main()->NO_GRAPHICS = true;
		echo common()->show_empty_page(tpl()->parse("tags/edit_tags_form", $replace));
	}

	/**
	* Alias for the "search"
	*/
	function tag() {
		return $this->search();
	}

	/**
	* Search entries by tag
	*/
	function search() {

		if (!empty($_POST)) {
			foreach ((array)$_POST as $k => $V) {
				if (strstr($k, "search_cats_")) {
					$_obj_name_array[] = _es($this->avail_objects[$V]);
				}					
			}
			if ($_POST["tag_name"]) {
				$_tag = $_POST["tag_name"];
			} else {
				return js_redirect($_SERVER["HTTP_REFERER"]);
			}
		}
		if (empty($_POST)) {
			// Needed only if search occurs by clicking to tag (not by the search form) 
			list($_tag) = explode(".", urldecode($_GET["id"]));
			list($_tag, $filter_user_id) = explode("-", $_tag);

			$filter_user_id = intval($filter_user_id);
		}

		$_tag = str_replace("+", " ", $_tag);
		$_tag = trim(preg_replace("/[^a-z0-9 -ï€-Ÿ\s]/ims", "", $_tag));
		$_tag = trim(preg_replace("/[\s]{2,}/ims", " ", $_tag));

		$GLOBALS['site_title'] = $_tag;

		if ($filter_user_id) {
			$filter_sql = " AND `user_id`=".$filter_user_id;
		} else {
			$filter_sql = "";
		}
		if ($_GET["object"] != "tags" && in_array($_GET["object"], $this->avail_objects)) {
			$filter_sql .= " AND `object_name`='"._es($_GET["object"])."'"; 
		}
		if (!empty($_obj_name_array)) {
			$filter_sql .= " AND `object_name` IN('".implode("','", $_obj_name_array)."')";
		}

		$sql = "SELECT * FROM `".db('tags')."` 
				WHERE `text`='"._es($_tag)."'
				".$filter_sql." 
				ORDER BY `add_date` ASC";

		if ($filter_user_id) {
			$_url_id = $_tag."-".$filter_user_id;
		}
		$url = "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".urlencode($_url_id);
		list($add_sql, $pages, $total) = common()->divide_pages($sql, $url/*, "", 3*/);
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$_tags[$A["object_name"]][$A["object_id"]] = $A["object_id"];

			if ($A["object_name"] == "blog") {
				$blog_ids_array[$A["object_id"]]	= $A["object_id"];
			}
			if ($A["object_name"] == "gallery") {
				$gallery_ids_array[$A["object_id"]]	= $A["object_id"];
			}
			if ($A["object_name"] == "articles") {
				$articles_ids_array[$A["object_id"]]= $A["object_id"];
			}
		}

		if (!empty($blog_ids_array)) {
			$COMMENTS_OBJ = &main()->init_class("comments", USER_MODULES_DIR);
			$BLOG_OBJ = main()->init_class("blog");

			$num_comments = $COMMENTS_OBJ->_get_num_comments(array(
				"object_name" => "blog",
				"objects_ids" => implode(",", $blog_ids_array),
			));

			$Q = db()->query("SELECT * FROM `".db('blog_posts')."` WHERE `id` IN(".implode(",", $blog_ids_array).")");
			while ($A = db()->fetch_assoc($Q)) {
				$post_info[$A["id"]] = $A;
			}

			// Process tags
			$BLOG_OBJ->_tags = $this->_show($blog_ids_array, array("object_name" => "blog"));
		}

		if (!empty($gallery_ids_array)) {
			$GALLERY_OBJ = &main()->init_class("gallery");
			$Q = db()->query("SELECT * FROM `".db('gallery_photos')."` WHERE `id` IN(".implode(",", $gallery_ids_array).")");
			while ($A = db()->fetch_assoc($Q)) {
				$_photo_infos[$A["id"]] = $A;
			}
			// Process tags
			$GALLERY_OBJ->_tags = $this->_show($gallery_ids_array, array("object_name" => "gallery"));
		}

		if (!empty($articles_ids_array)) {
			$COMMENTS_OBJ = &main()->init_class("comments", USER_MODULES_DIR);
			$ARTICLES_OBJ = &main()->init_class("articles"); 

			$num_comments = $COMMENTS_OBJ->_get_num_comments(array(
				"object_name" => "articles",
				"objects_ids" => implode(",", $articles_ids_array),
			));

			$Q = db()->query("SELECT * FROM `".db('articles_texts')."` WHERE `id` IN(".implode(",", $articles_ids_array).")");
			while ($A = db()->fetch_assoc($Q)) {
				$_articles_infos[$A["id"]] = $A;
			}
			$ARTICLES_OBJ->_tags = $this->_show($articles_ids_array, array("object_name" => "articles"));
			$ARTICLES_OBJ->_num_comments = $num_comments;
		}
		foreach ((array)$_tags as $obj_name => $obj) {
			if ($obj_name == "blog") {
				foreach ((array)$obj as $obj_id) {
					if (empty($post_info[$obj_id])) {
						$total--;
						continue;
					}
					$_item = $BLOG_OBJ->_show_post_item($post_info[$obj_id], $num_comments[$obj_id]);
					if (!$_item) {
						$total--;
						continue;
					}
					$replace = array(
						"cat_name"	=> $obj_name,
						"item"		=> $_item,
					);
					$items .= tpl()->parse("tags/search_tags_item", $replace);
				}
			}
			if ($obj_name == "gallery") {
				foreach ((array)$obj as $obj_id) {
					if (empty($_photo_infos[$obj_id])) {
						$total--;
						continue;
					}
					$_item = $GALLERY_OBJ->_show_photo_item($_photo_infos[$obj_id], "tag_search_");
					if (!$_item) {
						$total--;
						continue;
					}
					$replace = array(
						"cat_name"	=> $obj_name,
						"item"		=> $_item,
					);
					$items .= tpl()->parse("tags/search_tags_item", $replace);
				}
			}
// TODO: articles (show tags and number of comments)
			if ($obj_name == "articles") {
				foreach ((array)$obj as $obj_id) {
					if (empty($_articles_infos[$obj_id])) {
						$total--;
						continue;
					}
					$_item = $ARTICLES_OBJ->_process_stats_item($_articles_infos[$obj_id]);
					if (!$_item) {
						$total--;
						continue;
					}
					$replace = array(
						"cat_name"	=> $obj_name,
						"item"		=> $_item,
					);
					$items .= tpl()->parse("tags/search_tags_item", $replace);
				}
			}
		}
		$user_nick = "";
		if ($filter_user_id) {
			$user_info = user(1, array("nick"));			
			$user_nick = $user_info["nick"];
			$user_profile_link = _profile_link($filter_user_id);
		}

		$replace = array (
			"total" 			=> $total,
			"pages"				=> $pages,
			"tag_name"			=> _prepare_html($_tag),
			"items"				=> $items,
			"new_search_url"	=> "./?object=tags",
			"user_nick"			=> $user_nick,
			"user_profile_link"	=> $user_profile_link,
		);
		return tpl()->parse("tags/search_tags_main", $replace);
	}

	/**
	* Manage user tags settings
	*/
	function settings () {
		if (empty($this->USER_ID)) {
			 return _error_need_login();
		}
		if (!empty($_POST) && $this->ALLOWED_GROUP != $_POST["allowed_group"]) {
			// Saving new settings if changed
			db()->UPDATE("tags_settings", array(
					"allowed_group" => $_POST["allowed_group"]
				), "`user_id`=".$this->USER_ID
			);			
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}
		$replace = array(              
			"allowed_groups_box"	=> common()->select_box("allowed_group", $this->GROUPS, $this->ALLOWED_GROUP, 0),		
			"form_action"			=> "./?object=tags&action=settings",
		);
		return  tpl()->parse("tags/settings", $replace);
	}

	/**
	* Show or save user module specific tags settings (for blog, gallery)
	* $params:
	* 	module (gallery, blog)
	* 	object_id (blog - user_id, gallery folder)
	* saves if allowed group is set, otherwise - returns current value
	*/
	function _mod_spec_settings ($params, $allowed_group = NULL) {
		if (!isset($allowed_group)) {
			// Show box with current value selected
			if ($params["module"] == "blog" && isset($params["object_id"])) {
				$A = db()->query_fetch("SELECT `allow_tagging` FROM `".db('blog_settings')."` WHERE `user_id`=".$params["object_id"]);
				$allowed_box = common()->select_box("allowed_group", $this->GROUPS, $A["allow_tagging"], 0);		
    		} elseif ($params["module"] == "gallery" && isset($params["object_id"])) {
				$A = db()->query_fetch("SELECT `allow_tagging` FROM `".db('gallery_folders')."` WHERE `id`=".$params["object_id"]);
				$allowed_box = common()->select_box("allowed_group", $this->GROUPS, $A["allow_tagging"], 0);		
    		} else {
				return $allowed_box = common()->select_box("allowed_group", $this->GROUPS, $this->ALLOWED_GROUP, 0); // default settings otherwise
			}
			return $allowed_box;
		} else {
			// Save settings
			if ($params["module"] == "blog" && isset($params["object_id"])) {
				db()->UPDATE("blog_settings", array(	
						"allow_tagging" => $allowed_group,
					), "`user_id`=".$params["object_id"]
				);			
			} else {
				return false;
			}
			return true;
		}
	}

	/**
	* Check rights to edit tags
	*/
	function _check_rights($obj_id = 0, $_object_name = "") {
		
		$OBJECT_NAME = !empty($_object_name) ? $_object_name : $_GET["object"];
		if (!isset($obj_id)) {
			common()->_raise_error("No object ID to check rights!");
			return false;
		}

		if (!is_array($obj_id)) {
			$obj_id = array($obj_id);
		}
		// Find owners and their tags settings
		if (empty($this->_db_tables_for_object[$OBJECT_NAME])) {
			return false;
		}

		// Custom settings for blog
		if ($OBJECT_NAME == "blog") {
			$sql = "SELECT bp.`id`, bs.`user_id`, bs.`allow_tagging` AS `allowed_group` 
					FROM `".db('blog_settings')."` AS bs, `".db('blog_posts')."` AS bp 
					WHERE bp.`id` IN('".implode("','",$obj_id)."') AND bs.`user_id`=bp.`user_id`";
		//Custom settings for gallery folder
    	} elseif ($OBJECT_NAME == "gallery") {
			$sql = "SELECT gp.`id`, gp.`user_id`, gf.`allow_tagging` AS `allowed_group` 
					FROM `".db('gallery_folders')."` AS gf, `".db('gallery_photos')."` AS gp 
					WHERE gp.`id` IN('".implode("','",$obj_id)."') AND gp.`folder_id`=gf.`id`";
    	} else {
			$sql = "SELECT obj.`id`, obj.`user_id`, s.`allowed_group` 
					FROM `".DB_PREFIX. $this->_db_tables_for_object[$OBJECT_NAME]."` AS obj, `".db('tags_settings')."` AS s  
					WHERE obj.`id` IN('".implode("','",$obj_id)."') AND s.`user_id`=obj.`user_id`";
		}
		foreach ((array)db()->query_fetch_all($sql) as $A) {
			// Authors can edit tags in any case
			if ($this->USER_ID == $A["user_id"]) {
				$_allowed[$A["id"]]["allowed"] = 1;
			}

			// All can edit tags
			if ($A["allowed_group"] == 3) {
				$_allowed[$A["id"]]["allowed"] = 1;
			}
			// Only members can edit tags
			if ($A["allowed_group"] == 2 && $this->USER_ID) {
				$_allowed[$A["id"]]["allowed"] = 1;
			}
			// Only friends can edit tags
			if ($A["allowed_group"] == 1 && $this->USER_ID) {
				$FRIENDS_OBJ = main()->init_class("friends");
				$friend_of_array = $FRIENDS_OBJ->_get_users_where_friend_of($this->USER_ID);
				if(in_array($A["user_id"], $friend_of_array)) {
					$_allowed[$A["id"]]["allowed"] = 1;	
				}
			}

			if ($_allowed[$A["id"]]["allowed"] != 1) {
				$_allowed[$A["id"]]["allowed"] = 0;
			}
			$_allowed[$A["id"]]["owner_id"] = $A["user_id"];

		}
		// returns array like array([obj_id]=>array([allowed], [owner_id]))
		return $_allowed;
	}

	/**
	* Creates tags cloud for blog entries
	*/
	function _tags_cloud($object_name = "") {
		// Select top of the tags for cloud creation
		if (!$object_name) {
			$sql = "SELECT `text` , COUNT(*) AS `num` FROM `".db('tags')."` GROUP BY `text` ORDER BY `".$this->CLOUD_ORDER."` ".$this->CLOUD_ORDER_DIR." LIMIT ". $this->CLOUD_TAGS_LIMIT;			
		} else {
			$sql = "SELECT `text` , COUNT(*) AS `num` FROM `".db('tags')."` WHERE `object_name`='".$object_name."' GROUP BY `text` ORDER BY `".$this->CLOUD_ORDER."` ".$this->CLOUD_ORDER_DIR." LIMIT ". $this->CLOUD_TAGS_LIMIT;			
		}

		$tmp_cloud_data = db()->query_fetch_all($sql);
		$cloud_data = array();
		foreach ((array)$tmp_cloud_data as $A) {
			$cloud_data[$A["text"]] = $A["num"];
		}
		$items = $cloud_data ? common()->_create_cloud($cloud_data) : "";
		return $items;

	}

	/**
	* Prefetch tags
	*/
	function _prefetch_tags($obj_name, $obj_ids) {
		$check_rights_ids = $obj_ids;
		// Create array of tags data
		foreach ((array)$obj_ids as $k => $_id) {
			if (isset($GLOBALS['_tags_cache'][$obj_name][$_id])) {
				unset($obj_ids[$k]);
			}
		}
		if (!empty($obj_ids)) {
	   		// Get current object tags
			$Q = db()->query(
				"SELECT * FROM `".db('tags')."` 
				WHERE `object_name`='"._es($obj_name)."' 
					AND `object_id` IN(".implode(",",$obj_ids).")"
			);
			while ($A = db()->fetch_assoc($Q)) {
				$GLOBALS['_tags_cache'][$obj_name][$A["object_id"]][$A["id"]] = $A;
			}
		}
		foreach ((array)$obj_ids as $B) {
			if (!isset($GLOBALS['_tags_cache'][$obj_name][$B])) {
				$GLOBALS['_tags_cache'][$obj_name][$B] = "";
			}
		}
		//Add rights properties
		foreach ((array)$check_rights_ids as $k1 => $_id) {
			if (isset($GLOBALS['_tags_cache'][$obj_name][$_id]["allowed"]) && isset($GLOBALS['_tags_cache'][$obj_name][$_id]["owner_id"])) {
				unset($check_rights_ids[$k1]);
			}
		}
		if (!empty($check_rights_ids)) {
			$rights_array =	$this->_check_rights($check_rights_ids, $obj_name);
			foreach ((array)$rights_array as $k => $v) {
				$GLOBALS['_tags_cache'][$obj_name][$k] = my_array_merge($v, $GLOBALS['_tags_cache'][$obj_name][$k]);
			}
		}
		return true;
	}

	/**
	* Save tags to db 
	*/
	function _save_tags ($string = "", $obj_id = 0, $object_name = "") {
		if (!$object_name) {
			$object_name = $_GET["object"];
		}

		// Check rights
		$allow_array = $this->_check_rights($obj_id, $object_name);
		$allow_edit = $allow_array[$obj_id]["allowed"];

		if (!$allow_edit) {
			return false;
		}
		if (empty($this->tags)) {
			// Get current tags
			$Q = db()->query("SELECT * FROM `".db('tags')."` WHERE `object_name`='".$_GET["object"]."' AND `object_id`=".intval($obj_id));
			while ($A = db()->fetch_assoc($Q)) {
				$this->_tags[$A["id"]] = $A["text"];
			}
		}

		$keywords_array = array();
		// Process submitted tags
		$source = $string;
		$source = trim($source);
		$source = str_replace(array("\r", "\t"), array("", " "), $source);
		$source = preg_replace("/[ ]{2,}/is", " ", $source);
		$source = preg_replace("/[\n]{2,}/ims", "\n", $source);
		$source = preg_replace($this->REGEXP_ALLOWED, "", $source);
		// Split by lines
		$lines	= explode("\n", $source);
		// Last cleanup
		foreach ((array)$lines as $cur_word) {
			$cur_word = trim($cur_word);
			if (empty($cur_word) || (strlen($cur_word) * ($this->UTF8_MODE ? 2 : 1)) < $this->MIN_KEYWORD_LENGTH) {
				continue;
			}
			// Check max number of keywords
			if (++$i > $this->TAGS_PER_OBJ) {
				break;
			}
			// Cut long keywords
			if ($this->MAX_KEYWORD_LENGTH && strlen($cur_word) > $this->MAX_KEYWORD_LENGTH * ($this->UTF8_MODE ? 2 : 1)) {
				$cur_word = substr($cur_word, 0, $this->MAX_KEYWORD_LENGTH);
			}
			if (!isset($keywords_array[$cur_word])) {
				$keywords_array[$cur_word] = $cur_word;
			}
		}
		$TAGS_CHANGED = true;
		// Check if we have non-changed content
		if (count($this->_tags) == count($keywords_array) && !array_diff((array)$this->_tags, $keywords_array)) {
			$TAGS_CHANGED = false;
		}
		if ($TAGS_CHANGED) {
			$_new_tags = $this->_tags;
			// Find and remove from saving not changed tags
			foreach (array_intersect((array)$_new_tags, (array)$keywords_array) as $_key => $_val) {
				unset($keywords_array[$_val]);
				unset($_new_tags[$_key]);
			}

			$ids_to_delete = array_keys((array)$_new_tags);
			// Delete old keywords
			if (!empty($ids_to_delete)) {
				db()->query("DELETE FROM `".db('tags')."` WHERE `object_name`='".$object_name."' AND `object_id`=".intval($obj_id)." AND `id` IN(".implode(",", $ids_to_delete).")");
			}

			$num_tags = count($this->_tags);
			// Save new keywords
			foreach ((array)$keywords_array as $_word) {
				// Check total ads limit for non-owner
				if ($num_tags >= $this->TAGS_PER_OBJ) {
					break;
				}
				$num_tags++;
				// Save tag
				db()->INSERT("tags", array(
					"object_name"	=> $object_name,
					"object_id"		=> intval($obj_id),
					"user_id"		=> $allow_array[$obj_id]["owner_id"], // it's owner id!
					"text"			=> _es($_word),
					"add_date"		=> time(),
					"active"		=> 1,
				));
				$TAG_ID = db()->INSERT_ID();
				// Save log
				db()->INSERT("log_tags", array(
					"object_id"		=> intval($obj_id),
					"object_name"	=> $object_name,
					"tag_id"		=> intval($TAG_ID),
					"text"			=> _es($_word),
					"date"			=> time(),
					"site_id"		=> (int)conf('SITE_ID'),
					"owner_id"		=> $allow_array[$obj_id]["owner_id"],
					"ip"			=> _es(common()->get_ip()),
					"user_agent"	=> _es($_SERVER["HTTP_USER_AGENT"]),
					"referer"		=> _es($_SERVER["HTTP_REFERER"]),
					"request_uri"	=> _es($_SERVER["REQUEST_URI"]),
				));
			}
		}
	}

	/**
	* Prepare tags for editing 
	*/
	function _collect_tags ($obj_id = 0, $object_name = "") {
		if (!$object_name) {
			$object_name = $_GET["object"];
		}
		if (empty($this->tags)) {
			// Get current tags
			$Q = db()->query("SELECT * FROM `".db('tags')."` WHERE `object_name`='".$object_name."' AND `object_id`=".intval($obj_id));
			while ($A = db()->fetch_assoc($Q)) {
				$this->_tags[$A["id"]] = $A["text"];
			}
		}
		// Prepare for edit
		$tags_to_edit = implode("\r\n", (array)$this->_tags);
		return $tags_to_edit;
	}

	/**
	* Tags cloud 
	*/
	function _widget_cloud ($params = array()) {
		if ($params["describe"]) {
			return array("allow_cache" => 1);
		}
		$items = $this->_tags_cloud();
		if (!$items) {
			return "";
		}
		$replace = array(
			"items" => $items,
		);
		return tpl()->parse("tags/widget_cloud", $replace);
	}

	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> "Manage",
				"url"	=> "./?object=".$_GET["object"],
			),
			array(
				"name"	=> "Tags settings",
				"url"	=> "./?object=".$_GET["object"]."&action=settings",
			),
			array(
				"name"	=> "",
				"url"	=> "./?object=".$_GET["object"],
			),
		);
		return $menu;	
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));
		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"							=> "",
		);
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}
		return array(
			"header"	=> $page_header ? _prepare_html($page_header) : "Tags",
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}

	/**
	* Delete user data from tables which this module use
	*/
	function _on_delete_account($params = array()) {
		$USER_ID = intval($params["user_id"]);
		db()->query("DELETE FROM `".db('tags_settings')."` WHERE `user_id`=".$USER_ID);
	}	
}
