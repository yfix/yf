<?php
$GLOBALS['no_graphics'] = true;
include ('./index.php');
$qs = (!empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '');
if (empty($_SESSION['admin_id'])) {
	js_redirect('./'.$qs);
} else {
	echo tpl()->parse('main_frameset', array('query_string' => $qs));
}
