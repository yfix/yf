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
	/** @var bool Filter on/off */
// TODO: implement new filters, based on form2/table2
#	public $USE_FILTER				= true;
	public $USE_FILTER				= false;
	/** @var bool Ignore case on import/export */
	public $VARS_IGNORE_CASE		= true;

	/**
	* Framework constructor
	*/
	function _init () {
		$this->_std_trigger = array(
			'1' => '<span class="positive">YES</span>',
			'0' => '<span class="negative">NO</span>',
		);
		$this->_boxes = array(
			'lang_code'		=> 'select_box("lang_code",		$this->_langs,			$selected, false, 2, "", false)',
			'cur_langs'		=> 'select_box("lang_code",		$this->_cur_langs,		$selected, false, 2, "", false)',
			'file_format'	=> 'radio_box("file_format",	$this->_file_formats,	$selected, true, 2, "", false)',
			'mode'			=> 'radio_box("mode",			$this->_modes,			$selected, true, 2, "", false)',
			'search_type'	=> 'radio_box("search_type",	$this->_search_types,	$selected, false, 2, "", false)',
			'location'		=> 'select_box("location",		$this->_used_locations,	$selected, false, 2, "", false)',
			'module'		=> 'select_box("module",		$this->_modules,		$selected, false, 2, "", false)',
		);
		$this->_user_modules	= module('user_modules')->_get_modules(array('with_sub_modules' => 1));

		$tmp_admin_modules		= module('admin_modules')->_get_modules(array('with_sub_modules' => 1));
		$this->_admin_modules_prefix = 'admin___';
		foreach ((array)$tmp_admin_modules as $module_name) {
			$this->_admin_modules[$this->_admin_modules_prefix.$module_name] = $module_name;
		}
		$tmp_user_modules = $this->_user_modules;
		unset($tmp_user_modules['']);

		$this->_modules[''] = t('-- ALL --');
		if (!empty($this->_admin_modules)) {
			$this->_modules['admin'] = $this->_admin_modules;
		}
		if (!empty($tmp_user_modules)) {
			$this->_modules['user'] = $tmp_user_modules;
		}

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

		$this->_langs_for_search['all'] = t('All languages');
		foreach ((array)$this->_cur_langs_array as $A) {
			$this->_langs_for_search[$A['locale']] = t($A['name']);
			$this->_cur_langs[$A['locale']] = t($A['name']);
		}
		$this->_search_in = array(
			'all'			=> t('All strings in that language'),
			'translated'	=> t('Only translated strings'),
			'untranslated'	=> t('Only untranslated strings'),
		);
		$this->_file_formats = array(
			'csv'	=> t('CSV, compatible with MS Excel'),
			'xml'	=> t('XML'),
		);
		$this->_modes = array(
			1	=> t('Strings in the uploaded file replace existing ones, new ones are added'),
			2	=> t('Existing strings are kept, only new strings are added'),
		);
		$this->_search_types = array(
			'vars'			=> t('Vars'),
			'translations'	=> t('Translations'),
		);
#		if ($this->USE_FILTER) {
#			$this->_prepare_filter_data();
#		}
	}

	/**
	* Display all project languages
	*/
	function show() {
		if ($_POST) {
			foreach ((array)$this->_cur_langs_array as $A) {
				db()->UPDATE('locale_langs', array(
					'name'			=> _es($_POST['name_'.$A['id']]),
					'charset'		=> _es($_POST['charset_'.$A['id']]),
					'active'		=> intval((bool)$_POST['active_'.$A['id']]),
					'is_default'	=> intval($_POST['default'] == $A['id']),
				), 'id='.intval($A['id']));
			}
			cache()->refresh('locale_langs');
			return js_redirect('./?object='.$_GET['object'].'&action='.$_GET['action']);
		}
		$Q = db()->query('SELECT COUNT(var_id) AS num,locale FROM '.db('locale_translate').' WHERE value != "" GROUP BY locale');
		while ($A = db()->fetch_assoc($Q)) {
			$tr_vars[$A['locale']] = $A['num'];
		}
		list($total_vars) = db()->query_fetch('SELECT COUNT(id) AS `0` FROM '.db('locale_vars').'');
		foreach ((array)$this->_cur_langs_array as $A) {
			$tr_stats = !empty($total_vars) && !empty($tr_vars[$A['locale']]) ? round(100 * $tr_vars[$A['locale']] / $total_vars, 2) : 0;
			$replace2 = array(
				'bg_class'		=> !(++$i % 2) ? 'bg1' : 'bg2',
				'id'			=> intval($A['id']),
				'code'			=> _prepare_html($A['locale']),
				'name'			=> _prepare_html($A['name']),
				'charset'		=> _prepare_html($A['charset']),
				'tr_stats'		=> $tr_stats,
				'tr_stats_int'	=> intval($tr_stats),
				'tr_vars'		=> intval($tr_vars[$A['locale']]),
				'total_vars'	=> intval($total_vars),
				'active'		=> intval($A['active']),
				'is_default'	=> intval($A['is_default']),
				'delete_link'	=> './?object='.$_GET['object'].'&action=delete_lang&id='.$A['id'],
			);
			$items .= tpl()->parse($_GET['object'].'/langs_item', $replace2);
		}
		$replace = array(
			'items'				=> $items,
			'form_action'		=> './?object='.$_GET['object'].'&action='.$_GET['action'],
			'show_vars_link'	=> './?object='.$_GET['object'].'&action=show_vars',
			'add_lang_link'		=> './?object='.$_GET['object'].'&action=add_lang',
			'import_vars_link'	=> './?object='.$_GET['object'].'&action=import_vars',
			'export_vars_link'	=> './?object='.$_GET['object'].'&action=export_vars',
		);
		return tpl()->parse($_GET['object'].'/langs_main', $replace);
	}

	/**
	*/
	function add_lang() {
		foreach ((array)$this->_cur_langs_array as $A) {
			if (isset($this->_langs[$A['locale']])) {
				unset($this->_langs[$A['locale']]);
			}
		}
		if ($_POST) {
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
				cache()->refresh('locale_langs');
				return js_redirect('./?object='.$_GET['object']);
			}
		}
		$replace = array(
			'form_action'		=> './?object='.$_GET['object'].'&action='.$_GET['action'],
			'langs_box'			=> $this->_box('lang_code',	-1),
			'back_link'			=> './?object='.$_GET['object'],
			'error_message'		=> _e(),
		);
		return tpl()->parse($_GET['object'].'/add_lang', $replace);
	}

	/**
	*/
	function delete_lang() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			db()->query('DELETE FROM '.db('locale_langs').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			db()->query('DELETE FROM '.db('locale_translate').' WHERE locale="'._es($this->_cur_langs_array[$_GET['id']]['locale']).'"');
			common()->admin_wall_add(array('locale language deleted: '.$this->_cur_langs_array[$_GET['id']]['locale'], $_GET['id']));
		}
		cache()->refresh('locale_langs');
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
#		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : '';
		$sql = 'SELECT * FROM '.db('locale_vars').' AS v WHERE 1=1 '.$filter_sql.' ORDER BY '.($this->VARS_IGNORE_CASE ? 'LOWER(REPLACE(CONVERT(v.value USING utf8), " ", "_"))' : 'v.value').' ASC';

		$path = './?object='.$_GET['object'].'&action='.$_GET['action'];
		$per_page = conf('admin_per_page');
#		if ($this->USE_FILTER && !empty($_SESSION[$this->_filter_name]['per_page'])) {
#			$per_page = $_SESSION[$this->_filter_name]['per_page'];
#		}
		list($limit_sql, $pages, $total) = common()->divide_pages($sql, $path, null, $per_page);

		$Q = db()->query($sql. $limit_sql);
		while ($A = db()->fetch_assoc($Q)) $vars_array[$A['id']] = $A;

		if (!empty($vars_array)) {
			$Q = db()->query('SELECT * FROM '.db('locale_translate').' WHERE var_id IN('.implode(',', array_keys($vars_array)).')');
			while ($A = db()->fetch_assoc($Q)) $tr_vars[$A['var_id']][$A['locale']] = $A['value'];
			foreach ((array)$tr_vars as $var_id => $locales_array) {
				$body_array = array();
				foreach ((array)$locales_array as $locale_name => $tr_text) {
					if (empty($tr_text)) {
						continue;
					}
					$body_array[] = tpl()->parse($_GET['object'].'/locale_item', array(
						'name'		=> _prepare_html($locale_name),
						'is_empty'	=> empty($tr_text) ? 1 : 0,
					));
				}
				$used_locales[$var_id] = !empty($body_array) ? nl2br(implode(PHP_EOL, $body_array)) : '';
			}
		}
#		if (isset($_SESSION[$this->_filter_name]['show_locs']) && !$_SESSION[$this->_filter_name]['show_locs']) {
#			$this->DISPLAY_VARS_LOCATIONS = false;
#		}
		foreach ((array)$vars_array as $A) {
			$replace2 = array(
				'id'			=> $A['id'],
				'bg_class'		=> !(++$i % 2) ? 'bg1' : 'bg2',
				'value'			=> _prepare_html(str_replace('_', ' ', $A['value'])),
				'location'		=> $this->DISPLAY_VARS_LOCATIONS ? $this->_prepare_locations($A['location']) : '',
				'locales'		=> $used_locales[$A['id']],
				'edit_link'		=> './?object='.$_GET['object'].'&action=edit_var&id='.$A['id'],
				'delete_link'	=> './?object='.$_GET['object'].'&action=delete_var&id='.$A['id'],
			);
			$items .= tpl()->parse($_GET['object'].'/vars_item', $replace2);
		}
		$replace = array(
			'mass_delete_link'	=> './?object='.$_GET['object'].'&action=mass_delete_vars',
			'back_link'			=> './?object='.$_GET['object'],
			'add_link'			=> './?object='.$_GET['object'].'&action=add_var',
			'collect_vars_link'	=> './?object='.$_GET['object'].'&action=collect_vars',
			'cleanup_vars_link'	=> './?object='.$_GET['object'].'&action=cleanup_vars',
			'import_vars_link'	=> './?object='.$_GET['object'].'&action=import_vars',
			'export_vars_link'	=> './?object='.$_GET['object'].'&action=export_vars',
			'user_vars_link'	=> './?object='.$_GET['object'].'&action=user_vars',
			'items'				=> $items,
			'pages'				=> $pages,
			'total'				=> intval($total),
		);
		return tpl()->parse($_GET['object'].'/vars_main', $replace);
	}

	/**
	*/
	function add_var() {
		if (!empty($_POST['var_name'])) {
			$_POST['var_name'] = _strtolower(str_replace(' ', '_', $_POST['var_name']));
			$var_info = db()->query_fetch(
				'SELECT * FROM '.db('locale_vars').' WHERE LOWER(REPLACE(CONVERT(value USING utf8), " ", "_")) = "'._es($_POST['var_name']).'"'
			);
		}
		if (!empty($_POST['var_name']) && empty($var_info)) {
			db()->INSERT('locale_vars', array(
				'value'	=> _es($_POST['var_name'])
			));
			$INSERT_ID = db()->INSERT_ID();
			common()->admin_wall_add(array('locale var added: '.$_POST['var_name'], $INSERT_ID));
		}
		if (empty($INSERT_ID) && !empty($var_info)) {
			$INSERT_ID = $var_info['id'];
		}
		return js_redirect($INSERT_ID ? './?object='.$_GET['object'].'&action=edit_var&id='.intval($INSERT_ID) : './?object='.$_GET['object'].'&action=show_vars');
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
		while ($A = db()->fetch_assoc($Q)) $var_tr[$A['locale']] = $A['value'];

		if ($_POST) {
			if (!common()->_error_exists()) {
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
					cache()->refresh('locale_translate_'.$lang_info['locale']);
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
		if (isset($_GET['id']) && !isset($_GET['page'])) {
			$_GET['page'] = $_GET['id'];
			$_GET['id'] = null;
		}
		// Group actions here
		if (!empty($_POST)) {
			if (isset($_POST['multi-push'])) {
				foreach ((array)$_POST['items'] as $_id) {
					$_id = intval($_id);
					if (!empty($_id)) {
						$this->user_var_push($_id);
					}
				}
			}
			return js_redirect('./?object='.$_GET['object'].'&action=user_vars'. _add_get());
		}

		$sql = 'SELECT * FROM '.db('locale_user_tr').'';
// TODO: add filter here with sorting selection, user id, etc
		$sql .= strlen($filter_sql) ? ' WHERE 1 '. $filter_sql : ' ORDER BY user_id DESC, name ASC';

		list($add_sql, $pages, $total) = common()->divide_pages($sql, '', '', 100);

		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$data[$A['id']] = $A;
			if ($A['user_id']) {
				$users_ids[$A['user_id']] = intval($A['user_id']);
			}
			if (strlen($A['name'])) {
				$vars_names[$A['name']] = $A['name'];
			}
		}
		if (!empty($users_ids)) {
			$Q = db()->query('SELECT * FROM '.db('user').' WHERE id IN('.implode(',', $users_ids).')');
			while ($A = db()->fetch_assoc($Q)) {
				$users_names[$A['id']] = $A['email'];
			}
		}
		// Check if var exists in the global table
		$global_vars = array();
		if (!empty($vars_names)) {
			foreach ((array)db()->query_fetch_all('SELECT * FROM '.db('locale_vars')." WHERE value IN('".implode("','", $vars_names)."')") as $A) {
				$global_vars[$A['value']] = $A['id'];
			}
		}

		$color_exists	= '#ff5';

		foreach ((array)$data as $A) {
			$var_bg_color = '';
			$global_var_exists	= isset($global_vars[_strtolower(str_replace(' ', '_', $A['name']))]);
			if ($global_var_exists) {
				$var_bg_color = $color_exists;
			}
			$items[] = array(
				'id'			=> $A['id'],
				'bg_class'		=> $i++ % 2 ? 'bg1' : 'bg2',
				'id'			=> intval($A['id']),
				'user_id'		=> intval($A['user_id']),
				'user_name'		=> _prepare_html($users_names[$A['user_id']]),
				'user_link'		=> _profile_link($A['user_id']),
				'name'			=> _prepare_html(str_replace('_', ' ', $A['name'])),
				'translation'	=> _prepare_html($A['translation']),
				'locale'		=> _prepare_html($A['locale']),
				'site_id'		=> intval($A['site_id']),
				'last_update'	=> _format_date($A['last_update'], 'long'),
				'global_exists'	=> (int)$global_var_exists,
				'var_bg_color'	=> $var_bg_color,
				'active'		=> intval($A['active']),
				'edit_url'		=> './?object='.$_GET['object'].'&action=user_var_edit&id='.$A['id'],
				'delete_url'	=> './?object='.$_GET['object'].'&action=user_var_delete&id='.$A['id'],
				'push_url'		=> './?object='.$_GET['object'].'&action=user_var_push&id='.$A['id'],
			);
		}
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action']. ($_GET['id'] ? '&id='.$_GET['id'] : ''),
			'error'			=> _e(),
			'items'			=> $items,
			'pages'			=> $pages,
			'total'			=> $total,
			'show_vars_link' => './?object='.$_GET['object'].'&action=show_vars',
		);
		return tpl()->parse($_GET['object'].'/user_vars_main', $replace);
	}

	/**
	* Edit user var
	*/
	function user_var_edit() {
		$_GET['id'] = intval($_GET['id']);
		$A = db()->query_fetch('SELECT * FROM '.db('locale_user_tr').' WHERE id='.intval($_GET['id']));
		if (!$A) {
			return _e('No id');
		}
		if (!empty($_POST)) {
			db()->UPDATE('locale_user_tr', array(
				'name'			=> _es($_POST['name']),
				'translation'	=> _es($_POST['translation']),
				'last_update'	=> time(),
			), 'id='.intval($_GET['id']));
			return js_redirect('./?object='.$_GET['object'].'&action=user_vars');
		}
		$DATA = my_array_merge($A, $_POST);

		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action']. ($_GET['id'] ? '&id='.$_GET['id'] : ''),
			'back_url'		=> process_url('./?object='.$_GET['object'].'&action=user_vars'),
			'error'			=> _e(),
			'for_edit'		=> 1,
			'id'			=> _prepare_html($DATA['id']),
			'user_id'		=> _prepare_html($DATA['user_id']),
			'name'			=> _prepare_html($DATA['name']),
			'translation'	=> _prepare_html($DATA['translation']),
			'locale'		=> _prepare_html($DATA['locale']),
			'site_id'		=> _prepare_html($DATA['site_id']),
		);
		return tpl()->parse($_GET['object'].'/user_vars_edit', $replace);
	}

	/**
	* Delete user var
	*/
	function user_var_delete() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			db()->query('DELETE FROM '.db('locale_user_tr').' WHERE id='.intval($_GET['id']));
		}
		// Return user back
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.$_GET['object'].'&action=user_vars'. _add_get());
		}
	}

	/**
	* Push user var into main traslation table
	*/
	function user_var_push($FORCE_ID = false) {
		$_GET['id'] = intval($FORCE_ID ? $FORCE_ID : $_GET['id']);
		$A = db()->query_fetch('SELECT * FROM '.db('locale_user_tr').' WHERE id='.intval($_GET['id']));
		if (!$A) {
			return _e('No id');
		}
		$VAR_NAME	= $A['name'];
		if ($this->VARS_IGNORE_CASE) {
			$VAR_NAME = str_replace(' ', '_', _strtolower($VAR_NAME));
		}
		if (!strlen($VAR_NAME)) {
			return _e('Empty var name');
		}
		$CUR_LOCALE = $A['locale'];
		if (!$CUR_LOCALE) {
			return _e('Empty var locale');
		}
		$EDITED_VALUE = $A['translation'];
		if (!strlen($EDITED_VALUE)) {
			return _e('Empty var translation');
		}
		// Get main translation var (if exists)
		$var_info = db()->query_fetch('SELECT * FROM '.db('locale_vars').' WHERE value="'._es($VAR_NAME).'"');
		if (!$var_info) {
			$var_info = array(
				'value'		=> _es($VAR_NAME),
				'location'	=> '',
			);
			db()->INSERT('locale_vars', $var_info);
			$var_id = db()->INSERT_ID();
			if ($var_id) {
				$var_info['id'] = $var_id;
			}
		}
		if (!$var_info['id']) {
			return _e('No locale var id');
		}
		$sql_data = array(
			'var_id'	=> intval($var_info['id']),
			'value'		=> _es($EDITED_VALUE),
			'locale'	=> _es($CUR_LOCALE),
		);
		// Get translation for the current locale
		$Q = db()->query('SELECT * FROM '.db('locale_translate').' WHERE var_id='.intval($var_info['id']));
		while ($A = db()->fetch_assoc($Q)) {
			$var_tr[$A['locale']] = $A['value'];
		}
		if (isset($var_tr[$CUR_LOCALE])) {
			db()->UPDATE('locale_translate', $sql_data, 'var_id='.intval($var_info['id']).' AND locale="'._es($CUR_LOCALE).'"');
		} else {
			db()->INSERT('locale_translate', $sql_data);
		}
		return $FORCE_ID ? '' : js_redirect('./?object='.$_GET['object'].'&action=user_vars');
	}

	/**
	*/
	function import_vars() {
		if ($_POST) {
			if (empty($_FILES['import_file']['name'])) {
				_re('Please select file to process', 'name');
			}
			if (empty($_POST['file_format']) || !isset($this->_file_formats[$_POST['file_format']])) {
				_re('Please select file format', 'file_format');
			}
			$cur_locale = $_POST['lang_code'];
			if (empty($cur_locale)) {
				_re('Please select language', 'lang');
			}
			$raw_langs = $this->_get_iso639_list();
			if (!isset($raw_langs[$cur_locale])) {
				common()->_error_exists('Wrong language code');
			}
			if (!common()->_error_exists() && !isset($this->_cur_langs[$cur_locale])) {
				if (!common()->_error_exists()) {
					db()->INSERT('locale_langs', array(
						'locale'		=> _es($cur_locale),
						'name'			=> _es($raw_langs[$cur_locale][0]),
						'charset'		=> _es('utf-8'),
						'active'		=> 1,
						'is_default'	=> 0,
					));
					$this->_create_empty_vars_for_locale($cur_locale);
					cache()->refresh('locale_langs');
				}
			}
			$file_format = $_POST['file_format'];
			$IMPORT_MODE = !empty($_POST['mode']) ? intval($_POST['mode']) : 1;
			if (!common()->_error_exists()) {
				$new_file_name = $_FILES['import_file']['name'];
				$new_file_path = INCLUDE_PATH.$new_file_name;
				move_uploaded_file($_FILES['import_file']['tmp_name'], $new_file_path);

				if ($file_format == 'csv') {

					$handle = fopen($new_file_path, 'r');
					while (($data = fgetcsv($handle, 2048, ";", "\"")) !== false) {
						if ($i++ == 0) continue; // Skip header
						$found_vars[trim($data[0])] = trim($data[1]);
					}
					fclose($handle);

				} elseif ($file_format == 'xml') {

					$xml_parser = xml_parser_create();
					xml_parse_into_struct($xml_parser, file_get_contents($new_file_path), $xml_values);
					foreach ((array)$xml_values as $k => $v) {
						if ($v['type'] != 'complete') continue;
						if ($v['tag'] == 'SOURCE') {
							$source = $v['value'];
						}
						if ($v['tag'] == 'TRANSLATION') {
							$translation = $v['value'];
						}
						if (!empty($source) && !empty($translation)) {
							$found_vars[trim($source)] = trim($translation);
							$source			= '';
							$translation	= '';
						}
					}
					xml_parser_free($xml_parser);
				}
				$Q = db()->query("SELECT id, ".($this->VARS_IGNORE_CASE ? "LOWER(REPLACE(CONVERT(value USING utf8), ' ', '_'))" : "value")." AS val FROM ".db('locale_vars')." ORDER BY val ASC");
				while ($A = db()->fetch_assoc($Q)) $cur_vars_array[$A["id"]] = $A["val"];

				$Q = db()->query("SELECT * FROM ".db('locale_translate')." WHERE locale = '"._es($cur_locale)."'");
				while ($A = db()->fetch_assoc($Q)) $cur_tr_vars[$A["var_id"]] = $A["value"];

				foreach ((array)$found_vars as $source => $translation) {
					$var_id = 0;
					if ($this->VARS_IGNORE_CASE) {
						$source = str_replace(" ", "_", strtolower($source));
					}
					foreach ((array)$cur_vars_array as $cur_var_id => $cur_var_value) {
						if ($cur_var_value == $source) {
							$var_id = intval($cur_var_id);
							break;
						}
					}
					if (empty($var_id)) {
						db()->INSERT("locale_vars", array("value"	=> _es($source)));
						$var_id = db()->INSERT_ID();
					}
					$sql_array = array(
						"var_id"	=> intval($var_id),
						"locale"	=> _es($cur_locale),
						"value"		=> _es($translation),
					);
					if (isset($cur_tr_vars[$var_id])) {
						if ($IMPORT_MODE == 2 || $translation == $cur_tr_vars[$var_id]) continue;
						db()->UPDATE("locale_translate", $sql_array, "var_id=".intval($var_id)." AND locale='"._es($cur_locale)."'");
					} else {
						db()->INSERT("locale_translate", $sql_array);
					}
				}
				unlink($new_file_path);
				cache()->refresh("locale_translate_".$cur_locale);
				return js_redirect("./?object=".$_GET["object"]."&action=show_vars");
			}
		}
		if (!$_POST || common()->_error_exists()) {
			$replace = array(
				"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
				"back_link"			=> "./?object=".$_GET["object"],
				"error_message"		=> _e(),
				"langs_box"			=> $this->_box("lang_code",		-1),
				"file_formats_box"	=> $this->_box("file_format",	"csv"),
				"modes_box"			=> $this->_box("mode",			1),
			);
			return tpl()->parse($_GET["object"]."/import_vars", $replace);
		}
	}

	/**
	* Export vars
	*/
	function export_vars() {
		// Do save data
		if ($_POST) {
			// Check file format
			if (empty($_POST["file_format"]) || !isset($this->_file_formats[$_POST["file_format"]])) {
				_re(t("Please select file format"));
			}
			$IS_TEMPLATE = intval((bool)$_POST["is_template"]);
			// Check language code
			if (empty($_POST["lang_code"]) && !$IS_TEMPLATE) {
				_re(t("Please select language to export"));
			}
			// Prepare lang info
			$cur_locale = !empty($_POST["lang_code"]) ? $_POST["lang_code"] : "en";
			$cur_lang_info = array(
				"locale"	=> $cur_locale,
				"name"		=> $this->_cur_langs[$cur_locale],
			);
			// Get translations
			if (!$IS_TEMPLATE) {
				$Q = db()->query("SELECT * FROM ".db('locale_translate')." WHERE locale = '"._es($cur_locale)."'");
				while ($A = db()->fetch_assoc($Q)) $tr_vars[$A["var_id"]] = $A["value"];
			}
			// Get vars
			$Q = db()->query("SELECT * FROM ".db('locale_vars')." ORDER BY value ASC");
			while ($A = db()->fetch_assoc($Q)) {
				$source			= $A["value"];
				$translation	= $IS_TEMPLATE ? $A["value"] : $tr_vars[$A["id"]];
				// Skip not translated vars
				if (!$IS_TEMPLATE && empty($translation)) continue;
				// Export only for specified location
				if (!$IS_TEMPLATE && !empty($_POST["location"]) && (false === strpos($A["location"], $_POST["location"]))) {
					continue;
				}
				// Export only for specified module
				if (!empty($_POST["module"])) {
					$is_admin_module = false;
					// Admin module
					if (substr($_POST["module"], 0, strlen($this->_admin_modules_prefix)) == $this->_admin_modules_prefix) {
						$_POST["module"] = substr($_POST["module"], strlen($this->_admin_modules_prefix));
						$is_admin_module = true;
					}
					// Check for file name
					if ((false === strpos($A["location"], ($is_admin_module ? ADMIN_MODULES_DIR : USER_MODULES_DIR).$_POST["module"].".class.php"))
						&& (false === strpos($A["location"], "/".$_POST["module"]."/") || false === strpos($A["location"], ".stpl"))
					) continue;
				}
				// Prepare array for export
				$tr_array[$A["id"]] = array(
					"source"		=> trim($source),
					"translation"	=> trim($translation),
				);
			}
			// Check for errors
			if (!common()->_error_exists()) {
				// Get vars to export
				if ($_POST["file_format"] == "csv") {
					$body .= "source;translation\r\n";
					// Process vars
					foreach ((array)$tr_array as $info) {
						$body .= "\"".str_replace("\"","\"\"",$info["source"])."\";\"".
							str_replace("\"","\"\"",$info["translation"])."\"\r\n";
					}
					// Generate result file_name
					$file_name = $cur_lang_info["locale"]."_translation.csv";
				} elseif ($_POST["file_format"] == "xml") {
					// Generate XML string
					$body .= "<!DOCTYPE tr><tr>\r\n";
					$body .= "\t<info>\r\n";
					$body .= "\t\t<locale>"._prepare_html($cur_lang_info["locale"])."</locale>\r\n";
					$body .= "\t\t<lang_name>"._prepare_html($cur_lang_info["name"])."</lang_name>\r\n";
					$body .= "\t</info>\r\n";
					// Process vars
					foreach ((array)$tr_array as $info) {
						$body .= "\t<message>\r\n";
						$body .= "\t\t<source>"._prepare_html($info["source"])."</source>\r\n";
						$body .= "\t\t<translation>"._prepare_html($info["translation"])."</translation>\r\n";
						$body .= "\t</message>\r\n";
					}
					$body .= "</tr>";
					// Generate result file_name
					$file_name = $cur_lang_info["locale"]."_translation.xml";
				}
			}
			if (!common()->_error_exists()) {
				if (empty($body)) {
					_re(t("Error while exporting data"));
				}
			}
			if (!common()->_error_exists()) {
				main()->NO_GRAPHICS = true;

				header("Content-Type: application/force-download; name=\"".$file_name."\"");
				header("Content-Type: text/".$_POST["file_format"].";charset=utf-8");
				header("Content-Transfer-Encoding: binary");
				header("Content-Length: ".strlen($body));
				header("Content-Disposition: attachment; filename=\"".$file_name."\"");

				echo $body;
				exit();
			}
		}

		$this->_used_locations[""] = t("-- ALL --");
		foreach ((array)$this->_get_all_vars_locations() as $cur_location => $num_vars) {
			if (empty($num_vars)) {
				continue;
			}
			$this->_used_locations[$cur_location] = $cur_location." (".intval($num_vars).")";
		}
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"back_link"			=> "./?object=".$_GET["object"],
			"error_message"		=> _e(),
			"langs_box"			=> $this->_box("cur_langs",		-1),
			"file_formats_box"	=> $this->_box("file_format",	"csv"),
			"location_box"		=> $this->_box("location",		-1),
			"modules_box"		=> $this->_box("module",		-1),
		);
		return tpl()->parse($_GET["object"]."/export_vars", $replace);
	}

	/**
	* Automatic translator via Google translate
	*/
	function autotranslate() {
		if ($_POST["translate"] && $_POST["locale"]) {
			set_time_limit(1800); 
			$LOCALE_RES = $_POST["locale"];
	
			$base_url = "http://ajax.googleapis.com/ajax/services/language/translate"."?v=1.0";
			
			$vars = db()->query_fetch_all(
				"SELECT id,value FROM ".db('locale_vars')." WHERE id NOT IN( 
					SELECT var_id FROM ".db('locale_translate')." 
					WHERE locale = '".$LOCALE_RES."' AND value != '' 
				)");
			$_info = array();
			$max_threads = 4;
			$buffer = array();
			$translated = array();
_debug_log("LOCALE_NUM_VARS: ".count($vars));
			foreach ((array)$vars as $A) {
				$translated = array();
				$url = $base_url."&q=".urlencode(str_replace("_", " ", $A["value"]))."&langpair=en%7C".$LOCALE_RES;
				$_temp[$url] = $A["id"];
				if (count($buffer) < $max_threads) {
					$buffer[$url] = $url;
					continue;
				}
				foreach ((array)common()->multi_request($buffer) as $url => $response) {
					$response_array = json_decode($response);
					$response_text = trim($response_array->responseData->translatedText);
					$ID = $_temp[$url];
					$source = str_replace("_", " ", $vars[$ID]["value"]);
_debug_log("LOCALE: ".(++$j)." ## ".$ID." ## ".$source." ## ".$response_text." ## ".$url);
					if (_strlen($response_text) && $response_text != $source) {
						$translated[$ID] = $response_text;
					}
				}
				if ($translated) {
					$Q = db()->query(
						"DELETE FROM ".db('locale_translate')." 
						WHERE locale = '"._es($LOCALE_RES)."' 
							AND var_id IN(".implode(",", array_keys($translated)).")"
					);
				}
				foreach ((array)$translated as $_id => $_value) {
					db()->REPLACE("locale_translate", array(
						"var_id"	=> intval($_id),
						"value"		=> _es($_value),
						"locale"	=> _es($LOCALE_RES),
					));
				}
				$buffer = array();
				$_temp = array();
			}
			cache()->refresh("locale_translate_".$LOCALE_RES);
			return js_redirect("./?object=".$_GET["object"]);
		}

		$Q = db()->query("SELECT * FROM ".db('locale_langs')." ORDER BY name");
		while($A = db()->fetch_assoc($Q)){
			$locales[$A["locale"]] = $A["name"];
		}
		$replace = array(
			"locale_box" 		=> common()->select_box("locale", $locales),
			"locale_editor_url" => "./?object=locale_editor",
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
		);
		return tpl()->parse($_GET["object"]."/autotranslate", $replace);
	}	

	/**
	* Return array of all used locations in vars
	*/
	function _get_all_vars_locations() {
		$used_locations = array();
		$Q = db()->query("SELECT * FROM ".db('locale_vars')."");
		while ($A = db()->fetch_assoc($Q)) {
			foreach ((array)explode(";", $A["location"]) as $cur_location) {
				$cur_location = trim(substr($cur_location, 0, strpos($cur_location, ":")));
				if (empty($cur_location)) continue;
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
		// Select all known variables from db
		$Q = db()->query("SELECT * FROM ".db('locale_vars')." ORDER BY value ASC");
		while ($A = db()->fetch_assoc($Q)) {
			$this->_locale_vars[$A["value"]] = $A;
		}
		// Try to get variables from the source code
		$vars_from_code = $this->_parse_source_code_for_vars();
		// Process vars and update or insert if records are outdated
		foreach ((array)$vars_from_code as $cur_var_name => $var_files_info) {
			$location_array = array();
			foreach ((array)$var_files_info as $file_name => $line_numbers) {
				$location_array[] = $file_name.":".$line_numbers;
			}
			$location	= implode("; ", $location_array);
			$sql_array	= array(
				"value"		=> _es($cur_var_name),
				"location"	=> $location,
			);
			// If variable exists - use update
			if (isset($this->_locale_vars[$cur_var_name])) {
				db()->UPDATE("locale_vars", $sql_array, "id=".intval($this->_locale_vars[$cur_var_name]['id']));
			} else {
				db()->INSERT("locale_vars", $sql_array);
			}
		}
		// Return user back
		js_redirect("./?object=".$_GET["object"]."&action=show_vars");
	}

	/**
	* Collect vars from source files, no framework, just project and given module name (internal use only method)
	*/
	function collect_vars_for_module () {
		main()->NO_GRAPHICS = true;

		$module_name = preg_replace("/[^a-z0-9\_]/i", "", strtolower(trim($_GET["id"])));
		if (!$module_name) {
			return print "Error, no module name";
		}

		$vars = $this->_parse_source_code_for_vars(array(
			"only_project"	=> 1,
			"only_module"	=> $module_name,
		));

		echo "<pre>";
		foreach ((array)$vars as $var => $paths) {
			echo $var."\n";
		}
		echo "</pre>";
	}

	/**
	* Cleanup variables (Delete not translated or missed vars)
	*/
	function cleanup_vars () {
		// Find empty translations
		db()->query(
			"DELETE FROM ".db('locale_translate')." WHERE value=''"
		);
		// Delete non-changed translations
		$Q = db()->query(
			"SELECT * FROM ".db('locale_vars')." AS v
				, ".db('locale_translate')." AS t 
			WHERE t.var_id=v.id 
				AND (t.value=v.value OR t.value = '')"
		);
		while ($A = db()->fetch_assoc($Q)) {
			// Do delete found records
			db()->query(
				"DELETE FROM ".db('locale_translate')." 
				WHERE var_id=".intval($A["id"])." 
					AND locale='"._es($A["locale"])."'"
			);
		}
		// Special for the ignore case case
		if ($this->VARS_IGNORE_CASE) {
			// Delete non-changed translations
			$Q = db()->query(
				"SELECT * FROM ".db('locale_vars')." AS v
					, ".db('locale_translate')." AS t 
				WHERE t.var_id=v.id 
					AND LOWER(REPLACE(CONVERT(t.value USING utf8), ' ', '_')) 
						= LOWER(REPLACE(CONVERT(v.value USING utf8), ' ', '_'))"
			);
			// Delete non-changed translations
			while ($A = db()->fetch_assoc($Q)) {
				db()->query(
					"DELETE FROM ".db('locale_translate')." 
					WHERE var_id=".intval($A["id"])." 
						AND locale='"._es($A["locale"])."'"
				);
			}
			// Delete duplicated records
			$Q = db()->query(
				"SELECT id FROM ".db('locale_vars')."
				GROUP BY LOWER(REPLACE(CONVERT(value USING utf8), ' ', '_')) 
				HAVING COUNT(*) > 1"
			);
			while ($A = db()->fetch_assoc($Q)) {
				db()->query(
					"DELETE FROM ".db('locale_vars')." WHERE id=".intval($A["id"])
				);
			}
		}
		// Delete translations without parents
		db()->query(
			"DELETE FROM ".db('locale_translate')." 
			WHERE var_id NOT IN( 
				SELECT id FROM ".db('locale_vars')." 
			)"
		);
		// Delete parents without translations
		db()->query(
			"DELETE FROM ".db('locale_vars')." 
			WHERE id NOT IN( 
				SELECT var_id FROM ".db('locale_translate')." 
			)"
		);
		// Return user back
		return js_redirect("./?object=".$_GET["object"]."&action=show_vars");
	}

	/**
	* Parse source code for translate variables
	*/
	function _parse_source_code_for_vars ($params = array()) {
		$vars_array = array();
		// Avail params: only_framework, only_project, only_stpls, only_php, only_module

		$php_path_pattern	= "";
		$stpl_path_pattern	= "";
		if ($params["only_module"]) {
			$this->_include_php_pattern	= array("#/(modules)#", "#".preg_quote($params["only_module"], "#")."\.class\.php\$#");
			$this->_include_stpl_pattern	= array("#/templates#", "#\.stpl\$#");
			$stpl_path_pattern = "#templates/[^/]+/".$params["only_module"]."/#";
		}
		// Get source files from the framework
		if (!$params["only_project"]) {
			if (!$params["only_stpls"]) {
				$yf_framework_php_files	= _class("dir")->scan_dir(YF_PATH, true, $this->_include_php_pattern, $this->_exclude_pattern);
			}
			if (!$params["only_php"]) {
				$yf_framework_stpl_files	= _class("dir")->scan_dir(YF_PATH, true, $this->_include_stpl_pattern, $this->_exclude_pattern);
			}
		}
		// Get source files from the current project
		if (!$params["only_framework"]) {
			if (!$params["only_stpls"]) {
				$cur_project_php_files		= _class("dir")->scan_dir(INCLUDE_PATH, true, $this->_include_php_pattern, $this->_exclude_pattern);
			}
			if (!$params["only_php"]) {
				$cur_project_stpl_files		= _class("dir")->scan_dir(INCLUDE_PATH, true, $this->_include_stpl_pattern, $this->_exclude_pattern);
			}
		}
		// Get PHP files from the framework (classes and functions only)
		foreach ((array)$yf_framework_php_files as $file_name) {
			// Create short file name
			$short_file_name = str_replace(array(REAL_PATH, INCLUDE_PATH, YF_PATH), "", $file_name);
			// Merge vars
			foreach ((array)$this->_get_vars_from_file_name($file_name, $this->_translate_php_pattern) as $cur_var_name => $code_lines) {
				$vars_array[$cur_var_name][$short_file_name] = $code_lines;
			}
		}
		// Get PHP files from the current project (classes and functions only)
		foreach ((array)$cur_project_php_files as $file_name) {
			// Create short file name
			$short_file_name = str_replace(array(REAL_PATH, INCLUDE_PATH, YF_PATH), "", $file_name);
			// Merge vars
			foreach ((array)$this->_get_vars_from_file_name($file_name, $this->_translate_php_pattern) as $cur_var_name => $code_lines) {
				$vars_array[$cur_var_name][$short_file_name] = $code_lines;
			}
		}
		// Get STPL files from the framework
		foreach ((array)$yf_framework_stpl_files as $file_name) {
			// Create short file name
			$short_file_name = str_replace(array(REAL_PATH, INCLUDE_PATH, YF_PATH), "", $file_name);
			// Merge vars
			foreach ((array)$this->_get_vars_from_file_name($file_name, $this->_translate_stpl_pattern) as $cur_var_name => $code_lines) {
				$vars_array[$cur_var_name][$short_file_name] = $code_lines;
			}
		}
		// Get STPL files from the current project
		foreach ((array)$cur_project_stpl_files as $file_name) {
			// Create short file name
			$short_file_name = str_replace(array(REAL_PATH, INCLUDE_PATH, YF_PATH), "", $file_name);
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
	function _get_vars_from_file_name($file_name = "", $pattern = "") {
		$vars_array = array();
		if (empty($file_name)) {
			return $vars_array;
		}
		// Get file source as array
		$file_source_array = file($file_name);
		$match	= preg_match_all($pattern, implode("\n", $file_source_array), $matches);
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
			$vars_array[$cur_var_name] = implode(",",$code_lines);
		}
		return $vars_array;
	}

	/**
	* Prepare locations text
	*/
	function _prepare_locations($source_text = "") {
		if (empty($source_text)) {
			return false;
		}
		// Do not process links if turned off
		if (!$this->LOCATIONS_EDIT_LINKS) {
			return _prepare_html($source_text);
		}
		// Try to find separate links
		$body = array();
		foreach ((array)explode(";", $source_text) as $cur_source) {
			$cur_file_name = trim(substr($cur_source, 0, strpos($cur_source, ":")));
			$path_to = "";
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
					"link"	=> "./?object=file_manager&action=edit_item&f_=".basename($cur_file_name)."&dir_name=".urlencode($path_to.dirname($cur_file_name)),
					"text"	=> _prepare_html($cur_source),
				);
				$body[] = tpl()->parse($_GET["object"]."/location_item", $replace);
			}
		}
		return !empty($body) ? nl2br(implode(";\r\n", $body)) : _prepare_html($source_text);
	}

	/**
	* Create empty vars for the default language
	*/
	function _create_empty_vars_for_locale($force_locale = "") {
		$def_locale = "en";
		if (!empty($force_locale)) {
			$locale = $force_locale;
		} else {
			// Try to find default locale
			foreach ((array)$this->_cur_langs_array as $A) {
				if ($A["is_default"]) {
					$locale = $A["locale"];
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
				db()->INSERT("locale_translate", array(
					"var_id"	=> $A["id"],
					"value"		=> "",
					"locale"	=> $locale,
				));
			}
		}
	}

	/**
	* Some of the common languages with their English and native names
	*
	* Based on ISO 639 and http://people.w3.org/rishida/names/languages.html
	*
	* @private
	*/
	function _get_iso639_list() {
		return array(
			"aa" => array("Afar"),
			"ab" => array("Abkhazian", "аҧсуа бызшәа"),
			"ae" => array("Avestan"),
			"af" => array("Afrikaans"),
			"ak" => array("Akan"),
			"am" => array("Amharic", "አማርኛ"),
			"ar" => array("Arabic", "العربية"),
			"as" => array("Assamese"),
			"av" => array("Avar"),
			"ay" => array("Aymara"),
			"az" => array("Azerbaijani", "azərbaycan"),
			"ba" => array("Bashkir"),
			"be" => array("Belarusian", "Беларуская"),
			"bg" => array("Bulgarian", "Български"),
			"bh" => array("Bihari"),
			"bi" => array("Bislama"),
			"bm" => array("Bambara", "Bamanankan"),
			"bn" => array("Bengali"),
			"bo" => array("Tibetan"),
			"br" => array("Breton"),
			"bs" => array("Bosnian", "Bosanski"),
			"ca" => array("Catalan", "Català"),
			"ce" => array("Chechen"),
			"ch" => array("Chamorro"),
			"co" => array("Corsican"),
			"cr" => array("Cree"),
			"cs" => array("Czech", "Čeština"),
			"cu" => array("Old Slavonic"),
			"cv" => array("Welsh", "Cymraeg"),
			"cy" => array("Welch"),
			"da" => array("Danish", "Dansk"),
			"de" => array("German", "Deutsch"),
			"dv" => array("Maldivian"),
			"dz" => array("Bhutani"),
			"ee" => array("Ewe", "Ɛʋɛ"),
			"el" => array("Greek", "Ελληνικά"),
			"en" => array("English"),
			"eo" => array("Esperanto"),
			"es" => array("Spanish", "Español"),
			"et" => array("Estonian", "Eesti"),
			"eu" => array("Basque", "Euskera"),
			"fa" => array("Persian", "فارسی"),
			"ff" => array("Fulah", "Fulfulde"),
			"fi" => array("Finnish", "Suomi"),
			"fj" => array("Fiji"),
			"fo" => array("Faeroese"),
			"fr" => array("French", "Français"),
			"fy" => array("Frisian", "Frysk"),
			"ga" => array("Irish", "Gaeilge"),
			"gd" => array("Scots Gaelic"),
			"gl" => array("Galician", "Galego"),
			"gn" => array("Guarani"),
			"gu" => array("Gujarati"),
			"gv" => array("Manx"),
			"ha" => array("Hausa"),
			"he" => array("Hebrew", "עברית"),
			"hi" => array("Hindi", "हिन्दी"),
			"ho" => array("Hiri Motu"),
			"hr" => array("Croatian", "Hrvatski"),
			"hu" => array("Hungarian", "Magyar"),
			"hy" => array("Armenian", "Հայերեն"),
			"hz" => array("Herero"),
			"ia" => array("Interlingua"),
			"id" => array("Indonesian", "Bahasa Indonesia"),
			"ie" => array("Interlingue"),
			"ig" => array("Igbo"),
			"ik" => array("Inupiak"),
			"is" => array("Icelandic", "Íslenska"),
			"it" => array("Italian", "Italiano"),
			"iu" => array("Inuktitut"),
			"ja" => array("Japanese", "日本語"),
			"jv" => array("Javanese"),
			"ka" => array("Georgian"),
			"kg" => array("Kongo"),
			"ki" => array("Kikuyu"),
			"kj" => array("Kwanyama"),
			"kk" => array("Kazakh", "Қазақ"),
			"kl" => array("Greenlandic"),
			"km" => array("Cambodian"),
			"kn" => array("Kannada", "ಕನ್ನಡ"),
			"ko" => array("Korean", "한국어"),
			"kr" => array("Kanuri"),
			"ks" => array("Kashmiri"),
			"ku" => array("Kurdish", "Kurdî"),
			"kv" => array("Komi"),
			"kw" => array("Cornish"),
			"ky" => array("Kirghiz", "Кыргыз"),
			"la" => array("Latin", "Latina"),
			"lb" => array("Luxembourgish"),
			"lg" => array("Luganda"),
			"ln" => array("Lingala"),
			"lo" => array("Laothian"),
			"lt" => array("Lithuanian", "Lietuviškai"),
			"lv" => array("Latvian", "Latviešu"),
			"mg" => array("Malagasy"),
			"mh" => array("Marshallese"),
			"mi" => array("Maori"),
			"mk" => array("Macedonian", "Македонски"),
			"ml" => array("Malayalam", "മലയാളം"),
			"mn" => array("Mongolian"),
			"mo" => array("Moldavian"),
			"mr" => array("Marathi"),
			"ms" => array("Malay", "Bahasa Melayu"),
			"mt" => array("Maltese", "Malti"),
			"my" => array("Burmese"),
			"na" => array("Nauru"),
			"nd" => array("North Ndebele"),
			"ne" => array("Nepali"),
			"ng" => array("Ndonga"),
			"nl" => array("Dutch", "Nederlands"),
			"no" => array("Norwegian", "Norsk"),
			"nr" => array("South Ndebele"),
			"nv" => array("Navajo"),
			"ny" => array("Chichewa"),
			"oc" => array("Occitan"),
			"om" => array("Oromo"),
			"or" => array("Oriya"),
			"os" => array("Ossetian"),
			"pa" => array("Punjabi"),
			"pi" => array("Pali"),
			"pl" => array("Polish", "Polski"),
			"ps" => array("Pashto", "پښتو"),
			"pt" => array("Portuguese", "Português"),
			"qu" => array("Quechua"),
			"rm" => array("Rhaeto-Romance"),
			"rn" => array("Kirundi"),
			"ro" => array("Romanian", "Română"),
			"ru" => array("Russian", "Русский"),
			"rw" => array("Kinyarwanda"),
			"sa" => array("Sanskrit"),
			"sc" => array("Sardinian"),
			"sd" => array("Sindhi"),
			"se" => array("Northern Sami"),
			"sg" => array("Sango"),
			"sh" => array("Serbo-Croatian"),
			"si" => array("Singhalese"),
			"sk" => array("Slovak", "Slovenčina"),
			"sl" => array("Slovenian", "Slovenščina"),
			"sm" => array("Samoan"),
			"sn" => array("Shona"),
			"so" => array("Somali"),
			"sq" => array("Albanian", "Shqip"),
			"sr" => array("Serbian", "Српски"),
			"ss" => array("Siswati"),
			"st" => array("Sesotho"),
			"su" => array("Sudanese"),
			"sv" => array("Swedish", "Svenska"),
			"sw" => array("Swahili", "Kiswahili"),
			"ta" => array("Tamil", "தமிழ்"),
			"te" => array("Telugu", "తెలుగు"),
			"tg" => array("Tajik"),
			"th" => array("Thai", "ภาษาไทย"),
			"ti" => array("Tigrinya"),
			"tk" => array("Turkmen"),
			"tl" => array("Tagalog"),
			"tn" => array("Setswana"),
			"to" => array("Tonga"),
			"tr" => array("Turkish", "Türkçe"),
			"ts" => array("Tsonga"),
			"tt" => array("Tatar", "Tatarça"),
			"tw" => array("Twi"),
			"ty" => array("Tahitian"),
			"ug" => array("Uighur"),
			"uk" => array("Ukrainian", "Українська"),
			"ur" => array("Urdu", "اردو"),
			"uz" => array("Uzbek", "o'zbek"),
			"ve" => array("Venda"),
			"vi" => array("Vietnamese", "Tiếng Việt"),
			"wo" => array("Wolof"),
			"xh" => array("Xhosa", "isiXhosa"),
			"yi" => array("Yiddish"),
			"yo" => array("Yoruba", "Yorùbá"),
			"za" => array("Zhuang"),
			"zh-hans" => array("Chinese, Simplified", "简体中文"),
			"zh-hant" => array("Chinese, Traditional", "繁體中文"),
			"zu" => array("Zulu", "isiZulu"),
		);
	}

	/**
	* Process custom box
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Do get current languages from db
	*/
	function _get_locales () {
		$Q = db()->query("SELECT * FROM ".db('locale_langs')." ORDER BY is_default DESC, locale ASC");
		while ($A = db()->fetch_assoc($Q)) {
			$data[$A["locale"]] = $A["name"];
		}
		return $data;
	}
/*
	// Prepare required data for filter
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= $_GET["object"]."_filter";
		// Prepare boxes
		$this->_boxes = array_merge((array)$this->_boxes, array(
			"show_locs"			=> 'radio_box("show_locs",		$this->_std_trigger,		$selected, 0, 2, "", false)',
			"search_lang"		=> 'radio_box("search_lang",	$this->_langs_for_search,	$selected, true, 2, "", false)',
			"search_in"			=> 'radio_box("search_in",		$this->_search_in,			$selected, true, 2, "", false)',
			"sort_by"			=> 'select_box("sort_by",		$this->_sort_by,			$selected, 0, 2, "", false)',
			"sort_order"		=> 'select_box("sort_order",	$this->_sort_orders,		$selected, 0, 2, "", false)',
			"per_page"			=> 'select_box("per_page",		$this->_per_page,			$selected, 0, 2, "", false)',
			"case_sens"			=> 'radio_box("case_sens",		$this->_std_trigger,		$selected, 0, 2, "", false)',
		));
		$this->_std_trigger = array(
			"1" => "<span class='positive'>YES</span>",
			"0" => "<span class='negative'>NO</span>",
		);
		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Sort fields
		$this->_sort_by = array(
			"",
			"value",
		);
		// Number per page
		$this->_per_page = array("" => "", 10 => 10, 20 => 20, 50 => 50, 100 => 100, 200 => 200, 500 => 500);
		// Fields in the filter
		$this->_fields_in_filter = array(
			"search_lang",
			"search_in",
			"search_string",
			"search_type",
			"show_locs",
			"per_page",
			"case_sens",
			"sort_by",
			"sort_order",
		);
	}

	// Generate filter SQL query
	function _create_filter_sql () {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) {
			$SF[$k] = trim($v);
		}
		// Do not allow "_" pattern for LIKE
		$string = str_replace("_", "\_", str_replace(" ", "_", $SF["search_string"]));
		// Generate filter for the common fileds
		if ($SF["search_lang"] && $SF["search_lang"] != "all") {
			$tr_sql .= " AND t.locale='"._es($SF["search_lang"])."' \r\n";
		}
		if ($SF["search_in"] && $SF["search_lang"] != "all") {
			if ($SF["search_in"] == "translated") {
				$tr_sql .= " AND t.value != '' \r\n";
			} elseif ($SF["search_in"] == "untranslated") {
				$tr_sql .= " AND (t.value = '' OR LOWER(REPLACE(CONVERT(t.value USING utf8), ' ', '_')) = LOWER(REPLACE(CONVERT(v.value USING utf8), ' ', '_'))) \r\n";
			}
		}
		// Search in transtions
		if ($SF["search_string"] && $SF["search_type"] == "translations") {
			if (!$SF["case_sens"]) {
				$tr_sql .= " AND LOWER(REPLACE(CONVERT(t.value USING utf8), ' ', '_')) LIKE '"._es(_strtolower($string))."%' \r\n";
			} else {
				$tr_sql .= " AND t.value LIKE '"._es($string)."%' \r\n";
			}
		}
		if (!empty($tr_sql)) {
			$sql .= " AND v.id IN( SELECT t.var_id FROM ".db('locale_translate')." AS t WHERE 1=1 ".$tr_sql.") \r\n";
		}
		// Search in vars
		if ($SF["search_string"] && $SF["search_type"] != "translations") {
			if (!$SF["case_sens"]) {
				$sql .= " AND LOWER(REPLACE(CONVERT(v.value USING utf8), ' ', '_')) LIKE '"._es(_strtolower($string))."%' \r\n";
			} else {
				$sql .= " AND v.value LIKE '"._es($string)."%' \r\n";
			}
		}
		// Sorting here
		if ($SF["sort_by"]) {
		 	$sql .= " ORDER BY ".$this->_sort_by[$SF["sort_by"]]." \r\n";
		}
		if ($SF["sort_by"] && strlen($SF["sort_order"])) {
			$sql .= " ".$SF["sort_order"]." \r\n";
		}
		return substr($sql, 0, -3);
	}

	// Session - based filter
	function _show_filter () {
		if ($_GET["action"] != "show_vars") {
			return false;
		}
		$replace = array(
			"save_action"	=> "./?object=".$_GET["object"]."&action=save_filter"._add_get(),
			"clear_url"		=> "./?object=".$_GET["object"]."&action=clear_filter"._add_get(),
		);
		// Default values
		if (!isset($_SESSION[$this->_filter_name]["search_lang"])) {
			$_SESSION[$this->_filter_name]["search_lang"] = "all";
		}
		if (!isset($_SESSION[$this->_filter_name]["search_in"])) {
			$_SESSION[$this->_filter_name]["search_in"] = "all";
		}
		if (!isset($_SESSION[$this->_filter_name]["search_type"])) {
			$_SESSION[$this->_filter_name]["search_type"] = "vars";
		}
		if (!isset($_SESSION[$this->_filter_name]["show_locs"])) {
			$_SESSION[$this->_filter_name]["show_locs"] = 1;
		}
		if (!isset($_SESSION[$this->_filter_name]["case_sens"])) {
			$_SESSION[$this->_filter_name]["case_sens"] = 0;
		}
		foreach ((array)$this->_fields_in_filter as $name) {
			$replace[$name] = $_SESSION[$this->_filter_name][$name];
		}
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $_SESSION[$this->_filter_name][$item_name]);
		}
		return tpl()->parse($_GET["object"]."/filter", $replace);
	}

	// Filter save method
	function save_filter ($silent = false) {
		// Process featured countries
		if (FEATURED_COUNTRY_SELECT && !empty($_REQUEST["country"]) && substr($_REQUEST["country"], 0, 2) == "f_") {
			$_REQUEST["country"] = substr($_REQUEST["country"], 2);
		}
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_REQUEST[$name];
		}
		if (!$silent) {
			js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	// Clear filter
	function clear_filter ($silent = false) {
		if (is_array($_SESSION[$this->_filter_name])) {
			foreach ((array)$_SESSION[$this->_filter_name] as $name) unset($_SESSION[$this->_filter_name]);
		}
		if (!$silent) {
			js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}
*/

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
