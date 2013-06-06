<?php

/**
* Custom user design handler (for profile, etc)
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_custom_design {

	/** @var int Max background image size (In bytes) */
	var $MAX_BG_IMAGE_SIZE = 512000;

	/**
	* Constructor (PHP 4.x)
	*/
	function yf_custom_design () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	*/
	function __construct () {
		// Init dir module
		$this->DIR = main()->init_class("dir", "classes/");
		// Do get font size and font types
		$this->_font_sizes = main()->get_data("font_size");
		$this->_font_types = main()->get_data("font_type");
	}

	/**
	* Display CSS code for the given item_id and page
	* 
	* @access	public
	* @param	$params	array
	* @return	string
	*/
	function _show_css($params = array()) {
		$profile_style = "";
		// Prepare input params
		$page			= !empty($params["page"]) ? $params["page"] : "user_profile";
		$item_id		= !empty($params["item_id"]) ? intval($params["item_id"]) : 0;
		$photos_path	= !empty($params["photos_path"]) ? $params["photos_path"] : SITE_CUSTOM_DESIGN_DIR;
		if (empty($item_id)) return false;
		// Overwrite css class names
		$css_table_main		= !empty($params["css_table_main"])		? $params["css_table_main"]		: "tbl";
		$css_table_main		= !empty($params["css_table_main"])		? $params["css_table_main"]		: "tbl";
		$css_table_header	= !empty($params["css_table_header"])	? $params["css_table_header"]	: "tbl_h";
		// Do get style from db
		$style_info = db()->query_fetch("SELECT * FROM `".db('custom_style')."` WHERE `item_id`=".intval($item_id)." AND `page`='"._es($page)."' LIMIT 1");
		// Do get path to the user photos
		$path = $this->DIR->_gen_dir_path($item_id, WEB_PATH.$photos_path);
		// Process background
		if ($style_info["background"] != '') {
			$back	= "url(\"".$path.$style_info["background"]."\")";
			$repeat	= $style_info["is_tiledback"] == 1 ? 'repeat' : 'no-repeat top center';
			$scroll	= $style_info["is_scrollback"] == 1 ? 'scroll' : 'fixed';
			$profile_style .= "body { background: ".$scroll." ".$back." ".$repeat."; }";
			if ($style_info["is_transback"] == 1) {
				$profile_style .= ".".$css_table_main.",.".$css_table_header.",.".$css_table_border.",table,tr,td { background: transparent; }";
			}
		} elseif ($style_info["back_color"] != '') {
			$profile_style .= "body { background: ".$style_info["back_color"]."; }";
		}
		// Process table style
		$profile_style	.= $style_info["th_backcolor"] != '' ? ".".$css_table_header." { background: ".$style_info["th_backcolor"]." }" : '';
		$profile_style	.= $style_info["table_backcolor"] != '' ? ".".$css_table_main." { background: ".$style_info["table_backcolor"]." }" : '';
		$profile_style	.= $style_info["table_bordercolor"] != '' ? ".".$css_table_border." { background: ".$style_info["table_bordercolor"]." }" : '';
		$profile_style	.= $style_info["th_style"] != '' ? $this->_decode_style(".".$css_table_header, $style_info["th_style"]) : '';
		// Common styles
		$profile_style	.= $this->_decode_style("body,table,tr,td", $style_info["main_text_style"]);
		$profile_style	.= $this->_decode_style("a:link,a:visited", $style_info["link_style"]);
		$profile_style	.= $this->_decode_style("a:hover", $style_info["link_hover_style"]);
		// Images
		$profile_style	.= $style_info["image_color"] != '' ? "a img{border-color:".$style_info["image_color"]."; border-width:2px;border-style:solid;}" : '';
		// Process filters
		$filter .= $style_info["is_lightimg"] ? ' filter:Alpha(Opacity=60); ' : '';
		$filter .= $style_info["is_fliphorimg"] ? ' filter:fliph; ' : '';
		$filter .= $style_info["is_flipvertimg"] ? ' filter:flipv; ' : '';
		// Style for hyperlinks
		$profile_style	.= "a:hover img{".$filter." border-color:".$style_info["image_hover_color"]."; border-width:2px;border-style:solid;}";
		// Add style HTML tags
		if (!empty($profile_style)) {
			$profile_style = "<style type='text/css'>".$profile_style."</style>\r\n";
		}
		return $profile_style;
	}

	/**
	* Display form to edit style
	* 
	* @access	public
	* @param	array	$params
	* @return	string
	*/
	function _edit_form($params = array()) {
		// Prepare input params
		$page			= !empty($params["page"]) ? $params["page"] : "user_profile";
		$item_id		= !empty($params["item_id"]) ? intval($params["item_id"]) : main()->USER_ID;
		$photos_path	= !empty($params["photos_path"]) ? $params["photos_path"] : SITE_CUSTOM_DESIGN_DIR;
		$template_name	= !empty($params["template_name"]) ? $params["template_name"] : "custom_design/edit_form";
		$form_action	= !empty($params["form_action"]) ? $params["form_action"] : "./?object=".$_GET["object"]."&action=".$_GET["action"];
		$revert_link	= !empty($params["revert_link"]) ? $params["revert_link"] : "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=revert";
		$delete_bg_link	= !empty($params["delete_bg_link"]) ? $params["delete_bg_link"] : "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=delete_background";
		// Do revert design
		if ($_GET["id"] == "revert") {
			$A = db()->query_fetch("SELECT `background` FROM `".db('custom_style')."` WHERE `item_id`=".intval($item_id)." AND `page`='"._es($page)."' LIMIT 1");
			// Delete background image
			if ($A["background"] != '') {
				$path = $this->DIR->_gen_dir_path($item_id, INCLUDE_PATH.$photos_path);
				@unlink($path.$A["background"]);
			}
			db()->query("DELETE FROM `".db('custom_style')."` WHERE `item_id`=".intval($item_id)." AND `page`='"._es($page)."'");
			// Return user back
			return js_redirect("./?object=".$_GET["object"]."&action=".$_GET["action"]);
		}
		// Check if user already have record for cutom_design
		$style_info = db()->query_fetch("SELECT COUNT(`id`) AS `0` FROM `".db('custom_style')."` WHERE `item_id`=".intval($item_id)." AND `page`='"._es($page)."' LIMIT 1");
		if ($style_info[0] == 0) {
			db()->query("INSERT INTO `".db('custom_style')."` (`page`,`item_id`) VALUES ('"._es($page)."',".intval($item_id).")");
		}
		// Do delete background
		if ($_GET["id"] == "delete_background") {
			$style_info = db()->query_fetch("SELECT `background` FROM `".db('custom_style')."` WHERE `item_id`=".intval($item_id)." AND `page`='"._es($page)."' LIMIT 1");
			if ($style_info["background"] != '') {
				$path = $this->DIR->_gen_dir_path($item_id, INCLUDE_PATH.$photos_path);
				@unlink($path.$style_info["background"]);
			}
			db()->query("UPDATE `".db('custom_style')."` SET `background`='' WHERE `item_id` = ".intval($item_id)." AND `page`='"._es($page)."'");
			// Return user back
			return js_redirect("./?object=".$_GET["object"]."&action=".$_GET["action"]);
		}
		// Do upload file
		if (empty($_GET["id"]) && $_FILES['back']['tmp_name'] != "") {
			// Get photo extension
			$ext = strtolower(strrchr($_FILES['back']['name'], '.'));
			if ($ext == ".jpeg") $ext=".jpg";
			// Check file extension
			if ($ext != ".jpg" && $ext != ".gif") {
				common()->_raise_error(t("Incorrect file format for background!"));
			}
			// Check file size
			if ($_FILES['back']['size'] > $this->MAX_BG_IMAGE_SIZE) {
				common()->_raise_error(t("Background file is too big!"));
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// New name for the bg image
				$back = "back".$ext;
				// Prepare path
				$path = $this->DIR->_gen_dir_path($item_id, INCLUDE_PATH.$photos_path, true);
				// Check if background already exists
				$style_info = db()->query_fetch("SELECT `background` FROM `".db('custom_style')."` WHERE `item_id`=".intval($item_id)." AND `page`='"._es($page)."' LIMIT 1");
				if ($style_info["background"] != '') {
					@unlink($path.$style_info["background"]);
				}
				copy($_FILES['back']['tmp_name'], $path.$back);
				db()->query("UPDATE `".db('custom_style')."` SET `background`='$back' WHERE `item_id`=".intval($item_id)." AND `page`='"._es($page)."'");
				$_POST['background'] = $back;
			}
		}
		// Do save data
		if ($_POST['mode'] == 1 && !common()->_error_exists()) {
			db()->UPDATE("custom_style", array(
				"main_text_style"	=> _es($this->_pack_text_style("main_text_style")),
				"link_style"		=> _es($this->_pack_text_style("link_style")),
				"link_hover_style"	=> _es($this->_pack_text_style("link_hover_style")),
				"th_style"			=> _es($this->_pack_text_style("th_style")),
				"back_color"		=> $_POST["back_color"],
				"is_scrollback"		=> intval($_POST["is_scrollback"]),
				"is_tiledback"		=> intval($_POST["is_tiledback"]),
				"is_transback"		=> intval($_POST["is_transback"]),
				"th_backcolor"		=> $_POST["th_backcolor"],
				"table_backcolor"	=> $_POST["table_backcolor"],
				"table_bordercolor"	=> $_POST["table_bordercolor"],
				"image_color"		=> $_POST["image_color"],
				"image_hover_color"	=> $_POST["image_hover_color"],
				"is_lightimg"		=> intval($_POST["is_lightimg"]),
				"is_fliphorimg"		=> intval($_POST["is_fliphorimg"]),
				"is_flipvertimg"	=> intval($_POST["is_flipvertimg"]),
			), " `item_id`=".$item_id." AND `page`='".$page."'");
			// Return user back
			return js_redirect("./?object=".$_GET["object"]."&action=".$_GET["action"]);
		} elseif (!common()->_error_exists()) {
			$style_info = db()->query_fetch("SELECT * FROM `".db('custom_style')."` WHERE `item_id`=".intval($item_id)." AND `page`='"._es($page)."' LIMIT 1");
			$_POST = $style_info;
			$this->_unpack_text_style('main_text_style', $style_info['main_text_style']);
			$this->_unpack_text_style('link_style', $style_info['link_style']);
			$this->_unpack_text_style('link_hover_style', $style_info['link_hover_style']);
			$this->_unpack_text_style('th_style', $style_info['th_style']);
		}
		// Process template
		$replace = array(
			"form_action"			=> $form_action,
			"error_message"			=> _e(),
			"revert_link"			=> $revert_link,
			'link_deleteback'		=> $_POST['background'] != "" ? $delete_bg_link : "",
			'bg_image_link'			=> $_POST['background'] != "" ? $this->DIR->_gen_dir_path($item_id, WEB_PATH.$photos_path).$_POST['background'] : "",
			'back_color'			=> $_POST["back_color"],
			'is_tiledback'			=> $_POST["is_tiledback"] ? 'checked' : '',
			'is_scrollback'			=> $_POST["is_scrollback"] ? 'checked' : '',
			'is_transback'			=> $_POST["is_transback"] ? 'checked' : '',
			'is_lightimg'			=> $_POST["is_lightimg"] ? 'checked' : '',
			'is_fliphorimg'			=> $_POST["is_fliphorimg"] ? 'checked' : '',
			'is_flipvertimg'		=> $_POST["is_flipvertimg"] ? 'checked' : '',
			'image_color'			=> $_POST["image_color"],
			'image_hover_color'		=> $_POST["image_hover_color"],
			'th_backcolor'			=> $_POST["th_backcolor"],
			'table_backcolor'		=> $_POST["table_backcolor"],
			'table_bordercolor'		=> $_POST["table_bordercolor"],
			'main_text_style'		=> $this->_edit_text_style_item('main_text_style'),
			'link_style'			=> $this->_edit_text_style_item('link_style'),
			'link_hover_style'		=> $this->_edit_text_style_item('link_hover_style'),
			'th_style'				=> $this->_edit_text_style_item('th_style'),
			'is_profile_scrollback'	=> $style_info["is_profile_scrollback"] == 1 ? 'checked' : '',
			"max_bg_image_size"		=> common()->format_file_size($this->MAX_BG_IMAGE_SIZE),
		);
		return tpl()->parse($template_name, $replace);
	}

	/**
	* Show one item for editing form
	* 
	* @access	private
	* @param	
	* @return	string
	*/
	function _edit_text_style_item($name) {
		$replace = array(
			"name"			=> $name,
			"value"			=> $_POST[$name."_font_color"],
			"font_size_box"	=> common()->select_box($name."_font_size",	$this->_font_sizes,	$_POST[$name."_font_size"], "-- Default --", 2, "", 0),
			"font_type_box"	=> common()->select_box($name."_font_type",	$this->_font_types,	$_POST[$name."_font_type"], "-- Default --", 2, "", 0),
			"bold"			=> intval((bool)$_POST[$name.'_bold']),
			"underline"		=> intval((bool)$_POST[$name.'_underline']),
			"italic"		=> intval((bool)$_POST[$name.'_italic']),
		);
		return tpl()->parse(__CLASS__."/text_style_item", $replace);
	}

	/**
	* Decode style from inner structure into CSS view
	* 
	* @access	private
	* @param	
	* @return	string
	*/
	function _decode_style ($name, $packed_arr) {
		$arr = unserialize($packed_arr);
		// Prepare font color
		if ($arr["font_color"] != '') { 
			$body.= 'color: '.$arr["font_color"].';';
		}
		// Process font type
		if ($arr["font_type"] != '') {
			$body.= 'font-family: '.$this->_font_types[$arr["font_type"]].';';
		}
		// Process font size
		if ($arr["font_size"] != '') {
			$body.= 'font-size: '.$this->_font_sizes[$arr["font_size"]].'px;';
		}
		// Prepare bold, italic and underline
		if ($arr["bold"] == 1) {
			$body .= "font-weight:bold;";
		}
		if ($arr["italic"] == 1) {
			$body .= "font-style:italic;";
		}
		if ($arr["underline"] == 1) {
			$body .= "text-decoration:underline;";
		}
		// Return result
		return $name. " {". $body. "}";
	}

	/**
	* Pack CSS style into inner structure (serialized array)
	* 
	* @access	private
	* @param	
	* @return	string
	*/
	function _pack_text_style($name) {
		$pack_arr = array(
			'font_color'=> $_POST[$name.'_font_color'],
			'font_size'	=> $_POST[$name.'_font_size'],
			'font_type'	=> $_POST[$name.'_font_type'],
			'bold'		=> $_POST[$name.'_bold'],
			'underline'	=> $_POST[$name.'_underline'],
			'italic'	=> $_POST[$name.'_italic'],
		);
		return serialize($pack_arr);
	}

	/**
	* Unpack serialized array into CSS style
	* 
	* @access	private
	* @param	
	* @return	string
	*/
	function _unpack_text_style($name,$pack_arr) {
		$pack_arr = unserialize($pack_arr);
		$_POST[$name.'_font_color']	= $pack_arr['font_color'];
		$_POST[$name.'_font_type']	= $pack_arr['font_type'];
		$_POST[$name.'_font_size']	= $pack_arr['font_size'];
		$_POST[$name.'_bold']		= $pack_arr['bold'];
		$_POST[$name.'_underline']	= $pack_arr['underline'];
		$_POST[$name.'_italic']		= $pack_arr['italic'];
	}
}
