<?php

/**
* Admin rights handling class
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_admin_rights {

	/**
	* Constructor
	* 
	*/
	function yf_admin_rights () {
		// Get admin modules
		$this->_admin_modules = main()->_execute("admin_modules", "_get_modules");
	}

	/**
	* Form to edit admin rights
	*/
	function show() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$A2 = db()->query_fetch("SELECT * FROM `".db('admin_groups')."` WHERE `id`=".$_GET['id']);
			foreach ((array)$this->_admin_modules as $name) {
				$A = db()->query_fetch("SELECT * FROM `".db('admin_rights')."` WHERE `group`=".$_GET['id']." AND `module`='".$name."'");
				$replace2 = array(
					"text"		=> "<b>".$name."</b>".((conf('language') == "en") ? "" : "&nbsp; &nbsp;(".ucfirst(t($name)).")"),
					"name"		=> $name,
					"checked1"	=> $A["allow"] ? "checked" : "",
					"checked2"	=> !$A["allow"] ? "checked" : "",
				);
				$items .= tpl()->parse("admin_rights/modules_item", $replace2);
			}
			$replace = array(
				"header_text"		=> ucfirst(t('rights'))." ".t('for')." '".$A2["name"],
				"form_action"		=> "./?object=".$_GET["object"]."&action=update&id=".$_GET['id'],
				"items"				=> $items,
				"group_name"		=> $A2["name"],
				"show_tables"		=> $this->show_tables(),
				"back"				=> back("./?object=admin_groups"),
			);
			$body .= tpl()->parse("admin_rights/modules_main", $replace);
		} else $body = error_back();
		return $body;
	}

	/**
	* This function show tables to assign rights to them
	*/
	function show_tables () {
		// Insert all tables into array
		$Q = db()->query("SHOW TABLES");
		while ($A2 = db()->fetch_row($Q)) {
			$table_name = substr($A2[0], strlen(DB_PREFIX));
			if (substr($table_name, 0, 4) == "sys_") continue;
			if ($table_name == "db_parser_rights") continue;
			$tables[] = $table_name;
		}
		// Process collected tables
		foreach ((array)$tables as $k => $name) {
			$A = db()->query_fetch("SELECT * FROM `".db('db_parser_rights')."` WHERE `group`=".$_GET['id']." AND `table`='".$name."'");
			$replace2 = array(
				"text"		=> "<b>".$name."</b>".((conf('language') == 1) ? "" : "&nbsp; &nbsp;(".ucfirst(t($name)).")"),
				"name"		=> $name,
				"allow1"	=> $A["allow"] ? "checked" : "",
				"allow2"	=> !$A["allow"] ? "checked" : "",
				"debug"		=> $_GET['id'] == 1 ? ($A["debug"] ? "checked" : "") : "disabled",
				"hide"		=> $A["hide"] ? "checked" : "",
			);
			$items .= tpl()->parse("admin_rights/tables_item", $replace2);
		}
		$replace = array(
			"header_text"	=> ucfirst(t("rights"))." ".t("for")." ".t("db_parser"),
			"form_action"	=> "./?object=".$_GET["object"]."&action=update_tables&id=".$_GET['id'],
			"items"			=> $items,
		);
		return tpl()->parse("admin_rights/tables_main", $replace);
	}

	/**
	* This function update admin rights
	*/
	function update() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id'] && $_SESSION[admin_id] && $_SESSION[admin_group] == 1) {
			db()->query("DELETE FROM `".db('admin_rights')."` WHERE `group`=".$_GET['id']);
			foreach ((array)$this->_admin_modules as $name) {
				$sql = "INSERT INTO `".db('admin_rights')."` ( 
						`group`,
						`module`,
						`allow`
					) VALUES (
						".$_GET['id'].",
						'".$name."',
						'".$_POST[$name]."'	
					)\r\n";
				if (!empty($_POST[$name])) db()->query($sql);
			}
			if (!db()->query_num_rows("SELECT * FROM `".db('admin_groups')."` WHERE `id`=".$_GET['id'])) 
				db()->query("INSERT INTO `".db('admin_groups')."` (`id`, `name`) VALUES (".$_GET['id'].", '".$_POST["admin_group_name"]."')");
			else if ($_POST["admin_group_name"]) db()->query("UPDATE `".db('admin_groups')."` SET name='".$_POST["admin_group_name"]."' WHERE id=".$_GET['id']);
		}
		js_redirect("./?object=admin_groups");
	}

	/**
	* This function update admin rights
	*/
	function update_tables() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id'] && $_SESSION['admin_id'] && $_SESSION['admin_group'] == 1) {
			db()->query("DELETE FROM `".db('db_parser_rights')."` WHERE `group`=".$_GET['id']);
			$tables_list = db()->meta_tables();
			foreach ((array)$tables_list as $table_name) {
				$name = substr(str_replace("sys_","",$table_name), strlen(DB_PREFIX));
				if (!$_POST["allow_".$name]) continue;
				db()->INSERT("db_parser_rights", array(
					"group"	=> intval($_GET['id']),
					"table"	=> _es($name),
					"allow"	=> intval($_POST["allow_".$name]),
					"debug"	=> intval($_POST["debug_".$name]),
					"hide"	=> intval($_POST["hide_".$name]),
				));
			}
		}
		js_redirect("./?object=admin_groups");
	}
}
