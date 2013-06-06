<?php

$body .= print_r($GLOBALS["db"]->query_fetch("SELECT * FROM `".dbt_user_groups."` LIMIT 1"), 1)."<br /><br />\n";

$GLOBALS['db_pfadmin'] = &new yf_db("mysql41", 1, "pf_");
$GLOBALS['db_pfadmin']->connect("localhost", "root", "", "pf_admin");

$GLOBALS["db_pfadmin"]->INSERT("news", array("title" => "test"));
$GLOBALS["db_pfadmin"]->INSERT("pf_news", array("title" => "test"));

$insert_review = array(
	"email"		=> _es("test111@test.ru"),
	"status"	=> _es("new"),
	"edit_date"	=> time(),
);

$GLOBALS["db_pfadmin"]->INSERT("soft_review", $insert_review);

$body .= print_r($GLOBALS["db_pfadmin"]->query_fetch("SELECT * FROM `pf_sys_menu_items` LIMIT 2"), 1)."<br /><br />\n";

$GLOBALS['db_smsirc'] = &new yf_db("mysql5", 1, "pf_");
$GLOBALS['db_smsirc']->connect("localhost", "root", "", "smsirc_down");

$insert_review = array(
	"email"		=> _es("test222@test.ru"),
	"status"	=> _es("new"),
	"edit_date"	=> time(),
);
$GLOBALS["db_smsirc"]->INSERT("soft_review", $insert_review);

$body .= print_r($GLOBALS["db_smsirc"]->query_fetch("SELECT * FROM `hosts` LIMIT 3"), 1)."<br /><br />\n";

$GLOBALS['db_onekit'] = &new yf_db("mysql5", 1, "test_");
$GLOBALS['db_onekit']->connect("localhost", "root", "", "inffinity");

$insert_review = array(
	"email"		=> _es("test333@test.ru"),
	"status"	=> _es("new"),
	"edit_date"	=> time(),
);
$GLOBALS["db_onekit"]->INSERT("soft_review", $insert_review);

$body .= print_r($GLOBALS["db_onekit"]->query_fetch("SELECT * FROM `test_geo_city_location` LIMIT 5"), 1)."<br /><br />\n";
