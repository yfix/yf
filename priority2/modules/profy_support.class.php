<?php

/**
* Alias for the "help->email_form"
*/
class profy_support {

	/**
	* Default method
	*/
	function show () {
		return js_redirect("./?object=help&action=email_form");
	}
}
