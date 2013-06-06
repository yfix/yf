<?php

/**
* User rights handling class
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_user_rights {

	/** @var array @conf_skip */
	var $_trigger = array(
		1	=> "<span class='positive'>Allowed</span>",
		0	=> "<span class='negative'>Denied</span>",
	);

	/**
	* Form to edit user rights
	*/
	function show() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$GArray = db()->fetch_assoc(db()->query("SELECT * FROM `".db('user_groups')."` WHERE `id`=".$_GET['id']));
			$body .= "<h3>".ucfirst(t("access_rights_for"))." '".$GArray["name"]."'</h3>\r\n";
			$body .= "<table align='center' cellspacing='0' cellpadding='2' border='0'>
						<form action='./?object=".$_GET["object"]."&action=update&id=".$_GET['id']."&table=".$_GET["table"]."' method='post'>
						  <tr>
							<td><nobr>".t("module_name")."</nobr><br><br></td>
							<td align='center' style='color:green;'><b>".t('status')."</b><br><br></td>
						  </tr>\r\n";
			// Get user rights
			$Q = db()->query("SELECT * FROM `".db('user_rights')."` WHERE `group`=".$_GET['id']);
			while ($A = db()->fetch_assoc($Q)) $user_rights[$A["module"]] = $A["allow"];
			// Get available modules
			$Modules = $this->get_modules();
			// Process modules
			foreach ((array)$Modules as $k => $Name) {
				$body .= "<tr>
							<td><nobr><b>".$Name."</b>".(conf("language") != "en" ? "&nbsp; &nbsp;(".ucfirst(t($Name)).")\r\n" : "")."</nobr></td>\r\n";
				$body .= "	<td align='center'>".common()->radio_box($Name, $this->_trigger, $user_rights[$Name])."</td>\r\n";
				$body .= "</tr>\r\n";
			}
			$body .= "<tr>
						<td><br>".t("group_name")."</td>
						<td colspan='2' align='center'>
						<br><input type='text' name='user_group_name' value='".$GArray[name]."'>
					</td>
				  </tr>\r\n";
			$body .= "<tr><td colspan='3' align='center'><br><input type='submit' value='".strtoupper(t('save'))."'></td></tr>\r\n";
			$body .= "</form>
					</table>\r\n";
			$body .= back("./?object=user_groups");
			return $body;
		} else js_redirect("./?object=user_groups");
	}

	/**
	* Form to add new user group
	*/
	function add() {
		$GArray = db()->fetch_assoc(db()->query("SELECT MAX(`id`) AS `max` FROM `".db('user_groups')."`"));
		$id = $GArray['max'] + 1;

		$body .= "<h3>".t('new')." '".ucfirst(t('group'))."'</h3>\r\n";
		$body .= "<table width='200' align='center' cellspacing='0' cellpadding='2' border='0'>
					<form action='./?object=".$_GET["object"]."&action=update&id=".$id."&table=".$_GET[table]."' method='post'>
					  <tr>
						<td>".t('module')."<br><br></td>
						<td align='center' style='color:green;'><b>".t('status')."</b><br><br></td>
					  </tr>\r\n";
		// Get available modules
		$Modules = $this->get_modules();
		// Process modules
		foreach ((array)$Modules as $k => $Name) {
			$body .= "<tr>
						<td><nobr><b>".$Name."</b>".(conf("language") != "en" ? "&nbsp; &nbsp;(".ucfirst(t($Name)).")\r\n" : "")."</nobr></td>\r\n";
			$body .= "	<td align='center'>".common()->radio_box($Name, $this->_trigger, $user_rights[$Name])."</td>\r\n";
			$body .= "</tr>\r\n";
		}
		$body .= "<tr>
					<td><br>".t("group_name")."</td>
					<td colspan='2' align='center'>
					<br><input type='text' name='user_group_name' value='".$GArray[name]."'>
				</td>
			  </tr>\r\n";
		$body .= "<tr><td colspan='3' align='center'><br><input type='submit' name='submit' value='".strtoupper(t('save'))."'></td></tr>\r\n";
		$body .= "</form>
				</table>\r\n";
		$body .= back("./?object=user_groups");
		return $body;
	}

	/**
	* This function update admin rights
	*/
	function update() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id'] && $_SESSION[admin_id] && $_SESSION[admin_group] == 1) {
			db()->query("DELETE FROM `".db('user_rights')."` WHERE `group`=".$_GET['id']);
			$Modules = $this->get_modules();
			foreach ((array)$Modules as $k => $Name) {
				if (empty($_POST[$Name])) continue;
				$sql = "INSERT INTO `".db('user_rights')."` (`group`,`module`,`allow`) VALUES (".$_GET['id'].",'".$Name."','".$_POST[$Name]."')";
				db()->query($sql);
			}
			if (!db()->num_rows(db()->query("SELECT * FROM `".db('user_groups')."` WHERE `id`=".$_GET['id']))) 
				db()->query("INSERT INTO `".db('user_groups')."` (id, name) VALUES (".$_GET['id'].", '".$_POST["user_group_name"]."')");
			else if ($_POST["user_group_name"]) db()->query("UPDATE `".db('user_groups')."` SET name='".$_POST["user_group_name"]."' WHERE `id`=".$_GET['id']);
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("user_rights");
		js_redirect("./?object=user_groups");
	}

	/**
	* This function return array of existing modules (files)
	*/
	function get_modules ($path = "") {
		$this->_user_modules = main()->_execute("user_modules", "_get_modules", array("with_all" => 0));
		return $this->_user_modules;
/*
		$OBJ = main()->init_class("user_modules");
		if (is_object($OBJ)) {
			return $this->_user_modules = $OBJ->_get(0);
		}
*/
	}
}
