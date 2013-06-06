<?php

$debug_users = array(
	'central_test' => 'central_5555_test',
);
$debug_code = "57";
$debug_salt = "_5555_";
// TODO: need to check exact robots meta tag/header contents for SEO (maybe use: noindex, follow ?)
$robots_options = "noindex, nofollow, noarchive, nosnippet";

$console_mode = (!empty($_SERVER['argc']) && !array_key_exists('REQUEST_METHOD', $_SERVER));
$debug_request = (isset($_GET['debug']) ? $_GET['debug'] : (isset($_POST['debug']) ? $_POST['debug'] : false));
$need_debug_auth = (!defined('DEBUG_MODE') && !empty($debug_request));

if ($console_mode && $need_debug_auth) {
	define('DEBUG_MODE', 1);
} elseif (!$console_mode && $need_debug_auth) {
	$is_bot = preg_match('/(wget|curl|bot|spider|crawler|yandexbot|googlebot|slurp|baidu|cyscon|mcafee|scanalert|httpclient|mediapartners|ia\-archiver|ips\-agent)/ims', $_SERVER['HTTP_USER_AGENT']);
	if ($is_bot || $debug_request != $debug_code) {
		header(($_SERVER["SERVER_PROTOCOL"] ? $_SERVER["SERVER_PROTOCOL"] : "HTTP/1.1")." 301 Moved Permanently");
		header("Location: "._debug_remove_from_url());
		exit();
	}
	header("Expires: Tue, 03 Jul 2001 06:00:00 GMT"); // Date far in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header("X-Robots-Tag: ".$robots_options);

	$hash = substr(md5($debug_salt. gmdate("Y-m-d")), 6, 16);
	$cookie_name = '_debug_authorized_'.$hash;
	if (_debug_auth_check($debug_users) || !empty($_COOKIE[$cookie_name])) {
		$h = array_reverse(explode(".", $_SERVER["HTTP_HOST"]));
		setcookie($cookie_name, '1', 0, '/', $h[1].".".$h[0]); // Live for session, set for TLD
		define('DEBUG_MODE', 1);
	} else {
		header('WWW-Authenticate: Basic realm="Restricted area"');
		header('HTTP/1.0 401 Unauthorized');
		echo '<head><meta name="robots" content="'.$robots_options.'"></head>';
		echo '401 Unauthorized';
		exit();
	}
}
function _debug_remove_from_url($uri = "", $qs = "") {
	if (empty($uri)) { $uri = $_SERVER["REQUEST_URI"]; }
	if (empty($uri)) { $uri = "/"; }
	if (empty($qs))  { $qs = $_SERVER["QUERY_STRING"]; }
	if (empty($qs))  { $qs = ""; }
	if (strlen($qs)) {
		$uri = substr($uri, 0, -strlen($qs) - 1);
	}
	parse_str($qs, $p);
	if (!$p) {
		return $uri. ($qs ? "?".$qs : "");
	}
	if (isset($p["debug"])) {
		unset($p["debug"]);
	}
	$qs = http_build_query($p);
	return $uri. ($qs ? "?".$qs : "");
}
function _debug_auth_check($users = array()) {
	$auth_user = trim($_SERVER['PHP_AUTH_USER']);
	$auth_pswd = trim($_SERVER['PHP_AUTH_PW']);
	if (!strlen($auth_user) || !strlen($auth_pswd)) {
		return false;
	}
	foreach ((array)$users as $user => $pswd) {
		if ($auth_user == $user && $auth_pswd == $pswd) {
			return true;
		}
	}
	return false;
}
