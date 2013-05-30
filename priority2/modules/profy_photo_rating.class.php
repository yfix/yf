<?php

/**
* Photo ratings
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_photo_rating extends profy_module {

	/** @var int Number of stars to use */
	var $NUM_STARS					= 10;
	/** @var string Rate object name */
	var $RATE_OBJECT_NAME			= "gallery_photo";
	/** @var string Photo type main */
	var $PHOTO_TYPE_MAIN			= "medium";
	/** @var string Photo type last */
	var $PHOTO_TYPE_LAST			= "thumbnail";
	/** @var int */
	var $RESERVE_PHOTOS				= 10;
	/** @var int Guets timeout */
	var $GUESTS_TIMEOUT				= 3600;
	/** @var int Time to store views log */
	var $VIEWS_TTL					= 86400;
	/** @var bool */
	var $DISPLAY_RESULT_ONCE		= true;
	/** @var bool Search filter on/off */
	var $USE_FILTER					= true;
	/** @var bool Anti-cheat checking on/off */
	var $ANTICHEAT_CHECKING			= true;
	/** @var int Number of users voted for when anti-cheat starting */
	var $ANTICHEAT_TARGET_USERS		= 10;
	/** @var int */
	var $ANTICHEAT_POSITIVE_VOTE	= 7;
	/** @var int */
	var $ANTICHEAT_NEGATIVE_VOTE	= 3;
	/** @var float positive/negative threshhold to turn off counting */
	var $ANTICHEAT_POS_TO_NEG		= 4.0;
	/** @var int Negative votes to turn off counting */
	var $ANTICHEAT_NEGATE_AFTER		= 20;
	/** @var float Chanied with ANTICHEAT_NEGATE_AFTER, percent of negative votes */
	var $ANTICHEAT_NEGATE_PART		= 0.75;
	/** @var int Number of top photos per page */
	var $TOP_PER_PAGE				= 10;
	/** @var int Number of votes for top photos */
	var $TOP_MIN_VOTES				= 10;
	/** @var array @conf_skip Params for the comments */
	var $_comments_params	= array(
		"return_action" => "show",
		"object_name"	=> "gallery",
	);
	/** @var int */
	var $__TEST_MODE__				= 0;

	/**
	* Framework constructor
	*
	* @access	public
	* @return	void
	*/
	function _init () {
		define("PHOTO_RATING_CLASS_NAME", "photo_rating");
		// Prepare data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
		// Gallery object required
		$this->GALLERY_OBJ = main()->init_class("gallery");
		// Array of select boxes to process
		$this->_boxes = array(
			"rate_value"	=> 'radio_box("rate_value",	$this->_rate_values, $selected, false, 2, "", false)',
		);
		$this->_rate_values = _my_range(1, $this->NUM_STARS);
		// Initialize filter
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
	}

	/**
	* Default method
	*/
	function show () {
		return $this->_show_photo();
	}

	/**
	* Display top rated photos
	*/
	function top ($reverse_order = false) {
		// Prepare query
		$sql = "SELECT * 
				FROM `".db('gallery_photos')."` 
				WHERE `allow_rate`='1' 
					AND `is_public`='1' 
					AND `rating` != 0 
					AND `num_votes` >= ".intval($this->TOP_MIN_VOTES)." ";
		$order_by_sql = " ORDER BY `rating` ".($reverse_order ? "ASC" : "DESC")." ";
		// Connect pager
		list($add_sql, $pages, $total, $first) = common()->divide_pages($sql, "", $this->TOP_PER_PAGE);
		// Get top photos from db
		$Q = db()->query($sql.$order_by_sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$data[$A["id"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		// Get users ids
		if (!empty($users_ids)) {
			$users_infos = user($users_ids, array("id","group","name","login","email","nick","sex","country","state","city"), array("WHERE" => array("active" => "1")));
		}
		$i = $first;
		// Preapre photos
		foreach ((array)$data as $_photo_id => $photo_info) {
			$user_info = $users_infos[$photo_info["user_id"]];

			// Prepare other photo info
			$other_info = array();
			if (!empty($photo_info["other_info"])) {
				$other_info = unserialize($photo_info["other_info"]);
			}
			// Prepare real dimensions
			$real_w = $other_info[$this->PHOTO_TYPE_LAST]["w"];
			$real_h = $other_info[$this->PHOTO_TYPE_LAST]["h"];

			$view_photo_link = "./?object=gallery&action=show_medium_size&id=".intval($photo_info["id"]);

			$img_web_path = $this->GALLERY_OBJ->_photo_web_path($photo_info, $this->PHOTO_TYPE_LAST);
			$photos[$_photo_id] = array(
				"pos"			=> ++$i,
				"photo_id"		=> intval($_photo_id),
				"img_src"		=> $img_web_path,
				"real_w"		=> intval($real_w),
				"real_h"		=> intval($real_h),
				"photo_name"	=> _prepare_html($photo_info["name"]),
				"user_id"		=> intval($photo_info["user_id"]),
				"user_name"		=> _prepare_html(_display_name($user_info)),
				"user_link"		=> _profile_link($photo_info["user_id"]),
				"avatar"		=> _show_avatar($photo_info["user_id"], _display_name($user_info), 1, 0, 0, $view_photo_link),
				"gallery_link"	=> "./?object=gallery&action=show&id=".intval($photo_info["user_id"]),
				"photo_link"	=> $view_photo_link,
				"rating"		=> round($photo_info["rating"], 1),
				"num_votes"		=> intval($photo_info["num_votes"]),
			);
		}
		// Prepare template
		$replace = array(
			"photos"		=> $photos,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"reverse"		=> intval((bool)$reverse_order),
			"top_per_page"	=> intval($this->TOP_PER_PAGE),
			"top_min_votes"	=> intval($this->TOP_MIN_VOTES),
		);
		return tpl()->parse(__CLASS__."/top_main", $replace);
	}

	/**
	* Display bottom rated photos
	*/
	function bottom () {
		return $this->top(1);
	}

	/**
	* Try to display photo to rate for
	*/
	function _show_photo () {
		// Apply default filter
		if (!isset($_SESSION[$this->_filter_name])) {
			if ($this->USER_ID) {
				$_POST["sex"] = strtolower($this->_user_info["sex"]) == "female" ? "Male" : "Female";
			} else {
				$_POST["sex"] = "Female";
			}
			$geo_data = main()->_USER_GEO_DATA;
			if (!empty($geo_data["country_code"])) {
				$_POST["country"]	= $geo_data["country_code"];
			}
			$this->save_filter(1);
		}
		// Try to get random gallery photo
		$table_info = db()->query_fetch("SHOW TABLE STATUS LIKE '".db('gallery_photos')."'");
		$rand_num = rand(1, $table_info["Auto_increment"] - 1);
		// Prepare query
		$view_check_sql = " AND `id` NOT IN( 
			SELECT `photo_id` 
			FROM `".db('gallery_rate_views')."` 
			WHERE ".(
				$this->USER_ID 
				? " `user_id` = ".intval($this->USER_ID)." " 
				: " `sid` = '"._es(session_id())."' AND `date` > ".(time() - $this->GUESTS_TIMEOUT)." "
			)."
		)";
		if ($this->USER_ID) {
			$view_check_sql .= " AND `user_id` != ".intval($this->USER_ID)." ";
		}

// TODO: add more access level checks (ability to view also non-public photos if allowed)
		if ($this->__TEST_MODE__) {
			$view_check_sql = "";
		}

		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$Q = db()->query(
			"SELECT * 
			FROM `".db('gallery_photos')."` 
			WHERE `is_public`='1' 
				AND `allow_rate`='1' 
				AND `id` >= ".intval($rand_num)."
				".($this->__TEST_MODE__ ? " AND `user_id` = 1 " : "")."
				".$view_check_sql."
				".$filter_sql."
			LIMIT ".intval($this->RESERVE_PHOTOS)
		);
		while ($A = db()->fetch_assoc($Q)) {
			$img_path = $this->GALLERY_OBJ->_create_name_from_tpl($A, $this->PHOTO_TYPE_MAIN);
			if (!@file_exists(INCLUDE_PATH. $img_path) || !@filesize(INCLUDE_PATH. $img_path)) {
				continue;
			}
			$data[$A["id"]] = $A;
			if ($A["user_id"]) {
				$users_ids[$A["user_id"]] = $A["user_id"];
			}
		}
		// Get user infos
		if (!empty($users_ids)) {
			$users_infos = user($users_ids, "", array("WHERE" => array("active" => "1")));
		}
		// Next photos check
		foreach ((array)$data as $A) {
			if (empty($users_infos[$A["user_id"]])) {
				continue;
			}
			$photo_info	= $A;
			$user_info	= $users_infos[$A["user_id"]];
		}
		$NO_RESULTS = false;
		if (empty($photo_info) || empty($user_info)) {
			$NO_RESULTS = true;
		}
		// Prepare photo for displaying
		if (empty($NO_RESULTS)) {
			// Prepare other photo info
			$other_info = array();
			if (!empty($photo_info["other_info"])) {
				$other_info = unserialize($photo_info["other_info"]);
			}
			// Prepare real dimensions
			$real_w = $other_info[$this->PHOTO_TYPE_MAIN]["w"];
			$real_h = $other_info[$this->PHOTO_TYPE_MAIN]["h"];
			$_real_coef = $real_h ? $real_w / $real_h : 0;
			// Limits for the current photo size
			$_max_w = $this->GALLERY_OBJ->PHOTO_TYPES[$this->PHOTO_TYPE_MAIN]["max_x"];
			$_max_h = $this->GALLERY_OBJ->PHOTO_TYPES[$this->PHOTO_TYPE_MAIN]["max_y"];
			// Save photo view
			db()->REPLACE("gallery_rate_views", array(
				"sid"		=> _es(session_id()),
				"user_id"	=> intval($this->USER_ID),
				"photo_id"	=> intval($photo_info["id"]),
				"date"		=> time(),
			));
		}
		$this->_comments_params = array(
			"object_id"		=> $photo_info["id"],
			"object_name"	=> "gallery",
			"return_action"	=> "show",
		);
		// Prepare template
		$replace = array(
			"form_action"	=> "./?object=".PHOTO_RATING_CLASS_NAME."&action=ajax_vote",
			"img_src"		=> WEB_PATH. $img_path,
			"rate_box"		=> $this->_box("rate_value"),
			"real_w"		=> intval($real_w),
			"real_h"		=> intval($real_h),
			"user_id"		=> intval($photo_info["user_id"]),
			"user_name"		=> _prepare_html(_display_name($user_info)),
			"user_link"		=> _profile_link($photo_info["user_id"]),
			"gallery_link"	=> "./?object=gallery&action=show&id=".intval($photo_info["user_id"]),
			"photo_link"	=> "./?object=gallery&action=show_medium_size&id=".intval($photo_info["id"]),
			"photo_name"	=> _prepare_html($photo_info["name"]),
			"user_id"		=> intval($photo_info["user_id"]),
			"filter"		=> $this->_show_filter(),
			"photo_id"		=> $NO_RESULTS ? "" : intval($photo_info["id"]),
			"no_results"	=> intval((bool)$NO_RESULTS),
			"comments"		=> $this->_view_comments(),
		);
		return tpl()->parse(PHOTO_RATING_CLASS_NAME."/photo_main", $replace);
	}

	/**
	* Method for AJAX-based voting
	*/
	function ajax_vote () {
		main()->NO_GRAPHICS = true;
		// Check input ID
		$PHOTO_ID	= intval($_REQUEST["post_id"]);
		$VOTE_VALUE	= intval($_REQUEST["rate_value"]);
		// Check voted value
		if (empty($VOTE_VALUE) || !isset($this->_rate_values[$VOTE_VALUE])) {
			return print("Wrong vote value!");
		}
		// Try to get rate info (it need to be created before to prevent flooding)
		if (!empty($PHOTO_ID)) {
			$photo_info = db()->query_fetch(
				"SELECT * 
				FROM `".db('gallery_photos')."` 
				WHERE `id`=".intval($PHOTO_ID)." 
					AND `allow_rate`='1' 
					AND `is_public`='1'"
			);
		}

// TODO: add more access level checks (ability to view also non-public photos if allowed)

		// Last check for the vote record
		if (empty($photo_info["id"])) {
			return print("No photo id");
		}
		// Do not allow to rate own photos
		if ($this->USER_ID && $photo_info["user_id"] == $this->USER_ID) {
			if (!$this->__TEST_MODE__) {
				return print("You are not allowed to vote for own photo");
			}
		}

// TODO: need to add AJAX error response

// TODO: Get latest rate views and check if that photo is not there

		// Check if vote allowed
		$ALLOW_VOTE = false;
		// Check if user is allowed to make vote here and now
		if ($this->USER_ID) {
			$CHEAT_DETECTED = false;
			list($total_votes) = db()->query_fetch(
				"SELECT COUNT(*) AS `0` 
				FROM `".db('gallery_rate_votes')."` 
				WHERE `user_id`=".intval($photo_info["id"])
			);
			// More anti-cheat checks begin
			if ($this->ANTICHEAT_CHECKING && $total_votes) {
				list($num_target_users) = db()->query_fetch(
					"SELECT COUNT(*) AS `0` 
					FROM `".db('gallery_rate_votes')."` 
					WHERE `user_id`=".intval($photo_info["id"])." 
					GROUP BY `target_user_id`"
				);
				if ($num_target_users >= $this->ANTICHEAT_TARGET_USERS) {
					// Cet total number of positive and negative votes
					list($num_positive_targets) = db()->query_fetch(
						"SELECT COUNT(*) AS `0` 
						FROM `".db('gallery_rate_votes')."` 
						WHERE `user_id`=".intval($photo_info["id"])." 
							AND `voted` >= ".intval($this->ANTICHEAT_POSITIVE_VOTE)."
						GROUP BY `target_user_id`"
					);
					list($num_negative_targets) = db()->query_fetch(
						"SELECT COUNT(*) AS `0` 
						FROM `".db('gallery_rate_votes')."` 
						WHERE `user_id`=".intval($photo_info["id"])." 
							AND `voted` <= ".intval($this->ANTICHEAT_NEGATIVE_VOTE)."
						GROUP BY `target_user_id`"
					);
					$neg_to_pos		= $num_negative_targets ? $num_positive_targets / $num_negative_targets : 0;
					// Check if user voted mostly positively
					if ($neg_to_pos >= $this->ANTICHEAT_POS_TO_NEG) {
						$CHEAT_DETECTED = true;
					}
				}
				list($num_negative_votes) = db()->query_fetch(
					"SELECT COUNT(*) AS `0` 
					FROM `".db('gallery_rate_votes')."` 
					WHERE `user_id`=".intval($photo_info["id"])." 
						AND `voted` <= ".intval($this->ANTICHEAT_NEGATIVE_VOTE)
				);
				$neg_percent	= $total_votes ? $num_negative_votes / $total_votes : 0;
				// Check if user voted mostly negatively
				if ($total_votes >= $this->ANTICHEAT_NEGATE_AFTER && $neg_percent >= $this->ANTICHEAT_NEGATE_PART) {
					$CHEAT_DETECTED = true;
				}
			}
			$ALLOW_VOTE = true;
		}
		// DO check and save vote if params are ok
		if ($ALLOW_VOTE) {
			$sql_array = array(
				"photo_id"		=> intval($PHOTO_ID),
				"user_id"		=> intval($this->USER_ID),
				"target_user_id"=> intval($photo_info["user_id"]),
				"voted"			=> intval($VOTE_VALUE),
				"counted"		=> intval($CHEAT_DETECTED ? 0 : $VOTE_VALUE),
				"add_date"		=> time(),
				"ip"			=> _es(common()->get_ip()),
				"active"		=> 1,
			);
			// Check if user already voted for this photo
			$cur_photo_vote = db()->query_fetch(
				"SELECT * 
				FROM `".db('gallery_rate_votes')."` 
				WHERE `user_id` = ".intval($this->USER_ID)." 
					AND `photo_id` = ".intval($PHOTO_ID)
			);
			// Set empty previous vote results to for this photo from current user
			if (!empty($cur_photo_vote)) {
				db()->query(
					"UPDATE `".db('gallery_rate_votes')."` 
					SET `counted` = 0 
					WHERE `user_id` = ".intval($this->USER_ID)." 
						AND `photo_id` = ".intval($PHOTO_ID)
				);
			}
			db()->INSERT("gallery_rate_votes", $sql_array);
			// Update gallery photos table
			if (!$CHEAT_DETECTED) {
// TODO: needed to decide how to work without full log
				list($votes_sum, $num_votes) = db()->query_fetch(
					"SELECT SUM(`counted`) AS `0`, COUNT(*) AS `1` 
					FROM `".db('gallery_rate_votes')."` 
					WHERE `photo_id`=".intval($PHOTO_ID)." 
						AND `counted` != 0"
				);
				db()->query(
					"UPDATE `".db('gallery_photos')."` SET 
						`votes_sum`	= ".intval($votes_sum)." 
						,`num_votes`= ".intval($num_votes)." 
						,`rating`	= `votes_sum` / `num_votes` 
						,`last_vote_date`	= ".time()."
					WHERE `id`=".intval($PHOTO_ID)
				);
			}
		}
		// Cleanup old views
		db()->query(
			"DELETE FROM `".db('gallery_rate_views')."` WHERE `date` < ".(time() - $this->VIEWS_TTL)
		);
		// Store last photo in session
		$_SESSION["_photo_rating_last"] = array(
			"photo_id"	=> $PHOTO_ID,
			"voted"		=> $VOTE_VALUE,
			"counted"	=> $CHEAT_DETECTED ? 0 : $VOTE_VALUE,
			"date"		=> time(),
		);
		// Return result
		if ($_POST["by_ajax"]) {
			return print($PHOTO_ID.",".$VOTE_VALUE.",".$votes_sum.",".$num_votes.",".($num_votes ? round($votes_sum / $num_votes, 1) : 0));
		}
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	/**
	* Display box for given object
	*/
	function _show_ajax_box ($params = array()) {
		// Prepare input params
		$OBJECT_NAME		= !empty($params["object_name"]) ? $params["object_name"] : $_GET["object"];
		$OBJECT_ID			= intval(!empty($params["photo_id"]) ? $params["photo_id"] : $_GET["id"]);
		$OWNER_USER_ID		= intval($params["user_id"]);
		if (!$this->__TEST_MODE__) {
			// Do not display vote box for guests
			if (!$this->USER_ID) {
				return false;
			}
			// Do not display vote box for the owner
			if ($this->USER_ID && $OWNER_USER_ID && $OWNER_USER_ID == $this->USER_ID) {
				return false;
			}
// TODO: add basic check for allow rating (including latest voted photos)
		}
		// To prevent loading rating js code twice
		$GLOBALS["_rate_item_num_calls"]++;
		$this->_rate_values = my_array_merge(array("" => ""), $this->_rate_values);
//		$selected = ceil($this->NUM_STARS / 2);
		$selected = "";
		// Process template
		$replace = array(
			"object_id"			=> intval($OBJECT_ID),
			"object_name"		=> _prepare_html($OBJECT_NAME),
			"do_vote_url"		=> process_url("./?object=".PHOTO_RATING_CLASS_NAME."&action=ajax_vote"),
			"allow_vote"		=> intval((bool)$ALLOW_VOTE),
			"display_js"		=> intval($GLOBALS["_rate_item_num_calls"] == 1),
			"vote_box"			=> common()->select_box("vote", $this->_rate_values, $selected, false, 1, " class='vote_box' ", false),
		);
		return tpl()->parse(__CLASS__."/vote_box", $replace);
	}

	/**
	* Display box with last voted photo
	*/
	function _show_last_voted () {
		$info = $_SESSION["_photo_rating_last"];
		// For guests
		if (!$this->USER_ID) {
			if (!empty($info["photo_id"])) {
				$body = tpl()->parse(__CLASS__."/last_voted");
				// Cleanup result if needed
				if ($this->DISPLAY_RESULT_ONCE) {
					unset($_SESSION["_photo_rating_last"]);
				}
			}
			return $body;
		}
		if (empty($info["photo_id"])) {
			return "";
		}
		$PHOTO_ID = $info["photo_id"];
		// Get photo details
		$photo_info = db()->query_fetch(
			"SELECT * 
			FROM `".db('gallery_photos')."` 
			WHERE `id`=".intval($PHOTO_ID)." 
				AND `allow_rate`='1' 
				AND `is_public`='1'"
// TODO: add more complex access checks
		);
		if (empty($photo_info)) {
			return "";
		}
		// Get user info
		$user_info = user($photo_info["user_id"], "", array("WHERE" => array("active" => "1")));
		if (empty($user_info)) {
			return "";
		}
		$img_path = $this->GALLERY_OBJ->_create_name_from_tpl($photo_info, $this->PHOTO_TYPE_LAST);
		if (!@file_exists(INCLUDE_PATH. $img_path) || !@filesize(INCLUDE_PATH. $img_path)) {
			return "";
		}
		// Prepare other photo info
		$other_info = array();
		if (!empty($photo_info["other_info"])) {
			$other_info = unserialize($photo_info["other_info"]);
		}
		// Prepare real dimensions
		$real_w = $other_info[$this->PHOTO_TYPE_LAST]["w"];
		$real_h = $other_info[$this->PHOTO_TYPE_LAST]["h"];
		// Cleanup result if needed
		if ($this->DISPLAY_RESULT_ONCE) {
			unset($_SESSION["_photo_rating_last"]);
		}
		// Prepare template
		$replace = array(
			"photo_id"		=> $PHOTO_ID,
			"voted"			=> intval($info["voted"]),
			"counted"		=> intval($info["counted"]),
			"vote_date"		=> _format_date($info["date"]),
			"img_src"		=> WEB_PATH. $img_path,
			"real_w"		=> intval($real_w),
			"real_h"		=> intval($real_h),
			"photo_name"	=> _prepare_html($photo_info["name"]),
			"user_name"		=> _prepare_html(_display_name($user_info)),
			"user_link"		=> _profile_link($photo_info["user_id"]),
			"gallery_link"	=> "./?object=gallery&action=show&id=".intval($photo_info["user_id"]),
			"photo_link"	=> "./?object=gallery&action=show_medium_size&id=".intval($photo_info["id"]),
			"photo_rating"	=> round($photo_info["rating"], 1),
			"num_votes"		=> intval($photo_info["num_votes"]),
		);
		return tpl()->parse(__CLASS__."/last_voted", $replace);
	}

	/**
	* Prepare filter data
	*/
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= PHOTO_RATING_CLASS_NAME."_filter";
		// Connect common used arrays
		$f = INCLUDE_PATH."common_code.php";
		if (file_exists($f)) {
			include $f;
		}
		// Array of available filter fields
		$this->_fields_in_filter = array(
			"race",
			"sex",
			"country",
			"state",
		);
		// Prepae boxes
		$this->_boxes = array_merge((array)$this->_boxes, array(
			"state"			=> 'select_box("state",			$this->_states,			$selected, " ", 2, "", 0)',
			"country"		=> 'select_box("country",		$this->_countries,		$selected, 0, 2, "", 0)',
			"sex"			=> 'select_box("sex",			$this->_sex,			$selected, 0, 2, "", 0)',
			"race"			=> 'select_box("race",			$this->_races,			$selected, " ", 2, "", 0)',
		));
		// Process account types
		$this->_account_types	= main()->get_data("account_types");
		$this->_account_types2[" "]	= t("-- All --");
		foreach ((array)$this->_account_types as $k => $v) {
			$this->_account_types2[$k]	= t($v);
		}
		$this->_sex	= array_merge(array(" " => t("-- All --")), (array)$this->_sex);
	}

	/**
	* Generate filter SQL query
	*/
	function _create_filter_sql () {
		if (!$this->USE_FILTER) return "";
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) $SF[$k] = trim($v);
		// Prepare SQL
		if (strlen($SF["race"])) {
			$user_sql .= " AND `race` = '"._es($this->_races[$SF["race"]])."' \r\n";
		}
		if (strlen($SF["sex"])) {
			$user_sql .= " AND `sex` ='"._es($SF["sex"])."' \r\n";
		}
		$user_sql .= " AND `active` = '1'";
		// Create subquery for the user table
		if (!empty($user_sql)) {
			$sql .= " AND `user_id` IN(SELECT `id` FROM `".db('user')."` WHERE 1=1 ".$user_sql.") \r\n";
		}
		if (strlen($SF["country"])) {
			$sql .= " AND `geo_cc` ='"._es($SF["country"])."' \r\n";
		}
		if (strlen($SF["state"])) {
			$sql .= " AND `geo_rc` = '"._es($SF["state"])."' \r\n";
		}
		// Sorting here
		if (!empty($SF["sort_by"]) && isset($this->_sort_by[$SF["sort_by"]])) {
		 	$sql .= " ORDER BY `"._es($SF["sort_by"])."` \r\n";
			if (strlen($SF["sort_order"])) 	$sql .= " ".$SF["sort_order"]." \r\n";
		}
		return substr($sql, 0, -3);
	}

	/**
	* Session - based filter form
	*/
	function _show_filter () {
		if (!$this->USE_FILTER) return "";
		$replace = array(
			"save_action"	=> "./?object=".PHOTO_RATING_CLASS_NAME."&action=save_filter"._add_get(),
			"clear_url"		=> "./?object=".PHOTO_RATING_CLASS_NAME."&action=clear_filter"._add_get(),
		);
		foreach ((array)$this->_fields_in_filter as $name) {
			$replace[$name] = $_SESSION[$this->_filter_name][$name];
		}
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $_SESSION[$this->_filter_name][$item_name]);
		}
		return tpl()->parse(PHOTO_RATING_CLASS_NAME."/filter", $replace);
	}

	/**
	* Filter save method
	*/
	function save_filter ($silent = false) {
		if (!$this->USE_FILTER) return "";
		// Process featured countries
		if (FEATURED_COUNTRY_SELECT && !empty($_POST["country"]) && substr($_POST["country"], 0, 2) == "f_") {
			$_POST["country"] = substr($_POST["country"], 2);
		}
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_POST[$name];
		}
		if (!$silent) {
			js_redirect("./?object=".PHOTO_RATING_CLASS_NAME."&action=show");
		}
	}

	/**
	* Clear filter
	*/
	function clear_filter ($silent = false) {
		if (!$this->USE_FILTER) return "";
		if (isset($_SESSION[$this->_filter_name])) {
			$_SESSION[$this->_filter_name] = array();
		}
		if (!$silent) {
			js_redirect("./?object=".PHOTO_RATING_CLASS_NAME."&action=show");
		}
	}

	/**
	* Process custom box
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}
}
