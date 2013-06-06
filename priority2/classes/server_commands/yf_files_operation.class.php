<?php

class yf_files_operation{

	/**
	*
	*/
	function _init () {
		$this->PARENT_OBJ	= _class(SERVER_COMMANDS_CLASS_NAME);
	}
	
	/**
	* restore dir on server from database
	*/
	function upload_dir_from_base ($server_info, $conf_id) {
		if(empty($conf_id)){
			return false;
		}
		
		$conf_info = db()->query_fetch("SELECT * FROM `".db('services_conf')."` WHERE `id` = ".intval($conf_id));
		
		if(empty($conf_info)){
			return false;
		}
		
		$Q = db()->query("SELECT * FROM `".db('services_conf_files')."` WHERE `conf_id` = ".$conf_id);
		while ($A = db()->fetch_assoc($Q)) {
			$files[$A["id"]] = $A;
		}
		
		_ssh_exec($server_info, "rm -rf ".$conf_info["dir_path"]."*");

		//create dirs
		foreach ((array)$files as $file){
			if($file["type"] !== "d"){
				continue;
			}
			
			$this->PARENT_OBJ->SSH_OBJ->mkdir($server_info, $conf_info["dir_path"].$file["path"], $file["mode"]);
			$this->PARENT_OBJ->SSH_OBJ->chown($server_info, $conf_info["dir_path"].$file["path"], $file["user"], $file["group"]);
		}
		
		//create files
		foreach ((array)$files as $file){
			if($file["type"] !== "f"){
				continue;
			}
			
			$this->PARENT_OBJ->SSH_OBJ->write_string($server_info, $file["content"], $conf_info["dir_path"].$file["path"]);
			$this->PARENT_OBJ->SSH_OBJ->chmod($server_info, $conf_info["dir_path"].$file["path"], $file["mode"]);
			$this->PARENT_OBJ->SSH_OBJ->chown($server_info, $conf_info["dir_path"].$file["path"], $file["user"], $file["group"]);
		}
		
		//create symlinks
		foreach ((array)$files as $file){
			if($file["type"] !== "l"){
				continue;
			}
			
			_ssh_exec($server_info, "ln -s ".rtrim($file["content"], "/")." ".rtrim($conf_info["dir_path"].$file["path"], "/"));
			$this->PARENT_OBJ->SSH_OBJ->chmod($server_info, $conf_info["dir_path"].$file["content"], $file["mode"]);
			$this->PARENT_OBJ->SSH_OBJ->chown($server_info, $conf_info["dir_path"].$file["content"], $file["user"], $file["group"]);
		}
		
		return true;
	}
	
	/**
	* save file content to database
	*/
	function save_dir_to_base ($server_info, $name, $remote_path, $include_pattern = "", $exclude_pattern = "", $desc) {
	
		$time = time();
		$local_path = INCLUDE_PATH."services_conf/temp_".$name.$time."/";
	
		
		db()->INSERT("services_conf", array(
			"server_id"		=> $server_info["id"],
			"service_name"	=> $name,
			"desc"			=> $desc,
			"save_time"		=> $time,
			"dir_path"		=> $remote_path,
		));
		
		$conf_id = db()->INSERT_ID();
		
		
//		_mkdir_m($local_path);
//		$this->PARENT_OBJ->SSH_OBJ->download_dir($server_info, $remote_path, $local_path, $include_pattern, $exclude_pattern);

		$files_list = $this->PARENT_OBJ->SSH_OBJ->scan_dir($server_info, $remote_path, $include_pattern, $exclude_pattern, 99);
		
		foreach ((array)$files_list as $key => $file){
		
			if($file["type"] == "f"){
//				$content = file_get_contents(str_replace($remote_path, $local_path, $key));
				$content = $this->PARENT_OBJ->SSH_OBJ->read_file($server_info, $key);
			}else{
				$content = $file["link"];
			}
		
			db()->INSERT("services_conf_files", array(
				"conf_id"		=> $conf_id,
				"path"			=> _es(str_replace($remote_path, "", $key)),
				"content"		=> _es($content),
				"type"			=> $file["type"],
				"mode"			=> $file["mode"],
				"user"			=> $file["user"],
				"group"			=> $file["group"],
			));
		}
		
		// delete local temp files
	//	$DIR_OBJ = main()->init_class("dir", "classes/");
	//	$DIR_OBJ->delete_dir($local_path, true);
	}
	
	/**
	*
	*/
	function save_remote_dir ($server_info, $remote_path, $local_path, $include_pattern = "", $exclude_pattern = "") {
	
		$files_list = $this->PARENT_OBJ->SSH_OBJ->scan_dir($server_info, $remote_path, $include_pattern, $exclude_pattern, 99);	

		$DIR_OBJ = main()->init_class("dir", "classes/");
		$DIR_OBJ->delete_dir($local_path, true);
		$DIR_OBJ->mkdir_m($local_path);
		
		$php_config_file_content .="<?php\n\n";
		$php_config_file_content .="\$remote_path = \"".$remote_path."\";\n\n";
		$php_config_file_content .="\$files_list = array(\n";
		
		
		//create folders
		foreach ((array)$files_list as $key => $file){
			if($file["type"] !== "d"){
				continue;
			}
			
			$DIR_OBJ->mkdir_m(str_replace($remote_path, $local_path, $key));
			
			$php_config_file_content .= '	$remote_path."'.str_replace($remote_path, "", $key).'"			=> "d:'.$file["mode"].":".$file["user"].":".$file["group"].'",'."\n";
		}
		
		//create files
		foreach ((array)$files_list as $key => $file){
			if($file["type"] !== "f"){
				continue;
			}

			$content = $this->PARENT_OBJ->SSH_OBJ->read_file($server_info, $key);
			file_put_contents(str_replace($remote_path, $local_path, $key), $content);
			
			$php_config_file_content .= '	$remote_path."'.str_replace($remote_path, "", $key).'"			=> "f:'.$file["mode"].":".$file["user"].":".$file["group"].'",'."\n";

		}
		
		//create symlinks
		foreach ((array)$files_list as $key => $file){
			if($file["type"] !== "l"){
				continue;
			}
			
			file_put_contents(str_replace($remote_path, $local_path, $key.".symlink"), $file["link"]);
			$php_config_file_content .= '	$remote_path."'.str_replace($remote_path, "", $key.".symlink").'"			=> "l:'.$file["mode"].":".$file["user"].":".$file["group"].'",'."\n";
		}
		
		
		$php_config_file_content .= ");\n?>";
		file_put_contents($local_path."/_files_config.php", $php_config_file_content);
	}

	/**
	* var $remote_path from including file "_files_config"
	*/
	function upload_remote_dir ($server_info, $local_path, $remote_path_force = "", $user_force = "", $group_force = "") {
	
		$vars = $this->PARENT_OBJ->_get_vars($server_info);

		$tpl_params = array(
			"replace_images"	=> 0,
			"no_cache"			=> 1,
			"no_include"		=> 1,
			"no_execute"		=> 1,
		);
		// List of denied for pre-processing file extensions
		$denied_exts = array(
			"jpg", "jpeg", "gif", "png", "bmp", "tiff", "pdf", "mp3", "avi"
		);
		
		if(file_exists($local_path."_files_config.php")){
			$conf_file_content =  file_get_contents($local_path."_files_config.php");
			$conf_file_content = tpl()->parse_string("", $vars, $conf_file_content, $tpl_params);
			@eval("?".">".$conf_file_content);
		}
		
		if(!empty($remote_path_force)){
			$remote_path = $remote_path_force;
		}
		
		if(empty($remote_path) or empty($files_list)){
			return false;
		}
		
		// очищаем папку
		_ssh_exec($server_info, "rm -rf ".$remote_path."*");
		
		foreach ((array)$files_list as $key => $settings){
		
			list($type, $mode, $user, $group) = explode(":", $settings);
			
			$files_list[$key] = array(
				"type"	=> $type,
				"mode"	=> $mode,
				"user"	=> !empty($user_force)?$user_force:$user,
				"group"	=> !empty($group_force)?$group_force:$group,
			);
		}
		
		//create dirs
		foreach ((array)$files_list as $file_name => $file){
			if($file["type"] !== "d"){
				continue;
			}
			
			//$this->PARENT_OBJ->SSH_OBJ->mkdir($server_info, $file_name, $file["mode"]);
			
			$dirs_list[$file_name] = $file["mode"];
			$chown_files_list[$file_name] = $file["user"].":".$file["group"];
		}
		
		$this->PARENT_OBJ->SSH_OBJ->mkdir($server_info, $dirs_list);

		//create files
		foreach ((array)$files_list as $file_name => $file){
			if($file["type"] !== "f"){
				continue;
			}
			
			$_file_ext = common()->get_file_ext($file_name);
			$file_content = file_get_contents(str_replace($remote_path, $local_path, $file_name));
			
			if (!in_array($_file_ext, $denied_exts)) {
				if (false !== strpos($file_content, "{")) {
					$file_content = tpl()->parse_string("", $vars, $file_content, $tpl_params);
				}
			}
			
			$writestring_files_list[$file_name] = $file_content;
			//$this->PARENT_OBJ->SSH_OBJ->write_string($server_info, $file_content, $file_name);
			
			$chmod_files_list[$file_name] = $file["mode"];
			$chown_files_list[$file_name] = $file["user"].":".$file["group"];
		}
		
		$this->PARENT_OBJ->SSH_OBJ->write_string($server_info, $writestring_files_list);
		
		//create symlinks
		foreach ((array)$files_list as $file_name => $file){
			if($file["type"] !== "l"){
				continue;
			}
			
			$file_content = file_get_contents(str_replace($remote_path, $local_path, $file_name));
			$file_content = trim(tpl()->parse_string("", $vars, $file_content, $tpl_params));
			
			$symlink_name = rtrim(str_replace(".symlink", "", $file_name), "/");
			
			$symlink_create_command[] = "ln -s ".rtrim($file_content, "/")." ".$symlink_name;
			
			$chmod_files_list[$symlink_name] = $file["mode"];
			$chown_files_list[$symlink_name] = $file["user"].":".$file["group"];
		}
		
		$this->PARENT_OBJ->SSH_OBJ->chmod($server_info, $chmod_files_list);
		$this->PARENT_OBJ->SSH_OBJ->chown($server_info, $chown_files_list);
		
		_ssh_exec($server_info, implode(" | ", (array)$symlink_create_command));
		
	}
}
