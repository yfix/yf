<?php

class yf_manage_patterns{

	function show(){
		$Q = db()->query("SELECT * FROM `".db('grab_patterns')."` ORDER BY `name` DESC");
		while ($A = db()->fetch_assoc($Q)) {
		
			$pattern = unserialize($A["pattern"]);
			
			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"name"			=> _prepare_html($A["name"]),
				"desc"			=> _prepare_html($A["desc"]),	
				//"pattern"		=> _prepare_html($pattern[0][1]),
				"edit_link"		=> "./?object=".$_GET["object"]."&action=add&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$A["id"],
				"items_link"	=> "./?object=".$_GET["object"]."&action=view&id=".$A["id"],
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);		
		}
		
		if(isset($_POST["test_grab"])){	
			$OBJ = main()->init_class("content_grabber", "classes/");				
			$content = $OBJ->_grab($_POST["url"],"test");
		}
		
		$replace = array(
			"items"		=> $items,
			"url"		=> $_POST["url"],
			"content"	=> $content,
		);
		
		return tpl()->parse($_GET["object"]."/main", $replace);
	}
	
	
	function add() {	
	
		$_GET["id"] = intval($_GET["id"]);
		
		// save pattern to data base
		if (isset($_POST["save"])){
		
			if(empty($_POST["name"])) common()->_raise_error(t("Name is required!"));
			
			if(!common()->_error_exists()){
			
				//$pattern = array("0" => array("0" => "0", "1" => $_POST["pattern"]));
				
				if(empty($_GET["id"])){
					db()->INSERT("grab_patterns", array(
						"name"				=> _es($_POST["name"]),
						"desc"				=> _es($_POST["description"]),
						"pattern"			=> _es($_POST["pattern"]),
						"replace_pattern"	=> _es($_POST["replace_pattern"]),
					//	"user_id"			=> $this->USER_ID,
					));
				}else{
					db()->UPDATE("grab_patterns", array(
						"name"				=> _es($_POST["name"]),
						"desc"				=> _es($_POST["description"]),
						"pattern"			=> _es($_POST["pattern"]),
						"replace_pattern"	=> _es($_POST["replace_pattern"]),
					), "`id`=".$_GET["id"]);			
			
				
				}
				// Return user back
				return js_redirect("./?object=".$_GET["object"]);
			}
		}			
		
		// parse content
		if (isset($_POST["parse"])) {
		
			if(empty($_POST["site_url"])) common()->_raise_error(t("Site url is required!"));		
			
			if(!common()->_error_exists()){
				$content_original = file_get_contents($_POST["site_url"]);	
				
				//$preg_match=preg_match("/Content-Type:\s+\S+;\s*charset=(\S+)/i", $content_original,$charset_header);
				$preg_match=preg_match("/<meta\s+http-equiv\s*=\s*[\"|']?Content-Type[\"|']?\s+content\s*=\s*[\"|']\S+;\s*charset\s*=\s*(\S+)[\"|']\s*\/?>/i", $content_original,$charset_meta);
				
				if(($charset_meta[1] != "UTF-8") and ($charset_meta[1] != "")){
					$content_original=iconv($charset_meta[1],"UTF-8",$content_original);
				}

				preg_match($_POST["pattern"],$content_original, $matches); 
				$content = $matches[1];			
				if(!empty($_POST["replace_pattern"])){
					
					$_POST["replace_pattern"] = str_replace("\r", "", $_POST["replace_pattern"]);
					
					$replace_pattern = explode("\n", $_POST["replace_pattern"]);
					
					foreach ((array)$replace_pattern as $key => $value){		
						if(!empty($value)){
							$content = preg_replace($value, "", $content,1);				
						}
					}
				}
			}		
		}
		
		// view pattern_info 
		if ((!empty($_GET["id"])) and (!isset($_POST["parse"]))){
			$pattern_info = db()->query_fetch("SELECT * FROM `".db('grab_patterns')."` WHERE `id`=".$_GET["id"]);			
			$_POST["name"] = _prepare_html($pattern_info["name"]);
			$_POST["description"] = _prepare_html($pattern_info["desc"]);
			//$pattern = unserialize($pattern_info["pattern"]);
			$_POST["pattern"] = $pattern_info["pattern"];
			$_POST["replace_pattern"] = $pattern_info["replace_pattern"];
			
			
		}
		
		// Show template contents
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],					
			"name"				=> $_POST["name"],
			"description"		=> $_POST["description"],
			"pattern"			=> $_POST["pattern"],
			"replace_pattern"	=> $_POST["replace_pattern"],
			"site_url"			=> $_POST["site_url"],
			"source"			=> _prepare_html($content_original),
			"parsed"			=> _prepare_html($content),
			"parsed2"			=> $content,
			"error_message"		=> _e(),
			
		);
		return tpl()->parse($_GET["object"]."/add", $replace);
	}
	
	function delete(){

		$_GET["id"] = intval($_GET["id"]);
		
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}	
		
		$pattern_info = db()->query_fetch("SELECT * FROM `".db('grab_patterns')."` WHERE `id`=".$_GET["id"]);
			
		if (empty($pattern_info["id"])) {
			return _e(t("No such pattern!"));
		}	
		
		if (!empty($pattern_info["id"])) {
			db()->query("DELETE FROM `".db('grab_patterns')."` WHERE `id`=".intval($_GET["id"])." LIMIT 1");			
		}
		
		return js_redirect("./?object=".$_GET["object"]);
	}
	
	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> ucfirst($_GET["object"])." main",
				"url"	=> "./?object=".$_GET["object"],
			),
			array(
				"name"	=> "Add pattern",
				"url"	=> "./?object=".$_GET["object"]."&action=add",
			),
			array(
				"name"	=> "",
				"url"	=> "./?object=".$_GET["object"],
			),
		);
		return $menu;	
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = t("Manage patterns");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "",
			"add"					=> $_GET["id"] ? "Edit" : "Add",
		);             		
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}

		return array(
			"header"	=> $pheader,
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}
}
