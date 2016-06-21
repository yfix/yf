<?php

/**
* System settings interface
*/
class yf_settings {

	// bs2, bs3, pure, foundation, etc
	public $css_frameworks = [
		'bs2' => 'Twitter Bootstrap v2',
		'bs3' => 'Twitter Bootstrap v3',
#		'pure' => 'Yahoo PureCSS',
#		'foundation' => 'Zurb Foundation',
	];
	// currently for: bs2, bs3
	public $css_subthemes = [
		'default'	=> 'Bootstrap',
		'amelia'	=> 'Amelia (light)',
		'cerulean'	=> 'Cerulean (light)',
		'cosmo'		=> 'Cosmo (light)',
		'cyborg'	=> 'Cyborg (dark)',
		'flatly'	=> 'Flatly (light)',
		'journal'	=> 'Journal (light)',
		'readable'	=> 'Readable (light)',
		'simplex'	=> 'Simplex (light)',
		'slate'		=> 'Slate (dark)',
		'spacelab'	=> 'Spacelab (light)',
		'united'	=> 'United (light)',
	];
	// TODO: add more skins from fs inside project
	public $default_skins = [
		'user'	=> 'User (default)',
		'admin'	=> 'Admin (default)',
	];
	public $db_drivers = [
		'mysql'		=> 'mysql',
		'mysqli'	=> 'mysqli',
		'mysql_pdo'	=> 'mysql PDO',
#		'sqlite'	=> 'sqlite',
#		'oracle'	=> 'oracle',
#		'postgre'	=> 'postgre',
	];
	public $cache_drivers = [
		'memcache'	=> 'memcache',
		'xcache'	=> 'xcache',
#		'apc'		=> 'apc',
		'files'		=> 'files',
	];
	public $tpl_drivers = [
		'yf'		=> 'YF stpl (default)',
#		'smarty'	=> 'smarty',
#		'fenom'		=> 'fenom',
#		'twig'		=> 'twig',
#		'blitz'		=> 'blitz',
	];

	/**
	*/
	function show() {
		$r = [];
		foreach ((array)conf() as $k => $v) {
			if (is_array($v)) {
				foreach ((array)$v as $k2 => $v2) {
					$r[$k.'__'.$k2] = $v2;
				}
			} else {
				$r[$k] = $v;
			}
		}
		return '<pre>'._prepare_html(print_r($r, 1)).'</pre>';
	}
/*
	function show() {
		if (main()->is_post()) {
			$to_save = $this->_prepare_to_save($_POST);
			if ($to_save) {
				$saved_settings_content = '<'.'?php'.PHP_EOL.implode(PHP_EOL, $to_save).PHP_EOL;
				if (defined('CONFIG_PATH') && file_exists(CONFIG_PATH)) {
					$saved_settings_file = CONFIG_PATH.'saved_settings.php';
				} else {
					$saved_settings_file = PROJECT_PATH.'saved_settings.php';
				}
				common()->message_info('Saved settings file contents ('.$saved_settings_file.') <pre>'.str_replace('_', '&#95;', _prepare_html($saved_settings_content)).'</pre>');
				file_put_contents($saved_settings_file, $saved_settings_content);
				return js_redirect(url('/@object'));
			}
		}
		$a = array(
			'row_start',
				array('link', 'display_what', url('/@object/display_what'), array('no_text' => 1, 'icon' => 'icon-edit fa fa-edit')),
				array('save'),
				array('link', 'cache_purge', url('/@object/cache_purge'), array('class' => 'btn btn-default')), // TODO: link, method, icon
			'row_end',
		);
		$r = array();
		foreach ((array)conf() as $k => $v) {
			if (is_array($v)) {
				foreach ((array)$v as $k2 => $v2) {
					$r[$k.'__'.$k2] = $v2;
				}
			} else {
				$r[$k] = $v;
			}
		}
		$hooks_data = _class('admin_methods')->call_hooks('settings', $r);
		$avail_hook_modules = array();
		foreach ((array)$hooks_data as $k => $v) {
			list($module_name,) = explode('___', $k);
			$avail_hook_modules[$module_name] = $k;
		}
		$settings = $this->_get_settings($hooks_data);
		foreach ((array)$settings as $s) {
			$name = $s['item'];
			if (!isset($avail_hook_modules[$name])) {
				continue;
			}
			$hooks = $hooks_data[$avail_hook_modules[$name]];
			$is_checked = $s['value'] ? 1 : 0;
			if (!$is_checked || empty($hooks)) {
				continue;
			}
			$a[] = array('fieldset_start', array('id' => 'module_'.$name, 'legend' => $name, 'class' => 'well'));
			foreach ((array)$hooks as $hook_data) {
				$a[] = $hook_data;
			}
			$a[] = array('fieldset_end');
			$this->_used_modules[$name] = $name;
		}
		$r = (array)$_POST + (array)$r;
		return form($r, array('class' => 'form-vertical form-condensed span6'))->array_to_form($a);
	}
*/

	/**
	*/
	function cache_purge() {
		$result = _class('cache')->_clear_all();
		return js_redirect(url('/@object'));
	}

	/**
	*/
	function _get_settings($hooks_data) {
		$settings = db()->from('settings')->order_by('`order`, item ASC')->get_all();
		if (!$settings && $hooks_data) {
			$settings = [];
			foreach ((array)$hooks_data as $k => $v) {
				list($module_name,) = explode('___', $k);
				$settings[] = [
					'item'	=> $module_name,
					'value'	=> 0,
					'order'	=> ++$i,
				];
			}
			if ($settings) {
				db()->insert_safe('settings', $settings);
			}
		}
		return $settings;
	}

	/**
	*/
	function display_what() {
		$hooks_data = _class('admin_methods')->call_hooks('settings', $r);
		$avail_hook_modules = [];
		foreach ((array)$hooks_data as $k => $v) {
			list($module_name,) = explode('___', $k);
			$avail_hook_modules[$module_name] = $k;
		}
		$settings = $this->_get_settings($hooks_data);
		if (main()->is_post()) {
			parse_str($_POST['sort'], $tmp);
			$posted_sort = [];
			foreach ((array)$tmp['sort'] as $v) {
				$posted_sort[$v] = $v;
			}
			foreach ((array)$settings as $s) {
				$name = $s['item'];
				$_n = str_replace('_','',$name);
				if (!isset($avail_hook_modules[$name])) {
					unset($posted_sort[$_n]);
					continue;
				}
				$posted_sort[$_n] = $name;
			}
			$to_save = [];
			foreach ((array)$posted_sort as $_n => $name) {
				$to_save[$name] = [
					'item'	=> $name,
					'value'	=> isset($_POST['check'][$_n]) ? 1 : 0,
					'order'	=> ++$i,
				];
			}
			if ($to_save) {
				db()->query('TRUNCATE TABLE '.db('settings'));
				db()->insert_safe('settings', $to_save);
			}
			return js_redirect('/@object/@action');
		}
		jquery('
			var container = $("#settings-sortable-container")
			container.find("ul").sortable();
			container.closest("form").on("submit", function(){
				$(this).find("input[name=sort][type=hidden]").val( container.find("ul").sortable("serialize", { key: "sort[]" }) )
			});
		');
		$container_html = '
			<div class="span6" id="settings-sortable-container">
			    <ul class="nav nav-pills nav-stacked" id="sortable_settings">
		';
		foreach ((array)$settings as $s) {
			$name = $s['item'];
			if (!isset($avail_hook_modules[$name])) {
				continue;
			}
			$hooks = $hooks_data[$avail_hook_modules[$name]];
			$is_checked = $s['value'] ? 1 : 0;
			$container_html .= '<li class="item" id="liitem_'.str_replace('_', '', $name).'"><a style="cursor:move;"><i class="icon icon-move fa fa-arrows"></i> '.t($name).' ('.(count($hooks)).')'
				.' <input type="checkbox" name="check['.str_replace('_', '', $name).']" value="1" style="float:right;"'.($is_checked ? ' checked="checked"' : '').'></a></li>'.PHP_EOL;
		}
		$container_html .= '
			    </ul>
			</div>
		';
		$a['back_link'] = url('/@object');
		return form($a, ['legend' => 'Settings items'])
			->hidden('sort')
			->container($container_html, ['wide' => 1])
			->save_and_back();
	}

	/**
	*/
	function _addslashes($val) {
		if (is_array($val)) {
			foreach ($val as $k => $v) {
				$val[$k] = $this->_addslashes($v);
			}
			return $val;
		}
		return addslashes($val);
	}

	/**
	*/
	function _prepare_to_save($a) {
		$to_save = [];
		foreach((array)$a as $k => $v) {
			if (is_string($v) && !strlen($v)) {
				continue;
			}
			if (is_array($v)) {
				foreach((array)$v as $k2 => $v2) {
					if (!$k2 || (is_string($v2) && !strlen($v2))) {
						unset($v[$k2]);
					}
				}
				if (!count($v)) {
					continue;
				}
			}
			$to_save[$k] = $v;
		}
		if ($to_save) {
			foreach((array)$to_save as $k => $v) {
				if (is_array($v)) {
					foreach((array)$v as $k2 => $v2) {
						$to_save[$k.'::'.$k2] = '$CONF[\''.$this->_addslashes($k).'\'][\''.$this->_addslashes($k2).'\'] = \''.$this->_addslashes($v2).'\';';
					}
					unset($to_save[$k]);
				} elseif (false !== strpos($k, '__')) {
					list($_module, $_setting) = explode('__', $k);
					$to_save[$k] = '$CONF[\''.$this->_addslashes($_module).'\'][\''.$this->_addslashes($_setting).'\'] = \''.$this->_addslashes($v).'\';';
				} else {
					$to_save[$k] = '$CONF[\''.$this->_addslashes($k).'\'] = \''.$this->_addslashes($v).'\';';
				}
			}
		}
		return $to_save;
	}

	/**
	*/
	function _hook_settings(&$selected = []) {
		$selected['site_maintenance'] = conf('site_maintenance') ?: 0;
		$selected['main[USE_SYSTEM_CACHE]'] = module_conf('main', 'USE_SYSTEM_CACHE') || (defined('USE_CACHE') && USE_CACHE) ?: 0; // TODO: unify and simplify
		$selected['cache[DRIVER]'] = module_conf('cache', 'DRIVER') ?: 'memcache';
		$selected['main[ALLOW_DEBUG_PROFILING]'] = main()->ALLOW_DEBUG_PROFILING;
		$selected['DEBUG_CONSOLE_POPUP'] = conf('DEBUG_CONSOLE_POPUP');

		return [
			['yes_no_box', 'site_maintenance', ['tip' => '']],
			['yes_no_box', 'main[USE_SYSTEM_CACHE]', ['desc' => 'use_cache']],
			['select_box', 'cache[DRIVER]', $this->cache_drivers, ['desc' => 'cache_driver']],
#			array('number', 'cache[FILES_TTL]', array('desc' => 'cache_ttl')), //, cache()->FILES_TTL
			['select_box', 'css_framework', $this->css_frameworks, ['show_text' => 1]], // TODO: link to edit
			['yes_no_box', 'main[ALLOW_DEBUG_PROFILING]', ['desc' => 'Use built-in code profiling (for DEBUG_MODE)']],
			['yes_no_box', 'DEBUG_CONSOLE_POPUP', ['desc' => 'Debug console as popup window (for DEBUG_MODE)']],
/*
#			array('select_box', 'DEF_BOOTSTRAP_THEME', $this->css_subthemes, array('desc' => 'default_css_subtheme')), // TODO: link to edit
			array('select_box', 'default_css_subtheme', $this->css_subthemes), // TODO: link to edit
			array('select_box', 'default_skin', $this->default_skins), // TODO: link to edit
			array('select_box', 'default_language', main()->get_data('languages')), // TODO: link to edit
#			array('select_box', 'default_server', main()->get_data('servers')), // TODO: link to edit
#			array('select_box', 'default_site', main()->get_data('sites')), // TODO: link to edit
#			array('select_box', 'default_timezone', main()->get_data('timezones')), // TODO: link to edit
#			array('select_box', 'default_currency', main()->get_data('currencies')), // TODO: link to edit
#			array('city_box', 'default_city'), // Where site is located and propose this by default for visitors // TODO: link to edit

#			array('text', 'site_name', conf('SITE_NAME')),
			array('text', 'meta_keywords', 'default_meta_keywords'),
			array('text', 'meta_description', 'default_meta_description'),
			array('text', 'charset', 'default_charset'),
#			array('text', 'images_web_path'),
#			array('text', 'media_path_web'),
#			array('text', 'media_path_fs'),
#			array('text', 'media_domain'),

#			array('text', 'session_cookie_path'),
#			array('text', 'session_cookie_domain'),
#			array('text', 'session_cookie_lifetime'),
#			array('active_box', 'session_cookie_httponly'),
#			array('active_box', 'session_cookie_secure'),
#			array('active_box', 'session_referer_check'),

#			array('text', 'php_memory_limit'),
#			array('text', 'php_max_execution_time'),

#			array('active_box', 'rewrite_mode'),
#			array('active_box', 'debug_mode'),
#			array('active_box', 'dev_mode'),
#			array('active_box', 'output_cache'),
#			array('active_box', 'inline_locale_edit'),
#			array('active_box', 'inline_stpl_edit'),
#			array('active_box', 'xhprof_enable'),

			array('active_box', 'use_only_https'),
			array('active_box', 'use_phar_php_code'),
			array('active_box', 'online_users_tracking'),
			array('active_box', 'errors_custom_handler'),
			array('select_box', 'tpl_driver', $this->tpl_drivers),
			array('active_box', 'tpl_compile'),
			array('active_box', 'tpl_allow_use_db'),

#			array('select_box', 'mail_default_driver'),

#			array('active_box', 'admin_ajax_edit'),
#			array('active_box', 'admin_ajax_delete'),
#			array('active_box', 'form_input_no_append'),

			array('select_box', 'db_driver', $this->db_drivers),
#			array('active_box', 'db_auto_restore_tables'),
#			array('active_box', 'db_query_cache_enabled'),
#			array('number', 'db_query_cache_ttl'),
#			array('select_box', 'db_query_cache_driver'),
			'row_start',
#				array('link', 'cache_stats', url('/@object/cache_stats')), // TODO: link, method, icon
			'row_end',
*/
		];
	}

	/**
	*/
	function _hook_side_column() {
		if (!$this->_used_modules) {
			return false;
		}
		$items = [];
		$url = process_url(url('/@object'));
		foreach ((array)$this->_used_modules as $module_name) {
			$items[] = '<li><a href="'.$url.'#module_'.$module_name.'"><i class="icon-chevron-right fa fa-chevron-right"></i> '.t($module_name).'</a></li>';
		}
		return '<div class="span3 bs-docs-sidebar"><ul class="nav nav-list bs-docs-sidenav">'.implode(PHP_EOL, $items).'</ul></div>';
	}

	/**
	*/
	function cache_stats() {
// TODO
	}
}
