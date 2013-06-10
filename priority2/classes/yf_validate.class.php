<?php

/**
* Container for different validate methods
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_validate {

	/** @var int Minimal nick length */
	public $MIN_NICK_LENGTH		= 2;
	/** @var array Allowed nick symbols (display for user) */
	public $NICK_ALLOWED_SYMBOLS	= array("a-z","0-9","_","\-","@","#"," ");
	/** @var array Reserved words for the profile url (default) */
	public $reserved_words = array(
		"login",
		"logout",
		"admin",
		"admin_modules",
		"classes",
		"modules",
		"functions",
		"uploads",
		"fonts",
		"pages_cache",
		"core_cache",
		"templates"
	);

	/**
	* Constructor
	*/
	function _init () {
		// Get available user section modules
		$Q = db()->query("SELECT * FROM `".db('user_modules'));
		while ($A = db()->fetch_assoc($Q)) {
			$user_modules[$A["id"]] = $A["name"];
		}
		// Merge them with default ones
		if (is_array($user_modules)) {
			$this->reserved_words = array_merge($this->reserved_words, $user_modules);
		}
	}

	/**
	* Check user nick
	*/
	function _check_user_nick ($CUR_VALUE = "", $force_value_to_check = null, $name_in_form = "nick") {
		$TEXT_TO_CHECK = $_POST[$name_in_form];
		// Override value to check
		if (!is_null($force_value_to_check)) {
			$TEXT_TO_CHECK = $force_value_to_check;
			$OVERRIDE_MODE = true;
		}
		// Do check
		$_nick_pattern = implode("", $this->NICK_ALLOWED_SYMBOLS);
		if (empty($TEXT_TO_CHECK) || (strlen($TEXT_TO_CHECK) < $this->MIN_NICK_LENGTH)) {
			common()->_raise_error(t("Nick must have at least @num symbols", array("@num" => $this->MIN_NICK_LENGTH)));
		} elseif (!preg_match("/^[".$_nick_pattern."]+\$/iu", $TEXT_TO_CHECK)) {
			common()->_raise_error(t("Nick can contain only these characters: \"@text1\"", array("@text1" => _prepare_html(stripslashes(implode("\" , \"", $this->NICK_ALLOWED_SYMBOLS))))));
			if (!$OVERRIDE_MODE) {
				$_POST[$name_in_form] = preg_replace("/[^".$_nick_pattern."]+/iu", "", $_POST[$name_in_form]);
			}
		} elseif ($TEXT_TO_CHECK != $CUR_VALUE) {
			$NICK_ALREADY_EXISTS = (db()->query_num_rows("SELECT `id` FROM `".db('user')."` WHERE `nick`='"._es($TEXT_TO_CHECK)."'") >= 1);
			if ($NICK_ALREADY_EXISTS) {
				common()->_raise_error(t("Nick (\"@name\") is already reserved. Please try another one.", array("@name" => $TEXT_TO_CHECK)));
			}
		}
	}

	/**
	* Check user profile url
	*/
	function _check_profile_url ($CUR_VALUE = "", $force_value_to_check = null, $name_in_form = "profile_url") {
		$TEXT_TO_CHECK = $_POST[$name_in_form];
		// Override value to check
		if (!is_null($force_value_to_check)) {
			$TEXT_TO_CHECK = $force_value_to_check;
			$OVERRIDE_MODE = true;
		}
		// Ignore empty values
		if (empty($TEXT_TO_CHECK)) {
			return false;
		}
		// Do check profile url
		if (!empty($CUR_VALUE)) {
			common()->_raise_error(t("You have already chosen your profile url. You are not allowed to change it!"));
		} elseif (!preg_match("/^[a-z0-9]{0,64}$/ims", $TEXT_TO_CHECK)) {
			common()->_raise_error(t("Wrong Profile url format! (Letters or numbers only with no Spaces)"));
		} elseif (in_array($TEXT_TO_CHECK, $this->reserved_words)) {
			common()->_raise_error("This profile url (\"".$TEXT_TO_CHECK."\") is our site reserved name. Please try another one.");
		} elseif (db()->query_num_rows("SELECT `id` FROM `".db('user')."` WHERE `profile_url`='"._es($TEXT_TO_CHECK)."'") >= 1) {
			common()->_raise_error("This profile url (\"".$TEXT_TO_CHECK."\") has already been registered with us! Please try another one.");
		}
	}

	/**
	* 
	*/
	function _check_login () {
// TODO
		if ($_POST["login"] == "") {
			common()->_raise_error(t('Login required'));
		} elseif (db()->query_num_rows("SELECT `id` FROM `".db('user')."` WHERE `login`='"._es($_POST['login'])."'") >= 1) {
			common()->_raise_error("This login (".$_POST["login"].") has already been registered with us!");
		}
	}

	/**
	* 
	*/
	function _check_url () {
// TODO
		if ($_POST["recip_url"] == "http://" || $_POST["recip_url"] == "") {
			$_POST["recip_url"] = "";
		} elseif (!preg_match('#^http://[_a-z0-9-]+\\.[_a-z0-9-]+#ims', $_POST["recip_url"])) {
			common()->_raise_error(t('Invalid reciprocal URL'));
		}
//		return preg_match("!(http://|www|http://www)\\..+\\..+!", $url);
//		return preg_match('/^(http|https):\/\/[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}'.'((:[0-9]{1,5})?\/.*)?$/i', $url);
	}

	/**
	* 
	*/
	function _check_email () {
// TODO

//		$pattern = '/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,})+$/ims';
//		return preg_match($pattern, $email);
	}

	/**
	* 
	*/
	function _check_measurements () {
// TODO
		if ($_POST["measurements"] != "" && !preg_match("/[0-9]{2}(aaa|aa|a|b|c|d|dd|ddd|e|f|ff|g|gg|h|hh|j|jj|k|l)-([0-9]{2})-([0-9]{2})/i", $_POST["measurements"])) {
			common()->_raise_error(t('Invalid measurements (example, 36DD-27-32)!'));
		}
	}

	/**
	* Check selected location (country, region, city)
	*/
	function _check_location ($cur_country = "", $cur_region = "", $cur_city = "") {
		// Process featured countries
		if (FEATURED_COUNTRY_SELECT && !empty($_POST["country"]) && substr($_POST["country"], 0, 2) == "f_") {
			$_POST["country"] = substr($_POST["country"], 2);
		}
		// verify country
		if (!empty($_POST["country"])) {
			if (!isset($GLOBALS['countries'])) {
				$GLOBALS['countries'] = main()->get_data("countries");
			}
			// Check for correct country
			if (!isset($GLOBALS['countries'][$_POST["country"]])) {
				$_POST["country"]	= "";
				$_POST["region"]	= "";
				$_POST["state"]		= "";
				$_POST["city"]		= "";
			} else {
				$GLOBALS['_country_name'] = $GLOBALS['countries'][$_POST["country"]];
			}
		}
		// Verify region
		if (!empty($_POST["region"])) {
			$region_info = db()->query_fetch("SELECT * FROM `".db('geo_regions')."` WHERE `country` = '"._es($_POST["country"])."' AND `code`='"._es($_POST["region"])."'");
			if (empty($region_info)) {
				$_POST["region"]	= "";
				$_POST["state"]		= "";
				$_POST["city"]		= "";
			} else {
				$GLOBALS['_region_name'] = $region_info["name"];
			}
		}
		// Verify city
		if (!empty($_POST["city"])) {
			$city_info = db()->query_fetch("SELECT * FROM `".db('geo_city_location')."` WHERE `region` = '"._es($_POST["region"])."' AND `country` = '"._es($_POST["country"])."' AND `city`='"._es($_POST["city"])."'");
			if (empty($city_info)) {
				$_POST["city"]		= "";
			}
		}
	}

	/**
	* Check user birth date
	*/
	function _check_birth_date ($CUR_VALUE = "") {
		// Validate birth date
		$_POST["birth_date"]	= $CUR_VALUE;

		$_POST["year_birth"]	= intval($_POST["year_birth"]);
		$_POST["month_birth"]	= intval($_POST["month_birth"]);
		$_POST["day_birth"]		= intval($_POST["day_birth"]);
		if ($_POST["year_birth"] >= 1915 && $_POST["year_birth"] <= (date("Y") - 17)
			&& $_POST["month_birth"] >= 1 && $_POST["month_birth"] <= 12
			&& $_POST["day_birth"] >= 1 && $_POST["day_birth"] <= 31
		) {
			if ($_POST["month_birth"] < 10) {
				$_POST["month_birth"] = "0".$_POST["month_birth"];
			}
			if ($_POST["day_birth"] < 10) {
				$_POST["day_birth"] = "0".$_POST["day_birth"];
			}
			$_POST["birth_date"] = $_POST["year_birth"]."-".$_POST["month_birth"]."-".$_POST["day_birth"];
		}
		if (!empty($_POST["birth_date"])) {
			$_POST["age"] = _get_age_from_birth($_POST["birth_date"]);
		} else {
			// Make birth date required field
			if (in_array($_POST["account_type"], array("visitor","escort"))) {
				common()->_raise_error(t('Please enter you date of birth!'));
			}
		}
	}
}
