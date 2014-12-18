<?php

class yf_form2_tinymce {

	/**
	* Embedding tinymce editor (http://www.tinymce.com/).
	*/
	function _tinymce_html($extra = array(), $replace = array(), $__this) {
		if (!is_array($extra)) {
			return '';
		}
		$params = $extra['tinymce'];
		if (!is_array($params)) {
			$params = array();
		}
		if ($__this->_tinymce_scripts_included) {
			return '';
		}
		$web_path = '';
		if (conf('tinymce::use_submodule')) {
			$path = $params['tinymce_path'] ? $params['tinymce_path'] : 'tinymce/tiny_mce.js';
			$fs_path = PROJECT_PATH. $path;
			$web_path = WEB_PATH. $path;
			if (!file_exists($fs_path)) {
				return '';
			}
			js($fs_path);
		} else {
			js('tinymce');
		}
		$content_id = $extra['id'] ? $extra['id'] : 'content_editable';
		$hidden_id = $params['hidden_id'] ? $params['hidden_id'] : '';

		js('
			tinymce.init({
			    selector: "#'.$content_id.'", //"textarea",
//			    plugins: [
//			        "advlist autolink lists link image charmap print preview anchor",
// 					"searchreplace visualblocks code fullscreen",
//					"insertdatetime media table contextmenu paste moxiemanager"
//				],
				toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
			});
		');

		// Avoid including tinymce scripts several times on same page
		$__this->_tinymce_scripts_included = true;

		return $body;
	}
}