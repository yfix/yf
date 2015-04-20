<?php

class yf_logout {
	function show() {
		$_GET['task'] = 'logout';
		main()->init_auth();
	}
}
