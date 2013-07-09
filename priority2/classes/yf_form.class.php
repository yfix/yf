<?php

/**
* Form processor
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_form {

	// Generate date/time fields as input with attached calendar or like select boxes
	// 1 - Generate input with attached calendar type 1
	// 2 - Generate input with attached calendar type 2
	// 3 - Generate select boxes for year, month, day (and if needed - hour, minute, second)
	public $date_mode = 1;
	// JavaScript-based field values confirmation before submitting the form
	public $js_confirm = false;
	// Automatic creation of the form by parsing given Database Table
	public $auto_parser = false;
	// Form unique ID
	public $id = null;
	// Form name variable
	public $name = null;
	// Submitting method (GET, POST)
	public $method = "post";
	// Target action
	public $action = null;
	// Enctype for the form
	public $enctype = 1; // 0 - "application/x-www-form-urlencoded", 1 - "multipart/form-data"
	// Name of the database table (used in auto-parser)
	public $db_table = null;
	// Database query SQL text (need to auto-parser)
	public $sql = null;
	// Form filelds with their names and attributes
	public $fields = array();
	// Available fileld types (other will be ignored)
	public $field_types = array(
		"a" 	=> "p_active",		// Input type = "radio" with translated "yes", "no"
		"c" 	=> "p_checkbox",	// Input type = "checkbox"
		"date" 	=> "p_date",		// Date field (YYYY-MM-DD)
		"dt" 	=> "p_datetime",	// DateTime field (YYYY-MM-DD HH:MM:SS)
		"f" 	=> "p_file",		// Input type = "file"
		"h" 	=> "p_hidden",		// Input type = "hidden"
		"i" 	=> "p_input", 		// Input type = "text", values - any symbols
		"img" 	=> "p_image",		// Input type = "submit" (abbr from "button")
		"mul" 	=> "p_multi_select",// Multi-select box
		"photo" => "p_photo",		// Container with image within and delete button
		"psw" 	=> "p_password",	// Input type = "password", values - any symbols
		"r" 	=> "p_radio",		// Input type = "radio"
		"res" 	=> "p_reset",		// Input type = "reset" (abbr from "erase")
		"s" 	=> "p_select_box",	// Select box
		"sub" 	=> "p_submit",		// Input type = "image" (abbr from "go")
		"t" 	=> "p_textarea",	// Textarea, values - any symbols
		"time" 	=> "p_time",		// Time field (HH:MM:SS)
		"ts" 	=> "p_timestamp",	// Timestamp field (10 digits int)
	);
	// Array of fields where colspan=2 is needed without showing name and input
	public $colspan_fields = array (
		"img", 
		"photo",
		"res", 
		"sub",
	);
	// Variable where header text strored
	public $header_text = null;
	// Back button text
	public $back_button_url = null;
	// Template to display form inside
	public $tpl = null;
	// Custom javascript code to use (otherwise default will be used)
	public $js_code = null;
	// Calendar created (to prevent multiple initialization)
	public $_cal_created = false;
	// Try to find values from other tables
	public $_AUTO_FIND_TABLES = true;
	// Variable that containing error message if error exists
	public $_ERROR = null;

	/**
	* Constructor
	* 
	* @access	public
	* @param	string $params List of params like: "id=myid ~ name=myname ~ action=myaction"
	* @return	void
	*/
	function yf_form($params = "") {
		// Form parameters string
		$params = explode("~", $params);
		// Process form attributes
		foreach ((array)$params as $v) {
			$v = strtolower(trim($v));
			$name = trim(substr($v, 0, strpos($v, "=")));
			if (strlen($name)) $this->$name = trim(substr($v, strpos($v, "=") + 1));
		}
		// Check required form attributes
		if (!strlen($this->action)) $this->_ERROR .= t("form_action_missing")."\n";
		// If auto-parser is allowed - initialize it
		if ($this->auto_parser) $this->auto();
	}

	/**
	* Auto-parser of table structure
	*/
	function auto () {
		// For auto-parser db_table is required
		if (!strlen($this->db_table)) $this->_ERROR .= t("db_table_missed")."\n";
		// Fro now only mysql RDBMS is done for auto-parsing fields
		if (false === strpos(DB_TYPE, "mysql")) $this->_ERROR .= t("now_only_mysql_is_auto_parsed")."\n";
		// If there are no errors - continue
		if ($this->_ERROR) {
			return $this->_show_error();
		}
		// If SQL query or $_GET['id'] exists - try to grab values for the record
		if ($_GET['id'] && !$this->sql) {
			$this->sql = "SELECT * FROM ".$this->db_table." WHERE id=".$_GET['id'];
			$A = db()->fetch_assoc(db()->query($this->sql));
		}
		// Get all database tables
		$tq = db()->query("SHOW TABLES");
		while ($ta = db()->fetch_row($tq)) {
			$table_name = substr($ta['0'], strlen(DB_PREFIX));
			if (substr($table_name, 0, strlen("sys_")) == "sys_") {
				$table_name = substr($table_name, strlen("sys_"));
			}
			$tables[$table_name] = $ta['0'];
		}
		$_short_table_name = substr($this->db_table, strlen(DB_PREFIX));
		if (substr($_short_table_name, 0, strlen("sys_")) == "sys_") {
			$_short_table_name = substr($_short_table_name, strlen("sys_"));
		}
		// Process all table fields
		$dq = db()->query("DESCRIBE ".$this->db_table."");
		while ($da = db()->fetch_assoc($dq)) {
			// Field name
			$name = $da['Field'];
			// Skip "id" field from editing
			if ($name == "id") continue;
			// Try to determine field type
			$da['Type'] = str_replace("unsigned", "", $da['Type']);
			if (false !== strpos($da['Type'], "(")) {
				list($T, $length) = explode("(", $da['Type']);
				$length = substr($length, 0, strpos($length, ")"));
			} else {
				$T = $da['Type'];
				$length = false;
			}
			$props = $type = null;
			// Process different field types
			if (false !== strpos($T, "int")) {
				$tmp_name = $name;
				$field_table = false;
				// Try to determine "created" timestamp field
				if (in_array($tmp_name, array("created"))) {
					$type = "ts";
				} elseif (in_array($tmp_name, array("add_date"))) {
					$type = "date";
				} else {
					$auto_found_table	= "";
					if ($this->_AUTO_FIND_TABLES && !in_array($_short_table_name, array("user","admin"))) {
						$auto_names_array	= array();
						$auto_values		= array();
/*
						// Cut "_id" at the end of the name
						if (substr($tmp_name, -3) == "_id") $tmp_name = substr($tmp_name, 0, -3);
*/
						$auto_names_array = array(
							MAIN_TYPE. "_". $tmp_name,
							MAIN_TYPE. "_". $tmp_name. "s",
							$tmp_name,
							$tmp_name. "s",
						);
						// Try to find key table name with column "name"
						foreach ((array)$auto_names_array as $auto_name) {
//							if (array_key_exists($auto_name, $tables)) {
							if (isset($tables[$auto_name])) {
								$auto_found_table = $this->_find_table($tables[$auto_name]);
							}
							if (!empty($auto_found_table)) break;
						}
					}
					// If parser has found database table - parse it contents
					if (!empty($auto_found_table)) {
						$type = "s";
						$Q1 = db()->query("SELECT * FROM ".$auto_found_table."");
						while ($A1 = db()->fetch_assoc($Q1))	$auto_values[$A1['id']] = translate($A1['name']);
						$props['selected'] = $A[$name];
						$A[$name] = serialize($auto_values);
					} else {
						$type = "i";
						$props = array("size" => 5);
					}
				}
			} elseif ($T == "enum" || $T == "set") {
				if (in_array(str_replace(" ","",$length), array("'0','1'","'1','0'"))) {
					$type = "a"; // Try to determine "active" field
					$props['checked'] = $A[$name];
				} else {
					$type = "s";
					$props['selected'] = $A[$name];
					$tmp_array = array();
					foreach (explode(",", str_replace("'", "", $length)) as $v) $tmp_array[$v] = $v;
					$A[$name] = serialize($tmp_array);
					$tmp_array = null;
				}
			} elseif (false !== strpos($T, "text")) {
				$type = "t";
				$props = $this->_parse_properties("cols=40 ^ rows=8");
			} elseif (false !== strpos($T, "blob")) continue;
			elseif ($T == "date") $type = "date";
			elseif ($T == "time") $type = "time";
			elseif ($T == "datetime") $type = "dt";
			else {
				// Specially for the field type "password"
				if ($name == "password" && $length >= "32") {
					$type = "psw";
					$A[$name] = "";
				} elseif (false !== (strpos($A[$name], ".jpg") || strpos($A[$name], ".gif") )) {
					$type = "photo"; // Some cool stuff :-)
				} else $type = "i";
				// Set field size
				$props['size'] = ($length && $length <= 40) ? $length : 40;
			}
			// Fill array of parsed field properties
			$this->fields[$name] = array(
				"type" 	=> $type,
				"value" => $A[$name],
				"props"	=> $props,
				"name" 	=> $name,
			);
/*
			// Pregenerate "add_str"
			$this->fields[$name]['add_str'] = $this->_generate_add_string($fields[$name]['props']);
*/
		}
		// Insert submit button
		$name = "submit";
		$this->fields[$name] = array("type"=>"sub", "value"=>t("save"), "props"=>array(), "name"=>$name);
		// If header text is empty - fill it automatically
		if (!$this->header_text && !$GLOBALS['_no_auto_header']) {
			// Cut database prefix and system prefix (if exists)
			$table_name = substr($this->db_table, strlen(DB_PREFIX));
			if (substr($table_name, 0, strlen("sys_")) == "sys_") {
				$table_name = substr($table_name, strlen("sys_"));
			}
			// Insert into header text translated table name
			$this->header_text = ($_GET['id'] ? t("edit") : t("add"))." ".translate($table_name)." ".($_GET['id'] ? "#".$_GET['id'] : "");
		}
		// Try to create proper back button url
		if (!$this->back_button_url) {
			$this->back_button_url = "./?object=".$_GET['object'].($_GET['page'] ? "&page=".$_GET['page'] : ""). ($_GET['sort'] ? "&sort=".$_GET['sort'] : ""). ($_GET['order'] ? "&order=".$_GET['order'] : ""). ($_GET['table'] ? "&table=".$_GET['table'] : "");
		}
		return $body;
	}

	/**
	* Add fields to the form
	* 
	* @access	private
	* @code
	*		$fields = array(
	*			"login"		=> "i ~ of ~ required = 1 ^ class=nbb",  // type,[value(s)],['properties']
	*			"password"	=> "i ~ lala",
	*		)
	* @endcode
	* @param
	* @return
	*/
	function add_fields($fields = array()) {
		if (!$this->_ERROR) {
			// Process fields
			foreach ((array)$fields as $name => $params) {
				$name = trim($name);
				// Process field parameters
				list($type, $value, $properties) = explode("~", $params);
				// Field type flag
				$type = trim(strtolower($type));
				// Default field type is "input"
				if (!strlen($type)) $type = "i";
				// Check if current field type exists
//				if (!array_key_exists($type, $this->field_types))
				if (!isset($this->field_types[$type]))
					$this->_ERROR .= t("wrong_field_type")."\n";
				// Field value (could be serialized array for select boxes)
				$value = trim($value);
				// Sting containing properties flags
				$props = $this->_parse_properties($properties);
				// If field verifying passed successfully - store it
				if (!$this->_ERROR) {
					// Insert field parameters into class variable
					$this->fields[$name] = array(
						"type"		=> $type,
						"value"		=> $value,
						"props"		=> $props,
						"name"		=> $name,
						"add_str"	=> "",
					);
				}
			}
		} else $body .= $this->_show_error();
	}

	/**
	* Remove fields from the $fields array
	*/
	function remove_fields($fields = array()) {
		if (!$this->_ERROR) {
			foreach ((array)$fields as $name) unset($this->_fields[$name]);
		} else return $this->_show_error();
	}

	/**
	* Return array of properties and their values
	*/
	function _parse_properties($properties) {
		$Array = array();
		if (strlen($properties)) {
			$tmp = explode("^", trim($properties));
			foreach ((array)$tmp as $k => $v) {
				list($item, $value) = explode("=", trim($v));
				$item = trim(strtolower($item));
				$Array[$item] = trim($value);
			}
		}
		return $Array;
	}

	/**
	* Dynamically add new element processing types
	*/
	function add_type($types = array()) {
		foreach ((array)$types as $name => $method) $this->field_types[$name] = $method;
	}

	/**
	* Set Custom JavaScript code
	*/
	function set_js($js_text = "") {
		if (strlen($js_text)) $this->js_code = $js_text;
		else $this->_ERROR .= t("empty_JS_code")."\n";
	}

	/**
	* Set Custom Temlate
	*/
	function set_tpl($tpl_name = "") {
		if (strlen($tpl_name)) $this->tpl = $tpl_name;
		else $this->_ERROR .= t("empty_Template_name")."\n";
	}

	/**
	* Create form contents
	*/
	function create() {
		if (!count($this->fields) || !is_array($this->fields)) $this->_ERROR .= t("no_fields_to_display")."\n";
		if (!$this->_ERROR) {
			// Array of available enctypes
			$enc_types = array(
				"application/x-www-form-urlencoded",
				"multipart/form-data",
			);
			// If custom template is not set - generate default layout
			if (!$this->tpl) {
				$replace = array();
				// Insert Javascript-based confirmation if needed
				if ($this->js_confirm) $items .= $this->_js_confirm();
				foreach ((array)$this->fields as $name => $v) {
					$f_name = $this->field_types[$v['type']];
					if (!$this->tpl) {
						$bg_class = !(++$i % 2) ? "bg1" : "bg2";
						eval("\$value = ".(0 === strpos($f_name, "p_") ? "\$this->_" : "").$f_name."(\$v);");
						$items .= in_array($v['type'], $this->colspan_fields) ?
							tpl()->parse("system/form_item2", array("item_value" => $value, "bg_class" => $bg_class)) :
							tpl()->parse("system/form_item", array("item_name" => translate($v['name']), "item_value" => $value, "bg_class" => $bg_class));
					} else eval("\$replace[".$name."] = ".(0 === strpos($f_name, "p_") ? "\$this->_" : "").$f_name."(\$v);");
				}
				$replace2 = array(
					"form_header"	=> $this->header_text,
					"form_action"	=> $this->action,
					"form_id"		=> $this->id,
					"form_name"		=> $this->name,
					"form_method"	=> $this->method,
					"form_enctype"	=> $enc_types[$this->enctype],
					"form_items"	=> $items,
					"form_footer"	=> strlen($this->back_button_url) ? back($this->back_button_url. _add_get()) : "",
				);
				$body .= tpl()->parse($this->tpl ? $this->tpl : "system/form_main", array_merge($replace, $replace2));
			// Process custom template
			} else {
				$replace = array();
				// Insert Javascript-based confirmation if needed
				if ($this->js_confirm) $items .= $this->_js_confirm();
				foreach ((array)$this->fields as $name => $v) {
					$f_name = $this->field_types[$v['type']];
					eval("\$replace[".$name."] = ".(0 === strpos($f_name, "p_") ? "\$this->_" : "").$f_name."(\$v);");
				}
				$body = tpl()->parse($this->tpl, $replace);
			}
		} else $body .= $this->_show_error();
		return $body;
	}

	/**
	* Generate additional string with attributes for the element
	*/
	function _generate_add_string($props = array()) {
		// Generate string with additional properties or attributes
		foreach ((array)$props as $k => $v) {
			if ($k == "disabled" || $k == "readonly" || $k == "checked" || $k == "selected" || $k == "required") $add_str .= " ".$k;
			else $add_str .= " ".$k."=\"".$v."\"";
		}
		return $add_str;
	}

	/**
	* Show error message
	*/
	function _show_error () {
		if (!$error_shown) {
			$body .= "<h2>".t("FORM PROCESSOR ERROR").":</h2>
					<span style='color:red'>".nl2br($this->_ERROR)."</span>\n";
			$this->error_shown = true;
		}
		return $body;
	}

	/**
	* Clear all fields and form properties (when using form processor 
	* several times for one page with many different forms)
	*/
	function clear() {
		$this->date_mode = 1;
		$this->js_confirm = true;
		$this->auto_parser = false;
		$this->id = null;
		$this->name = null;
		$this->method = "post";
		$this->action = null;
		$this->enctype = 1;
		$this->db_table = null;
		$this->sql = null;
		unset($this->fields);
		$this->_cal_created = false;
		$this->header_text = null;
		$this->back_button_url = null;
		$this->_ERROR = null;
		$this->tpl = null;
		$this->js_code = null;
	}

	/**
	* This function generate Javascript Code for form confirmation
	*/
	function _js_confirm () {
		if (!strlen($this->js_code)) {
			foreach ((array)$this->fields as $v) $tasks1.= "'".$v['name']."', ";
			foreach ((array)$this->fields as $v) $tasks2.= "'".translate($v['name'])."', ";	
			$replace = array(
				"form_tasks1" => substr($tasks1, 0, -2),
				"form_tasks2" => substr($tasks2, 0, -2),
			);
			$body .= tpl()->parse("system/form_js", $replace);
		} else $body = $this->js_code;
		return $body;
	}

	/**
	*  Find Table
	*/
	function _find_table ($tbl_name = "") {
		if (db()->query_num_rows("SHOW COLUMNS FROM ".$tbl_name." LIKE 'name'"))
			return $tbl_name;
		else return false;
	}

	// ########### DEFAULT PROCESSING METHODS ################
	/**
	* Process "active" field
	*/
	function _p_active ($field = array()) {
		$body .= '<label type="radio"><input type="radio" name="'.$field['name'].'" value="0" id="radio_'.$field['name'].'_0"'.$field['add_str'].' '.(!$field['value'] ? 'checked' : '').'>
					<span class="label label-warning">'.t('INACTIVE').'</b></label>';
		$body .= '<label type="radio"><input type="radio" name="'.$field['name'].'" value="1" id="radio_'.$field['name'].'_1"'.$field['add_str'].' '.($field['value'] ? 'checked' : '').'>
					<span class="label label-success">'.t('INACTIVE').'</b></label>';
		return $body;
	}

	/**
	* Process checkbox field
	*/
	function _p_checkbox ($field = array()) {
		return "<input type=\"checkbox\" name=\"".$field['name']."\" value=\"".$field['value']."\"".$field['add_str'].">";
	}

	/**
	* Process date field
	*/
	function _p_date ($field = array()) {
		// Assign random id for the field it is empty
		if (!$field['props']['id']) $field['props']['id'] = common()->rand_name();
		// Pregenerate "add_str"
		$field['add_str'] = $this->_generate_add_string($field['props']);
/*
		// Try to init calendar module
		if (in_array($this->date_mode, array(1,2))) {
			$M = main()->init_class("minicalendar", "classes/");
		}
		// Calendar type 1
		if ($this->date_mode == 1 && is_object($M)) {
			// Prevent multiple initialization
			if (!$this->_cal_created) {
				$body .= $M->createcalendar();
				$this->_cal_created = true;
			}
			$body .= "<input type=\"text\" name=\"".$field['name']."\" value=\"".$field['value']."\" ".$field['add_str']."> ";
			$body .= $M->_show_image($field['props']['id']);
		// Calendar type 2
		} elseif ($this->date_mode == 2 && is_object($M)) {
			// Prevent multiple initialization
			if (!$this->_cal_created) {
				$body .= $M->createcalendar2();
				$this->_cal_created = true;
			}
			$body .= "<input type=\"text\" name=\"".$field['name']."\" value=\"".$field['value']."\" ".$field['add_str']."> ";
			$body .= $M->_show_image2($field['props']['id']);
		// Date box (3 select boxes)
		} else 
*/
			$body .= "<input type=\"text\" name=\"".$field['name']."\" value=\"".$field['value']."\" ".$field['add_str']."> ";
//		$body .= common()->date_box($field['value'], "1950-".(date("Y") + 1), "", $field['add_str']);
		return $body;
	}

	/**
	* Process date field
	*/
	function _p_datetime ($field = array()) {
		// Assign random id for the field it is empty
		if (!$field['props']['id']) $field['props']['id'] = common()->rand_name();
		// Pregenerate "add_str"
		$field['add_str'] = $this->_generate_add_string($field['props']);
		// Try to init calendar module
		if (in_array($this->date_mode, array(2))) {
			$M = main()->init_class("minicalendar", "classes/");
		}
		// Only for calendar mode 2
		if ($this->date_mode == 2 && is_object($M)) {
			// Prevent multiple initialization
			if (!$this->_cal_created) {
				$body .= $M->createcalendar2();
				$this->_cal_created = true;
			}
			$body .= "<input type=\"text\" name=\"".$field['name']."\" value=\"".$field['value']."\" ".$field['add_str']."> ";
			$body .= $M->_show_image2($field['props']['id'], true);
		} else $body .= common()->date_box(substr($field['value'], 0, 10), "1950-".(date("Y") + 1), "", $field['add_str'])
						.common()->time_box(substr($field['value'], 11), "", $field['add_str']);
		return $body;
	}

	/**
	* Process file upload field
	*/
	function _p_file ($field = array()) {
		return "<input type=\"file\" name=\"".$field['name']."\" value=\"".$field['value']."\"".$field['add_str'].">";
	}

	/**
	* Process hidden field
	*/
	function _p_hidden ($field = array()) {
		return "<input type=\"hidden\" name=\"".$field['name']."\" value=\""._prepare_html($field['value'])."\"".$field['add_str'].">";
	}

	/**
	* Process image button
	*/
	function _p_image ($field = array()) {
		return "<input type=\"image\" name=\"".$field['name']."\" src=\"".$field['value']."\"".$field['add_str'].">";
	}

	/**
	* Process input field
	*/
	function _p_input ($field = array()) {
		return "<input type=\"text\" name=\"".$field['name']."\" value=\""._prepare_html($field['value'])."\"".$field['add_str'].">";
	}

	/**
	* Process multi-select field
	*/
	function _p_multi_select ($field = array()) {
		return $this->_multi_select($field['name'], unserialize($field['value']), unserialize($field['props']['selected']), $field['props']['head'], 2, $field['add_str']);
	}

	/**
	* Process password field
	*/
	function _p_password ($field = array()) {
		return "<input type=\"password\" name=\"".$field['name']."\" value=\"".$field['value']."\"".$field['add_str'].">";
	}

	/**
	* Process "photo" field
	*/
	function _p_photo ($field = array()) {
		if (conf('dir_small')) {
			$file_path = REAL_PATH. conf('dir_small'). $field['value'];
			if (file_exists($file_path)) {
				$body .= "<img src=\"".$file_path."\" border=0>\n";
				$body .= "<br><input type=\"button\" value=\"".t("delete")."\" onclick=\"if (confirm('".t("are_you_sure")."?')) window.location.href='./?object=".$_GET['object']."&action=delete_photo&id=".$_GET['id']."'\">";
			} else $body .= t("no_photo");
		} else $body .= $this->_p_input();
		return $body;
	}

	/**
	* Processing radio button
	*/
	function _p_radio ($field = array()) {
		// Process all radio selectors
		$values = unserialize($field['value']);
		foreach ((array)$values as $k => $v) {
			$body .= "<input type=\"radio\" name=\"".$field['name']."\" class=\"check\" value=\"".$k."\"".$field['add_str']." ".(($k == $field['props']['selected']) ? "checked" : "").">\n";
			$body .= _prepare_html(translate($v))."\n";
		}
		return $body;
	}

	/**
	* Process reset button
	*/
	function _p_reset ($field = array()) {
		return "<input type=\"reset\" name=\"".$field['name']."\" value=\"".$field['value']."\"".$field['add_str']." class=\"btn\">";
	}

	/**
	* Processing select box
	*/
	function _p_select_box ($field = array()) {
		return common()->select_box($field['name'], unserialize($field['value']), $field['props']['selected'], $field['props']['head'], 2/*, $field['add_str']*/);
	}

	/**
	* Process submit button
	*/
	function _p_submit ($field = array()) {
		if ($this->js_confirm) $body .= "<input type=\"button\" value=\"".$field['value']."\"".$field['add_str']." onClick=\"form_check(this.form)\" class=\"btn\">";
		else $body .= "<input type=\"submit\" name=\"".$field['name']."\" value=\"".$field['value']."\"".$field['add_str']." class=\"btn\">";
		return $body;
	}

	/**
	* Processing textarea field
	*/
	function _p_textarea ($field = array()) {
		$field['add_str'] .= " cols=\"".$field["props"]["cols"]."\" rows=\"".$field["props"]["rows"]."\"";
		return "<textarea name=\"".$field['name']."\" ".$field['add_str'].">"._prepare_html($field['value'])."</textarea>";
	}

	/**
	* Process time field
	*/
	function _p_time ($field = array()) {
		return common()->time_box($field['value'], "", $field['add_str']);
	}

	/**
	* Process timestamp field
	*/
	function _p_timestamp ($field = array()) {
		return _format_date($field['value'], "long");
	}
}
