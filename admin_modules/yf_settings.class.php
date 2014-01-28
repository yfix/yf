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
		'memcached'	=> 'memcaached',
		'xcache'	=> 'xcache',
		'apc'		=> 'apc',
		'files'		=> 'files',
	);

	/**
	*/
	function show() {
// TODO: purge cache (memcached), disable site (maintenance), change default language, change default template, enable/disable other features here
		return form()
			->row_start()
				->link('cache_purge', './?object='.$_GET['object'].'&action=cache_purge') // TODO: link, method, icon
				->link('cache_stats', './?object='.$_GET['object'].'&action=cache_stats') // TODO: link, method, icon
			->row_end()
			->active_box('use_cache')
			->select_box('cache_driver', $this->cache_drivers)
			->number('cache_ttl')//, cache()->FILES_TTL

			->active_box('site_enabled')
			->select_box('default_css_framework', $this->css_frameworks) // TODO: link to edit
			->select_box('default_css_subtheme', $this->css_subthemes) // TODO: link to edit
			->select_box('default_skin', $this->default_skins) // TODO: link to edit
			->select_box('default_language', main()->get_data('languages')) // TODO: link to edit
#			->select_box('default_server', main()->get_data('servers')) // TODO: link to edit
#			->select_box('default_site', main()->get_data('sites')) // TODO: link to edit
#			->select_box('default_timezone', main()->get_data('timezones')) // TODO: link to edit
#			->select_box('default_currency', main()->get_data('currencies')) // TODO: link to edit
#			->city_box('default_city') // Where site is located and propose this by default for visitors // TODO: link to edit

#			->text('site_name', conf(''))
#			->text('default_meta_keywords')
#			->text('default_meta_description')
#			->text('default_charset')
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

#			->active_box('use_only_https')
#			->active_box('css_minimize')
#			->active_box('js_minimize')
#			->active_box('use_phar_php_code')
#			->active_box('online_users_tracking')
#			->active_box('errors_custom_handler')
#			->active_box('tpl_allow_use_db')
#			->select_box('tpl_driver')
#			->active_box('tpl_compile')

#			->select_box('mail_default_driver')

#			->active_box('admin_ajax_edit')
#			->active_box('admin_ajax_delete')
#			->active_box('form_input_no_append')

			->select_box('db_driver', $this->db_drivers)
#			->active_box('db_auto_restore_tables')
#			->active_box('db_query_cache_enabled')
#			->number('db_query_cache_ttl')
#			->select_box('db_query_cache_driver')
		;
	}
}
