<?php

/**
* Do clean UTF8-encoded text from unwanted chars
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_utf8_clean {

	/** @var @conf_skip */
	var $UTF8_LOWER_ACCENTS = array(
	  'à' => 'a', 'ô' => 'o', 'ď' => 'd', 'ḟ' => 'f', 'ë' => 'e', 'š' => 's', 'ơ' => 'o',
	  'ß' => 'ss', 'ă' => 'a', 'ř' => 'r', 'ț' => 't', 'ň' => 'n', 'ā' => 'a', 'ķ' => 'k',
	  'ŝ' => 's', 'ỳ' => 'y', 'ņ' => 'n', 'ĺ' => 'l', 'ħ' => 'h', 'ṗ' => 'p', 'ó' => 'o',
	  'ú' => 'u', 'ě' => 'e', 'é' => 'e', 'ç' => 'c', 'ẁ' => 'w', 'ċ' => 'c', 'õ' => 'o',
	  'ṡ' => 's', 'ø' => 'o', 'ģ' => 'g', 'ŧ' => 't', 'ș' => 's', 'ė' => 'e', 'ĉ' => 'c',
	  'ś' => 's', 'î' => 'i', 'ű' => 'u', 'ć' => 'c', 'ę' => 'e', 'ŵ' => 'w', 'ṫ' => 't',
	  'ū' => 'u', 'č' => 'c', 'ö' => 'oe', 'è' => 'e', 'ŷ' => 'y', 'ą' => 'a', 'ł' => 'l',
	  'ų' => 'u', 'ů' => 'u', 'ş' => 's', 'ğ' => 'g', 'ļ' => 'l', 'ƒ' => 'f', 'ž' => 'z',
	  'ẃ' => 'w', 'ḃ' => 'b', 'å' => 'a', 'ì' => 'i', 'ï' => 'i', 'ḋ' => 'd', 'ť' => 't',
	  'ŗ' => 'r', 'ä' => 'ae', 'í' => 'i', 'ŕ' => 'r', 'ê' => 'e', 'ü' => 'ue', 'ò' => 'o',
	  'ē' => 'e', 'ñ' => 'n', 'ń' => 'n', 'ĥ' => 'h', 'ĝ' => 'g', 'đ' => 'd', 'ĵ' => 'j',
	  'ÿ' => 'y', 'ũ' => 'u', 'ŭ' => 'u', 'ư' => 'u', 'ţ' => 't', 'ý' => 'y', 'ő' => 'o',
	  'â' => 'a', 'ľ' => 'l', 'ẅ' => 'w', 'ż' => 'z', 'ī' => 'i', 'ã' => 'a', 'ġ' => 'g',
	  'ṁ' => 'm', 'ō' => 'o', 'ĩ' => 'i', 'ù' => 'u', 'į' => 'i', 'ź' => 'z', 'á' => 'a',
	  'û' => 'u', 'þ' => 'th', 'ð' => 'dh', 'æ' => 'ae', 'µ' => 'u', 'ĕ' => 'e', '€'=>'€','£'=>'£',' '=>' '/*,''=>''*/,'﻿'=>'﻿',
	);
	/** @var @conf_skip */
	var $UTF8_UPPER_ACCENTS = array(
	  'À' => 'A', 'Ô' => 'O', 'Ď' => 'D', 'Ḟ' => 'F', 'Ë' => 'E', 'Š' => 'S', 'Ơ' => 'O',
	  'Ă' => 'A', 'Ř' => 'R', 'Ț' => 'T', 'Ň' => 'N', 'Ā' => 'A', 'Ķ' => 'K',
	  'Ŝ' => 'S', 'Ỳ' => 'Y', 'Ņ' => 'N', 'Ĺ' => 'L', 'Ħ' => 'H', 'Ṗ' => 'P', 'Ó' => 'O',
	  'Ú' => 'U', 'Ě' => 'E', 'É' => 'E', 'Ç' => 'C', 'Ẁ' => 'W', 'Ċ' => 'C', 'Õ' => 'O',
	  'Ṡ' => 'S', 'Ø' => 'O', 'Ģ' => 'G', 'Ŧ' => 'T', 'Ș' => 'S', 'Ė' => 'E', 'Ĉ' => 'C',
	  'Ś' => 'S', 'Î' => 'I', 'Ű' => 'U', 'Ć' => 'C', 'Ę' => 'E', 'Ŵ' => 'W', 'Ṫ' => 'T',
	  'Ū' => 'U', 'Č' => 'C', 'Ö' => 'Oe', 'È' => 'E', 'Ŷ' => 'Y', 'Ą' => 'A', 'Ł' => 'L',
	  'Ų' => 'U', 'Ů' => 'U', 'Ş' => 'S', 'Ğ' => 'G', 'Ļ' => 'L', 'Ƒ' => 'F', 'Ž' => 'Z',
	  'Ẃ' => 'W', 'Ḃ' => 'B', 'Å' => 'A', 'Ì' => 'I', 'Ï' => 'I', 'Ḋ' => 'D', 'Ť' => 'T',
	  'Ŗ' => 'R', 'Ä' => 'Ae', 'Í' => 'I', 'Ŕ' => 'R', 'Ê' => 'E', 'Ü' => 'Ue', 'Ò' => 'O',
	  'Ē' => 'E', 'Ñ' => 'N', 'Ń' => 'N', 'Ĥ' => 'H', 'Ĝ' => 'G', 'Đ' => 'D', 'Ĵ' => 'J',
	  'Ÿ' => 'Y', 'Ũ' => 'U', 'Ŭ' => 'U', 'Ư' => 'U', 'Ţ' => 'T', 'Ý' => 'Y', 'Ő' => 'O',
	  'Â' => 'A', 'Ľ' => 'L', 'Ẅ' => 'W', 'Ż' => 'Z', 'Ī' => 'I', 'Ã' => 'A', 'Ġ' => 'G',
	  'Ṁ' => 'M', 'Ō' => 'O', 'Ĩ' => 'I', 'Ù' => 'U', 'Į' => 'I', 'Ź' => 'Z', 'Á' => 'A',
	  'Û' => 'U', 'Þ' => 'Th', 'Ð' => 'Dh', 'Æ' => 'Ae', 'Ĕ' => 'E', '€'=>'€','£'=>'£',' '=>' ','﻿'=>'﻿',
	);
	
	/**
	* Constructor
	*/
	function _init () {
		include_once (YF_PATH. "libs/utf8_funcs/utils/ascii.php");
/*
		// russian ones here
		foreach (range("а","я") as $v) {
			$this->UTF8_LOWER_ACCENTS[$v] = $v;
		}
		foreach (range("А","Я") as $v) {
			$this->UTF8_UPPER_ACCENTS[$v] = $v;
		}
		*/
/*
		foreach ((array)$this->UTF8_LOWER_ACCENTS as $k => $v) {
echo $k." <b> ".dechex(ord($k{0}))." ".dechex(ord($k{1}))." </b><br />\n";
		}
*/


		$this->fss_exists = function_exists("fss_prep_replace");

	}

	/**
	* Do clean
	*/
	function _do ($text = "", $params = array()) {
		if (!strlen($text)) {
			return "";
		}
		$text = utf8_strip_ascii_ctrl($text);
// TODO: make the country be bassed as para to work from inside the admin section
		if ((SEARCH_COUNTRY == 'ru' || $params["country"] == "ru" || SEARCH_COUNTRY == 'ua' || $params["country"] == "ua") && preg_match("/[а-яА-Я]/", $text)) {
			$text = $this->_utf8_bad_for_rus_clean($text);
		} else {
			$text = $this->_utf8_strip_non_ascii($text);
		}
		if ($params["simple_chars"]) {
			$allowed_symbols = implode("", array_keys($this->UTF8_LOWER_ACCENTS)). implode("", array_keys($this->UTF8_UPPER_ACCENTS));
			if ((SEARCH_COUNTRY == 'ru' || $params["country"] == "ru" || SEARCH_COUNTRY == 'ua' || $params["country"] == "ua")) {
				$allowed_symbols .= "а-яА-Я";
			}
			$text = preg_replace("/[^a-z0-9".$allowed_symbols." ]/", "", $text);
		}
		return $text;
	}

	/**
	* Special for the russian language
	*/
	function _utf8_bad_for_rus_clean($str) {
		$UTF8_BAD =
			'([\x00-\x7F]'.						  # ASCII (including control chars)
			'|[\xC2-\xDF][\x80-\xBF]'.			   # non-overlong 2-byte
			'|\xE0[\xA0-\xBF][\x80-\xBF]'.		   # excluding overlongs
			'|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'.	# straight 3-byte
			'|\xED[\x80-\x9F][\x80-\xBF]'.		   # excluding surrogates
			'|\xF0[\x90-\xBF][\x80-\xBF]{2}'.		# planes 1-3
			'|[\xF1-\xF3][\x80-\xBF]{3}'.			# planes 4-15
			'|\xF4[\x80-\x8F][\x80-\xBF]{2}'.		# plane 16
			'|(.{1}))';							  # invalid byte
		ob_start();
		while (preg_match('/'.$UTF8_BAD.'/S', $str, $matches)) {
			if ( !isset($matches[2])) {
				echo $matches[0];
			}
			$str = substr($str,strlen($matches[0]));
		}
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}


	/**
	* Strip out all non-7bit ASCII bytes
	* If you need to transmit a string to system which you know can only
	* support 7bit ASCII, you could use this function.
	* @param string
	* @return string with non ASCII bytes removed
	*/
	function _utf8_strip_non_ascii ($str) {
		ob_start();
		while (preg_match('/^([\x00-\x7F]+)|([^\x00-\x7F]+)/S', $str, $matches)) {
			if (!isset($matches[2])) {
				echo $matches[0];
			} else {
				// Leave accented symbols untouched
				if (isset($this->UTF8_LOWER_ACCENTS[$matches[0]]) || isset($this->UTF8_UPPER_ACCENTS[$matches[0]])) {
					echo $matches[0];
				}
//echo "<b> ".dechex(ord($matches[0]{0}))." ".dechex(ord($matches[0]{1}))." </b>";
			}
			$str = substr($str, strlen($matches[0]));
		}
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}

	/**
	* Unaccent
	*/
	function _unaccent ($str = "") {
		if (!common()->is_utf8($str)) {
			$str = utf8_encode($str);
		}
//return $this->_unaccent_test3($str);
		if ($this->fss_exists && !$GLOBALS["force_strtr"]) {
// TODO: fss
			if(!isset($GLOBALS["fss"]["unaccent"])){
				$s = array_merge($this->UTF8_UPPER_ACCENTS, $this->UTF8_LOWER_ACCENTS);
				$GLOBALS["fss"]["unaccent"] = fss_prep_replace($s);
			}
			$str = fss_exec_replace($GLOBALS["fss"]["unaccent"], $str);
			
			
			return $str;
		}
		$str = strtr($str, $this->UTF8_UPPER_ACCENTS);
		$str = strtr($str, $this->UTF8_LOWER_ACCENTS);
		return $str;
 	}

	/*
	* Alternate experimental method 2 (same speed, same results)
	*/
	function _unaccent_test2($string = "") {
		$transliteration =  array(
			"À" => "A","Á" => "A","Â" => "A","Ã" => "A","Ä" => "A",
			"Å" => "A","Æ" => "A","Ā" => "A","Ą" => "A","Ă" => "A",
			"Ç" => "C","Ć" => "C","Č" => "C","Ĉ" => "C","Ċ" => "C",
			"Ď" => "D","Đ" => "D","È" => "E","É" => "E","Ê" => "E",
			"Ë" => "E","Ē" => "E","Ę" => "E","Ě" => "E","Ĕ" => "E",
			"Ė" => "E","Ĝ" => "G","Ğ" => "G","Ġ" => "G","Ģ" => "G",
			"Ĥ" => "H","Ħ" => "H","Ì" => "I","Í" => "I","Î" => "I",
			"Ï" => "I","Ī" => "I","Ĩ" => "I","Ĭ" => "I","Į" => "I",
			"İ" => "I","Ĳ" => "IJ","Ĵ" => "J","Ķ" => "K","Ľ" => "K",
			"Ĺ" => "K","Ļ" => "K","Ŀ" => "K","Ł" => "L","Ñ" => "N",
			"Ń" => "N","Ň" => "N","Ņ" => "N","Ŋ" => "N","Ò" => "O",
			"Ó" => "O","Ô" => "O","Õ" => "O","Ö" => "Oe","Ø" => "O",
			"Ō" => "O","Ő" => "O","Ŏ" => "O","Œ" => "OE","Ŕ" => "R",
			"Ř" => "R","Ŗ" => "R","Ś" => "S","Ş" => "S","Ŝ" => "S",
			"Ș" => "S","Š" => "S","Ť" => "T","Ţ" => "T","Ŧ" => "T",
			"Ț" => "T","Ù" => "U","Ú" => "U","Û" => "U","Ü" => "Ue",
			"Ū" => "U","Ů" => "U","Ű" => "U","Ŭ" => "U","Ũ" => "U",
			"Ų" => "U","Ŵ" => "W","Ŷ" => "Y","Ÿ" => "Y","Ý" => "Y",
			"Ź" => "Z","Ż" => "Z","Ž" => "Z","à" => "a","á" => "a",
			"â" => "a","ã" => "a","ä" => "ae","ā" => "a","ą" => "a",
			"ă" => "a","å" => "a","æ" => "ae","ç" => "c","ć" => "c",
			"č" => "c","ĉ" => "c","ċ" => "c","ď" => "d","đ" => "d",
			"è" => "e","é" => "e","ê" => "e","ë" => "e","ē" => "e",
			"ę" => "e","ě" => "e","ĕ" => "e","ė" => "e","ƒ" => "f",
			"ĝ" => "g","ğ" => "g","ġ" => "g","ģ" => "g","ĥ" => "h",
			"ħ" => "h","ì" => "i","í" => "i","î" => "i","ï" => "i",
			"ī" => "i","ĩ" => "i","ĭ" => "i","į" => "i","ı" => "i",
			"ĳ" => "ij","ĵ" => "j","ķ" => "k","ĸ" => "k","ł" => "l",
			"ľ" => "l","ĺ" => "l","ļ" => "l","ŀ" => "l","ñ" => "n",
			"ń" => "n","ň" => "n","ņ" => "n","ŉ" => "n","ŋ" => "n",
			"ò" => "o","ó" => "o","ô" => "o","õ" => "o","ö" => "oe",
			"ø" => "o","ō" => "o","ő" => "o","ŏ" => "o","œ" => "oe",
			"ŕ" => "r","ř" => "r","ŗ" => "r","ś" => "s","š" => "s",
			"ť" => "t","ù" => "u","ú" => "u","û" => "u","ü" => "ue",
			"ū" => "u","ů" => "u","ű" => "u","ŭ" => "u","ũ" => "u",
			"ų" => "u","ŵ" => "w","ÿ" => "y","ý" => "y","ŷ" => "y",
			"ż" => "z","ź" => "z","ž" => "z","ß" => "ss","ſ" => "ss"
		);
		$string = strtr($string, $transliteration);
		return $string;
	}

	/*
	* Alternate experimental method (more speed, but results sometimes not exact as on base variant)
	* Currently fastest variant
	*/
	function _unaccent_test3($text = "") {
		static $search;
		if (!$search) {
			$search = array();
			// Get the HTML entities table into an array
			$trans = get_html_translation_table(HTML_ENTITIES);
			// Go through the entity mappings one-by-one
			foreach ((array)$trans as $literal => $entity) {
			  // Make sure we don't process any other characters
			  // such as fractions, quotes etc:
			  if (ord($literal) >= 192) {
				// Get the accented form of the letter
				// Get e.g. 'E' from the string '&Eacute'
				$search[utf8_encode($literal)] = $entity[1];
			  }
			}
		}
		return strtr($text, $search);
	}
}
