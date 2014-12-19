<?php

return function() {
	if (isset($_GET['id'])) {
		list($id, $ext) = explode('.', $_GET['id']);
	} else {
		list($id, $ext) = explode('.', substr($_SERVER['REQUEST_URI'], strlen('/dynamic/placeholder/')));
	}
	list($w, $h) = explode('x', $id);
	$w = (int)$w ?: 100;
	$h = (int)$h ?: 100;

	require_once YF_PATH.'share/functions/yf_placeholder_img.php';
	echo yf_placeholder_img($w, $h);

	return true; // Means success
};
