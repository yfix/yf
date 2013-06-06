<?php

/**
* Dynamic replacement custom HTML content (page title, meta keywords, meta description)
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_custom_meta_info {

	/** @var array @conf_skip Replace tags */
	var $_tags				= array();
	/** @var array @conf_skip Current rules to process */
	var $_rules				= array();
	/** @var bool */
	var $AUTO_ADD_QUOTES	= true;

	/**
	* Constructor (PHP 4.x)
	*
	* @access	public
	* @return	void
	*/
	function yf_custom_meta_info () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	*
	* @access	public
	* @return	void
	*/
	function __construct () {
		// Get available replacement tags
		$this->_tags	= main()->get_data("custom_replace_tags");
		// Get available replacement words
		$this->_words	= main()->get_data("custom_replace_words");
		// Do sort them by key length desc
		if (!empty($this->_words)) {
			uksort($this->_words, array(&$this, "_sort_by_key_length_desc"));
		}
		// Get rules from db
		$all_rules		= main()->get_data("custom_replace_rules");
		// Filter rules that not match current query params
		foreach ((array)$all_rules as $rule_id => $rule_info) {
			// Check for correct tag_id
			if (!isset($this->_tags[$rule_info["tag_id"]])) {
				continue;
			}
			// Check for site_id (0 => means for all)
			if ($rule_info["site_id"] != 0 && $rule_info["site_id"] != SITE_ID) {
				continue;
			}
			// Check for language (0 => means for all)
			if ($rule_info["language"] != "" && $rule_info["language"] != conf('language')) {
				continue;
			}
			// Skip not matched "methods" ("" => means for all)
			if ($rule_info["methods"] != "" && $rule_info["methods"] != $_GET["object"]) {
				// Check if we have multiple methods here
				if (false !== strpos($rule_info["methods"], ",")) {
					if (!in_array($_GET["object"].".".$_GET["action"], explode(",", $rule_info["methods"]))) {
						continue;
					}
				// Single method check
				} elseif ($rule_info["methods"] != $_GET["object"].".".$_GET["action"]) {
					continue;
				}
			}
			// Skip not matched "query_string" ("" => means for all)
			if ($rule_info["query_string"] != "" 
				&& !@preg_match("/".$rule_info["query_string"]."/ims", $_SERVER["QUERY_STRING"])
			) {
				continue;
			}
			// Store sule if all checks passed successfully
			$this->_rules[$rule_id] = array(
				"tag_id"		=> $rule_info["tag_id"],
				"tag_replace"	=> $rule_info["tag_replace"],
				"eval_code"		=> $rule_info["eval_code"],
			);
		}
	}

	/**
	* Do process custom tags
	*/
	function _process ($body) {
		// Stop here if no rules defined
		if (empty($this->_rules)) {
			return $body;
		}
		if (DEBUG_MODE) {
			$this->_time_start = microtime(true);
		}
		// Process them
		foreach ((array)$this->_rules as $rule_id => $rule_info) {
			if (DEBUG_MODE) {
				$old_replace = $rule_info["tag_replace"];
			}
			// Replace all known words
			if (!empty($this->_words)) {
				$rule_info["tag_replace"] = str_replace(array_keys($this->_words), array_values($this->_words), $rule_info["tag_replace"]);
			}
			// Check single string with spaces
			if (preg_match("/^[a-z0-9\s\-\_]+$/ims", $rule_info["tag_replace"])) {
				$rule_info["tag_replace"] = "'".$rule_info["tag_replace"]."'";
			}
			// Check if replacement need to be eval'ed
			if ($rule_info["eval_code"]) {
				$eval_what = trim($rule_info["tag_replace"]);
				if ($this->AUTO_ADD_QUOTES && $eval_what{0} != "\"" && $eval_what{0} != "'" && $eval_what{0} != "\$" && substr($eval_what, -1) != "\"" && substr($eval_what, -1) != "'") {
					$eval_what = "\"".$eval_what."\"";
				}
				$cur_replace = eval("return ".$eval_what.";");
			} else {
				$cur_replace = $rule_info["tag_replace"];
			}
			// Fill patterns with replacements
			$patterns[$rule_id]		= $this->_tags[$rule_info["tag_id"]]["pattern_find"]."ims";
			// Fix for the replacements starting with number that can cause wrong results with sub-patterns replaces
			if (is_numeric($cur_replace{0})) {
				$cur_replace = " ".$cur_replace;
			}
			$replacements[$rule_id]	= str_replace("%%TAG%%", $cur_replace, $this->_tags[$rule_info["tag_id"]]["pattern_replace"]);
			// Store debug info
			if (DEBUG_MODE) {
				$GLOBALS['CUSTOM_REPLACED_DEBUG'][] = array(
					"tag_id"		=> $rule_info["tag_id"],
					"pattern"		=> $patterns[$rule_id],
					"replace_first"	=> $old_replace,
					"replace_words"	=> $rule_info["tag_replace"],
					"replace_evaled"=> $cur_replace,
					"replace_last"	=> $replacements[$rule_id],
				);
			}
		}
		if (!empty($patterns)) {
			$body = preg_replace($patterns, $replacements, $body);
		}
		if (DEBUG_MODE) {
			$GLOBALS['custom_replace_exec_time'] = (float)microtime(true) - (float)$this->_time_start;
		}
		return $body;
	}

	/**
	* Sorting array by key length in desc order
	*/
	function _sort_by_key_length_desc ($a, $b) {
		if (strlen($a) == strlen($b)) {
			return 0;
		}
		return (strlen($a) > strlen($b)) ? -1 : 1;
	}
}
