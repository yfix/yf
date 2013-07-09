<?php
class yf_manage_shop__show_header{

	function _show_header() {
		$pheader = t("Shop");
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"	=> "Products",
			"add"	=> "Add product",
		);			  		
		if (isset($cases[$_GET["action"]])) {
			$subheader = $cases[$_GET["action"]];
		}
		return array(
			"header"	=> $pheader,
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}
	
}