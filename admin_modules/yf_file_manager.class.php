<?php

/**
* File manager module
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_file_manager {

	/** @var string Color of the subdirs */
	var	$color_dir			= "#0066cc";
	/** @var string Color of the files */
	var	$color_file			= "#00aa66";
	/** @var string Color of the ZIP archives */
	var	$color_zip			= "navy";
	/** @var int */
	public $_copy_dir_deepness = 2;
	/** @var string */
	public $_default_email		= "";
	/** @var string @conf_skip Include files pattern */
	public $_include_pattern	= "";
	/** @var string @conf_skip Exclude files pattern */
	public $_exclude_pattern	= "#\.(svn|git)#";
	/** @var int @conf_skip */
// TODO: need to connect them
	public $_default_chmod		= 0777;

	/**
	* Function shows directory contents
	*/
	function show() {
		// Current path info
		$path_info = pathinfo($_SERVER["SCRIPT_FILENAME"]);
		$_old_dir_name	= str_replace("\\", "/", getcwd());
		// If directory name comes from GET array
		if ($_GET['dir_name']) {
			$dir_name = urldecode($_GET['dir_name']);
			chdir($dir_name);
		} else {
			$dir_name = $path_info["dirname"];
		}
		// Up dir path info
		$path_info_up = pathinfo($dir_name);
		// Current directory contents
		$cur_dir_name		= str_replace("\\", "/", getcwd());
		$encoded_cur_dir	= urlencode($cur_dir_name);
		$dir_contents		= $this->_get_dir_contents($cur_dir_name);
		// Return to base dir
		chdir($_old_dir_name);
		// Process directory items
		if (!empty($dir_contents["dirs"]) || !empty($dir_contents["files"])) {
			// Show subdirs in the current directory
			foreach ((array)$dir_contents["dirs"] as $k => $v) {
				// Process template
				$replace2 = array(
					"encoded_name"	=> urlencode($v),
					"name"			=> $v,
					"go_into_link"	=> "./?object=".$_GET["object"]."&dir_name=".urlencode($dir_name."/".$v). _add_get(array("dir_name")),
					"color"			=> $this->color_dir,
					"m_date"		=> date ("Y-m-d H:i:s", filemtime($cur_dir_name. "/". $v)),
					"perms"			=> $this->_get_perms($cur_dir_name. "/". $v),
					"size"			=> 0,
					"is_dir"		=> 1,
					"is_file"		=> 0,
				);
				$items .= tpl()->parse($_GET["object"]."/item", $replace2);
			}
			// Show files in the current directory
			foreach ((array)$dir_contents["files"] as $k => $v) {
				// Show ZIP archives with another color
				if (substr($v, -3) == "zip") {
					$color = $this->color_zip;
				} else {
					$color = $this->color_file;
				}
				// Count file size
				$file_size = filesize($cur_dir_name. "/". $v);
				$total_files_size += $file_size;
				// Process template
				$replace2 = array(
					"encoded_name"	=> urlencode($v),
					"name"			=> $v,
					"go_into_link"	=> "",
					"color"			=> $color,
					"m_date"		=> date ("Y-m-d H:i:s", filemtime($cur_dir_name. "/". $v)),
					"perms"			=> $this->_get_perms($cur_dir_name. "/". $v),
					"size"			=> $file_size,
					"is_dir"		=> 0,
					"is_file"		=> 1,
				);
				$items .= tpl()->parse($_GET["object"]."/item", $replace2);
			}
		}
		clearstatcache();
		// More useful navigation
		$_tmp_path = "";
		$_tmp_array = array();
		foreach ((array)explode("/", $cur_dir_name) as $_folder) {
			$_tmp_path .= $_folder."/";
			$_tmp_array[] = "<a href='./?object=".$_GET["object"]."&dir_name=".urlencode($_tmp_path)._add_get(array("dir_name"))."'>"._prepare_html($_folder)."</a>";
		}
		if ($_tmp_array) {
			$cur_dir_name = implode("/", $_tmp_array);
		}
		// Process template
		$replace = array(
			"form_action"			=> "./?object=".$_GET["object"]._add_get(array("dir_name"))."&action=",
			"upload_form_action"	=> "./?object=".$_GET["object"]."&action=upload_file&dir_name=".$encoded_cur_dir._add_get(array("dir_name")),
			"mkdir_form_action"		=> "./?object=".$_GET["object"]."&action=make_dir&dir_name=".$encoded_cur_dir._add_get(array("dir_name")),
			"cur_dir_name"			=> $cur_dir_name,
			"encoded_dir_name"		=> $encoded_cur_dir,
			"go_up_level_link"		=> "./?object=".$_GET["object"]."&dir_name=".urlencode($path_info_up["dirname"])._add_get(array("dir_name")),
			"go_home_link"			=> "./?object=".$_GET["object"]._add_get(array("dir_name")),
			"total_files_size"		=> intval($total_files_size),
			"total_files"			=> intval(count($dir_contents["files"])),
			"total_dirs"			=> intval(count($dir_contents["dirs"])),
			"default_zip_name"		=> "tmp12345",
			"default_email"			=> conf('webmaster_mail'),
			"default_chmod"			=> 755,
			"items"					=> $items,
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Return sorted directory contents
	*/
	function _get_dir_contents($abs_dir_name) {
		$contents = array(
			"dirs"	=> array(), 
			"files"	=> array(),
		);
		$handle = opendir($abs_dir_name);
		while (false !== ($tmp_file = readdir($handle))) { 
			if ($tmp_file == "." || $tmp_file == "..") {
				continue;
			}
			// Include files only if they match the mask
			if (!empty($this->_include_pattern)) {
				if (!preg_match($this->_include_pattern."ims", $tmp_file)) continue;
			}
			// Exclude files from list by mask
			if (!empty($this->_exclude_pattern)) {
				if (preg_match($this->_exclude_pattern."ims", $tmp_file)) continue;
			}
			if (is_dir($tmp_file)) {
				$contents["dirs"][]		= $tmp_file;
			} elseif (is_file($tmp_file)) {
				$contents["files"][]	= $tmp_file;
			}
		}
		natsort($contents["dirs"]);
		natsort($contents["files"]);
		return $contents;
	}

	/**
	* Read file contents
	*/
	function view_item() {
		foreach ((array)$_POST as $k => $v) {
			$tmp = substr($k, 0, 2);
			if ($tmp == "d_" || $tmp == "f_") {
				$name = $v;
				break;
			}
		}
		$dir_name	= urldecode($_POST['dir_name']);
		$file_name	= str_replace("\\", "/", $dir_name."/".$name);
		$file_path	= $file_name;
		// More useful navigation
		$_tmp_path = "";
		$_tmp_array = array();
		foreach ((array)explode("/", dirname($file_name)) as $_folder) {
			$_tmp_path .= $_folder."/";
			$_tmp_array[] = "<a href='./?object=".$_GET["object"]."&dir_name=".urlencode($_tmp_path)._add_get(array("dir_name"))."'>"._prepare_html($_folder)."</a>";
		}
		if ($_tmp_array) {
			$file_name = implode("/", $_tmp_array)."/"._prepare_html($name, 0);
		}
		// Process template
		$replace = array(
			"file_name"	=> $file_name,
			"text"		=> highlight_file($file_path, true),
			"edit_link"	=> "./?object=".$_GET["object"]."&action=edit_item&f_=".urlencode(basename($file_path))."&dir_name=".urlencode(dirname($file_path))._add_get(array("dir_name")),
			"back"		=> back("./?object=".$_GET["object"]."&dir_name=".$_POST['dir_name']._add_get(array("dir_name"))),
		);
		return tpl()->parse($_GET["object"]."/view", $replace);
	}

	/**
	* Form to edit given file
	*/
	function edit_item() {
		if (!empty($_GET["id"])) {
			$file_name	= urldecode($_GET["id"]);
			$file_path	= $file_name;
			$dir_name	= dirname($file_path);
		} else {
			foreach ((array)$_REQUEST as $k => $v) {
				$tmp = substr($k, 0, 2);
				if ($tmp == "d_" || $tmp == "f_") {
					$name = $v;
					break;
				}
			}
			$dir_name	= urldecode($_REQUEST['dir_name']);
			$file_name	= str_replace("\\", "/", $dir_name."/".$name);
			$file_path	= $file_name;
		}
		// More useful navigation
		$_tmp_path = "";
		$_tmp_array = array();
		foreach ((array)explode("/", dirname($file_name)) as $_folder) {
			$_tmp_path .= $_folder."/";
			$_tmp_array[] = "<a href='./?object=".$_GET["object"]."&dir_name=".urlencode($_tmp_path)._add_get(array("dir_name"))."'>"._prepare_html($_folder)."</a>";
		}
		if ($_tmp_array) {
			$file_name = implode("/", $_tmp_array)."/"._prepare_html($name, 0);
		}
		// Process template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=save_file&dir_name=".$_REQUEST['dir_name']."&file_name=".urlencode($file_path)._add_get(array("dir_name")),
			"file_name"		=> $file_name,
			"file_ext"		=> common()->get_file_ext(basename($file_path)),
			"text"			=> _prepare_html(file_get_contents($file_path), 0),
			"back"			=> back("./?object=".$_GET["object"]."&dir_name=".$_REQUEST['dir_name']._add_get(array("dir_name"))),
		);
		return tpl()->parse($_GET["object"]."/edit", $replace);
	}

	/**
	* Save edited file
	*/
	function save_file() {
		// Decode file name
		$file_name = urldecode($_GET["file_name"]);
		// Do save file contents
		file_put_contents($file_name, $_POST["source"]);
		// Return user to the file folder
		return js_redirect("./?object=".$_GET["object"]."&dir_name=".$_GET['dir_name']._add_get(array("dir_name")));
	}

	/**
	* Show multi-level tree with folders inside
	*/
	function _get_all_dirs($dir_name = "", $level = 1) {
		if (!file_exists($dir_name)) {
			return;
		}
		$_old_dir_name	= str_replace("\\", "/", getcwd());
		chdir($dir_name);
		$dir_contents = $this->_get_dir_contents($dir_name);
		chdir($_old_dir_name);

		foreach ((array)$dir_contents["dirs"] as $cur_name) {
			$dir_next = $dir_name."/".$cur_name;
			// Process template
			$replace = array(
				"color"			=> $this->color_dir,
				"name"			=> $cur_name,
				"encoded_name"	=> urlencode($dir_next),
				"padding"		=> ($level - 1) * 30,
			);
			$body .= tpl()->parse($_GET["object"]."/copy_dir_item", $replace);
			// Try to show sub dirs
			if ($level < $this->_copy_dir_deepness) {
				$body .= $this->_get_all_dirs($dir_next, $level + 1);
			}
		}
		return $body;
	}

	/**
	* Copy selected items
	*/
	function copy_item() {
		$dir_name = urldecode($_POST['dir_name']);
		$items_to_copy = array();
		foreach ((array)$_POST as $k => $v) {
			$tmp = substr($k, 0, 2);
			if ($tmp != "d_" && $tmp != "f_") continue;

			if ($tmp == "d_") {
				$color = $this->color_dir;
				$type = strtoupper(t("directory"));
			} elseif ($tmp == "f_") {
				$color = $this->color_file;
				$type = strtoupper(t("file"));
			}
			$file_name	= str_replace("\\", "/", $dir_name."/".$v);
			$items_to_copy[] = $tmp.$file_name;
			$replace2 = array(
				"color"		=> $color,
				"file_name"	=> $file_name,
				"type"		=> $type,
			);
			$items .= tpl()->parse($_GET["object"]."/copy_item", $replace2);
		}
		// Process template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=copy_item2&dir_name=".$_POST['dir_name']._add_get(array("dir_name")),
			"items"			=> $items,
			"items_to_copy"	=> urlencode(serialize($items_to_copy)),
			"dest_dirs"		=> $this->_get_all_dirs(dirname($dir_name)),
			"back"			=> back("./?object=".$_GET["object"]."&dir_name=".$_POST['dir_name']._add_get(array("dir_name"))),
		);
		return tpl()->parse($_GET["object"]."/copy", $replace);
	}

	/**
	* Copy selected items into selected destination folders
	*/
	function copy_item2() {
// TODO
		if ($_POST['items_to_copy']) {
			$items = unserialize(urldecode($_POST['items_to_copy']));
			// Try to find items to copy within decoded array
			foreach ((array)$items as $k => $v) {
				$tmp = substr($v, 0, 2);
				if ($tmp == "d_" || $tmp == "f_") {
					$items_to_copy[] = substr($v, 2);
				}
			}
			// Try to find destination folders
			foreach ((array)$_POST as $k => $v) {
				$tmp = substr($k, 0, 2);
				if ($tmp == "d_" || $tmp == "f_") {
					$dest_folders[] = urldecode(substr($k, 2));
				}
			}		
			// Verify decoded arrays of source and destination items
			if (count($items_to_copy)) {
				if (count($dest_folders)) {
					foreach ((array)$items_to_copy as $k => $v) {
						if (is_dir($v)) {
							foreach ((array)$dest_folders as $k1 => $v1) {
								// Check if dirs are different
// !! Need to add checking of parentess of dirs (to avoid infinite copy recursion)
								if ($v != $v1) {
									$new_name = $v1."/".basename($v);
									mkdir($new_name, 0777);
									_class("dir")->copy_dir($v, $new_name);
								}
							}
						} else {
							foreach ((array)$dest_folders as $k1 => $v1) {
								copy($v, $v1."/".basename($v));
							}
						}
					}
					js_redirect("./?object=".$_GET["object"]."&dir_name=".$_GET['dir_name']._add_get(array("dir_name")));
				} else $body .= t("please_select_destinatin_folders");
			} else $body .= t("please_select_items_to_copy");
		} else $body .= t("please_select_items_to_copy");

		$body .= back("./?object=".$_GET["object"]."&dir_name=".$_GET['dir_name']);

		return $body;
	}

	/**
	* Delete selected items
	*/
	function delete_item() {
		$dir_name = urldecode($_POST['dir_name']);
		foreach ((array)$_POST as $k => $v) {
			$file_name	= str_replace("\\", "/", $dir_name."/".$v);

			$tmp = substr($k, 0, 2);
			if ($tmp != "d_" && $tmp != "f_") continue;
			if (!file_exists($file_name)) continue;

			if (is_dir($file_name)) {
				_class("dir")->delete_dir($file_name);
				@closedir($file_name);
				@rmdir($file_name);
			} else @unlink($file_name);
		}
		return js_redirect("./?object=".$_GET["object"]."&dir_name=".$_POST['dir_name']._add_get(array("dir_name")));
	}

	/**
	* Chmod for selected items
	*/
	function chmod_item() {
		$dir_name = urldecode($_POST['dir_name']);
		// Default value
		if (!$_POST['new_chmod']) {
			$_POST['new_chmod'] = "755";
		}
		// Change current working directory
		$_old_dir_name	= str_replace("\\", "/", getcwd());
		chdir($dir_name);
		foreach ((array)$_POST as $k => $v) {
			$tmp = substr($k, 0, 2);
			if ($tmp != "d_" && $tmp != "f_") {
				continue;
			}
			$file_name	= str_replace("\\", "/", $dir_name."/".$v);
			chmod($file_name, "0".$_POST['new_chmod']);
		}
		chdir($_old_dir_name);
		return js_redirect("./?object=".$_GET["object"]."&dir_name=".$_POST['dir_name']._add_get(array("dir_name")));
	}

	/**
	* Make zip
	*/
	function make_zip_item() {
		$dir_name = urldecode($_POST['dir_name']);
		if (!$_POST['new_zip_name']) {
			$_POST['new_zip_name'] = "tmp_1234";
		}
		// Name of new zip archive
		$new_zip_name = $dir_name."/".$_POST['new_zip_name'].".zip";
		// Initialize zip library
		main()->load_class_file("pclzip", "classes/");
		if (class_exists("pclzip")) {
			$this->ZIP_OBJ = &new pclzip($new_zip_name);
		}
		// Check if library loaded
		if (!is_object($this->ZIP_OBJ)) {
			trigger_error("FILE_MANAGER: Cant init PclZip module", E_USER_ERROR);
			return false;
		}
		// Change current directory
		$_old_dir_name	= str_replace("\\", "/", getcwd());
		chdir($dir_name);
		// List of items to be zipped together
		$item_list = array();
		foreach ((array)$_POST as $k => $v) {
			$tmp = substr($k, 0, 2);
			if ($tmp != "d_" && $tmp != "f_") {
				continue;
			}
			$file_name	= str_replace("\\", "/", $dir_name."/".$v);
			if (file_exists($file_name) && is_readable($file_name)) {
				$item_list[] = $v;
			}
		}
		$_old_dir_name	= str_replace("\\", "/", getcwd());
		chdir($_old_dir_name);
		// Create zip archive including specified files
		$this->ZIP_OBJ->create($item_list);
		return js_redirect("./?object=".$_GET["object"]."&dir_name=".$_POST['dir_name']._add_get(array("dir_name")));
	}

	/**
	* Unzip files from archive
	*/
	function unzip_item() {
		$dir_name = urldecode($_POST['dir_name']);
		$_old_dir_name	= str_replace("\\", "/", getcwd());
		foreach ((array)$_POST as $k => $name) {
			$tmp = substr($k, 0, 2);
			if ($tmp != "d_" && $tmp != "f_") {
				continue;
			}
			// Check file extension (must be "zip")
			$ext = array_pop(explode(".",$name));
			if ($ext != "zip") continue;

			$file_name	= str_replace("\\", "/", $dir_name."/".$name);
			// Initialize zip library
			main()->load_class_file("pclzip", "classes/");
			if (class_exists("pclzip")) {
				$this->ZIP_OBJ = &new pclzip($file_name);
			}
			// Check if library loaded
			if (!is_object($this->ZIP_OBJ)) {
				trigger_error("FILE_MANAGER: Cant init PclZip module", E_USER_ERROR);
				return false;
			}
			// Rewrite file target aerchive to be extracted
			$this->ZIP_OBJ->pclzip($file_name);
			// Directory where files will be extracted
			$extraction_dir = $dir_name."/".substr($name, 0, -4);
			// Create directory to extract files
			if (!file_exists($extraction_dir)) {
				mkdir($extraction_dir, 0777);
			}
			// Change working directory to the new one
			chdir($extraction_dir);
			// Extract files from specified zip archive
			$this->ZIP_OBJ->extract();
		}
		chdir($_old_dir_name);
		return js_redirect("./?object=".$_GET["object"]."&dir_name=".$_POST['dir_name']._add_get(array("dir_name")));
	}

	/**
	* Download file
	*/
	function download_item() {
		$dir_name = urldecode($_POST['dir_name']);
		// Change current working directory
		$_old_dir_name	= str_replace("\\", "/", getcwd());
		chdir($dir_name);
		foreach ((array)$_POST as $k => $v) {
			$file_name	= str_replace("\\", "/", $dir_name."/".$v);

			$tmp = substr($k, 0, 2);
			if ($tmp != "d_" && $tmp != "f_") continue;

			// Check if given item is file and it can be read
			if (is_readable($file_name) && is_file($file_name)) {
				clearstatcache();
				header("Content-Type: application/force-download; name=\"".$v."\"");
				header("Content-Type: text/plain");
				header("Content-Transfer-Encoding: binary");
				header("Content-Length: ".filesize($file_name));
				header("Content-Disposition: attachment; filename=\"".$v."\"");
				readfile($file_name);
				main()->NO_GRAPHICS = true;
				exit();
			} else {
				chdir($_old_dir_name);
				return js_redirect("./?object=".$_GET["object"]."&dir_name=".$_POST['dir_name']._add_get(array("dir_name")));
			}
		}
	}

	/**
	* Email file
	*/
	function email_item() {
		$dir_name = urldecode($_POST['dir_name']);
		// Change current working directory
		$_old_dir_name	= str_replace("\\", "/", getcwd());
		chdir($dir_name);
		// Create empty array
		$attach = array();
		foreach ((array)$_POST as $k => $v) {
			$file_name	= str_replace("\\", "/", $dir_name."/".$v);

			$tmp = substr($k, 0, 2);
			if ($tmp != "d_" && $tmp != "f_") {
				continue;
			}
			// Check if given item is file and it can be read
			if (file_exists($file_name) && is_readable($file_name)) {
				$attach[] = $v;
			}
		}
		// If there is at least one file to send - then continue with sending
		if (count($attach)) {
// TODO: Add config vars here
			$email_from = "yfix.dev auto-sender";
			if (!$_POST['target_email']) {
				$email_to = "ph@mail.zp.ua";
			} else {
				$email_to = $_POST['target_email'];
			}
			$to_name = "auto-sender destination";
			$subject = "yfix.dev auto-sender generated email";
			$HTML = "see attachment";
			$TEXT = "see attachment";
			$result = common()->send_mail("PHP-Mailer", $email_from, $email_to, $to_name, $subject, $TEXT, $HTML, $attach);
		}
		chdir($_old_dir_name);
		return js_redirect("./?object=".$_GET["object"]."&dir_name=".$_POST['dir_name']._add_get(array("dir_name")));
	}

	/**
	* Function that uploads file to the specified directory name
	*/
	function upload_file() {
		if ($_POST['verify']) {
			if ($_POST['name']) {
				$Name = $_POST['name'];
			} else {
				$Name = $_FILES['file']['name'];
			}
			$fileName = urldecode($_POST['dir_name'])."/".$Name;
			if (!move_uploaded_file($_FILES['file']['tmp_name'], $fileName)) {
				$body .= error_back();
			} else {
				$body .= t('save_successful')."<br />\r\n";
			}
		}
		$body .= $this->show();
		return $body;
	}

	/**
	* Make new directory
	*/
	function make_dir() {
		if ($_POST['verify'] && $_POST['name']) {
			$new_dir = urldecode($_POST['dir_name'])."/".$_POST['name'];
			if (!@file_exists($new_dir)) {
				mkdir($new_dir, 0777);
			}
		}
		$body .= $this->show();
		return $body;
	}

	/**
	* Show permissions for UNIX server
	*/
	function _get_perms ($file_name) {
		return substr(sprintf('%o', fileperms($file_name)), -3);
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
		$pheader = t("File Manager");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"				=> "",
			"view_item"			=> "View File",
			"edit_item"			=> "Edit File",
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
