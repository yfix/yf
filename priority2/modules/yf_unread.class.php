<?php
class yf_unread {

	public $UNREAD_ENABLED = false;

	/**
	*
	*/
	function show () {
	
		if(!$this->UNREAD_ENABLED){
			return;
		}
	
		if(empty($this->USER_ID)){
			return;
		}
	
		$user_modules_methods = main()->call_class_method("user_modules", "admin_modules/", "_get_methods", array("private" => "1")); 

		foreach ((array)$user_modules_methods as $module_name => $module_methods) {
			$OBJ = "";
			foreach ((array)$module_methods as $method_name) {
				if (substr($method_name, 0, 7) != "_unread"){
					continue;
				}
				
				$unread_name = str_replace("_unread","",$method_name);
				
				$OBJ = main()->init_class($module_name);
				if(is_object($OBJ)){
					$data[$module_name.$unread_name] = $OBJ->$method_name();
				}
			}
		}
		
		foreach ((array)$data as $module => $content){
		
			$module = _es($module);

			if(!empty($content)){
				// записываем непрочтённые записи, о которых сообщил модуль
				$this->_set_unread($module, $content["ids"]);
			}

			$modules[$module] = "'".$module."'";
		}
		
		$Q = db()->query("SELECT `module_name`,`object_id` FROM `".db('unread')."` WHERE `user_id` = ".intval($this->USER_ID)." AND `module_name` IN(".implode(",", $modules).")");
		while ($A = db()->fetch_assoc($Q)) {
			$unread_count[$A["module_name"]][$A["object_id"]] = $A["object_id"];
		}
		
		foreach ((array)$data as $module => $content){
			$module = _es($module);
			$unread_count[$module] = count($unread_count[$module]);

			if(empty($unread_count[$module]) AND !empty($content["count"])){
				$unread_count[$module] = $content["count"];
			}
			
			if(!empty($unread_count[$module])){
				$unread[$module] = array(
					"count"	=> $unread_count[$module],
					"link"	=> $content["link"],
				);
			}
		}
		
		if(empty($unread)){
			return;
		}
		
		$replace = array(
			"unread"			=> $unread,
			"set_all_read_link"	=> "./?object=unread&action=set_all_read",
		);
		
		return tpl()->parse(__CLASS__ ."/main", $replace);
	}
	
	/**
	*
	*/
	function _get_unread ($module) {
		if(!$this->UNREAD_ENABLED){
			return;
		}

		if(empty($this->USER_ID)){
			return;
		}
		
		if(empty($module)){
			return;
		}

		$Q = db()->query("SELECT `object_id` FROM `".db('unread')."` WHERE `user_id` = ".intval($this->USER_ID)." AND `module_name` = '".$module."'");
		while ($A = db()->fetch_assoc($Q)) {
			$ids[$A["object_id"]] = $A["object_id"];
		}
		
		return $ids;
	}
	
	/**
	*
	*/
	function _set_read ($module, $ids) {
		if(!$this->UNREAD_ENABLED){
			return;
		}
		
		if(empty($this->USER_ID)){
			return;
		}
		
		if(empty($ids)){
			return;
		}
		
		db()->query("DELETE FROM `".db('unread')."`  WHERE `user_id` = ".intval($this->USER_ID)." AND `module_name` = '"._es($module)."' AND `object_id` IN(".implode(",", (array)$ids).")");
	}
	
	
	/**
	*
	*/
	function _set_unread ($module, $ids) {
		if(!$this->UNREAD_ENABLED){
			return;
		}
		
		if(empty($this->USER_ID)){
			return;
		}

		if(empty($ids)){
			return;
		}
		
		$Q = db()->query("SELECT `object_id` FROM `".db('unread')."` WHERE `user_id` = ".intval($this->USER_ID)." AND `module_name` = '"._es($module)."' AND `object_id` IN(".implode(",", (array)$ids).")");
		while ($A = db()->fetch_assoc($Q)) {
			$exist_id[$A["object_id"]] = $A["object_id"];
		}
		
		foreach ((array)$ids as $id){
		
			if(!in_array($id, (array)$exist_id)){
				db()->INSERT("unread", array(
					"module_name"		=> _es($module),
					"object_id"			=> intval($id),
					"user_id"			=> intval($this->USER_ID),
				));
			}
		}
		
	}
	

	/**
	*
	*/
	function set_all_read () {
		if(!$this->UNREAD_ENABLED){
			return;
		}
		
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}

		db()->query("DELETE FROM `".db('unread')."`  WHERE `user_id` = ".intval($this->USER_ID));
		return js_redirect(WEB_PATH);
	}
	

}
