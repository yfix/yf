<?php

/**
* Output cache refresh triggers
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_output_cache_triggers {

	/**
	* Execute triggers code
	*
	* @access	private
	* @return	string	Output
	*/
	function _exec_trigger ($params = array()) {
		// Output cache trigger
		if (!main()->OUTPUT_CACHING || module("output_cache")->_oc_trigger_executed) {
			return false;
		}
		$OBJECT		= !empty($params["object"]) ? $params["object"] : $_GET["object"];
		$ACTION		= !empty($params["action"]) ? $params["action"] : $_GET["action"];
		$USER_ID	= !empty($params["user_id"]) ? $params["user_id"] : main()->USER_ID;
		$USER_ID2	= $params["user_id2"];
		$CAT_ID		= $params["cat_id"];
		$AD_ID		= $params["ad_id"];
		$SEX		= $params["sex"];
		// Triggers here
		if ($OBJECT == "user_info") {
			$Q = db()->query("SELECT `ad_id`,`cat_id` FROM `".db('ads')."` WHERE `user_id` IN(".intval($USER_ID).")");
			while ($ad_info = db()->fetch_assoc($Q)) {
				_class("output_cache")->_clean_by_params(array(
					"object"	=> "escort",
					"id"		=> $ad_info["ad_id"],
				));
			}
		} elseif ($OBJECT == "friends" && in_array($ACTION, array("add","delete"))) {
			$Q = db()->query("SELECT `ad_id`,`cat_id` FROM `".db('ads')."` WHERE `user_id` IN(".intval($USER_ID).",".intval($USER_ID2).")");
			while ($ad_info = db()->fetch_assoc($Q)) {
				_class("output_cache")->_clean_by_params(array(
					"object"	=> "escort",
					"id"		=> $ad_info["ad_id"],
				));
			}
		} elseif ($OBJECT == "account" && in_array($ACTION, array("cancel_mship"))) {
			$Q = db()->query("SELECT `ad_id`,`cat_id` FROM `".db('ads')."` WHERE `user_id`=".intval($USER_ID));
			while ($ad_info = db()->fetch_assoc($Q)) {
				_class("output_cache")->_clean_by_params(array(
					"object"	=> "category",
					"cat_id"	=> $ad_info["cat_id"],
					"sex"		=> $ad_info["sex"],
				));
				_class("output_cache")->_clean_by_params(array(
					"object"	=> "escort",
					"id"		=> $ad_info["ad_id"],
				));
			}
			_class("output_cache")->_clean_by_params(array(
				"object"	=> "recent",
			));
		} elseif ($OBJECT == "account" && in_array($ACTION, array("favorite_add","favorite_delete"))) {
			$Q = db()->query("SELECT `ad_id`,`cat_id` FROM `".db('ads')."` WHERE `user_id` IN(".intval($USER_ID).",".intval($USER_ID2).")");
			while ($ad_info = db()->fetch_assoc($Q)) {
				_class("output_cache")->_clean_by_params(array(
					"object"	=> "escort",
					"id"		=> $ad_info["ad_id"],
				));
			}
		} elseif ($OBJECT == "account" && in_array($ACTION, array("ignore_user","unignore_user"))) {
			$Q = db()->query("SELECT `ad_id`,`cat_id` FROM `".db('ads')."` WHERE `user_id` IN(".intval($USER_ID).",".intval($USER_ID2).")");
			while ($ad_info = db()->fetch_assoc($Q)) {
				_class("output_cache")->_clean_by_params(array(
					"object"	=> "escort",
					"id"		=> $ad_info["ad_id"],
				));
			}
		} elseif (in_array($OBJECT, array("account","manage_ads")) && in_array($ACTION, array("list_ad"))) {
			_class("output_cache")->_clean_by_params(array(
				"object"	=> "category",
				"cat_id"	=> $CAT_ID,
				"sex"		=> $SEX,
			));
			_class("output_cache")->_clean_by_params(array(
				"object"	=> "recent",
			));
		} elseif (in_array($OBJECT, array("account","manage_ads")) && in_array($ACTION, array("edit_ad","delete_ad","multi_moderate"))) {
			_class("output_cache")->_clean_by_params(array(
				"object"	=> "category",
				"cat_id"	=> $CAT_ID,
				"sex"		=> $SEX,
			));
			_class("output_cache")->_clean_by_params(array(
				"object"	=> "escort",
				"id"		=> $AD_ID,
			));
			_class("output_cache")->_clean_by_params(array(
				"object"	=> "recent",
			));
		} elseif ($OBJECT == "reviews" && in_array($ACTION, array("insert","update","delete"))) {
			$Q = db()->query("SELECT `ad_id`,`cat_id` FROM `".db('ads')."` WHERE `user_id` IN(".intval($USER_ID).")");
			while ($ad_info = db()->fetch_assoc($Q)) {
				_class("output_cache")->_clean_by_params(array(
					"object"	=> "escort",
					"id"		=> $ad_info["ad_id"],
				));
			}
		} elseif ($OBJECT == "user_info" && in_array($ACTION, array("insert","update","delete"))) {
			$Q = db()->query("SELECT `ad_id`,`cat_id` FROM `".db('ads')."` WHERE `user_id` IN(".intval($USER_ID).")");
			while ($ad_info = db()->fetch_assoc($Q)) {
				_class("output_cache")->_clean_by_params(array(
					"object"	=> "escort",
					"id"		=> $ad_info["ad_id"],
				));
				_class("output_cache")->_clean_by_params(array(
					"object"	=> "category",
					"cat_id"	=> $ad_info["cat_id"],
					"sex"		=> $ad_info["sex"],
				));
			}
		} elseif (($OBJECT == "bad_contact_report" && in_array($ACTION, array("send")))
				|| ($OBJECT == "manage_bad_contacts" && in_array($ACTION, array("edit","delete")))
		) {
			$Q = db()->query("SELECT `ad_id`,`cat_id` FROM `".db('ads')."` WHERE `user_id` IN(".intval($USER_ID).")");
			while ($ad_info = db()->fetch_assoc($Q)) {
				_class("output_cache")->_clean_by_params(array(
					"object"	=> "escort",
					"id"		=> $ad_info["ad_id"],
				));
			}
		}
		// To prevent multi-calls
		module("output_cache")->_oc_trigger_executed = true;
	}
}
