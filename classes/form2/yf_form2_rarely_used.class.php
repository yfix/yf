<?php

/**
*/
class yf_form2_rarely_used {

	/**
	*/
	function user_info($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
		$name = 'user_name';
		$user_id = $__this->_replace['user_id'];

		$user_info = db()->get('SELECT login,email,phone,nick,id AS user_name FROM '.db('user').' WHERE id='.intval($user_id));
		$user_name = array();
		if ($user_info) {
			if (strlen($user_info['id'])) {
				$user_name[] = $user_info['id'];
			}
			if (strlen($user_info['login'])) {
				$user_name[] = $user_info['login'];
			}
			if (strlen($user_info['email'])) {
				$user_name[] = $user_info['email'];
			} elseif (strlen($user_info['phone'])) {
				$user_name[] = $user_info['phone'];
			} elseif (strlen($user_info['nick'])) {
				$user_name[] = $user_info['nick'];
			}
		}
		$__this->_replace[$name] = implode('; ', $user_name);

		$extra['link'] = './?object=members&action=edit&id='.$user_id;
		return $__this->info($name, $desc, $extra, $replace);
	}

	/**
	*/
	function admin_info($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
		$name = 'admin_name';
		$user_id = $__this->_replace['user_id'];

		$user_info = db()->get('SELECT login,id AS user_name FROM '.db('admin').' WHERE id='.intval($user_id));
		$user_name = array();
		if ($user_info) {
			if (strlen($user_info['id'])) {
				$user_name[] = $user_info['id'];
			}
			if (strlen($user_info['login'])) {
				$user_name[] = $user_info['login'];
			}
		}
		$__this->_replace[$name] = implode('; ', $user_name);
		$extra['link'] = './?object=admin&action=edit&id='.$user_id;
		return $__this->info($name, $desc, $extra, $replace);
	}

	/**
	*/
	function country_box($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
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
		foreach ((array)main()->get_data('countries_new') as $v) {
			$data[$v['code']] = '<i class="bfh-flag-'.strtoupper($v['code']).'"></i> '. $v['name'].' ['.strtoupper($v['code']).']';
		}
		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = './?object=manage_countries';
		}
		return $__this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function region_box($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
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
		$data = array();
		foreach ((array)main()->get_data('regions_new') as $v) {
			$data[$v['code']] = $v['name'].' ['.$v['code'].']';
		}
		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = './?object=manage_regions';
		}
		return $__this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function city_box($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
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
		$data = array();
// TODO
		foreach ((array)main()->get_data('cities_new') as $v) {
			$data[$v['code']] = $v['name'].' ['.$v['code'].']';
		}
		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = './?object=manage_cities';
		}
		return $__this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function currency_box($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
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
		foreach ((array)main()->get_data('currencies') as $v) {
			$data[$v['id']] = $v['sign'].' &nbsp; '. $v['name'].' ['.$v['id'].']';
		}
		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = './?object=manage_currencies';
		}
		return $__this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function language_box($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
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
		foreach ((array)main()->get_data('languages_new') as $v) {
			$data[$v['code']] = ($v['country'] ? '<i class="bfh-flag-'.strtoupper($v['country']).'"></i> ' : ''). $v['native'].' ['.$v['code'].']';
		}
		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = './?object=manage_languages';
		}
		return $__this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function timezone_box($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
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
		foreach ((array)main()->get_data('timezones_new') as $v) {
			$data[$v['code']] = '<small>'.$v['offset'].' ['.$v['code'].'] '.$v['name'].'</small>';
		}
		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = './?object=manage_timezones';
		}
		return $__this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function icon_select_box($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
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
		foreach ((array)main()->get_data('fontawesome_icons') as $icon) {
			$data[$icon] = '<i class="icon '.$icon.'"></i> '.$icon;
		}
		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = './?object=manage_icons';
		}
		return $__this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function method_select_box($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
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
		return $__this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function template_select_box($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
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
		return $__this->list_box($name, $data, $extra, $replace);
	}

	/**
	*/
	function location_select_box($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
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
		return $__this->text($name, $data, $extra, $replace);
/*
		$data = array();
		if ($extra['for_type'] == 'admin') {
		} else {
		}

		if (MAIN_TYPE_ADMIN && !isset($extra['edit_link'])) {
			$extra['edit_link'] = $extra['for_type'] == 'admin' ? './?object=blocks' : './?object=blocks';
		}
		return $__this->list_box($name, $data, $extra, $replace);
*/
	}
}
