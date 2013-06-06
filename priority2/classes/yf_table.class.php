<?php

/**
* Table processor
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_table {

	// Name of the database table
	var $db_table = null;
	// Array of the database table fields
	var $db_fields = array();
	// Table unique ID
	var $id = null;
	// Table name
	var $name = null;
	// Automatic creation of the table by parsing given Database Table
	var $auto_parser = false;
	// Start query to process table structure
	var $sql = null;
	// Path to the page where table is shown
	var $path = null;
	// Available fileld types (other will be ignored)
	var $cell_types = array(
		"a" => "\$this->_p_active",
		"d" => "\$this->_p_date",
		"i" => "\$this->_p_image",
		"t" => "\$this->_p_text",
	);
	// Available cell properties (other will be ignored)
	var $cell_props = array(
		"class",	// string	// HTML "class" attribute
		"style",	// string	// HTML "style" attribute
		"sort",		// (1 or 0)	// HTML "style" attribute
		"text",		// string	// text to show in the link
		"translate",// (1 or 0)	// translate or not contents of the cell
	);
	// Array of buttons for each table row
	var $buttons = array(
		"edit"		=> "",
		"delete"	=> "",
		"add"		=> "",
	);
	// Table cells with their names and attributes
	var $_cells = array();
	// Table header
	var $header_text = null;
	// Display number of total records
	var $show_total_records = true;
	// Display or not cell names
	var $show_cell_names = true;
	// Display actions or not
	var $show_actions = true;
	// Display "ADD" button
	var $show_add_button = true;
	// Display actions on the left side
	var $show_actions_left = false;
	// Template to display form inside
	var $tpl = null;
	// Custom javascript code to use
	var $js_code = null;
	// Add group delete checkboxes to the table
	var $ALLOW_GROUP_DELETE = true;
	// Variable that containing error message if error exists
	var $_ERROR = null;

	/**
	* Constructor
	*/
	function yf_table($params = "") {
		// Table parameters string
		$params = explode("~", $params);
		// Process table attributes
		foreach ((array)$params as $v) {
			$v = strtolower(trim($v));
			$name = trim(substr($v, 0, strpos($v, "=")));
			if (strlen($name)) {
				$this->$name = trim(substr($v, strpos($v, "=") + 1));
			}
		}
		// Default query text (part of auto-parser)
		if (!$this->sql) {
			$this->sql = "SELECT * FROM `".$this->db_table."`";
		}
		// Default path (part of auto-parser)
		if (!$this->path) {
			$this->path = "./?object=".$_GET['object'];
		}
		// db_table is required for correct table processing
		if (!strlen($this->db_table)) {
			$this->_ERROR .= t("db_table_missed")."\r\n";
		}
		// If auto-parser is allowed - initialize it
		if ($this->auto_parser) {
			$this->auto();
		}
	}

	/**
	* Auto-parser of table structure
	*/
	function auto () {
		if ($this->_ERROR) {
			return $this->_show_error();
		}
		// Process all table fields
		$table_columns	= db()->meta_columns($this->db_table);
		// Catch empty columns and try to create such table
		if (empty($table_columns)) {
			db()->query("SELECT * FROM `".$this->db_table."`");
			if (substr($this->db_table, 0, 4) == "dbt_") {
				$this->db_table = DB_PREFIX. substr($this->db_table, 4);
			}
			$table_columns	= db()->meta_columns($this->db_table);
		}
		foreach ((array)$table_columns as $A) {
			$type	= "t"; // Default cell type
			$props	= ""; // Cell properties are empty by default
			$name	= $A["name"];
			// Try to determine special field types
			if (in_array($name, array("created", "date", "add_date"))) $type = "d";
			if (in_array($name, array("active"))) $type = "a";
			// Assign cels properties for the current item
			$this->_cells[$name] = array(
				"type" 	=> $type,
				"props"	=> $props,
				"name" 	=> $name,
			);
		}
		// If header text is empty - fill it automatically
		if (!$this->header_text && !$GLOBALS['_no_auto_header']) {
			// Cut database prefix and system prefix (if exists)
			$table_name = substr($this->db_table, strlen(DB_PREFIX));
			if (substr($table_name, 0, strlen("sys_")) == "sys_") $table_name = substr($table_name, strlen("sys_"));
			// Insert into header text translated table name
			$this->header_text = translate($table_name);
		}
	}

	//-----------------------------------------------------------------------------
	// Add cells to the table
	// Example:
	//	$cells = array(
	//				"login"		=> "i ~ of ~ required = 1 ^ class=nbb",
	//				"password"	=> "i ~ lala",
	//				)
	function add_cells ($cells = array()) {
		if ($this->_ERROR) {
			return $this->_show_error();
		}
		// Process fields
		foreach ((array)$cells as $name => $params) {
			$name = trim($name);
			// Process cell parameters
			list($type, $properties) = explode("~", $params);
			// Cell type flag
			$type = trim(strtolower($type));
			// Default cell type is "text"
			if (!strlen($type)) $type = "t";
			// Check if current cell type exists
			if (!isset($this->cell_types[$type])) {
				$this->_ERROR .= t("wrong_cell_type")." \"".$type."\"\r\n";
			}
			// Sting containing properties flags
			$props = $this->_parse_properties($properties);
			// If cell verification passed successfully - store it
			if (!$this->_ERROR) {
				// Insert cell parameters into class variable
				$this->_cells[$name] = array(
					"type" 	=> $type,
					"props"	=> $props,
					"name" 	=> $name,
				);
			}
		}
		return $body;
	}

	/**
	* Remove cells from the $cells array
	*/
	function remove_cells ($cells = array()) {
		if (!$this->_ERROR) {
			foreach ((array)$cells as $name) unset($this->_cells[$name]);
		} else return $this->_show_error();
	}

	/**
	* Dynamically add new element processing types
	*/
	function add_type($types = array()) {
		foreach ((array)$types as $name => $method) $this->cell_types[$name] = $method;
	}

	/**
	* Create new table
	*/
	function create() {
		if (!count($this->_cells) || !is_array($this->_cells)) {
			$this->_ERROR = t("no_cells_to_display")."\r\n";
		}
		if ($this->_ERROR) {
			return $this->_show_error();
		}
		// HEADER_TEXT
		if ($this->header_text) {
			$body .= "<h1>".$this->header_text."</h1>\r\n";
		}
		// Fill array of db table fields
		if ($this->db_table) {
			$this->_db_table_fields();
		}
		// Check if such field name exists in the database table
		if ($_GET['sort'] && in_array($_GET['sort'], $this->db_fields)) {
			// Set default sorting order
			if (!$_GET['order']) {
				$_GET['order'] = "a";
			}
			// Generate additional sorting query text
			list($sort_text, $new_order) = $this->_generate_sort();
		}
		// Calling function to divide records per pages
		list ($add_text, $pages, $total) = common()->divide_pages($this->sql, $this->path);
		// Process full query (with added sort text)
		$Q = db()->query($this->sql.$sort_text.$add_text);
		// If there is at least one record - show it
		if (db()->num_rows($Q)) {
			// TOTAL RECORDS
			if ($this->show_total_records) {
				$body .= "<div align=\"center\">".t('total')." ".$total." ".t("records")."<br><br></div>\r\n";
			}
			if ($this->ALLOW_GROUP_DELETE) {
				$body .= "<form action=\"./?object=".$_GET["object"]."&action=group_delete"._add_get(array("page"))."\" method=\"post\" name=\"my_cool_form\">\r\n";
			}
			$body .= "<table align='center'>\r\n";
			// CELL NAMES
			if ($this->show_cell_names) {
				$body .= "\t<thead>\r\n";
				if ($this->ALLOW_GROUP_DELETE) {
					$body .= "\t\t<th></th>\r\n";
				}
				// LEFT ACTIONS HEADER (if is set to show them on the left)
				if ($this->show_actions && $this->show_actions_left) {
					$body .= "\t\t<th>".t('action')."</th>\r\n";
				}
				// Process lcells
				foreach ((array)$this->_cells as $k => $v) {
					$body .= "\t\t<th>";
					// Text to display for cell
					$text = $v['props']['text'] ? $v['props']['text'] : $v['name'];
					// If cell is disabled for sorting - show only text
					if ($v['props']['sort'] != "0")	{
						$body .= $this->_order_image($v['name'])."<a href=\"".$this->path."&sort=".$v['name']
							. ($new_order ? "&order=".$new_order : "")
							. ($_GET['table'] ? "&table=".$_GET['table'] : "")
							."\"><b>".translate($text)."</b></a>";
					} else {
						$body .= translate($text);
					}
					$body .= "</th>\r\n";
				}
				// ACTIONS HEADER (default position - on the right side)
				if ($this->show_actions && !$this->show_actions_left) {
					$body .= "\t\t<th>".t('action')."</th>\r\n";
				}
				$body .= "\t</thead>\r\n";
			}
			$body .= "\t<tbody>\r\n";
			// Process records
			while ($Array = db()->fetch_assoc($Q)) {
				$body .= "\t<tr class=\"".(!(++$i % 2) ? "bg1" : "bg2")."\" id=\"del_row_".$Array["id"]."\">\r\n";
				if ($this->ALLOW_GROUP_DELETE) {
					$body .= "\t\t<td><input type=\"checkbox\" name=\"_group_".$Array["id"]."\" value=\"1\"></td>\r\n";
				}
				// LEFT ACTIONS HEADER
				if ($this->show_actions && $this->show_actions_left) $body .= $this->_show_action_buttons ($Array['id']);
				// Process cells
				foreach ((array)$this->_cells as $k => $v) {	
					$value = null;
					eval("\$value = ".$this->cell_types[$v['type']]."(\$Array[\$v[\"name\"]]);");
					$body .= "\t\t<td>".(strlen($value) ? ($v['props']['translate'] ? translate($value) : $value) : "&nbsp;")."</td>\r\n";
				}
				// ACTIONS HEADER (right - default)
				if ($this->show_actions && !$this->show_actions_left) $body .= $this->_show_action_buttons ($Array['id']);
				$body .= "\t</tr>\r\n";
			}
			$body .= "\t</tbody>\r\n";
			$body .= "</table>\r\n";
			if ($this->ALLOW_GROUP_DELETE) {
				if ($total) {
					$body .= "<br /><div align=\"left\"><label for=\"my_check_all\"><input type='checkbox' id=\"my_check_all\" name='check_all' onclick='my_toggle_boxes(this.form);'> ".t("SELECT ALL")." </label><input type='submit' value='".t("Delete selected")."'></div>\r\n";
				}
				$body .= "</form>\r\n";
			}
			// Show pages text
			$body .= $pages ? "<br><div align=\"center\">".$pages."</div>\r\n" : "";
		} else {
			$body .= "<div align=\"center\">".t("no_records")."</div>\r\n";
		}
		// Show add button or not
		if ($this->show_add_button) {
			$body .= "<br><div align=\"center\"><input type='button' class='button2' value='".t('add')."' onclick=\"window.location.href='".(strlen($this->buttons['add']) ? $this->buttons['add'] : "./?object=".$_GET['object']."&action=add"). _add_get()."'\"></div>\r\n";
		}
		return $body;
	}

	/**
	* Show buttons for each record
	*/
	function _show_action_buttons ($id) {
		$body .= "\t\t<td class='td'><nobr>\r\n";
		// Process available buttons
		foreach ((array)$this->buttons as $k => $href) {
			// Skip add button from standard processing
			if ($k == "add") continue;
			// Process standard "edit" and "delete" buttons
			if ($k == "edit" && !strlen($href)) {
				$href = "./?object=".$_GET['object']."&action=edit&id=".$id. _add_get();
			} elseif ($k == "delete" && !strlen($href)) {
				$href = "./?object=".$_GET['object']."&action=delete&id=".$id. _add_get();
			// Make some variables to be assigned with their real values
			} else {
				eval("\$href = \"".$href."\";");
			}
			$body .= "\t\t\t<input type=\"button\" class=\"".($k == "delete" ? "ajax_delete" : "button2")."\" value=\"".translate($k)."\""
/*				.(" onclick=\"".($k == "delete" ? "if (confirm('".t("are_you_sure")."?'))" : "")." window.location.href='".$href. _add_get()."'")*/
				.($k != "delete" ? " onclick=\"window.location.href='".$href. _add_get()."'\"" : "")
				.(" profy:href='".$href. _add_get()."'")
				."\">\r\n";
		}
		$body .= "\t\t</nobr></td>\r\n";
		return $body;
	}

	/**
	* Return image for show sorting order
	*/
	function _order_image ($cell = "") {
		if (!in_array($_GET['sort'], $this->db_fields) || $_GET['sort'] != $cell) {
			return "";
		}
		if ($_GET['order'] == "a") {
			$image_name = "&#x25b2;";
		} elseif ($_GET['order'] == "d") {
			$image_name = "&#x25bc;";
		}
		if ($image_name) {
			return "<span style='font-size:24px;line-height:12px;color:yellow;'>".$image_name."</span>";
		}
	}
	
	/**
	* Fill array of database table fields
	*/
	function _db_table_fields () {
		$meta_columns = db()->meta_columns($this->db_table);
		foreach ((array)$meta_columns as $A) $this->db_fields[] = $A["name"];
	}

	/**
	* Generate sorting additional query text
	*/
	function _generate_sort () {
		if ($_GET['sort']) {
			$sort_text = " ORDER BY `".$_GET['sort']."` ";
			if ($_GET['order'] == "a") {
				$sort_text .= " ASC ";
				$new_order = "d";
			} elseif ($_GET['order'] == "d") {
				$sort_text .= " DESC ";
				$new_order = "a";
			} else {
				$sort_text .= " ASC ";
				$new_order = "a";
			}
		}
		return array($sort_text, $new_order);
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
				if (in_array($item, $this->cell_props)) $Array[$item] = trim($value);
			}
		}
		return $Array;
	}

	/**
	* Clear all fields and table properties (useful to call this method when
	* using table processor several times for one page with many different tables)
	*/
	function clear() {
		$this->id = null;
		$this->name = null;
		$this->db_table = null;
		unset($this->fields);
	}

	/**
	* Show error message
	*/
	function _show_error () {
		if (!$error_shown) {
			$body .= "<h2>TABLE PROCESSOR ERROR:</h2>
					<span style='color:red'>".nl2br($this->_ERROR)."</span>\r\n";
			$this->error_shown = true;
		}
		return $body;
	}

	// ############## DEFAULT PROCESSING METHODS ##############
	/**
	* Processing "active" fields
	*/
	function _p_active ($input) {
		return "<b style=\"text-transform:uppercase;\">".translate($input ? "yes" : "no")."</b>";
	}

	/**
	* Processing date fields
	*/
	function _p_date ($input) {
		if (strlen($input) == 10 && is_numeric($input)) $output = _format_date((strlen($input) == 10 && is_numeric($input)) ? $input : strtotime($input));
		else $output = substr($input, 0, 10);
		return "<nobr>".$output."</nobr>";
	}

	/**
	* Processing image fields
	*/
	function _p_image ($input) {
		if (strlen($input)) {
			$file_name = conf('dir_small'). $input;
			if (file_exists(REAL_PATH.$file_name)) $output = "<img src=\"".WEB_PATH.$file_name."\" border=\"0\">";
			else $output = "<div align=\"center\">".t("no_image")."</div>";
		}
		return $output;
	}

	/**
	* Processing text fields
	*/
	function _p_text ($input) {
		$output = substr($input, 0, conf('length_trim') ? conf('length_trim') : 100);
		$output = wordwrap($output, 50, "\r\n", 1);
		return _prepare_html($output);
	}
}
