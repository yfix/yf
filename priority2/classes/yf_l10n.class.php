<?php

class yf_l10n {

	/**
	* Framework constructor
	*/
	function _init() {
		$this->_load_lang();
	}

	/**
	* Load language
	*/
	function _load_lang($lang = "") {
		if (!$lang) {
			$lang = SEARCH_COUNTRY;
		}
		if (isset($this->_loaded[$lang])) {
			return false;
		}
		$this->_loaded[$lang] = false;

		$class_name = "l10n_".$lang;
		$dir_name = "classes/l10n/";
		$path = INCLUDE_PATH. $dir_name.$class_name.".class.php";
		if (file_exists($path)) {
			main()->init_class($class_name, $dir_name);
			$this->_loaded[$lang] = true;
		}
	}

	/**
	* Get localized var value
	*/
	function _get_var($name = "", $lang = "") {
		if (!$name) {
			return false;
		}
		if (!$lang && SEARCH_COUNTRY != "" && SEARCH_COUNTRY != "SEARCH_COUNTRY") {
			$lang = SEARCH_COUNTRY;
		}
		if (!$lang) {
			return false;
		}
		$this->_load_lang($lang);
		if (!$this->_loaded[$lang]) {
			return false;
		}
		return _class("l10n_".$lang, "classes/l10n/")->$name;
	}

	/**
	* Format number
	*/
	function format_number ($number = "", $lang = "") {
		$data = $this->_get_var("_format_number", $lang);
		if (!$data) {
			return $number;
		}
		return number_format((float)$number, (int)$data["decimals"], $data["dec_point"], $data["thousands_sep"]);
	}

	/**
	* Format price using country settings
	*/
	function format_price ($price = "", $lang = "") {
		$price = $this->format_number(intval($price), $lang);
		return str_replace("%s", $price, $this->_get_var("currency_sign", $lang));
	}

	/**
	* local translate vars
	*/
	function t($t_var = "", $lang = "") {
		$data = $this->_get_var("_trans_vars", $lang);
		if (!$data) {
			return t($t_var);
		}
		$t_var = str_replace(" ", "_", strtolower($t_var));
		$t_var = $data[$t_var];
		return $t_var;
	}

	/**
	* local translate vars
	*/
	function months_abbr_js($t_var = "", $lang = "") {
		$data = $this->_get_var("month_names_abbr", $lang);
//		return print_r($data, 1);
		return common()->json_encode($data);
/*
		if (!$data) {
			return $number;
		}
*/
	}
}