<?php

/**
* Static pages display module
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_static_pages {

	const table = 'static_pages';
	/** @var string @conf_skip */
	public $PAGE_NAME			= null;
	/** @var string @conf_skip */
	public $PAGE_TITLE			= null;
	/** @var bool Allow HTML in text */
	public $ALLOW_HTML_IN_TEXT	= true;
	/** @var bool */
	public $MULTILANG_MODE		= false;

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	*/
	function _module_action_handler($name) {
		if (method_exists($this, $name) && substr($name, 0, 1) !== '_') {
			return $this->$name();
		} else {
			$_GET['action'] = 'show';
			$_GET['id'] = $name;
			return $this->show();
		}
	}

	/**
	* Display page contents
	*/
	function show () {
		$a = $this->_get_page_from_db();
		if (!$a) {
			return _404();
		}
		$this->_set_global_info($a);
		// Get sub-pages (from menu)
		$sub_pages = array();
		$menus = main()->get_data('menus');

		$cur_menu_id = 0;
		// Find first user menu
		if (!$cur_menu_id) {
			foreach ((array)$menus as $_info) {
				if ($_info['type'] == 'user' && $_info['active']) {
					$cur_menu_id = $_info['id'];
					break;
				}
			}
		}
		$cur_menu_item_id = 0;
		if ($cur_menu_id) {
			$menu_items = main()->get_data('menu_items');
			foreach ((array)$menu_items[$cur_menu_id] as $item_info) {
				if (!$item_info['active'] || $item_info['parent_id']) {
					continue;
				}
				if ($item_info['location'] == 'object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id']) {
					$cur_menu_item_id = $item_info['parent_id'] ? $item_info['parent_id'] : $item_info['id'];
					break;
				}
			}
		}
		if ($cur_menu_id && $cur_menu_item_id) {
			foreach ((array)$menu_items[$cur_menu_id] as $item_info) {
				if (!$item_info['active'] || $item_info['parent_id'] != $cur_menu_item_id) {
					continue;
				}
				$sub_pages[$item_info['id']] = array(
					'name'	=> _prepare_html($item_info['name']),
					'link'	=> process_url('./?'.$item_info['location']),
				);
			}
		}
		$content = tpl()->parse_string(stripslashes($a['text']), array(), 'static_page__'.$a['id']);
		// Process template
		$replace = array(
			'id'				=> intval($a['id']),
			'name'				=> stripslashes($a['name']),
//			'content'			=> stripslashes($a['text']), // DO NOT ADD _prepare_html here!
			'content'			=> $content,
			'page_heading'		=> _prepare_html(_ucfirst($a['page_heading'])),
			'page_page_title'	=> _prepare_html(_ucfirst($a['page_title'])),
			'print_link'		=> './?object='.$_GET['object'].'&action=print_view&id='.$a['id'],
			'pdf_link'			=> './?object='.$_GET['object'].'&action=pdf_view&id='.$a['id'],
			'email_link'		=> './?object='.$_GET['object'].'&action=email_page&id='.$a['id'],
			'sub_pages'			=> $sub_pages,
		);
		return tpl()->parse($_GET['object'].'/main', $replace);
	}

	/**
	*/
	function _get_page_from_db ($id = null) {
		$id = $id ?: $_GET['id'];
var_dump($id);
		if (empty($id)) {
			return array();
		}
		$q = db()->from(self::table)->where('active', '1');
		if (is_numeric($id)) {
			$q->where('id', (int)$id);
		} else {
			$q->where('name', _strtolower($id));
		}
		if ($this->MULTILANG_MODE) {
			$q->where('locale', conf('language'));
		}
		return $q->get();
	}

	/**
	* Print View
	*/
	function print_view () {
		$a = $this->_get_page_from_db();
		if (!$a) {
			return _404();
		}
		$this->_set_global_info($a);
		$text = $this->ALLOW_HTML_IN_TEXT ? $a['text'] : _prepare_html($a['text']);
		return common()->pdf_page($text, 'page_'.$a['name']);
	}

	/**
	* Pdf View
	*/
	function pdf_view () {
		$a = $this->_get_page_from_db();
		if (!$a) {
			return _404();
		}
		$this->_set_global_info($a);
		$text = $this->ALLOW_HTML_IN_TEXT ? $a['text'] : _prepare_html($a['text']);
		return common()->pdf_page($text, 'page_'.$a['name']);
	}

	/**
	* Email Page
	*/
	function email_page () {
		$a = $this->_get_page_from_db();
		$this->_set_global_info($a);
		// Show error message
		if (empty($a)) {
			_re('No such page!');
			$body = _e();
		} else {
			$body = common()->email_page($a['text']);
		}
		return $body;
	}

	/**
	* Rss Page
	*/
	function rss_page () {
		$data	= array();
		$params = array();
		$body = common()->rss_page($data, $params);
		return $body;
	}

	/**
	* Display RSS channels contents
	*/
	function get_rss_page () {
		$params = array();
		$body = common()->fetch_rss($params);
		return $body;
	}

	/**
	* Set page infor for global use
	*/
	function _set_global_info ($a = array()) {
		$this->PAGE_NAME	= _prepare_html($a['name']);
		$this->PAGE_HEADING	= _prepare_html(_ucfirst($a['page_heading']));
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
		$Q = db()->query('SELECT * FROM '.db(self::table)." WHERE active='1'". ($this->MULTILANG_MODE ? " AND locale='"._es(conf('language'))."'" : ""));
		while ($A = db()->fetch_assoc($Q)) {
			$OBJ->_store_item(array(
				'url'	=> './?object=static_pages&action=show&id='.$A['id'],
			));
		}
		return true;
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = $this->PAGE_HEADING ? $this->PAGE_HEADING : $this->PAGE_NAME;
		// Default subheader get from action name
		$subheader = '';
		// Array of replacements
		$cases = array (
			//$_GET['action'] => {string to replace}
			'show'	=> '',
		);
		if (isset($cases[$_GET['action']])) {
			// Rewrite default subheader
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
		$subtitle = $this->PAGE_TITLE ? $this->PAGE_TITLE : $this->PAGE_NAME;
		// Save old items
		$old_items = $params['items'];
		// Create new items
		$items = array();
#		$items[]	= $NAV_BAR_OBJ->_nav_item('Home', './');
		$items[]	= $NAV_BAR_OBJ->_nav_item($subtitle);
		return $items;
	}
}
