<?php

/**
* Text editor abstract class
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_text_editor {

	/** @var array */
	public $_avail_editors		= array(
		"fckeditor",
		"tinymce",
		"htmlarea3",
		"xinha",
//		"tinyfck",
//		"tinymcpuk",
	);
	/** @var string */
	public $_CUR_EDITOR		= "fckeditor";
//	public $_CUR_EDITOR		= "tinymce";
//	public $_CUR_EDITOR		= "xinha";
//	public $_CUR_EDITOR		= "htmlarea3";
	/** @var array @conf_skip */
	public $_editor_params		= array(
		"CustomConfigurationsPath"	=> "editor/plugins/bbcode/_sample/sample.config.js",
	);
	/** @var string Default text field name where stored editable text (usually for <textarea>) */
	public $TEXT_FIELD_NAME	= "text_to_edit";
	/** @var bool Editor really exists in current project or not */
	public $EDITOR_EXISTS		= false;

	/**
	* Framework contructor
	*/
	function _init () {
		// Quick check
		if (empty($this->_CUR_EDITOR) || !in_array($this->_CUR_EDITOR, $this->_avail_editors)) {
			return false;
		}
		// Try to load selected editor (if exists one)
		$SUB_EDITOR_OBJ = main()->init_class("editor_".$this->_CUR_EDITOR, "classes/text_editors/");
		if (!is_object($SUB_EDITOR_OBJ)) {
			return false;
		}
		$this->EDITOR_EXISTS = $SUB_EDITOR_OBJ->_check_if_editor_exists();
	}

	/**
	* Display editor code
	*
	* @access	public
	* @return	string
	*/
	function _display_code ($text_to_edit = "", $force_text_name = "", $style = "") {
		// Quick check
		if (empty($this->_CUR_EDITOR) || !in_array($this->_CUR_EDITOR, $this->_avail_editors)) {
			return false;
		}
		// Try to load selected editor (if exists one)
		$SUB_EDITOR_OBJ = main()->init_class("editor_".$this->_CUR_EDITOR, "classes/text_editors/");
		if (!is_object($SUB_EDITOR_OBJ)) {
			return false;
		}
		// Set editor's configuration
		$SUB_EDITOR_OBJ->text_field_name = $force_text_name ? $force_text_name : $this->TEXT_FIELD_NAME;
		if($style == "bbcode"){
			$SUB_EDITOR_OBJ->_set_config($this->_editor_params);
		}
		// Display editor's code
		return $SUB_EDITOR_OBJ->_create_code($text_to_edit);
	}
}
