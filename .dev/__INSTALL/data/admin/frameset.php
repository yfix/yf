<?php

$GLOBALS['no_graphics'] = true;

include ("./index.php");

if (empty($_SESSION["admin_id"])) {
	js_redirect("./".(!empty($_SERVER["QUERY_STRING"]) ? "?".$_SERVER["QUERY_STRING"] : ""));
} else {
	echo $GLOBALS['tpl']->parse("main_frameset", array("query_string" => !empty($_SERVER["QUERY_STRING"]) ? "?".$_SERVER["QUERY_STRING"] : ""));
}

?>