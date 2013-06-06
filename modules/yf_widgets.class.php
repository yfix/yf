<?php

/**
* Widgets loader
*/
class yf_widgets {

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING);
		return false;
	}

	/**
	*/
	function _init() {
		$this->IS_FRONT = defined("IS_FRONT") ? IS_FRONT : 0;
	}

	/**
	* Main method
	*/
	function _show($params = array()) {
		$FOR_COLUMN = $params["column"];
		// Avoid several calls on one page
		if (!isset($GLOBALS['_widgets_from_db'])) {
			// Get current user front theme
			$user_theme_id = 0;
			if ($this->IS_FRONT == 1 && defined("FRONT_THEME_ID")) {
				$user_theme_id = FRONT_THEME_ID;
			}
			$GLOBALS['_widgets_from_db'] = array();
			// Put to the system cache
			if (main()->USE_SYSTEM_CACHE) {
				$override = main()->get_data("widgets_override");
				$priorities = array(
					"__theme__".$user_theme_id."__".$_GET["object"]."->".$_GET["action"],
					"__theme__".$user_theme_id."__".$_GET["object"],
					"__theme__".$user_theme_id."__",
					$_GET["object"]."->".$_GET["action"],
					$_GET["object"],
				);
				foreach ((array)$priorities as $v) {
					if (isset($override[$v])) {
						$GLOBALS['_widgets_from_db'] = $override[$v];
						break;
					}
				}
			} else {
				// Custom SQL if user has custom theme
				$theme_sql = "";
				if ($user_theme_id) {
					$theme_sql = "
							SELECT `id`,`columns` FROM `".db('widgets')."` WHERE `theme` LIKE '%;".intval($user_theme_id).";%' AND `object` = '".$_GET["object"]."' AND `action` = '".$_GET["action"]."' AND `active` = '1'
						) UNION ALL	(
							SELECT `id`,`columns` FROM `".db('widgets')."` WHERE `theme` LIKE '%;".intval($user_theme_id).";%' AND `object` = '".$_GET["object"]."' AND `action` = '' AND `active` = '1'
						) UNION ALL	(
							SELECT `id`,`columns` FROM `".db('widgets')."` WHERE `theme` LIKE '%;".intval($user_theme_id).";%' AND `object` = '' AND `action` = '' AND `active` = '1'
						) UNION ALL	(
					";
				}
				// Tricky query (selects top priority record for the current page)
				$A = db()->query_fetch("
					SELECT * FROM ((".$theme_sql."
						SELECT `id`,`columns` FROM `".db('widgets')."` WHERE `object` = '".$_GET["object"]."' AND `action` = '".$_GET["action"]."' AND `active` = '1'
					) UNION ALL	(
						SELECT `id`,`columns` FROM `".db('widgets')."` WHERE `object` = '".$_GET["object"]."' AND `action` = '' AND `active` = '1'
					)) AS `tmp` LIMIT 1"
				);
				if ($A["columns"]) {
					$GLOBALS['_widgets_from_db'] = @unserialize($A["columns"]);
				}
			}
		}
		// We have override widgets layout in db
		if (!empty($GLOBALS['_widgets_from_db'])) {
			$body = $GLOBALS['_widgets_from_db'][$FOR_COLUMN];
			if ($body) {
				$body = tpl()->parse_string("_widgets_".$FOR_COLUMN."__virtual", "", $body);
			}
			return $body;
		// Use default widgets scheme from stpls
		} else {
			$stpl_name = $FOR_COLUMN."_area_widgets";
			if (tpl()->_stpl_exists($stpl_name)) {
				$stpl_contents = tpl()->parse($stpl_name);
			}
			return $stpl_contents;
		}
	}
	
	/**
	* 
	*/
	function _special(){
	}
}
