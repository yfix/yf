<?php

/**
* Typo Generator Class
* 
* @example
* 
* $typoEngine = new yf_graphics_typos();
* $typos = $typoEngine->get_wrong_key_typos("Hello");
* print_r($typos);
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_graphics_typos {

	/** @var array @conf_skip
	* array of keys near character on a QWERTY keyboard
	* only valid characters in a domain name
	*/
	public $keyboard = array(
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
	/** @var string */
	public $DEF_STPL_NAME	= "system/typos";
	/** @var int */
	public $DEF_TYPE		= 0;
	/** @var int */
	public $DEF_LIMIT		= 20;
	
	/**
	* Get all typos with params processed through STPL
	*
	* @access	public
	* @param	string
	* @return	array
	*/
	function get_all_with_stpl($word = "", $params = array()) {
		if (empty($word)) {
			return false;
		}
		// Prepare params
		$STPL_NAME	= $params["stpl"] ? $params["stpl"] : $this->DEF_STPL_NAME;
		$TYPE		= $params["type"] ? $params["type"] : $this->DEF_TYPE;
		$LIMIT		= $params["limit"] ? $params["limit"] : $this->DEF_LIMIT;
		// Process typos
		$typos = array();
		if (!$TYPE || $TYPE == 1) {
			$typos = array_merge($typos, $this->get_wrong_key_typos($word));
		}
		if (!$TYPE || $TYPE == 2) {
			$typos = array_merge($typos, $this->get_missed_char_typos($word));
		}
		if (!$TYPE || $TYPE == 3) {
			$typos = array_merge($typos, $this->get_transposed_char_typos($word));
		}
		if (!$TYPE || $TYPE == 4) {
			$typos = array_merge($typos, $this->get_double_char_typos($word));
		}
		// Shake them all
		shuffle($typos);
		// Cutoff list
		if ($LIMIT && count($typos) > $LIMIT) {
			$typos = array_slice($typos, 0, $LIMIT);
		}
		// Prepare them for STPL
		$typos_for_stpl = array();
		foreach ((array)$typos as $_cur_text) {
			$typos_for_stpl[] = array("text" => $_cur_text);
		}
		// Prepare template
		$replace = array(
			"typos"	=> $typos_for_stpl,
		);
		return tpl()->parse($STPL_NAME, $replace);
	}
	
	/**
	* Get all typos
	*
	* @access	public
	* @param	string
	* @return	array
	*/
	function get_all($word) {
		$typos = array();

		$typos = array_merge($typos, $this->get_wrong_key_typos($word));
		$typos = array_merge($typos, $this->get_missed_char_typos($word));
		$typos = array_merge($typos, $this->get_transposed_char_typos($word));
		$typos = array_merge($typos, $this->get_double_char_typos($word));

		return $typos;
	}

	/**
	* returns array of likely single "wrong key" typos
	* arrays contain only characters that are valid domain names
	*
	* @access	public
	* @param	string
	* @return	array
	*/
	function get_wrong_key_typos ($word) {
		$word	= strtolower( $word );
		$typos	= array();
		$length = strlen($word);
		// check each character
		for ($i = 0; $i < $length; $i++) {
			// if character has replacements then create all replacements
			if($this->keyboard[$word{$i}]) {
				// temp word for manipulating
				$tempWord = $word;
				foreach ((array)$this->keyboard[$word{$i}] as $char) {
					$tempWord{$i} = $char;			
					if ($tempWord != $word) { 
						array_push($typos, $tempWord);
					}
				}
			}
		}
		return $typos;
	}

	/**
	* returns array of likely single missed character typos
	* arrays contain only characters that are valid domain names
	*
	* @access	public
	* @param	string
	* @return	array
	*/
	function get_missed_char_typos ($word) {
		$word	= strtolower($word);
		$typos	= array();
		$length = strlen($word);
		// check each character
		for ($i = 0; $i < $length; $i++) {
			$tempWord = '';
			if ($i == 0) {
				// at first character
				$tempWord = substr($word, ($i + 1));
			} else if (($i + 1) == $length) {
				// at last character
				$tempWord = substr($word, 0, $i) ;
			} else {
				// in between
				$tempWord = substr($word, 0, $i) ;
				$tempWord .= substr($word, ($i + 1)) ;
			}
			if ($tempWord != $word) { 
				array_push($typos, $tempWord);
			}
		}
		return $typos;
	}

	/**
	* returns array of likely transposed character typos
	* arrays contain only characters that are valid domain names
	*
	* @access	public
	* @param	string
	* @return	array
	*/
	function get_transposed_char_typos ($word) {
		$word	= strtolower($word);
		$typos	= array();
		$length	= strlen($word);
		// check each character
		for ($i = 0; $i < $length; $i++) {
			if (($i + 1) == $length) {
				// can have simplified the test by throwing it in 
				// the for loop but I didn't to keep it readable
				// at the end no transposition
			} else {
				$tempWord = $word;
				$tempChar = $tempWord{$i};			
				$tempWord{$i} = $tempWord{($i + 1)};
				$tempWord{($i + 1)} = $tempChar;			
				if ($tempWord != $word) { 
					array_push($typos, $tempWord);
				}
			}
		}
		return $typos;
	}

	/**
	* returns array of likely double entered character typos
	* arrays contain only characters that are valid domain names
	*
	* @access	public
	* @param	string
	* @return	array
	*/
	function get_double_char_typos ($word) {
		$word	= strtolower($word);
		$typos	= array();
		$length	= strlen($word);
		// check each character
		for ($i = 0; $i < $length; $i++) {
			// get first part of word
			$tempWord = substr($word, 0, ($i + 1));
			// add a character
			$tempWord .= $word{$i};
			// add last part of strin if there is any 
			if ($i == ($length - 1)) {
				// do nothing we are at the end
			} else {
				// add the end part of the string
				$tempWord .= substr($word, ($i+1));
			}
			if ($tempWord != $word) { 
				array_push($typos, $tempWord);
			}
		}
		return $typos;
	}
}
