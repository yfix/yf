<?php

/**
* HTML content editor (using TinyMCE Editor)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_editor_tinymce {

	/** @var string */
	var $text_field_name	= "text_to_edit";

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
		$PATH_TO_FILES = WEB_PATH."tinymce/";

		$html = '
			<!-- TinyMCE -->
			<script language="javascript" type="text/javascript" src="'.$PATH_TO_FILES.'tiny_mce.js"></script>
			<script language="javascript" type="text/javascript">
				tinyMCE.init({
					mode : "textareas",
					theme : "advanced",
					plugins : "style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,flash,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable",
					theme_advanced_buttons1_add_before : "save,newdocument,separator",
					theme_advanced_buttons1_add : "fontselect,fontsizeselect",
					theme_advanced_buttons2_add : "separator,insertdate,inserttime,preview,separator,forecolor,backcolor",
					theme_advanced_buttons2_add_before: "cut,copy,paste,pastetext,pasteword,separator,search,replace,separator",
					theme_advanced_buttons3_add_before : "tablecontrols,separator",
					theme_advanced_buttons3_add : "emotions,iespell,flash,advhr,separator,print,separator,ltr,rtl,separator,fullscreen",
//					theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops",
					theme_advanced_toolbar_location : "top",
					theme_advanced_toolbar_align : "left",
					theme_advanced_path_location : "bottom",
//					content_css : "example_full.css",
//				    plugin_insertdate_dateFormat : "%Y-%m-%d",
//				    plugin_insertdate_timeFormat : "%H:%M:%S",
					extended_valid_elements : "hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]",
//					external_link_list_url : "example_link_list.js",
//					external_image_list_url : "example_image_list.js",
//					flash_external_list_url : "example_flash_list.js",
//					file_browser_callback : "fileBrowserCallBack",
					theme_advanced_resize_horizontal : false,
					theme_advanced_resizing : true
				});
			</script>
			<!-- /TinyMCE -->

			<textarea id="elm1" name="'.$this->text_field_name.'" rows="15" cols="80" style="width: 100%">"'.$text_to_edit.'"</textarea>
		';
		return $html;
	}

	/**
	* Do create editor code
	*/
	function _check_if_editor_exists() {
		$fs_path = INCLUDE_PATH."tinymce/tiny_mce.js";
		return file_exists($fs_path) && filesize($fs_path) > 2 ? 1 : 0;
	}
}
