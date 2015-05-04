<?php

class yf_form2_tbl_funcs {

	/**
	* For use inside table item template
	*/
	function tbl_link($name, $link, $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
		}
		$extra['name'] = $extra['name'] ?: $name;
		$extra['link'] = $extra['link'] ?: $link;
		$func = function($extra, $r, $form) {
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
			$icon = $extra['icon'] ? $extra['icon'] : _class('table2')->CLASS_ICON_BTN;
			$extra['href'] = $link_url;
			$extra['class'] = $extra['class'] ?: $form->CLASS_BTN_MINI. ($extra['class_add'] ? ' '.$extra['class_add'] : '');
			$attrs_names = array('id','name','href','class','style','target','alt','title');
			return ' <a'._attrs($extra, $attrs_names).'><i class="'.$icon.'"></i>'. (!$extra['hide_text'] ? ' '.t($extra['name']) : '').'</a> ';
		};
		if ($form->_chained_mode) {
			$form->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $form;
		}
		return $func((array)$extra + (array)$form->_extra, (array)$replace + (array)$form->_replace, $form);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_add($name = '', $link = '', $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
		}
		if (!$name) {
			$name = 'Add';
		}
		$extra['link_variants'] = array('add_link','add_url');
		if (!isset($extra['icon'])) {
			$extra['icon'] = _class('table2')->CLASS_ICON_ADD;
		}
		return $form->tbl_link($name, $link, $extra, $replace);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_edit($name = '', $link = '', $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
		}
		if (!$name) {
			$name = 'Edit';
		}
		$extra['link_variants'] = array('edit_link','edit_url');
		if (!isset($extra['icon'])) {
			$extra['icon'] = _class('table2')->CLASS_ICON_EDIT;
		}
		if (!isset($extra['class_add']) && !$extra['no_ajax']) {
			$extra['class_add'] = _class('table2')->CLASS_AJAX_EDIT;
		}
		return $form->tbl_link($name, $link, $extra, $replace);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_delete($name = '', $link = '', $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
		}
		if (!$name) {
			$name = 'Delete';
		}
		$extra['link_variants'] = array('delete_link','delete_url');
		if (!isset($extra['icon'])) {
			$extra['icon'] = _class('table2')->CLASS_ICON_DELETE;
		}
		if (!isset($extra['class_add']) && !$extra['no_ajax']) {
			$extra['class_add'] = _class('table2')->CLASS_AJAX_DELETE;
		}
		return $form->tbl_link($name, $link, $extra, $replace);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_clone($name = '', $link = '', $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
		}
		if (!$name) {
			$name = 'Clone';
		}
		$extra['link_variants'] = array('clone_link','clone_url');
		if (!isset($extra['icon'])) {
			$extra['icon'] = _class('table2')->CLASS_ICON_CLONE;
		}
		if (!isset($extra['class_add']) && !$extra['no_ajax']) {
			$extra['class_add'] = _class('table2')->CLASS_AJAX_CLONE;
		}
		return $form->tbl_link($name, $link, $extra, $replace);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_view($name = '', $link = '', $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
		}
		if (!$name) {
			$name = 'View';
		}
		$extra['link_variants'] = array('view_link','view_url');
		if (!isset($extra['icon'])) {
			$extra['icon'] = _class('table2')->CLASS_ICON_VIEW;
		}
		if (!isset($extra['class_add']) && !$extra['no_ajax']) {
			$extra['class_add'] = _class('table2')->CLASS_AJAX_VIEW;
		}
		return $form->tbl_link($name, $link, $extra, $replace);
	}

	/**
	* For use inside table item template
	*/
	function tbl_link_active($name = '', $link = '', $extra = array(), $replace = array(), $form) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
		}
		$extra['name'] = $extra['name'] ?: ($name ?: 'active');
		$extra['link'] = $extra['link'] ?: $link;
		$extra['desc'] = $form->_prepare_desc($extra, $desc);
		$func = function($extra, $r, $form) {
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
				if (!isset($form->_pair_active_buttons)) {
					$form->_pair_active_buttons = main()->get_data('pair_active_buttons');
				}
				$extra['items'] = $form->_pair_active_buttons;
			}
			return ' <a href="'.$link_url.'" class="'._class('table2')->CLASS_CHANGE_ACTIVE.'">'.$extra['items'][$is_active].'</a> ';
		};
		if ($form->_chained_mode) {
			$form->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $form;
		}
		return $func((array)$extra + (array)$form->_extra, (array)$replace + (array)$form->_replace, $form);
	}
}
