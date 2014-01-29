<?php

/**
* System settings interface
*/
class yf_settings {

	// bs2, bs3, pure, foundation, etc
	public $css_frameworks = array(
		'bs2' => 'Twitter Bootstrap v2',
		'bs3' => 'Twitter Bootstrap v3',
		'pure' => 'Yahoo PureCSS',
		'foundation' => 'Zurb Foundation',
	);
	// currently for: bs2, bs3	
	public $css_subthemes = array(
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
	);
	// TODO: add more skins from fs inside project
	public $default_skins = array(
		'user'	=> 'User (default)',
		'admin'	=> 'Admin (default)',
	);
	public $db_drivers = array(
		'mysql'		=> 'mysql',
		'mysqli'	=> 'mysqli',
#		'mysql_pdo'	=> 'mysql PDO',
#		'sqlite'	=> 'sqlite',
#		'oracle'	=> 'oracle',
#		'postgre'	=> 'postgre',
	);
	public $cache_drivers = array(
		'memcached'	=> 'memcached',
		'xcache'	=> 'xcache',
		'apc'		=> 'apc',
		'files'		=> 'files',
	);
	public $tpl_drivers = array(
		'yf'		=> 'YF stpl (default)',
		'smarty'	=> 'smarty',
		'fenom'		=> 'fenom',
		'twig'		=> 'twig',
		'blitz'		=> 'blitz',
	);

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
	function show() {
// TODO: long descriptions for each item
// TODO: connect this (save to auto-generated file)
/* TODO: add 3rd level of configuring:
	1) class property
	2) PROJECT_CONF
	3) TODO: from auto-generated file or from conf('') ?
	4) _init() still able to override everything
*/
// TODO: maybe use conf('$mod_name::$setting', '$value') for overriding PROJECT_CONF from here
#print_r($GLOBALS['CONF']);

		if (main()->is_post()) {
			$to_save = array();
			foreach((array)$_POST as $k => $v) {
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
				if ($to_save) {
					$saved_settings_content = '<'.'?php'.PHP_EOL.implode(PHP_EOL, $to_save).PHP_EOL;
					$saved_settings_file = PROJECT_PATH.'saved_settings.php';
					common()->message_info('Saved settings file contents ('.$saved_settings_file.') <pre>'._prepare_html($saved_settings_content).'</pre>');
					file_put_contents($saved_settings_file, $saved_settings_content);
					return js_redirect('./?object='.$_GET['object']);
				}
			}
		}
		$r = (array)$_POST + (array)conf();
		$a = array(
			'row_start',
				'save',
				array('link', 'cache_purge', './?object='.$_GET['object'].'&action=cache_purge'), // TODO: link, method, icon
				array('link', 'cache_stats', './?object='.$_GET['object'].'&action=cache_stats'), // TODO: link, method, icon
				array('link', 'minify_css', './?object='.$_GET['object'].'&action=minify_css'), // TODO: link, method, icon
				array('link', 'minify_js', './?object='.$_GET['object'].'&action=minify_js'), // TODO: link, method, icon
			'row_end',
			array('active_box', 'main[USE_SYSTEM_CACHE]', array('desc' => 'use_cache')),
			array('select_box', 'cache[DRIVER]', $this->cache_drivers, array('desc' => 'cache_driver')),
			array('number', 'cache[FILES_TTL]', array('desc' => 'cache_ttl')), //, cache()->FILES_TTL
			'save',
		);
		return form()->array_to_form($a, array('class' => 'form-horizontal form-condensed'));
/*
		return form($r, array('class' => 'form-horizontal form-condensed'))
			->row_start()
				->save()
				->link('cache_purge', './?object='.$_GET['object'].'&action=cache_purge') // TODO: link, method, icon
				->link('cache_stats', './?object='.$_GET['object'].'&action=cache_stats') // TODO: link, method, icon
				->link('minify_css', './?object='.$_GET['object'].'&action=minify_css') // TODO: link, method, icon
				->link('minify_js', './?object='.$_GET['object'].'&action=minify_js') // TODO: link, method, icon
			->row_end()
			->active_box('main[USE_SYSTEM_CACHE]', array('desc' => 'use_cache'))
			->select_box('cache[DRIVER]', $this->cache_drivers, array('desc' => 'cache_driver'))
			->number('cache[FILES_TTL]', array('desc' => 'cache_ttl'))//, cache()->FILES_TTL

			->active_box('site_maintenance', array('tip' => ''))
			->select_box('default_css_framework', $this->css_frameworks) // TODO: link to edit
#			->select_box('DEF_BOOTSTRAP_THEME', $this->css_subthemes, array('desc' => 'default_css_subtheme')) // TODO: link to edit
			->select_box('default_css_subtheme', $this->css_subthemes) // TODO: link to edit
			->select_box('default_skin', $this->default_skins) // TODO: link to edit
			->select_box('default_language', main()->get_data('languages')) // TODO: link to edit
#			->select_box('default_server', main()->get_data('servers')) // TODO: link to edit
#			->select_box('default_site', main()->get_data('sites')) // TODO: link to edit
#			->select_box('default_timezone', main()->get_data('timezones')) // TODO: link to edit
#			->select_box('default_currency', main()->get_data('currencies')) // TODO: link to edit
#			->city_box('default_city') // Where site is located and propose this by default for visitors // TODO: link to edit

#			->text('site_name', conf('SITE_NAME'))
			->text('meta_keywords', 'default_meta_keywords')
			->text('meta_description', 'default_meta_description')
			->text('charset', 'default_charset')
#			->text('images_web_path')
#			->text('media_path_web')
#			->text('media_path_fs')
#			->text('media_domain')

#			->text('session_cookie_path')
#			->text('session_cookie_domain')
#			->text('session_cookie_lifetime')
#			->active_box('session_cookie_httponly')
#			->active_box('session_cookie_secure')
#			->active_box('session_referer_check')

#			->text('php_memory_limit')
#			->text('php_max_execution_time')

#			->active_box('rewrite_mode')
#			->active_box('debug_mode')
#			->active_box('dev_mode')
#			->active_box('output_cache')
#			->active_box('inline_locale_edit')
#			->active_box('inline_stpl_edit')
#			->active_box('xhprof_enable')

			->active_box('use_only_https')
			->active_box('css_minimize')
			->active_box('js_minimize')
			->active_box('use_phar_php_code')
			->active_box('online_users_tracking')
			->active_box('errors_custom_handler')
			->select_box('tpl_driver', $this->tpl_drivers)
			->active_box('tpl_compile')
			->active_box('tpl_allow_use_db')

#			->select_box('mail_default_driver')

#			->active_box('admin_ajax_edit')
#			->active_box('admin_ajax_delete')
#			->active_box('form_input_no_append')

			->select_box('db_driver', $this->db_drivers)
#			->active_box('db_auto_restore_tables')
#			->active_box('db_query_cache_enabled')
#			->number('db_query_cache_ttl')
#			->select_box('db_query_cache_driver')

			->save()
		;
*/
	}

	/**
	*/
	function cache_purge() {
		$result = _class('cache')->_clear_all();
		return js_redirect('./?object='.$_GET['object']);
	}

	/**
	*/
	function cache_stats() {
// TODO
	}

	/**
	*/
	function minify_css() {
// TODO
	}

	/**
	*/
	function minify_js() {
// TODO
	}
}
