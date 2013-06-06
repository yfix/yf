<?php

/**
* HTML content editor (using HTMLArea3 Editor)
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_editor_htmlarea3 {

	/** @var string */
	var $text_field_name	= "text_to_edit";

	/**
	* Helper method
	*/
	function _set_config ($new_config = array()) {
// TODO
/*
		if (empty($new_config)) {
			return false;
		}
		$this->Config = array_merge((array)$this->Config, (array)$new_config);
*/
	}

	/**
	* Do create editor code
	*/
	function _create_code($text_to_edit = "") {
// TODO
		$text_to_edit = _prepare_html($text_to_edit, 0);
		$form_action = "";
		$PATH_TO_FILES = WEB_PATH."htmlarea3/";

		$html = '
			<script type="text/javascript">
			  _editor_url = "'.$PATH_TO_FILES.'";
			  _editor_lang = "en";
			</script>
			<script type="text/javascript" src="'.$PATH_TO_FILES.'htmlarea.js"></script>
			<script type="text/javascript">
			  HTMLArea.loadPlugin("ContextMenu");
			  HTMLArea.onload = function() {
			    var editor = new HTMLArea("my_editor");
			    editor.registerPlugin(ContextMenu);
			    editor.generate();
			  };
			  HTMLArea.init();
			</script>
			<textarea id="my_editor" name="'.$this->text_field_name.'" rows="20" cols="80" style="width: 100%">'.$text_to_edit.'</textarea>
		';

		return $html;
	}

	/**
	* Do create editor code
	*/
	function _check_if_editor_exists() {
		$fs_path = INCLUDE_PATH."htmlarea3/htmlarea.js";
		return file_exists($fs_path) && filesize($fs_path) > 2 ? 1 : 0;
	}
}
