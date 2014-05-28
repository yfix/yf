<?php

class yf_form2_image {

	/**
	* Image upload
	*/
	function image($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
// TODO: show already uploaded image, link to delete it, input to upload new
		$extra['name'] = $extra['name'] ?: ($name ?: 'image');
		$extra['desc'] = $__this->_prepare_desc($extra, $desc);
		$func = function($extra, $r, $_this) {
/*
			$_this->_prepare_inline_error($extra);
			$extra['id'] = $extra['name'];
*/
			return $_this->_row_html('<input type="file">', $extra, $r);
		};
		if ($__this->_chained_mode) {
			$__this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $__this;
		}
		return $func($extra, $replace, $__this);
	}
}