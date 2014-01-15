<?php

/**
* Server file manager
*/
class yf_server_file_manager {

	/** @var string Home dir */
	public $HOME_DIR 				= "/var/www/";
	/** @var array Viewable and editable file extensions */
	public $EDIT_ALLOWED_TYPES 	= array(
		"php",
		"html",
		"htaccess",
		"txt",
	);
	/** @var array Denied folders */
	public $BLACKLIST 	= array(
		"/bin",
		"/boot",
		"/dev",
		"/initrd",
		"/proc",
		"/ssl",
		"/lost+found",
		"/mnt",
		"/root",
		"/lib",
		"/libexec",
		"/sbin",
		"/dist",
//		"/home",
	);
	/** @var int Maximum filesize to allow edit or view it through web interface */
	public $MAX_EDITABLE_FILESIZE 	= 1048000;
	/** @var string Tar file prefix (using for tar filename creation when compress files) */
	public $TAR_PREFIX 			= "compress-";
	/** @var bool Allow to change user and user group */
	public $ALLOW_CHANGE_OWNER 	= true;
	/** @var bool Show server name in page header */
	public $SHOW_SERVER_NAME 		= true;
	/** @var string Server info url */
	public $SERVER_INFO_URL 		= "./?object=admin_servers&action=view_server";

	/**
	* Constructor
	*/
	function _init () {
		$this->SERVER_ID = intval($_GET["id"]);
		$this->_server_info = db()->query_fetch("SELECT * FROM ".db('servers')." WHERE id=".intval($_GET["id"]));
		
		// Init ssh class
		$this->SSH_OBJ = _class("ssh");

		$this->START_DIR = "/";

		$this->GET_PATH = $_GET["page"];

		// Get images for extensions
		$this->DIR_OBJ = _class("dir");		
		$images = $this->DIR_OBJ->scan_dir("./uploads/icons/ext", true, "", "/(svn|git)/i");
		foreach((array)$images as $filename) {
			list($ext_name) = explode(".", basename($filename));
			$this->ext_images[$ext_name] = basename($filename);
		}
	}

	/**
	* Catch _ANY_ call to the class methods (yf special hook)
	*/
	function _module_action_handler($called_action = "") {
		if (!$this->_server_info) {
			return _e(t("Server not found"));
		}
		if (!_check_rights("$called_action")) {
			return _e(t("Access denied"));
		}
		$body = $this->$called_action();
		return $body;
	}

	/**
	* Default method
	*/
	function show () {
//		$this->SERVER_ID = intval($_GET["id"]);
		// Check if <dir_name> is inside <START_FOLDER>
		if ($this->GET_PATH) {
			$dir_name = $this->_urldecode($this->GET_PATH);
		} else {
			$dir_name = $this->START_DIR;
		}

		$dir_name = $this->_prepare_path($dir_name);

		$dir_contents = $this->SSH_OBJ->scan_dir($this->_server_info, $dir_name, "", "/\.(svn|git)/ims", 0);
		if (is_array($dir_contents)) {
			uasort($dir_contents, array(&$this, "_sort_by_type"));
		}

		// More useful navigation
		$_tmp_path = "";
		$_tmp_array = array();
		$dir_name = rtrim($dir_name, "/");
		if(substr_count($dir_name, "/") < 1) {
			$allow_delete = false;
			$allow_chmod = false;
		} else {
			$allow_delete = true;
			$allow_chmod = true;
		}

		$folders_array = explode("/", $dir_name); 
		$num = count($folders_array);
		foreach ((array)$folders_array as $_folder) {
			$i++;
			$_tmp_path .= $_folder."/";
			if ($i >= $num || !substr_count($_tmp_path, $this->START_DIR)) {
				$_tmp_array[] = _prepare_html($_folder);
			} else {
				$_tmp_array[] = "<a href='./?object=".$_GET["object"]."&action=show&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode($_tmp_path)."'>"._prepare_html($_folder)."</a>";
			}
		}
		if ($_tmp_array) {
			$cur_dir_name = implode("/", $_tmp_array);
		}
		$tmp_path_info = pathinfo($dir_name);
		$up_dir_name = str_replace("\\", "/", $tmp_path_info["dirname"]);
		// Limit navigation within start folder
		if (!substr_count($dir_name, $this->START_DIR) || $dir_name == $this->START_DIR) {
			$up_dir_name = "";
		}

		$num_dirs 	= 0;
		$num_files 	= 0;
		foreach ((array)$dir_contents as $_path => $_info) {
			// Gathering of common statistics
			if ($_info["type"] != "d") {
				$total_fsize += intval($_info["size"]);
				$num_files ++; 
			} else {
				$num_dirs++;
			}
			// Determine which files allowed to view and edit 
			$file_path_info = pathinfo($_info["name"]);
			if ($_info["type"] != "d" && in_array($file_path_info["extension"] ,(array)$this->EDIT_ALLOWED_TYPES)) {
				$allow_edit = true;
			} else {
				$allow_edit = false;
			}

			if($_info["type"] != "d"){
				$path_parts = pathinfo($_path);
				if(in_array($path_parts["extension"], (array)array_keys($this->ext_images))){
					$ext_img = $this->ext_images[$path_parts["extension"]];
				} else {
					$ext_img = $this->ext_images["default"];
				}
			} else {
				$ext_img = $this->ext_images["folder"];
			}
			$replace2 = array(
				"name"			=> _prepare_html($_info["name"]),
				"encoded_name"	=> $this->_urlencode($_path),
				"ext_img"		=> $ext_img ? $ext_img : "",
				"type"			=> $_info["type"],
				"size"			=> common()->format_file_size($_info["size"]),
				"date"			=> _format_date($_info["date"], "long"),
				"perms"			=> $_info["perms"],
				"user"			=> $_info["user"],
				"view_url"		=> $allow_edit ? "./?object=".$_GET["object"]."&action=view_file&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode($_path) : "",
				"edit_url"		=> $allow_edit ? "./?object=".$_GET["object"]."&action=edit_file&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode($_path) : "",
				"delete_url"	=> (($allow_delete && $_info["type"] == "d") || $_info["type"] != "d") ? "./?object=".$_GET["object"]."&action=".($_info["type"] == "d" ? "delete_folder" : "delete_file")."&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode($_path) : "",
				"dir_url"		=> ($_info["type"] == "d" && $this->_check_blacklist($_path))? "./?object=".$_GET["object"]."&action=show&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode($_path) : "",
				"download_url"	=> $_info["type"] != "d" ? "./?object=".$_GET["object"]."&action=download_file&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode($_path) : "",
				"chmod_url"		=> $allow_chmod ? "./?object=".$_GET["object"]."&action=edit_chmod&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode($_path) : "",
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}

		$replace = array(
			"server_name"	=> $this->_server_info["name"],
			"server_ip"		=> $this->_server_info["base_ip"],
			"server_url"	=> $this->SHOW_SERVER_NAME ? $this->SERVER_INFO_URL."&id=".$this->SERVER_ID : "",
			"up_level_url"	=> $up_dir_name ? "./?object=".$_GET["object"]."&action=show&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode($up_dir_name) : "",
			"items"			=> $items,
			"form_action"	=> "./?object=".$_GET["object"]."&action=upload_file&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode($dir_name),
			"mkdir_action"	=> "./?object=".$_GET["object"]."&action=create_folder&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode($dir_name),
			"dir_name"		=> $cur_dir_name,
			"total_fsize"	=> common()->format_file_size($total_fsize),
			"num_files"		=> $num_files,
			"num_dirs"		=> $num_dirs,
			"group_delete_url"	=> "./?object=".$_GET["object"]."&action=group_delete&id=".$this->SERVER_ID,
			"group_chmod_url"	=> "./?object=".$_GET["object"]."&action=edit_chmod&id=".$this->SERVER_ID,
			"tar_url"		=> "./?object=".$_GET["object"]."&action=tar&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode($dir_name),
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* View file contents
	*/
	function view_file () {
		$filename = $this->_prepare_path($this->_urldecode($this->GET_PATH));
		
		$file_content = $this->SSH_OBJ->read_file($this->_server_info, $filename);
		$replace = array(
			"filename"		=> $filename,
			"file_content" 	=> $file_content,
			"back_url"		=> "./?object=".$_GET["object"]."&action=show&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode(dirname($filename)),
		);
		return tpl()->parse($_GET["object"]."/view", $replace);
	}

	/**
	* chmod
	*/
	function edit_chmod () {
		$_SELECTED_FILES = array();
		if ($this->GET_PATH) {
			$this->GET_PATH = $this->_urldecode($this->GET_PATH);
			$_SELECTED_FILES[] = $this->_prepare_path($this->GET_PATH);
		} elseif (!empty($_POST["selected"])) {
			foreach ((array)$_POST["selected"] as $path) {
				$path = $this->_urldecode($path);
				$_SELECTED_FILES[] = $this->_prepare_path($path);
			}
		} else {
			_re(t("File path missing"));
		}
		if (common()->_error_exists()) {
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}
		// Check that given path is not a root folder or path
		foreach ((array)$_SELECTED_FILES as $path) {
			$tmp_dir_name = rtrim($path, "/");
			if(substr_count($tmp_dir_name, "/") < 1) {
				return js_redirect($_SERVER["HTTP_REFERER"]);
			}
		}

		if ($this->ALLOW_CHANGE_OWNER) {
			// Init server commands class
			$this->SERVER_OBJ = &_class("server_commands"); 

			// Find all users
			$system_users_array = $this->SERVER_OBJ->get_system_users($this->_server_info);
			foreach ((array)$system_users_array as $v) {
				$users_array[$v["user_name"]] = $v["user_name"];
			}
			asort($users_array);
			
			// Find all groups
			$system_groups_array = $this->SERVER_OBJ->get_system_groups($this->_server_info);
			foreach ((array)$system_groups_array as $v) {
				$groups_array[$v["group_name"]] = $v["group_name"];
			}
			asort($groups_array);
		}

		if (count($_SELECTED_FILES) == 1) {
			// Find file info
			$file_info = $this->SSH_OBJ->file_info($this->_server_info, $path);
			$perms = substr($file_info["perms"], 1);
		} else {
			$perms = "rwxrwxrwx";
		}

		$perms_array = array(
			array(t("read"), 	$perms{0}),
			array(t("write"), 	$perms{1}),
			array(t("execute"),	$perms{2}),
			array(t("read"), 	$perms{3}),
			array(t("write"), 	$perms{4}),
			array(t("execute"),	$perms{5}),
			array(t("read"),	$perms{6}),
			array(t("write"),	$perms{7}),
			array(t("execute"),	$perms{8}),
		);

//		if (!empty($_POST["user"]) || !empty($_POST["group"])) {
		if (main()->is_post()) {
			// Save data
			if ($_POST["mass_selected"]) {
				$_POST["mass_selected"] = unserialize($_POST["mass_selected"]);
			}

			$_POST["perms_octal"] = intval($_POST["perms_octal"]);
			$recurs = $_POST["change_recurs"] ? 1 : 0;
			if (!empty($_POST["perms_octal"])) {
				// Convert octal form to string form
				if (strlen($_POST["perms_octal"]) != 3) {
					_re(t("Wrong permission")."!");
				}
				$octal_array = str_split($_POST["perms_octal"]);
				foreach ((array)$octal_array as $octal) {
					if(intval($octal) > 7) {
						_re(t("Wrong permission")."!");
					}
				}
				if (!common()->_error_exists()) {
					foreach ((array)$_POST["mass_selected"] as $path) {
						// Change chmod
						$this->SSH_OBJ->chmod($this->_server_info, $path, $_POST["perms_octal"], $recurs);
					}
				}
			} elseif (!empty($_POST["perms"])) {
				foreach (range(0,7) as $v){
					if (!isset($_POST["perms"][$v])) {
						$_POST["perms"][$v] = "-";
					}
				}
				ksort($_POST["perms"]);
				$perm_string = implode("", $_POST["perms"]);	
				$octal = $this->_perm_str2num($perm_string);

				if (!common()->_error_exists()) {
					foreach ((array)$_POST["mass_selected"] as $path) {
						// Change chmod
						$this->SSH_OBJ->chmod($this->_server_info, $path, $octal, $recurs);
					}
				}
			}

			if ($this->ALLOW_CHANGE_OWNER) {
				// Change group and (or) owner
				if (!common()->_error_exists()) {
					foreach ((array)$_POST["mass_selected"] as $path) {
						$this->SSH_OBJ->chown($this->_server_info, $path, $_POST["user"], $_POST["group"], $recurs);
					}
				}
			}
			return js_redirect("./?object=".$_GET["object"]."&action=show&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode(dirname($path)));
		}

		$replace = array(
			"filepath"		=> _prepare_html($path),
			"group_box"		=> $this->ALLOW_CHANGE_OWNER ? common()->select_box("group", $groups_array, count($_SELECTED_FILES) == 1 ? $file_info["group"] : "root") : "",
			"user_box"		=> $this->ALLOW_CHANGE_OWNER ? common()->select_box("user", $users_array, count($_SELECTED_FILES) == 1 ? $file_info["user"] : "root") : "",
			"perms"			=> $perms_array,
			"form_action"	=> "./?object=".$_GET["object"]."&action=edit_chmod&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode($path),
			"error_message"	=> _e(),
			"is_folder"		=> $file_info["type"] == "d" ? 1 : 0,
			"back_url"		=> "./?object=".$_GET["object"]."&action=show&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode(dirname($path)),
			"mass_selected"	=> _prepare_html(serialize($_SELECTED_FILES)),
		);
		return tpl()->parse($_GET["object"]."/chmod_form", $replace);
	}

	/**
	* Edit file contents
	*/
	function edit_file () {
		$filename = $this->_prepare_path($this->_urldecode($this->GET_PATH));
		if ($_POST["file_content"]) {
			// Save file
			$this->SSH_OBJ->write_string($this->_server_info, $_POST["file_content"], $filename);
			return js_redirect("./?object=".$_GET["object"]."&action=show&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode(dirname($filename)));
		}
		
		$file_content = $this->SSH_OBJ->read_file($this->_server_info, $filename);
		$replace = array(
			"filename"		=> $filename,
			"file_content" 	=> _prepare_html($file_content, 0),
			"back_url"		=> "./?object=".$_GET["object"]."&action=show&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode(dirname($filename)),
			"form_action"	=> "./?object=".$_GET["object"]."&action=edit_file&id=".($this->SERVER_ID ? $this->SERVER_ID."&page=" : "").$this->_urlencode($filename),
		);
		return tpl()->parse($_GET["object"]."/edit_form", $replace);
	}

	/**
	* Delete file
	*/
	function delete_file () {
		$file_name = $this->_prepare_path($this->_urldecode($this->GET_PATH));
		if ($this->SSH_OBJ->file_exists($this->_server_info, $file_name)) {
			$this->SSH_OBJ->unlink($this->_server_info, $file_name);
		}
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Group delete of files links and folders
	*/
	function group_delete () {
		foreach ((array)$_POST["selected"] as $path) {
			$path = $this->_urldecode($path);
			$path = $this->_prepare_path($path);
			$file_info = $this->SSH_OBJ->file_info($this->_server_info, $path);
			if ($file_info["type"] == "d") {
				$this->SSH_OBJ->rmdir($this->_server_info, $path);				
			} else {
				$this->SSH_OBJ->unlink($this->_server_info, $path);
			}
		}
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Create tar file from given list of files
	*/
	function tar () {
		$cur_folder = $this->_urldecode($this->GET_PATH);
		foreach ((array)$_POST["selected"] as $path) {
			$path = $this->_urldecode($path);
			$path = $this->_prepare_path($path);
			$command_string .= $path." ";
		}
		$tar_file_name = $this->TAR_PREFIX.time().".tar.gz";
		$command = "tar -czf ".$cur_folder."/".$tar_file_name." ".$command_string;
		$this->SSH_OBJ->exec($this->_server_info, $command);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Create folder
	*/
	function create_folder () {
		$dir_name = $this->_prepare_path($this->_urldecode($this->GET_PATH));
		if ($_POST["new_dir_name"]) {
			$_POST["new_dir_name"] = preg_replace("/[^a-z0-9\-\_\.\!\@\#\%]/i", "", $_POST["new_dir_name"]);
			$this->SSH_OBJ->mkdir($this->_server_info, $dir_name."/".$_POST["new_dir_name"]);
		}
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Delete file
	*/
	function delete_folder () {
		$folder_name = $this->_prepare_path($this->_urldecode($this->GET_PATH));
		$this->SSH_OBJ->rmdir($this->_server_info, $folder_name);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Upload file
	*/
	function upload_file () {
		$dest_folder = $this->_prepare_path($this->_urldecode($this->GET_PATH));
		if (!empty($_FILES["file_to_upload"])) {
			$this->SSH_OBJ->write_file($this->_server_info, $_FILES["file_to_upload"]["tmp_name"], $dest_folder."/".$_FILES["file_to_upload"]["name"]);
		}
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Download file
	*/
	function download_file () {
		$fpath = $this->_prepare_path($this->_urldecode($this->GET_PATH));
		$body = $this->SSH_OBJ->read_file($this->_server_info, $fpath);
		$fname = basename($fpath);
		if (!strlen($fname)) {
			exit;
		}
		main()->NO_GRAPHICS = true;
		// Throw headers
		header("Content-Type: application/force-download; name=\"".$fname."\"");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".strlen($body));
		header("Content-Disposition: attachment; filename=\"".$fname."\"");
		// Throw content
		echo $body;
		exit;
	}

	/**
	* Sort array of files by creation date (use for usort)
	*/
	function _sort_by_type ($a, $b) {
		if ($a["type"] == $b["type"]) {
			if ($a["name"] < $b["name"]) {
				return -1;
			} else {
				return 1;
			}
			//return 0;
		} elseif ($a["type"] < $b["type"]) {
			return -1;
		} else {
			return 1;
		}
	}

	/**
	* Check blacklist
	*/
	function _check_blacklist ($path = "") {
		if (empty($this->BLACKLIST)) {
			return true;
		}
		$path = $this->_prepare_path($path);
		foreach ((array)$this->BLACKLIST as $bls_item) {
			if (substr($path, 0, strlen($bls_item)) == $bls_item) {
				return false;
			}
		}
		return true;
	}

	/**
	* Prepare path, Prevent some hacks and misuses
	*/
	function _prepare_path ($path = "") {
		if (!strlen($path)) {
			return "";
		}
		if (is_array($path)) {
			foreach ((array)$path as $k => $v) {
				$path[$k] = $this->_prepare_path($v);
			}
			return $path;
		}
		$result = str_replace(array("", "\"", "\'", "~", ".."), "", rtrim(str_replace(array("\\", "//", "///"), "/", trim($path)), "/"));
		return strlen($result) ? $result : "/";
	}

	/**
	* Convert string permission output to numerical
	*/
	function _perm_str2num ($perm = "") {
		$perm_len = strlen(trim($perm));
		if ($perm_len > 10 && $perm_len < 9) {
			return false;
		}
		if ($perm_len == 10) {
			$perm = substr($perm, 1);
		}
		$perm_array = str_split($perm);
		foreach ((array)$perm_array as $k => $v) {
			if ($v != "-") {
				// Owner
				if ($k == 0) {
					$own += 4;
				}
				if ($k == 1) {
					$own += 2;
				}
				if ($k == 2) {
					$own += 1;
				}
				// Group
				if ($k == 3) {
					$grp += 4;
				}
				if ($k == 4) {
					$grp += 2;
				}
				if ($k == 5) {
					$grp += 1;
				}
				// Others
				if ($k == 6) {
					$oth += 4;
				}
				if ($k == 7) {
					$oth += 2;
				}
				if ($k == 8) {
					$oth += 1;
				}
			} 
		}
		return $own.$grp.$oth;
	}

	/**
	* Custom urlencode function (to avoid problems with rewrite mode)
	*/
	function _urlencode($url = "") {
		return urlencode(str_replace("/", ";", $url)); 
	}

	/**
	* Custom urldecode function (to avoid problems with rewrite mode)
	*/
	function _urldecode($encoded_url = "") {
		return str_replace(";", "/", urldecode($encoded_url));

	}

	/******************************** HOOKS ***************************/

	/**
	* check rights
	*/
	function _get_methods_for_check_rights(){
		$methods = array(
			"show",
			"view_file",
			"edit_chmod",
			"edit_file",
			"delete_file",
			"group_delete",
			"tar",
			"create_folder",
			"delete_folder",
			"upload_file",
			"download_file",
		);
		return $methods;
	}
}
