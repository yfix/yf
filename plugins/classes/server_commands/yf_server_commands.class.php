<?php

/**
*/
class yf_server_commands{

	public $mysql_perms = array(
			"Select_priv"			=> "Select table data",
			"Insert_priv"			=> "Insert table data",
			"Update_priv"			=> "Update table data",
			"Delete_priv"			=> "Delete table data",
			"Create_priv"			=> "Create tables",
			"Drop_priv"				=> "Drop tables",
			"Reload_priv"			=> "Reload grants",
			"Shutdown_priv"			=> "Shutdown database",
			"Process_priv"			=> "Manage processes",
			"File_priv"				=> "File operations",
			"Grant_priv"			=> "Grant privileges",
			"References_priv"		=> "Reference operations",
			"Index_priv"			=> "Manage indexes",
			"Alter_priv"			=> "Alter tables",
			"Show_db_priv"			=> "Show databases",
			"Super_priv"			=> "Superuser",
			"Create_tmp_table_priv"	=> "Create temp tables",
			"Lock_tables_priv"		=> "Lock tables",
			"Execute_priv"			=> "Execute",
			"Repl_slave_priv"		=> "Slave replication",
			"Repl_client_priv"		=> "Client replication",
			"Create_view_priv"		=> "Create View",
			"Show_view_priv"		=> "Show View",
			"Create_routine_priv"	=> "Create Routine",
			"Alter_routine_priv"	=> "Alter Routine",
			"Create_user_priv"		=> "Create User",
		);
		
	public $mysql_db_perms = array(
			"Select_priv"			=> "Select table data",
			"Insert_priv"			=> "Insert table data",
			"Update_priv"			=> "Update table data",
			"Delete_priv"			=> "Delete table data",
			"Create_priv"			=> "Create tables",
			"Drop_priv"				=> "Drop tables",
			"Grant_priv"			=> "Grant privileges",
			"References_priv"		=> "Reference operations",
			"Index_priv"			=> "Manage indexes",
			"Alter_priv"			=> "Alter tables",
			"Create_tmp_table_priv"	=> "Create temp tables",
			"Lock_tables_priv"		=> "Lock tables",
			"Execute_priv"			=> "Execute",
			"Create_view_priv"		=> "Create View",
			"Show_view_priv"		=> "Show View",
			"Create_routine_priv"	=> "Create Routine",
			"Alter_routine_priv"	=> "Alter Routine",
		);
		
		
	/**
	*
	*/
	function _init () {
		$this->SSH_OBJ = main()->init_class("ssh", "classes/");
		define("SERVER_COMMANDS_CLASS_NAME", "server_commands");
	}
	
	/**
	*
	*/
	function get_procces_info($server_info){
	
		$SERVER_OS = $this->SSH_OBJ->_get_remote_os($server_info);
			
		if($SERVER_OS == "FREEBSD"){
			$services = _ssh_exec($server_info, "ps h -w -A -o pid,comm,user,%cpu,%mem | awk '{if(($1 != \"PID\") && ($1 !=\"\"))print $0}'");
		}else{
			$services = _ssh_exec($server_info, "ps h -w -A -o pid,comm,user,%cpu,%mem");
		}
		
		$services = explode("\n", $services);

		foreach((array)$services as $service){
			if(empty($service)){
				continue;
			}
			$service = trim($service);
		
			$items = explode(" ", $service);
			
			$temp = array();
			foreach((array)$items as $item){
				if(strlen($item)){
					$temp[] = $item;
				}
			}
			
			if($temp[0] === ""){
				continue;
			}
			
			$temp_service["pid"] = $temp[0];
			$temp_service["command"] = $temp[1];
			$temp_service["user"] = $temp[2];
			$temp_service["cpu"] = $temp[3];
			$temp_service["mem"] = $temp[4];
			
			$result[] = $temp_service;
		}
		return $result;
	}
	
	/**
	*
	*/
	function get_cpu_info($server_info){

		$SERVER_OS = $this->SSH_OBJ->_get_remote_os($server_info);
		
		if($SERVER_OS == "FREEBSD"){
			$buffer = _ssh_exec($server_info, "sysctl -a | egrep -i 'hw.machine|hw.model|hw.ncpu'");
			$buffer = split("\n",$buffer);
			
			foreach ((array)$buffer as $info){
				if(empty($info)){
					continue;
				}
				
				list($key, $value) = explode(":", $info);
				$cpuinfo[trim($key)] = trim($value);
			}
		}else{
			$buffer = _ssh_exec($server_info, "cat /proc/cpuinfo");
			$meminfo = split("\n",$buffer);
			
			if(is_array($meminfo)) {
				foreach ((array)$meminfo as $mline){
					if(trim($mline) != "") {
						list($key, $val) = split(":",$mline);
						$cpuinfo[trim($key)] = trim($val);
					}
				}
			}
		}
		
		return $cpuinfo;
	}
	
	/**
	*
	*/
	function get_proc_count ($server_info) {
		$command = "cat /proc/cpuinfo | grep processor";
		$buffer = _ssh_exec($server_info, $command);

		if(isset($_GET["test"])){
			echo "get_proc_count command - '".$command."'\n";
			echo "get_proc_count result - '".$buffer."'\n";
		}
		
		if(empty($buffer)){
			return 0;
		}
		
		$proc_count = 0;
		foreach ((array)split("\n",$buffer) as $proc){
			if(empty($proc)){
				continue;
			}
			
			$proc_count++;
		}
		
		return $proc_count;
	}
	
	
	
	/**
	*
	*/
	function get_mem_info ($server_info) {
		$OBJ = main()->init_class("mem_info", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->get_mem_info($server_info) : false;
	}

	/**
	*
	*/
	function get_disk_info($server_info){

		$buffer = _ssh_exec($server_info, "df -k");

		$df_out = split("\n",$buffer);
		$df_num = sizeof($df_out);
		for($i=0;$i<$df_num;$i++){
		  if(ltrim($df_out[$i]) != $df_out[$i]){
			if(isset($df_out[($i-1)])){
			  $df_out[($i-1)] .= $df_out[$i];
			  unset($df_out[$i]);
			}
		  }
		}

		unset($df_out["0"]);
		
		foreach ((array)$df_out as $key => $df_line) {
			if(!empty($df_line)){
				$values = preg_split ("/[\s]+/", $df_line);
				
				$result[$key]["file_system"] = $values["0"];
				$result[$key]["size"] = $values["1"];
				$result[$key]["used"] = $values["2"];
				$result[$key]["free"] = $values["3"];
				$result[$key]["use"] = $values["4"];
				$result[$key]["mounted_on"] = $values["5"];
			}
		}
		
		return $result;
	}
	
	/**
	*
	*/
	function get_uptime($server_info){
	
		$SERVER_OS = $this->SSH_OBJ->_get_remote_os($server_info);
		
		if($SERVER_OS == "FREEBSD"){
			$result = _ssh_exec($server_info, "uptime");
			$result = explode(",", $result);
			$result = $result["0"];
			$proc_uptime = trim(substr($result, strpos($result, "up") + 2, strlen($result)));
		}else{
			$proc_uptime = trim(_ssh_exec($server_info, "cat /proc/uptime | cut -f1 -d' '"));
			
			$days = floor($proc_uptime/86400);
			$hours = floor(($proc_uptime-$days*86400)/3600);
			$minutes = str_pad(floor(($proc_uptime-$days*86400-$hours*3600)/60), 2, "0", STR_PAD_LEFT);
		}

		if(empty($proc_uptime)){
			return false;
		}

		$result  = array(
			"uptime"	=> $proc_uptime,
			"days"		=> $days,
			"hours"		=> $hours,
			"minutes"	=> $minutes,
		);
		
		return $result;
	}
	
	/**
	*
	*/
	function get_cpu_usage_info($server_info){
		$buffer = _ssh_exec($server_info, "uptime");

		$uptime = split(",",strrev($buffer));
		
		$minute_1 = str_replace("load average: ", "", strrev($uptime["2"]));
		$minute_1 = str_replace("load averages: ", "", $minute_1);
		
		$result = array(
			"1_minute"	=> $minute_1,
			"5_minute"	=> trim(strrev($uptime["1"])),
			"15_minute"	=> trim(strrev($uptime["0"])),
		);

		return $result;
	}
	
	/**
	*
	*/
	function get_run_level($server_info){
		return trim(_ssh_exec($server_info, "runlevel | awk '{print $2}'"));
	}
	
	
	/**
	*
	*/
	function get_services($server_info){
	
		$SERVER_OS = $this->SSH_OBJ->_get_remote_os($server_info);
		
		if($SERVER_OS == "FREEBSD"){
			$dir_path = $this->_get_param($server_info, "init_d_dir_path");
			$files_list = array_keys($this->SSH_OBJ->scan_dir($server_info, $dir_path, "", ""));
			$files_list = $this->SSH_OBJ->clean_path($files_list);
		}else{
			$dir_path = "/etc/rc".$this->get_run_level($server_info).".d/";

			$files_list = array_keys($this->SSH_OBJ->scan_dir($server_info, $dir_path, "/[S|K][0-9]{1,2}.+/", ""));
			$files_list = $this->SSH_OBJ->clean_path($files_list);
		}

		
		$service_status = split("\n", _ssh_exec($server_info, "ps auwwwx | grep -v grep | awk '{ a = index($0,$11); print substr($0, a, length($0)) }'"));
		
		if($SERVER_OS !== "FREEBSD"){
			$temp_file_name = "/tmp/yf_framework_temp_1";
			$decriptions = split("#%#%#%", _ssh_exec($server_info, 'unlink '.$temp_file_name.' | for SCRIPT in '.$dir_path.'* ; do if [ -f $SCRIPT ]; then echo "#%#%#% $SCRIPT" >> '.$temp_file_name.'; cat "$SCRIPT" | grep "Short-Description:" >> '.$temp_file_name.'  ;fi;done; cat '.$temp_file_name));
		}
		
		foreach ((array)$decriptions as $decription){
			if(empty($decription)){
				continue;
			}
			
			list($name, $desc) = explode("\n", $decription);
			
			if(empty($desc)){
				continue;
			}
			
			$name = str_replace($dir_path, "", trim($name));
			$desc = trim(str_replace("# Short-Description:", "", trim($desc)));

			$short_desc[$name] = $desc;
		}
		
		foreach ((array)$files_list as $file){
			$file_name = str_replace($dir_path, "", $file);
			
			if($SERVER_OS == "FREEBSD"){
				$service_name = $file_name;

			}else{
				$service_name = substr_replace($file_name, "", "0", "3");
			}
			
			
			/* old version*/
			/*
			$content = $this->SSH_OBJ->read_file($server_info, $dir_path.$file_name);
			preg_match("/Short-Description:(.+?)\n/si", $content, $short_description);
			$short_description = trim($short_description["1"]);
			//$short_description = _ssh_exec($server_info, "cat ".$dir_path.$file_name." | grep Short-Description:");
			*/
			
			$result[$service_name] = array(
				"file_name"				=> $file_name,
				"service_start_type"	=> $SERVER_OS !== "FREEBSD"?substr($file_name, "0", "1"):"",
				"service_start_count"	=> $SERVER_OS !== "FREEBSD"?substr($file_name, "1", "2"):"",
				"short_description"		=> $SERVER_OS !== "FREEBSD"?$short_desc[$file_name]:"",
				"status"				=> $this->_check_value_in_mass($service_status, $service_name)?"1":"0",
				//"status"				=> $this->service_check_status($server_info, $service_name),
			);
		}
		if(!empty($result)){
			ksort($result);
		}
		
		return $result;
	}
	
	/**
	*
	*/
	function service_check_status($server_info, $name){
		$command = "ps auwwwx | grep ".$name." | grep -v grep | awk '{ print $2 }'";
		$result = _ssh_exec($server_info, $command);
		
		if(!empty($result)){
			return "1";
		}else{
			return "0";
		}
	}
	
	/**
	*
	*/
	function service_stop($server_info, $name){
//TODO  сделать список исключений


		$init_d_dir_path = $this->_get_param($server_info, "init_d_dir_path");
	
		_ssh_exec($server_info, $init_d_dir_path.$name." stop");
		return $this->service_check_status($server_info, $name);
	}
	
	/**
	*
	*/
	function service_start($server_info, $name){
		$init_d_dir_path = $this->_get_param($server_info, "init_d_dir_path");
		
		_ssh_exec($server_info, $init_d_dir_path.$name." start");
		return $this->service_check_status($server_info, $name);
	}
	
	/**
	*
	*/
	function service_restart($server_info, $name){
		$init_d_dir_path = $this->_get_param($server_info, "init_d_dir_path");

		_ssh_exec($server_info, $init_d_dir_path.$name." restart");
		return $this->service_check_status($server_info, $name);
	}

	
	/**
	*
	*/
	function get_network_interfaces($server_info){
	
		$SERVER_OS = $this->SSH_OBJ->_get_remote_os($server_info);
			
		if($SERVER_OS == "FREEBSD"){
			$content = _ssh_exec($server_info, "ifconfig | grep \"flags=\" | awk '{print $1}'");
			$content = split(":\n", $content);
		}else{
			$content = _ssh_exec($server_info, "ifconfig -s | awk '{print $1}'");
			$content = split("\n", $content);
			unset($content["0"]);
		}

		foreach ((array)$content as $interface_name){
			if(empty($interface_name)){
				continue;
			}

			$interfaces_info = $this->get_inteface_info($server_info, trim($interface_name));
			
			if(is_array($interfaces_info["ip"])){
				foreach ((array)$interfaces_info["ip"] as $key => $value){
					$interfaces[] = array(
						"interface_name"	=> $interface_name,
						"ip"				=> $interfaces_info["ip"][$key],
						"mask"				=> $interfaces_info["mask"][$key],
					);
	
				}
			}else{
				$interfaces[] = array(
					"interface_name"	=> $interface_name,
					"ip"				=> $interfaces_info["ip"],
					"mask"				=> $interfaces_info["mask"],
				);
			}
		}
		return $interfaces;
	}
	
	/**
	*
	*/
	function get_inteface_info ($server_info, $interface_name) {
		$content = _ssh_exec($server_info, "ifconfig ".$interface_name." | grep \"inet \"");
		$content = split("\n", $content);
		
		$SERVER_OS = $this->SSH_OBJ->_get_remote_os($server_info);
			
		if($SERVER_OS == "FREEBSD"){
		// FREEBSD ######################################
			$temps = array();
			foreach ((array)$content as $ip){
				if(empty($ip)){
					continue;
				}
			
				$temps = explode(" ", $ip);
				
				$info["ip"][] = trim($temps["1"]);
				$info["mask"][] = $this->_convert_mask(trim($temps["3"]));
			}
		}else{
		// LINUX ######################################
			$temps = explode(" ", $content["0"]);
			foreach ((array)$temps as $temp){
				if(substr($temp, "0", "5") == "addr:"){
					$info["ip"] = trim(str_replace("addr:", "", $temp));
				}
				
				if(substr($temp, "0", "5") == "Mask:"){
					$info["mask"] = trim(str_replace("Mask:", "", $temp));
				}
			}
		}
		
		return $info;
	}
	
	/**
	*
	*/
	function add_vitual_ip ($server_info, $interface_name, $interface_num, $ip, $mask) {
		if(!empty($interface_num)){
			$interface_name .= ":".$interface_num;
		}
		
		$SERVER_OS = $this->SSH_OBJ->_get_remote_os($server_info);
		if($SERVER_OS == "FREEBSD"){
			$alias = " alias";
		}
		
		
		_ssh_exec($server_info, "ifconfig ".$interface_name." ".$ip." netmask ".$mask.$alias);
	}
	
	/**
	*
	*/
	function delete_vitual_ip ($server_info, $interface_name, $ip) {
		$SERVER_OS = $this->SSH_OBJ->_get_remote_os($server_info);
		
		if($SERVER_OS == "FREEBSD"){ 
			_ssh_exec($server_info, "ifconfig ".$interface_name." ".$ip." delete");
		}else{
			_ssh_exec($server_info, "ifconfig ".$interface_name." down");
		}
	}
	
	/**
	*
	*/
	function get_hosts($server_info){
		$buffer = _ssh_exec($server_info, "cat /etc/hosts");
		$buffer = split("\n", $buffer);
		
		foreach ((array)$buffer as $host){
			if(($host == "") or (substr($host, "0", "1") == "#")){
				continue;
			}
			
			$host = str_replace("	", " ", $host);
			$space_pos = strpos($host, " ");
			
			$ip = trim(substr($host, "0", $space_pos));
			$host_name = trim(substr($host, $space_pos, strlen($host) - $space_pos));
			
			$hosts[] = array(
				"ip"		=> $ip,
				"host_name"	=> $host_name,
			); 
		}
		
		return $hosts;
	}
	
	/**
	*
	*/
	function apache_conection_count ($server_info) {
	
		$command = "netstat -anpt | grep :80[^0-9] | wc -l";
		$result = intval(_ssh_exec($server_info, $command));
		
		
		if(isset($_GET["test"])){
			echo "apache_conection_count command - ".$command."\n";
			echo "apache_conection_count result - ".$result."\n";
		}
		
		return $result;
	}
	
	/**
	*
	*/
	function apache_virtual_hosts_get ($server_info) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->apache_virtual_hosts_get($server_info) : false;
	}
	
	/**
	*
	*/
	function apache_virtual_host_enable ($server_info, $virtual_host_name) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->apache_virtual_host_enable($server_info, $virtual_host_name) : false;
	}
	
	/**
	*
	*/
	function apache_virtual_host_disable ($server_info, $virtual_host_name) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->apache_virtual_host_disable($server_info, $virtual_host_name) : false;
	}
	
	/**
	*
	*/
	function apache_virtual_host_delete ($server_info, $virtual_host_name) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->apache_virtual_host_delete($server_info, $virtual_host_name) : false;
	}
	
	/**
	*
	*/
	function apache_virtual_host_add ($server_info, $param) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->apache_virtual_host_add($server_info, $param) : false;
	}
	
	/**
	*
	*/
	function apache_virtual_host_edit ($server_info, $param) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->apache_virtual_host_edit($server_info, $param) : false;
	}
	
	/**
	*
	*/
	function apache_virtual_host_file_content_get ($server_info, $virtual_host_name) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->apache_virtual_host_file_content_get($server_info, $virtual_host_name) : false;
	}
	
	/**
	*
	*/
	function apache_virtual_host_file_content_set ($server_info, $virtual_host_name, $content) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->apache_virtual_host_file_content_set($server_info, $virtual_host_name, $content) : false;
	}
	
	/**
	*
	*/
	function nginx_virtual_hosts_get ($server_info) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->nginx_virtual_hosts_get($server_info) : false;
	}
	
	/**
	*
	*/
	function nginx_virtual_host_enable ($server_info, $virtual_host_name) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->nginx_virtual_host_enable($server_info, $virtual_host_name) : false;
	}
	
	/**
	*
	*/
	function nginx_virtual_host_disable ($server_info, $virtual_host_name) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->nginx_virtual_host_disable($server_info, $virtual_host_name) : false;
	}
	
	/**
	*
	*/
	function nginx_virtual_host_add ($server_info, $param) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->nginx_virtual_host_add($server_info, $param) : false;
	}
	
	/**
	*
	*/
	function nginx_virtual_host_delete ($server_info, $virtual_host_name) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->nginx_virtual_host_delete($server_info, $virtual_host_name) : false;
	}
	
	/**
	*
	*/
	function nginx_virtual_host_file_content_get ($server_info, $virtual_host_name) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->nginx_virtual_host_file_content_get($server_info, $virtual_host_name) : false;
	}
	
	/**
	*
	*/
	function nginx_virtual_host_file_content_set ($server_info, $virtual_host_name, $content) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->nginx_virtual_host_file_content_set($server_info, $virtual_host_name, $content) : false;
	}
	
	/**
	*
	*/
	function virtual_hosts_get ($server_info) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->virtual_hosts_get($server_info) : false;
	}
	
	/**
	*
	*/
	function virtual_host_add ($server_info, $param) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->virtual_host_add($server_info, $param) : false;
	}
	
	/**
	*
	*/
	function virtual_host_enable ($server_info, $virtual_host_name) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->virtual_host_enable($server_info, $virtual_host_name) : false;
	}
	
	/**
	*
	*/
	function virtual_host_disable ($server_info, $virtual_host_name) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->virtual_host_disable($server_info, $virtual_host_name) : false;
	}
	
	/**
	*
	*/
	function virtual_host_delete ($server_info, $virtual_host_name) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->virtual_host_delete($server_info, $virtual_host_name) : false;
	}
	
	/**
	*
	*/
//TODO доделать; что бы и в апаче работало и nginx
	function virtual_host_edit ($server_info, $param) {
		$OBJ = main()->init_class("virtual_host", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->virtual_host_edit($server_info, $param) : false;
	}
	
	/**
	*
	*/
	function mysql_get_statistic ($server_info) {

		$server_info["mysql_pswd"] = str_replace('"', '\"', $server_info["mysql_pswd"]);
		$command = "mysqladmin -u ".$server_info["mysql_user"]." --password=\"".$server_info["mysql_pswd"]."\" status";
		$info = _ssh_exec($server_info, $command);
		
		if(isset($_GET["test"])){
			echo "mysql_get_statistic command - ".$command."\n";
			echo "mysql_get_statistic result - ".$info."\n";
		}

		$pos = strpos($info, "Access denied");
		if ($pos !== false) {
			return "Access denied";
		}

		$info = explode(" ", $info);
		
		if(empty($info["1"])){
			return false;
		}
		
		$result = array(
			"uptime"				=> intval($info["1"]),
			"threads"				=> $info["4"],
			"questions"				=> $info["7"],
			"slow_queries"			=> $info["11"],
			"opens"					=> $info["14"],
			"flush_tables"			=> $info["18"],
			"open_tables"			=> $info["22"],
			"queries_per_second_avg"=> intval($info["28"]),
		);
		
		return $result;
	}
	
	/**
	*
	*/
	function mysql_get_databases_list ($server_info) {
		$bases = _ssh_exec($server_info, "mysqlshow -v -u ".$server_info["mysql_user"]." --password='".$server_info["mysql_pswd"]."' | grep -v \"+\| Databases\|rows in set\" | awk '{print $2,$4}'");
		$bases = explode("\n", $bases);
		
		foreach ((array)$bases as $base){
			if(empty($base)){
				continue;
			}
			
			list($base_name, $num_tables_in_base) = explode(" ", trim($base));
		
			$result[$base_name] = array(
				"base_name"				=> $base_name,
				"num_tables_in_base"	=> $num_tables_in_base,
			);
		}
		
		return $result;
	}
	
	/**
	*
	*/
	function get_mysql_processlist ($server_info) {
		if (substr(php_uname(), 0, 7) == "Windows"){ 
//			return "Windows :(";
		}
		
		if (substr(php_uname(), 0, 7) == "Windows"){ 
			$result = file_get_contents("../../test.xml");
		}else{
			$command = "echo \"show full processlist;\" | mysql --xml -u ".$server_info["mysql_user"]." -p\"".$server_info["mysql_pswd"]."\" ".$server_info["mysql_default_db"];
			$result = _ssh_exec($server_info, $command);
		}
		
		// отключаем внешний вывод ошибок
		libxml_use_internal_errors(true);
			
		$xml = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA );
		$xml = $this->_to_array($xml);
		
		foreach ((array)$xml["row"] as $info){
			$info = $info["field"];
			
			if($info[4] == "Sleep"){
				continue;
			}
			
			if(is_array($info[6])){
				$info[6] = "NULL";
			}
			if(is_array($info[7])){
				$info[7] = "";
			}
			
			$results[$info[0]] = array(
				"id"		=> $info[0],
				"user"		=> $info[1],
				"host"		=> $info[2],
				"db"		=> $info[3],
				"command"	=> $info[4],
				"time"		=> $info[5],
				"state"		=> $info[6],
				"info"		=> $info[7],
			);
		}
		
		if(isset($_GET["test"])){
			echo "get_mysql_processlist command - ".$command."\n";
			echo "get_mysql_processlist result - ".$result."\n";
			echo "get_mysql_processlist array - ".$xml."\n";
		}
		
		return $results;
		
	}
	
	/**
	*
	*/
	function mysql_kill_processlist ($server_info, $id) {
		$command = "mysqladmin -u ".$server_info["mysql_user"]." --password=\"".$server_info["mysql_pswd"]."\" kill ".$id;
		$result = _ssh_exec($server_info, $command);
		return $result;
	}
	
	

	
	/**
	*
	*/
	function mysql_add_db ($server_info, $table_name) {

		if(strpos($table_name, " ") !== false){
			return false;
		}
	
		$result = _ssh_exec($server_info, "mysqladmin -u ".$server_info["mysql_user"]." --password='".$server_info["mysql_pswd"]."' create \"".$table_name."\"");
		
		if(empty($result)){
			return true;
		}else{
			return false;
		}
	}

	
	/**
	*
	*/
	function mysql_delete_db ($server_info, $table_name) {
	
		if(strpos($table_name, " ") !== false){
			return false;
		}

		$result = _ssh_exec($server_info, "mysqladmin --force -u ".$server_info["mysql_user"]." --password='".$server_info["mysql_pswd"]."' drop \"".$table_name."\"");
		
		return true;
	}


	/**
	*
	*/
	function mysql_execute_sql ($server_info, $sql, $debug = false, $human = true) {
	
		if(!empty($server_info["mysql_pswd"])){
			$server_info["mysql_pswd"] = str_replace('"', '\"', $server_info["mysql_pswd"]);
			$passwd = ' -p"'.$server_info["mysql_pswd"].'"';
		}
		
		if(empty($server_info["mysql_default_db"])){
			$result[0]["error"] = 'no set "mysql_default_db"';
			return $result;
		}
		
		
		$sql = rtrim($sql, ";");
		
		if($human){
			$human_str = "-H --verbose";
		}
		$str = "echo \"".$sql."\" | mysql ".$human_str." -u ".$server_info["mysql_user"].$passwd." ".$server_info["mysql_default_db"];
		
		if($debug){
			echo $str."\n\n";
		}
		
		$result = _ssh_exec($server_info, $str);
		return $result;
/*		
		$result = explode("#new_str#", $result);
		
		foreach ((array)$result as $string){
			if(empty($string)){
				continue;
			}
			
			foreach ((array)$string as $string_info){
				
				$string_info_items = explode("\n", $string_info);
				
				$string_setting = array();	
				foreach ((array)$string_info_items as $item){
					$item = trim($item);
					if(empty($item)){
						continue;
					}
					
					list($key, $value) = explode(":", $item);
					$string_setting[trim($key)] = trim($value);
				}
				$result_sql[] = $string_setting;
			}
			
		}
		
		
		return $result_sql;
		*/

	}

	
	/**
	*
	*/
	function mysql_get_user_list ($server_info) {
		$result = _ssh_exec($server_info, "echo \"select * from user \G;\" | mysql -u ".$server_info["mysql_user"]." -p".$server_info["mysql_pswd"]." mysql | awk '{if($1 == \"***************************\") print \"#new_user_heare#\"; else print $0}'");	
		$result = explode("#new_user_heare#", $result);
		
		foreach ((array)$result as $user){
			if(empty($user)){
				continue;
			}
			
			foreach ((array)$user as $user_info){
				
				$user_info_items = explode("\n", $user_info);
				
				$user_setting = array();	
				foreach ((array)$user_info_items as $item){
					if(empty($item)){
						continue;
					}
					list($key, $value) = explode(":", $item);
					$user_setting[trim($key)] = trim($value);
				}
			}
			
			$user_settings[] = $user_setting;
			
		}
		return $user_settings;
	}
	
	/**
	*
	*/
	function mysql_add_user ($server_info, $user, $host, $password, $perms) {

		if(!empty($perms)){
			foreach ((array)$perms as $key => $value){
				$perms_key .= ",".$key;
				$perms_val .= ",'".$value."'";
			}
		}
		
		$result = _ssh_exec($server_info, "echo \"INSERT INTO user (Host,User,Password".$perms_key.") VALUES ('".$host."','".$user."',PASSWORD('".$password."')".$perms_val."); FLUSH PRIVILEGES; \" | mysql -u ".$server_info["mysql_user"]." -p".$server_info["mysql_pswd"]." mysql");
		if(empty($result)){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	*
	*/
	function mysql_delete_user ($server_info, $user, $host) {
		$result = _ssh_exec($server_info, "echo \"DELETE FROM user WHERE user='".$user."' AND host='".$host."'; FLUSH PRIVILEGES; \" | mysql -u ".$server_info["mysql_user"]." -p".$server_info["mysql_pswd"]." mysql");

		if(empty($result)){
			return true;
		}else{
			return false;
		}
	}

	/**
	*
	*/
	function mysql_edit_user ($server_info, $user, $host, $password, $perms) {

		foreach ((array)$perms as $key => $value){
			$perms_key .= ",".$key;

			$perms_string .= ",".$key."='".$value."'";
		}

		$result = _ssh_exec($server_info, "echo \"UPDATE user SET Host='".$host."',User='".$user."',Password=PASSWORD('".$password."')".$perms_string." WHERE user='".$user."' AND host='".$host."'; FLUSH PRIVILEGES; \" | mysql -u ".$server_info["mysql_user"]." -p".$server_info["mysql_pswd"]." mysql");
		
		if(empty($result)){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	*
	*/
	function mysql_set_db_priv ($server_info, $host, $db, $user, $perms) {
		
		if(!empty($perms)){
			foreach ((array)$perms as $key => $value){
				$perms_key .= ",".$key;
				$perms_val .= ",'".$value."'";
			}
		}
		
		$result = _ssh_exec($server_info, "echo \"INSERT INTO db (Host,Db,User".$perms_key.") VALUES ('".$host."','".$db."','".$user."'".$perms_val."); FLUSH PRIVILEGES; \" | mysql -u ".$server_info["mysql_user"]." -p".$server_info["mysql_pswd"]." mysql");

		if(empty($result)){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	*
	*/
	function mysql_delete_db_priv ($server_info, $host, $db, $user) {
		
		$result = _ssh_exec($server_info, "echo \"DELETE FROM db WHERE user='".$user."' AND Db='".$db."' AND host='".$host."'; FLUSH PRIVILEGES; \" | mysql -u ".$server_info["mysql_user"]." -p".$server_info["mysql_pswd"]." mysql");

		if(empty($result)){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	*
	*/
	function mysql_backup ($server_info, $database, $save_dir) {
	
		$backup_file_path = $save_dir."backup.sql.gz";
		$server_info["mysql_pswd"] = str_replace('"', '\"', $server_info["mysql_pswd"]);
		$command = "mysqldump -u ".$server_info["mysql_user"]." -h localhost --password=\"".$server_info["mysql_pswd"]."\" ".$database." | gzip -5 > ".$backup_file_path;
		
		$this->SSH_OBJ->rmdir($server_info, $save_dir);
		$this->SSH_OBJ->mkdir_m($server_info, $save_dir, 777);

		$info = _ssh_exec($server_info, $command);
		return $info;
	}
	
	
	/**
	*
	*/
	
	function cron_get_jobs ($server_info) {
		$SERVER_OS = $this->SSH_OBJ->_get_remote_os($server_info);

		if($SERVER_OS == "FREEBSD"){
			$content = _ssh_exec($server_info, 'cat /etc/crontab | awk \'{a = index($0,$7); if((substr($1, 0, 1) != "#") && (NF > 5)) print $1"#|#"$2"#|#"$3"#|#"$4"#|#"$5"#|#"$6"#|#"substr($0,a,length($0))}\'');
			$content = explode("\n", $content);
			
			$i = 0;
			foreach ((array)$content as $job){
				if(empty($job)){
					continue;
				}
				$i++;

				$job_info = explode("#|#", $job);
				
				$result[$i] = array(
					"minutes"	=> $job_info["0"],
					"hours"		=> $job_info["1"],
					"days"		=> $job_info["2"],
					"months"	=> $job_info["3"],
					"weekdays"	=> $job_info["4"],
					"user"		=> $job_info["5"],
					"command"	=> $job_info["6"],
				);
			}
			
		}else{
			$dir_path = "/etc/cron.d/";
			$files_list = array_keys($this->SSH_OBJ->scan_dir($server_info, $dir_path, "", ""));
			$files_list = $this->SSH_OBJ->clean_path($files_list);
			
			
			foreach ((array)$files_list as $file_name){
				$job_name = str_replace($dir_path, "", $file_name);
				$content = _ssh_exec($server_info, 'cat '.$file_name.' | awk \'{a = index($0,$7); if((substr($1, 0, 1) != "#") && (NF > 5)) print $1"#|#"$2"#|#"$3"#|#"$4"#|#"$5"#|#"$6"#|#"substr($0,a,length($0))}\'');
				
				if(empty($content)){
					continue;
				}
				
				$job_info = explode("#|#", $content);
				
				$result[$job_name] = array(
					"minutes"	=> $job_info["0"],
					"hours"		=> $job_info["1"],
					"days"		=> $job_info["2"],
					"months"	=> $job_info["3"],
					"weekdays"	=> $job_info["4"],
					"user"		=> $job_info["5"],
					"command"	=> $job_info["6"],
				);
			}
		}

		
		
		return $result;
	}
	
	/**
	*
	*/
	function cron_add_job ($server_info, $job_name, $minutes, $hours, $days, $months, $weekdays, $command, $user_name = "root") {
	
		$SERVER_OS = $this->SSH_OBJ->_get_remote_os($server_info);
		$content = $minutes."	".$hours."	".$days."	".$months."	".$weekdays."	".$user_name."	".$command;

		if($SERVER_OS == "FREEBSD"){
			$file_content = $this->SSH_OBJ->read_file($server_info, "/etc/crontab");
			$this->SSH_OBJ->write_string($server_info, $file_content.$content, "/etc/crontab");
		}else{
			$this->SSH_OBJ->write_string($server_info, $content, "/etc/cron.d/".$job_name);
		}
	}

	/**
	*
	*/
	function cron_edit_job ($server_info, $job_name, $minutes, $hours, $days, $months, $weekdays, $command, $user_name = "root") {

		$SERVER_OS = $this->SSH_OBJ->_get_remote_os($server_info);

		if($SERVER_OS == "FREEBSD"){
		
		}else{
			$this->SSH_OBJ = main()->init_class("ssh", "classes/");
			$content = $this->SSH_OBJ->read_file($server_info, "/etc/cron.d/".$job_name);
		
			$old_command = trim(_ssh_exec($server_info, 'cat /etc/cron.d/'.$job_name.' | awk \'{a = index($0,$7); if(($1 != "#") && (NF > 5)) print $0}\''));
			$new_command = $minutes." ".$hours." ".$days." ".$months." ".$weekdays." ".$user_name." ".$command;
			
			$content = str_replace($old_command, $new_command, $content);
			
			$this->SSH_OBJ->write_string($server_info, $content, "/etc/cron.d/".$job_name);
		}
	}

	/**
	*
	*/
	function cron_delete_job ($server_info, $job_name) {
		$SERVER_OS = $this->SSH_OBJ->_get_remote_os($server_info);

		if($SERVER_OS == "FREEBSD"){
			$content = $this->SSH_OBJ->read_file($server_info, "/etc/crontab");
			$content = explode("\n", $content);
			
			$i = 1;
			foreach ((array)$content as $key => $line){
				$columns = count(explode("	", $line));
				
				if((substr($line, "0", "1") == "#") OR ($columns < 6)){
					continue;
				}
				
				if($job_name == $i){
					unset($content[$key]);
					break;	
				}
				
				$i++;	
			}

			$content = implode("\n", $content);
			$this->SSH_OBJ->write_string($server_info, $content, "/etc/crontab");
		}else{
			_ssh_exec($server_info, "unlink /etc/cron.d/".$job_name);
		}
	}
	
	
	/**
	*
	*/
	function upload_dir_from_base ($server_info, $conf_id) {
		$OBJ = main()->init_class("files_operation", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->upload_dir_from_base($server_info) : false;
	}

	/**
	*
	*/
	function save_dir_to_base ($server_info, $name, $remote_path, $include_pattern = "", $exclude_pattern = "", $desc) {
		$OBJ = main()->init_class("files_operation", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->save_dir_to_base($server_info, $name, $remote_path, $include_pattern = "", $exclude_pattern = "", $desc) : false;
	}

	/**
	*
	*/
	function save_remote_dir ($server_info, $remote_path, $local_path, $include_pattern = "", $exclude_pattern = "") {
		$OBJ = main()->init_class("files_operation", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->save_remote_dir($server_info, $remote_path, $local_path, $include_pattern = "", $exclude_pattern = "") : false;
	}
	
	/**
	*
	*/
	function upload_remote_dir ($server_info, $local_path, $remote_path_force = "", $user_force = "", $group_force = "") {
		$OBJ = main()->init_class("files_operation", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->upload_remote_dir($server_info, $local_path, $remote_path_force = "", $user_force = "", $group_force = "") : false;
	}
	
	/**
	*
	*/
	function create_system_user ($account_info) {
	
		$server_info = _server_info($account_info["server_id"]);
		
		if(empty($account_info["account_name"])){
			return false;
		}
		
		// в имени недолжно быть точки
		$dot_pos = strpos($account_info["account_name"], ".");
		if ($dot_pos === false) {
		} else {
			trigger_error("server_commands: ".__FUNCTION__.", user name can not exist '.'", E_USER_WARNING);
			return false;
		}
		
		// проверка уникальности имени
		if(in_array($account_info["account_name"], array_keys($this->get_system_users($server_info)))){
			trigger_error("server_commands: ".__FUNCTION__.", user with name ".$account_info["account_name"]." already exist", E_USER_WARNING);
			return false;
		}
		
		$SERVER_OS = $this->SSH_OBJ->_get_remote_os($server_info);

		if($SERVER_OS == "FREEBSD"){
			$result = _ssh_exec($server_info, "pw useradd ".$account_info["account_name"]);
		}else{
			$result = _ssh_exec($server_info, "useradd ".$account_info["account_name"]);
		}

		$this->_check_scripts_on_server($server_info);
		
		$scrips_dir_path = $this->_get_scripts_dir_path($server_info);
		
		$result = _ssh_exec($server_info, $scrips_dir_path."autopasswd ".$account_info["account_name"]." ".$account_info["account_pswd"]);
		
		if(!in_array($account_info["account_name"], array_keys($this->get_system_users($server_info)))){
			trigger_error("server_commands: ".__FUNCTION__.", user not create", E_USER_WARNING);
			return false;
		}
		
		return true;
	}
	
	/**
	*
	*/
	function delete_system_user ($account_info) {
		
		$server_info = _server_info($account_info["server_id"]);
		
		if(empty($account_info["account_name"])){
			return false;
		}
		
		$SERVER_OS = $this->SSH_OBJ->_get_remote_os($server_info);


		if($SERVER_OS == "FREEBSD"){
			$result = _ssh_exec($server_info, "pw userdel ".$account_info["account_name"]." -r");
		}else{
			$result = _ssh_exec($server_info, "userdel -f -r ".$account_info["account_name"]);
		}
		
		if(in_array($account_info["account_name"], array_keys($this->get_system_users($server_info)))){
			trigger_error("server_commands: ".__FUNCTION__.", user not delete", E_USER_WARNING);
			return false;
		}
		
		return true;
	}
	
	
	/**
	*
	*/
	function create_account_skel ($account_info) {
	
		if(empty($account_info["account_name"])){
			return false;
		}
	
		$server_info = _server_info($account_info["server_id"]);
	
		$this->upload_remote_dir($server_info, INCLUDE_PATH."uploads/host_skel/", $this->_get_param($server_info, "home_dir_path").$account_info["account_name"]."/", $account_info["account_name"], $account_info["account_name"]);
		
		return true;
	}
	
	/**
	*
	*/
	function get_system_users ($server_info, $show_only_upanel_user = false) {
		$users = _ssh_exec($server_info, "cat /etc/passwd");
		
		$tmp_array = explode("\n", trim($users));
		
		foreach ((array)$tmp_array as $v) {
			$user_info = explode(":", $v);
			
			if(substr($user_info["0"], "0", "1") == "#"){
				continue;
			}
			
			if($show_only_upanel_user){
				if (!preg_match('#u[0-9]{5}#i', $user_info["0"])) {
					continue;
				}
			}
			
			$users_array[$user_info["0"]] = array(
				"user_name"	=> $user_info["0"],
				"user_id"	=> $user_info["2"],
				"group_id"	=> $user_info["3"],
				"home_dir"	=> $user_info["5"],
			);
		}
		
		asort($users_array);
		
		return $users_array;
	}
	
	/**
	*
	*/
	function get_system_groups ($server_info) {
		$users = _ssh_exec($server_info, "cat /etc/group");
		
		$tmp_array = explode("\n", trim($users));
		
		foreach ((array)$tmp_array as $v) {
			$group_info = explode(":", $v);

			if(substr($group_info["0"], "0", "1") == "#"){
				continue;
			}
			
			$group_array[$group_info["0"]] = array(
				"group_name"	=> $group_info["0"],
				"group_id"		=> $group_info["2"],
			);
		}
		
		asort($group_array);
		
		return $group_array;
	}
	
	/**
	*
	*/
	function get_system_user_info($account_info) {
		$server_info = _server_info($account_info["server_id"]);
		
		$user_info = _ssh_exec($server_info, "cat /etc/passwd | awk -F: '{if($1==\"".$account_info["account_name"]."\") print $0}'");
		
		$user_info = explode(":", $user_info);
		
		$result = array(
			"user_name"	=> $user_info["0"],
			"user_id"	=> $user_info["2"],
			"group_id"	=> $user_info["3"],
			"home_dir"	=> $user_info["5"],
		);
		
		return $result;
	}
	
	/**
	*
	*/
	function domain_get_list ($server_info) {
		$OBJ = main()->init_class("domains", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->domain_get_list($server_info) : false;
	}

	/**
	*
	*/
	function domain_add ($server_info, $param) {
		$OBJ = main()->init_class("domains", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->domain_add($server_info, $param) : false;
	}

	/**
	*
	*/
	function domain_delete ($server_info, $domain_name) {
		$OBJ = main()->init_class("domains", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->domain_delete($server_info, $domain_name) : false;
	}

	/**
	*
	*/
	function domain_get_subdomains_list ($server_info, $domain_name) {
		$OBJ = main()->init_class("domains", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->domain_get_subdomains_list($server_info, $domain_name) : false;
	}

	/**
	*
	*/
	function domain_add_subdomain ($server_info, $domain_name, $sub_domain_name) {
		$OBJ = main()->init_class("domains", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->domain_add_subdomain($server_info, $domain_name, $sub_domain_name) : false;
	}

	/**
	*
	*/
	function domain_delete_subdomain ($server_info, $domain_name, $sub_domain_name) {
		$OBJ = main()->init_class("domains", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->domain_delete_subdomain($server_info, $domain_name, $sub_domain_name) : false;
	}

	/**
	*
	*/
	function domain_named_checkconf ($server_info) {
		$OBJ = main()->init_class("domains", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->domain_named_checkconf($server_info) : false;
	}

	/**
	*
	*/
	function domain_named_check_zone ($server_info, $domain) {
		$OBJ = main()->init_class("domains", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->domain_named_check_zone($server_info, $domain) : false;
	}

	/**
	*
	*/
	function domain_named_check_all_zone ($server_info) {
		$OBJ = main()->init_class("domains", "classes/server_commands/");
		return is_object($OBJ) ? $OBJ->domain_named_check_all_zone($server_info) : false;
	}
	
	/**
	*
	*/
	function log_get_file ($server_info, $file_path, $revert = false, $page, $lines_on_page, $include = "", $exclude = "") {
	
		empty($lines_on_page)?$lines_on_page = "10":"";
		empty($page)?$page = "1":"";
	
		if(empty($file_path)){
			return false;
		}
	
		$is_file_exist = $this->SSH_OBJ->file_exists($server_info, $file_path);
		
		if(!$is_file_exist){
			return false;
		}
		
		$lines_in_file = intval(_ssh_exec($server_info, "wc -l ".$file_path));
		
		if(!empty($exclude)){
			$filter = " | grep -v '".$this->SSH_OBJ->_prepare_text($exclude)."'";
		}
		
		if(!empty($include)){
			$filter .= " | grep '".$this->SSH_OBJ->_prepare_text($include)."'";
		}
		
		if($lines_on_page < $lines_in_file){
			$pages = ceil($lines_in_file/$lines_on_page);	
		}else{
			$pages = 1;	
		}
		
		$first_line = (($page - 1) * $lines_on_page) + 1;
		$last_line = $page * $lines_on_page;

		
		if($revert){
			$command = "tac ".$file_path." | sed -n '".$first_line.",".$last_line."p'".$filter;
		}else{
			$command = "sed -n '".$first_line.",".$last_line."p' ".$file_path.$filter;
		}
		
		$content = _ssh_exec($server_info, $command);
		
		$result = array(
			"content"		=> $content,
			"pages"			=> $pages,
			"total_lines"	=> $lines_in_file,
		);
		
		return $result;
	}

	
	/**
	******************************************************************
	*/
	
	/**
	*
	*/
	function _save_backup ($server_info, $files_name) {
	
		if(empty($files_name)){
			return;
		}
		
		foreach ((array)$files_name as $file){
			$result = _ssh_exec($server_info, "cat ".$file." > ".$file.".backup");	
		}
	}
	
	/**
	*
	*/
	function _restore_backup ($server_info, $files_name) {

		if(empty($files_name)){
			return;
		}
		
		foreach ((array)$files_name as $file){
			$result = _ssh_exec($server_info, "cat ".$file.".backup > ".$file);
			$this->SSH_OBJ->unlink($server_info, $file.".backup");
		}
	}
	
	/**
	*
	*/
	function _delete_backup ($server_info, $files_name) {

		if(empty($files_name)){
			return;
		}
		
		foreach ((array)$files_name as $file){
			$this->SSH_OBJ->unlink($server_info, $file.".backup");
		}
	}
	
	/**
	*
	*/
	function _check_value_in_mass ($mass, $check_value) {
		foreach ((array)$mass as $value){
			if(stripos($value, $check_value) !== false){
				return 1;
			}
		}
		
		return 0;
	}
	
	
	/**
	*  
	*/
	function _get_param ($server_info, $param_name) {
		
		$param_info = db()->query_fetch("SELECT id FROM ".db('os_params')." WHERE name = '".$param_name."'");
		$param_value = db()->query_fetch("SELECT value FROM ".db('os_params_value')." WHERE param_id = ".intval($param_info["id"])." AND os_id = ".intval($server_info["server_os"]));
		
		return $param_value["value"];
	}
	
	/**
	*  
	*/
	function _get_params_for_os ($os_id) {
		
		$param_info = db()->query_fetch("SELECT id FROM ".db('os_params')." WHERE name = '".$param_name."'");
		
		$Q = db()->query("SELECT id,name FROM ".db('os_params')."");
		while ($A = db()->fetch_assoc($Q)) {
			$params[$A["id"]] = $A["name"];
		}
		
		$Q = db()->query("SELECT param_id,value FROM ".db('os_params_value')." WHERE os_id = ".$os_id);
		while ($A = db()->fetch_assoc($Q)) {
			$result[$params[$A["param_id"]]] = $A["value"];
		}
		
		return $result;
	}
	
	/**
	*
	*/
	function _get_vars ($server_info) {
		
		$replace = $this->_get_params_for_os($server_info["server_os"]);
		
		$replace2 = array(
			"server_id"				=> $server_info["id"],
			"server_base_ip"		=> $server_info["base_ip"],
			"server_name"			=> $server_info["name"],
			"server_os_id"			=> $server_info["server_os"],
		);
		
		$replace = my_array_merge($replace, $replace2);
		
		return $replace;
	}
	
	/**
	*
	*/
	function _check_scripts_on_server ($server_info) {
		
		$scripts_dir_path = $this->_get_scripts_dir_path($server_info);
		
		$is_exist = $this->SSH_OBJ->file_exists($server_info, $scripts_dir_path);

		if(!$is_exist){
			$this->SSH_OBJ->upload_dir($server_info, INCLUDE_PATH."uploads/scripts/", $scripts_dir_path);
			$result = _ssh_exec($server_info, "chmod 0777 ".$scripts_dir_path."*");
		}
	}
	
	function _get_scripts_dir_path ($server_info){

		if($server_info["ssh_user"] == "root"){
			$scripts_dir_path = "/root/scripts/";
		}else{
			$scripts_dir_path = $this->_get_param("home_dir_path").$server_info["ssh_user"]."/scripts/";
		}

		return $scripts_dir_path;
	}

	
	/**
	* 	AVailable-ENabled cofig system
	*/
	function _get_aven_list ($server_info, $available_path, $enabled_path) {

		// get available
		$available_list = array_keys($this->SSH_OBJ->scan_dir($server_info, $available_path, "", ""));
		$available_list = $this->SSH_OBJ->clean_path($available_list);
		$available_list = str_replace($available_path, "", $available_list);
		
		// get enabled
		$SERVER_OS = $this->SSH_OBJ->_get_remote_os($server_info);
		
		if($SERVER_OS == "FREEBSD"){ 
			$command = "ls -l ".$enabled_path." | grep -v 'total ' | awk '{print $11,$9}'";
		}else{
			$command = "ls -l ".$enabled_path." | grep -v 'total ' | awk '{print $10,$8}'";
		}

		$enabled_list = _ssh_exec($server_info, $command);
		$enabled_list = split("\n",$enabled_list);
		$enabled_list = str_replace($available_path, "", $enabled_list);
		
		
		foreach ((array)$enabled_list as $host_name){
			if(empty($host_name)){
				continue;
			}
			list($virtual_host_name, $link) = explode(" ", $host_name);
			$symlink[$virtual_host_name] = $link;
		}

		foreach ((array)$available_list as $virtual_host_name){
			$result[$virtual_host_name] = array(
				"virtual_host_name"	=> $virtual_host_name,
				"symlink_name"		=> $symlink[$virtual_host_name],
				"status"			=> !empty($symlink[$virtual_host_name])?"1":"0",
			);
		}

		return $result;
	}
	
	/**
	* 	AVailable-ENabled cofig system
	*/
	function _enable_aven ($server_info, $available_path, $enabled_path, $virtual_host_name) {
	
		if(!$this->SSH_OBJ->file_exists($server_info, $available_path.$virtual_host_name)){
			return false;
		}
		
		$symlink_name = rtrim($enabled_path.$virtual_host_name, "/");
		$result = _ssh_exec($server_info, " ln -s ".rtrim($available_path.$virtual_host_name, "/")." ".$symlink_name);
		
		if(empty($result)){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	* 	AVailable-ENabled cofig system
	*/
	function _disable_aven ($server_info, $available_path, $enabled_path, $virtual_host_name) {

		if(!$this->SSH_OBJ->file_exists($server_info, $available_path.$virtual_host_name)){
			return false;
		}

		$virtual_hosts = $this->_get_aven_list($server_info, $available_path, $enabled_path);
		
		$command = "unlink ".$enabled_path.$virtual_hosts[$virtual_host_name]["symlink_name"];
		$result = _ssh_exec($server_info, $command);
		
		if(empty($result)){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	* 	AVailable-ENabled cofig system
	*/
	function _delete_aven ($server_info, $available_path, $enabled_path, $virtual_host_name) {

		$virtual_hosts = $this->_get_aven_list($server_info, $available_path, $enabled_path);
		
		if(!in_array($virtual_host_name, array_keys($virtual_hosts))){
			return false;
		}
		
		// delete file
		$command = "unlink ".$available_path.$virtual_hosts[$virtual_host_name]["virtual_host_name"];
		$result = _ssh_exec($server_info, $command);
		
		// delete symlink
		$command2 = "unlink ".$enabled_path.$virtual_hosts[$virtual_host_name]["symlink_name"];
		_ssh_exec($server_info, $command2);
	
		if(empty($result)){
			return true;
		}else{
			return false;
		}
	}

	/**
	* 	AVailable-ENabled cofig system
	*/
	function _add_aven ($server_info, $available_path, $enabled_path, $virtual_host_name, $content, $status) {

		$this->SSH_OBJ->write_string($server_info, $content, $available_path.$virtual_host_name);
		
		if($status == "1"){
			$symlink_name = rtrim($enabled_path.$virtual_host_name, "/");
		
			$result = _ssh_exec($server_info, " ln -s ".rtrim($available_path.$virtual_host_name, "/")." ".$symlink_name);
		}else{
			$virtual_hosts = $this->_get_aven_list($server_info, $available_path, $enabled_path);
			_ssh_exec($server_info, "unlink ".$enabled_path.$virtual_hosts[$virtual_host_name]["symlink_name"]);
		}
		
		return true;
	}
	
	/**
	* convert mask for freebsd
	*/
	function _convert_mask ($mask) {
		$mask = str_replace("0x", "", $mask);
		$m1 = hexdec(substr($mask, "0", "2"));
		$m2 = hexdec(substr($mask, "2", "2"));
		$m3 = hexdec(substr($mask, "4", "2"));
		$m4 = hexdec(substr($mask, "6", "2"));
		
		return $m1.".".$m2.".".$m3.".".$m4;
	}
	
	// grabs a key from sysctl(8)
	function _grab_key ($server_info, $key) {
		return _ssh_exec($server_info, "sysctl -n ".$key);
	} 
  
	function _format_bytesize ($intKbytes, $intDecplaces = 2) {
		$strSpacer = '&nbsp;';

		if( $intKbytes > 1048576 ) {
			$strResult = sprintf( '%.' . $intDecplaces . 'f', $intKbytes / 1048576 );
			$strResult .= $strSpacer . t("gb");
		} elseif( $intKbytes > 1024 ) {
			$strResult = sprintf( '%.' . $intDecplaces . 'f', $intKbytes / 1024);
			$strResult .= $strSpacer . t("mb");
		} else {
			$strResult = sprintf( '%.' . $intDecplaces . 'f', $intKbytes );
			$strResult .= $strSpacer . t("kb");
		}

		return $strResult;
	}

	
	/** Convert stdClass into array */
	function _to_array ($obj = null) {
		$obj = (array)$obj;
		foreach ((array)$obj as $k => $v) {
			if (is_object($v) || is_array($v)) {
				$obj[$k] = (array)$this->_to_array($v);
			}
		}
		return $obj;
	}


}
