<?php

/**
 * Locale, i18n (Internationalization) editor.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
#[AllowDynamicProperties]
class yf_locale_editor
{
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

    /** @var bool Ignore case on import/export */
    public $VARS_IGNORE_CASE = true;
    /** @var bool Ignore case on import/export */
    public $FILE_MANAGER_ALLOWED = false;
    /** @var bool @conf_skip */
    private $_preload_complete = false;

    /**
     * @param mixed $name
     */
    public function __get($name)
    {
        if ( ! $this->_preload_complete) {
            $this->_preload_data();
        }
        return $this->$name;
    }

    /**
     * @param mixed $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        if ( ! $this->_preload_complete) {
            $this->_preload_data();
        }
        $this->$name = $value;
        return $this->$name;
    }


    public function _preload_data()
    {
        if ($this->_preload_complete) {
            return true;
        }
        $this->_preload_complete = true;

        asset('bfh-select');
        $this->lang_def_country = main()->get_data('lang_def_country');

        $this->_modules = _class('admin_methods')->find_active_modules();

        $langs = [];
        foreach ((array) $this->_get_iso639_list() as $code => $v) {
            $langs[$code] = t($v[0]) . ($v[1] ? ' (' . $v[1] . ') ' : '');
        }
        $this->_langs = $langs;

        $this->_cur_langs_array = from('locale_langs')->order_by('is_default DESC, locale ASC')->all();
        if ( ! $this->_cur_langs_array) {
            db()->insert_safe('locale_langs', [
                'locale' => 'en',
                'name' => t('English'),
                'charset' => 'utf-8',
                'active' => 1,
                'is_default' => 1,
            ]);
            return js_redirect('/@object/@action/@id');
        }
        $this->_cur_langs = [];
        foreach ((array) $this->_cur_langs_array as $a) {
            $this->_cur_langs[$a['locale']] = t($a['name']);
        }
        $this->_import_export_file_formats = [
            'php' => t('PHP array file format'),
            'json' => t('JSON file format'),
            'yaml' => t('YAML file format'),
            'csv' => t('CSV, compatible with MS Excel'),
        ];
        $this->_import_modes = [
            1 => t('Strings in the uploaded file replace existing ones, new ones are added'),
            2 => t('Existing strings are kept, only new strings are added'),
        ];
    }


    public function _show_quick_filter()
    {
        if ( ! in_array($_GET['action'], ['vars'])) {
            return false;
        }
        $filter = _class('admin_methods')->_get_filter(['filter_name' => $_GET['object'] . '__show']);
        $is_current = function ($item) use ($filter) {
            return $filter[$name] ? ' disabled' : '';
        };
        $a = [];
        $a[] = a('/@object/filter_save/clear/?filter=in_db:1', 'Filter in db', 'fa fa-database', '', 'btn-info', '');
        $a[] = a('/@object/filter_save/clear/?filter=in_files:1', 'Filter in files', 'fa fa-file', '', 'btn-info', '');
        $a[] = a('/@object/filter_save/clear/?filter=has_tr:1', 'Filter has translations', 'fa fa-asterisk', '', 'btn-info', '');
        foreach ((array) $this->_cur_langs as $lang => $name) {
            $a[] = a('/@object/filter_save/clear/?filter=locale:' . $lang, 'Filter translations for ' . $name, 'bfh-flag-' . $this->lang_def_country[$lang], strtoupper($lang), 'btn-default', '');
        }
        $a[] = a('/@object/filter_save/clear/', 'Clear filter', 'fa fa-close', '', 'btn-primary', '');
        return $a ? '<div class="pull-right">' . implode(PHP_EOL, $a) . '</div>' : '';
    }


    public function _header_links()
    {
        $is_current = function ($action) {
            return $_GET['action'] == $action ? ' disabled' : '';
        };
        $header_links = table([], ['no_records_html' => ''])
            ->header_link('Langs', url('/@object/show'), ['icon' => 'fa fa-globe', 'class_add' => 'btn-default ' . $is_current('show')])
            ->header_link('Translate', url('/@object/autotranslate'), ['icon' => 'fa fa-cogs', 'class_add' => 'btn-success ' . $is_current('autotranslate')])
            ->header_link('Collect', url('/@object/collect'), ['icon' => 'fa fa-flask', 'class_add' => 'btn-warning ' . $is_current('collect')])
            ->header_link('Cleanup', url('/@object/cleanup'), ['icon' => 'fa fa-eraser', 'class_add' => 'btn-danger ' . $is_current('cleanup')])
            ->header_link('Import', url('/@object/import'), ['icon' => 'fa fa-download', 'class_add' => 'btn-info ' . $is_current('import')])
            ->header_link('Export', url('/@object/export'), ['icon' => 'fa fa-upload', 'class_add' => 'btn-info ' . $is_current('export')])
            ->header_link('Files', url('/@object/files'), ['icon' => 'fa fa-files-o', 'class_add' => 'btn-primary ' . $is_current('files')])
            ->header_link('Sources', url('/@object/sources'), ['icon' => 'fa fa-bars', 'class_add' => 'btn-primary ' . $is_current('sources')])
            ->header_link('Vars', url('/@object/vars'), ['icon' => 'fa fa-language', 'class_add' => 'btn-primary ' . $is_current('vars')]);
        return
            '<div class="col-md-12">' .
                '<div class="col-md-6">' . $header_links . '</div>' .
                '<div class="col-md-6 pull-right" title="' . t('Quick filter') . '">' . $this->_show_quick_filter() . '</div>' .
            '</div>';
    }

    /**
     * Display all project languages.
     */
    public function show()
    {
        $all_vars = $this->_get_all_vars();
        $total_vars = count((array) $all_vars);
        $tr_vars = [];
        foreach ((array) $all_vars as $source => $v) {
            if ( ! isset($v['translation'])) {
                continue;
            }
            foreach ((array) $v['translation'] as $lang => $tr) {
                if ( ! $tr || $tr == $source) {
                    continue;
                }
                @$tr_vars[$lang]++;
            }
        }
        $data = [];
        foreach ((array) $this->_cur_langs_array as $v) {
            $id = $v['locale'];
            $v['tr_count'] = (string) ($tr_vars[$id]);
            $v['tr_percent'] = $total_vars && $v['tr_count'] ? round(100 * $v['tr_count'] / $total_vars, 2) . '%' : '';
            $data[$id] = $v;
        }
        $no_actions_if_default = function ($row) {
            return $row['is_default'] ? false : true;
        };
        $_this = $this;
        return $this->_header_links() .
            table($data, [
                'pager_records_on_page' => 1000,
                'hide_empty' => 1,
                'no_total' => 1,
            ])
            ->func('locale', function ($lang) {
                return $this->_lang_icon($lang, false);
            })
            ->text('name')
            ->text('charset')
            ->text('tr_count', 'Num vars')
            ->text('tr_percent', 'Translated', ['badge' => 'info'])
            ->func('is_default', function ($is) {
                return $is ? '<span class="label label-info">' . t('DEFAULT') . '</span>' : '';
            })
            ->btn_edit('', url('/@object/lang_edit/%d'), ['btn_no_text' => 1])
            ->btn_delete('', url('/@object/lang_delete/%d'), ['display_func' => $no_actions_if_default, 'btn_no_text' => 1])
            ->btn('Make default', url('/@object/lang_default/%d'), ['class_add' => 'btn-info', 'display_func' => $no_actions_if_default, 'btn_no_text' => 1])
            ->btn_active('', url('/@object/lang_active/%d'), ['display_func' => $no_actions_if_default])
            ->footer_add('Add', url('/@object/lang_add'), ['no_ajax' => 1, 'class_add' => 'btn-warning']);
    }


    public function lang_add()
    {
        $raw = $this->_get_iso639_list();
        $langs = [];
        foreach ($raw as $code => $v) {
            if (isset($this->_cur_langs[$code])) {
                continue;
            }
            $langs[$code] = implode(' | ', $v);
        }
        $a['redirect_link'] = url('/@object');
        return form((array) $_POST + (array) $a)
            ->validate(['locale' => ['trim|required', function ($in) use ($langs) {
                return isset($langs[$in]);
            }]])
            ->insert_if_ok('sys_locale_langs', ['locale'], [
                'name' => $raw[$_POST['locale']][0] ?? '',
                'charset' => 'utf-8',
                'active' => 0,
                'is_default' => 0,
            ])
            ->on_after_update(function () {
                cache_del('locale_langs');
            })
            ->select_box('locale', $langs)
            ->save('Add');
    }


    public function lang_edit()
    {
        $id = (int) ($_GET['id']);
        $id && $a = from('locale_langs')->whereid($id)->get();
        if ( ! $a) {
            return _e('No id');
        }
        $a = (array) $_POST + (array) $a;
        $a['redirect_link'] = url('/@object');
        return form($a, ['autocomplete' => 'off'])
            ->validate([
                'name' => 'trim|required|is_unique_without[locale_langs.name.' . $id . ']',
                'charset' => 'trim|required',
            ])
            ->db_update_if_ok('locale_langs', ['name', 'charset'], 'id=' . $id)
            ->on_after_update(function () {
                cache_del('locale_langs');
                common()->admin_wall_add(['locale lang updated: ' . $_POST['name'] . '', $id]);
            })
            ->info('locale')
            ->text('name')
            ->text('charset')
            ->save_and_back();
    }


    public function lang_active()
    {
        $id = (int) ($_GET['id']);
        $id && $a = from('locale_langs')->whereid($id)->get();
        if ( ! empty($a) && ! $a['is_default']) {
            db()->update_safe('locale_langs', ['active' => (int) ( ! $a['active'])], 'id=' . (int) $id);
            common()->admin_wall_add(['locale lang ' . $a['name'] . ' ' . ($a['active'] ? 'inactivated' : 'activated'), $id]);
            cache_del(['locale_langs']);
        }
        if (is_ajax()) {
            no_graphics(true);
            echo $a['active'] ? 0 : 1;
        } else {
            return js_redirect('/@object');
        }
    }


    public function lang_default()
    {
        $id = (int) ($_GET['id']);
        $id && $a = from('locale_langs')->whereid($id)->get();
        if ( ! empty($a) && ! $a['is_default']) {
            db()->update_safe('locale_langs', ['is_default' => 0], '1 = 1');
            db()->update_safe('locale_langs', ['is_default' => 1], 'id = ' . (int) $id);
            common()->admin_wall_add(['locale lang ' . $a['name'] . ' made default', $id]);
            cache_del(['locale_langs']);
        }
        if (is_ajax()) {
            no_graphics(true);
            echo 1;
        } else {
            return js_redirect('/@object');
        }
    }


    public function lang_delete()
    {
        $id = (int) ($_GET['id']);
        $id && $a = from('locale_langs')->whereid($id)->get();
        if ($a) {
            $lang = $this->_cur_langs_array[$id]['locale'];
            db()->delete('locale_langs', $id);
            db()->delete('locale_translate', 'locale = "' . _es($lang) . '"');
            common()->admin_wall_add(['locale language deleted: ' . $lang, $id]);
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
     * @param mixed $lang
     * @param mixed $btn
     */
    public function _lang_icon($lang = 'en', $btn = false)
    {
        $icon = html()->icon('bfh-flag-' . $this->lang_def_country[$lang], strtoupper($lang));
        if ( ! $lang) {
            return false;
        }
        return $btn ? '<span class="btn btn-xs btn-primary disabled">' . $icon . '</span>' : $icon;
    }


    public function files()
    {
        $self_page_css = 'body.get-object-' . $_GET['object'];
        css('
			' . $self_page_css . ' li.li-header { list-style: none; display:none; }
			' . $self_page_css . ' li.li-level-0 { display: block; font-size: 15px; }
			' . $self_page_css . ' li.li-level-1 { padding-top: 10px; font-size: 13px; }
			' . $self_page_css . ' .source_container { width: 90%; height: 400px; }
		');
        jquery('
			var self_page = "' . $self_page_css . '";
			$(".li-level-0 > a", self_page).before("&nbsp;<button class=\"btn btn-mini btn-xs btn-default\" class=\"toggle_source\"><i class=\"fa fa-toggle-down\"></i> Toggle source</button>&nbsp;")
			$(".li-level-0 .togle_source", self_page).click(function(){
				$(".li-level-1", $(this).closest(".li-level-0")).toggle()
			})
			$(".li-level-0", self_page).click(function(){
				$(".li-level-1", this).toggle()
			})
		');
        $all_langs = (array) $this->_cur_langs;
        foreach ((array) $all_langs as $lang => $name) {
            list($lang_vars, $var_files, $lang_files) = $this->_get_vars_from_files($lang);
            if ( ! $lang_files) {
                continue;
            }
            $body[] = '<h3>' . $this->_lang_icon($lang, false) . '</h3>';
            $body[] = $this->_show_files_for_lang($lang, $lang_files, $var_files);
        }
        return $this->_header_links() . implode(PHP_EOL, $body);
    }


    public function sources()
    {
        $vars = $this->_get_all_vars_from_db();
        $files = [];
        foreach ((array) $vars as $source => $a) {
            if ( ! $a['location']) {
                continue;
            }
            $source = trim($source);
            foreach (explode(';', $a['location']) as $raw) {
                list($file, $lines) = explode(':', $raw);
                $file = trim($file);
                $lines = trim($lines);
                $file && @$files[$file][$source]++;
            }
        }
        ksort($files);
        foreach ((array) $files as $k => $v) {
            ksort($v);
            $files[$k] = '<small>' . implode('<br>', _prepare_html(array_keys($v))) . '</small>';
        }
        return $this->_header_links() . html()->simple_table($files);
    }

    /**
     * @param mixed $lang
     * @param mixed $lang_files
     * @param mixed $var_files
     */
    public function _show_files_for_lang($lang, $lang_files, $var_files)
    {
        $yf_path_len = strlen(YF_PATH);
        $app_path_len = strlen(APP_PATH);

        $vars_by_path = [];
        foreach ((array) $var_files as $source => $path) {
            @$vars_by_path[$path]++;
        }
        $i = 0;
        foreach ((array) $lang_files as $path) {
            $i++;
            $name = $path;
            if (substr($name, 0, $yf_path_len) === YF_PATH) {
                $name = '[YF] ' . substr($name, $yf_path_len);
            } elseif (substr($name, 0, $app_path_len) === APP_PATH) {
                $name = '[APP] ' . substr($name, $app_path_len);
            }
            $name .= ' <span class="text-info">[vars: ' . (int) $vars_by_path[$path] . ']</span>';
            $items[$i] = [
                'parent_id' => 0,
                'name' => $name,
                'link' => $this->FILE_MANAGER_ALLOWED ? url('/file_manager/view/' . urlencode($path)) : 'javascript:void()',
                'id' => 'lang_file_' . $i,
            ];
            $div_id = 'editor_html_' . $lang . '_' . $i;
            $hidden_id = 'file_text_hidden_' . $lang . '_' . $i;
            $items['1111' . $i] = [
                'parent_id' => $i,
                'body' => form()
                    ->container('<div id="' . $div_id . '" class="source_container">' . _prepare_html(addslashes(file_get_contents($path))) . '</div>', '', [
                        'id' => $div_id, 'wide' => 1, 'ace_editor' => ['mode' => common()->get_file_ext($path)],
                    ])
                    ->hidden($hidden_id),
            ];
        }
        return html()->li_tree($items);
    }

    /**
     * @param mixed $lang
     */
    public function _get_vars_from_files($lang)
    {
        $files = [];
        // Auto-find shared language vars. They will be connected in order of file system
        // Names can be any, but better to include lang name into file name. Examples:
        // share/langs/ru/001_other.php
        // share/langs/ru/002_other2.php
        // share/langs/ru/other.php
        // share/langs/ru/ru_shop.php
        // plugins/shop/share/langs/ru/ru_user_register.php
        $ext = '.php';
        $patterns = [
            'framework' => YF_PATH . 'langs/' . $lang . '/*'. $ext,
            'framework_plugins' => YF_PATH . 'plugins/*/langs/' . $lang . '/*' . $ext,
            'framework_share' => YF_PATH . 'share/langs/' . $lang . '/*'. $ext,
            'project' => PROJECT_PATH . 'langs/' . $lang . '/*'. $ext,
            'project_plugins' => PROJECT_PATH . 'plugins/*/langs/' . $lang . '/*' . $ext,
            'project_share' => PROJECT_PATH . 'share/langs/' . $lang . '/*'. $ext,
            'app' => APP_PATH . 'langs/' . $lang . '/*'. $ext,
            'app_plugins' => APP_PATH . 'plugins/*/langs/' . $lang . '/*' . $ext,
            'app_share' => APP_PATH . 'share/langs/' . $lang . '/*'. $ext,
        ];
        foreach ($patterns as $glob) {
            foreach (glob($glob) as $f) {
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
        $ext = '.php';
        $patterns = [
            'framework' => YF_PATH . $modules . '/*/__locale__' . $lang . '*' . $ext,
            'framework_plugins' => YF_PATH . 'plugins/*/'. $modules . '/*/__locale__' . $lang . '*' . $ext,
            'project' => PROJECT_PATH . $modules . '/*/__locale__' . $lang . '*' . $ext,
            'project_plugins' => PROJECT_PATH . 'plugins/*/' . $modules . '/*/__locale__' . $lang . '*' . $ext,
            'app' => APP_PATH . $modules . '/*/__locale__' . $lang . '*' . $ext,
            'app_plugins' => APP_PATH . 'plugins/*/' . $modules . '/*/__locale__' . $lang . '*' . $ext,
        ];
        // Order matters! Project vars will have ability to override vars from franework
        foreach ($patterns as $glob) {
            foreach (glob($glob) as $f) {
                $files[basename($f)] = $f;
            }
        }
        foreach ((array) $files as $path) {
            $data = include $path;
            foreach ((array) $data as $source => $tr) {
                $this->VARS_IGNORE_CASE && $source = _strtolower($source);
                $tr_vars[$source] = $tr;
                $tr_files[$source] = $path;
            }
        }
        return [$tr_vars, $tr_files, $files];
    }


    public function _get_all_vars_from_files()
    {
        $vars = [];
        foreach ((array) $this->_cur_langs as $lang => $lang_name) {
            list($lang_vars, $var_files) = $this->_get_vars_from_files($lang);
            foreach ((array) $lang_vars as $source => $tr) {
                if ( ! $source) {
                    continue;
                }
                $this->VARS_IGNORE_CASE && $source = _strtolower($source);
                ! is_array($vars[$source]) && $vars[$source] = [];
                $vars[$source]['id'] = $source;
                $vars[$source]['source'] = $source;
                $vars[$source]['locale'][$lang] = $lang;
                $vars[$source]['translation'][$lang] = $tr;
                $vars[$source]['files'][$var_files[$source]] = $var_files[$source];
            }
        }
        return $vars;
    }


    public function _get_all_vars_from_db()
    {
        $vars = [];
        $lang_ids = array_keys($this->_cur_langs);
        $tr_all = [];
        foreach ((array) from('locale_translate')->where_raw('locale IN("' . implode('","', $lang_ids) . '")')->all() as $a) {
            $tr_all[$a['var_id']][$a['locale']] = $a['value'];
        }
        foreach ((array) from('locale_vars')->all() as $a) {
            $var_id = $a['id'];
            $source = $a['value'];
            $this->VARS_IGNORE_CASE && $source = _strtolower($source);
            $vars[$source]['id'] = $source;
            $vars[$source]['source'] = $source;
            $trs = $tr_all[$var_id];
            foreach ((array) $trs as $lang => $tr) {
                $vars[$source]['locale'][$lang] = $lang;
                $vars[$source]['translation'][$lang] = $tr;
            }
            $vars[$source]['var_id'] = $var_id;
            $vars[$source]['location'] = $a['location'];
            $vars[$source]['add_date'] = $a['add_date'];
        }
        return $vars;
    }


    public function _get_all_vars()
    {
        $vars = $this->_get_all_vars_from_files();
        $vars_db = $this->_get_all_vars_from_db();
        foreach ((array) $vars_db as $source => $a) {
            foreach ($a as $k => $v) {
                $vars[$source][$k] = $v;
            }
        }
        return $vars;
    }


    public function vars()
    {
        $vars = $this->_get_all_vars_from_files();
        $vars_db = $this->_get_all_vars_from_db();
        foreach ((array) $vars_db as $source => $a) {
            foreach ($a as $k => $v) {
                $vars[$source][$k] = $v;
            }
        }
        $filter = _class('admin_methods')->_get_filter(['filter_name' => $_GET['object'] . '__show']);
        if ($filter) {
            foreach ((array) $vars as $source => $a) {
                if ($filter['in_db'] && ! $a['var_id']) {
                    unset($vars[$source]);
                    continue;
                } elseif ($filter['in_files'] && ! $a['files']) {
                    unset($vars[$source]);
                    continue;
                } elseif ($filter['has_tr'] && ! $a['translation']) {
                    unset($vars[$source]);
                    continue;
                } elseif ($filter['locale'] && ! $a['translation'][$filter['locale']]) {
                    unset($vars[$source]);
                    continue;
                }
            }
        }

        $edit_link_tpl = url('/@object/var_edit/%id');

        ksort($vars);
        return $this->_header_links() .
            table($vars, ['pager_records_on_page' => 10000, 'id' => 'source', 'very_condensed' => 1])
            ->func('source', function ($in, $e, $a, $t) {
                return _wordwrap(_prepare_html($in), 120, '<br>');
            }, ['desc' => 'Var name'])
            ->func('source', function ($in, $e, $a, $t) use ($vars_db) {
                return isset($vars_db[$in]['translation']) ? (string) implode(',', array_keys($vars_db[$in]['translation'])) : '';
            }, ['desc' => 'Db override'])
            ->func('id', function ($in, $e, $a, $t) {
                return isset($a['files']) ? (int) count((array) $a['files']) : '';
            }, ['desc' => 'Num files'])
            ->func('id', function ($in, $e, $a, $t) {
                $trs = $a['locale'];
                foreach ((array) $trs as $lang) {
                    $out[] = $this->_lang_icon($lang, true);
                }
                return $out ? implode(' ', $out) : '';
            }, ['desc' => 'Langs'])
            ->func('source', function ($in, $e, $a, $t) {
                return isset($a['add_date']) ? '<small>' . $a['add_date'] . '</small>' : '';
            }, ['desc' => 'Add date'])
            ->btn_edit('', url('/@object/var_edit/%source'), ['btn_no_text' => 1])
            ->btn_delete('', url('/@object/var_delete/%source'), ['btn_no_text' => 1, 'display_func' => function ($a) {
                return $a['var_id'] ? 1 : 0;
            }])
            ->header_add('', url('/@object/var_add'), ['btn_no_text' => 1, 'class_add' => 'btn-warning', 'no_ajax' => 1])
            ->footer_add('', url('/@object/var_add'), ['btn_no_text' => 1, 'class_add' => 'btn-warning', 'no_ajax' => 1]);
    }


    public function var_edit()
    {
        $a = $this->_get_var_info($_GET['id']);
        if ( ! $a) {
            return _e('Wrong var id');
        }
        $var_db = $a;

        $langs = [];
        foreach ((array) $this->_cur_langs_array as $l) {
            $langs[$l['locale']] = $l['name'];
        }

        $vars = $this->_get_all_vars_from_files();
        $var = $vars[$a['value']];
        foreach ((array) $langs as $lang => $name) {
            $a['translation_' . $lang] = $var['translation'][$lang];
        }

        // Override from db
        $var_tr_db = from('locale_translate')->where('var_id', (int) $a['id'])->get_2d('locale,value');
        foreach ((array) $var_tr_db as $lang => $tr) {
            $a['translation_' . $lang] = $tr;
        }

        $a['back_link'] = url('/@object/vars');
        $a['redirect_link'] = url('/@object/@action/@id');

        $form = form($a);
        $form->container('<b><big class="text-success">' . _prepare_html($a['value']) . '</big></b>');
        foreach ((array) $langs as $lang => $name) {
            $form->textarea('translation_' . $lang, ['desc' => $this->_lang_icon($lang, true), 'placeholder' => $name]);
        }
        $form->on_post(function ($a, $r, $f) use ($langs, $var, $var_db, $var_tr_db) {
            $up = [];
            $var_id = $a['id'];
            foreach ((array) $langs as $lcode => $lname) {
                $p = &$_POST;
                $posted = trim($p['translation_' . $lcode]);
                $existed = trim($a['translation_' . $lcode]);
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
        foreach ((array) $files as $k => $path) {
            if (strpos($path, YF_PATH) === 0) {
                $files[$k] = '[YF]&nbsp;' . substr($path, strlen(YF_PATH));
            } elseif (strpos($path, APP_PATH) === 0) {
                $files[$k] = '[APP]&nbsp;' . substr($path, strlen(APP_PATH));
            }
            if (preg_match('~/langs/(?P<lang>[a-z]{2})/~i', $files[$k], $m)) {
                $files[$k] = $this->_lang_icon($m['lang'], true) . '&nbsp;' . $files[$k];
            }
        }
        $files && $storages[] = '<div class="col-md-offset-3"><h3>Files</h3><b>' . implode('<br>', $files) . '</b></div>';
        $langs_in_db_icons = [];
        if ($var_tr_db) {
            foreach ((array) array_keys($var_tr_db) as $lang) {
                $langs_in_db_icons[$lang] = $this->_lang_icon($lang, true);
            }
        }
        $var_tr_db && $storages[] = '<div class="col-md-offset-3"><h3>Db</h3><b>' . implode(' ', $langs_in_db_icons) . '</b></div>';

        if (is_ajax()) {
            return $form . implode($storages);
        }
        return
            '<div class="col-md-8">' . $form . implode($storages) . '</div>' .
            '<div class="col-md-4">' . $help . '</div>';
    }


    public function var_add()
    {
        $a['back_link'] = url('/@object/vars');
        $a['redirect_link'] = $a['back_link'];
        if (is_post()) {
            $val = trim($_POST['value']);
            strlen($val) && $a['redirect_link'] = url('/@object/var_edit/' . urlencode($val));
        }
        return form($a + (array) $_POST)
            ->validate(['value' => 'trim|required'])
            ->db_insert_if_ok('locale_vars', ['value'])
            ->text('value')
            ->save_and_back();
    }


    public function var_delete()
    {
        $a = $this->_get_var_info($_GET['id']);
        if ($a['id']) {
            $id = (int) $a['id'];
            db()->delete('locale_vars', $id);
            db()->delete('locale_translate', 'var_id = ' . (int) $id);
        }
        if (is_ajax()) {
            no_graphics(true);
            echo $_GET['id'];
        } else {
            return js_redirect('/@object/vars');
        }
    }

    /**
     * @param mixed $id
     */
    public function _get_var_info($id)
    {
        $id = trim($id);
        if ( ! strlen($id)) {
            return [];
        }
        $a = [];
        if (is_numeric($id)) {
            $a = from('locale_vars')->whereid($id)->limit(1)->get();
        } else {
            $this->VARS_IGNORE_CASE && $id = _strtolower($id);
            if ($this->VARS_IGNORE_CASE) {
                $where = 'LOWER(CONVERT(`value` USING utf8)) = LOWER(CONVERT("' . _es($id) . '" USING utf8))';
            } else {
                $where = '`value` = "' . _es($id) . '"';
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
     * Cleanup variables (Delete not translated or missed vars).
     */
    public function cleanup()
    {
        $cls = 'locale_editor';
        $func = __FUNCTION__;
        return _class($cls . '_' . $func, 'admin_modules/' . $cls . '/')->$func();
    }

    /**
     * Export vars.
     */
    public function export()
    {
        $cls = 'locale_editor';
        $func = __FUNCTION__;
        return _class($cls . '_' . $func, 'admin_modules/' . $cls . '/')->$func();
    }

    /**
     * Import vars.
     */
    public function import()
    {
        $cls = 'locale_editor';
        $func = __FUNCTION__;
        return _class($cls . '_' . $func, 'admin_modules/' . $cls . '/')->$func();
    }

    /**
     * Collect vars from source files (Framework included).
     */
    public function collect()
    {
        $cls = 'locale_editor';
        $func = __FUNCTION__;
        return _class($cls . '_' . $func, 'admin_modules/' . $cls . '/')->$func();
    }

    /**
     * Automatic translator via Google translate API.
     */
    public function autotranslate()
    {
        $cls = 'locale_editor';
        $func = __FUNCTION__;
        return _class($cls . '_' . $func, 'admin_modules/' . $cls . '/')->$func();
    }


    public function update_files_back()
    {
        // TODO
    }


    public function swap_translations()
    {
        // TODO
    }

    /**
     * Display list of user-specific vars.
     */
    public function user_vars()
    {
        // TODO: cleanup
//		$cls = 'locale_editor'; return _class($cls.'_user_vars', 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
    }


    public function user_var_edit()
    {
        // TODO: cleanup
//		$cls = 'locale_editor'; return _class($cls.'_user_vars', 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
    }


    public function user_var_delete()
    {
        // TODO: cleanup
//		$cls = 'locale_editor'; return _class($cls.'_user_vars', 'admin_modules/'.$cls.'/')->{__FUNCTION__}();
    }

    /**
     * Push user var into main traslation table.
     * @param mixed $FORCE_ID
     */
    public function user_var_push($FORCE_ID = false)
    {
        // TODO: cleanup
//		$cls = 'locale_editor'; return _class($cls.'_user_vars', 'admin_modules/'.$cls.'/')->{__FUNCTION__}($FORCE_ID);
    }

    /**
     * Some of the common languages with their English and native names
     * Based on ISO 639 and http://people.w3.org/rishida/names/languages.html.
     */
    public function _get_iso639_list()
    {
        $cls = 'locale_editor';
        return _class($cls . '_langs', 'admin_modules/' . $cls . '/')->{__FUNCTION__}();
    }


    public function _get_locales()
    {
        return from('locale_langs')->order_by(['is_default DESC', 'locale ASC'])->get_2d('locale,name');
    }


    public function filter_save()
    {
        return _class('admin_methods')->filter_save(['redirect_url' => url('/@object/vars')]);
    }


    public function _show_filter()
    {
        if ( ! in_array($_GET['action'], ['vars'])) {
            return false;
        }
        $order_fields = [];
        foreach (explode('|', 'id|locale|source|translation') as $f) {
            $order_fields[$f] = $f;
        }
        $per_page = ['' => '', 10 => 10, 20 => 20, 50 => 50, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000, 2000 => 2000, 5000 => 5000];
        return form($r, ['filter' => true])
            ->text('value', 'Source var')
            ->text('translation')
            ->select_box('locale', $this->_cur_langs)
            ->row_start()
                ->select_box('order_by', $order_fields, ['show_text' => '= Сортировка =', 'desc' => 'Сортировка'])
                ->select_box('order_direction', ['asc' => '⇑', 'desc' => '⇓'])
                ->select_box('per_page', $per_page, ['style' => 'width:100px', 'no_label' => 1])
            ->row_end()
            ->save_and_clear();
    }

    /**
     * @param mixed $section
     * @param mixed $lang
     */
    public function _help($section, $lang = '')
    {
        $help = self::$HELP[$section];
        if ( ! isset($help)) {
            return false;
        }
        $lang = $lang ?: conf('language');
        return $this->_pre_text(trim($help[$lang] ?: $help['en'] ?: current($help)));
    }

    /**
     * @param mixed $text
     * @param mixed $class
     */
    public function _pre_text($text = '', $class = 'text-info')
    {
        css('
			pre.docs-text { background-color: transparent; border: 0; font-family: inherit; font-size: inherit; font-weight: bold; }
			pre.docs-text > code { color: white; }
		');
        return '<pre class="docs-text"><code><span class="' . $class . '">' . $text . '</span></code></pre>';
    }
}
