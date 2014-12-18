<?php

class yf_form2_ckeditor {

	/**
	* Embedding ckeditor (http://ckeditor.com/) with kcfinder (http://kcfinder.sunhater.com/).
	* You can include it into project like this:
	*
	* git submodule add https://github.com/yfix/ckeditor-releases.git www/ckeditor/ && cd www/ckeditor/ && git checkout latest/full
	* git submodule add https://github.com/yfix/kcfinder.git www/kcfinder
	* 
	* 'www/' usually means PROJECT_PATH inside project working copy.
	* P.S. You can use free CDN for ckeditor as alternate solution.
	*/
	function _ckeditor_html($extra = array(), $replace = array(), $__this) {
		if (!is_array($extra)) {
			return '';
		}
		$params = $extra['ckeditor'];
		if (!is_array($params)) {
			$params = array();
		}
		if ($__this->_ckeditor_scripts_included) {
			return '';
		}
		$web_ck_path = '';
		if (conf('ckeditor::use_submodule')) {
			$ck_path = $params['ck_path'] ? $params['ck_path'] : 'ckeditor/ckeditor.js';
			$fs_ck_path = PROJECT_PATH. $ck_path;
			$web_ck_path = WEB_PATH. $ck_path;
			if (!file_exists($fs_ck_path)) {
				return '';
			}
			js($fs_ck_path);
		} else {
			js('ckeditor');
		}

		// Theme-wide ckeditor config inside stpl (so any engine vars can be processed or included there)
		$stpl_name = 'ckeditor_config'; // Example filesystem location: PROJECT_PATH.'templates/admin/ckeditor_config.stpl'
		if (!isset($replace['content_id'])) {
			$replace['content_id'] = $content_id;
		}
		$config_js .= tpl()->_stpl_exists($stpl_name) ? tpl()->parse($stpl_name, (array)$extra + (array)$replace) : '';
		js($config_js);

		$content_id = $extra['id'] ? $extra['id'] : 'content_editable';
		$hidden_id = $params['hidden_id'] ? $params['hidden_id'] : '';

		jquery('
			var _content_id = "#'.$content_id.'";
			var _hidden_id = "#'.$hidden_id.'";
			$(_content_id).parents("form").submit(function(){
				$("input[type=hidden]" + _hidden_id).val( $(_content_id).html() );
			})
		');

		// Avoid including ckeditor scripts several times on same page
		$__this->_ckeditor_scripts_included = true;

		return $body;
	}
}