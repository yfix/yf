<?php

/**
* ProEngine Settings handling class
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_settings {

	/** @var array @conf_skip types of settings items */
	var	$types = array(
		"text" => "text",
		"enum" => "enum",
		"char" => "char",
		"date" => "date",
	);
	/** @var array @conf_skip categories of setting (for groupping) */
	var	$categories = array();
	/** @var bool @conf_skip Constructor mode */
	var $CONSTRUCTOR_MODE = false;

	/**
	* Constructor
	*/
	function yf_settings () {
		// Check if need to turn on additional constructor mode possibilities
		if (conf('constructor_mode') && $_SESSION['admin_group'] == 1) {
			$this->CONSTRUCTOR_MODE = true;
		}
		// For constructor mode enabling some useful things
		if ($this->CONSTRUCTOR_MODE) {
			// Select all settings items even if they have no assigned category
			$sql = "SELECT * FROM `".db('settings_category')."` ORDER BY `order` ASC";
		} else {
			$sql = "SELECT * FROM `".db('settings_category')."` WHERE `order` > 0 ORDER BY `order` ASC";
		}
		// Insert settings categories into array
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$this->categories[$A['id']] = $A['name'];
		}
	}

	/**
	* Settings editing form
	*/
	function show() {
		// For correct processing missed category
		if ($_POST['category']) {
			$_GET['category'] = $_POST['category'];
		}
		if (!$_GET['category']) {
			$_GET['category'] = 1;
		}
		// Creating new array for showing puproses
		$categories2 = $this->categories;
		// Groupping settings by category
		if ($this->CONSTRUCTOR_MODE) {
			foreach ((array)$categories2 as $k2 => $v2) {
				$Q = db()->query("SELECT * FROM ".db('settings')." WHERE `category`=".$k2." AND `item` != 'constructor_mode' ORDER BY `order` ASC");
				// Category name
				$items .= tpl()->parse("settings/cat_name", array("name" => t($v2)));
   				while ($A = db()->fetch_array($Q)) 
					$items .= $this->_show_record($A);
			}
			// Try to find items with wrong or missed categories
			$Q = db()->query("SELECT DISTINCT(`category`) AS `cat` FROM `".db('settings')."` WHERE `item`!='constructor_mode' ORDER BY `category` ASC");
			while ($A = db()->fetch_array($Q)) {
				$sql = "SELECT * FROM `".db('settings_category')."` WHERE `id`=".$A['cat'];
				if (!db()->query_num_rows($sql)) $missed[] = $A[cat];
			}
			// If there are lost categories - show them
			if (count($missed)) {
				$Q = db()->query("SELECT * FROM `".db('settings')."` WHERE `category` IN (".implode(",",$missed).") AND `item`!='constructor_mode' ORDER BY `order` ASC");
				// If there are at least one lost item - show title
				if (db()->num_rows($Q)) {
					// Category name
					$items .= tpl()->parse("settings/cat_name", array("name" => t("other_items")));
   					while ($A = db()->fetch_array($Q))
						$items .= $this->_show_record($A);
				}
			}
		} else {
			$Q = db()->query("SELECT * FROM ".db('settings')." WHERE `category`=".intval($_GET['category'])." AND `order` > 0 AND `item`!='constructor_mode' ORDER BY `order` ASC");
			while ($A = db()->fetch_array($Q)) {
				// Hide constructor setting items if this mode is turned off
//				if (!$this->CONSTRUCTOR_MODE) continue;
				$items .= $this->_show_record($A);
			}
		}
		// Process template
		$replace = array(
			"form_action"			=> "./?object=".$_GET["object"]."&action=update&category=".$_GET['category'],
			"constructor_header"	=> $this->CONSTRUCTOR_MODE ? tpl()->parse("settings/constructor_header") : "",
			"items"					=> $items,
			"box_category"			=> !$this->CONSTRUCTOR_MODE ? tpl()->parse("settings/box_category", array("select_box" => common()->select_box("category", $this->categories, $_GET['category'], false, 2, "onchange='this.form.submit()'"), "form_action" => "./?object=".$_GET["object"])) : "",
			"box_constructor_mode"	=> $_SESSION["admin_group"] == 1 ? tpl()->parse("settings/box_constructor_mode", array("select_box" => common()->select_box("constructor_mode", array(t('no'), t('yes')), $this->CONSTRUCTOR_MODE, false, 2))) : "",
			"add_form"				=> $this->CONSTRUCTOR_MODE ? $this->_add_form() : "",
		);
		return tpl()->parse("settings/main", $replace);
	}

	/**
	* Show form to add new record
	*/
	function _add_form () {
		// Total number of settings variables (not only in the specified category)
		$num_rows = db()->query_num_rows("SELECT * FROM `".db('settings'));
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=add",
			"type"			=> common()->select_box("type", $this->types, "", false, 2),
			"order"			=> common()->select_box("order", range(0, $num_rows), $num_rows, false, 1),
			"category"		=> common()->select_box("category", $this->categories, "", false, 1),
			"cat_link"		=> "./?object=".$_GET["object"]."&action=show_categories",
		);
		return tpl()->parse("settings/add_form", $replace);
	}

	/**
	* Show record with formatting
	*/
	function _show_record ($A = array()) {
		// Count number of records
		static $i;
		// Stripping slashes from all array elements
		foreach ((array)$A as $k => $v) $A[$k] = stripslashes($v);
		// For column type "text" (show textarea)
		if ($A['type'] == "text") {
			// Trying to grab textarea size (number of columns and rows)
			if (strlen($A['size'])) {
				list($cols, $rows) = explode(",", $A[size]);
				$Size = "cols='".$cols."' rows='".$rows."'";
			} else $Size = "";
			$item_value = "<textarea name='".$A['item']."' ".$Size.">".htmlspecialchars($A['value'])."</textarea>\r\n";
		// For column type "enum" (show select box)
		} elseif ($A['type'] == "enum") {
			// Show theme field (non-standard field)
			if ($A['item'] == "theme") {
				$tpl_dir = dirname(INCLUDE_PATH. tpl()->TPL_PATH)."/";
				if ($h = opendir($tpl_dir)) {
					while (false !== ($fp = readdir($h))) { 
						if (in_array($fp, array(".","..","index.htm","index.htm","index.php"))) continue;
						if (!is_dir($tpl_dir.$fp)) continue;
						if (false !== strpos($fp, ".svn")) continue;
						if ($A['value'] == $fp) $selected = $A['value'];
						$values[$fp] = $fp;
					}
					closedir($h); 
				}
			// Show language field (non-standard field)
			} elseif ($A['item'] == "language") {
				return "";
			// Show select box with parsed "size" column as array of attributes
			} else {
				$attr = explode(',', $A['size']);
				foreach ((array)$attr as $k => $v) {
					$v = trim(str_replace("'", "", $v));
					if ($v == 0 && count($attr) == 2) $text = "no";
					elseif ($v == 1 && count($attr) == 2) $text = "yes";
					else $text = $v;
					if ($A['value'] == $v) $selected = $A['value'];
					$values[$v] = t($text);
				}
			}
			// Show select box with values
			$item_value = common()->select_box($A['item'], $values, $selected, false, 2, "", 0);
		// For column type "char" (show text box)
		} elseif ($A['type'] == "char") {
			$item_value = "<input type='text' value=\"".htmlspecialchars($A['value'])."\" name='".$A['item']."' ".(strlen($A['size']) ? "size='".$A['size']."'" : "").">\r\n";
		}
		// Process template
		$replace = array(
			"id"			=> ++$i,
			"item"			=> t($A['item']),
			"value"			=> $item_value,
			"type"			=> $A['type'],
			"size"			=> $A['size'],
			"order"			=> $A['order'],
			"category"		=> $this->categories[$A['category']],
			"edit_link"		=> "./?object=".$_GET["object"]."&action=edit&id=".$A['id'],
			"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$A['id'],
//			"bg_class"		=> !($i % 2) ? "bg1" : "bg2",
		);
		return tpl()->parse("settings/record_".($this->CONSTRUCTOR_MODE ? "constructor" : "main"), $replace);
	}

	/**
	* Edit setting variable
	*/
	function edit() {
		$_GET['id'] = intval($_GET['id']);
		// Checking admin rights and constructor mode
		if ($_GET['id'] && $this->CONSTRUCTOR_MODE) {
			$num_rows = db()->query_num_rows("SELECT `id` FROM `".db('settings')."`");
			$A = db()->query_fetch("SELECT * FROM `".db('settings')."` WHERE `id`=".$_GET['id']);
			// Stripping slashes from all fields
			foreach ((array)$A as $k => $v) $A[$k] = stripslashes($v);
			$replace = array(
				"header_text"	=> t("edit")." \"".$A['item']."\"",
				"form_action"	=> "./?object=".$_GET["object"]."&action=save&id=".$_GET['id'],
				"item"			=> $A['item'],
				"value"			=> htmlspecialchars($A['value']),
				"type"			=> common()->select_box("type", $this->types, $A['type'], false, 2),
				"size"			=> $A['size'],
				"order"			=> common()->select_box("order", range(0, $num_rows - 1), $A['order'], false, 1),
				"category"		=> common()->select_box("category", $this->categories, $A['category'], false, 2),
				"back"			=> back("./?object=".$_GET["object"]),
			);
			return tpl()->parse("settings/edit_var", $replace);
		}
	}

	/**
	* Update settings
	*/
	function update() {
		$Q = db()->query("SELECT * FROM `".db('settings')."`");
		while ($A = db()->fetch_assoc($Q)) {
			$item = $A['item'];
			if (!isset($_POST[$item])) continue;
			$_POST[$item] = str_replace(array('"', "'"), array('\"', "\'"), html_entity_decode(stripslashes($_POST[$item])));
			$sql = "UPDATE `".db('settings')."` SET 
					`value`='"._es($_POST[$item])."' 
				WHERE `id`=".$A['id'];
			db()->query($sql);
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("settings");
		return js_redirect("./?object=".$_GET["object"]."&category=".$_GET['category']);
	}

	/**
	* Add setting variable
	*/
	function add() {
		// Checking admin rights and constructor mode
		if ($this->CONSTRUCTOR_MODE) {
			$sql = "INSERT INTO `".db('settings')."` (
					`item`,
					`value`,
					`type`,
					`size`,
					`order`,
					`category`
				) VALUES (
					'"._es($_POST['item'])."',
					'"._es($_POST['value'])."',
					'"._es($_POST['type'])."',
					'"._es($_POST['size'])."',
					".intval($_POST['order']).",
					".intval($_POST['category'])."
				)";
			db()->query($sql);
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("settings");
		return js_redirect("./?object=".$_GET["object"]."&category=".$_GET['category']);
	}

	/**
	* Delete setting variable
	*/
	function delete() {
		$_GET['id'] = intval($_GET['id']);
		// Checking admin rights and constructor mode
		if ($_GET['id'] && $this->CONSTRUCTOR_MODE) {
			db()->query("DELETE FROM `".db('settings')."` WHERE `id`=".$_GET['id']);
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("settings");
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	* Save setting variable
	*/
	function save() {
		$_GET['id'] = intval($_GET['id']);
		// Checking admin rights and constructor mode and non-empty ID 
		if ($_GET['id'] && $this->CONSTRUCTOR_MODE) {
			$sql = "UPDATE `".db('settings')."` SET
					`item`		= '"._es($_POST['item'])."',
					`value`		= '"._es($_POST['value'])."',
					`type`		= '"._es($_POST['type'])."',
					`size`		= '"._es($_POST['size'])."',
					`order`		= "._es($_POST['order']).",
					`category`	= "._es($_POST['category'])."
				WHERE `id`=".$_GET['id'];
			db()->query($sql);
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("settings");
		return js_redirect("./?object=".$_GET["object"]);
	}

	/**
	* Show settings categories
	*/
	function show_categories() {
		// Checking admin rights and constructor mode
		if ($this->CONSTRUCTOR_MODE) {
			$Q = db()->query("SELECT * FROM `".db('settings_category')."` ORDER BY `order` ASC");
			$num_rows = db()->num_rows($Q);
			while ($A = db()->fetch_assoc($Q)) {
				$replace2 = array(
					"id"		=> $A['id'],
					"name"		=> $A['name'],
					"order"		=> common()->select_box("order_".$A['id'], range(0, $num_rows), $A['order'], false, 2),
					"del_link"	=> "./?object=".$_GET["object"]."&action=delete_category&id=".$A['id'],
				);
				$items .= tpl()->parse("settings/cat_item", $replace2);
			}
			$replace = array(
				"form_action"	=> "./?object=".$_GET["object"]."&action=update_category",
				"items"			=> $items,
				"add_action"	=> "./?object=".$_GET["object"]."&action=add_category",
				"add_name"		=> "",
				"add_order"		=> common()->select_box("order", range(0, $num_rows + 1), $num_rows + 1, false, 2),
				"back"			=> back("./?object=".$_GET["object"]),
			);
			return tpl()->parse("settings/cat_main", $replace);
		}
	}

	/**
	* Update settings categories
	*/
	function update_category() {
		// Checking admin rights and constructor mode
		if ($this->CONSTRUCTOR_MODE) {
			$Q = db()->query("SELECT * FROM `".db('settings_category')."`");
			while ($A = db()->fetch_assoc($Q)) {
				$item = "name_".$A['id'];
				// If item is empty - skip it
				if (!strlen($_POST[$item])) continue;
				$_POST[$item] = str_replace(array('"', "'"), array('\"', "\'"), html_entity_decode(stripslashes($_POST[$item])));
				$sql = "UPDATE `".db('settings_category')."` SET 
						`name`='"._es($_POST[$item])."', 
						`order`=".$_POST["order_".$A['id']]." 
					WHERE `id`=".$A['id'];
				db()->query($sql);
			}
		}
		return js_redirect("./?object=".$_GET["object"]."&action=show_categories");
	}

	/**
	* Add setting category
	*/
	function add_category() {
		// Checking admin rights and constructor mode
		if ($this->CONSTRUCTOR_MODE) {
			$sql = "INSERT INTO `".db('settings_category')."` (
					`name`,
					`order`
				) VALUES (
					'"._es($_POST['name'])."',
					".intval($_POST['order'])."
				)";
			db()->query($sql);
		}
		return js_redirect("./?object=".$_GET["object"]."&action=show_categories");
	}

	/**
	* Delete setting category
	*/
	function delete_category() {
		$_GET['id'] = intval($_GET['id']);
		// Checking admin rights and constructor mode and non-empty ID
		if ($_GET['id'] && $this->CONSTRUCTOR_MODE) {
			db()->query("DELETE FROM `".db('settings_category')."` WHERE `id`=".$_GET['id']);
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]."&action=show_categories");
		}
	}
}
