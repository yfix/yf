<?php

class yf_form2_image {

	/**
	* Image upload
	*/
	function image($name = '', $desc = '', $extra = array(), $replace = array(), $form) {
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
// TODO: show already uploaded image, link to delete it, input to upload new
		$extra['name'] = $extra['name'] ?: ($name ?: 'image');
		$extra['desc'] = $form->_prepare_desc($extra, $desc);
		$func = function($extra, $r, $form) {
/*
			$form->_prepare_inline_error($extra);
			$extra['id'] = $extra['name'];
*/
			return $form->_row_html('<input type="file">', $extra, $r);
		};
		if ($form->_chained_mode) {
			$form->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $form;
		}
		return $func($extra, $replace, $form);
	}
}