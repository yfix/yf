<?php

class yf_form2_ckeditor {

	/**
	* Embedding ckeditor (http://ckeditor.com/) with kcfinder (http://kcfinder.sunhater.com/).
	* Best way to include it into project: 
	*
	* git submodule add https://github.com/yfix/ckeditor-releases.git www/ckeditor/ && cd www/ckeditor/ && git checkout latest/full
	* git submodule add git@github.com:yfix/yf_kcfinder.git www/kcfinder
	* 
	* 'www/' usually means PROJECT_PATH inside project working copy.
	* P.S. You can use free CDN for ckeditor as alternate solution: <script src="//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.0.1/ckeditor.js"></script>
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
		} else {
			// Note: do not use ckeditor.min.js - not working for some reason on cdnjs
			$web_ck_path = '//cdnjs.cloudflare.com/ajax/libs/ckeditor/4.3.2/ckeditor.js';
		}
		if (!$web_ck_path) {
			return '';
		}
		// Main ckeditor script
#		require_js($web_ck_path);
		$body .= '<script src="'.$web_ck_path.'" type="text/javascript"></script>'.PHP_EOL;

		// Theme-wide ckeditor config inside stpl (so any engine vars can be processed or included there)
		$stpl_name = 'ckeditor_config'; // Example filesystem location: PROJECT_PATH.'templates/admin/ckeditor_config.stpl'
		if (!isset($replace['content_id'])) {
			$replace['content_id'] = $content_id;
		}
		$config_js .= tpl()->_stpl_exists($stpl_name) ? tpl()->parse($stpl_name, (array)$extra + (array)$replace ) : '';
#		require_js($config_js);
		$body .= $config_js;

		$content_id = $extra['id'] ? $extra['id'] : 'content_editable';
		$hidden_id = $params['hidden_id'] ? $params['hidden_id'] : '';

		$js = '<script type="text/javascript">
			$(function(){
				var _content_id = "#'.$content_id.'";
				var _hidden_id = "#'.$hidden_id.'";
				$(_content_id).parents("form").submit(function(){
					$("input[type=hidden]" + _hidden_id).val( $(_content_id).html() );
				})
			})
			</script>';
#		require_js($js);
		$body .= $js;

		// Avoid including ckeditor scripts several times on same page
		$__this->_ckeditor_scripts_included = true;

		return $body;
	}
}