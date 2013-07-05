<?php

/**
* Test sub-class
*/
class yf_test_maxmind_phone_check {

	/**
	* YF module constructor
	*/
	function _init () {
		$this->TEST_OBJ = module("test");
	}

	/**
	*/
	function run_test () {
		if ($this->USER_ID != 1) {
			return "<b style='color:red;'>ACCESS DENIED!!!</b>";
		}
		$OBJ = main()->init_class("maxmind_phone_verify", "classes/");
		if (!empty($_POST["user"])) {
			$_POST["user"] = intval($_POST["user"]);
			$A = user($_POST["user"], array("phone", "country"));
			$check_result = $OBJ->_send_request($A["phone"], $_POST["user"], $A["country"]);
			return $OBJ->_action_log. "<br /><br />Phone check result: ".($check_result ? "<b style='color:green;'>Good</b>" : "<b style='color:red;'>Bad</b>");
		}
		// Display form
		$sql = "SELECT id, nick, phone, country 
				FROM ".db('user')." 
				WHERE country IN ('".implode("','", $OBJ->_cc_to_verify)."') 
					AND group='3' 
					AND phone != '' 
					AND active='1' 
				ORDER BY id DESC 
				LIMIT 100";
		$A = db()->query_fetch_all($sql);
		foreach ((array)$A as $B){
			$select_array[$B["id"]] = _prepare_html("(ID:".$B["id"].") ".$B["nick"]." (".$B["phone"].") (".$B["country"].")");
		}
		$replace = array(
			"action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"user_box" 	=> common()->select_box("user", $select_array, "", " ", 2, "", false),
		);		
		return tpl()->parse($_GET["object"]."/maxmind_phone_form", $replace);
	}
}
