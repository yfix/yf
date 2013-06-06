<?php

/**
* Profy Login Form
*
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_login_form {

	/** @var string */
	var $DEF_REDIRECT_URL	= "./?object=account";

	/**
	* Default function
	*/
	function show () {
		return $this->_show_form();
	}

	/**
	* Login form
	*/
	function _show_form () {
		// Already logged in users not needed to login again
		if ($this->USER_ID) {
			return js_redirect($this->DEF_REDIRECT_URL);
		}
		// Default resirect after login
		$_url_after_login = $this->DEF_REDIRECT_URL;
		// Process special redirect format
		if (!empty($_GET["go_url"])) {

			if (false !== ($pos1 = strpos($_GET["go_url"], ";"))) {
				$_GET["go_url"] = substr($_GET["go_url"], 0, $pos1)."&action=".substr($_GET["go_url"], $pos1 + 1);
			}
			$_url_after_login = "./?object=".str_replace(";", "&", $_GET["go_url"]);

		} elseif (conf('_force_login_go_url')) {

			$_url_after_login = conf('_force_login_go_url');

		}
		if (strlen($_url_after_login) > 3 && !$_SESSION['user_go_url']) {
			if (substr($_url_after_login, 0, 3) == "./?") {
				$_url_after_login = substr($_url_after_login, 3);
			}
			$_SESSION['user_go_url'] = $_url_after_login;
		}
		// To prevent multiple login forms displayed on one page
		conf('_login_form_displayed', true);

		return tpl()->parse(__CLASS__."/form", array("form_action" => "./?task=login"));
	}

	/**
	* Wrong Login
	*/
	function wrong_login () {
		$replace = array(
			"login_form_url"	=> process_url("./?object=login_form"),
		);
		return tpl()->parse(__CLASS__."/wrong_login", $replace);
	}

	/**
	* Account Inactive
	*/
	function account_inactive () {
		$replace = array(
			"login_form_url"	=> process_url("./?object=login_form"),
		);
		return tpl()->parse(__CLASS__."/account_inactive", $replace);
	}
}
