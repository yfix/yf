<?php
class yf_rss {

	/**
	* Default method
	*/
	function show(){
	
		if(isset($_GET["id"])){
			$rss_module_name = explode(",", $_GET["id"]);
		}
		
		$user_modules_methods = main()->call_class_method("user_modules", "admin_modules/", "_get_methods", array("private" => "1")); 

		foreach ((array)$user_modules_methods as $module_name => $module_methods) {
			$OBJ = "";
			foreach ((array)$module_methods as $method_name) {
				if ($method_name != "_rss_general"){
					continue;
				}
				
				if(!empty($_GET["id"])){
					if(in_array($module_name, $rss_module_name)){
						$OBJ = main()->init_class($module_name);
						$data[] = $OBJ->_rss_general();
					}
				}else{
					$OBJ = main()->init_class($module_name);
					$data[] = $OBJ->_rss_general();
				}
				
			}
		}
		
		// prepare array
		$rss_data = array();
		if(!empty($data)){
			foreach ((array)$data as $data_module){
				if(!empty($data_module)){
					foreach ((array)$data_module as $data_module_item){
						$rss_data[] = $data_module_item;
					}
				}
			}
		}
		
		// sort array by 'date'
		$rss_data = $this->_sort_array_by_field($rss_data, "date");

		// Fill array of feed params
		$params = array(
			"feed_file_name"	=> "",
			"feed_title"		=> str_replace('http://', "", WEB_PATH),
			"feed_desc"			=> "",
			"feed_url"			=> "",
		);
		
		return common()->rss_page($rss_data, $params);
	}
	
	/**
	* manage rss
	*/
	function manage(){
	
		$user_modules_methods = main()->call_class_method("user_modules", "admin_modules/", "_get_methods", array("private" => "1")); 
		
		foreach ((array)$user_modules_methods as $module_name => $module_methods) {
			foreach ((array)$module_methods as $method_name) {
				if ($method_name != "_rss_general"){
					continue;
				}
				
				$rss_module_name[$module_name] = $module_name;
			}
		}
		
		$rss_object_select = $rss_module_name;
		$rss_url = process_url("./?object=rss");
		
		if($_POST){
			foreach ((array)$rss_module_name as $key => $value){
				if(isset($_POST["rss_manage_box_".$key])){
					$rss_module_name_new[$key] = $value;
				}
			}
			
			$rss_object_select = $rss_module_name_new;
			
			if(!empty($rss_object_select)){
				$rss_url = process_url("./?object=rss&action=show&id=".implode(",",$rss_object_select));
			}else{
				$rss_url = "";
			}
		}
		
		$rss_manage_box = common()->multi_check_box("rss_manage_box", $rss_module_name, $rss_object_select, true, "2", "", true);
		
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"rss_manage_box"	=> $rss_manage_box,
			"rss_url"			=> $rss_url,
		);
		
		return tpl()->parse($_GET["object"]."/manage", $replace);
	}

	/**
	* sort array by field
	*/
	function _sort_array_by_field($original, $field, $descending = false){
		$sortArr = array();
			
		foreach ((array)$original as $key => $value){
			$sortArr[ $key ] = $value[ $field ];
		}

		if ($descending){
			arsort( $sortArr );
		}else{
			asort( $sortArr );
		}
		
		$resultArr = array();
		foreach ((array)$sortArr as $key => $value ){
			$resultArr[$key] = $original[$key];
		}
	
		return $resultArr;
	}		   

	/**
	* widget, show rss button
	*/
	function _widget_button ($params = array()) {
		if (isset($params["describe"]) && $params["describe"]) {
			return array("allow_cache" => 1);
		}

		$replace = array(
			"rss_posts_button"		=> _class('graphics')->_show_rss_button(WEB_PATH, process_url("./?object=rss")),
			"manage_rss_link"		=> "./?object=".__CLASS__."&action=manage",
		);
		
		return tpl()->parse(__CLASS__."/widget_rss", $replace);
	}

}
