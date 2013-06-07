<?php

class yf_virtual_host{

	/**
	*
	*/
	function _init () {
		$this->PARENT_OBJ	= _class(SERVER_COMMANDS_CLASS_NAME);
	}
	
	/**
	*
	*/
	function virtual_hosts_get ($server_info) {
		
		switch($server_info["http_d_type"]){
			case 1:		// apache
				return $this->apache_virtual_hosts_get($server_info);
				break;	
			case 2:		// apache+nginx
				return $this->apache_virtual_hosts_get($server_info);
				break;
			case 3:		// nginx
				return $this->nginx_virtual_hosts_get($server_info);
				break;
			default: return false;
		}
		return true;
	}

	/**
	*
	*/
	function virtual_host_enable ($server_info, $virtual_host_name) {
		
		switch($server_info["http_d_type"]){
			case 1:		// apache
				$this->apache_virtual_host_enable($server_info, $virtual_host_name);
				break;	
			case 2:		// apache+nginx
				$this->apache_virtual_host_enable($server_info, $virtual_host_name);
				$this->nginx_virtual_host_enable($server_info, $virtual_host_name);
				break;
			case 3:		// nginx
				$this->nginx_virtual_host_enable($server_info, $virtual_host_name);
				break;
			default: return false;
		}
		return true;
	}

	/**
	*
	*/
	function virtual_host_disable ($server_info, $virtual_host_name) {
		
		switch($server_info["http_d_type"]){
			case 1:		// apache
				$this->apache_virtual_host_disable($server_info, $virtual_host_name);
				break;	
			case 2:		// apache+nginx
				$this->apache_virtual_host_disable($server_info, $virtual_host_name);
				$this->nginx_virtual_host_disable($server_info, $virtual_host_name);
				break;
			case 3:		// nginx
				$this->nginx_virtual_host_disable($server_info, $virtual_host_name);
				break;
			default: return false;
		}
		return true;
	}

	/**
	*
	*/
	function virtual_host_add ($server_info, $param) {
	
		switch($server_info["http_d_type"]){
			case 1:		// apache
				$this->apache_virtual_host_add($server_info, $param);
				break;	
			case 2:		// apache+nginx
				$this->apache_virtual_host_add($server_info, $param);
				$this->nginx_virtual_host_add($server_info, $param);
				break;
			case 3:		// nginx
				$this->nginx_virtual_host_add($server_info, $param);
				break;
			default: return false;
		}
		return true;
	}

	/**
	*
	*/
	function virtual_host_delete ($server_info, $virtual_host_name) {

		switch($server_info["http_d_type"]){
			case 1:		// apache
				$this->apache_virtual_host_delete($server_info, $virtual_host_name);
				break;	
			case 2:		// apache+nginx
				$this->apache_virtual_host_delete($server_info, $virtual_host_name);
				$this->nginx_virtual_host_delete($server_info, $virtual_host_name);
				break;
			case 3:		// nginx
				$this->nginx_virtual_host_delete($server_info, $virtual_host_name);
				break;
			default: return false;
		}
		return true;
	}

	/**
	*
	*/
	function virtual_host_edit ($server_info, $param) {

		switch($server_info["http_d_type"]){
			case 1:		// apache
				$this->apache_virtual_host_edit($server_info, $param);
				break;	
			case 2:		// apache+nginx
				$this->apache_virtual_host_edit($server_info, $param);
				break;
			case 3:		// nginx
				break;
			default: return false;
		}
		
		return true;
	}
	
	/**
	*
	*/
	function apache_virtual_hosts_get($server_info){
	
		$apache_path = $this->PARENT_OBJ->_get_param($server_info, "apache_conf_path");
		$available_path = $apache_path."sites-available/";
		$enabled_path = $apache_path."sites-enabled/";
	
		$available_virtual_hosts = $this->PARENT_OBJ->_get_aven_list($server_info, $available_path, $enabled_path);
		
		foreach ((array)$available_virtual_hosts as $virtual_host_name => $virtual_host_value){
			
			$virtual_host_info = _ssh_exec($server_info, "cat ".$available_path.$virtual_host_name." | awk '{if(($1 == \"ServerName\") || ($1 == \"DocumentRoot\")) print $1,$2}'");
			
			
			preg_match("/ServerName (.+?)\n/", $virtual_host_info, $mathes)?$server_name = trim($mathes["1"]):"";
			preg_match("/DocumentRoot (.+?)\n/", $virtual_host_info, $mathes)?$document_root = trim($mathes["1"]):"";
	
			$result[$virtual_host_name] = array(
				"virtual_host_name"	=> $virtual_host_name,
				"symlink_name"		=> $virtual_host_value["symlink_name"],
				"status"			=> $virtual_host_value["status"],
				"server_name"		=> $server_name,
				"document_root"		=> $document_root,
			);
		}
		
		return $result;
	}
	
	/**
	*
	*/
	function apache_virtual_host_enable ($server_info, $virtual_host_name) {
		$apache_path = $this->PARENT_OBJ->_get_param($server_info, "apache_conf_path");
		$result = $this->PARENT_OBJ->_enable_aven($server_info, $apache_path."sites-available/", $apache_path."sites-enabled/", $virtual_host_name);
		

		$apache_service_name = $this->PARENT_OBJ->_get_param($server_info, "apache_service_name");
		$this->PARENT_OBJ->service_restart($server_info, $apache_service_name);
		
		return $result;
	}
	
	/**
	*
	*/
	function apache_virtual_host_disable ($server_info, $virtual_host_name) {

		$apache_path = $this->PARENT_OBJ->_get_param($server_info, "apache_conf_path");
		$result = $this->PARENT_OBJ->_disable_aven($server_info, $apache_path."sites-available/", $apache_path."sites-enabled/", $virtual_host_name);

		$apache_service_name = $this->PARENT_OBJ->_get_param($server_info, "apache_service_name");
		$this->PARENT_OBJ->service_restart($server_info, $apache_service_name);

		return $result;
	}
	
	/**
	*
	*/
	function apache_virtual_host_delete ($server_info, $virtual_host_name) {
	
		$apache_path = $this->PARENT_OBJ->_get_param($server_info, "apache_conf_path");
		$result = $this->PARENT_OBJ->_delete_aven($server_info, $apache_path."sites-available/", $apache_path."sites-enabled/", $virtual_host_name);
		
		$apache_service_name = $this->PARENT_OBJ->_get_param($server_info, "apache_service_name");
		$this->PARENT_OBJ->service_restart($server_info, $apache_service_name);

		return $result;
	}

	/**
	*
	*/
	function apache_virtual_host_add ($server_info, $param) {

		if(empty($param["vh_host_name"])){
			return false;
		}
		
		$vars = $this->PARENT_OBJ->_get_vars($server_info);
		$vars = my_array_merge($vars, $param);
		
		if($server_info["http_d_type"] == 1){
			$content = file_get_contents(INCLUDE_PATH."services_conf/apache2/_stpl/virtual_host.stpl");
		}
		
		if($server_info["http_d_type"] == 2){
			$content = file_get_contents(INCLUDE_PATH."services_conf/nginx_apache2/apache2/_stpl/virtual_host.stpl");
		}
		
		$content = tpl()->parse_string("", $vars, $content);
		
		$apache_path = $this->PARENT_OBJ->_get_param($server_info, "apache_conf_path");
		$result = $this->PARENT_OBJ->_add_aven($server_info, $apache_path."sites-available/", $apache_path."sites-enabled/", $param["vh_host_name"], $content, $param["vh_status"]);

		$apache_service_name = $this->PARENT_OBJ->_get_param($server_info, "apache_service_name");
		$this->PARENT_OBJ->service_restart($server_info, $apache_service_name);
		
		return $result;
	}

	/**
	*
	*/
	function apache_virtual_host_edit ($server_info, $param) {
	
		if(empty($param["vh_host_name"])){
			return false;
		}
	
		$apache_path = $this->PARENT_OBJ->_get_param($server_info, "apache_conf_path");
		$available_path = $apache_path."sites-available/";
		$enabled_path = $apache_path."sites-enabled/";

		$virtual_hosts = $this->PARENT_OBJ->_get_aven_list($server_info, $available_path, $enabled_path);
		
		if(!in_array($param["vh_host_name"], array_keys($virtual_hosts))){
			return false;
		}
		
		$content = $this->PARENT_OBJ->SSH_OBJ->read_file($server_info, $available_path.$param["vh_host_name"]);
		
		$content = preg_replace("/ServerName (.+?)\n/", "ServerName ".$param["vh_server_name"]."\n", $content);
		$content = preg_replace("/DocumentRoot (.+?)\n/", "DocumentRoot ".$param["vh_document_root"]."\n", $content);

		$result = $this->PARENT_OBJ->_add_aven($server_info, $available_path, $enabled_path, $param["vh_host_name"], $content, $param["vh_document_root"]);

		$apache_service_name = $this->PARENT_OBJ->_get_param($server_info, "apache_service_name");
		$this->PARENT_OBJ->service_restart($server_info, $apache_service_name);
		
		return $result;
	}

	/**
	*
	*/
	function apache_virtual_host_file_content_get ($server_info, $virtual_host_name) {
		$apache_path = $this->PARENT_OBJ->_get_param($server_info, "apache_conf_path");
		$available_path = $apache_path."sites-available/";
		$enabled_path = $apache_path."sites-enabled/";

		$virtual_hosts = $this->PARENT_OBJ->_get_aven_list($server_info, $available_path, $enabled_path);
		
		if(!in_array($virtual_host_name, array_keys($virtual_hosts))){
			return false;
		}
		
		$content = $this->PARENT_OBJ->SSH_OBJ->read_file($server_info, $available_path.$virtual_host_name);
		
		return $content;
	}

	/**
	*
	*/
	function apache_virtual_host_file_content_set ($server_info, $virtual_host_name, $content) {
		$apache_path = $this->PARENT_OBJ->_get_param($server_info, "apache_conf_path");
		$available_path = $apache_path."sites-available/";

		$content = str_replace("\r", "", $content);
		$this->PARENT_OBJ->SSH_OBJ->write_string($server_info, $content, $available_path.$virtual_host_name);
		
		$apache_service_name = $this->PARENT_OBJ->_get_param($server_info, "apache_service_name");
		$this->PARENT_OBJ->service_restart($server_info, $apache_service_name);
		
		return true;
	}

	/**
	*
	*/
	function nginx_virtual_hosts_get ($server_info) {

		$nginx_conf_path = $this->PARENT_OBJ->_get_param($server_info, "nginx_conf_path");
		$available_path = $nginx_conf_path."sites-available/";
		$enabled_path = $nginx_conf_path."sites-enabled/";
	
		$available_virtual_hosts = $this->PARENT_OBJ->_get_aven_list($server_info, $available_path, $enabled_path);
		
		foreach ((array)$available_virtual_hosts as $virtual_host_name => $virtual_host_value){

			$virtual_host_info = _ssh_exec($server_info, "cat ".$available_path.$virtual_host_name." | awk '{if(($1 == \"server_name\") || ($1 == \"root\")) print $1,$2}'");
			
			preg_match("/server_name (.+?);\n/", $virtual_host_info, $mathes)?$server_name = trim($mathes["1"]):"";
			preg_match("/root (.+?);\n/", $virtual_host_info, $mathes)?$document_root = trim($mathes["1"]):"";
	
			$result[$virtual_host_name] = array(
				"virtual_host_name"	=> $virtual_host_name,
				"symlink_name"		=> $virtual_host_value["symlink_name"],
				"status"			=> $virtual_host_value["status"],
				"server_name"		=> $server_name,
				"document_root"		=> $document_root,
			);
		}

		return $result;
	}

	/**
	*
	*/
	function nginx_virtual_host_enable ($server_info, $virtual_host_name) {
		$nginx_conf_path = $this->PARENT_OBJ->_get_param($server_info, "nginx_conf_path");
		$result = $this->PARENT_OBJ->_enable_aven($server_info, $nginx_conf_path."sites-available/", $nginx_conf_path."sites-enabled/", $virtual_host_name);
		
		$nginx_service_name = $this->PARENT_OBJ->_get_param($server_info, "nginx_service_name");
		$this->PARENT_OBJ->service_restart($server_info, $nginx_service_name);
		
		return $result;
	}

	/**
	*
	*/
	function nginx_virtual_host_disable ($server_info, $virtual_host_name) {

		$nginx_conf_path = $this->PARENT_OBJ->_get_param($server_info, "nginx_conf_path");
		$result = $this->PARENT_OBJ->_disable_aven($server_info, $nginx_conf_path."sites-available/", $nginx_conf_path."sites-enabled/", $virtual_host_name);

		$nginx_service_name = $this->PARENT_OBJ->_get_param($server_info, "nginx_service_name");
		$this->PARENT_OBJ->service_restart($server_info, $nginx_service_name);

		return $result;
	}
	
	/**
	*
	*/
	function nginx_virtual_host_add ($server_info, $param) {
		
		if(empty($param["vh_host_name"])){
			return false;
		}
		
		$vars = $this->PARENT_OBJ->_get_vars($server_info);
		$vars = my_array_merge($vars, $param);
		
		if($server_info["http_d_type"] == 2){
			$content = file_get_contents(INCLUDE_PATH."services_conf/nginx_apache2/nginx/_stpl/virtual_host.stpl");
		}
		
		if($server_info["http_d_type"] == 3){
			$content = file_get_contents(INCLUDE_PATH."services_conf/nginx/_stpl/virtual_host.stpl");
		}

		
		$content = tpl()->parse_string("", $vars, $content);
		
		$nginx_conf_path = $this->PARENT_OBJ->_get_param($server_info, "nginx_conf_path");
		$result = $this->PARENT_OBJ->_add_aven($server_info, $nginx_conf_path."sites-available/", $nginx_conf_path."sites-enabled/", $param["vh_host_name"], $content, $param["vh_status"]);

		$nginx_service_name = $this->PARENT_OBJ->_get_param($server_info, "nginx_service_name");
		$this->PARENT_OBJ->service_restart($server_info, $nginx_service_name);
		
		return $result;
	}
	
	/**
	*
	*/
	function nginx_virtual_host_edit ($server_info, $param) {
	/*
		if(empty($param["vh_host_name"])){
			return false;
		}
	
		$nginx_path = $this->PARENT_OBJ->_get_param($server_info, "nginx_conf_path");
		$available_path = $nginx_path."sites-available/";
		$enabled_path = $nginx_path."sites-enabled/";

		$virtual_hosts = $this->PARENT_OBJ->_get_aven_list($server_info, $available_path, $enabled_path);
		
		if(!in_array($param["vh_host_name"], array_keys($virtual_hosts))){
			return false;
		}
		
		$content = $this->PARENT_OBJ->SSH_OBJ->read_file($server_info, $available_path.$param["vh_host_name"]);
		
		//$content = preg_replace("/ServerName (.+?)\n/", "ServerName ".$param["vh_server_name"]."\n", $content);
		//$content = preg_replace("/DocumentRoot (.+?)\n/", "DocumentRoot ".$param["vh_document_root"]."\n", $content);

		$result = $this->PARENT_OBJ->_add_aven($server_info, $available_path, $enabled_path, $param["vh_host_name"], $content, $param["vh_document_root"]);

		$apache_service_name = $this->PARENT_OBJ->_get_param($server_info, "nginx_service_name");
		$this->PARENT_OBJ->service_restart($server_info, $apache_service_name);
		
		return $result;
		
		*/
	}

	/**
	*
	*/
	function nginx_virtual_host_delete ($server_info, $virtual_host_name) {
	
		$nginx_conf_path = $this->PARENT_OBJ->_get_param($server_info, "nginx_conf_path");
		$result = $this->PARENT_OBJ->_delete_aven($server_info, $nginx_conf_path."sites-available/", $nginx_conf_path."sites-enabled/", $virtual_host_name);
		
		$nginx_service_name = $this->PARENT_OBJ->_get_param($server_info, "nginx_service_name");
		$this->PARENT_OBJ->service_restart($server_info, $nginx_service_name);

		return $result;
	}

	/**
	*
	*/
	function nginx_virtual_host_file_content_get ($server_info, $virtual_host_name) {
		$nginx_conf_path = $this->PARENT_OBJ->_get_param($server_info, "nginx_conf_path");
		$available_path = $nginx_conf_path."sites-available/";
		$enabled_path = $nginx_conf_path."sites-enabled/";

		$virtual_hosts = $this->PARENT_OBJ->_get_aven_list($server_info, $available_path, $enabled_path);
		
		if(!in_array($virtual_host_name, array_keys($virtual_hosts))){
			return false;
		}
		
		$content = $this->PARENT_OBJ->SSH_OBJ->read_file($server_info, $available_path.$virtual_host_name);
		
		return $content;
	}

	/**
	*
	*/
	function nginx_virtual_host_file_content_set ($server_info, $virtual_host_name, $content) {
		$nginx_conf_path = $this->PARENT_OBJ->_get_param($server_info, "nginx_conf_path");
		$available_path = $nginx_conf_path."sites-available/";

		$content = str_replace("\r", "", $content);
		$this->PARENT_OBJ->SSH_OBJ->write_string($server_info, $content, $available_path.$virtual_host_name);
		
		$nginx_service_name = $this->PARENT_OBJ->_get_param($server_info, "nginx_service_name");
		$this->PARENT_OBJ->service_restart($server_info, $nginx_service_name);
		
		return true;
	}
}
