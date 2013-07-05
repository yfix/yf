<?php
class yf_shop__show_header{

	function _show_header() {
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));
		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"		=> "",
			"basket"		=> t("Shopping basket"),
			"order"		=> t("Checkout"),
		);
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}
		return array(
			"header"	=> $page_header ? _prepare_html($page_header) : t("shop"),
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}
	
}