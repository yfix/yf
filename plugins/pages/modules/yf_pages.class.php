<?php

/**
*/
class yf_pages {

	/** @var string @conf_skip */
	public $PAGE_NAME			= null;
	/** @var string @conf_skip */
	public $PAGE_TITLE			= null;

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		$try_page = $this->_get_page_from_db($name);
		if ($try_page) {
			$_GET['id'] = $name;
			return $this->show();
		}
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* Catch _ANY_ call to the class methods (yf special hook)
	*/
	function _module_action_handler ($called_action) {
		$public_methods = array();
		foreach (get_class_methods($this) as $m) {
			if ($m[0] == '_') {
				continue;
			}
			$public_methods[$m] = $m;
		}
		if (isset($public_methods[$called_action])) {
			return $this->$called_action();
		} else {
			$try_page = $this->_get_page_from_db($called_action);
			if ($try_page) {
				$_GET['id'] = $called_action;
				return $this->show();
			}
		}
		return _e('Page not found');
	}

	/**
	* Get page from database
	*/
	function _get_page_from_db ($id = null) {
		if (!$id) {
			$id = $_GET['id'];
		}
		return db()->get('SELECT * FROM '.db('pages').' WHERE (name="'._es(_strtolower(urldecode($id))).'" OR id='.intval($id).') AND active = 1 LIMIT 1');
	}

	/**
	* List all available active pages
	*/
	function all () {
/*
		return table('SELECT * FROM '.db('pages').' WHERE active = 1', array('no_header' => 1))
			->text('title', array('link' => './?object=pages&id=%d', 'link_field_name' => 'name', 'link_trim_width' => 200));
*/
		$data = db()->get_all('SELECT * FROM '.db('pages').' WHERE active = 1');
		foreach ((array)$data as $v) {
			$a[$v['id']] = array(
				'link'	=> './?object=pages&id='.$v['name'],
				'head'	=> $v['title'],
#				'body'	=> _substr($v['text'], 0, 400),
				'date'	=> $v['date_modified'],
			);
		}
		return _class('html')->media_objects($a);
	}

	/**
	* Display page contents
	*/
	function show () {
		if (empty($_GET['id'])) {
			$_GET['action'] = 'all';
			return $this->all();
		} else {
			$a = db()->get('SELECT * FROM '.db('pages').' WHERE name="'._es(_strtolower(urldecode($_GET['id']))).'" OR id='.intval($_GET['id']));
			$this->_set_global_info($a);
			if (empty($a)) {
				return _e('Page not found');
			}
		}
		$body = stripslashes($a['text']);
		$r = array();
		return form($r, array('no_form' => 1))
			->container('<h1>'._prepare_html($a['title']).'</h1>')
			->container($body, array('wide'	=> 1))
		;
	}

	/**
	* Set page infor for global use
	*/
	function _set_global_info ($a = array()) {
		$this->PAGE_NAME	= _prepare_html($a['name']);
		$this->PAGE_HEADING	= _prepare_html(_ucfirst($a['heading']));
		$this->PAGE_TITLE	= _prepare_html(_ucfirst($a['title'] ? $a['title'] : $a['page_title']));
		conf('meta_keywords', _prepare_html($a['meta_keywords']));
		conf('meta_description', _prepare_html($a['meta_desc']));
	}

	/**
	* Hook for the site_map
	*/
	function _site_map_items ($OBJ = false) {
		if (!is_object($OBJ)) {
			return false;
		}
		$Q = db()->query('SELECT * FROM '.db('pages')." WHERE active='1'". ($this->MULTILANG_MODE ? " AND locale='"._es(conf('language'))."'" : ""));
		while ($A = db()->fetch_assoc($Q)) {
			$OBJ->_store_item(array(
				'url'	=> './?object=pages&action=show&id='.$A['id'],
			));
		}
		return true;
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = $this->PAGE_HEADING ? $this->PAGE_HEADING : $this->PAGE_NAME;
		$subheader = '';
		$cases = array (
			//$_GET['action'] => {string to replace}
			'show'	=> '',
		);
		if (isset($cases[$_GET['action']])) {
			$subheader = $cases[$_GET['action']];
		}
		return array(
			'header'	=> $pheader,
			'subheader'	=> $subheader ? _prepare_html($subheader) : '',
		);
	}

	/**
	* Title hook
	*/
	function _site_title($title) {
		$subtitle = '';
		$subtitle = $this->PAGE_TITLE ? $this->PAGE_TITLE : $this->PAGE_NAME;
		if ($subtitle) {
			$title .= ' : '.t($subtitle);
		}
		return $title;
	}

	/**
	* Hook for navigation bar
	*/
	function _nav_bar_items ($params = array()) {
		$NAV_BAR_OBJ = &$params['nav_bar_obj'];
		if (!is_object($NAV_BAR_OBJ)) {
			return false;
		}
		$old_items = $params['items'];
		$items = array();
		$items[] = $NAV_BAR_OBJ->_nav_item('Home', './');
		$subtitle = $this->PAGE_TITLE ? $this->PAGE_TITLE : $this->PAGE_NAME;
		if (strlen($subtitle)) {
			$items[] = $NAV_BAR_OBJ->_nav_item($subtitle);
		}
		return $items;
	}
}
