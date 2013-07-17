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
		if (empty($this->_CUR_EDITOR) || !in_array($this->_CUR_EDITOR, $this->_avail_editors)) {
			return false;
		}
		$this->EDITOR_EXISTS = _class("editor_".$this->_CUR_EDITOR, "classes/text_editors/")->_check_if_editor_exists();
	}

	/**
	* Display editor code
	*
	* @access	public
	* @return	string
	*/
	function _display_code ($text_to_edit = "", $force_text_name = "", $style = "") {
		if (empty($this->_CUR_EDITOR) || !in_array($this->_CUR_EDITOR, $this->_avail_editors)) {
			return false;
		}
		$obj = _class("editor_".$this->_CUR_EDITOR, "classes/text_editors/");
		if (!is_object($obj)) {
			return false;
		}
		$obj->text_field_name = $force_text_name ? $force_text_name : $this->TEXT_FIELD_NAME;
		if ($style == "bbcode") {
			$obj->_set_config($this->_editor_params);
		}
		return $obj->_create_code($text_to_edit);
	}
}
