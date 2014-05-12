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
		} else {
			$web_path = '//cdnjs.cloudflare.com/ajax/libs/tinymce/3.5.8/tiny_mce.js';
		}
		if (!$web_path) {
			return '';
		}
		$content_id = $extra['id'] ? $extra['id'] : 'content_editable';
		$hidden_id = $params['hidden_id'] ? $params['hidden_id'] : '';

		$body .= '<script src="'.$web_path.'" type="text/javascript"></script>'.PHP_EOL;
#		js($web_path);

/*
			$(function(){
				var _content_id = "#'.$content_id.'";
				var _hidden_id = "#'.$hidden_id.'";
				$(_content_id).parents("form").submit(function(){
					$("input[type=hidden]" + _hidden_id).val( $(_content_id).html() );
				})
			})
*/
		$body .= '<script type="text/javascript">
tinymce.init({
    selector: "#'.$content_id.'", //"textarea",
//    plugins: [
//        "advlist autolink lists link image charmap print preview anchor",
//        "searchreplace visualblocks code fullscreen",
//        "insertdatetime media table contextmenu paste moxiemanager"
//    ],
    toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
});
			</script>';

/*
		// Theme-wide config inside stpl (so any engine vars can be processed or included there)
		$stpl_name = 'tinymce_config'; // Example filesystem location: PROJECT_PATH.'templates/admin/tinymce_config.stpl'
		if (!isset($replace['content_id'])) {
			$replace['content_id'] = $content_id;
		}
		$config_js .= tpl()->_stpl_exists($stpl_name) ? tpl()->parse($stpl_name, (array)$extra + (array)$replace ) : '';
#		js($config_js);
		$body .= $config_js;
*/
		// Avoid including tinymce scripts several times on same page
		$__this->_tinymce_scripts_included = true;

		return $body;
	}
}