<?php

require dirname(__FILE__)."/header.php";

if(isset($_POST["next"])){
	$_SESSION['INSTALL']["modules"] = $_POST["modules"];
	$_SESSION['INSTALL']["import_initial_data"] = $_POST["import_initial_data"];

	$log_text .= "\n//********* Modules **********//\n";
	$log_text .= "Selected modules ids\"".implode(",", array_keys((array)$_POST["modules"]))."\"\n";
	$log_text .= "Import initial data: ".$_POST["import_initial_data"]."\n";

	add_log($log_text);
	
	header("Refresh: 0;URL=".$_SESSION['INSTALL']['_WEB_PATH']."install.php?step=".$next_step);
}

if(isset($_POST["prev"])){
	header("Refresh: 0;URL=".$_SESSION['INSTALL']['_WEB_PATH']."install.php?step=".$back_step);
}

	include dirname(__FILE__)."/data_menu_items.php";

	function _sort_menu_items ($a, $b) {
		if ($a["order"] == $b["order"]) {
			return 0;
		}
		return ($a["order"] < $b["order"]) ? -1 : 1;
	}
	uasort($GLOBALS['INSTALL']["data_menu_items"], "_sort_menu_items");

	$menu_id_for_template = 2;
	$items_for_template = array();
	$hide_items = array(
		"Terms",
		"Privacy",
		"About",
	);
	foreach ((array)$GLOBALS['INSTALL']["data_menu_items"] as $item) {
		if ($item["menu_id"] != $menu_id_for_template || in_array($item["name"], $hide_items)) {
			continue;
		}
		$items_for_template[$item["id"]] = $item["name"];
	}
	$checked_items = $_SESSION['INSTALL']["modules"] ? $_SESSION['INSTALL']["modules"] : $items_for_template;

	include $_SESSION['INSTALL']["install_path"]."template/header.stpl";
	include $_SESSION['INSTALL']["install_path"]."template/step_".$_GET["step"].".stpl";
	include $_SESSION['INSTALL']["install_path"]."template/footer.stpl";

?>