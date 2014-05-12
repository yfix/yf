<?php

/**
* Locale, i18n (Internationalization) editor
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_locale_editor {

	/** @var string @conf_skip PHP files to parse */
	public $_include_php_pattern	= array('#\/(admin_modules|classes|functions|modules)#', '#\.php$#');
	/** @var string @conf_skip STPL Files to parse */
	public $_include_stpl_pattern	= array('#\/(templates)#', '#\.stpl$#');
	/** @var string @conf_skip Exclude files from parser */
	public $_exclude_pattern		= array('#\/(adodb|captcha_fonts|domit|feedcreator|html2fpdf|locale|smarty|samples|pear)#', '');
	/** @var string @conf_skip Search vars in PHP files */
	public $_translate_php_pattern	= "/[\(\{\.\,\s\t=]+?(translate|t|i18n)[\s\t]*?\([\s\t]*?('[^'\$]+?'|\"[^\"\$]+?\")/ims";
	/** @var string @conf_skip Search vars in STPL files */
	public $_translate_stpl_pattern= "/\{(t|translate|i18n)\([\"']*([\s\w\-\.\,\:\;\%\&\#\/\<\>]*)[\"']*[,]*[^\)\}]*\)\}/is";
	/** @var bool Display vars locations */
	public $DISPLAY_VARS_LOCATIONS	= true;
	/** @var bool Display links to edit source files (in location) */
	public $LOCATIONS_EDIT_LINKS	= true;
	/** @var bool Ignore case on import/export */
	public $VARS_IGNORE_CASE		= true;

	/**
	* Framework constructor
	*/
	function _init () {
		$this->_boxes = array(
			'lang_code'		=> 'select_box("lang_code",		$this->_langs,			$selected, false, 2, "", false)',
			'cur_langs'		=> 'select_box("lang_code",		$this->_cur_langs,		$selected, false, 2, "", false)',
			'file_format'	=> 'radio_box("file_format",	$this->_file_formats,	$selected, true, 2, "", false)',
			'mode'			=> 'radio_box("mode",			$this->_modes,			$selected, true, 2, "", false)',
			'search_type'	=> 'radio_box("search_type",	$this->_search_types,	$selected, false, 2, "", false)',
			'location'		=> 'select_box("location",		$this->_used_locations,	$selected, false, 2, "", false)',
			'module'		=> 'select_box("module",		$this->_modules,		$selected, false, 2, "", false)',
		);

		$this->_modules = _class('common_admin')->find_active_modules();

		foreach ((array)$this->_get_iso639_list() as $lang_code => $lang_params) {
			$this->_langs[$lang_code] = t($lang_params[0]).(!empty($lang_params[1]) ? ' ('.$lang_params[1].') ' : '');
		}

		$Q = db()->query('SELECT * FROM '.db('locale_langs').' ORDER BY is_default DESC, locale ASC');
		while ($A = db()->fetch_assoc($Q)) {
			$this->_cur_langs_array[$A['id']] = $A;
		}

		if (empty($this->_cur_langs_array)) {
			db()->INSERT('locale_langs', array(
				'locale'	=> 'en',
				'name'		=> t('English'),
				'charset'	=> 'utf-8',
				'active'	=> 1,
				'is_default'=> 1,
			));
			js_redirect('./?object='.$_GET['object'].'&action='.$_GET['action']);
		}

		$this->_langs_for_search[''] = t('All languages');
		foreach ((array)$this->_cur_langs_array as $A) {
			$this->_langs_for_search[$A['locale']] = t($A['name']);
			$this->_cur_langs[$A['locale']] = t($A['name']);
		}
// TODO: add support for these file formats for import/export:
// * JSON
// * PHP
// * GNU Gettext (.po)  http://www.gutenberg.org/wiki/Gutenberg:GNU_Gettext_Translation_How-To, https://en.wikipedia.org/wiki/Gettext
		$this->_file_formats = array(
			'csv'	=> t('CSV, compatible with MS Excel'),
			'xml'	=> t('XML'),
		);
		$this->_modes = array(
			1	=> t('Strings in the uploaded file replace existing ones, new ones are added'),
			2	=> t('Existing strings are kept, only new strings are added'),
		);
	}

	/**
	* Display all project languages
	*/
	function show() {
		$tr_vars = db()->get_2d('SELECT locale, COUNT(var_id) AS num FROM '.db('locale_translate').' WHERE value != "" GROUP BY locale');
		$total_vars = (int)db()->get_one('SELECT COUNT(*) FROM '.db('locale_vars'));

		$data = array();
		foreach ((array)$this->_cur_langs_array as $v) {
			$id = $v['locale'];
			$v['tr_count'] = strval($tr_vars[$id]);
			$v['tr_percent'] = $total_vars && $v['tr_count'] ? round(100 * $v['tr_count'] / $total_vars, 2).'%' : '';
			$data[$id] = $v;
		}
		$no_actions_if_default = function($row) {
			return $row['is_default'] ? false : true;
		};
		return table($data)
			->text('locale', array('badge' => 'default', 'transform' => 'strtoupper'))
			->text('name')
			->text('charset')
			->text('tr_count', 'Num vars')
			->text('tr_percent', 'Translated', array('badge' => 'info'))
			->text('is_default')
			->btn_edit('', './?object='.$_GET['object'].'&action=lang_edit&id=%d')
			->btn_delete('', './?object='.$_GET['object'].'&action=lang_delete&id=%d', array('display_func' => $no_actions_if_default))
			->btn('Make default', './?object='.$_GET['object'].'&action=lang_default&id=%d', array('class' => 'btn-info', 'display_func' => $no_actions_if_default))
			->btn_active('', './?object='.$_GET['object'].'&action=lang_active&id=%d', array('display_func' => $no_actions_if_default))
			->footer_link('Manage vars', './?object='.$_GET['object'].'&action=show_vars')
			->footer_add('Add language', './?object='.$_GET['object'].'&action=lang_add')
			->footer_link('Import vars', './?object='.$_GET['object'].'&action=import_vars', array('icon' => 'icon-signin'))
			->footer_link('Export vars', './?object='.$_GET['object'].'&action=export_vars', array('icon' => 'icon-signout'))
#			->footer_link('Collect vars', './?object='.$_GET['object'].'&action=collect_vars')
#			->footer_link('Cleanup vars', './?object='.$_GET['object'].'&action=cleanup_vars')
#			->footer_link('Import vars', './?object='.$_GET['object'].'&action=import_vars')
#			->footer_link('Export vars', './?object='.$_GET['object'].'&action=export_vars')
#			->footer_link('User vars', './?object='.$_GET['object'].'&action=user_vars')
		;
	}

	/**
	*/
	function lang_add() {
		foreach ((array)$this->_cur_langs_array as $A) {
			if (isset($this->_langs[$A['locale']])) {
				unset($this->_langs[$A['locale']]);
			}
		}
		if (main()->is_post()) {
			if (empty($_POST['lang_code'])) {
				common()->_error_exists('Please select language to add');
			}
			if (!empty($_POST['lang_code']) && isset($this->_langs[$_POST['lang_code']])) {
				common()->_error_exists('This language has been added already');
			}
// TODO: replace this with form->language_box()
			$raw_langs = $this->_get_iso639_list();
			if (!isset($raw_langs[$_POST['lang_code']])) {
				common()->_error_exists('Wrong language code');
			}
			if (!common()->_error_exists()) {
				db()->INSERT('locale_langs', array(
					'locale'		=> _es($_POST['lang_code']),
					'name'			=> _es($raw_langs[$_POST['lang_code']][0]),
					'charset'		=> _es('utf-8'),
					'active'		=> 1,
					'is_default'	=> 0,
				));
				$this->_create_empty_vars_for_locale($_POST['lang_code']);
				cache_del('locale_langs');
				return js_redirect('./?object='.$_GET['object']);
			}
		}
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'],
// TODO: replace this with _class('html')->list_box()
			'langs_box'		=> $this->_box('lang_code',	-1),
			'back_link'		=> './?object='.$_GET['object'],
			'error_message'	=> _e(),
		);
		return tpl()->parse($_GET['object'].'/add_lang', $replace);
	}

	/**
	*/
	function lang_edit() {
		$id = intval($_GET['id']);
		if (!$id) {
			return _e('No id');
		}
		$a = db()->query_fetch('SELECT * FROM '.db('locale_langs').' WHERE id='.intval($_GET['id']));
		$a = (array)$_POST + (array)$a;
		$a['redirect_link'] = './?object='.$_GET['object'];
		return form($a, array('autocomplete' => 'off'))
			->validate(array(
				'name' => 'trim|required|is_unique_without[locale_langs.name.'.$id.']',
				'charset' => 'trim|required',
			))
			->db_update_if_ok('locale_langs', array('name','charset'), 'id='.$id)
			->on_after_update(function() {
				cache_del('locale_langs');
				common()->admin_wall_add(array('locale lang updated: '.$_POST['name'].'', $id));
			})
			->info('locale')
			->text('name')
			->text('charset')
			->save_and_back();
	}

	/**
	*/
	function lang_active() {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$info = db()->get('SELECT * FROM '.db('locale_langs').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($info) && !$info['is_default']) {
			db()->update('locale_langs', array('active' => intval(!$info['active'])), 'id='.intval($_GET['id']));
			common()->admin_wall_add(array('locale lang '.$info['name'].' '.($info['active'] ? 'inactivated' : 'activated'), $_GET['id']));
			cache_del(array('locale_langs'));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($info['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function lang_default() {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$info = db()->get('SELECT * FROM '.db('locale_langs').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($info) && !$info['is_default']) {
			db()->update('locale_langs', array('is_default' => 0), '1=1');
			db()->update('locale_langs', array('is_default' => 1), 'id='.intval($_GET['id']));
			common()->admin_wall_add(array('locale lang '.$info['name'].' made default', $_GET['id']));
			cache_del(array('locale_langs'));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($group_info['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function lang_delete() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			db()->query('DELETE FROM '.db('locale_langs').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			db()->query('DELETE FROM '.db('locale_translate').' WHERE locale="'._es($this->_cur_langs_array[$_GET['id']]['locale']).'"');
			common()->admin_wall_add(array('locale language deleted: '.$this->_cur_langs_array[$_GET['id']]['locale'], $_GET['id']));
		}
		cache_del('locale_langs');
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function show_vars() {
		if (array_key_exists('mass_delete', $_POST)) {
			return $this->mass_delete_vars();
		}
		$sql = 'SELECT v.id, v.value, IFNULL(GROUP_CONCAT(DISTINCT CONCAT("<b class=badge>", UPPER(t.locale), "</b> ", t.value) SEPARATOR "'.PHP_EOL.'<br>"), "") AS translation
			FROM '.db('locale_vars').' AS v 
			LEFT JOIN '.db('locale_translate').' AS t ON t.var_id = v.id
			LEFT JOIN '.db('locale_langs').' AS l ON t.locale = l.locale
			WHERE 1
				/*FILTER*/
			GROUP BY v.id
				/*ORDER*/';

		$filter_name = $_GET['object'].'__'.$_GET['action'];
		return table($sql, array(
				'pager_records_on_page' => $_SESSION[$filter_name]['per_page'],
				'filter' => $_SESSION[$filter_name],
				'filter_params' => array(
					'value'			=> function($a){ return ' v.value LIKE "%'._es($a['value']).'%" '; },
#					'value'			=> array('cond' => 'like', 'field' => 'v.value'),
					'translation'	=> array('like', 't.value'),
					'locale'		=> array('eq', 't.locale'),
				),
			))
			->check_box('id', array('width' => '1%', 'desc' => ''))
			->text('value', array('wordwrap' => '40', 'hl_filter' => 1))
			->text('translation', array('wordwrap' => '40', 'hl_filter' => 1))
			->btn_edit('', './?object='.$_GET['object'].'&action=edit_var&id=%d')
			->btn_delete('', './?object='.$_GET['object'].'&action=delete_var&id=%d')
			->footer_add('', './?object='.$_GET['object'].'&action=add_var')
			->footer_submit('mass_delete', array('icon' => 'icon-trash', 'class' => 'btn-danger'))
			->header_add('', './?object='.$_GET['object'].'&action=add_var')
#			->footer_link('collect_vars', './?object='.$_GET['object'].'&action=collect_vars')
#			->footer_link('cleanup_vars', './?object='.$_GET['object'].'&action=cleanup_vars')
		;
	}

	/**
	*/
	function add_var() {
		if (main()->is_post()) {
			$_POST['var_name'] = _strtolower(str_replace(' ', '_', $_POST['var_name']));
			$var_info = db()->get('SELECT * FROM '.db('locale_vars').' WHERE LOWER(REPLACE(CONVERT(value USING utf8), " ", "_")) = "'._es($_POST['var_name']).'"');
			if (!empty($_POST['var_name']) && empty($var_info)) {
				db()->insert('locale_vars', _es(array(
					'value' => $_POST['var_name']
				)));
				$INSERT_ID = db()->insert_id();
				common()->admin_wall_add(array('locale var added: '.$_POST['var_name'], $INSERT_ID));
			}
			if (empty($INSERT_ID) && !empty($var_info)) {
				$INSERT_ID = $var_info['id'];
			}
			if (!_ee()) {
				$sql = array();
				$cnames = array();
				foreach ((array)$this->_cur_langs_array as $info) {
					$tr_name = 'var_tr__'.$info['locale'];
					if (!isset($_POST[$tr_name])) {
						continue;
					}
					$sql[] = _es(array(
						'var_id'	=> (int)$INSERT_ID,
						'value'		=> $_POST[$tr_name],
						'locale'	=> $info['locale'],
					));
					$cnames[] = 'locale_translate_'.$info['locale'];
				}
				if ($sql && $INSERT_ID) {
					db()->insert('locale_translate', $sql);
					cache_del($cnames);
				}
				common()->admin_wall_add(array('locale var added: '.$_POST['var_name']));
				return js_redirect($INSERT_ID ? './?object='.$_GET['object'].'&action=edit_var&id='.intval($INSERT_ID) : './?object='.$_GET['object'].'&action=show_vars');
			}
		}
		$r = (array)$_POST + array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'back_link'		=> './?object='.$_GET['object'].'&action=show_vars',
		);
		$form = form($r)->text('var_name');
		foreach ((array)$this->_cur_langs_array as $info) {
			$form->textarea('var_tr__'.$info['locale'], $info['name']);
		}
		return $form->save_and_back();
	}

	/**
	*/
	function edit_var() {
		$_GET['id'] = trim($_GET['id']);
		// Try to find numeric id for the given string var
		if (!empty($_GET['id']) && !is_numeric($_GET['id'])) {
			$_GET['id'] = urldecode($_GET['id']);
			$var_info = db()->query_fetch(
				'SELECT * FROM '.db('locale_vars').' WHERE LOWER(REPLACE(CONVERT(value USING utf8), " ", "_")) = "'._es($_GET['id']).'"'
			);
			if ($var_info) {
				$_GET['id'] = $var_info['id'];
			} else {
				db()->INSERT('locale_vars', array(
					'value'	=> _es($_GET['id'])
				));
				$_GET['id'] = db()->INSERT_ID();
			}
		}
		$_GET['id'] = intval($_GET['id']);

		$var_info = db()->query_fetch('SELECT * FROM '.db('locale_vars').' WHERE id='.intval($_GET['id']));
		if (empty($var_info['id'])) {
			_re('No such var!', 'id');
			return _e();
		}

		$Q = db()->query('SELECT * FROM '.db('locale_translate').' WHERE var_id='.intval($var_info['id']));
		while ($A = db()->fetch_assoc($Q)) {
			$var_tr[$A['locale']] = $A['value'];
		}

		if (main()->is_post()) {
			if (!_ee()) {
				foreach ((array)$this->_cur_langs_array as $lang_id => $lang_info) {
					if (!isset($_POST[$lang_info['locale']])) {
						continue;
					}
					$sql_data = array(
						'var_id'	=> intval($var_info['id']),
						'value'		=> _es($_POST[$lang_info['locale']]),
						'locale'	=> _es($lang_info['locale']),
					);
					if (isset($var_tr[$lang_info['locale']])) {
						db()->UPDATE('locale_translate', $sql_data, 'var_id='.intval($var_info['id'])." AND locale='"._es($lang_info["locale"])."'");
					} else {
						db()->INSERT('locale_translate', $sql_data);
					}
					cache_del('locale_translate_'.$lang_info['locale']);
				}
				common()->admin_wall_add(array('locale var updated: '.$var_info['value'], $_GET['id']));
				return js_redirect('./?object='.$_GET['object'].'&action=show_vars');
			}
		}
		foreach ((array)$this->_cur_langs_array as $lang_id => $lang_info) {
			// Paste default value for the english locale (if translation is absent)
			$tr_value = !isset($var_tr[$lang_info['locale']]) && $lang_info['locale'] == 'en' ? $var_info['value'] : $var_tr[$lang_info['locale']];
			$langs[$lang_info['locale']] = array(
				'locale'	=> $lang_info['locale'],
				'name'		=> _prepare_html($lang_info['name']),
				'tr_value'	=> _prepare_html(trim($tr_value)),
			);
		}
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'back_link'		=> './?object='.$_GET['object'].'&action=show_vars',
			'error_message'	=> _e(),
			'langs'			=> $langs,
			'var_value'		=> _prepare_html($var_info['value']),
			'location'		=> $this->DISPLAY_VARS_LOCATIONS ? $this->_prepare_locations($var_info['location']) : '',
		);
		return tpl()->parse($_GET['object'].'/edit_var', $replace);
	}

	/**
	*/
	function mass_delete_vars() {
		$ids_to_delete = array();
		foreach ((array)$_POST['items'] as $_cur_id) {
			if (empty($_cur_id)) {
				continue;
			}
			$ids_to_delete[$_cur_id] = $_cur_id;
		}
		if (!empty($ids_to_delete)) {
			db()->query('DELETE FROM '.db('locale_vars').' WHERE id IN('.implode(',',$ids_to_delete).')');
			db()->query('DELETE FROM '.db('locale_translate').' WHERE var_id IN('.implode(',',$ids_to_delete).')');
			common()->admin_wall_add(array('locale vars mass deletion: '.implode(',',$ids_to_delete)));
		}
		return js_redirect('./?object='.$_GET['object'].'&action=show_vars');
	}

	/**
	*/
	function delete_var() {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$var_info = db()->query_fetch('SELECT * FROM '.db('locale_vars').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($var_info['id'])) {
			db()->query('DELETE FROM '.db('locale_vars').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			db()->query('DELETE FROM '.db('locale_translate').' WHERE var_id='.intval($_GET['id']));
			common()->admin_wall_add(array('locale var deleted: '.$var_info['value'], $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object'].'&action=show_vars');
		}
	}

	/**
	* Display list of user-specific vars
	*/
	function user_vars() {
		$cls = 'locale_editor'; return _class($cls.'_user_vars', 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
	}

	/**
	* Edit user var
	*/
	function user_var_edit() {
		$cls = 'locale_editor'; return _class($cls.'_user_vars', 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
	}

	/**
	* Delete user var
	*/
	function user_var_delete() {
		$cls = 'locale_editor'; return _class($cls.'_user_vars', 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
	}

	/**
	* Push user var into main traslation table
	*/
	function user_var_push($FORCE_ID = false) {
		$cls = 'locale_editor'; return _class($cls.'_user_vars', 'admin_modules/'.$cls.'/')->{__FUNCTION__}($FORCE_ID);
	}

	/**
	*/
	function import_vars() {
		$cls = 'locale_editor'; return _class($cls.'_import', 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
	}

	/**
	*/
	function export_vars() {
		$cls = 'locale_editor'; return _class($cls.'_export', 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
	}

	/**
	* Automatic translator via Google translate
	*/
	function autotranslate() {
		$cls = 'locale_editor'; return _class($cls.'_'.$func, 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
	}	

	/**
	* Return array of all used locations in vars
	*/
	function _get_all_vars_locations() {
// TODO: move out into submodule
		$used_locations = array();
		$Q = db()->query('SELECT * FROM '.db('locale_vars').'');
		while ($A = db()->fetch_assoc($Q)) {
			foreach ((array)explode(';', $A['location']) as $cur_location) {
				$cur_location = trim(substr($cur_location, 0, strpos($cur_location, ':')));
				if (empty($cur_location)) {
					continue;
				}
				$used_locations[$cur_location]++;
			}
		}
		if (!empty($used_locations)) ksort($used_locations);
		return $used_locations;
	}

	/**
	* Collect vars from source files (Framework included)
	*/
	function collect_vars () {
// TODO: move out into submodule
		// Select all known variables from db
		$Q = db()->query('SELECT * FROM '.db('locale_vars').' ORDER BY value ASC');
		while ($A = db()->fetch_assoc($Q)) {
			$this->_locale_vars[$A['value']] = $A;
		}
		// Try to get variables from the source code
		$vars_from_code = $this->_parse_source_code_for_vars();
		// Process vars and update or insert if records are outdated
		foreach ((array)$vars_from_code as $cur_var_name => $var_files_info) {
			$location_array = array();
			foreach ((array)$var_files_info as $file_name => $line_numbers) {
				$location_array[] = $file_name.':'.$line_numbers;
			}
			$location	= implode('; ', $location_array);
			$sql_array	= array(
				'value'		=> _es($cur_var_name),
				'location'	=> $location,
			);
			// If variable exists - use update
			if (isset($this->_locale_vars[$cur_var_name])) {
				db()->UPDATE('locale_vars', $sql_array, 'id='.intval($this->_locale_vars[$cur_var_name]['id']));
			} else {
				db()->INSERT('locale_vars', $sql_array);
			}
		}
		// Return user back
		js_redirect('./?object='.$_GET['object'].'&action=show_vars');
	}

	/**
	* Collect vars from source files, no framework, just project and given module name (internal use only method)
	*/
	function collect_vars_for_module () {
// TODO: move out into submodule
		main()->NO_GRAPHICS = true;

		$module_name = preg_replace('/[^a-z0-9\_]/i', '', strtolower(trim($_GET['id'])));
		if (!$module_name) {
			return print 'Error, no module name';
		}

		$vars = $this->_parse_source_code_for_vars(array(
			'only_project'	=> 1,
			'only_module'	=> $module_name,
		));

		echo '<pre>';
		foreach ((array)$vars as $var => $paths) {
			echo $var.PHP_EOL;
		}
		echo '</pre>';
	}

	/**
	* Cleanup variables (Delete not translated or missed vars)
	*/
	function cleanup_vars () {
		$cls = 'locale_editor'; return _class($cls.'_cleanup', 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
	}

	/**
	* Parse source code for translate variables
	*/
	function _parse_source_code_for_vars ($params = array()) {
// TODO: move out into submodule
		$vars_array = array();
		// Avail params: only_framework, only_project, only_stpls, only_php, only_module

		$php_path_pattern	= '';
		$stpl_path_pattern	= '';
		if ($params['only_module']) {
			$this->_include_php_pattern	= array('#/(modules)#', '#'.preg_quote($params['only_module'], '#').'\.class\.php$#');
			$this->_include_stpl_pattern	= array('#/templates#', '#\.stpl$#');
			$stpl_path_pattern = '#templates/[^/]+/'.$params['only_module'].'/#';
		}
		// Get source files from the framework
		if (!$params['only_project']) {
			if (!$params['only_stpls']) {
				$yf_framework_php_files	= _class('dir')->scan_dir(YF_PATH, true, $this->_include_php_pattern, $this->_exclude_pattern);
			}
			if (!$params['only_php']) {
				$yf_framework_stpl_files	= _class('dir')->scan_dir(YF_PATH, true, $this->_include_stpl_pattern, $this->_exclude_pattern);
			}
		}
		// Get source files from the current project
		if (!$params['only_framework']) {
			if (!$params['only_stpls']) {
				$cur_project_php_files		= _class('dir')->scan_dir(INCLUDE_PATH, true, $this->_include_php_pattern, $this->_exclude_pattern);
			}
			if (!$params['only_php']) {
				$cur_project_stpl_files		= _class('dir')->scan_dir(INCLUDE_PATH, true, $this->_include_stpl_pattern, $this->_exclude_pattern);
			}
		}
		// Get PHP files from the framework (classes and functions only)
		foreach ((array)$yf_framework_php_files as $file_name) {
			// Create short file name
			$short_file_name = str_replace(array(REAL_PATH, INCLUDE_PATH, YF_PATH), '', $file_name);
			// Merge vars
			foreach ((array)$this->_get_vars_from_file_name($file_name, $this->_translate_php_pattern) as $cur_var_name => $code_lines) {
				$vars_array[$cur_var_name][$short_file_name] = $code_lines;
			}
		}
		// Get PHP files from the current project (classes and functions only)
		foreach ((array)$cur_project_php_files as $file_name) {
			// Create short file name
			$short_file_name = str_replace(array(REAL_PATH, INCLUDE_PATH, YF_PATH), '', $file_name);
			// Merge vars
			foreach ((array)$this->_get_vars_from_file_name($file_name, $this->_translate_php_pattern) as $cur_var_name => $code_lines) {
				$vars_array[$cur_var_name][$short_file_name] = $code_lines;
			}
		}
		// Get STPL files from the framework
		foreach ((array)$yf_framework_stpl_files as $file_name) {
			// Create short file name
			$short_file_name = str_replace(array(REAL_PATH, INCLUDE_PATH, YF_PATH), '', $file_name);
			// Merge vars
			foreach ((array)$this->_get_vars_from_file_name($file_name, $this->_translate_stpl_pattern) as $cur_var_name => $code_lines) {
				$vars_array[$cur_var_name][$short_file_name] = $code_lines;
			}
		}
		// Get STPL files from the current project
		foreach ((array)$cur_project_stpl_files as $file_name) {
			// Create short file name
			$short_file_name = str_replace(array(REAL_PATH, INCLUDE_PATH, YF_PATH), '', $file_name);
			if ($stpl_path_pattern && !preg_match($stpl_path_pattern, $short_file_name)) {
				continue;
			}
			// Merge vars
			foreach ((array)$this->_get_vars_from_file_name($file_name, $this->_translate_stpl_pattern) as $cur_var_name => $code_lines) {
				$vars_array[$cur_var_name][$short_file_name] = $code_lines;
			}
		}
		ksort($vars_array);
		return $vars_array;
	}

	/**
	* Get vars from the given file name
	*/
	function _get_vars_from_file_name($file_name = '', $pattern = '') {
// TODO: move out into submodule
		$vars_array = array();
		if (empty($file_name)) {
			return $vars_array;
		}
		// Get file source as array
		$file_source_array = file($file_name);
		$match	= preg_match_all($pattern, implode(PHP_EOL, $file_source_array), $matches);
		// Skip files with no translate vars
		if (empty($matches[0])) {
			return $vars_array;
		}
		// Add variable
		foreach ((array)$matches[2] as $match_number => $cur_var_name) {
			$code_lines		= array();
			$cur_var_name	= trim($cur_var_name, "\"'");
			foreach ((array)$file_source_array as $line_number => $line_text) {
				if (false === strpos($line_text, $matches[0][$match_number])) {
					continue;
				}
				$code_lines[] = $line_number;
			}
			if (empty($code_lines) || empty($cur_var_name)) {
				continue;
			}
			$vars_array[$cur_var_name] = implode(',',$code_lines);
		}
		return $vars_array;
	}

	/**
	* Prepare locations text
	*/
	function _prepare_locations($source_text = '') {
// TODO: move out into submodule
		if (empty($source_text)) {
			return false;
		}
		// Do not process links if turned off
		if (!$this->LOCATIONS_EDIT_LINKS) {
			return _prepare_html($source_text);
		}
		// Try to find separate links
		$body = array();
		foreach ((array)explode(';', $source_text) as $cur_source) {
			$cur_file_name = trim(substr($cur_source, 0, strpos($cur_source, ':')));
			$path_to = '';
			// Try to find real file
			if (file_exists(REAL_PATH.$cur_file_name)) {
				$path_to = REAL_PATH;
			} elseif (file_exists(INCLUDE_PATH.$cur_file_name)) {
				$path_to = INCLUDE_PATH;
			} elseif (file_exists(YF_PATH.$cur_file_name)) {
				$path_to = YF_PATH;
			}
			// Check if file is found
			if (empty($path_to)) {
				$body[] = $cur_source;
			} else {
				$replace = array(
					'link'	=> './?object=file_manager&action=edit_item&f_='.basename($cur_file_name).'&dir_name='.urlencode($path_to.dirname($cur_file_name)),
					'text'	=> _prepare_html($cur_source),
				);
				$body[] = tpl()->parse($_GET['object'].'/location_item', $replace);
			}
		}
		return !empty($body) ? nl2br(implode(';'.PHP_EOL, $body)) : _prepare_html($source_text);
	}

	/**
	* Create empty vars for the default language
	*/
	function _create_empty_vars_for_locale($force_locale = '') {
		$def_locale = 'en';
		if (!empty($force_locale)) {
			$locale = $force_locale;
		} else {
			// Try to find default locale
			foreach ((array)$this->_cur_langs_array as $A) {
				if ($A['is_default']) {
					$locale = $A['locale'];
					break;
				}
			}
			if (empty($locale)) {
				$locale = $def_locale;
			}
		}
		// Check if we found default locale
		if (!empty($locale)) {
			// Select all known variables from db
			$Q = db()->query("SELECT * FROM ".db('locale_vars')." WHERE id NOT IN(SELECT var_id FROM ".db('locale_translate')." WHERE locale='"._es($locale)."')");
			while ($A = db()->fetch_assoc($Q)) {
				// Do create empty records
				db()->INSERT('locale_translate', array(
					'var_id'	=> $A['id'],
					'value'		=> '',
					'locale'	=> $locale,
				));
			}
		}
	}

	/**
	* Some of the common languages with their English and native names
	* Based on ISO 639 and http://people.w3.org/rishida/names/languages.html
	*/
	function _get_iso639_list() {
		$cls = 'locale_editor'; return _class($cls.'_langs', 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
	}

	/**
	* Do get current languages from db
	*/
	function _get_locales () {
		$Q = db()->query('SELECT * FROM '.db('locale_langs').' ORDER BY is_default DESC, locale ASC');
		while ($A = db()->fetch_assoc($Q)) {
			$data[$A['locale']] = $A['name'];
		}
		return $data;
	}

	/**
	*/
	function filter_save() {
		$filter_name = $_GET['object'].'__show_vars';
		if ($_GET['page'] == 'clear') {
			$_SESSION[$filter_name] = array();
		} else {
			$_SESSION[$filter_name] = $_POST;
			foreach (explode('|', 'clear_url|form_id|submit') as $f) {
				if (isset($_SESSION[$filter_name][$f])) {
					unset($_SESSION[$filter_name][$f]);
				}
			}
		}
		return js_redirect('./?object='.$_GET['object'].'&action='. str_replace ($_GET['object'].'__', '', $filter_name));
	}

	/**
	*/
	function _show_filter() {
		if (!in_array($_GET['action'], array('show_vars'))) {
			return false;
		}
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$r = array(
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
		);
		$order_fields = array(
			'v.value'     => 'value',
		);
		$langs_for_select = $this->_langs_for_search;
		$per_page = array('' => '', 10 => 10, 20 => 20, 50 => 50, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000, 2000 => 2000, 5000 => 5000);
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
//				'class'		=> 'form-inline',
			))
			->text('value', 'Source var')
			->text('translation')
			->select_box('locale', $langs_for_select)
			->select_box('per_page', $per_page)
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}

	/**
	*/
	function _box ($name = '', $selected = '') {
		if (empty($name) || empty($this->_boxes[$name])) {
			return false;
		} else {
			return eval('return common()->'.$this->_boxes[$name].';');
		}
	}

	/**
	*/
	function _hook_widget__installed_locales ($params = array()) {
// TODO
	}

	/**
	*/
	function _hook_widget__locale_stats ($params = array()) {
// TODO
	}

	/**
	*/
	function _hook_widget__latest_locale_vars ($params = array()) {
// TODO
	}

}
