<?php

/**
* Visual widgets editor
*/
class widgets_editor {

	/** @var array */
	public $COLOR = array(
		"blog"		=> "689A46",
		"forum"		=> "FF9933",
		"gallery"	=> "C08040",
		"calendar"	=> "3399FF",
		"users"		=> "330099",
		"poll"		=> "780099",
		"interests"	=> "CC3300",
		"tags"		=> "009FAA",
		"rss"		=> "E46E14",
	);
	/** @var string */
	public $FORCE_USER_THEME = "";
	
	/**
	* Framework constructor
	*/
	function _init(){
		$this->_user_modules_methods = main()->_execute("user_modules", "_get_methods", array("private" => "1"));
		
		foreach ((array)$this->_user_modules_methods as $module_name => $module_methods) {
			foreach ((array)$module_methods as $method_name) {
				if (substr($method_name, 0, 8) != "_widget_"){
					continue;
				}
				$widgets_name = str_replace("_widget_","",$method_name);
				
				$name = $module_name.":".str_replace("_"," ",$widgets_name);
				$display_name = $module_name." ".str_replace("_"," ",$widgets_name);
				
				$widgets_name = $module_name."_".$widgets_name;
				
				$this->block[$widgets_name] = array(
					"name"			=> $name,
					"color"			=> $this->COLOR[$module_name],
					"widget_title"	=> $display_name,
					"execute"		=> $module_name.",".$method_name,
					"display_name"	=> $display_name,
					"module_name"	=> $module_name,
					"method_name"	=> $method_name,
				);
				
				
				//widget,_special
				// special widget HTML
				$this->block["html"] = array(
					"name"			=> "Special:HTML",
					"color"			=> "",
					"widget_title"	=> "",
					"execute"		=> "widgets,_special",
					"display_name"	=> "",
					"module_name"	=> "widgets",
					"method_name"	=> "_special",
					
				);
			}
		}

		// Select user theme
		if ($this->FORCE_USER_THEME) {
			$user_theme = $this->FORCE_USER_THEME;
		} else {
			$sites_info = _class("sites_info")->info;
			$user_theme = $sites_info["1"]["DEFAULT_SKIN"];
		}
		
		if (empty($user_theme)) {
			$user_theme = "user";
		}
		
		$this->theme_path = INCLUDE_PATH. "templates/". $user_theme."/";
	}
	
	/**
	* Default method
	*/
	function show() {

		$Q = db()->query("SELECT * FROM ".db('widgets')."");
		while ($A = db()->fetch_assoc($Q)) {
			$modules[$A["id"]] = $A;
		}

		$default = array(
			"0"		=> array(
				"id"		=> "-1",
				"object"	=> "default",
				"active"	=> "1",
			)
		);
		
		$modules = my_array_merge($default, $modules);
		
		foreach ((array)$modules as $module){

			$columns  = unserialize($module["columns"]);
			$columns_text = "";

			$columns["left"]			? $columns_text .= "+" : $columns_text .= "-";
			$columns["center-top"]		? $columns_text .= "+" : $columns_text .= "-";
			$columns["center-bottom"]	? $columns_text .= "+" : $columns_text .= "-";
			$columns["right"]			? $columns_text .= "+" : $columns_text .= "-";

			if ($module["id"] == "-1") { // if default column
				$columns_text = "";

				
				$left_block 			= $this->_get_area_content("left_area_widgets.stpl");
				$center_top_block 		= $this->_get_area_content("center-top_area_widgets.stpl");
				$center_bottom_block 	= $this->_get_area_content("center-bottom_area_widgets.stpl");
				$right_block 			= $this->_get_area_content("right_area_widgets.stpl");
				
				$left_block			? $columns_text .= "+" : $columns_text .= "-";
				$center_top_block	? $columns_text .= "+" : $columns_text .= "-";
				$center_bottom_block? $columns_text .= "+" : $columns_text .= "-";
				$right_block		? $columns_text .= "+" : $columns_text .= "-";
			}
			
			$theme_name = array();
			
			$replace2 = array(
				"object"			=> $module["object"],
				"action"			=> $module["action"],
				"theme"				=> $theme_name,
				"active"			=> $module["active"],
				"active_link"		=> "./?object=".$_GET["object"]."&action=activate_item&id=".$module["id"],
				"edit_link"			=> "./?object=".$_GET["object"]."&action=edit&id=".$module["id"],
				"visual_edit_link"	=> "./?object=".$_GET["object"]."&action=visual_edit&id=".$module["id"],
				"delete_link"		=> "./?object=".$_GET["object"]."&action=delete&id=".$module["id"],
				"clone_link"		=> "./?object=".$_GET["object"]."&action=clone_widgets&id=".$module["id"],
				"item_id"			=> $module["id"],
				"columns"			=> $columns_text,
				"comments"			=> _prepare_html($module["comments"]),
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}

		$replace = array(
			"form_action"			=> "./?object=".$_GET["object"]."&action=add",
			"form_action_group_edit"=> "./?object=".$_GET["object"]."&action=visual_edit&id=group",
			"items"					=> $items,
			
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}
	
	function _get_area_content($name){
		if (file_exists($this->theme_path.$name)) {
			return file_get_contents($this->theme_path.$name);
		}else{
			return file_get_contents(YF_PATH."templates/user/".$name);
		}
	}
	
	/**
	* Add new override option
	*/
	function add(){
		if (isset($_POST["go"])) {
			if (!$_POST["location"] && !$_POST["theme_select_box"]) {
				_re(t("Please select something"));
			}
			
			if (!common()->_error_exists()) {
			
				list($object, $action) = explode("&",$_POST["location"]);
				$object = str_replace("object=","",$object);
				$action = str_replace("action=","",$action);
			
				db()->INSERT("widgets", array(
					"object"	=> _es($object),
					"action"	=> _es($action),
					"theme"		=> ";".implode(";", $_POST["theme_select_box"]).";",
					"active"	=> $_POST["active"],
					"comments"	=> _es($_POST["comments"]),
				));
				// Refresh system cache
				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("widgets_override");
					cache()->refresh("widgets_params");
				}
				return js_redirect("./?object=".$_GET["object"]."&action=show");
			}
		}
	
		$methods_box = common()->select_box("methods", $this->_get_user_methods(), $_GET["id"], false, 2, "", false);	

		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"edit_modules_link"	=> "./?object=user_modules",
			"location"			=> "",
			"methods_box"		=> $methods_box,
			"comments"			=> "",
			"active"			=> "1",
			"error_message"		=> _e(),
			"theme_select_box"	=> $theme_select_box,
		);
		return tpl()->parse($_GET["object"]."/add", $replace);
	}
	
	/**
	* Edit override option
	*/
	function edit(){
		if(empty($_GET["id"])){
			return _e("id empty");
		}
		
		if(isset($_POST["go"])){
			if (!$_POST["location"] && !$_POST["theme_select_box"]) {
				_re(t("Please select something"));
			}
			
			if(!common()->_error_exists()){
				list($object, $action) = explode("&",$_POST["location"]);
				$object = str_replace("object=","",$object);
				$action = str_replace("action=","",$action);
			
				db()->UPDATE("widgets", array(
					"object"	=> _es($object),
					"action"	=> _es($action),
					"theme"		=> ";".implode(";", $_POST["theme_select_box"]).";",
					"active"	=> $_POST["active"],
					"comments"	=> _es($_POST["comments"]),
				), "id=".intval($_GET["id"]));
				// Refresh system cache
				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("widgets_override");
					cache()->refresh("widgets_params");
				}
				
				return js_redirect("./?object=".$_GET["object"]."&action=show");
			}
		}

		
		$widgets = db()->query_fetch("SELECT * FROM ".db('widgets')." WHERE id=".intval($_GET["id"]));
		
		$methods_box = common()->select_box("methods", $this->_get_user_methods(), $_GET["id"], false, 2, "", false);	
		
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"edit_modules_link"	=> "./?object=user_modules",
			"location"			=> "object=".$widgets["object"]."&action=".$widgets["action"],
			"methods_box"		=> $methods_box,
			"comments"			=> $widgets["comments"],
			"active"			=> $widgets["active"],
			"error_message"		=> _e(),
			"theme_select_box"	=> $theme_select_box,

		);
		
		return tpl()->parse($_GET["object"]."/add", $replace);
	}

	/**
	* Clone widgets rule
	*/
	function clone_widgets () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		if ($_GET["id"] == -1) {
			$columns = array(
				"left"			=> $this->_get_area_content("left_area_widgets.stpl"),
				"center-top"	=> $this->_get_area_content("center-top_area_widgets.stpl"),
				"center-bottom"	=> $this->_get_area_content("center-bottom_area_widgets.stpl"),
				"right"			=> $this->_get_area_content("right_area_widgets.stpl"),
			);
			$info = array(
				"columns"	=> _es(serialize($columns)),
				"active"	=> 0,
			);
		} else {
			$info = db()->query_fetch("SELECT * FROM ".db('widgets')." WHERE id=".intval($_GET["id"]));
		}
		if (!$info) {
			return _e("Record to clone not found!");
		}
		// Prepare SQL
		$sql = $info;
		unset($sql["id"]);
		// Do clone menu record
		db()->INSERT("widgets", $sql);
		$NEW_WIDGET_ID = db()->INSERT_ID();
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("blocks_names");
			cache()->refresh("blocks_rules");
		}
		return js_redirect("./?object=".$_GET["object"]);
	}
		
	/**
	* Visual editor here
	*/
	function visual_edit(){
		// Save form
		if (isset($_POST["go"])) {
			// get left block names
			$left_blocks = explode("&",$_POST["sidebar-1order"]);
			$new_blocks_name = array();
			foreach ((array)$left_blocks as $block){
				$new_blocks_name[] = str_replace("sidebar-1[]=", "", $block);
			}
			$left_content = $this->_get_content($new_blocks_name);

			// get right block names
			$right_blocks = explode("&",$_POST["sidebar-2order"]);
			$new_blocks_name = array();
			foreach ((array)$right_blocks as $block){
				$new_blocks_name[] = str_replace("sidebar-2[]=", "", $block);
			}
			$right_content = $this->_get_content($new_blocks_name);

			// get center-top block names
			$center_top_blocks = explode("&",$_POST["sidebar-center-toporder"]);
			$new_blocks_name = array();
			foreach ((array)$center_top_blocks as $block){
				$new_blocks_name[] = str_replace("sidebar-center-top[]=", "", $block);
			}
			$center_top_content = $this->_get_content($new_blocks_name);
		
			// get center-bottom block names
			$center_bottom_blocks = explode("&",$_POST["sidebar-center-bottomorder"]);
			$new_blocks_name = array();
			foreach ((array)$center_bottom_blocks as $block){
				$new_blocks_name[] = str_replace("sidebar-center-bottom[]=", "", $block);
			}
			$center_bottom_content = $this->_get_content($new_blocks_name);
		
		
			if ($_GET["id"] == "-1") {  // save widget to template

				file_put_contents($this->theme_path."left_area_widgets.stpl" ,$left_content);
				file_put_contents($this->theme_path."right_area_widgets.stpl" ,$right_content);
				file_put_contents($this->theme_path."center-top_area_widgets.stpl" ,$center_top_content);
				file_put_contents($this->theme_path."center-bottom_area_widgets.stpl" ,$center_bottom_content);

			} else {  // save widget to database
			
				$columns = array(
					"left"			=> $left_content,
					"center-top"	=> $center_top_content,
					"center-bottom"	=> $center_bottom_content,
					"right"			=> $right_content,
				);

				if ($_GET["id"] == "group") {
					$ids = explode("_",$_POST["group_save_id"]);
					
					foreach ((array)$ids as $id){
						db()->UPDATE("widgets", array(
							"columns"	=> _es(serialize($columns)),  // @unserialize
						), "id=".intval($id));
					}
				} else {
				
					db()->UPDATE("widgets", array(
						"columns"	=> _es(serialize($columns)),  // @unserialize
					), "id=".intval($_GET["id"]));
				}
			}
			// Refresh system cache
			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("widgets_override");
				cache()->refresh("widgets_params");
			}
			return js_redirect("./?object=".$_GET["object"]);
		}
		
		if ($_GET["id"] != "-1") {
			if ($_GET["id"] == "group") {
				$widget_id = $_POST["item"]["0"];
			} else {
				$widget_id = $_GET["id"];
			}
			
			$widget = db()->query_fetch("SELECT * FROM ".db('widgets')." WHERE id=".intval($widget_id));
		 }

		// get settings		
		$pattern = "/\{execute\((.+?)\)\}.+?class=\"title\">\{t\(\"(.+?)\"\)\}<\/div>/si";

		if ($_GET["id"] == "-1"){

			$left_block = $this->_get_area_content("left_area_widgets.stpl");
			$right_block = $this->_get_area_content("right_area_widgets.stpl");
			$center_top_block = $this->_get_area_content("center-top_area_widgets.stpl");
			$center_bottom_block = $this->_get_area_content("center-bottom_area_widgets.stpl");

		} else {
			$columns_data = unserialize($widget["columns"]);
			
			$left_block				= $columns_data["left"];
			$right_block			= $columns_data["right"];
			$center_top_block		= $columns_data["center-top"];
			$center_bottom_block	= $columns_data["center-bottom"];
		}
		
		preg_match_all($pattern, $left_block, $left_block_names);
		preg_match_all($pattern, $right_block, $right_block_names);
		preg_match_all($pattern, $center_top_block, $center_top_block_names);
		preg_match_all($pattern, $center_bottom_block, $center_bottom_block_names);

		
		$this->palette = $this->block;
		
		$sidebar_left 			= $this->_prepare_array($left_block_names, $left_block);
		$sidebar_right 			= $this->_prepare_array($right_block_names, $right_block);
		$sidebar_center_top 	= $this->_prepare_array($center_top_block_names, $center_top_block);
		$sidebar_center_bottom 	= $this->_prepare_array($center_bottom_block_names, $center_bottom_block);
		
		unset($this->palette["html"]);
		
		// group save ids
		if($_GET["id"] == "group"){
			if(!empty($_POST["item"])){
				$group_save_id = implode("_",$_POST["item"]);
			}else{
				$group_save_id = $_POST["group_save_id"];
			}
		}else{
			$group_save_id = "";
		}
		
		$replace = array(
			"form_action"			=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"expert_mode_link"		=> "./?object=".$_GET["object"]."&action=expert_mode&id=".$_GET["id"],
			"sidebar_left"			=> $sidebar_left,
			"sidebar_right"			=> $sidebar_right,
			"sidebar_center_top"	=> $sidebar_center_top,
			"sidebar_center_bottom"	=> $sidebar_center_bottom,
			"palette"				=> $this->palette,
			"group_save_id"			=> $group_save_id,
			"widgets"				=> $this->block,
			"control"				=> $this->control,
		);
		
		return tpl()->parse($_GET["object"]."/visual_edit", $replace);
	}
	

	
	/**
	* Delte overriding option
	*/
	function delete(){
		if(empty($_GET["id"])){
			return _e("id empty");
		}
		
		if($_GET["id"] == "-1"){
			return js_redirect("./?object=".$_GET["object"]);
		}


		if (!empty($_GET["id"])) {
			$widget = db()->query_fetch("SELECT * FROM ".db('widgets')." WHERE id=".intval($_GET["id"]));
		}
		if (!empty($widget["id"])) {
			db()->query("DELETE FROM ".db('widgets')." WHERE id=".intval($_GET["id"])." LIMIT 1");
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("widgets_override");
			cache()->refresh("widgets_params");
		}
		return js_redirect("./?object=".$_GET["object"]);
	}
	
	/**
	* Edit overriding option in expert mode
	*/
	function expert_mode(){
		list($_GET["id"], $side) = explode("_",$_GET["id"]);
		
		if(($side !== "left") and ($side !== "center-top") and ($side !== "center-bottom") and ($side !== "right")){
			return _e("error side");
		}

		if(isset($_POST["go"])){
			if(($_GET["id"] != "-1")){
			
				if($_GET["id"] == "group"){
					$ids = explode("_",$_POST["group_save_id"]);
				}else{
					$ids = array($_GET["id"]);
				}
				
				foreach ((array)$ids as $id){
					$widget = db()->query_fetch("SELECT * FROM ".db('widgets')." WHERE id=".intval($id));

					$content = unserialize($widget["columns"]);
					
					$left_content = $content["left"];
					$center_top_content = $content["center-top"];
					$center_bottom_content = $content["center-bottom"];
					$right_content = $content["right"];
					
					$var_name = $side."_content";
					$text = $_POST["text"];

					$text = str_replace("&#123;","{",$text);
					$text = str_replace("&#125;","}",$text);
					
					$$var_name = $text;
					
					$columns = array(
						"left"			=> $left_content,
						"center-top"	=> $center_top_content,
						"center-bottom"	=> $center_bottom_content,
						"right"			=> $right_content,
					);

					db()->UPDATE("widgets", array(
						"columns"		=> _es(serialize($columns)),
					), "id=".intval($id));
				}
				
			}else{
				$text = $_POST["text"];
				
				$text = str_replace("&#123;","{",$text);
				$text = str_replace("&#125;","}",$text);

				file_put_contents($this->theme_path.$side."_area_widgets.stpl" ,$text);
			}
			// Refresh system cache
			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("widgets_override");
				cache()->refresh("widgets_params");
			}
			
			return js_redirect("./?object=".$_GET["object"]);
		}

		
		if($_GET["id"] != "-1"){
			if($_GET["id"] == "group"){
				list($widget_id) = explode("_",$_POST["group_save_id"]);
			}else{
				$widget_id = $_GET["id"];
			}

			$widget = db()->query_fetch("SELECT * FROM ".db('widgets')." WHERE id=".intval($widget_id));
			$text = unserialize($widget["columns"]);
			$text = $text[$side];
		} else {
			$text = $this->_get_area_content($side."_area_widgets.stpl");
		}
		
		$text = str_replace("{","&#123;",$text);
		$text = str_replace("}","&#125;",$text);
		
		// group save ids
		if($_GET["id"] == "group"){
			if(!empty($_POST["item"])){
				$group_save_id = implode("_",$_POST["item"]);
			}else{
				$group_save_id = $_POST["group_save_id"];
			}
		}else{
			$group_save_id = "";
		}
		

		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"]."_".$side,
			"title"			=> $side,
			"text"			=> $text,
			"widgets"		=> $this->block,
			"group_save_id"	=> $group_save_id,
		);
		
		return tpl()->parse($_GET["object"]."/expert_mode", $replace);
	}
	
	/**
	* Change option "active" status
	*/
	function activate_item() {
		// Try to find such menu item in db
		if (!empty($_GET["id"])) {
			$widget = db()->query_fetch("SELECT * FROM ".db('widgets')." WHERE id=".intval($_GET["id"]));
		}
		// Do change activity status
		if (!empty($widget)) {
			db()->UPDATE("widgets", array("active" => (int)!$widget["active"]), "id=".intval($widget["id"]));
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("widgets_override");
			cache()->refresh("widgets_params");
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($widget["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]."&action=show");
		}
	}
	
	/**
	* 
	*/
	function _get_content($blocks_name) {
		if (empty($blocks_name)) {
			return "";
		}
		
		if(empty($GLOBALS['widget_html_count'])){
			$GLOBALS['widget_html_count'] = 1;
		}
		
		foreach ((array)$blocks_name as $block_name) {
			if (empty($block_name)) {
				continue;
			}
			
			$_cur_block = $this->block[$block_name];
			$_name = ucfirst($_cur_block["display_name"]);
			
			if(!empty($_POST[$block_name."_title"])){
				$_name = $_POST[$block_name."_title"];
			}
			
			$_name = "{t(\"".str_replace("\"", "&quot;", $_name)."\")%%##%%";
			
			$replace = array(
				"display_name"	=> $_name,
				"execute_text"	=> $_cur_block["execute"],
				"var_name"		=> "_w_".$_cur_block["module_name"]."_".substr($_cur_block["method_name"], strlen("_widget_")),
			);
			
			$template = "widget";
			
			if(substr($block_name,"0","4") == "html"){
				$num = substr_replace($block_name, "", 0, 4);

				$replace["text"] = $_POST["html".$num."_text"];
				$replace["display_name"] = "{t(\"".str_replace("\"", "&quot;", $_POST["html".$num."_title"])."\")%%##%%";
				$replace["execute_text"] = $this->block["html"]["execute"].";id=".$GLOBALS['widget_html_count'];
	
				$template = "widget_special_html";
				$GLOBALS['widget_html_count']++;
			}
			
			$content .= tpl()->parse($_GET["object"]."/".$template, $replace);
			$content =  str_replace("%%##%%", "}", $content);
		}
		return $content;
	}
	
	/**
	* 
	*/
	function _get_widget_key($widget){
		foreach ((array)$this->block as $_k => $settings) {
			foreach ((array)$settings as $setting) {
				if ($setting == $widget) {
					return $_k;
				}
			}
		}
	}
	
	/**
	* Parse and return array of available methods in user section modules
	*/
	function _get_user_methods(){
		// Get user modules
		$this->_user_modules = main()->_execute("user_modules", "_get_modules");
		// Get user methods groupped by modules
		$this->_user_modules_methods = main()->_execute("user_modules", "_get_methods");
		$this->_user_methods[""] = "-- ALL --";
		// Prepare methods
		foreach ((array)$this->_user_modules_methods as $module_name => $module_methods) {
			$this->_user_methods["object=".$module_name] = $module_name." -> -- ALL --";
			foreach ((array)$module_methods as $method_name) {
				if ($method_name == $module_name) {
					continue;
				}
				$this->_user_methods["object=".$module_name."&action=".$method_name] = _prepare_html($module_name." -> ".$method_name);
			}
		}
		return $this->_user_methods;
	}
	
	/**
	* 
	*/
	function _prepare_array($block_names, $block_content){
		if(!empty($block_names[1])){
			$i=0;
			foreach ((array)$block_names[1] as $widget){
				
				//for html cpecial widget
				$html_widget = strpos($widget, $this->block["html"]["execute"]);
				
				$content = array();
				$settings_info = array();
				if ($html_widget !== false) {
					$len = strlen($widget) - strlen($this->block["html"]["execute"].";id=");
					$key = "html".substr($widget, strlen($widget) - $len, $len);
					
					preg_match('/{execute\('.$widget.'\)}.+?class="title">\{t\("(.+?)"\)\}<\/div>(.+?)<\/div>/si', $block_content, $settings_info);
					$content = array(
						"name"			=> $this->block["html"]["name"],
						"color"			=> "",
						"widget_title"	=> $settings_info[1],
//						"execute"		=> "",
//						"display_name"	=> "",
//						"module_name"	=> "",
//						"method_name"	=> "",
					);
					
					$this->control[$key] = array(
						"widget_title"	=> $settings_info[1],
						"widget_text"	=> $settings_info[2],
					);
					
					//$this->block[$key]["widget_title"] = $settings_info[1];
				} else {
					$key = $this->_get_widget_key($widget);
					$this->block[$key]["widget_title"] = $block_names[2][$i];
					$content = $this->block[$key];
					
				}			
				
				$sidebar[$key] = $content;
				unset($this->palette[$key]);
				$i++;
			}
			return $sidebar;
		}
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
				"name"	=> "Add",
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
		$pheader = t("Widgets editor");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "",
			"import"				=> "Install",
			"uninstall"				=> "",
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
