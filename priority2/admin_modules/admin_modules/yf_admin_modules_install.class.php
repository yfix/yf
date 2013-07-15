<?php

/**
* Installation code
*/
class yf_admin_modules_install {

	/**
	* Constructor
	*/
	function _init () {
		// Reference to the parent object
		$this->PARENT_OBJ	= module("admin_modules");
	}

	/**
	* Delete module (uninstall)
	*/
	function _uninstall () {
		if (empty($_GET["id"])) {
			return _e(t("Wrong module id"));
		}
		// Try to find such module in db
		$module_info = db()->query_fetch("SELECT * FROM ".db('admin_modules')." WHERE name='"._es($_GET["id"])."' LIMIT 1");
		if (empty($module_info)) {
			return _e(t("No such module"));
		}
		// Try to find files to be deleted
		$module_class_path = ADMIN_REAL_PATH.ADMIN_MODULES_DIR.$_GET["id"].CLASS_EXT;
		if (file_exists($module_class_path)) {
			$files[$module_class_path] = $module_class_path;
		}
		// Sub modules
		$sub_modules_dir = ADMIN_REAL_PATH.ADMIN_MODULES_DIR.$_GET["id"]."/";
		if (file_exists($sub_modules_dir)) {
			foreach ((array)$this->PARENT_OBJ->DIR_OBJ->scan_dir($sub_modules_dir, true, $this->PARENT_OBJ->_include_pattern) as $cur_file_path) {
				$cur_file_name = basename($cur_file_path);
				if (false === strpos($cur_file_name, CLASS_EXT)) {
					continue;
				}
				$files[$cur_file_path] = $cur_file_path;
			}
		}
		// Try to find templates
		$module_templates_dir = INCLUDE_PATH.tpl()->_THEMES_PATH. conf('theme')."/".$_GET["id"]."/";
		if (file_exists($module_templates_dir)) {
			foreach ((array)$this->PARENT_OBJ->DIR_OBJ->scan_dir($module_templates_dir, true, $this->PARENT_OBJ->_include_pattern) as $cur_file_path) {
				$cur_file_name = basename($cur_file_path);
				if (false === strpos($cur_file_name, ".stpl")) {
					continue;
				}
				$files[$cur_file_path] = $cur_file_path;
			}
		}
		// Do delete data
		if ($_POST) {
			// Process posted files
			foreach ((array)$_POST["files_to_delete"] as $cur_file_path) {
				if (!isset($files[INCLUDE_PATH.$cur_file_path])) {
					continue;
				}
			}
			// Do delete module db record
			db()->query("DELETE FROM ".db('admin_modules')." WHERE id=".intval($module_info["id"]));
			// Refresh system cache
			if (main()->USE_SYSTEM_CACHE)	cache()->refresh("admin_modules");
			// Return user back
			return js_redirect("./?object=".$_GET["object"]);
		}
		// Display form
		if (!$_POST || common()->_error_exists()) {
			// Process items			
			foreach ((array)$files as $file_path) {
				$replace2 = array(
					"bg_class"	=> !(++$i % 2) ? "bg1" : "bg2",
					"file_path"	=> _prepare_html(str_replace(INCLUDE_PATH, "", $file_path)),
					"edit_link"	=> "./?object=file_manager&action=edit_item&f_=".basename($file_path)."&dir_name=".urlencode(dirname($file_path)),
				);
				$files_to_delete .= tpl()->parse($_GET["object"]."/uninstall_item", $replace2);
			}
			// Process template
			$replace = array(
				"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
				"back_link"			=> "./?object=".$_GET["object"],
				"error_message"		=> _e(),
				"module_name"		=> _prepare_html($_GET["id"]),
				"files_to_delete"	=> $files_to_delete,
// TODO: show and execute db queries
				"uninstall_queries"	=> $uninstall_queries,
			);
			return tpl()->parse($_GET["object"]."/uninstall", $replace);
		}
	}

	/**
	* Import module
	* 
	*/
	function _import () {
		// Do import data
		if ($_POST) {
			if (empty($_FILES["import_file"]['tmp_name']) || empty($_FILES["import_file"]['size'])) {
				_re(t("Error while uploading file"));
			}
			// Move to the temporary folder
			if (!common()->_error_exists()) {
				// Prepare temporary folder
				$tmp_folder_path = INCLUDE_PATH. $this->PARENT_OBJ->TEMP_DIR. "import_module".($this->PARENT_OBJ->USE_UNIQUE_TMP_DIR ? "_".substr(md5(microtime(true)), 0, 8) : "")."/";
				if (file_exists($tmp_folder_path)) {
					$this->PARENT_OBJ->DIR_OBJ->delete_dir($tmp_folder_path);
				} else {
					_mkdir_m($tmp_folder_path);
				}
				$uploaded_file_path = $tmp_folder_path.$_FILES["import_file"]['name'];
				move_uploaded_file($_FILES["import_file"]['tmp_name'], $uploaded_file_path);
			}
			// Check for uploaded file
			if (!file_exists($uploaded_file_path) || !filesize($uploaded_file_path)) {
				_re(t("Error while moving temporary file"));
			}
			// Check for errors
			if (!common()->_error_exists()) {
				$file_ext = common()->get_file_ext(basename($uploaded_file_path));
				// Try to get archive type
				$ARCHIVE_TYPE = "";
				if ($file_ext == "zip") {
					$ARCHIVE_TYPE = "zip";
				} else {
					$fp		= @fopen($path, 'rb');
					$test	= fread($fp, 3);
					fclose($fp);
					if ($test[0] == chr(31) && $test[1] == chr(139)) {
						$ARCHIVE_TYPE = "gz";
					}
					if ($test == 'BZh') {
						$ARCHIVE_TYPE = "bz2";
					}
				}
				// Check if we found achive type
				if (empty($ARCHIVE_TYPE)) {
					_re(t("Unknown archive type"));
				}
			}
			// Try to extract file from archive
			if (!common()->_error_exists()) {
				// Prepare extract folder
				$extract_path = $tmp_folder_path;
				// Switch between archive types
				if ($ARCHIVE_TYPE == "zip") {
					// Init zip object
					main()->load_class_file("pclzip", "classes/");
					if (class_exists("pclzip")) {
						$this->PARENT_OBJ->ZIP_OBJ = &new pclzip($uploaded_file_path);
					}
					// Check if library loaded
					if (!is_object($this->PARENT_OBJ->ZIP_OBJ)) {
						trigger_error("INSTALLER: Cant init PclZip module", E_USER_ERROR);
						return false;
					}
					$result = $this->PARENT_OBJ->ZIP_OBJ->extract(PCLZIP_OPT_PATH, $extract_path);
					// Check for extraction errors
					if (!$result) {
						_re(t("Error while extracting files from ZIP archive"));
					}
				} elseif (in_array($ARCHIVE_TYPE, array("gz","bz2"))) {
					include (YF_PATH."libs/pear/Archive/Tar.php");
					if (class_exists("Archive_Tar")) {
						$this->PARENT_OBJ->TAR_OBJ = &new Archive_Tar($uploaded_file_path, $ARCHIVE_TYPE);
					}
					// Check if library loaded
					if (!is_object($this->PARENT_OBJ->TAR_OBJ)) {
						trigger_error("INSTALLER: Cant init Archive_Tar module", E_USER_ERROR);
						return false;
					}
					$this->PARENT_OBJ->TAR_OBJ->setErrorHandling(PEAR_ERROR_PRINT);
					$result = !$this->PARENT_OBJ->TAR_OBJ->extractModify($extract_path, '');
					// Check for extraction errors
					if (!$result) {
						_re(t("Error while extracting files from TAR @name archive", array("@name" => strtoupper($ARCHIVE_TYPE))));
					}
				}
			}
			// Try to find package description file
			if (!common()->_error_exists()) {
				// Use first found ".xml" file
				foreach ((array)$this->PARENT_OBJ->DIR_OBJ->scan_dir($extract_path, true, $this->PARENT_OBJ->_desc_file_pattern) as $file_name) {
					$package_desc_file_name = basename($file_name);
					break;
				}
				if (empty($package_desc_file_name)) {
					_re(t("Can not find package description xml file"));
				}
			}
			// Try to parse description file
			if (!common()->_error_exists()) {
				// Package name from description name
				$PACKAGE_NAME = substr($package_desc_file_name, 0, -strlen(".xml"));
				// Load XML parser
				$this->PARENT_OBJ->XML_OBJ = main()->init_class("xml_parser", "classes/");
				$this->PARENT_OBJ->XML_OBJ->xml_parse_document(file_get_contents($extract_path. $package_desc_file_name));
				$xml_data = &$this->PARENT_OBJ->XML_OBJ->xml_array;
				// Validate array parsed from package file
				if (!isset($xml_data["yf_install"]) || !isset($xml_data["yf_install"]["ATTRIBUTES"]["type"])) {
					_re(t("Cant find package info in XML data"));
				} elseif ($xml_data["yf_install"]["ATTRIBUTES"]["type"] != "admin_module") {
					_re(t("Wrong package type"));
				} elseif (empty($xml_data["yf_install"]["name"]["VALUE"]) || $PACKAGE_NAME != $xml_data["yf_install"]["name"]["VALUE"]) {
					_re(t("Wrong package name"));
				} elseif (empty($xml_data["yf_install"]["files"]["VALUE"])) {
					_re(t("Empty package files list"));
				}
			}
			// Check if such module exists
			if (!common()->_error_exists()) {
				if (isset($this->PARENT_OBJ->_modules[$PACKAGE_NAME])) {
					_re(t("Admin module \"@name\" already exists. If you want to install current package - you need to uninstall old one first.", array("@name" => $PACKAGE_NAME)));
				}
			}
			// Process package contents
			if (!common()->_error_exists()) {
				// Verify package files
				foreach ((array)$xml_data["yf_install"]["files"]["file_name"] as $file_info) {
					$cur_file_path = $extract_path.$file_info["VALUE"];
					// Check if such file exists
					if (!file_exists($cur_file_path)) {
						_re(t("Package file name \"@name\" not exists", array("@name" => $file_info["VALUE"])));
					}
					// Fill required arrays
					if ($file_info["ATTRIBUTES"]["type"] == "stpl") {
						$templates_list[]	= $cur_file_path;
					} elseif ($file_info["ATTRIBUTES"]["type"] == "php") {
						$php_files_list[]	= $cur_file_path;
					}
				}
			}
			// Copy required files into current project
			if (!common()->_error_exists()) {
				// Process templates
				foreach ((array)$templates_list as $file_path) {
					// Prepare sub folders
					$file_dir = dirname($file_path);
					if (!file_exists($file_dir)) {
						_mkdir_m($file_dir);
					}
					copy($file_path, INCLUDE_PATH. str_replace($extract_path, "", $file_path));
				}
				// Process PHP files
				foreach ((array)$php_files_list as $file_path) {
					// Prepare sub folders
					$file_dir = dirname($file_path);
					if (!file_exists($file_dir)) {
						_mkdir_m($file_dir);
					}
					copy($file_path, ADMIN_REAL_PATH. str_replace($extract_path, "", $file_path));
				}
			}
			// Execute queries if found ones
			if (!common()->_error_exists()) {
				foreach ((array)$xml_data["yf_install"]["install_queries"]["query"] as $query_info) {
					db()->query($query_info["VALUE"]);
				}
			}
			// Run custom installer file
			if (!common()->_error_exists()) {
				$custom_install_file = $xml_data["yf_install"]["install_file"]["VALUE"];
				if (!empty($custom_install_file) && file_exists($extract_path.$custom_install_file)) {
					@eval(file_get_contents($extract_path.$custom_install_file));
				}
			}
			// Add module record into db
			if (!common()->_error_exists()) {
				db()->INSERT("admin_modules", array(
					"name"			=> _es($PACKAGE_NAME),
					"description"	=> _es($xml_data["yf_install"]["description"]["VALUE"]),
					"version"		=> _es($xml_data["yf_install"]["version"]["VALUE"]),
					"author"		=> _es($xml_data["yf_install"]["author"]["VALUE"]),
					"active"		=> 0,
				));
			}
			// Put uninstall info (file and queries) into uninstall repository
			if (!common()->_error_exists()) {
// TODO
			}
			// Refresh system cache
			if (!common()->_error_exists()) {
				if (main()->USE_SYSTEM_CACHE)	cache()->refresh("admin_modules");
			}
			// Cleanup imported files
			$this->PARENT_OBJ->DIR_OBJ->delete_dir($tmp_folder_path, true);
			if (file_exists($uploaded_file_path)) {
				unlink($uploaded_file_path);
			}
		}
		// Display form
		if (!$_POST || common()->_error_exists()) {
			$replace = array(
				"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
				"back_link"			=> "./?object=".$_GET["object"],
				"error_message"		=> _e(),
			);
			return tpl()->parse($_GET["object"]."/import", $replace);
		}
	}

	/**
	* Export module
	* 
	*/
	function _export () {
		// Do save data
		if ($_POST) {
			// Check file format
			if (empty($_POST["file_format"]) || !isset($this->PARENT_OBJ->_file_formats[$_POST["file_format"]])) {
				_re(t("Please select file format"));
			}
			// Check module name to export
			if (empty($_POST["module"])) {
				_re(t("Please select module to export"));
			}
			// Do export
			if (!common()->_error_exists()) {
				// Prepare temporary folder
				$tmp_folder_path = INCLUDE_PATH. $this->PARENT_OBJ->TEMP_DIR. "export_module".($this->PARENT_OBJ->USE_UNIQUE_TMP_DIR ? "_".substr(md5(microtime(true)), 0, 8) : "")."/";
				if (file_exists($tmp_folder_path)) {
					$this->PARENT_OBJ->DIR_OBJ->delete_dir($tmp_folder_path);
				} else {
					_mkdir_m($tmp_folder_path);
				}
				// Create export dir for user templates
				$tmp_templates_path = $tmp_folder_path.tpl()->_THEMES_PATH."admin/";
				_mkdir_m($tmp_templates_path);
				// Try to find templates for the specified module
				$templates_path = INCLUDE_PATH.tpl()->_THEMES_PATH. conf('theme')."/".$_POST["module"]."/";
				$templates_dir_contents = $this->PARENT_OBJ->DIR_OBJ->scan_dir($templates_path, true, $this->PARENT_OBJ->_include_pattern);
				// Copy templates
				foreach ((array)$templates_dir_contents as $file_name) {
					$new_file_name = str_replace($templates_path, "", $file_name);
					// Check if folders exists in template name
					if (false !== strpos($new_file_name, "/")) {
						_mkdir_m($tmp_templates_path.dirname($new_file_name));
					}
					copy($file_name, $tmp_templates_path.$new_file_name);
				}
				// Create export dir for user modules
				$tmp_modules_path = $tmp_folder_path.ADMIN_MODULES_DIR;
				_mkdir_m($tmp_modules_path);
				// Copy module file
				$module_file_name = $_POST["module"].CLASS_EXT;
				$module_source_path = ADMIN_REAL_PATH.ADMIN_MODULES_DIR.$module_file_name;
				if (file_exists($module_source_path)) {
					copy($module_source_path, $tmp_modules_path.$module_file_name);
				}
				// Try to get sub modules
				$sub_modules_path = ADMIN_REAL_PATH.ADMIN_MODULES_DIR.$_POST["module"]."/";
				if (file_exists($sub_modules_path)) {
					_mkdir_m($tmp_modules_path.$_POST["module"]."/");
					$sub_modules_dir_contents = $this->PARENT_OBJ->DIR_OBJ->scan_dir($sub_modules_path, true, $this->PARENT_OBJ->_include_pattern);
					foreach ((array)$sub_modules_dir_contents as $file_name) {
						$new_file_name = str_replace($sub_modules_path, "", $file_name);
						copy($file_name, $tmp_modules_path.$_POST["module"]."/".$new_file_name);
					}
				}
				// Get files list to be exported
				$export_dit_contents = $this->PARENT_OBJ->DIR_OBJ->scan_dir($tmp_folder_path, true, $this->PARENT_OBJ->_include_pattern);
				foreach ((array)$export_dit_contents as $file_name) {
					if (basename($file_name) == $_POST["module"].".xml") {
						continue;
					}
					$export_files[] = array(
						"type"	=> common()->get_file_ext($file_name),
						"name"	=> str_replace($tmp_folder_path, "", $file_name),
					);
				}
				// Put description xml file
				$replace2 = array(
					"install_type"		=> _prepare_html("admin_module"),
					"module_name"		=> _prepare_html($_POST["module"]),
					"module_desc"		=> _prepare_html("!!!TODO!!!"),
					"module_author"		=> _prepare_html("!!!TODO!!!"),
					"install_file"		=> _prepare_html("install.".$_POST["module"].".php"),
					"uninstall_file"	=> _prepare_html("uninstall.".$_POST["module"].".php"),
//					"files"				=> $export_files,
					"files"				=> $user_files,
					"install_queries"	=> $install_queries,
					"uninstall_queries"	=> $uninstall_queries,
//					"admin_files"		=> $admin_files,
					"admin_files"		=> $export_files,
					"params"			=> $install_params,
				);
				$desc_file_string = tpl()->parse($_GET["object"]."/desc_file", $replace2);
				$desc_file_string = preg_replace("/(\r\n){2,}/", "\r\n", $desc_file_string);
				$desc_file_path = $tmp_folder_path.$_POST["module"].".xml";
				file_put_contents($desc_file_path, $desc_file_string);
				// Create new archive name
				foreach ((array)$this->PARENT_OBJ->DIR_OBJ->scan_dir($tmp_folder_path) as $file_name) {
					$items_for_archive[] = str_replace($tmp_folder_path, "", $file_name);
				}
				$new_archive_name	= dirname($tmp_folder_path)."/yf_admin_module__".$_POST["module"].($this->PARENT_OBJ->USE_UNIQUE_TMP_DIR ? "_".substr(md5(microtime(true)), 0, 8) : "").".".($_POST["file_format"] == "zip" ? $_POST["file_format"] : "tar.".$_POST["file_format"]);
				$_old_dir_name	= str_replace("\\", "/", getcwd());
				chdir($tmp_folder_path);
				// Put result files to archive
				if (in_array($_POST["file_format"], array("gz","bz2"))) {
					include (YF_PATH."libs/pear/Archive/Tar.php");
					if (class_exists("Archive_Tar")) {
						$this->PARENT_OBJ->TAR_OBJ = &new Archive_Tar($new_archive_name, $_POST["file_format"]);
					}
					// Check if library loaded
					if (!is_object($this->PARENT_OBJ->TAR_OBJ)) {
						trigger_error("INSTALLER: Cant init Archive_Tar module", E_USER_ERROR);
						return false;
					}
					$this->PARENT_OBJ->TAR_OBJ->create($items_for_archive);
					// Set headers params
					if ($_POST["file_format"] == "gz") {
						$mime_type = "application/x-gzip";
						// needed to avoid recompression by server modules like mod_gzip:
						$content_encoding = 'x-gzip';
					} else {
						$mime_type = "application/x-bzip2";
					}
				} elseif ($_POST["file_format"] == "zip") {
					// Init zip object
					main()->load_class_file("pclzip", "classes/");
					if (class_exists("pclzip")) {
						$this->PARENT_OBJ->ZIP_OBJ = &new pclzip($new_archive_name);
					}
					// Check if library loaded
					if (!is_object($this->PARENT_OBJ->ZIP_OBJ)) {
						trigger_error("INSTALLER: Cant init PclZip module", E_USER_ERROR);
						return false;
					}
					$this->PARENT_OBJ->ZIP_OBJ->create($items_for_archive);
					// Set headers params
					$mime_type = "application/zip";
				}
				chdir($_old_dir_name);
			}
			// Get archive into string
			$body = file_get_contents($new_archive_name);
			// Check for errors
			if (!common()->_error_exists()) {
				if (empty($body)) {
					_re(t("Error while exporting data"));
				}
			}
			// Check for errors
			if (!common()->_error_exists()) {
				main()->NO_GRAPHICS = true;
				// Prepare file name to download
				$file_name = "yf_admin_module__".$_POST["module"].".".($_POST["file_format"] == "zip" ? $_POST["file_format"] : "tar.".$_POST["file_format"]);
				// Throw headers
				if (!empty($content_encoding)) {
					header('Content-Encoding: ' . $content_encoding);
				}
				header("Content-Type: ". $mime_type);
				header("Content-Length: ".strlen($body));
				header("Expires: ". gmdate('D, d M Y H:i:s'). " GMT");
				// Get browser detailed info
				$BROWSER_INFO = common()->get_browser_info();
				// IE need specific headers
				if ($BROWSER_INFO["USER_BROWSER_AGENT"] == 'IE') {
					header("Content-Disposition: inline; filename=\"".$file_name."\"");
					header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
					header("Pragma: public");
				} else {
					header("Content-Disposition: attachment; filename=\"".$file_name."\"");
					header("Pragma: no-cache");
				}		   
				// Throw content
				echo $body;
			}
			// Cleanup export files
			$this->PARENT_OBJ->DIR_OBJ->delete_dir($tmp_folder_path, true);
			if (file_exists($new_archive_name)) {
				unlink($new_archive_name);
			}
		}
		// Display form
		if (!$_POST || common()->_error_exists()) {
			$replace = array(
				"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
				"modules_box"		=> $this->PARENT_OBJ->_box("module"),
				"file_formats_box"	=> $this->PARENT_OBJ->_box("file_format", "zip"),
				"back_link"			=> "./?object=".$_GET["object"],
				"error_message"		=> _e(),
			);
			return tpl()->parse($_GET["object"]."/export", $replace);
		}
	}
}
