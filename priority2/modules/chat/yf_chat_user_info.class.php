<?php

/**
* Chat User Info
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_chat_user_info {

	var $CHAT_OBJ = null;

	/**
	* Chat User Info
	*/
	function yf_chat_user_info () {
		// Reference to the main chat object
		$this->CHAT_OBJ = module('chat');
	}

	/**
	* Show information about the user with given ID
	*/
	function _view() {
		$_GET["user_id"] = intval($_GET["user_id"]);
		if ($_GET["user_id"]) return false;
		// Get info
		$A = db()->query_fetch("SELECT * FROM `".db('chat_users')."` WHERE `id`=".intval($_GET["user_id"]));
		$allowed_fields = array();
		if (strlen($A["allowed_fields"])) {
			$allowed_fields = explode(",",$A["allowed_fields"]);
		}
		// Prepare birth date
		$A["birth"] = date("Y-m-d", $A["birth"]);
		// Check if info is correct
		if (empty($A["id"]) || empty($A["info_add_date"])) {
			return tpl()->parse("chat/no_info");
		}
		foreach ((array)$A as $name => $v) {
			if (!in_array($name, $allowed_fields) || in_array($name, array("photo","other_info"))) {
				continue;
			} elseif (in_array($name, array("married","children","drinking","smoking"))) {
				$v = $v ? t("yes") : t("no");
			} elseif ($name == "education")	{
				$v = $this->CHAT_OBJ->_edu_array[$v];
			} elseif ($name == "hair_color") {
				$v = $this->CHAT_OBJ->_hair_color[$v];
			} elseif ($name == "eyes_color") {
				$v = $this->CHAT_OBJ->_eyes_color[$v];
			} elseif ($name == "web_page") {
				$v = "<a href=\"".$v."\" target=\"_blank\">".$v."</a>";
			}
			// Skip empty values
			if (!strlen($v)) {
				continue;
			}
			// Process template
			$replace2 = array(
				"item"		=> t($name),
				"value"		=> $name != "web_page" ? _prepare_html($v) : $v,
				"bg_class"	=> $i++ % 2 ? "bg1" : "bg2",
			);
			$info_items .= tpl()->parse("chat/view_info_item", $replace2);
		}
		if (strlen($A["photo"]) && in_array("photo", $allowed_fields)) {
			if (file_exists(REAL_PATH.$this->CHAT_OBJ->PHOTOS_FOLDER.$A["photo"])) {
				$photo = "<img src=\"".WEB_PATH.$this->CHAT_OBJ->PHOTOS_FOLDER.$A["photo"]."\" hspace=\"5\" vspace=\"5\">";
			}
		}
		// Process template
		$replace = array(
			"login"			=> $A["login"]. ($A["group_id"] == 1 ? " (".t("Moderator").")" : ""),
			"info_items"	=> $info_items,
			"photo"			=> $photo,
			"other_info"	=> nl2br(_prepare_html($A["other_info"])),
			"statistics"	=> $this->_user_stats($A),
		);
		return tpl()->parse("chat/view_info_main", $replace);
	}

	/**
	* Count user statistics
	*/
	function _user_stats($A) {
		if (!$A["id"]) {
			return false;
		}
		$A2 = db()->query_fetch("SELECT MIN(`login_date`) AS `first_login`, MAX(`login_date`) AS `last_login` FROM `".db('chat_log_online')."` WHERE `user_id`=".intval($A['id']));
		$A3 = db()->query_fetch("SELECT SUM(`logout_date` - `login_date`) AS `month_chat_time` FROM `".db('chat_log_online')."` WHERE `user_id`=".intval($A['id'])." AND `logout_date`!=0 AND `login_date`>".strtotime(date("Y-m-01")));
		$stats["first_visit"]		= $A2["first_login"] ? date("H:i:s d/m/Y", $A2["first_login"]) : "";
		$stats["last_visit"]		= $A2["last_login"] ? date("H:i:s d/m/Y", $A2["last_login"]) : "";
		$stats["month_chat_time"]	= $A3["month_chat_time"] ? $this->_format_seconds_as_hours($A3["month_chat_time"]) : t("no_data");
		$stats["average_per_day"]	= $A3["month_chat_time"] ? $this->_format_seconds_as_hours(floor($A3["month_chat_time"] / date("t"))) : t("no_data");
		$stats["info_add_date"]		= date("H:i:s d/m/Y", $A["info_add_date"]);
		$stats["photo_date"]		= $A["photo"] ? date("H:i:s d/m/Y", @filemtime(REAL_PATH.$this->CHAT_OBJ->PHOTOS_FOLDER.$A["photo"])) : "";
		return tpl()->parse("chat/view_info_stats", $stats);
	}

	/**
	* Format time given in seconds
	*/
	function _format_seconds_as_hours($time_in_seconds = 0) {
		$hours		= floor($time_in_seconds / 60 / 60);
		$minutes	= floor(($time_in_seconds - $hours * 3600) / 60);
		$seconds	= $time_in_seconds - $hours * 3600 - $minutes * 60;
		if ($time_in_seconds < 60) $formatted = $seconds." ".t("seconds");
		else $formatted = ($hours ? $hours." ".t("hours").", " : "").str_pad($minutes,2,"0", STR_PAD_LEFT)." ".t("minutes");
		return $formatted;
	}

	/**
	* Form to edit user info
	*/
	function _edit() {
		$A = db()->query_fetch("SELECT * FROM `".db('chat_users')."` WHERE `id`=".intval(CHAT_USER_ID));
		$replace = $A;
		// Show moderator
		if ($A["group_id"] == 1) {
			$replace["login"] .= " (".t("Moderator").")";
		}
		$replace["form_action"]		= "./?object=".CHAT_CLASS_NAME."&action=save_personal_info";
		$replace["birth_box"]		= common()->date_box($A["birth"], "1930-1999");
		$replace["education_box"]	= common()->select_box("education", $this->CHAT_OBJ->_edu_array, $A["education"], false);
		$replace["married_box"]		= common()->select_box("married", array(t("no"), t("yes")), $A["married"], false);
		$replace["children_box"]	= common()->select_box("children", array(t("no"), t("yes")), $A["children"], false);
		$replace["drink_box"]		= common()->select_box("drinking", array(t("no"), t("yes")), $A["drinking"], false);
		$replace["smoke_box"]		= common()->select_box("smoking", array(t("no"), t("yes")), $A["smoking"], false);
		$replace["hair_color_box"]	= common()->select_box("hair_color", $this->CHAT_OBJ->_hair_color, $A["hair_color"], false);
		$replace["eyes_color_box"]	= common()->select_box("eyes_color", $this->CHAT_OBJ->_eyes_color, $A["eyes_color"], false);
		$replace["height"]			= $A["height"] ? $A["height"] : "";
		$replace["weight"]			= $A["weight"] ? $A["weight"] : "";
		if (strlen($A["allowed_fields"])) {
			$allowed_fields = explode(",",$A["allowed_fields"]);
			foreach ((array)$allowed_fields as $name) {
				$replace["c_".$name] = "checked";
			}
		}
		return tpl()->parse("chat/edit_info", $replace, true);
	}

	/**
	* Save user info
	*/
	function _save() {
		if (!count($_POST)) {
			return false;
		}
		// Process info
		$info_txt_fields = array(
			"first_name",
			"last_name",
			"city",
			"country",
			"job",
			"speciality",
			"home_phone",
			"cell_phone",
			"icq",
			"msn",
			"yahoo",
			"web_page",
			"other_info"
		);
		$info_int_fields = array(
			"birth",
			"height",
			"weight",
			"hair_color",
			"eyes_color",
			"education",
			"married",
			"children",
			"drinking",
			"smoking"
		);
		if (strlen($_POST["password"]) < 4 || strlen($_POST["password"]) > 32) {
			common()->_raise_error(t("4 > password > 32"));
		}
		$_POST["birth"] = strtotime(intval($_POST["year"])."-".intval($_POST["month"])."-".intval($_POST["day"]));
		// Try to upload photo
		if ($_FILES["photo"]["name"]) {
			// Check image file size
			if ($_FILES["photo"]["size"] > $this->CHAT_OBJ->PHOTO_MAX_SIZE) {
				common()->_raise_error(t("too_large_file").": size=".$_FILES["photo"]["size"]);
			} elseif (is_uploaded_file($_FILES["photo"]["tmp_name"])) {
				list($w, $h, $type,) = getimagesize($_FILES["photo"]["tmp_name"]);
				// Check image dimensions and type
				if ($type != 2) {
					common()->_raise_error(t("only_JPEG_allowed"));
				}
				if ($w > $this->CHAT_OBJ->PHOTO_MAX_WIDTH || $h > $this->CHAT_OBJ->PHOTO_MAX_HEIGHT) {
					common()->_raise_error(t("too_big_dimensions").": w=".$w.",h=".$h);
				}
				// Upload file if everything is ok
				if (!common()->_error_exists()) {
					$new_photo = CHAT_USER_ID.".jpg";
					@move_uploaded_file($_FILES["photo"]["tmp_name"], REAL_PATH.$this->CHAT_OBJ->PHOTOS_FOLDER.$new_photo);
				}
			}
		}
		// Continue if no errors occured
		if (!common()->_error_exists()) {
			$sql = "UPDATE `".db('chat_users')."` SET \r\n";
			foreach ((array)$info_txt_fields as $name) {
				if (isset($_POST[$name])) $sql .= "`".$name."`='"._es(trim(strip_tags($_POST[$name])))."',\r\n";
				if ($_POST["c_".$name]) $allowed_fields[] = $name;
			}
			foreach ((array)$info_int_fields as $name) {
				if (isset($_POST[$name])) $sql .= "`".$name."`=".intval($_POST[$name]).",\r\n";
				if ($_POST["c_".$name]) $allowed_fields[] = $name;
			}
			if ($_POST["c_photo"]) {
				$allowed_fields[] = "photo";
			}
			if (strlen($new_photo)) {
				$sql .= "`photo`='".$new_photo."',\r\n";
			}
			$sql .= "`info_add_date`=".time().",\r\n";
			$sql .= "`allowed_fields`='".implode(",",$allowed_fields)."'\r\n";
			$sql .= " WHERE `id`=".intval(CHAT_USER_ID);
			db()->query($sql);
			// Update online table (user info status)
			$A = db()->query_fetch("SELECT * FROM `".db('chat_users')."` WHERE `id`=".intval(CHAT_USER_ID));
			$info_status = strlen($A["photo"]) ? 2 : ($A["info_add_date"] ? 1 : 0);
			$sql = "UPDATE `".db('chat_online')."` SET `info_status`=".$info_status." WHERE `user_id`=".intval(CHAT_USER_ID);
			db()->query($sql);
			// Close window after saving
			$body .= "<script>window.close();</script>\r\n";
			$body .= "<script>alert('".t("save_successful")."');</script>\r\n";
		} else {
			$body .= common()->_show_error_message();
		}
		return $body;
	}
}
