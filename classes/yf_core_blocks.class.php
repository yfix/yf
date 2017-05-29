<?php

/**
* Core blocks methods
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_core_blocks {

	public $TASK_NOT_FOUND_404_HEADER = false;
	public $TASK_DENIED_403_HEADER = false;
	public $FORCE_ALLOWED_CLASSES = [
		'api',
	];

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* Alias for the '_show_block'
	*/
	function show_block($params = []) {
		return $this->_show_block($params);
	}

	/**
	* Show custom block contents
	*/
	function _show_block($input = []) {
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
			return $this->_action_on_block_denied($block_name);
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
			$special_params = [
				'block_name'	=> $block_name,
				'block_id'		=> $block_id,
			];
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
		$prepend = _class('core_events')->fire('block.prepend['.$block_name.']');

		$body = tpl()->parse($cur_block_info['stpl_name'] ?: $block_name, [
			'block_name'=> $block_name,
			'block_id'	=> $block_id,
		]);

		$append = _class('core_events')->fire('block.append['.$block_name.']', [&$body]);

		return ($prepend ? implode(PHP_EOL, $prepend) : ''). $body. ($append ? implode(PHP_EOL, $append) : '');
	}

	/**
	* Action to on denied block
	*/
	function _action_on_block_denied($block_name = '') {
		if ($block_name == 'center_area') {
			if ($this->TASK_DENIED_403_HEADER) {
				header(($_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1').' 403 Forbidden');
				main()->IS_403 = true;
			}
			if (MAIN_TYPE_USER && !main()->USER_ID) {
				$redir_params = [
					'%%object%%'		=> $_GET['object'],
					'%%action%%'		=> $_GET['action'],
					'%%add_get_vars%%'	=> str_replace('&',';',_add_get(['object','action'])),
				];
				$redir_url = str_replace(array_keys($redir_params), array_values($redir_params), main()->REDIR_URL_DENIED);
				if (!empty($redir_url)) {
					if ($_GET['object'] == 'login_form') {
						return 'Access to login form denied on center block ('.__CLASS__.'.'.__FUNCTION__.')';
					} else {
						return js_redirect($redir_url);
					}
				}
			} elseif (MAIN_TYPE_USER && main()->USER_ID) {
				return '<div class="alert alert-error alert-danger">'.t('Access denied').'</div>';
			} elseif (MAIN_TYPE_ADMIN && main()->ADMIN_ID) {
				return '<div class="alert alert-error alert-danger">'.t('Access denied').'</div>';
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
	function _load_blocks_rules() {
		if (!empty($this->_blocks_rules)) {
			return false;
		}
		$rules = main()->get_data('blocks_rules');
		$rule_names_to_skip = ['id','block_id','rule_type','active','order'];
		foreach ((array)$rules as $rule_id => $rule_info) {
			foreach ((array)$rule_info as $rule_name => $rule_text) {
				if (in_array($rule_name, $rule_names_to_skip) || empty($rule_text)) {
					continue;
				}
				$rule_text = trim(str_replace([' ',"\t","\r","\n","\"","'",',,'], '', $rule_text), ',');
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

		$rules = [];
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
	function _check_block_rights($block_id = 0, $OBJECT = '', $ACTION = '') {
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
		return $this->tasks($allowed_check = true);
	}

	/**
	* Display main 'center' block contents
	*/
	function show_center() {
		return $this->tasks($allowed_check = true);
	}

	/**
	*/
	function _get_denied_tasks_names() {
		$cache = 'cache_'.__FUNCTION__;
		if (isset($this->$cache)) {
			return $this->$cache;
		}
		$names = [];
		$ext = '.class.php';
		$pattern = '{,plugins/*/}classes/*'. $ext;
		$globs = [
			'framework'	=> YF_PATH. $pattern,
			'project'	=> PROJECT_PATH. $pattern,
			'app'		=> APP_PATH. $pattern,
		];
		$ext_len = strlen($ext);
		$names = [];
		$prefix = YF_PREFIX;
		$plen = strlen($prefix);
		foreach ($globs as $glob) {
			foreach (glob($glob, GLOB_BRACE) as $path) {
				$name = substr(basename($path), 0, -$ext_len);
				if (substr($name, 0, $plen) === $prefix) {
					$name = substr($name, $plen);
				}
				$names[$name] = $name;
			}
		}
		if ($exclude = $this->FORCE_ALLOWED_CLASSES) {
			foreach ((array)$exclude as $name) {
				if (isset($names[$name])) {
					unset($names[$name]);
				}
			}
		}
		$this->$cache = $names;
		return $names;
	}

	/**
	* Main $_GET tasks handler
	*/
	function tasks($allowed_check = false) {
		$main = main();
		if ($main->is_console() || $main->is_ajax()) {
			$main->no_graphics(true);
		}
		// Singleton
		$_center_result = tpl()->_CENTER_RESULT;
		if (isset($_center_result)) {
			return $_center_result;
		}
		$not_found		= false;
		$access_denied	= false;
		$custom_handler_exists = false;

		$OBJECT = &$_GET['object'];
		$ACTION = &$_GET['action'];

		_class('router')->_route_request();
		// Check if called class method is 'private' - then do not use it
		// Also we protect here core classes that can be instantinated before this method and can be allowed by mistake
		// Use other module names, think about this list as "reserved" words
		if (substr($ACTION, 0, 1) == '_' || !strlen($OBJECT) || substr($OBJECT, 0, strlen(YF_PREFIX)) === YF_PREFIX || in_array($OBJECT, $this->_get_denied_tasks_names())) {
			$access_denied = true;
		}
		if (!$access_denied) {
			$obj = module($OBJECT);
			if (!is_object($obj)) {
				$not_found = true;
			}
			if (!$not_found && !method_exists($obj, $ACTION)) {
				$not_found = true;
			}
			// Check if we have custom action handler in module (catch all requests to module methods)
			if (method_exists($obj, $main->MODULE_ACTION_HANDLER)) {
				$custom_handler_exists = true;
			}
			if (!$not_found || $custom_handler_exists) {
				if ($custom_handler_exists) {
					$not_found = false;
					$body = $obj->{$main->MODULE_ACTION_HANDLER}($ACTION, $main->_ARGS_DIRTY);
				} else {
					$is_banned = false;
					if (MAIN_TYPE_USER && $main->AUTO_BAN_CHECKING) {
						$is_banned = _class('ban_status')->_auto_check([]);
					}
					if ($is_banned) {
						$body = _e();
					} else {
						$body = $obj->$ACTION();
					}
				}
			}
		}
		$redirect_func = function($url) {
			$redir_params = [
				'%%object%%'		=> $OBJECT,
				'%%action%%'		=> $ACTION,
				'%%add_get_vars%%'	=> str_replace('&',';',_add_get(['object','action'])),
			];
			$redir_url = str_replace(array_keys($redir_params), array_values($redir_params), $url);
			if (!empty($redir_url)) {
				redirect($redir_url, 1, tpl()->parse('system/error_not_found'));
			}
		};
		if ($not_found) {
			$main->BLOCKS_TASK_404 = true;
			if ($this->TASK_NOT_FOUND_404_HEADER) {
				header(($_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1').' 404 Not Found');
				$main->IS_404 = true;
			}
			if (_class('graphics')->NOT_FOUND_RAISE_WARNING) {
				trigger_error(__CLASS__.': Task not found: '.$OBJECT.'.'.$ACTION, E_USER_WARNING);
			}
			if (MAIN_TYPE_USER) {
				$u = $main->REDIR_URL_NOT_FOUND;
				if (is_array($u) && !empty($u)) {
					// Prefill GET keys from redirect url
					foreach (['object','action','id','page'] as $k) {
						$_GET[$k] = $u[$k];
					}
					if (!empty($u['object'])) {
						$action = $u['action'] ?: 'show';
						$body = _class_safe($u['object'], $u['path'])->$action();
					} elseif (isset($u['stpl'])) {
						$main->no_graphics(true);
						print tpl()->parse($u['stpl']);
					}
				} else {
					$redir_func = $this->REDIRECT_CALLBACK_404 ?: $redirect_func;
					$redir_func($u);
				}
			}
		} elseif ($allowed_check && $access_denied) {
			$main->BLOCKS_TASK_403 = true;
			if ($this->TASK_DENIED_403_HEADER) {
				header(($_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1').' 403 Forbidden');
				$main->IS_403 = true;
			}
			trigger_error(__CLASS__.': Access denied: '.$OBJECT.'.'.$ACTION, E_USER_WARNING);
			if (MAIN_TYPE_USER) {
				$redir_func = $this->REDIRECT_CALLBACK_403 ?: $redirect_func;
				$redir_func($main->REDIR_URL_DENIED);
			}
		}
		$block_name = 'center_area';
		$events = _class('core_events');
		$prepend= $events->fire('block.prepend['.$block_name.']');
		$append	= $events->fire('block.append['.$block_name.']', [&$body]);
		$body = ($prepend ? implode(PHP_EOL, $prepend) : ''). $body. ($append ? implode(PHP_EOL, $append) : '');
		// Singleton
		tpl()->_CENTER_RESULT = (string)$body;
		// Output only center content, when we are inside AJAX_MODE
		if ($main->is_ajax()) {
			print $body;
		}
		return $body;
	}
}
