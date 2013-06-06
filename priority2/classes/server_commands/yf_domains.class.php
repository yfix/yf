<?php

class yf_domains{

	/**
	*
	*/
	function _init () {
		$this->PARENT_OBJ	= _class(SERVER_COMMANDS_CLASS_NAME);
	}
	
	/**
	*
	*/
	function domain_get_list ($server_info) {
	
		$bind_conf_path = $this->PARENT_OBJ->_get_param($server_info, "bind_conf_path");
		
		$files_list = $this->PARENT_OBJ->SSH_OBJ->scan_dir($server_info, $bind_conf_path."domains/", "", "");
		$files_list = $this->PARENT_OBJ->SSH_OBJ->clean_path($files_list);
		
		foreach ((array)$files_list as $file){
			$result[$file["name"]] = $file["name"];
		}
		
		return $result;
	}

	/**
	*
	*/
	function domain_add ($server_info, $param) {
		if(empty($param["dmn_name"])){
			return false;
		}
		
		empty($param["dmn_notify"])?$param["dmn_notify"] = "yes":"";
		empty($param["dmn_type"])?$param["dmn_type"] = "master":"";
		
		$domains = $this->domain_get_list($server_info);
		
		if(in_array($param["dmn_name"], $domains)){
			trigger_error("server_commands: ".__FUNCTION__.", domain '".$param["dmn_name"]."' already exist", E_USER_WARNING);
			return false;
		}
			
		$vars = $this->PARENT_OBJ->_get_vars($server_info);
		$vars = my_array_merge($vars, $param);
		
		$vars["timestamp"] = abs(crc32(str_replace(",", "",  microtime(true))));
		
		// dmn
		$dmn_stpl_content = file_get_contents(INCLUDE_PATH."services_conf/bind/_stpl/domain.stpl");
		$dmn_stpl_content = tpl()->parse_string("", $vars, $dmn_stpl_content);
		
		$bind_conf_path = $this->PARENT_OBJ->_get_param($server_info, "bind_conf_path");
		
		$this->PARENT_OBJ->SSH_OBJ->mkdir_m($server_info, $bind_conf_path."domains");
		$this->PARENT_OBJ->SSH_OBJ->write_string($server_info, $dmn_stpl_content, $bind_conf_path."domains/".$param["dmn_name"]);
		
		// dmn file
		$db_e_stpl_content = file_get_contents(INCLUDE_PATH."services_conf/bind/_stpl/db_e.stpl");
		$db_e_stpl_content = tpl()->parse_string("", $vars, $db_e_stpl_content);
		
		$this->PARENT_OBJ->SSH_OBJ->mkdir_m($server_info, $bind_conf_path."zones");
		$this->PARENT_OBJ->SSH_OBJ->write_string($server_info, $db_e_stpl_content, $bind_conf_path."zones/".$param["dmn_name"]);
		
		// сохраняем резервную копию
		$this->PARENT_OBJ->_save_backup($server_info, $bind_conf_path."named.conf.local");
		
		//собираем named.conf.local
		$this->_create_bind_named_conf_local($server_info);
		
		
		// проверка конфигов
		$config_check = $this->domain_named_checkconf($server_info);
		$zone_check = $this->domain_named_check_zone($server_info, $param["dmn_name"]);
		
		if((!$config_check) OR (!$zone_check)){
			// восстанавливаем резервную копию
			$this->PARENT_OBJ->_restore_backup($server_info, $bind_conf_path."named.conf.local");
			// удаляем файлы добоваляемого домена
			$this->PARENT_OBJ->SSH_OBJ->unlink($server_info, $bind_conf_path."/zones/".$param["dmn_name"]);
			$this->PARENT_OBJ->SSH_OBJ->unlink($server_info, $bind_conf_path."/domains/".$param["dmn_name"]);
			
			return false;
		}

		// удаляем резервную копию
		$this->PARENT_OBJ->_delete_backup($server_info, $bind_conf_path."named.conf.local");
		
		// перезагрузка сервиса bind
		$bind_service_name = $this->PARENT_OBJ->_get_param($server_info, "bind_service_name");
		$this->PARENT_OBJ->service_restart($server_info, $bind_service_name);
		
		return true;
	}
	
	/**
	*
	*/
	function domain_delete ($server_info, $domain_name) {
		if(empty($domain_name)){
			return false;
		}
		
		$bind_conf_path = $this->PARENT_OBJ->_get_param($server_info, "bind_conf_path");
		
		$this->PARENT_OBJ->SSH_OBJ->unlink($server_info, $bind_conf_path."domains/".$domain_name);
		$this->PARENT_OBJ->SSH_OBJ->unlink($server_info, $bind_conf_path."zones/".$domain_name);

		//собираем named.conf.local
		$this->_create_bind_named_conf_local($server_info);
		
		// проверка конфигов
		$config_check = $this->domain_named_checkconf($server_info);
		if(!$config_check){
			return false;
		}
		
		// перезагрузка сервиса bind
		$bind_service_name = $this->PARENT_OBJ->_get_param($server_info, "bind_service_name");
		$this->PARENT_OBJ->service_restart($server_info, $bind_service_name);

		return true;
	}
	
	/**
	*
	*/
	function domain_get_subdomains_list ($server_info, $domain_name) {
		$bind_conf_path = $this->PARENT_OBJ->_get_param($server_info, "bind_conf_path");
		$content = $this->PARENT_OBJ->SSH_OBJ->read_file($server_info, $bind_conf_path."zones/".$domain_name);
		preg_match_all("/; sub \[(.+?)\] entry BEGIN\./i", $content, $matches);
		
		return $matches["1"];
	}

	/**
	*
	*/
	function domain_add_subdomain ($server_info, $domain_name, $sub_domain_name) {
		if(empty($domain_name) or empty($sub_domain_name)){
			return false;
		}
		
		$subdomains_list = $this->domain_get_subdomains_list($server_info, $domain_name);
		
		if(in_array($sub_domain_name, $subdomains_list)){
			trigger_error("server_commands: ".__FUNCTION__.", subdomain \"".$sub_domain_name."\" on domain \"".$domain_name."\" already exist", E_USER_WARNING);
			return false;
		}
	
		$bind_conf_path = $this->PARENT_OBJ->_get_param($server_info, "bind_conf_path");
		
		$vars = $this->PARENT_OBJ->_get_vars($server_info);
		$vars["sub_domain_name"] = $sub_domain_name;
		
		// сохраняем резервную копию
		$this->PARENT_OBJ->_save_backup($server_info, $bind_conf_path."zones/".$domain_name);
		
		$content = $this->PARENT_OBJ->SSH_OBJ->read_file($server_info, $bind_conf_path."zones/".$domain_name);
		
		$db_sub_entry_stpl_content = file_get_contents(INCLUDE_PATH."services_conf/bind/_stpl/db_sub_entry.stpl");
		$db_sub_entry_stpl_content = tpl()->parse_string("", $vars, $db_sub_entry_stpl_content);
		
		$this->PARENT_OBJ->SSH_OBJ->write_string($server_info, $content.$db_sub_entry_stpl_content, $bind_conf_path."zones/".$domain_name);
		
		// проверка конфигов
		$config_check = $this->domain_named_checkconf($server_info);
		$zone_check = $this->domain_named_check_zone($server_info, $domain_name);
		
		if((!$config_check) OR (!$zone_check)){
			// восстанавливаем резервную копию
			$this->PARENT_OBJ->_restore_backup($server_info, $bind_conf_path."zones/".$domain_name);
			return false;
		}

		
		// удаляем резервную копию
		$this->PARENT_OBJ->_delete_backup($server_info, $bind_conf_path."zones/".$domain_name);
		
		// перезагрузка сервиса bind
		$bind_service_name = $this->PARENT_OBJ->_get_param($server_info, "bind_service_name");
		$this->PARENT_OBJ->service_restart($server_info, $bind_service_name);
	}
	
	/**
	*
	*/
	function domain_delete_subdomain ($server_info, $domain_name, $sub_domain_name) {
		if(empty($domain_name) or empty($sub_domain_name)){
			return false;
		}
		
		$subdomains_list = $this->domain_get_subdomains_list($server_info, $domain_name);
		
		if(!in_array($sub_domain_name, $subdomains_list)){
			return;
		}

		$bind_conf_path = $this->PARENT_OBJ->_get_param($server_info, "bind_conf_path");
		
		$content = $this->PARENT_OBJ->SSH_OBJ->read_file($server_info, $bind_conf_path."zones/".$domain_name);
		$content =  preg_replace("/; sub \[".$sub_domain_name."\] entry BEGIN\..+?; sub \[".$sub_domain_name."\] entry END\./si", "", $content, 1);
		
		// сохраняем резервную копию
		$this->PARENT_OBJ->_save_backup($server_info, $bind_conf_path."zones/".$domain_name);

		
		$this->PARENT_OBJ->SSH_OBJ->write_string($server_info, $content, $bind_conf_path."zones/".$domain_name);
		
		// проверка конфигов
		$config_check = $this->domain_named_checkconf($server_info);
		$zone_check = $this->domain_named_check_zone($server_info, $domain_name);
		
		if((!$config_check) OR (!$zone_check)){
			// восстанавливаем резервную копию
			$this->PARENT_OBJ->_restore_backup($server_info, $bind_conf_path."zones/".$domain_name);
			return false;
		}

		// удаляем резервную копию
		$this->PARENT_OBJ->_delete_backup($server_info, $bind_conf_path."zones/".$domain_name);

		// перезагрузка сервиса bind
		$bind_service_name = $this->PARENT_OBJ->_get_param($server_info, "bind_service_name");
		$this->PARENT_OBJ->service_restart($server_info, $bind_service_name);
	}
	
	/**
	*
	*/
	function domain_named_checkconf ($server_info) {
	
		$bind_conf_path = $this->PARENT_OBJ->_get_param($server_info, "bind_conf_path");
		
		$result = _ssh_exec($server_info, "named-checkconf ".$bind_conf_path."named.conf.local");
		
		if(empty($result)){
			return true;
		}else{
			trigger_error("server_commands: ".__FUNCTION__.", ".$result, E_USER_WARNING);
			return false;
		}
	}

	/**
	*
	*/
	function domain_named_check_zone ($server_info, $domain) {
		if(empty($domain)){
			return false;
		}
		
		$bind_conf_path = $this->PARENT_OBJ->_get_param($server_info, "bind_conf_path");
		
		
		$result = _ssh_exec($server_info, "named-checkzone ".$domain." ".$bind_conf_path."zones/".$domain."| grep -v \": loaded serial\"");	
		$result = trim($result);
		
		if($result !== "OK"){
			trigger_error("server_commands: ".__FUNCTION__.", ".$result, E_USER_WARNING);
			return false;
		}else{
			return true;
		}
	}

	/**
	*
	*/
	function domain_named_check_all_zone ($server_info) {
		$bind_conf_path = $this->PARENT_OBJ->_get_param($server_info, "bind_conf_path");
		
		$domains = $this->domain_get_list($server_info);
		
		
		foreach ((array)$domains as $domain){
		
			$result = _ssh_exec($server_info, "named-checkzone ".$domain." ".$bind_conf_path."zones/".$domain."| grep -v \": loaded serial\"");	
			$result = trim($result);
			
			if($result !== "OK"){
				trigger_error("server_commands: ".__FUNCTION__.", ".$result, E_USER_WARNING);
				$errors = true;
			}
		}
		
		if(!$errors){
			return true;
		}else{
			return false;
		}
	}


	/**
	*
	*/
	function _create_bind_named_conf_local ($server_info) {
		$bind_conf_path = $this->PARENT_OBJ->_get_param($server_info, "bind_conf_path");
		_ssh_exec($server_info, '"">'.$bind_conf_path."named.conf.local".' | for SCRIPT in '.$bind_conf_path."domains/".'* ; do if [ -f $SCRIPT ]; then cat "$SCRIPT" >> '.$bind_conf_path."named.conf.local".' ;fi;done;');
	}




}
