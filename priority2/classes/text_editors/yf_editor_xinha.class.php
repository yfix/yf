<?php

/**
* HTML content editor (using Xinha Editor)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_editor_xinha {

	/** @var string */
	public $text_field_name	= "text_to_edit";

	/**
	* Helper method
	* 
	* @access	public
	* @param	array
	* @return	void
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
		$text_to_edit = _prepare_html($text_to_edit, 0);
		$form_action = "";
		$PATH_TO_FILES = WEB_PATH."xinha/";

		$html = '
			<script type="text/javascript">
			var _editor_url  = "'.$PATH_TO_FILES.'";
			var _editor_lang = "en";
			</script>
			<!-- Load up the actual editor core -->
			<script type="text/javascript" src="'.$PATH_TO_FILES.'htmlarea.js"></script>
			<script type="text/javascript">
			var xinha_plugins =
			[
			 "CharacterMap",
			 "ContextMenu",
			 "FullScreen",
			 "ListType",
//			 "SpellChecker",
			 "Stylist",
			 "SuperClean"/*,
			 "TableOperations"*/
			];
			var xinha_editors =
			[
			  "my_editor"
			];
			
			function xinha_init()
			{
			  // THIS BIT OF JAVASCRIPT LOADS THE PLUGINS, NO TOUCHING  :)
			  if(!HTMLArea.loadPlugins(xinha_plugins, xinha_init)) return;
			  var xinha_config = new HTMLArea.Config();
			  xinha_editors = HTMLArea.makeEditors(xinha_editors, xinha_config, xinha_plugins);
			  xinha_editors.my_editor.config.statusBar = false;
			  HTMLArea.startEditors(xinha_editors);
			}
			window.onload = xinha_init;
			</script>
			<style type="text/css">@import url("'.$PATH_TO_FILES.'skins/blue-look/skin.css")</style>

			<textarea id="my_editor" name="'.$this->text_field_name.'" rows="10" cols="80" style="width:100%">'.$text_to_edit.'</textarea>
		';

		return $html;
	}

	/**
	* Do create editor code
	*/
	function _check_if_editor_exists() {
		$fs_path = INCLUDE_PATH."xinha/htmlarea.js";
		return file_exists($fs_path) && filesize($fs_path) > 2 ? 1 : 0;
	}
}
