<?php

/**
* Typo Generator Class
* 
* @example
* 
* $typoEngine = new yf_text_typos();
* $typos = $typoEngine->get_wrong_key_typos("Hello");
* print_r($typos);
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_text_typos {

	/** @var array @conf_skip
	* array of keys near character on a QWERTY keyboard
	* only valid characters in a domain name
	*/
	public $keyboard = array(
/*
		// top row
		'1' => array( '2', 'q' ),
		'2' => array( '1', 'q', 'w', '3' ),
		'3' => array( '2', 'w', 'e', '4' ),
		'4' => array( '3', 'e', 'r', '5' ),
		'5' => array( '4', 'r', 't', '6' ),
		'6' => array( '5', 't', 'y', '7' ),
		'7' => array( '6', 'y', 'u', '8' ),
		'8' => array( '7', 'u', 'i', '9' ),
		'9' => array( '8', 'i', 'o', '0' ),
		'0' => array( '9', 'o', 'p', '-' ),
		'-' => array( '0', 'p' ),
*/
		// 2nd from top
		'q' => array( '1', '2', 'w', 'a' ),
		'w' => array( 'q', 'a', 's', 'e', '3', '2' ),
		'e' => array( 'w', 's', 'd', 'r', '4', '3' ),
		'r' => array( 'e', 'd', 'f', 't', '5', '4' ),
		't' => array( 'r', 'f', 'g', 'y', '6', '5' ),	
		'y' => array( 't', 'g', 'h', 'u', '7', '6' ),
		'u' => array( 'y', 'h', 'j', 'i', '8', '7' ),
		'i' => array( 'u', 'j', 'k', 'o', '9', '8' ),
		'o' => array( 'i', 'k', 'l', 'p', '0', '9' ),
		'p' => array( 'o', 'l', '-', '0' ),
		// home row
		'a' => array( 'z', 's' , 'w', 'q' ),
		's' => array( 'a', 'z', 'x', 'd', 'e', 'w' ),
		'd' => array( 's', 'x', 'c', 'f', 'r', 'e' ),
		'f' => array( 'd', 'c', 'v', 'g', 't', 'r' ),
		'g' => array( 'f', 'v', 'b', 'h', 'y', 't' ),
		'h' => array( 'g', 'b', 'n', 'j', 'u', 'y' ),
		'j' => array( 'h', 'n', 'm', 'k', 'i', 'u' ),
		'k' => array( 'j', 'm', 'l', 'o', 'i' ),
		'l' => array( 'k', 'p', 'o' ),
		// bottom row
		'z' => array( 'x', 's', 'a' ),
		'x' => array( 'z', 'c', 'd', 's' ),
		'c' => array( 'x', 'v', 'f', 'd' ),
		'v' => array( 'c', 'b', 'g', 'f' ),
		'b' => array( 'v', 'n', 'h', 'g' ),
		'n' => array( 'b', 'm', 'j', 'h' ),
		'm' => array( 'n', 'k', 'j' )
	);

	/** @var array @conf_skip
	* Russain typos array
	*/
	public $_russian_keyboard = array(
/*
		// top row
		'1' => array( '2', 'q', 'й' ),
		'2' => array( '1', 'q', 'w', 'й', 'ц', '3' ),
		'3' => array( '2', 'w', 'e', 'ц', 'у', '4' ),
		'4' => array( '3', 'e', 'r', 'у', 'к', '5' ),
		'5' => array( '4', 'r', 't', 'к', 'е', '6' ),
		'6' => array( '5', 't', 'y', 'е', 'н', '7' ),
		'7' => array( '6', 'y', 'u', 'н', 'г', '8' ),
		'8' => array( '7', 'u', 'i', 'г', 'ш', '9' ),
		'9' => array( '8', 'i', 'o', 'ш', 'щ', '0' ),
		'0' => array( '9', 'o', 'p', 'щ', 'з', '-' ),
		'-' => array( '0', 'p', 'з', 'х' ),
*/
		// 2nd from top row
		'й' => array( '1', '2', 'ц', 'ф', 'ы', 'w', 'a' ),
		'ц' => array( '2', '3', 'й', 'ф', 'ы', 'в', 'у', 'q', 'a', 's', 'e' ),
		'у' => array( '3', '4', 'ц', 'ы', 'в', 'а', 'к', 'w', 's', 'd', 'r' ),
		'к' => array( '4', '5', 'у', 'в', 'а', 'п', 'е', 'e', 'd', 'f', 't' ),
		'е' => array( '5', '6', 'к', 'а', 'п', 'р', 'н', 'r', 'f', 'g', 'y' ),
		'н' => array( '6', '7', 'е', 'п', 'р', 'о', 'г', 't', 'g', 'h', 'u' ),
		'г' => array( '7', '8', 'н', 'р', 'о', 'л', 'ш', 'y', 'h', 'j', 'i' ),
		'ш' => array( '8', '9', 'г', 'о', 'л', 'д', 'щ', 'u', 'j', 'k', 'o' ),
		'щ' => array( '9', '0', 'ш', 'л', 'д', 'ж', 'з', 'i', 'k', 'l', 'p' ),
		'з' => array( '0', '-', 'щ', 'д', 'ж', 'э', 'х', 'o', 'l' ),
		'х' => array( '-', 'з', 'ж', 'э', 'ъ' ),
		'ъ' => array( 'х', 'э' ),
		// home row
		'ф' => array( 'й', 'ц', 'я', 'ы', 'z', 's', 'w', 'q' ),
		'ы' => array( 'ц', 'у', 'ф', 'я', 'ч', 'в', 'a', 'z', 'x', 'd', 'e', 'w' ),
		'в' => array( 'у', 'к', 'ы', 'ч', 'с', 'а', 's', 'x', 'c', 'f', 'r', 'e' ),
		'а' => array( 'к', 'е', 'в', 'с', 'м', 'п', 'd', 'c', 'v', 'g', 't', 'r' ),
		'п' => array( 'е', 'н', 'а', 'м', 'и', 'р', 'f', 'v', 'b', 'h', 'y', 't', 'е' ),
		'р' => array( 'н', 'г', 'п', 'и', 'т', 'о', 'g', 'b', 'n', 'j', 'u', 'y' ),
		'о' => array( 'г', 'ш', 'р', 'т', 'ь', 'л', 'h', 'n', 'm', 'k', 'i', 'u' ),
		'л' => array( 'ш', 'щ', 'о', 'ь', 'б', 'д', 'j', 'm', 'l', 'o', 'i' ),
		'д' => array( 'щ', 'з', 'л', 'б', 'ю', 'ж', 'k', 'p', 'o' ),
		'ж' => array( 'з', 'х', 'д', 'ю', 'э', 'l' ),
		'э' => array( 'х', 'ъ', 'ж' ),
		// bottom row
		'я' => array( 'ф', 'ы', 'ч', 'x', 's', 'a' ),
		'ч' => array( 'я', 'ы', 'в', 'с', 'z', 'c', 'd', 's' ),
		'с' => array( 'ч', 'в', 'а', 'м', 'x', 'v', 'f', 'd' ),
		'м' => array( 'с', 'а', 'п', 'и', 'c', 'b', 'g', 'f' ),
		'и' => array( 'м', 'п', 'р', 'т', 'v', 'n', 'h', 'g' ),
		'т' => array( 'и', 'р', 'о', 'ь', 'b', 'm', 'j', 'h' ),
		'ь' => array( 'т', 'о', 'л', 'б', 'n', 'k', 'j' ),
		'б' => array( 'ь', 'л', 'д', 'ю', 'm', 'k', 'l' ),
		'ю' => array( 'б', 'д', 'ж', 'l' ),
	);
	/** @var string */
	public $DEF_STPL_NAME	= "system/typos";
	/** @var int */
	public $DEF_TYPE		= 0;
	/** @var int */
	public $DEF_LIMIT		= 20;
	/** @var int */
	public $USE_RUSSIAN	= true;

	/**
	* Framework constructor
	*/
	function _init () {
		if ($this->USE_RUSSIAN && !extension_loaded("mbstring")) {
			$this->USE_RUSSIAN = false;
		}
		// Overwrite keywords with russian ones
		if ($this->USE_RUSSIAN) {
			$this->keyboard = $this->_russian_keyboard;
			mb_internal_encoding("UTF-8");
		}
	}
	
	/**
	* Process text using params
	*/
	function process($text = "", $params = array()) {
		if (preg_match("/[а-я]/iu", " ", $new_text) && !$this->USE_RUSSIAN) {
			return $text;
		}
		// Default params here
		if (!isset($params["replace_percent"]) && !isset($params["num_replaces"])) {
			$params["replace_percent"] = 0.2;
		}
		// By default we use all typos
		if (!isset($params["typos_all"]) && !isset($params["typos_wrong_key"]) && !isset($params["typos_missed_char"]) && !isset($params["typos_trans_char"]) && !isset($params["typos_double_char"]) && !isset($params["typos_cut_spaces"]) && !isset($params["typos_cut_words"])) {
			$params["typos_all"] = 1;
		}
		// Use all typos if needed
		if ($params["typos_all"]) {
			$params["typos_wrong_key"]	= 1;
			$params["typos_missed_char"]= 1;
			$params["typos_trans_char"]	= 1;
			$params["typos_double_char"]= 1;
			$params["typos_cut_spaces"]	= 1;
			$params["typos_cut_words"]	= 1;
		}
		// Get usable words from text
		$new_text = trim(preg_replace("/\s+/i", " ", preg_replace("/[^a-z0-9а-я]/iu", " ", trim($text))));
		$words = array();
		foreach (explode(" ", $new_text) as $word) {
			if ($this->_strlen($word) <= 4) {
				continue;
			}
			$words[$word] = $word;
// TODO: implement independent replacement of the words with several occurencies
			$_words_count[$word]++;
		}
		$words_orig = $words;

		$NUM_REPLACES = $params["replace_percent"] ? ceil($params["replace_percent"] * count($words)) : $params["num_replaces"];
		// Reduce randomly words to replace
		// DO NOT USE shuffle here!!!
		uasort($words, create_function('$a,$b', 'return rand(-1,1);'));
		$words = array_slice($words, 0, $NUM_REPLACES, true);
		// Process selected words
		foreach ((array)$words as $word) {
			$typos = $this->_get_all_typos($word, $params);
			if (!$typos) {
				continue;
			}
			$words[$word] = $typos[array_rand($typos)];
		}
		// Cut some spaces
		if ($params["typos_cut_spaces"]) {
			$words2 = $words_orig;
			uasort($words2, create_function('$a,$b', 'return rand(-1,1);'));
			$words2 = array_slice($words2, 0, ceil($NUM_REPLACES / 2), true);
			foreach ((array)$words2 as $word) {
				$words[$word." "] = $word;
			}
		}
		// Cut some words
		if ($params["typos_cut_words"]) {
			$words2 = $words_orig;
			uasort($words2, create_function('$a,$b', 'return rand(-1,1);'));
			$words2 = array_slice($words2, 0, ceil($NUM_REPLACES / 2), true);
			foreach ((array)$words2 as $word) {
				$words[$word] = "";
			}
		}
		// Now we allow URLs in text
		if (preg_match_all('/((http|https|ftp|ftps):\/\/[a-z0-9%&\?_\-\=\.\/]|<(a|img) [^>]+>)+/ims', $text, $m)) {
			// Do extract URLs from text
			foreach ((array)$m[0] as $_cur_url) {
				$cur_pair_key = "%%".(++$_cur_number)."%%";
				$url_pairs[$_cur_url]			= " ".$cur_pair_key." ";
				$reverted_pairs[$cur_pair_key]	= " ".$_cur_url." ";
			}
			krsort($url_pairs);
			krsort($reverted_pairs);
		}
		// Replace urls with temporary placeholders
		if (!empty($url_pairs)) {
			$text = str_replace(array_keys($url_pairs), array_values($url_pairs), $text);
		}
		// Process text
		$text = strtr($text, $words);
		// Retuen emails on their placeholders
		if (!empty($reverted_pairs)) {
			$text = str_replace(array_keys($reverted_pairs), array_values($reverted_pairs), $text);
		}
		return $text;
	}
	
	/**
	* Get all common typos
	*
	* @access	public
	* @param	string
	* @return	array
	*/
	function _get_all_typos($word = "", $params = array()) {
		$typos = array();

		if ($params["typos_wrong_key"]) {
			$typos = array_merge($typos, (array)$this->_get_wrong_key_typos($word));
		}
		if ($params["typos_missed_char"]) {
			$typos = array_merge($typos, (array)$this->_get_missed_char_typos($word));
		}
		if ($params["typos_trans_char"]) {
			$typos = array_merge($typos, (array)$this->_get_transposed_char_typos($word));
		}
		if ($params["typos_double_char"]) {
			$typos = array_merge($typos, (array)$this->_get_double_char_typos($word));
		}
		return $typos;
	}

	/**
	* returns array of likely single "wrong key" typos
	* arrays contain only characters that are valid domain names
	*/
	function _get_wrong_key_typos ($word = "") {
		$typos	= array();
		$chars	= $this->_str_split($word);
		$length = count($chars);
		for ($i = 0; $i < $length; $i++) {
			$_cur_char = $chars[$i];
			// if character has replacements then create all replacements
			$_replace_chars = $this->keyboard[$this->_strtolower($_cur_char)];
			if (!$_replace_chars) {
				continue;
			}
			// temp word for manipulating
			$_tmp_chars = $chars;
			foreach ((array)$_replace_chars as $_char) {
				$_tmp_chars[$i] = $_char;

				$_new_word = implode("", (array)$_tmp_chars);
				if ($_new_word != $word) { 
					array_push($typos, $_new_word);
				}
			}
		}
		return $typos;
	}

	/**
	* returns array of likely single missed character typos
	* arrays contain only characters that are valid domain names
	*/
	function _get_missed_char_typos ($word = "") {
		$typos	= array();
		$chars	= $this->_str_split($word);
		$length = count($chars);
		// check each character
		for ($i = 0; $i < $length; $i++) {
			$_tmp_chars = array();
			if ($i == 0) {
				// at first character
				$_tmp_chars = array_slice($chars, ($i + 1));
			} elseif (($i + 1) == $length) {
				// at last character
				$_tmp_chars = array_slice($chars, 0, $i);
			} else {
				// in between
				$_tmp_chars = array_slice($chars, 0, $i);
				$_tmp_chars = array_merge($_tmp_chars, array_slice($chars, ($i + 1)));
			}

			$_new_word = implode("", (array)$_tmp_chars);
			if ($_new_word != $word) {
				array_push($typos, $_new_word);
			}
		}
		return $typos;
	}

	/**
	* returns array of likely transposed character typos
	* arrays contain only characters that are valid domain names
	*/
	function _get_transposed_char_typos ($word = "") {
		$typos	= array();
		$chars	= $this->_str_split($word);
		$length = count($chars);
		// check each character
		for ($i = 0; $i < $length; $i++) {
			if (($i + 1) == $length) {
				// can have simplified the test by throwing it in 
				// the for loop but I didn't to keep it readable
				// at the end no transposition
			} else {
				$_tmp_chars			= $chars;
				$_tmp				= $_tmp_chars[$i];
				$_tmp_chars[$i]		= $_tmp_chars[$i + 1];
				$_tmp_chars[$i + 1] = $_tmp;

				$_new_word = implode("", (array)$_tmp_chars);
				if ($_new_word != $word) {
					array_push($typos, $_new_word);
				}
			}
		}
		return $typos;
	}

	/**
	* returns array of likely double entered character typos
	* arrays contain only characters that are valid domain names
	*/
	function _get_double_char_typos ($word = "") {
		$typos	= array();
		$chars	= $this->_str_split($word);
		$length = count($chars);
		// check each character
		for ($i = 0; $i < $length; $i++) {
			// get first part of word
			$_tmp_chars = array_slice($chars, 0, ($i + 1));
			// add a character
			$_tmp_chars[] = $chars[$i];
			// add last part of strin if there is any 
			if ($i == ($length - 1)) {
				// do nothing we are at the end
			} else {
				// add the end part of the string
				$_tmp_chars = array_merge($_tmp_chars, array_slice($chars, ($i + 1)));
			}

			$_new_word = implode("", (array)$_tmp_chars);
			if ($_new_word != $word) {
				array_push($typos, $_new_word);
			}
		}
		return $typos;
	}
	
	/**
	* Compatibility method
	*/
	function _strlen($text = "") {
		if ($this->USE_RUSSIAN) {
			return mb_strlen($text);
		} else {
			return strlen($text);
		}
	}
	
	/**
	* Compatibility method
	*/
	function _strtolower($text = "") {
		if ($this->USE_RUSSIAN) {
			return mb_strtolower($text);
		} else {
			return strtolower($text);
		}
	}
	
	/**
	* Compatibility method
	*/
	function _substr($text = "", $start = null, $length = null) {
		if ($this->USE_RUSSIAN) {
			return mb_substr($text, $start, $length);
		} else {
			return substr($text, $start, $length);
		}
	}

	/**
	* Split string into separate chars
	*/
	function _str_split ($string = "") {
		if ($this->USE_RUSSIAN) {
			$strlen = mb_strlen($string);
			while ($strlen) {
				$array[]= mb_substr($string, 0, 1);
				$string	= mb_substr($string, 1, $strlen);
				$strlen	= mb_strlen($string);
			}
			return $array;
		} else {
			return str_split($string);
		}
	}
}
