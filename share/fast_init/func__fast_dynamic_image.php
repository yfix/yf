<?php

// Fast process dynamic image
function _fast_dynamic_image () {
	main()->NO_GRAPHICS = true;

	$c = "profy_dynamic";
	include (PF_PATH."modules/".$c.".class.php");
	if (class_exists($c)) {
		$obj = new $c();
		$obj->image();
	}

	return true; // Means success
}
