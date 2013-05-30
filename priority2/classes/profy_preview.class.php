<?php

/**
* Class to handle output caching
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_preview {

	/** @var array These variables will not be displayed in preview */
	var $skip_fields = array(
		"mode",
		"f_family_text",
		"f_size_text",
		"f_color_text",
		"helpbox_text",
		"tag_count_text",

		"parent_object",
		"parent_action",
		"form_action",
	);
	/** @var bool */
	var $USE_BB_CODES = true;

	/**
	* Display preview method
	*
	* NOTE : parent_object & parent_action are used to determine
	*/
	function _display_preview($params = array(), $template = "") {
		$replace = $params['replace'];
		$PARENT_OBJECT = $_REQUEST["parent_object"];
		$PARENT_ACTION = $_REQUEST["parent_action"];
		// If no custom replace given, try to make own
		if (empty($replace)) {
			foreach ((array)$_POST as $k => $v) {
				if (in_array($v, $this->skip_fields)) {
					continue;
				}
				if ($k != 'category_id') {
					$replace[$k] = $this->_format_text($v);
				} else {
					// Try to get category_id based on parent object
					$categories = cache()->get($PARENT_OBJECT."_categories");
					$replace['category_id'] = $categories[$v];
				}
			}
		}
		// Try to get template
		if (false !== strpos($_POST['preview_form_action'], "add_comment")) {
			$body = tpl()->parse("comments/preview", $replace);
		} else {
			$stpl_name = $PARENT_OBJECT."/".$PARENT_ACTION."_preview";
			$body = tpl()->_stpl_exists($stpl_name) ? tpl()->parse($stpl_name, $replace) : "";
		}
		// Default body
		if (empty($body)) {
			$body = tpl()->parse(__CLASS__."/default", $replace);
		}
		// Process template
		$replace2 = array(
			"template" => $body,
		);
		return common()->show_empty_page(tpl()->parse("preview/main", $replace2), array("title" => t("Preview")));
	}

	/**
	* Display buttons code
	*/
	function _display_buttons($params = array()/*, $template = ""*/) {
		$replace = array(
			"preview_link"	=> "./?object=".$_GET["object"]."&action=display_preview",
			"width"			=> !empty($params['width'])	? intval($params['width'])	: 400,
			"height"		=> !empty($params['height'])? ($params['height'])		: 300,
			"default_action"=> "",
			"parent_object"	=> $_GET["object"],
			"parent_action"	=> $_GET["action"],
		);
		return tpl()->parse(__CLASS__."/buttons", $replace);
	}

	/**
	* Format given text (convert BB Codes, new lines etc)
	*
	* @access	private
	* @return	string
	*/
	function _format_text ($body = "") {
		// Stop here if text is empty
		if (empty($body)) {
			return "";
		}
/*
		if ($this->FORCE_STRIPSLASHES) {
			$body = stripslashes($body);
		}
*/
		// If special code is "on" - process it
		if ($this->USE_BB_CODES) {
			$BB_CODES_OBJ = main()->init_class("bb_codes", "classes/");
		}
		// We cannot die, need to be safe
		if ($this->USE_BB_CODES && is_object($BB_CODES_OBJ)) {
			$body = $BB_CODES_OBJ->_process_text($body);
		} else {
			$body = nl2br(_prepare_html($body, 0));
		}
		return $body;
	}
}
