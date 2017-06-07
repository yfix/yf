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
	public $_include_php_pattern	= ['#\/(admin_modules|classes|functions|modules)#', '#\.php$#'];
	/** @var string @conf_skip STPL Files to parse */
	public $_include_stpl_pattern	= ['#\/(templates)#', '#\.stpl$#'];
	/** @var string @conf_skip Exclude files from parser */
	public $_exclude_pattern		= ['#\/(commands|docs|libs|scripts|sql|storage|tests)#', ''];
	/** @var string @conf_skip Search vars in PHP files */
	public $_translate_php_pattern	= "/[\(\{\.\,\s\t=]+?(t)[\s\t]*?\([\s\t]*?('[^'\$]+?'|\"[^\"\$]+?\")/ims";
	/** @var string @conf_skip Search vars in STPL files */
	public $_translate_stpl_pattern= "/\{(t)\([\"']*([\s\w\-\.\,\:\;\%\&\#\/\<\>]*)[\"']*[,]*[^\)\}]*\)\}/is";
	/** @var bool Display vars locations */
	public $DISPLAY_VARS_LOCATIONS	= true;
	/** @var bool Display links to edit source files (in location) */
	public $LOCATIONS_EDIT_LINKS	= true;
	/** @var bool Ignore case on import/export */
	public $VARS_IGNORE_CASE		= true;
	/** @var bool @conf_skip */
	private	$_preload_complete = false;
	/** @var array @conf_skip */
	private static $per_page_values = ['' => '', 10 => 10, 20 => 20, 50 => 50, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000, 2000 => 2000, 5000 => 5000];
	/** @var array @conf_skip */
	private static $HELP = [
		'edit' => [
			'en' => '
Translation variants depending on input number (optional).

<u class="text-primary">Definition</u>
{<b class="text-warning">source_variable</b>|<b class="text-warning">default_translation</b>}
<u class="text-primary">or</u>
{<b class="text-warning">source_variable</b>|<b class="text-warning">last_number</b>:translation|<b class="text-warning">default_translation</b>}

<u class="text-primary">Params</u>
<u class="text-warning">source_variable</u>
string, starts with "%", no spaces (example: %var_with_underscore)
<u class="text-warning">last_number</u>
* int (example: "5")
* list (example: "2,3,4")
* range (example: "10-12")
* exact (example: "#1" match only "1", not "11","21","31")
<u class="text-warning">default_translation</u>
Fallback when no numbers matched (any string)

<u class="text-primary">Examples</u>
* В процессе поиска
{Найдено %num папок|0:Папок не найдено|1:Найдена %num папка|2,3,4:Найдено %num папки|11-14:Найдено %num папок|Найдено %num папок}

* {%num horas|#1:%num hora|%num horas}
			',
		],
	];

	/**
	*/
	function __get ($name) {
		if (!$this->_preload_complete) {
			$this->_preload_data();
		}
		return $this->$name;
	}

	/**
	*/
	function __set ($name, $value) {
		if (!$this->_preload_complete) {
			$this->_preload_data();
		}
		$this->$name = $value;
		return $this->$name;
	}

	/**
	*/
	function _preload_data () {
		if ($this->_preload_complete) {
			return true;
		}
		$this->_preload_complete = true;

		asset('bfh-select');
		$this->lang_def_country = main()->get_data('lang_def_country');

		$this->_modules = _class('admin_methods')->find_active_modules();

		$langs = [];
		foreach ((array)$this->_get_iso639_list() as $lang_code => $lang_params) {
			$langs[$lang_code] = t($lang_params[0]).(!empty($lang_params[1]) ? ' ('.$lang_params[1].') ' : '');
		}
		$this->_langs = $langs;

		$this->_cur_langs_array = from('locale_langs')->order_by('is_default DESC, locale ASC')->all();
		if (!$this->_cur_langs_array) {
			db()->insert_safe('locale_langs', [
				'locale'	=> 'en',
				'name'		=> t('English'),
				'charset'	=> 'utf-8',
				'active'	=> 1,
				'is_default'=> 1,
			]);
			return js_redirect('/@object/@action');
		}

		$langs_for_search[''] = t('All languages');
		foreach ((array)$this->_cur_langs_array as $A) {
			$langs_for_search[$A['locale']] = t($A['name']);
			$cur_langs[$A['locale']] = t($A['name']);
		}
		$this->_langs_for_search = $langs_for_search;
		$this->_cur_langs = $cur_langs;
// TODO: add support for these file formats for import/export:
// * JSON
// * PHP
// * GNU Gettext (.po)  http://www.gutenberg.org/wiki/Gutenberg:GNU_Gettext_Translation_How-To, https://en.wikipedia.org/wiki/Gettext
		$this->_file_formats = [
			'csv'	=> t('CSV, compatible with MS Excel'),
			'xml'	=> t('XML'),
		];
		$this->_modes = [
			1	=> t('Strings in the uploaded file replace existing ones, new ones are added'),
			2	=> t('Existing strings are kept, only new strings are added'),
		];
	}

	/**
	* Display all project languages
	*/
	function show() {
#		$tr_vars = db()->get_2d('SELECT locale, COUNT(var_id) AS num FROM '.db('locale_translate').' WHERE value != "" GROUP BY locale');
#		$total_vars = (int)db()->get_one('SELECT COUNT(*) FROM '.db('locale_vars'));

		$data = [];
		foreach ((array)$this->_cur_langs_array as $v) {
			$id = $v['locale'];
			$v['tr_count'] = strval($tr_vars[$id]);
			$v['tr_percent'] = $total_vars && $v['tr_count'] ? round(100 * $v['tr_count'] / $total_vars, 2).'%' : '';
			$data[$id] = $v;
		}
		$no_actions_if_default = function($row) {
			return $row['is_default'] ? false : true;
		};
		$_this = $this;
		return table($data, [
				'pager_records_on_page' => 1000,
				'hide_empty' => 1,
				'no_total' => 1,
			])
			->func('locale', function($lang) {
				return $this->_lang_icon($lang, false);
			})
			->text('name')
			->text('charset')
			->text('tr_count', 'Num vars')
			->text('tr_percent', 'Translated', ['badge' => 'info'])
			->func('is_default', function($is) { return $is ? '<span class="label label-info">'.t('DEFAULT').'</span>' : ''; })
			->btn_edit('', url('/@object/lang_edit/%d'), ['btn_no_text' => 1])
			->btn_delete('', url('/@object/lang_delete/%d'), ['display_func' => $no_actions_if_default, 'btn_no_text' => 1])
			->btn('Make default', url('/@object/lang_default/%d'), ['class_add' => 'btn-info', 'display_func' => $no_actions_if_default, 'btn_no_text' => 1])
			->btn_active('', url('/@object/lang_active/%d'), ['display_func' => $no_actions_if_default])
			->footer_add('Add', url('/@object/lang_add'), ['no_ajax' => 1, 'class_add' => 'btn-warning'])
			->header_link('Collect', url('/@object/collect'), ['icon' => 'fa fa-cogs', 'class_add' => 'btn-warning'])
			->header_link('Cleanup', url('/@object/cleanup'), ['icon' => 'fa fa-eraser', 'class_add' => 'btn-danger'])
			->header_link('Import', url('/@object/import'), ['icon' => 'fa fa-download', 'class_add' => 'btn-info'])
			->header_link('Export', url('/@object/export'), ['icon' => 'fa fa-upload', 'class_add' => 'btn-info'])
			->header_link('Files', url('/@object/files'), ['icon' => 'fa fa-files-o', 'class_add' => 'btn-primary'])
			->header_link('Vars', url('/@object/vars'), ['icon' => 'fa fa-bars', 'class_add' => 'btn-primary'])
		;
	}

	/**
	*/
	function lang_add() {
		$raw = $this->_get_iso639_list();
		$langs = [];
		foreach ($raw as $code => $v) {
			if (isset($this->_cur_langs[$code])) {
				continue;
			}
			$langs[$code] = implode(' | ', $v);
		}
		$a['redirect_link'] = url('/@object');
		return form((array)$_POST + (array)$a)
			->validate(['locale' => ['trim|required', function($in) use ($langs) { return isset($langs[$in]); }] ])
			->insert_if_ok('sys_locale_langs', ['locale'], [
				'name'		=> $raw[$_POST['locale']][0],
				'charset'	=> 'utf-8',
				'active'	=> 0,
				'is_default'=> 0,
			])
			->on_after_update(function(){
				cache_del('locale_langs');
			})
			->select_box('locale', $langs)
			->save('Add')
		;
	}

	/**
	*/
	function lang_edit() {
		$id = intval($_GET['id']);
		$id && $a = from('locale_langs')->whereid($id)->get();
		if (!$a) {
			return _e('No id');
		}
		$a = (array)$_POST + (array)$a;
		$a['redirect_link'] = url('/@object');
		return form($a, ['autocomplete' => 'off'])
			->validate([
				'name' => 'trim|required|is_unique_without[locale_langs.name.'.$id.']',
				'charset' => 'trim|required',
			])
			->db_update_if_ok('locale_langs', ['name','charset'], 'id='.$id)
			->on_after_update(function() {
				cache_del('locale_langs');
				common()->admin_wall_add(['locale lang updated: '.$_POST['name'].'', $id]);
			})
			->info('locale')
			->text('name')
			->text('charset')
			->save_and_back();
	}

	/**
	*/
	function lang_active() {
		$id = intval($_GET['id']);
		$id && $a = from('locale_langs')->whereid($id)->get();
		if (!empty($a) && !$a['is_default']) {
			db()->update_safe('locale_langs', ['active' => intval(!$a['active'])], 'id='.(int)$id);
			common()->admin_wall_add(['locale lang '.$a['name'].' '.($a['active'] ? 'inactivated' : 'activated'), $id]);
			cache_del(['locale_langs']);
		}
		if (is_ajax()) {
			no_graphics(true);
			echo ($a['active'] ? 0 : 1);
		} else {
			return js_redirect('/@object');
		}
	}

	/**
	*/
	function lang_default() {
		$id = intval($_GET['id']);
		$id && $a = from('locale_langs')->whereid($id)->get();
		if (!empty($a) && !$a['is_default']) {
			db()->update_safe('locale_langs', ['is_default' => 0], '1 = 1');
			db()->update_safe('locale_langs', ['is_default' => 1], 'id = '.(int)$id));
			common()->admin_wall_add(['locale lang '.$a['name'].' made default', $id]);
			cache_del(['locale_langs']);
		}
		if (is_ajax()) {
			no_graphics(true);
			echo 1;
		} else {
			return js_redirect('/@object');
		}
	}

	/**
	*/
	function lang_delete() {
		$id = intval($_GET['id']);
		$id && $a = from('locale_langs')->whereid($id)->get();
		if ($a) {
			$lang = $this->_cur_langs_array[$id]['locale'];
			db()->delete('locale_langs', $id);
			db()->delete('locale_translate', 'locale = "'._es($lang).'"');
			common()->admin_wall_add(['locale language deleted: '.$lang, $id]);
			cache_del('locale_langs');
		}
		if (is_ajax()) {
			no_graphics(true);
			echo $id;
		} else {
			return js_redirect('/@object');
		}
	}

	/**
	*/
	function _lang_icon($lang = 'en', $btn = false) {
		$icon = html()->icon('bfh-flag-'.$this->lang_def_country[$lang], strtoupper($lang));
		if (!$lang) {
			return false;
		}
		return $btn ? '<span class="btn btn-xs btn-primary disabled">'.$icon.'</span>' : $icon;
	}

	/**
	*/
	function files() {
		$self_page_css = 'body.get-object-'.$_GET['object'];
		css('
			'.$self_page_css.' li.li-header { list-style: none; display:none; }
			'.$self_page_css.' li.li-level-0 { display: block; font-size: 15px; }
			'.$self_page_css.' li.li-level-1 { padding-top: 10px; font-size: 13px; }
			'.$self_page_css.' .source_container { width: 90%; height: 400px; }
		');
		jquery('
			var self_page = "'.$self_page_css.'";
			$(".li-level-0 > a", self_page).before("&nbsp;<button class=\"btn btn-mini btn-xs btn-default\" class=\"toggle_source\"><i class=\"fa fa-toggle-down\"></i> Toggle source</button>&nbsp;")
			$(".li-level-0 .togle_source", self_page).click(function(){
				$(".li-level-1", $(this).closest(".li-level-0")).toggle()
			})
			$(".li-level-0", self_page).click(function(){
				$(".li-level-1", this).toggle()
			})
		');
		$all_langs = (array)$this->_cur_langs;
		foreach ((array)$all_langs as $lang => $name) {
			list($lang_vars, $var_files, $lang_files) = $this->_get_vars_from_files($lang);
			if (!$lang_files) {
				continue;
			}
			$body[] = '<h3>'.$this->_lang_icon($lang, false).'</h3>';
			$body[] = $this->_show_files_for_lang($lang, $lang_files, $var_files);
		}
		$links = table([], ['no_records_html' => ''])
			->header_link('Collect', url('/@object/collect'), ['icon' => 'fa fa-cogs', 'class_add' => 'btn-warning'])
			->header_link('Cleanup', url('/@object/cleanup'), ['icon' => 'fa fa-eraser', 'class_add' => 'btn-danger'])
			->header_link('Import', url('/@object/import'), ['icon' => 'fa fa-download', 'class_add' => 'btn-info'])
			->header_link('Export', url('/@object/export'), ['icon' => 'fa fa-upload', 'class_add' => 'btn-info'])
			->header_link('Vars', url('/@object/vars'), ['icon' => 'fa fa-bars', 'class_add' => 'btn-primary'])
		;
		return $links . implode(PHP_EOL, $body);
	}

	/**
	*/
	function _show_files_for_lang($lang, $lang_files, $var_files) {
		$yf_path_len = strlen(YF_PATH);
		$app_path_len = strlen(APP_PATH);

		$vars_by_path = [];
		foreach ((array)$var_files as $source => $path) {
			$vars_by_path[$path]++;
		}
		foreach ((array)$lang_files as $path) {
			$i++;
			$name = $path;
			if (substr($name, 0, $yf_path_len) === YF_PATH) {
				$name = '[YF] '.substr($name, $yf_path_len);
			} elseif (substr($name, 0, $app_path_len) === APP_PATH) {
				$name = '[APP] '.substr($name, $app_path_len);
			}
			$name .= ' (vars: '.$vars_by_path[$path].')';
			$items[$i] = [
				'parent_id'	=> 0,
				'name'		=> $name,
				'link'		=> url('/file_manager/view/'.urlencode($path)),
				'id'		=> 'lang_file_'.$i,
			];
			$div_id = 'editor_html_'.$lang.'_'.$i;
			$hidden_id = 'file_text_hidden_'.$lang.'_'.$i;
			$items['1111'.$i] = [
				'parent_id'	=> $i,
				'body'		=> form()
					->container('<div id="'.$div_id.'" class="source_container">'._prepare_html(addslashes(file_get_contents($path))).'</div>', '', [
						'id' => $div_id, 'wide' => 1, 'ace_editor' => ['mode' => common()->get_file_ext($path)],
					])
					->hidden($hidden_id)
			];
		}
		return html()->li_tree($items);
	}

	/**
	*/
	function _get_vars_from_files($lang) {
		$files = [];
		// Auto-find shared language vars. They will be connected in order of file system
		// Names can be any, but better to include lang name into file name. Examples:
		// share/langs/ru/001_other.php
		// share/langs/ru/002_other2.php
		// share/langs/ru/other.php
		// share/langs/ru/ru_shop.php
		// plugins/shop/share/langs/ru/ru_user_register.php
		$pattern = '{,plugins/*/}{,share/}langs/'.$lang.'/*.php';
		$globs = [
			'framework'	=> YF_PATH. $pattern,
			'project'	=> PROJECT_PATH. $pattern,
			'app'		=> APP_PATH. $pattern,
		];
		// Order matters! Project vars will have ability to override vars from franework
		foreach ($globs as $glob) {
			foreach ((array)glob($glob, GLOB_BRACE) as $f) {
				$files[basename($f)] = $f;
			}
		}
		// Auto-find vars for user modules. They will be connected in order of file system
		// Names must begin with __locale__{lang} and then any name. Examples:
		// modules/shop/__locale__ru.php
		// modules/shop/__locale__ru_orders.php
		// modules/shop/__locale__ru_products.php
		// plugins/shop/modules/shop/__locale__ru_products.php
		$modules = 'modules';
		$pattern = '{,plugins/*/}'.$modules.'/*/__locale__'.$lang.'*.php';
		$globs = [
			'framework'	=> YF_PATH. $pattern,
			'project'	=> PROJECT_PATH. $pattern,
			'app'		=> APP_PATH. $pattern,
		];
		// Order matters! Project vars will have ability to override vars from franework
		foreach ($globs as $globs) {
			foreach ((array)glob($glob, GLOB_BRACE) as $f) {
				$files[basename($f)] = $f;
			}
		}
		foreach ((array)$files as $path) {
			$data = include $path;
			foreach ((array)$data as $source => $tr) {
				$this->VARS_IGNORE_CASE && $source = _strtolower($source);
				$tr_vars[$source] = $tr;
				$tr_files[$source] = $path;
			}
		}
		return [$tr_vars, $tr_files, $files];
	}

	/**
	*/
	function _get_all_vars_from_files() {
		$vars = [];
		foreach ((array)$this->_cur_langs as $lang => $lang_name) {
			list($lang_vars, $var_files) = $this->_get_vars_from_files($lang);
			foreach ((array)$lang_vars as $source => $tr) {
				if (!$source) {
					continue;
				}
				$this->VARS_IGNORE_CASE && $source = _strtolower($source);
				!is_array($vars[$source]) && $vars[$source] = [];
				$vars[$source]['id'] = $source;
				$vars[$source]['source'] = $source;
				$vars[$source]['locale'][$lang] = $lang;
				$vars[$source]['translation'][$lang] = $tr;
				$vars[$source]['files'][$var_files[$source]] = $var_files[$source];
			}
		}
		return $vars;
	}

	/**
	*/
	function _get_all_vars_from_db() {
		$vars = [];
		$lang_ids = array_keys($this->_cur_langs);
		$tr_all = [];
		foreach((array)from('locale_translate')->where_raw('locale IN("'.implode('","',$lang_ids).'")')->all() as $a) {
			$tr_all[$a['var_id']][$a['locale']] = $a['value'];
		}
		foreach ((array)from('locale_vars')->get_2d('value,id') as $source => $vid) {
			$this->VARS_IGNORE_CASE && $source = _strtolower($source);
			$vars[$source]['id'] = $source;
			$vars[$source]['source'] = $source;
			$trs = $tr_all[$vid];
			foreach((array)$trs as $lang => $tr) {
				$vars[$source]['locale'][$lang] = $lang;
				$vars[$source]['translation'][$lang] = $tr;
			}
		}
		return $vars;
	}

	/**
	*/
	function vars() {
		$vars = $this->_get_all_vars_from_files();
		$vars_db = $this->_get_all_vars_from_db();
		foreach((array)$vars_db as $source => $a) {
			foreach($a as $k => $v) {
				$vars[$source][$k] = $v;
			}
		}

		$edit_link_tpl = url('/@object/var_edit/%id');

		ksort($vars);
		return table($vars, ['pager_records_on_page' => 10000, 'id' => 'source', 'very_condensed' => 1])
			->text('source', ['transform' => '_prepare_html', 'desc' => 'Var name'])
			->func('id', function($in,$e,$a,$t) {
				$trs = $a['locale'];
				foreach ((array)$trs as $lang) {
					$out[] = $this->_lang_icon($lang, true);
				}
				return $out ? implode(' ', $out) : '';
			}, ['desc' => 'Langs'])
			->func('source', function($in,$e,$a,$t) use ($vars_db) {
				return isset($vars_db[$in]['translation']) ? (string)implode(',',array_keys($vars_db[$in]['translation'])) : '';
			}, ['desc' => 'Db override'])
			->func('id', function($in,$e,$a,$t) {
				return isset($a['files']) ? (int)count($a['files']) : '';
			}, ['desc' => 'Num files'])
			->btn_edit('', url('/@object/var_edit/%source'), ['btn_no_text' => 1])
			->header_add('', url('/@object/var_add'), ['btn_no_text' => 1, 'class_add' => 'btn-warning', 'no_ajax' => 1])
			->footer_add('', url('/@object/var_add'), ['btn_no_text' => 1, 'class_add' => 'btn-warning', 'no_ajax' => 1])
			->header_link('Collect', url('/@object/collect'), ['icon' => 'fa fa-cogs', 'class_add' => 'btn-warning'])
			->header_link('Cleanup', url('/@object/cleanup'), ['icon' => 'fa fa-eraser', 'class_add' => 'btn-danger'])
			->header_link('Import', url('/@object/import'), ['icon' => 'fa fa-download', 'class_add' => 'btn-info'])
			->header_link('Export', url('/@object/export'), ['icon' => 'fa fa-upload', 'class_add' => 'btn-info'])
			->header_link('Files', url('/@object/files'), ['icon' => 'fa fa-files-o', 'class_add' => 'btn-primary'])
		;
	}

	/**
	*/
	function var_edit() {
		$a = $this->_get_var_info($_GET['id']);
		if (!$a) {
			return _e('Wrong var id');
		}
		$var_db = $a;

		$langs = [];
		foreach ((array)$this->_cur_langs_array as $l) {
			$langs[$l['locale']] = $l['name'];
		}

		$vars = $this->_get_all_vars_from_files();
		$var = $vars[$a['value']];
		foreach ((array)$langs as $lang => $name) {
			$a['translation_'.$lang] = $var['translation'][$lang];
		}

		// Override from db
		$var_tr_db = from('locale_translate')->where('var_id', (int)$a['id'])->get_2d('locale,value');
		foreach ((array)$var_tr_db as $lang => $tr) {
			$a['translation_'.$lang] = $tr;
		}

		$a['back_link'] = url('/@object/vars');
		$a['redirect_link'] = url('/@object/@action/@id');

		$form = form($a);
		$form->container('<b><big class="text-success">'._prepare_html($a['value']).'</big></b>');
		foreach ((array)$langs as $lang => $name) {
			$form->textarea('translation_'.$lang, ['desc' => $this->_lang_icon($lang, true), 'placeholder' => $name]);
		}
		$form->on_post(function($a,$r,$f) use ($langs, $var, $var_db, $var_tr_db) {
			$up = [];
			$var_id = $a['id'];
			foreach ((array)$langs as $lcode => $lname) {
				$p = &$_POST;
				$posted = trim($p['translation_'.$lcode]);
				$existed = trim($a['translation_'.$lcode]);
				// if posted val is empty - we mean empty translation
				if ($posted != $existed && (strlen($posted) || strlen($existed))) {
					$up[$lcode] = [
						'var_id' => $var_id,
						'locale' => $lcode,
						'value' => $posted,
					];
				}
			}
			$up && db()->replace('locale_translate', $up);
			return js_redirect($data['redirect_link']);
		});
		$form->save_and_back();
		$form->render($a);
		$help = $this->_help('edit');

		$storages = [];
		$files = $var['files'];
		foreach ((array)$files as $k => $path) {
			if (strpos($path, YF_PATH) === 0) {
				$files[$k] = '[YF]&nbsp;'.substr($path, strlen(YF_PATH));
			} elseif (strpos($path, APP_PATH) === 0) {
				$files[$k] = '[APP]&nbsp;'.substr($path, strlen(APP_PATH));
			}
			if (preg_match('~/langs/(?P<lang>[a-z]{2})/~i', $files[$k], $m)) {
				$files[$k] = $this->_lang_icon($m['lang'], true). '&nbsp;'. $files[$k];
			}
		}
		$files && $storages[] = '<div class="col-md-offset-3"><h3>Files</h3><b>'.implode('<br>', $files).'</b></div>';
		$langs_in_db_icons = [];
		foreach(array_keys($var_tr_db) as $lang) {
			$langs_in_db_icons[$lang] = $this->_lang_icon($lang, true);
		}
		$var_tr_db && $storages[] = '<div class="col-md-offset-3"><h3>Db</h3><b>'.implode(' ', $langs_in_db_icons).'</b></div>';

		if (is_ajax()) {
			return $form. implode($storages);
		}
		return 
			'<div class="col-md-8">'.$form. implode($storages). '</div>'.
			'<div class="col-md-4">'.$help.'</div>'
		;
	}

	/**
	*/
	function var_add() {
		$a['back_link'] = url('/@object/vars');
		$a['redirect_link'] = $a['back_link'];
		if (is_post()) {
			$val = trim($_POST['value']);
			strlen($val) && $a['redirect_link'] = url('/@object/var_edit/'.urlencode($val));
		}
		return form($a + (array)$_POST)
			->validate(['value' => 'trim|required'])
			->db_insert_if_ok('locale_vars', ['value'])
			->text('value')
			->save_and_back();
	}

	/**
	*/
	function var_delete() {
		$a = $this->_get_var_info($_GET['id']);
		if ($a['id']) {
			$id = (int)$a['id'];
			db()->delete('locale_vars', $id);
			db()->delete('locale_translate', 'var_id = '.(int)$id);
			common()->admin_wall_add(['locale var deleted: '.$a['value'], $id]);
		}
		if (is_ajax()) {
			no_graphics(true);
			echo $_GET['id'];
		} else {
			return js_redirect('/@object/vars');
		}
	}

	/**
	*/
	function _get_var_info($id) {
		$id = trim($id);
		if (!strlen($id)) {
			return [];
		}
		$a = [];
		if (is_numeric($id)) {
			$a = from('locale_vars')->whereid($id)->limit(1)->get();
		} else {
			$this->VARS_IGNORE_CASE && $id = _strtolower($id);
			if ($this->VARS_IGNORE_CASE) {
				$where = 'LOWER(CONVERT(`value` USING utf8)) = LOWER(CONVERT("'._es($id).'" USING utf8))';
			} else {
				$where = '`value` = "'._es($id).'"';
			}
			$a = from('locale_vars')->where_raw($where)->get();
			if ($a) {
				$id = $a['id'];
			} else {
				db()->replace_safe('locale_vars', ['value' => $id]);
				$id = db()->insert_id();
				$id && $a = from('locale_vars')->whereid($id)->limit(1)->get();
			}
		}
		return $a;
	}

	/**
	* Cleanup variables (Delete not translated or missed vars)
	*/
	function cleanup () {
# TODO: testme
#		$cls = 'locale_editor'; return _class($cls.'_cleanup', 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
	}

	/**
	* Automatic translator via Google translate
	*/
	function autotranslate() {
# TODO: testme
#		$cls = 'locale_editor'; return _class($cls.'_'.$func, 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
	}

	/**
	* Import vars
	*/
	function import() {
# TODO: testme
#		$cls = 'locale_editor'; return _class($cls.'_import', 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
	}

	/**
	* Export vars
	*/
	function export() {
# TODO: testme
#		$cls = 'locale_editor'; return _class($cls.'_export', 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
	}

	/**
	* Collect vars from source files (Framework included)
	*/
	function collect () {
# TODO: testme
		foreach((array)from('locale_vars')->order_by('LOWER(CONVERT(value USING utf8)) ASC')->all() as $a) {
			$this->_locale_vars[$a['value']] = $a;
		}
		$vars_from_code = $this->_parse_source_code_for_vars();
		foreach ((array)$vars_from_code as $cur_var_name => $var_files_info) {
			$location_array = [];
			foreach ((array)$var_files_info as $file_name => $line_numbers) {
				$location_array[] = $file_name.':'.$line_numbers;
			}
			$location	= implode('; ', $location_array);
			$sql_array	= [
				'value'		=> _es($cur_var_name),
				'location'	=> $location,
			];
#			if (isset($this->_locale_vars[$cur_var_name])) {
#				db()->update_safe('locale_vars', $sql_array, 'id='.intval($this->_locale_vars[$cur_var_name]['id']));
#			} else {
#				db()->insert_safe('locale_vars', $sql_array);
#			}
		}
# TODO: show some report after completion: where and how many found
#		return js_redirect('/@object/vars');
	}

	/**
	* Collect vars from source files, no framework, just project and given module name (internal use only method)
	*/
	function collect_vars_for_module () {
// TODO: move out into submodule
		no_graphics(true);

		$module_name = preg_replace('/[^a-z0-9\_]/i', '', _strtolower(trim($_GET['id'])));
		if (!$module_name) {
			return print 'Error, no module name';
		}

		$vars = $this->_parse_source_code_for_vars([
			'only_project'	=> 1,
			'only_module'	=> $module_name,
		]);

		echo '<pre>';
		foreach ((array)$vars as $var => $paths) {
			echo $var.PHP_EOL;
		}
		echo '</pre>';
	}

	/**
	* Parse source code for translate variables
	*/
	function _parse_source_code_for_vars ($params = []) {
// TODO: move out into submodule
		$vars_array = [];

		$php_path_pattern	= '';
		$stpl_path_pattern	= '';
		if ($params['only_module']) {
			$this->_include_php_pattern	= ['#/(modules)#', '#'.preg_quote($params['only_module'], '#').'\.class\.php$#'];
			$this->_include_stpl_pattern	= ['#/templates#', '#\.stpl$#'];
			$stpl_path_pattern = '#templates/[^/]+/'.$params['only_module'].'/#';
		}
		if (!$params['only_project']) {
			if (!$params['only_stpls']) {
				$yf_framework_php_files	= _class('dir')->scan_dir(YF_PATH, true, $this->_include_php_pattern, $this->_exclude_pattern);
			}
			if (!$params['only_php']) {
				$yf_framework_stpl_files = _class('dir')->scan_dir(YF_PATH, true, $this->_include_stpl_pattern, $this->_exclude_pattern);
			}
		}
		if (!$params['only_framework']) {
			if (!$params['only_stpls']) {
				$cur_project_php_files = _class('dir')->scan_dir(INCLUDE_PATH, true, $this->_include_php_pattern, $this->_exclude_pattern);
			}
			if (!$params['only_php']) {
				$cur_project_stpl_files = _class('dir')->scan_dir(INCLUDE_PATH, true, $this->_include_stpl_pattern, $this->_exclude_pattern);
			}
		}
		foreach ((array)$yf_framework_php_files as $file_name) {
			$short_file_name = str_replace([REAL_PATH, INCLUDE_PATH, YF_PATH], '', $file_name);
			foreach ((array)$this->_get_vars_from_file_name($file_name, $this->_translate_php_pattern) as $cur_var_name => $code_lines) {
				$vars_array[$cur_var_name][$short_file_name] = $code_lines;
			}
		}
		foreach ((array)$cur_project_php_files as $file_name) {
			$short_file_name = str_replace([REAL_PATH, INCLUDE_PATH, YF_PATH], '', $file_name);
			foreach ((array)$this->_get_vars_from_file_name($file_name, $this->_translate_php_pattern) as $cur_var_name => $code_lines) {
				$vars_array[$cur_var_name][$short_file_name] = $code_lines;
			}
		}
		foreach ((array)$yf_framework_stpl_files as $file_name) {
			$short_file_name = str_replace([REAL_PATH, INCLUDE_PATH, YF_PATH], '', $file_name);
			foreach ((array)$this->_get_vars_from_file_name($file_name, $this->_translate_stpl_pattern) as $cur_var_name => $code_lines) {
				$vars_array[$cur_var_name][$short_file_name] = $code_lines;
			}
		}
		foreach ((array)$cur_project_stpl_files as $file_name) {
			$short_file_name = str_replace([REAL_PATH, INCLUDE_PATH, YF_PATH], '', $file_name);
			if ($stpl_path_pattern && !preg_match($stpl_path_pattern, $short_file_name)) {
				continue;
			}
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
		$vars_array = [];
		if (empty($file_name)) {
			return $vars_array;
		}
		$file_source_array = file($file_name);
		$match	= preg_match_all($pattern, implode(PHP_EOL, $file_source_array), $matches);
		if (empty($matches[0])) {
			return $vars_array;
		}
		foreach ((array)$matches[2] as $match_number => $cur_var_name) {
			$code_lines		= [];
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
	* Return array of all used locations in vars
	*/
	function _get_all_vars_locations() {
// TODO: move out into submodule
		$used_locations = [];
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
	* Some of the common languages with their English and native names
	* Based on ISO 639 and http://people.w3.org/rishida/names/languages.html
	*/
	function _get_iso639_list() {
		$cls = 'locale_editor'; return _class($cls.'_langs', 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
	}

	/**
	*/
	function _get_locales () {
		return from('locale_langs')->order_by(['is_default DESC', 'locale ASC'])->get_2d('locale,name');
	}

	/**
	*/
	function filter_save() {
		return _class('admin_methods')->filter_save();
	}

	/**
	*/
	function _show_filter() {
		if (!in_array($_GET['action'], ['vars'])) {
			return false;
		}
		$order_fields = [];
		foreach (explode('|', 'id|locale|source|translation') as $f) {
			$order_fields[$f] = $f;
		}
		$langs_for_select = $this->_langs_for_search;
		return form($r, ['filter' => true])
			->text('value', 'Source var')
			->text('translation')
			->select_box('locale', $langs_for_select)
			->row_start()
				->select_box('order_by', $order_fields, ['show_text' => '= Сортировка =', 'desc' => 'Сортировка'])
				->select_box('order_direction', ['asc' => '⇑', 'desc' => '⇓'])
				->select_box('per_page', self::$per_page_values, ['style' => 'width:100px', 'no_label' => 1])
			->row_end()
			->save_and_clear();
		;
	}

	/**
	*/
	function _help($section, $lang = '') {
		$help = self::$HELP[$section];
		if (!isset($help)) {
			return false;
		}
		css('
			pre.docs-text { background-color: transparent; border: 0; font-family: inherit; font-size: inherit; font-weight: bold; }
			pre.docs-text > code { color: white; }
		');
		$lang = $lang ?: conf('language');
		return '<pre class="docs-text"><code><span class="text-info">'.trim($help[$lang] ?: $help['en'] ?: current($help)).'</span></code></pre>';
	}

	/**
	* Display list of user-specific vars
	*/
	function user_vars() {
		$cls = 'locale_editor'; return _class($cls.'_user_vars', 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
	}

	/**
	*/
	function user_var_edit() {
		$cls = 'locale_editor'; return _class($cls.'_user_vars', 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
	}

	/**
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
}
