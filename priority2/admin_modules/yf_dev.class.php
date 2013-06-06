<?php

/**
* Development methods
*/
class yf_dev{

	/** @var array */
	var $_colors = array(
		"background",
		"background-color",
		"background-image",
// TODO: other background atts
		"border",
		"border-top",
		"border-right",
		"border-bottom",
		"border-left",
		"border-color",
		"border-top-color",
		"border-right-color",
		"border-bottom-color",
		"border-left-color",
		"color",
		"outline-color",
	);

	/** @var array */
	var $_fonts = array(
		"font",
		"font-family",
		"font-style",
		"font-variant",
		"font-weight",
		"font-size",
		"height",
		"line-height",
		"text-decoration",
		"letter-spacing",
		"word-spacing",
		"text-transform",
	);
	/** @var string Physical path to php.exe */
	var	$PHP_REALPATH = "D:/www/php5/php.exe";

	
	//#####make_wiki_xml VARS###### 
		/** @var string @conf_skip */
	var $_regexp_pairs_cleanup = array(
		"/([^\s\t])[\s\t]+=>/ims"
			=> "\\1=>",
		"/(array\(|[0-9a-z\'\"]+,|=>)[\s\t]+([^\s\t])/ims"
			=> "\\1\\2",
		"/[\s\t]+\)\$/ims"
			=> ")",
	);
	/** @var string @conf_skip */
	var $_var_regexp	= "/\tvar[\s\t]{1,}\\\$([a-z_][a-z0-9_]*)[\s\t]*=[\s\t]*([^;]+);/ims";
	/** @var string @conf_skip */
	var $_info_regexp	= "/\t\/\*\*[^@]*[\s\t]{1,}@var[\s\t]{1,}(bool|int|float|array|string|mixed) (.*?)\*\/[\r\n]*\tvar[\s\t]{1,}\\\$([a-z_][a-z0-9_]*)/ims";
	/** @var array  @conf_skip */
	var $_allowed_types	= array(
		"bool",
		"int",
		"float",
		"array",
		"string",
		"mixed",
	);
	/** @var string @conf_skip */
	var $_SYSTEM_NS		= "_";
	
	/** @var string @conf_skip Class method pattern */
	var $_method_pattern		= "/function ([a-zA-Z_][a-zA-Z0-9_]+)/is";

	//### END make_wiki_xml####

	function _init() {
		// Init dir class
		$this->DIR_OBJ = main()->init_class("dir", "classes/");
	}

	/**
	* List all available methods
	*/
	function show () {
		$methods = array();
		$class_name = get_class($this);
		foreach ((array)get_class_methods($class_name) as $_method_name) {
			// Skip unwanted methods
			if ($_method_name{0} == "_" || $_method_name == $class_name || $_method_name == __FUNCTION__) {
				continue;
			}
			$methods[] = array(
				"link"	=> "./?object=".$_GET["object"]."&action=".$_method_name,
				"name"	=> $_method_name,
			);
		}
		// Process template
		$replace = array(
			"methods"	=> $methods,
		);
		return tpl()->parse(__CLASS__."/main", $replace);
	}
	
	/**
	* Convert apache (cpanel-based) virtualhosts into nginx virtual hosts
	*/
	function nginx_virtualhosts () {
		$vhost_regexp		= "#<VirtualHost(?P<host>[^>]+)>(?P<content>.*?)<\/VirtualHost>#ims";
		$clean_host_regexp	= "#[^0-9\.:\*\-a-z\s]#i";
		$server_name_regexp = "#ServerName ([a-z0-9\*\.\-]+)#ims";
		$server_alias_regexp= "#ServerAlias ([^\r\n]+)#ims";
		$doc_root_regexp	= "#DocumentRoot ([a-z0-9\*\.\/\-\_]+)#ims";
		// Process config
		if ($_POST) {
			// Do parse pache conf
			preg_match_all($vhost_regexp, $_POST["apache_conf"], $m);
			foreach ((array)$m["host"] as $k => $listen) {
				$listen = preg_replace($clean_host_regexp, "", trim($listen));
				$content = $m["content"][$k];

				$server_name	= "";
				if (preg_match($server_name_regexp, $content, $m2)) {
					$server_name = trim($m2[1]);
				}

				$server_aliases	= "";
				if (preg_match_all($server_alias_regexp, $content, $m2)) {
					foreach ((array)$m2[1] as $k2 => $v2) {
						$server_aliases[] = $v2;
					}
				}
				$doc_root		= "";
				if (preg_match($doc_root_regexp, $content, $m2)) {
					$doc_root = trim($m2[1]);
				}

				$hosts[] = array(
					"listen"	=> $listen,
					"name"		=> $server_name,
					"aliases"	=> is_array($server_aliases) ? implode(" ", $server_aliases) : $server_aliases,
					"root"		=> $doc_root,
				);
			}
			// Do generate nginx conf
			$nginx_sites = array();
			foreach ((array)$hosts as $server) {
				$server["listen"] = str_replace(":81", "", $server["listen"]);
				if (false === strpos($server["listen"], ":")) {
					$server["listen"] .= ":80";
				}
				$nginx_sites[] = 
"server {
	listen      ".$server["listen"].";
	server_name  ".str_replace(":81", "", $server["name"]." ".$server["aliases"]).";
	root ".$server["root"].";

    include nginx_shared.conf;

	location @proxy_without_cache {
        proxy_pass	http://".$server["name"].":81;
        proxy_set_header Host \$server_name;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
		proxy_set_header If-Modified-Since \"\";
    }
	location @proxy_with_cache {
		proxy_cache mycache;
		proxy_cache_valid 200 301 302 304 5m;
		proxy_cache_key \"\$request_method|\$host|\$request_uri\";
		proxy_hide_header \"Set-Cookie\";
		proxy_ignore_headers \"Cache-Control\" \"Expires\";

        proxy_pass  http://".$server["name"].":81;
        proxy_set_header Host \$server_name;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
		proxy_set_header If-Modified-Since \"\";
	}
}
";
			}
//print_R($hosts);
			$body .= "<pre>".str_replace(array("    ", "\r"), array("\t", ""), implode("\n\n\n", $nginx_sites)."</pre>");
		}
		$body .= "<form action='./?object=".$_GET["object"]."&action=".$_GET["action"]."' method='post'>
					<textarea name='apache_conf' style='width:90%; height:200px;'></textarea>
					<br /><input type='submit' value='Convert!' />
				</form>";
		return $body;
	}
	
	/**
	* Search code
	*/
	function code_search() {
		if (!empty($_POST) && strlen($_POST["text"])) {
			$items = array();
			$DIR_OBJ = main()->init_class("dir", "classes/");
			foreach ((array)$DIR_OBJ->search(array(INCLUDE_PATH/*, YF_PATH*/), array("", "/\.class\.php/"), "/(svn|git)/", "#".preg_quote($_POST["text"], "#")."#") as $_path) {
				$items[] = array(
					"path"		=> _prepare_html($_path),
					"edit_link"	=> "./?object=file_manager&action=edit_item&id=".urlencode($_path),
				);
			}
		}
		// Process template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"text"			=> _prepare_html($_POST["text"]),
			"items"			=> $items ? $items : "",
			"total"			=> $items ? count($items) : "",
		);
		return tpl()->parse(__CLASS__."/code_search", $replace);
	}
	
	/**
	* make wiki(import) xml-file from yf_framework
	*/
	function make_wiki_xml(){
	
		$pattern_include = array("", "#".preg_quote(CLASS_EXT)."\$#");
		$pattern_exclude = "#\.(svn|git)#";
		$DIR_OBJ = main()->init_class("dir", "classes/");

		$_fwork_classes = $DIR_OBJ->scan_dir(YF_PATH."classes/", 1, $pattern_include, $pattern_exclude);
		$_project_classes = $DIR_OBJ->scan_dir(INCLUDE_PATH."classes/", 1, $pattern_include, $pattern_exclude);

		$_ext_len = strlen(CLASS_EXT);
		$_fwork_class_names = array();
		foreach ((array)$_fwork_classes as $_path) {
			$_class_name = str_replace(PF_PREFIX, "", substr(strrchr($_path, "/"), 1, -$_ext_len));
			$_classes[$_class_name] = $_path;
			$_classes_for_vars[$_class_name] = $_class_name;
			
		}
//		print_r($_classes);
		//list($PARSED_CONF_3, $PARSED_META_3) = $this->_collect_conf("classes/", $_classes_for_vars);
		//print_r($PARSED_META_3);
	
	
		$all_modules["admin"] = main()->_execute("admin_modules", "_get_modules");
		$all_modules["user"] = main()->_execute("user_modules", "_get_modules");
		$all_modules["classes"] = $_classes;

		ksort($all_modules["classes"]);
	
		foreach ((array)$all_modules as $modules_key => $modules_value){
		
			$PARSED_CONF_1 = array();
			$PARSED_META_1 = array();

			if($modules_key == "admin"){
				list($PARSED_CONF_1, $PARSED_META_1) = $this->_collect_conf(ADMIN_MODULES_DIR, $all_modules["admin"]);
			}
			
			if($modules_key == "user"){
				list($PARSED_CONF_1, $PARSED_META_1) = $this->_collect_conf(USER_MODULES_DIR, $all_modules["user"]);
			}

			if($modules_key == "classes"){
				list($PARSED_CONF_1, $PARSED_META_1) = $this->_collect_conf("classes/", $_classes_for_vars);
			}

			$id = 2;
			foreach ((array)$modules_value as $module_name => $module){
				$vars = array();
				$links[] = $modules_key == "admin"?"adm__".$module:$module;
				
				if($modules_key == "admin"){
					$links_admin[] = "adm__".$module;
				}
				if($modules_key == "user"){
					$links_user[] = $module;
				}
				if($modules_key == "classes"){
					$links_class[] = $module_name;
				}
				
				if((!empty($PARSED_META_1[$module])) or (!empty($PARSED_META_1[$module_name]))){
					if($modules_key == "classes"){
						$META = $PARSED_META_1[$module_name];
					}else{
						$META = $PARSED_META_1[$module];
					}
					
					foreach ((array)$META as $key => $meta){
						$desc = str_replace("&","&amp;",$meta["desc"]);
						$desc = str_replace("<","&lt;",$desc);
						$desc = str_replace(">","&gt;",$desc);
						
						$vars[$key] = array("type" => $meta["type"], "desc" => $desc);
					}
				}
				
				if(!empty($vars)){
					ksort($vars);
				}
				
				if($modules_key == "admin"){
					$path = YF_PATH."admin_modules/yf_".$module.".class.php";
				}
				if($modules_key == "user"){
					$path = YF_PATH."modules/yf_".$module.".class.php";
				}
				if($modules_key == "classes"){
					$path = $module;
				}

				$text = file_get_contents($path);
				$methods = array();
				$private_methods = array();
				$methods = $this->_get_methods_names_from_text($text);
				if(!empty($methods)){
					ksort($methods);
				}
				$private_methods = $this->_get_methods_names_from_text($text, 1);

				if(!empty($private_methods)){
					ksort($private_methods);
					$methods = my_array_merge($methods, $private_methods);
				}
				
				

				if($modules_key == "classes"){
					$title = $module_name;
				}else{
					$title = $modules_key == "admin"?"adm__".$module:$module;
				}
				
				$replace2 = array(
					"title"		=> $title,
					"id"		=> $id,
					"methods"	=> $methods,
					"vars"		=> $vars,
				);
				
				$pages .= tpl()->parse($_GET["object"]."/wiki_xml_page", $replace2);
				$id++;
			}
		}
		
		ksort($links);
		
		$replace = array(
			"pages"				=> $pages,
			"links_admin"		=> $links_admin,
			"links_user"		=> $links_user,
			"links_class"		=> $links_class,
		);
		
		$xml = tpl()->parse($_GET["object"]."/wiki_xml", $replace);
		
		file_put_contents("../import_wiki.xml", $xml);
		
		//echo $xml;
		return "File 'import_wiki.xml' saved to project dir";
		
	}
	
	
	function _get_methods_names_from_text ($text = "", $ONLY_PRIVATE_METHODS = false) {
		$methods = array();
		if (empty($text)) {
			return $methods;
		}
		preg_match_all($this->_method_pattern, $text, $matches);
		foreach ((array)$matches[1] as $method_name) {
			$_is_private_method = ($method_name[0] == "_");
			// Skip non-needed methods
			if ($ONLY_PRIVATE_METHODS && !$_is_private_method) {
				continue;
			}
			if (!$ONLY_PRIVATE_METHODS && $_is_private_method) {
				continue;
			}
			$methods[$method_name] = $method_name;
		}
		ksort($methods);
		return $methods;
	}

	
	
	
	
	
	function _collect_conf ($dir_name = "", $modules_list = array()) {
		if (empty($dir_name) || empty($modules_list)) {
			return false;
		}
		$PARSED_CONF = array();
		$PARSED_META = array();

		foreach ((array)$modules_list as $_module_name) {
			$file_path			= REAL_PATH.$dir_name.$_module_name.CLASS_EXT;
			$file_path_fwork	= "";
			$file_path_project	= "";
			$_source = "site";
			if (!file_exists($file_path)) {
				$file_path = INCLUDE_PATH.$dir_name.$_module_name.CLASS_EXT;
				$_source = "project";
				if (!file_exists($file_path)) {
					$file_path = YF_PATH.$dir_name.PF_PREFIX.$_module_name.CLASS_EXT;
					$_source = "framework";
					if (!file_exists($file_path)) {
						continue;
					}
				} else {
					$file_path_fwork = YF_PATH.$dir_name.PF_PREFIX.$_module_name.CLASS_EXT;
				}
			} else {
				$file_path_fwork	= YF_PATH.$dir_name.PF_PREFIX.$_module_name.CLASS_EXT;
				$file_path_project	= INCLUDE_PATH.$dir_name.$_module_name.CLASS_EXT;
			}

			$_tmp_conf = array();
			$_tmp_meta = array();
			if (!empty($file_path_fwork) && file_exists($file_path_fwork)) {
				list($PARSED_CONF_1, $PARSED_META_1) = $this->_get_conf_from_file($file_path_fwork);
				$_tmp_conf = $PARSED_CONF_1;
				$_tmp_meta = $PARSED_META_1;
			}
			if (!empty($file_path_project) && file_exists($file_path_project)) {
				list($PARSED_CONF_2, $PARSED_META_2) = $this->_get_conf_from_file($file_path_project);
				$_tmp_conf = my_array_merge($_tmp_conf, (array)$PARSED_CONF_2);
				$_tmp_meta = my_array_merge($_tmp_meta, (array)$PARSED_META_2);
			}
			list($PARSED_CONF_3, $PARSED_META_3) = $this->_get_conf_from_file($file_path);
			$_tmp_conf = my_array_merge($_tmp_conf, (array)$PARSED_CONF_3);
			$_tmp_meta = my_array_merge($_tmp_meta, (array)$PARSED_META_3);

			$PARSED_CONF[$_module_name] = $_tmp_conf;
			$PARSED_META[$_module_name] = $_tmp_meta;
		}
		return array($PARSED_CONF, $PARSED_META);
	}

	/**
	* Create array code recursive
	*/
	function _get_conf_from_file ($file_path = "") {
		if (empty($file_path)) {
			return false;
		}
		$CONF = array();
		$META = array();

		$test_string = file_get_contents($file_path);
		if (empty($test_string)) {
			return false;
		}
		// Get conf items
		preg_match_all($this->_var_regexp, $test_string, $m);
		foreach ((array)$m[0] as $_m_id => $_m_tmp) {
			$var_name	= $m[1][$_m_id];
			$value		= $m[2][$_m_id];
			$CONF[$var_name] = @eval("return ".$value.";");
		}
		$m = false;

		// Get conf meta
		preg_match_all($this->_info_regexp, $test_string, $m);
		foreach ((array)$m[0] as $_m_id => $_m_tmp) {
			$type		= $m[1][$_m_id];
			$desc		= $m[2][$_m_id];
			$var_name	= $m[3][$_m_id];
			if (!strlen($type) && !strlen($desc)) {
				continue;
			}
			// Check if current var needed to be skipped
			if (false !== strpos($desc, "@conf_skip")) {
				unset($CONF[$var_name]);
				continue;
			}
			// Cleanup comments new lines with "*"
			$desc = str_replace("\n\t* ", "\n", $desc);
			$META[$var_name] = array(
				"type"	=> trim($type),
				"desc"	=> trim($desc),
			);
		}
		return array($CONF, $META);
	}

	
	

	
//#######################################################################	
	


	/**
	* Try to find non-closed HTML tags
	*/
	function check_tag($internal_request = false){
		$DIRS = array(
			"framework" => YF_PATH."templates/",
			"project"	=> "../templates/",
		);
	
		$tag = array(
			"open"			=>  "\<(a|b|u|i|s|p|pre|small|label|div|blockquote|code|dfn|em|form|h1|h2|h3|h4|h5|h6|head|thead|tbody|kbd|map|marquee|nobr|ol|pre|q|samp|select|span|strong|sub|sup|table|td|th|title|tr|tt|ul|var|legend|fieldset)(|[\s\t]+[^\>]*)\>",
			"close"			=> "<\/(a|b|u|i|s|p|pre|small|label|div|blockquote|code|dfn|em|form|h1|h2|h3|h4|h5|h6|head|thead|tbody|kbd|map|marquee|nobr|ol|pre|q|samp|select|span|strong|sub|sup|table|td|th|title|tr|tt|ul|var|legend|fieldset)>",
		);
		
		$project_exclude_file_path = "../share/dev_check_tag_exclude_files.php";
		$framework_exclude_file_path = YF_PATH."/share/dev_check_tag_exclude_files.php";
		
		if(file_exists($project_exclude_file_path)){
			include $project_exclude_file_path;
			$exclude_files_list = $exclude_files_list_project;
		}
		if(file_exists($framework_exclude_file_path)){
			include $framework_exclude_file_path;
			
			foreach ((array)$exclude_files_list_framework as $val){
				$exclude_files_list[] = YF_PATH.$val;
			}
		}
		
		if(isset($_POST["go"]) || $internal_request == true){
			$OBJ_DIR = main()->init_class("dir", "classes/");
			
			if($internal_request){
				$_POST["dir_path"] = array(
					"framework"	=> "on",
					"project"	=> "on",
				);
			}
			
			foreach ((array)$_POST["dir_path"] as $dir_path_name => $status){
			 	$files_temp = $OBJ_DIR->scan_dir($DIRS[$dir_path_name],"false",array("", "/.*\.stpl/"),"/\.(svn|git)/");
				$files = my_array_merge($files, $files_temp);
			}	
			
			foreach ((array)$files as $key => $file_path){
					if(in_array($file_path, (array)$exclude_files_list)){
					continue;
				}
				
				$content = file_get_contents($file_path);
				// delete javascript in content
				$content = preg_replace('/<script(.+?)<\/script>/si', "", $content);

				preg_match_all('/'.$tag["open"].'/si', $content, $matches_open);
				preg_match_all('/'.$tag["close"].'/si', $content, $matches_close);
				
				foreach ((array)$matches_open[1] as $_m) {
					$tag_key = strtolower($_m);
					$files_result[$key][$tag_key]["count_open"]++;
				}
				
				// for <a name="1" />
				preg_match_all('/\<(a)(|[\s\t]+[^\>]*)\/\>/si', $content, $matches_a);
				$matches_a_count = count($matches_a[1]);
				if($matches_a_count > 0){
					$files_result[$key]["a"]["count_open"] -= $matches_a_count;
				}
				
				foreach ((array)$matches_close[1] as $_m) {
					$tag_key = strtolower($_m);
					$files_result[$key][$tag_key]["count_close"]++;
				}
			}
			
			//print_r($files_result);
			foreach ((array)$files_result as $files_result_key => $files_result_info){
				$flag = 0;
				foreach ((array)$files_result_info as $tag_name => $tag){
					if($tag["count_open"] != $tag["count_close"]){
						$count_errors++;
						if(!$internal_request){
							$replace2 = array(
								"file_path"			=> $flag!=1?$files[$files_result_key]:"",
								"editor_url"		=> "./?object=file_manager&action=edit_item&f_=".basename($files[$files_result_key])."&dir_name=".urlencode(dirname($files[$files_result_key])),
								"tag_name"			=> $tag_name,
								"tag_open_count"	=> $tag["count_open"]==""?0:$tag["count_open"],
								"tag_close_count"	=> $tag["count_close"]==""?0:$tag["count_close"],
							);
							
							$items.= tpl()->parse($_GET["object"]."/tag_item", $replace2);
							$flag = 1;
						}
					}
				}
			}
			
			if($internal_request){
				return intval($count_errors);
			}

		}
		foreach ((array)$DIRS as $name => $path){
			$replace3 = array(
				"name"		=> $name,
				"path"		=> $path,
			);
			$check_box .= tpl()->parse($_GET["object"]."/tag_check_box", $replace3);
		}
		$replace = array(
			"action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"check_box"		=> $check_box,
			"items"			=> $items,
		);
		return tpl()->parse($_GET["object"]."/tag_main", $replace);
	}
	
	/**
	* Converter from old HTML sreucture into new
	*/
	function form_converter(){
		function form_convert () {
			if (!$_POST["source"]) {
				return false;
			}
			$_process = $_POST["source"];

			$replace = array(
				"/(<td[^>]*>)(.*<table[^>]*>.*<\/table>.*)(<\/td>)/ims"	=> "$2",
				"/(<tr[^>]*>)([^<]*?)(<td[^>]*colspan=\"2\"[^>]*>)([^<]*<input type=\"submit\"[^>]*>.*)(<\/td>)([^<]*?)(<\/tr>)/ims"	=> "<div class=\"button_div\">$2$4$6</div>",
			);
			$_result = preg_replace(array_keys($replace), array_values($replace), $_process);

			$replace = array(
				"/(<tr[^>]*>)([^<]*?)(<td[^>]*colspan=\"2\"[^>]*>)(.*?)(<\/td>)([^<]*?)(<\/tr>)/ims"	=> "<p class=\"full_width\">$2$4$6</p>",		
			);
			$_result = preg_replace(array_keys($replace), array_values($replace), $_result);

			$replace = array(
				"/<table[^>]*>/ims"				=> "<div class=\"editform\">",
				"/<\/table>/ims"				=> "</div>",
				"/(\t)*(<[\/]*form[^>]*>)/ims"	=> "$2",
				"/<tr[^>]*>/ims"				=> "<p>",
				"/<\/tr>/ims"					=> "</p>",
				"/<b>/ims"						=> "",
				"/<\/b>/ims"					=> "",
				"/(<td[^>]*>)(.*?)(<\/td>)([^<]*?)(<td[^>]*>)(.*?)(<\/td>)/ims"	=> "<label for=\"\">$2</label>$4$6",
			);
			$_result = preg_replace(array_keys($replace), array_values($replace), $_result);
			return $_result;
		}
	
		$_result = form_convert();
		$replace = array(
			"source"	=> _prepare_html($_POST["source"]),
			"result"	=> _prepare_html($_result),
			"form_action"=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
		);
    	return tpl()->parse($_GET["object"]."/form_convert_main", $replace);
	}

	/**
	* Extract colors from given CSS file
	*/
	function css_divider(){
		$this->_to_extract = array_merge($this->_colors, $this->_fonts);
		$this->comparison_array = array_flip($this->_to_extract);

		if (!empty($_POST["source"])){
			// Extract colors only
			if ($_POST["colors_only"]) {
				$this->_to_extract = $this->_colors;
				$this->comparison_array = array_flip($this->_to_extract);
			}
			$_result = $this->_parse_css($_POST["source"]);
		}
		$replace = array(
			"source"		=> _prepare_html($_POST["source"]),
			"result"		=> _prepare_html($_result),
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
		);
    	return tpl()->parse($_GET["object"]."/css_divider_main", $replace);
	}
	
	/**
	* Do parse given CSS string into array
	*/
	function _parse_css ($str) {
	    // Remove comments
	    $str = preg_replace("/\/\*(.*)?\*\//Usi", "", $str);

	    // Parse csscode into array [selector] => array([properties]=>[values])
	    $parts = explode("}",$str);
		foreach ((array)$parts as $part) {
			$part = trim($part);
	        list($keystr,$codestr) = explode("{",$part);
			$properties = explode(";", trim($codestr));
			foreach ((array)$properties as $A) {
				list($property, $value) = explode(":", $A);
				if ($property != ""){
					$this->css[trim($keystr)][trim($property)] = trim($value);
				}
			}
		}		
		// Compare and create array of extracted elements
		foreach ((array)$this->css as $selector => $attr) {
			$new_attr = array_intersect_key($attr, $this->comparison_array);
			if (!empty($new_attr)) {
				$output_css_array[$selector] = $new_attr;
			}
		}
		// Create CSS file content from array
		$output_css = "";
		foreach ((array)$output_css_array as $selector => $attr_array) {
			$attributes = "";
			foreach ((array)$attr_array as $property => $value) {
				$attributes .= "\t".$property.": ".$value.";\r\n";
			}
			$output_css .= $selector." {\r\n".$attributes."}\r\n";
		}
		return $output_css;
	}


	/**
	* Check script (classes, modules, admin modules, functions) for fatal errors
	*/
	function syntax_checker($internal_request = false){

//		ini_set("memory_limit", "64M");
//		set_time_limit(120);

		$folders_to_check = array(
			"framework" => array(
				"classes/",
				"admin_modules/",
				"modules/",
			),
			"project" => array(
				"admin/",
				"classes/",
				"modules/",
			),
		);

		$location_array = array_keys($folders_to_check);

		if (!empty($_POST) || $internal_request == true) {
		
			if($internal_request){
				$_POST["path_to_php"] = str_replace("\\", "/", dirname(ini_get("extension_dir"))); 
			}
		
			if ($_POST["path_to_php"] && file_exists($_POST["path_to_php"]."/php.exe")) {
				$this->PHP_REALPATH = $_POST["path_to_php"]."/php.exe"; 
			} else {
				_re("Cannot find php.exe file in ".$_POST["path_to_php"]);
			}
			
			// Check for errors and continue if ok
			if (!common()->_error_exists()) {
			
				foreach ((array)$_POST as $k => $post_data) {
					if (false !== strpos($k, "location_")) {
						$chosen_location[] = $location_array[$post_data];
					}
				}
				
				if($internal_request){
					$chosen_location[] = "project";
					$chosen_location[] = "framework";
				}
				
				
				if (main()->USE_SYSTEM_CACHE) {
					$syntax_check_file_hashes = cache()->get("syntax_check_file_hashes");
				}
			
				$hashes_old = (array)$syntax_check_file_hashes;
				// Make syntax checking
				// Reset file counter
				$i = 0;
				foreach ((array)$folders_to_check as $location => $fld) {
					if (!in_array($location, $chosen_location)) {
						continue;
					}
					if ($location == "framework") {
						$basedir = YF_PATH;
					} elseif ($location == "project") {
						$basedir = INCLUDE_PATH;
					}
		    
					foreach ((array)$fld as $folder) {
		    
						// Get array of files inside folder
						foreach ((array)$this->DIR_OBJ->scan_dir($basedir. $folder, true, array("", "/.php\$/"), "/(svn|git)/") as $fpath) {
							if (!strlen($fpath)) {
								continue;
							}
							$fpath = realpath($fpath);
							if (isset($retval)) {
								unset($retval);
							}
							$_file_hash = false;
							if (main()->USE_SYSTEM_CACHE) {
								$_file_hash = md5_file($fpath);
							}
							// Check file syntax if file changed or cache is empty
							if (!$_file_hash || $_file_hash != $syntax_check_file_hashes[$fpath]) {
								exec($this->PHP_REALPATH." -l ".$fpath, $retval);
								$retval = implode("\n", (array)$retval);
								if (false === strpos($retval, "No syntax errors detected")) {
									$p = strpos($retval, "Errors parsing");
		                            $retval = substr($retval, 0, $p);
									$error_message[] = $retval;
								} else {
									if (empty($syntax_check_file_hashes[$fpath]) || $_file_hash != $syntax_check_file_hashes[$fpath]) {
										$syntax_check_file_hashes[$fpath] = $_file_hash;
									}
								}
							}
							$i++;
						}
					}
				}
		    
				$files_changed = 0;			
				if (empty($hashes_old) || $hashes_old != $syntax_check_file_hashes) {
					$files_changed = 1;
				}
				// Put file hashes to cache
				if (main()->USE_SYSTEM_CACHE && $files_changed) {
					cache()->put("syntax_check_file_hashes", $syntax_check_file_hashes);
				}
		    
				$num_errors = count($error_message);
				
				if($internal_request){
					return $num_errors;
				}
				
				// Prepare template
				foreach ((array)$error_message as $error) {
					$replace2 = array(
						"error_message"	=> $error,
					);
					$errors_items .= tpl()->parse(__CLASS__ ."/syntax_checker_item", $replace2);
				}
				$replace = array (
					"errors_items"	=> $errors_items,
					"num_files"		=> $i,
					"num_errors"	=> $num_errors,
					"exec_time"		=> common()->_format_time_value(microtime(true) - main()->_time_start),
					"back_url"		=> "./?object=".$_GET["object"]."&action=syntax_checker",
				);
				return tpl()->parse(__CLASS__ ."/syntax_checker_main", $replace);
			}
		}

		// Finding path to PHP 
		$path_to_php = str_replace("\\", "/", dirname(ini_get("extension_dir")))."/"; 

		$replace = array(
			"error_message" => _e(),
			"cache_enabled"	=> (int)main()->USE_SYSTEM_CACHE,
			"php_version"	=> phpversion(),
			"path_to_php"	=> $path_to_php,
			"location_box"	=> common()->multi_check_box("location", $location_array, $location_array, true),
			"form_action"	=> "./?object=".$_GET["object"]."&action=syntax_checker",
		);
		return tpl()->parse($_GET["object"]."/syntax_checker_form", $replace);
	}

	/**
	* Convert wordpress themes into PF structure
	*/
	function wp_converter(){

		//Settings
		$CENTER_BLOCK = "<!-- CENTER_AREA -->\n{execute(graphics,_show_block,name=center_area)}\n<!-- /CENTER_AREA -->";
		$ELEMENTS_ARRAY = array(
			"<?php wp_title(); ?>"							=> "{conf('page_title')}",
			"<?php bloginfo('name'); ?>"					=> "{conf('site_title')}",
		);
		$NEW_META_TAGS = array(
			"<meta name='generator' content='YFFramework' />",
		);

//TODO atomatic theme download


		if (!empty($_POST) || !empty($_FILES)) {

			if ($_FILES["theme"]["type"] != "application/zip") {
				_re("Not a zip file");
			}

			// Decompress zip file into temporary folder
			$tmp_dir = INCLUDE_PATH."uploads/tmp/";

			$archive_path = $_FILES["theme"]["tmp_name"];
			$extract_path = $tmp_dir;

			// Init zip object
			main()->load_class_file("pclzip", "classes/");
			if (class_exists("pclzip")) {
				$ZIP_OBJ = &new pclzip($archive_path);
				$result = $ZIP_OBJ->extract(PCLZIP_OPT_PATH, $extract_path); 
			} 

			$original_theme_name = $result[0]["stored_filename"];

			$ELEMENTS_ARRAY = my_array_merge($ELEMENTS_ARRAY, array(
				"<?php bloginfo('stylesheet_directory'); ?>"	=> "const{WEB_PATH}templates/".$original_theme_name,
				"<?php bloginfo('stylesheet_url'); ?>"			=> "const{WEB_PATH}templates/".$original_theme_name."style.css",
			));

//print_r($result);
			$extracted_theme_folder = $result[0]["filename"];
			
			$tmp_content_string = "";
			
			$tmp_content_string .= file_get_contents($extracted_theme_folder."header.php");
			$tmp_content_string .= $CENTER_BLOCK;
			$tmp_content_string .= file_get_contents($extracted_theme_folder."footer.php");

// INCLUDE_PATH. tpl()->TPL_PATH
// WEB_PATH. tpl()->TPL_PATH

			// Find elements and replace them
			$tmp_content_string = str_replace(array_keys($ELEMENTS_ARRAY), array_values($ELEMENTS_ARRAY), $tmp_content_string);


			// Cleanup from the rest PHP entries
			$tmp_content_string = preg_replace("/(<\?php)(.+?)(\?>)/ims", "", $tmp_content_string);
			//Find old meta-tags and remove them 
			$tmp_content_string = preg_replace("/<meta[^>]+\>/ims", "", $tmp_content_string);
			$tmp_content_string = preg_replace("/<\/title>/ims", "</title>\n".implode("\n", $NEW_META_TAGS), $tmp_content_string);


			// Create theme folder
			$theme_folder_path = INCLUDE_PATH."templates/".$original_theme_name;
			if (!file_exists($theme_folder_path)){
				_mkdir_m($theme_folder_path);
			}

			// Save		
			file_put_contents($theme_folder_path."main.stpl" ,$tmp_content_string);


			// Process post template
			$POST_ELEMENTS_ARRAY = array(
				"/<\?php else : \?>(.)+?<\?php endif; \?>/ims" 	=> "",
				"/<\?php the_permalink\(\) \?>/ims"				=> "{post_link}",
				"/<\?php the_title\(\); \?>/ims"				=> "{title}",
				"/<\?php the_content\((.)+?\); \?>/ims"			=> "{description}",
				"/<\?php the_time\((.)+?\) \?>/ims"				=> "{add_date}",
			);
			

			$post_content_string .= file_get_contents($extracted_theme_folder."index.php");
			$post_content_string = preg_replace(array_keys($POST_ELEMENTS_ARRAY), array_values($POST_ELEMENTS_ARRAY), $post_content_string);

			// Cleanup from the rest PHP entries
			$post_content_string = preg_replace("/(<\?php)(.+?)(\?>)/ims", "", $post_content_string);
			// Create theme folder
			$post_template_folder = $theme_folder_path."/post";
			if (!file_exists($post_template_folder)){
				_mkdir_m($post_template_folder);
			}

			$main_post_template_content = "{items}{if('pages' ne '')}<div align='center'><br />{t(Pages)}: {pages}<br /></div>{/if}";
			// Save	main
			file_put_contents($post_template_folder."/main.stpl", $main_post_template_content);
			// Save	item
			file_put_contents($post_template_folder."/item.stpl", $post_content_string);

			// Process view template
			$VIEW_ELEMENTS_ARRAY = array(
				"/<\?php the_title\(\); \?>/ims"		 	=> "{title}",
				"/<\?php the_content\((.)+?\); \?>/ims"		=> "{text}",
				/* fill it if needed! */


			);
			$view_content_string .= file_get_contents($extracted_theme_folder."page.php");
			$view_content_string = preg_replace(array_keys($VIEW_ELEMENTS_ARRAY), array_values($VIEW_ELEMENTS_ARRAY), $view_content_string);

			// Cleanup from the rest PHP entries
			$view_content_string = preg_replace("/(<\?php)(.+?)(\?>)/ims", "", $view_content_string);
			// Save	view
			file_put_contents($post_template_folder."/view.stpl", $view_content_string);

			$this->DIR_OBJ->copy_dir($extracted_theme_folder, $theme_folder_path, "", "/\.php/");

//print_r($tmp_content_string);
//print_r($_FILES);

			if (file_exists($extracted_theme_folder)) {
				unlink($extracted_theme_folder);
			}
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=wp_converter",
			"error_message"	=> _e(),
		);
		return tpl()->parse($_GET["object"]."/wp_converter_form", $replace);
	}

	/**
	* Internal crawler
	*/
	function crawler() {
		$OBJ = main()->init_class("crawler", "classes/");
		return is_object($OBJ) ? $OBJ->go() : "";
	}

	/**
	* Form generator (jQuery)
	*/
	function form_generator() {

		$FORM_TEMPLATES_DIR = YF_PATH."templates/admin/dev/formgenerator_templates/";
 
		$db_tables = array();
		foreach ((array)db()->meta_tables() as $_name) {
			$dt_name = substr($_name, strlen(DB_PREFIX));
			if (substr($dt_name, 0, strlen("sys_")) == "sys_") {
				$dt_name = substr($dt_name, strlen("sys_"));
			}
			$db_tables[$dt_name] = $_name;
		}
		if ($_POST["style"]) {
			$tpls = array();
			$tpl_paths = array();

			$tpl_paths = $this->DIR_OBJ->scan_dir($FORM_TEMPLATES_DIR. $_POST["style"], true, "", "/\.(svn|git)/");
			foreach ((array)$tpl_paths as $_path) {
				$_tpl_name = substr(basename($_path), 0, -5);
	 			$tpls[$_tpl_name] = file_get_contents($_path);
			}
			echo common()->json_encode($tpls);
			exit;
		}

	
		if ($_POST["save"]) {
			
			// Get php functions
			foreach ((array)$_POST["php"] as $func_name => $func_code) {
				$functions .=  $func_code."\n";
			}

			$class_name = strtolower($_POST["module_name"]);

			$replace = array(
				"class_name"	=> $class_name,
				"functions"		=> $functions,
			);


			$body = tpl()->parse($_GET["object"]."/formgenerator_templates/php_class_main", $replace);

			$module_fpath = INCLUDE_PATH."modules/".$class_name.".class.php";
			if (!file_exists($module_fpath)) {
				file_put_contents($module_fpath, $body);
			}

			// Create folder
			$stpl_dir = INCLUDE_PATH."templates/user/".$class_name."/";
			if (!file_exists($stpl_dir)) {
				_mkdir_m($stpl_dir);
			}

			// Save templates
			foreach ((array)$_POST["stpl"] as $stpl_name => $stpl_code) {
				$stpl_fpath = $stpl_dir.$stpl_name.".stpl";
				if (!file_exists($stpl_fpath)) {
					file_put_contents($stpl_fpath, $stpl_code);
				}
			}
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}

		// Get form templates for constructing
		$form_tpls_array = $this->DIR_OBJ->scan_dir($FORM_TEMPLATES_DIR, false, "-d", "/\.(svn|git)/ims");
		foreach ((array)$form_tpls_array as $_dir => $_files) {
				$name = basename($_dir);
				$form_tpls[$name] = ucfirst(trim(str_replace("_", " ", $name)));
		}

		$replace = array(
			"style_box"		=> common()->select_box("style", $form_tpls, "", false),
			"download_url"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"get_style_url"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"db_table_box"	=> common()->select_box("db_table", $db_tables),
		);
		return tpl()->parse($_GET["object"]."/form_generator", $replace);

	}
	
	
	/**
	*
	*/
	function _unit_test_syntax_checker () {
	
	
		$result[] = array(
			"title"		=> "php_syntax (count errors)",
			"expect"	=> "0",
			"url"		=> "./?object=dev&action=syntax_checker",
			"result"	=> $this->syntax_checker(true),
		);
		
		$result[] = array(
			"title"		=> "html_syntax (count errors)",
			"expect"	=> "0",
			"url"		=> "./?object=dev&action=check_tag",
			"result"	=> $this->check_tag(true),
		);
		
		return $result;
	}
}