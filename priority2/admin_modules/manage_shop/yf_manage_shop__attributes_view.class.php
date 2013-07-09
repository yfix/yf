<?php
class yf_manage_shop__attributes_view{

	function _attributes_view ($object_id = 0) {
		return module("manage_shop")->_attributes_html($object_id, true);
	}
	
}