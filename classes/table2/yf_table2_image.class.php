<?php

/**
* Table2 plugin
*/
class yf_table2_image {

	/**
	*/
	function image($name, $path, $link = '', $extra = array(), $_this) {
		if (is_array($path)) {
			$extra = (array)$extra + $path;
			$path = '';
		}
		if (is_array($link)) {
			$extra = (array)$extra + $link;
			$link = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		if (!$link) {
			$link = $extra['link'] ?: WEB_PATH. $path;
		}
		if (!isset($extra['width'])) {
			$extra['width'] = '50px';
		}
		$_this->_fields[] = array(
			'type'	=> __FUNCTION__,
			'name'	=> $name,
			'extra'	=> $extra,
			'desc'	=> $extra['desc'] ? $extra['desc'] : 'Image',
			'path'	=> $path,
			'link'	=> $link,
			'func'	=> function($field, $params, $row, $instance_params, $_this) {
				$extra = $params['extra'];
				$id = $row['id'];
				$fs_path = $extra['fs_path'] ?: PROJECT_PATH;
				$web_path = $extra['web_path'] ?: WEB_PATH;
				// Make 3-level dir path
				$d = sprintf('%09s', $id);
				$replace = array(
					'{subdir1}'	=> substr($d, 0, -6),
					'{subdir2}'	=> substr($d, -6, 3),
					'{subdir3}'	=> substr($d, -3, 3),
					'%d'		=> $id,
				);
				if (isset($extra['img_path_callback']) && is_callable($extra['img_path_callback'])) {
					$img_path = $extra['img_path_callback']($field, $params, $row, $instance_params);
				} else {
					$img_path = str_replace(array_keys($replace), array_values($replace), $params['path']);
				}
				if (!$img_path && $extra['default_image']) {
					$img_path = $extra['default_image'];
				}
				if (!$img_path) {
					return '';
				}
				if (!file_exists($fs_path. $img_path)) {
					return '';
				}
				if (isset($extra['link_callback']) && is_callable($extra['link_callback'])) {
					$link_url = $extra['link_callback']($field, $params, $row, $instance_params);
				} else {
					$link_url = str_replace(array_keys($replace), array_values($replace), $params['link']);
				}
				if (!$link_url && $img_path) {
					$link_url = $web_path. $img_path;
				}
				if ($extra['no_link']) {
					$link_url = '';
				}
				if ($link_url) {
					if (MAIN_TYPE_ADMIN && main()->ADMIN_GROUP != 1) {
						$is_link_allowed = _class('common_admin')->_admin_link_is_allowed($link_url);
						if (!$is_link_allowed) {
							$link_url = '';
						}
					}
				}
				$style = ($extra['width'] ? 'width:'.$extra['width'].';' : ''). ($extra['height'] ? 'height:'.$extra['height'].';' : ''). $extra['style'];
				return ($link_url ? '<a href="'.$link_url.'">' : '')
					.'<img src="'.$web_path. $img_path.'"'
						.($extra['class'] ? ' class="'.$extra['class'].'"' : '')
						.($extra['width'] ? ' width="'.preg_replace('~[^[0-9]%]~ims', '', $extra['width']).'"' : '')
						.($extra['height'] ? ' height="'.preg_replace('~[^[0-9]%]~ims', '', $extra['height']).'"' : '')
						.($style ? ' style="'.$style.'"' : '')
					.'">'
					.($link_url ? '</a>' : '');
			}
		);
		return $_this;
	}
}
