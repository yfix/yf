<?php

class yf_login {
	function show() {
		$_GET['task'] = 'login';
		main()->init_auth();
	}
}
