<?php

/**
*/
class yf_form2_boxes {

	/**
	*/
	function country_box($name = '', $desc = '', $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'country';
		}
		$data = array();
		$row_tpl = $extra['row_tpl'] ?: '%icon %name %code';
		foreach ((array)main()->get_data('geo_countries') as $v) {
			$r = array(
				'%icon'	=> '<i class="bfh-flag-'.strtoupper($v['code']).'"></i>',
				'%name'	=> $v['name'],
				'%code'	=> '['.strtoupper($v['code']).']',
			);
			$data[$v['code']] = str_replace(array_keys($r), array_values($r), $row_tpl);
		}
		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = './?object=manage_countries';
		}
		$renderer = $extra['renderer'] ?: 'list_box';
		return $form->$renderer($name, $data, $extra, $replace);
	}

	/**
	*/
	function region_box($name = '', $desc = '', $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'region';
		}
		$extra['country'] = $extra['country'] ?: 'UA';
		$data = array();
		$row_tpl = $extra['row_tpl'] ?: '%name';
		foreach ((array)main()->get_data('geo_regions', 0, array('country' => $extra['country'])) as $v) {
			$r = array(
				'%name'	=> $v['name'],
			);
			$data[$v['code']] = str_replace(array_keys($r), array_values($r), $row_tpl);
		}
		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = './?object=manage_regions';
		}
		$renderer = $extra['renderer'] ?: 'list_box';
		return $form->$renderer($name, $data, $extra, $replace);
	}

	/**
	*/
	function city_box($name = '', $desc = '', $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'city';
		}
		$extra['country'] = $extra['country'] ?: 'UA';
		$data = array();
		$row_tpl = $extra['row_tpl'] ?: '%name';
		foreach ((array)main()->get_data('geo_regions', 0, array('country' => $extra['country'])) as $v) {
			$data[$v['name']] = array();
			$region_names[$v['id']] = $v['name'];
		}
		foreach ((array)main()->get_data('geo_cities', 0, array('country' => $extra['country'])) as $v) {
			$region_name = $region_names[$v['region_id']];
			if (!$region_name) {
				continue;
			}
			$r = array(
				'%name'	=> $v['name'],
			);
			$data[$region_name][$v['id']] = str_replace(array_keys($r), array_values($r), $row_tpl);
		}
		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = './?object=manage_cities';
		}
		$renderer = $extra['renderer'] ?: 'select2_box';
		return $form->$renderer($name, $data, $extra, $replace);
	}

	/**
	*/
	function currency_box($name = '', $desc = '', $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'currency';
		}
		$data = array();
		$row_tpl = $extra['row_tpl'] ?: '%sign &nbsp; %name %code';
		foreach ((array)main()->get_data('currencies') as $v) {
			$r = array(
				'%sign'	=> $v['sign'],
				'%name'	=> $v['name'],
				'%code'	=> '['.$v['id'].']',
			);
			$data[$v['id']] = str_replace(array_keys($r), array_values($r), $row_tpl);
		}
		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = './?object=manage_currencies';
		}
		$renderer = $extra['renderer'] ?: 'list_box';
		return $form->$renderer($name, $data, $extra, $replace);
	}

	/**
	*/
	function language_box($name = '', $desc = '', $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'language';
		}
		$data = array();
		$row_tpl = $extra['row_tpl'] ?: '%icon %name %code';
		foreach ((array)main()->get_data('languages_new') as $v) {
			$r = array(
				'%icon'	=> ($v['country'] ? '<i class="bfh-flag-'.strtoupper($v['country']).'"></i> ' : ''),
				'%name'	=> $v['native'],
				'%code'	=> '['.$v['code'].']',
			);
			$data[$v['code']] = str_replace(array_keys($r), array_values($r), $row_tpl);
		}
		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = './?object=manage_languages';
		}
		$renderer = $extra['renderer'] ?: 'list_box';
		return $form->$renderer($name, $data, $extra, $replace);
	}

	/**
	*/
	function timezone_box($name = '', $desc = '', $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'timezone';
		}
		$data = array();
		$row_tpl = $extra['row_tpl'] ?: '<small>%offset %code %name</small>';
		foreach ((array)main()->get_data('timezones') as $v) {
			$r = array(
				'%offset'	=> $v['offset'],
				'%name'		=> $v['name'],
				'%code'		=> '['.$v['code'].']',
			);
			$data[$v['code']] = str_replace(array_keys($r), array_values($r), $row_tpl);
		}
		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = './?object=manage_timezones';
		}
		$renderer = $extra['renderer'] ?: 'list_box';
		return $form->$renderer($name, $data, $extra, $replace);
	}

	/**
	*/
	function icon_select_box($name = '', $desc = '', $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'icon';
		}
		$data = array();
		$row_tpl = $extra['row_tpl'] ?: '%icon %name';
		foreach ((array)main()->get_data('fontawesome_icons') as $icon) {
			$r = array(
				'%icon'	=> '<i class="icon '.$icon.'"></i> ',
				'%name'	=> $icon,
			);
			$data[$icon] = str_replace(array_keys($r), array_values($r), $row_tpl);
		}
		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = './?object=manage_icons';
		}
		$renderer = $extra['renderer'] ?: 'list_box';
		return $form->$renderer($name, $data, $extra, $replace);
	}

	/**
	*/
	function method_select_box($name = '', $desc = '', $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'method';
		}
		$data = array();
		if ($extra['for_type'] == 'admin') {
			$data = _class('admin_modules', 'admin_modules/')->_get_methods_for_select();
		} else {
			$data = _class('user_modules', 'admin_modules/')->_get_methods_for_select();
		}
		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = $extra['for_type'] == 'admin' ? './?object=admin_modules' : './?object=user_modules';
		}
		$renderer = $extra['renderer'] ?: 'list_box';
		return $form->$renderer($name, $data, $extra, $replace);
	}

	/**
	*/
	function template_select_box($name = '', $desc = '', $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'template';
		}
		$data = array();
		if ($extra['for_type'] == 'admin') {
			$data = _class('template_editor', 'admin_modules/')->_get_stpls_for_type('admin');
		} else {
			$data = _class('template_editor', 'admin_modules/')->_get_stpls_for_type('user');
		}
		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = $extra['for_type'] == 'admin' ? './?object=template_editor' : './?object=template_editor';
		}
		$renderer = $extra['renderer'] ?: 'list_box';
		return $form->$renderer($name, $data, $extra, $replace);
	}

	/**
	*/
	function location_select_box($name = '', $desc = '', $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = '';
		}
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		if (!$name) {
			$name = 'location';
		}
// TODO
		return $form->text($name, $data, $extra, $replace);
/*
		$data = array();
		if ($extra['for_type'] == 'admin') {
		} else {
		}

		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = $extra['for_type'] == 'admin' ? './?object=blocks' : './?object=blocks';
		}
		$renderer = $extra['renderer'] ?: 'list_box';
		return $form->$renderer($name, $data, $extra, $replace);
*/
	}
}
