<?php

class yf_notifications_admin {
// hack - we need admin data for this
	function check() {
		return _class("notifications", "modules/")->check();
	}
	
	function read() {
		return _class("notifications", "modules/")->read();
	}
	
}

	
