<?php

class yf_settings {
	function _module_action_handler($called_action) {
		return js_redirect("./?object=manage_conf");
	}
}
