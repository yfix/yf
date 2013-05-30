<?php

/**
* Ban editor
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_ban_editor {

	/**
	* Default method
	*/
	function show () {
		$sql = "SELECT * FROM `".db('banned_ips')."` ORDER BY `ip` ASC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$data[$A["ip"]] = $A;
			if ($A["admin_id"]) {
				$admins_ids[$A["admin_id"]] = $A["admin_id"];
			}
		}
		// Get admins infos
		if ($admins_ids) {
			$Q = db()->query("SELECT * FROM `".db('admin')."` WHERE `id` IN(".implode(",",$admins_ids).")");
			while ($A = db()->fetch_assoc($Q)) $admins_names[$A["id"]] = $A["first_name"]." ".$A["last_name"];
		}
		// Process items
		foreach ((array)$data as $A) {
			$items[$A["ip"]] = array(
				"ip"			=> _prepare_html($A["ip"]),
				"date"			=> _format_date($A["time"], "long"),
				"admin_link"	=> "./?object=admin&action=show&id=".$A["admin_id"],
				"admin_name"	=> _prepare_html($admins_names[$A["admin_id"]]),
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".urlencode($A["ip"]),
			);
		}
		$replace = array(
			"items"		=> $items,
			"pages"		=> $pages,
			"total"		=> intval($total),
			"add_link"	=> "./?object=".$_GET["object"]."&action=add",
		);
		return tpl()->parse(__CLASS__."/main", $replace);
	}

	/**
	* Add new IP to ban list
	*/
	function add () {
		if (!empty($_POST)) {
			$_POST["ip"] = trim(preg_replace("/[^0-9\.\/\*]/i", "", $_POST["ip"]));
			if (!empty($_POST["ip"])) {
				db()->INSERT("banned_ips", array(
					"ip"		=> _es($_POST["ip"]),
					"time"		=> time(),
					"admin_id"	=> intval($_SESSION["admin_id"]),
				));
				// Refresh system cache
				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("banned_ips");
				}
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"back_link"		=> "./?object=".$_GET["object"]."&action=add",
			"ip"			=> _es($_POST["ip"]),
		);
		return tpl()->parse(__CLASS__."/add", $replace);
	}

	/**
	* Delete IP from ban
	*/
	function delete () {
		$IP = trim(preg_replace("/[^0-9\.\/\*]/i", "", urldecode($_GET["id"])));
		if ($IP) {
			db()->query("DELETE FROM `".db('banned_ips')."` WHERE `ip` = '"._es($IP)."'");
			// Refresh system cache
			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("banned_ips");
			}
		}
		return js_redirect("./?object=".$_GET["object"]);
	}
}
