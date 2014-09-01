<?php

/**
* Twitter bootstrap v2.3 html5 abstraction driver
*/
class yf_html5_framework_bs2 {

	public $def_class = array(
		'form_group'	=> 'control-group form-group',
		'label'			=> 'control-label col-lg-4',
		'controls'		=> 'controls',
		'desc'			=> 'col-lg-8',
	);

	/**
	*/
	function form_render_out ($content, $extra = array(), $replace = array(), $obj) {
		return $content;
	}

	/**
	*/
	function form_row ($content, $extra = array(), $replace = array(), $obj) {
		$name = $extra['name'];
		$is_html_array = (false !== strpos($name, '['));
		if ($is_html_array) {
			$name_dotted = str_replace(array('[',']'), array('.',''), trim($name,']['));
		}
		$no_label = false;
		if (isset($obj->_params['no_label'])) {
			$no_label = $obj->_params['no_label'];
		}
		if (isset($extra['no_label'])) {
			$no_label = $extra['no_label'];
		}
		$_css_group_map = array(
			'errors'	=> 'error',
			'success'	=> 'success',
			'warnings'	=> 'warning',
			'infos'		=> 'info',
		);
		foreach ($_css_group_map as $_a => $_css_class) {
			if (isset($extra[$_a][$name]) || ($is_html_array && isset($extra[$_a][$name_dotted]))) {
				$extra['class_add_form_group'] .= ' '.$_css_class;
				break;
			}
		}
		$class_form_group = $extra['class_form_group'] ?: $this->def_class['form_group']. ($extra['class_add_form_group'] ? ' '.$extra['class_add_form_group'] : '');
		if ($extra['class_add_wrapper']) {
			$class_form_group .= ' '.$extra['class_add_wrapper'];
		}
		$class_label = $extra['class_label'] ?: $this->def_class['label']. ($extra['class_add_label'] ? ' '.$extra['class_add_label'] : '');
		$class_controls = $extra['class_controls'] ?: $this->def_class['controls']. ($extra['desc'] && !$no_label ? ' '.$this->def_class['desc'] : ''). ($extra['class_add_controls'] ? ' '.$extra['class_add_controls'] : '');

		$row_start =
			'<div class="'.$class_form_group.'">'.PHP_EOL
				.($extra['desc'] && !$no_label ? '<label class="'.$class_label.'" for="'.$extra['id'].'">'.t($extra['desc']).'</label>'.PHP_EOL : '')
				.(!$extra['wide'] ? '<div class="'.$class_controls.'">'.PHP_EOL : '');

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

		$inline_help_before = ($extra['help_before'] ? '<span class="help-block">'.nl2br($extra['help_before']).'</span>'.PHP_EOL : '');
		$inline_help_after = ($extra['inline_help'] ? '<span class="help-block">'.nl2br($extra['inline_help']).'</span>'.PHP_EOL : '');
		$inline_tip_html = ($extra['tip'] ? ' '.$obj->_show_tip($extra['tip'], $extra, $replace) : '');

		if ($extra['only_row_start']) {
			return $row_start;
		} elseif ($extra['only_row_end']) {
			return $row_end;
		} elseif ($extra['stacked']) {
			return $inline_help_before. $before_content_html. $content. PHP_EOL. $after_content_html
				.$edit_link_html. $link_name_html. $inline_tip_html. $inline_help_after;
		} else {
			// Full variant
			return $row_start
					.$inline_help_before. $before_content_html. $content. PHP_EOL. $after_content_html
					.$edit_link_html. $link_name_html. $inline_tip_html. $inline_help_after
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

		$class_wrapper = $extra['class_wrapper'] ?: 'dl-horizontal';
		if ($extra['class_add_wrapper']) {
			$class_wrapper .= ' '.$extra['class_add_wrapper'];
		}
		$class_dd = $extra['class_dd'] ?: '';
		if ($extra['class_add_dd']) {
			$class_dd .= ' '.$extra['class_add_dd'];
		}
		$row_start = !$extra['wide'] ? '<dl class="'.$class_wrapper.'">'.PHP_EOL.'<dt>'.t($extra['desc']).'</dt>'.PHP_EOL : '';
		$before_content_html = '<dd'.($class_dd ? ' class="'.$class_dd.'"' : '').'>';
		$after_content_html = '</dd>';
		$row_end = '</dl>'.PHP_EOL;

		if ($extra['edit_link']) {
			if (MAIN_TYPE_ADMIN && main()->ADMIN_GROUP != 1 && !_class('common_admin')->_admin_link_is_allowed($extra['edit_link'])) {
				$extra['edit_link'] = '';
			}
		}
		$edit_link_html = ($extra['edit_link'] ? ' <a href="'.$extra['edit_link'].'" class="btn btn-default btn-mini btn-xs"><i class="icon-edit"></i> '.t('Edit').'</a>'.PHP_EOL : '');
		$link_name_html = (($extra['link_url'] && $extra['link_name']) ? ' <a href="'.$extra['link_url'].'" class="btn btn-default">'.t($extra['link_name']).'</a>'.PHP_EOL : '');

#		$inline_help_before = ($extra['help_before'] ? '<span class="help-block">'.nl2br($extra['help_before']).'</span>'.PHP_EOL : '');
#		$inline_help_after = ($extra['inline_help'] ? '<span class="help-block">'.nl2br($extra['inline_help']).'</span>'.PHP_EOL : '');
		$inline_tip_html = ($extra['tip'] ? ' '.$obj->_show_tip($extra['tip'], $extra, $replace) : '');

		if ($extra['only_row_start']) {
			return $row_start . $before_content_html;
		} elseif ($extra['only_row_end']) {
			return $after_content_html . $row_end;
		} elseif ($extra['stacked']) {
			return $inline_help_before. $content. PHP_EOL
				.$edit_link_html. $link_name_html. $inline_tip_html. $inline_help_after;
		} else {
			// Full variant
			return $row_start
					.$before_content_html. $inline_help_before. $content. PHP_EOL
					.$edit_link_html. $link_name_html. $inline_tip_html. $inline_help_after. $after_content_html
					.(isset($extra['ace_editor']) ? $obj->_ace_editor_html($extra, $replace) : '')
					.(isset($extra['ckeditor']) ? $obj->_ckeditor_html($extra, $replace) : '')
					.(isset($extra['tinymce']) ? $obj->_tinymce_html($extra, $replace) : '')
				.$row_end;
		}
	}
}
