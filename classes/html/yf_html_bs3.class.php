<?php

// TODO
class yf_html_bs3 {
	function form_row ($content, $extra = array(), $replace = array(), $obj) {
		return '
			<div class="control-group form-group'.(isset($extra['errors'][$extra['name']]) ? ' error' : '').'">'.PHP_EOL
				.($extra['desc'] ? '<label class="control-label col-lg-2" for="'.$extra['id'].'">'.t($extra['desc']).'</label>'.PHP_EOL : '')
				.(!$extra['wide'] ? '<div class="controls col-lg-4">'.PHP_EOL : '')

					.(($extra['prepend'] || $extra['append']) ? '<div class="input-group '.($extra['prepend'] ? 'input-prepend' : '').($extra['append'] ? ' input-append' : '').'">'.PHP_EOL : '')
					.($extra['prepend'] ? '<span class="add-on input-group-addon">'.$extra['prepend'].'</span>'.PHP_EOL : '')

					.$content.PHP_EOL

					.($extra['append'] ? '<span class="add-on input-group-addon">'.$extra['append'].'</span>'.PHP_EOL : '')
					.(($extra['prepend'] || $extra['append']) ? '</div>'.PHP_EOL : '')

					.($extra['edit_link'] ? ' <a href="'.$extra['edit_link'].'" class="btn btn-mini btn-xs"><i class="icon-edit"></i> '.t('Edit').'</a>'.PHP_EOL : '')
					.(($extra['link_url'] && $extra['link_name']) ? ' <a href="'.$extra['link_url'].'" class="btn">'.t($extra['link_name']).'</a>'.PHP_EOL : '')

					.($extra['inline_help'] ? '<span class="help-inline">'.$extra['inline_help'].'</span>'.PHP_EOL : '')
					.($extra['tip'] ? ' '.$obj->_show_tip($extra['tip'], $extra, $replace) : '')
					.(isset($extra['ckeditor']) ? $obj->_ckeditor_html($extra, $replace) : '')

				.(!$extra['wide'] ? '</div>'.PHP_EOL : '')
			.'</div>'.PHP_EOL
		;
	}
}