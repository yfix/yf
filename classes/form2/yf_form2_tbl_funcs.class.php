<?php

class yf_form2_tbl_funcs {

	/**
	* For use inside table item template
	*/
	function tbl_link($name, $link, $extra = array(), $replace = array(), $__this) {
		$extra['name'] = $extra['name'] ?: $name;
		$extra['link'] = $extra['link'] ?: $link;
		$func = function($extra, $r, $_this) {
			$link = $extra['link'];
			if (!$link && $extra['link_variants']) {
				foreach((array)$extra['link_variants'] as $link_variant) {
					if (isset($r[$link_variant])) {
						$link = $link_variant;
					}
				}
			}
			$link_url = isset($r[$link]) ? $r[$link] : $link;
			if ($link_url) {
				if (MAIN_TYPE_ADMIN && main()->ADMIN_GROUP != 1 && !_class('common_admin')->_admin_link_is_allowed($link_url)) {
					return '';
				}
			}
			if ($extra['rewrite']) {
				$link_url = url($link_url);
			}
			$icon = $extra['icon'] ? $extra['icon']: 'icon-tasks';
			$extra['href'] = $link_url;
			$extra['class'] = $extra['class'] ?: 'btn btn-default btn-mini btn-xs'. ($extra['class_add'] ? ' '.$extra['class_add'] : '');
			$attrs_names = array('id','name','href','class','style','target','alt','title');
			return ' <a'._attrs($extra, $attrs_names).'><i class="'.$icon.'"></i> '.t($extra['name']).'</a> ';
		};
		if ($__this->_chained_mode) {
			$__this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $__this;
		}
		return $func((array)$extra + (array)$__this->_extra, (array)$replace + (array)$__this->_replace, $__this);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_edit($name = '', $link = '', $extra = array(), $replace = array(), $__this) {
		if (!$name) {
			$name = 'Edit';
		}
		$extra['link_variants'] = array('edit_link','edit_url');
		if (!isset($extra['icon'])) {
			$extra['icon'] = 'icon-edit';
		}
		if (!isset($extra['class_add'])) {
			$extra['class_add'] = 'ajax_edit';
		}
		return $__this->tbl_link($name, $link, $extra, $replace);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_delete($name = '', $link = '', $extra = array(), $replace = array(), $__this) {
		if (!$name) {
			$name = 'Delete';
		}
		$extra['link_variants'] = array('delete_link','delete_url');
		if (!isset($extra['icon'])) {
			$extra['icon'] = 'icon-trash';
		}
		if (!isset($extra['class_add'])) {
			$extra['class_add'] = 'ajax_delete btn-danger';
		}
		return $__this->tbl_link($name, $link, $extra, $replace);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_clone($name = '', $link = '', $extra = array(), $replace = array(), $__this) {
		if (!$name) {
			$name = 'Clone';
		}
		$extra['link_variants'] = array('clone_link','clone_url');
		if (!isset($extra['icon'])) {
			$extra['icon'] = 'icon-plus';
		}
		if (!isset($extra['class_add'])) {
			$extra['class_add'] = 'ajax_clone';
		}
		return $__this->tbl_link($name, $link, $extra, $replace);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_view($name = '', $link = '', $extra = array(), $replace = array(), $__this) {
		if (!$name) {
			$name = 'View';
		}
		$extra['link_variants'] = array('view_link','view_url');
		if (!isset($extra['icon'])) {
			$extra['icon'] = 'icon-eye-open';
		}
		if (!isset($extra['class_add'])) {
			$extra['class_add'] = 'ajax_view';
		}
		return $__this->tbl_link($name, $link, $extra, $replace);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_active($name = '', $link = '', $extra = array(), $replace = array(), $__this) {
		$extra['name'] = $extra['name'] ?: ($name ?: 'active');
		$extra['link'] = $extra['link'] ?: $link;
		$extra['desc'] = $__this->_prepare_desc($extra, $desc);
		$func = function($extra, $r, $_this) {
			$link = $extra['link'];
			if (!$link) {
				$link = 'active_link';
				if (!isset($r['active_link']) && isset($r['active_url'])) {
					$link = 'active_url';
				}
			}
			$link_url = isset($r[$link]) ? $r[$link] : $link;
			if ($link_url) {
				if (MAIN_TYPE_ADMIN && main()->ADMIN_GROUP != 1 && !_class('common_admin')->_admin_link_is_allowed($link_url)) {
					return '';
				}
			}
			if ($extra['rewrite']) {
				$link_url = url($link_url);
			}
			$is_active = (bool)$r[$extra['name']];
			if (!$extra['items']) {
				if (!isset($_this->_pair_active_buttons)) {
					$_this->_pair_active_buttons = main()->get_data('pair_active_buttons');
				}
				$extra['items'] = $_this->_pair_active_buttons;
			}
			return ' <a href="'.$link_url.'" class="change_active">'.$extra['items'][$is_active].'</a> ';
		};
		if ($__this->_chained_mode) {
			$__this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $__this;
		}
		return $func((array)$extra + (array)$__this->_extra, (array)$replace + (array)$__this->_replace, $__this);
	}
}