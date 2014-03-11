<?php

/**
* Twitter bootstrap v2.3 html5 abstraction driver
*/
class yf_html5_framework_bs2 {

	/**
	*/
	function form_render_out ($content, $extra = array(), $replace = array(), $obj) {
		return $content;
	}

	/**
	*/
	function form_row ($content, $extra = array(), $replace = array(), $obj) {
		$css_group = '';
		if (isset($extra['errors'][$extra['name']])) { $css_group = 'error'; }
		if (isset($extra['success'][$extra['name']])) { $css_group = 'success'; }
		if (isset($extra['warnings'][$extra['name']])) { $css_group = 'warning'; }
		if (isset($extra['infos'][$extra['name']])) { $css_group = 'info'; }
		$no_label = false;
		if (isset($obj->_params['no_label'])) {
			$no_label = $obj->_params['no_label'];
		}
		if (isset($extra['no_label'])) {
			$no_label = $extra['no_label'];
		}
		$row_start = 
			'<div class="control-group form-group'. ($css_group ? ' '.$css_group : '').'">'.PHP_EOL
				.($extra['desc'] && !$no_label ? '<label class="control-label col-lg-4" for="'.$extra['id'].'">'.t($extra['desc']).'</label>'.PHP_EOL : '')
				.(!$extra['wide'] ? '<div class="controls'.($extra['desc'] && !$no_label ? ' col-lg-8' : ''/*' col-lg-offset-4'*/).'">'.PHP_EOL : '');

		$row_end =
				(!$extra['wide'] ? '</div>'.PHP_EOL : '')
			.'</div>';

		$before_content_html = 
			(($extra['prepend'] || $extra['append']) ? '<div class="input-group '.($extra['prepend'] ? 'input-prepend' : '').($extra['append'] ? ' input-append' : '').'">'.PHP_EOL : '')
			.($extra['prepend'] ? '<span class="add-on input-group-addon">'.$extra['prepend'].'</span>'.PHP_EOL : '');

		$after_content_html = 
			($extra['append'] ? '<span class="add-on input-group-addon">'.$extra['append'].'</span>'.PHP_EOL : '')
			.(($extra['prepend'] || $extra['append']) ? '</div>'.PHP_EOL : '');

		if ($extra['edit_link']) {
			if (MAIN_TYPE_ADMIN && main()->ADMIN_GROUP != 1 && !_class('common_admin')->_admin_link_is_allowed($extra['edit_link'])) {
				$extra['edit_link'] = '';
			}
		}

		$edit_link_html = ($extra['edit_link'] ? ' <a href="'.$extra['edit_link'].'" class="btn btn-default btn-mini btn-xs"><i class="icon-edit"></i> '.t('Edit').'</a>'.PHP_EOL : '');
		$link_name_html = (($extra['link_url'] && $extra['link_name']) ? ' <a href="'.$extra['link_url'].'" class="btn btn-default">'.t($extra['link_name']).'</a>'.PHP_EOL : '');

		$inline_help_html = ($extra['inline_help'] ? '<span class="help-inline">'.$extra['inline_help'].'</span>'.PHP_EOL : '');
		$inline_tip_html = ($extra['tip'] ? ' '.$obj->_show_tip($extra['tip'], $extra, $replace) : '');

		if ($extra['only_row_start']) {
			return $row_start;
		} elseif ($extra['only_row_end']) {
			return $row_end;
		} elseif ($extra['stacked']) {
			return $before_content_html. $content. PHP_EOL. $after_content_html
				.$edit_link_html. $link_name_html. $inline_help_html. $inline_tip_html;
		} else {
			// Full variant
			return $row_start
					.$before_content_html. $content. PHP_EOL. $after_content_html
					.$edit_link_html. $link_name_html. $inline_help_html. $inline_tip_html
					.(isset($extra['ace_editor']) ? $obj->_ace_editor_html($extra, $replace) : '')
					.(isset($extra['ckeditor']) ? $obj->_ckeditor_html($extra, $replace) : '')
					.(isset($extra['tinymce']) ? $obj->_tinymce_html($extra, $replace) : '')
				.$row_end;
		}
	}

	/**
	* Generate form row using dl>dt,dd html tags. Useful for user profle and other simple table-like content
	*/
	function form_dd_row($content, $extra = array(), $replace = array(), $obj) {
		$dd_class = $obj->_params['dd_class'] ?: 'span6';

		$row_start = !$extra['wide'] ? '<dl class="dl-horizontal">'.PHP_EOL.'<dt>'.t($extra['desc']).'</dt>'.PHP_EOL : '';
		$content = '<dd>'.$content.'</dd>'.PHP_EOL;
		$row_end = '</dl>'.PHP_EOL;

		if ($extra['edit_link']) {
			if (MAIN_TYPE_ADMIN && main()->ADMIN_GROUP != 1 && !_class('common_admin')->_admin_link_is_allowed($extra['edit_link'])) {
				$extra['edit_link'] = '';
			}
		}
		$edit_link_html = ($extra['edit_link'] ? ' <a href="'.$extra['edit_link'].'" class="btn btn-default btn-mini btn-xs"><i class="icon-edit"></i> '.t('Edit').'</a>'.PHP_EOL : '');

		if ($extra['only_row_start']) {
			return $row_start;
		} elseif ($extra['only_row_end']) {
			return $row_end;
		} elseif ($extra['stacked']) {
			return $before_content_html. $content. PHP_EOL. $after_content_html
				.$edit_link_html. $link_name_html. $inline_help_html. $inline_tip_html;
		} else {
			// Full variant
			return $row_start
					.$before_content_html. $content. PHP_EOL. $after_content_html
					.$edit_link_html. $link_name_html. $inline_help_html. $inline_tip_html
					.(isset($extra['ace_editor']) ? $obj->_ace_editor_html($extra, $replace) : '')
					.(isset($extra['ckeditor']) ? $obj->_ckeditor_html($extra, $replace) : '')
					.(isset($extra['tinymce']) ? $obj->_tinymce_html($extra, $replace) : '')
				.$row_end;
		}
	}
}