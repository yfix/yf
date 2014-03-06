<?php

/**
* Core blocks methods
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_core_blocks {

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* Display main 'center' block contents
	*/
	function show_center () {
		$graphics = _class('graphics');
		if ($graphics->USE_SE_KEYWORDS) {
			$graphics->_set_se_keywords();
		}
		if ($graphics->IFRAME_CENTER) {
			if (false !== strpos($_SERVER['QUERY_STRING'], 'center_area=1')) {
				main()->NO_GRAPHICS = true;
				$replace = array(
					'css'	=> '<link rel="stylesheet" type="text/css" href="'.$graphics->MEDIA_PATH. tpl()->TPL_PATH. 'style.css">',
					'text'	=> $graphics->tasks(1),
				);
				$body = tpl()->parse('system/empty_page', $replace);
				echo module('rewrite')->_replace_links_for_iframe($body);
			} else {
				$replace = array(
					'src'	=> WEB_PATH.'?'.(strlen($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'].'&' : '').'center_area=1',
				);
				$body .= tpl()->parse('system/iframe', $replace);
			}
		} else {
			if (false !== strpos($_SERVER['QUERY_STRING'], 'center_area=1')) {
				main()->NO_GRAPHICS = true;
				$replace = array(
					'css'	=> '<link rel="stylesheet" type="text/css" href="'.$graphics->MEDIA_PATH. tpl()->TPL_PATH.'style.css">',
					'text'	=> $graphics->tasks(1),
				);
				echo tpl()->parse('system/empty_page', $replace);
			} else {
				$body = $graphics->tasks(1);
			}
		}
		return $body;
	}

	/**
	* Alias for the '_show_block'
	*/
	function show_block ($params = array()) {
		return $this->_show_block($params);
	}

	/**
	* Show custom block contents
	*/
	function _show_block ($input = array()) {
		if (!isset($this->_blocks_infos)) {
			$this->_blocks_infos = main()->get_data('blocks_all');
		}
		if (empty($this->_blocks_infos)) {
			if (!$this->_error_no_blocks_raised) {
				trigger_error(__CLASS__.': Blocks names not loaded', E_USER_WARNING);
				$this->_error_no_blocks_raised = true;
			}
			return false;
		}
		$BLOCK_EXISTS = false;
		if (isset($input['block_id']) && is_numeric($input['block_id'])) {
			$block_info = $this->_blocks_infos[$input['block_id']];
			if ($block_info && trim($block_info['type']) == MAIN_TYPE) {
				$block_id = $input['block_id'];
				$block_name = $block_info['name'];
				$BLOCK_EXISTS = true;
			}
		} else {
			$block_name = $input['name'];
			if (empty($block_name)) {
				trigger_error(__CLASS__.': Given empty block name to show', E_USER_WARNING);
				return false;
			}
			foreach ((array)$this->_blocks_infos as $block_info) {
				// Skip blocks from other init type ('admin' or 'user')
				if (trim($block_info['type']) != MAIN_TYPE) {
					continue;
				}
				// Found!
				if ($block_info['name'] == $block_name) {
					$BLOCK_EXISTS = true;
					$block_id = $block_info['id'];
					break;
				}
			}
		}
		if (!$BLOCK_EXISTS) {
			trigger_error(__CLASS__.': block "'._prepare_html($block_name).'" not found in blocks list', E_USER_WARNING);
			return false;
		}
		if (!$this->_blocks_infos[$block_id]['active']) {
			return false;
		}
		if (!$this->_check_block_rights($block_id, $_GET['object'], $_GET['action'])) {
			return _class('graphics')->_action_on_block_denied($block_name);
		}
		if (MAIN_TYPE_USER && $block_name == 'center_area' && _class('graphics')->USE_SE_KEYWORDS) {
			_class('graphics')->_set_se_keywords();
		}
		$cur_block_info = $this->_blocks_infos[$block_id];
		// 	If special object method specified - then call it
		//	Syntax: [path_to]$class_name.$method_name
		//	@example 'static_pages.show'
		//	@example 'classes/minicalendar.createcalendar'
		if (!empty($cur_block_info['method_name'])) {
			$special_path = '';
			if (false !== strpos($cur_block_info['method_name'], '/')) {
				$special_path = substr($cur_block_info['method_name'], 0, strrpos($cur_block_info['method_name'], '/') + 1);
				$cur_block_info['method_name'] = substr($cur_block_info['method_name'], strrpos($cur_block_info['method_name'], '/') + 1);
			}
			list($special_class_name, $special_method_name) = explode('.', $cur_block_info['method_name']);
			$special_params = array(
				'block_name'	=> $block_name,
				'block_id'		=> $block_id,
			);
			if (!empty($special_class_name) && !empty($special_method_name)) {
				$obj = _class_safe($special_class_name, $special_path);
				if (is_object($obj) && method_exists($obj, $special_method_name)) {
					return $obj->$special_method_name($special_params);
				} else {
					trigger_error(__CLASS__.': block "'._prepare_html($block_name).'" custom php module.method not exists: '._prepare_html($cur_block_info['method_name']), E_USER_WARNING);
					return false;
				}
			}
		}
		$stpl_name = $cur_block_info['stpl_name'] ?: $block_name;
		return tpl()->parse($stpl_name, array(
			'block_name'=> $block_name,
			'block_id'	=> $block_id,
		));
	}

	/**
	* Action to on denied block
	*/
	function _action_on_block_denied ($block_name = '') {
		if ($block_name == 'center_area') {
			if (MAIN_TYPE_USER && !main()->USER_ID) {
				$redir_params = array(
					'%%object%%'		=> $_GET['object'],
					'%%action%%'		=> $_GET['action'],
					'%%add_get_vars%%'	=> str_replace('&',';',_add_get(array('object','action'))),
				);
				$redir_url = str_replace(array_keys($redir_params), array_values($redir_params), main()->REDIR_URL_DENIED);
				if (!empty($redir_url)) {
					if ($_GET['object'] == 'login_form') {
						return 'Access to login form denied on center block (graphics->_action_on_block_denied)';
					} else {
						return js_redirect($redir_url);
					}
				}
			} elseif (MAIN_TYPE_USER && main()->USER_ID) {
				return '<div class="alert alert-error">'.t('Access denied').'</div>';
			} elseif (MAIN_TYPE_ADMIN && main()->ADMIN_ID) {
				return '<div class="alert alert-error">'.t('Access denied').'</div>';
			//} elseif (MAIN_TYPE_ADMIN && !main()->ADMIN_ID) {
			}
		}
		return false;
	}

	/**
	* Try to find id of the center block
	*/
	function _get_center_block_id() {
		if (!isset($this->_blocks_infos)) {
			$this->_blocks_infos = main()->get_data('blocks_all');
		}
		$center_block_id = 0;
		foreach ((array)$this->_blocks_infos as $cur_block_id => $cur_block_info) {
			if ($cur_block_info['type'] == MAIN_TYPE && trim($cur_block_info['name']) == 'center_area') {
				$center_block_id = $cur_block_id;
				break;
			}
		}
		return $center_block_id;
	}

	/**
	* Load array of blocks rules
	*/
	function _load_blocks_rules () {
		if (!empty($this->_blocks_rules)) {
			return false;
		}
		$rules = main()->get_data('blocks_rules');
		$rule_names_to_skip = array('id','block_id','rule_type','active','order');
		foreach ((array)$rules as $rule_id => $rule_info) {
			foreach ((array)$rule_info as $rule_name => $rule_text) {
				if (in_array($rule_name, $rule_names_to_skip) || empty($rule_text)) {
					continue;
				}
				$rule_text = trim(str_replace(array(' ',"\t","\r","\n","\"","'",',,'), '', $rule_text), ',');
				$rule_text = explode(',',$rule_text);
				$rules[$rule_id][$rule_name] = $rule_text;
			}
		}
		$this->_blocks_rules = $rules;
	}

	/**
	*/
	function _get_center_block_rules() {
		$rules = &$this->CENTER_BLOCK_RULES;
		if (isset($rules)) {
			return $rules;
		}
		$this->CENTER_BLOCK_ID = $this->_get_center_block_id();

		$rules = array();
		foreach ((array)$this->_blocks_rules as $rid => $rinfo) {
			if ($rinfo != $this->CENTER_BLOCK_ID) {
				continue;
			}
			$rules[$rid] = $rinfo;
		}

		$this->CENTER_BLOCK_RULES = $rules;
		return $rules;
	}

	/**
	* Check rights for blocks
	*/
	function _check_block_rights ($block_id = 0, $OBJECT = '', $ACTION = '') {
		if (empty($block_id) || empty($OBJECT)) {
			return false;
		}
		if (empty($ACTION)) {
			$ACTION = 'show';
		}
		$CUR_USER_GROUP = intval(MAIN_TYPE_ADMIN ? $_SESSION['admin_group'] : $_SESSION['user_group']);
		$CUR_USER_THEME	= conf('theme');
		$CUR_LOCALE		= conf('language');
		$CUR_SITE		= (int)conf('SITE_ID');
		$CUR_SERVER_ID	= (int)conf('SERVER_ID');
		$CUR_SERVER_ROLE= conf('SERVER_ROLE');
		$RESULT = false;
		if (!isset($this->_blocks_rules)) {
			$this->_load_blocks_rules();
		}
		foreach ((array)$this->_blocks_rules as $rule_id => $rule_info) {
			if ($rule_info['block_id'] != $block_id) {
				continue;
			}
			$matched_method		= false;
			$matched_user_group	= false;
			$matched_theme		= false;
			$matched_locale		= false;
			$matched_site		= false;
			$matched_server_id	= false;
			$matched_server_role= false;
			// Check matches
			if (is_array($rule_info['methods']) && (in_array($OBJECT, $rule_info['methods']) || in_array($OBJECT.'.'.$ACTION, $rule_info['methods']))) {
				$matched_method = true;
			}
			if (is_array($rule_info['user_groups']) && in_array($CUR_USER_GROUP, $rule_info['user_groups'])) {
				$matched_user_group = true;
			}
			if (is_array($rule_info['themes']) && in_array($CUR_USER_THEME, $rule_info['themes'])) {
				$matched_theme = true;
			}
			if (is_array($rule_info['locales']) && in_array($CUR_LOCALE, $rule_info['locales'])) {
				$matched_locale = true;
			}
			if (is_array($rule_info['site_ids']) && in_array($CUR_SITE, $rule_info['site_ids'])) {
				$matched_site = true;
			}
			if (is_array($rule_info['server_ids']) && in_array($CUR_SERVER_ID, $rule_info['server_ids'])) {
				$matched_server_id = true;
			}
			if (is_array($rule_info['server_roles']) && in_array($CUR_SERVER_ROLE, $rule_info['server_roles'])) {
				$matched_server_role = true;
			}
			if ((!is_array($rule_info['methods'])			|| $matched_method)
				&& (!is_array($rule_info['user_groups'])	|| $matched_user_group)
				&& (!is_array($rule_info['themes'])			|| $matched_theme		|| !$CUR_USER_THEME)
				&& (!is_array($rule_info['locales'])		|| $matched_locale		|| !$CUR_LOCALE)
				&& (!is_array($rule_info['site_ids'])		|| $matched_site		|| !$CUR_SITE)
				&& (!is_array($rule_info['server_ids'])		|| $matched_server_id	|| !$CUR_SERVER_ID)
				&& (!is_array($rule_info['server_roles'])	|| $matched_server_role	|| !$CUR_SERVER_ROLE)
			) {
				$RESULT = trim($rule_info['rule_type']) == 'ALLOW' ? true : false;
			}
		}
		return $RESULT;
	}

	/**
	* Try to run center block module/method if allowed
	*/
	function prefetch_center() {
		$block_name = 'center_area';
		if (!isset($this->_blocks_infos)) {
			$this->_blocks_infos = main()->get_data('blocks_all');
		}
		if (empty($this->_blocks_infos)) {
			return false;
		}
		$BLOCK_EXISTS = false;
		foreach ((array)$this->_blocks_infos as $block_info) {
			if (trim($block_info['type']) != MAIN_TYPE) {
				continue;
			}
			if ($block_info['name'] == $block_name) {
				$BLOCK_EXISTS = true;
				$block_id = $block_info['id'];
				break;
			}
		}
		if (!$BLOCK_EXISTS) {
			return false;
		}
		if (!$this->_blocks_infos[$block_id]['active']) {
			return false;
		}
		if (!$this->_check_block_rights($block_id, $_GET['object'], $_GET['action'])) {
			return _class('graphics')->_action_on_block_denied($block_name);
		}
		return _class('graphics')->tasks(1);
	}

	/**
	* Main $_GET tasks handler
	*/
	function tasks($CHECK_IF_ALLOWED = false) {
		// Singleton
		$_center_result = tpl()->_CENTER_RESULT;
		if (isset($_center_result)) {
			return $_center_result;
		}
		$NOT_FOUND		= false;
		$ACCESS_DENIED	= false;
		$custom_handler_exists = false;

		_class('graphics')->_route_request();
		// Check if called class method is 'private' - then do not use it
		if (substr($_GET['action'], 0, 1) == '_' || $_GET['object'] == 'main') {
			$ACCESS_DENIED = true;
		}
		if (!$ACCESS_DENIED) {
			$obj = module($_GET['object']);
			if (!is_object($obj)) {
				$NOT_FOUND = true;
			}
			if (!$NOT_FOUND && !method_exists($obj, $_GET['action'])) {
				$NOT_FOUND = true;
			}
			// Check if we have custom action handler in module (catch all requests to module methods)
			if (method_exists($obj, main()->MODULE_CUSTOM_HANDLER)) {
				$custom_handler_exists = true;
			}
			// Do call class method
			if (!$NOT_FOUND || $custom_handler_exists) {
				if ($custom_handler_exists) {
					$NOT_FOUND = false;
					$body = $obj->{main()->MODULE_CUSTOM_HANDLER}($_GET['action']);
				} else {
					// Automatically call output cache trigger
					$is_banned = false;
					if (MAIN_TYPE_USER && isset(main()->AUTO_BAN_CHECKING) && main()->AUTO_BAN_CHECKING) {
						$is_banned = _class('ban_status')->_auto_check(array());
					}
					if ($is_banned) {
						$body = _e();
					} else {
						$body = $obj->$_GET['action']();
					}
				}
			}
		}
		// Process errors if exiss ones
		$redir_params = array(
			'%%object%%'		=> $_GET['object'],
			'%%action%%'		=> $_GET['action'],
			'%%add_get_vars%%'	=> str_replace('&',';',_add_get(array('object','action'))),
		);
		if ($NOT_FOUND) {
			if (_class('graphics')->NOT_FOUND_RAISE_WARNING) {
				trigger_error('MAIN: Task not found: '.$_GET['object'].'->'.$_GET['action'], E_USER_WARNING);
			}
			if (MAIN_TYPE_USER) {
				$url_not_found = main()->REDIR_URL_NOT_FOUND;
				if (is_array($url_not_found) && !empty($url_not_found)) {
					$_GET['object'] = $url_not_found['object'];
					$_GET['action'] = $url_not_found['action'];
					$_GET['id']		= $url_not_found['id'];
					$_GET['page']	= $url_not_found['page'];

					if (!empty($url_not_found['object'])) {
						$OBJ = _class($url_not_found['object'], $url_not_found['path']);
						$action = $url_not_found['action'] ? $url_not_found['action'] : 'show';
						if (method_exists($OBJ, $action)) {
							$body = $OBJ->$action();
						} else {
							main()->NO_GRAPHICS = true;
							echo '404: not found by main';
						}
					} elseif (isset($url_not_found['stpl'])) {
						main()->NO_GRAPHICS = true;
						echo tpl()->parse($url_not_found['stpl']);
					}
				} else {
					$redir_url = str_replace(array_keys($redir_params), array_values($redir_params), $url_not_found);
					if (!empty($redir_url)) {
						redirect($redir_url, 1, tpl()->parse('system/error_not_found'));
					}
				}
				$GLOBALS['task_not_found'] = true;
			}
		} elseif ($CHECK_IF_ALLOWED && $ACCESS_DENIED) {
			trigger_error('MAIN: Access denied: '.$_GET['object'].'->'.$_GET['action'], E_USER_WARNING);
			if (MAIN_TYPE_USER) {
				$redir_url = str_replace(array_keys($redir_params), array_values($redir_params), main()->REDIR_URL_DENIED);
				if (!empty($redir_url)) {
					redirect($redir_url, 1, tpl()->parse('system/error_not_found'));
				}
				$GLOBALS['task_denied'] = true;
			}
		}
		// Do not touch !!!
		tpl()->_CENTER_RESULT = (string)$body;
		// Output only center content, when we are inside AJAX_MODE
		if (conf('IS_AJAX')) {
			main()->NO_GRAPHICS = true;
			print $body;
		}
		return $body;
	}

	/**
	* Method that allows to change standard tasks mapping (if needed)
	*/
	function _route_request() {
		/* // Map example
		if ($_GET['object'] == 'forum') {
			$_GET = array();
			$_GET['object'] = 'gallery';
			$_GET['action'] = 'show';
		}
		*/
		// Custom routing for static pages (eq. for URL like /terms/ instead of /static_pages/show/terms/)
		if (!main()->STATIC_PAGES_ROUTE_TOP || MAIN_TYPE_ADMIN) {
			return false;
		}
		$_user_modules = main()->get_data('user_modules');
		// Do not override existing modules
		if (isset($_user_modules[$_GET['object']])) {
			return false;
		}
		$static_pages_names = main()->get_data('static_pages_names');
		$replaced_obj = str_replace('_', '-', $_GET['object']);
		if (in_array($_GET['object'], (array)$static_pages_names)) {
			$_GET['id']		= $_GET['object'];
			$_GET['object'] = 'static_pages';
			$_GET['action'] = 'show';
		} elseif (in_array($replaced_obj, (array)$static_pages_names)) {
			$_GET['id']		= $replaced_obj;
			$_GET['object'] = 'static_pages';
			$_GET['action'] = 'show';
		}
	}
}
