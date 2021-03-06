<?php

/**
 * Static/HTML pages content editor.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_static_pages
{
    const table = 'static_pages';


    public function show()
    {
        $_this = $this;
        return table(db()->from(self::table)->order_by('name ASC, locale ASC'), [
                'filter' => true,
                'filter_params' => [
                    'name' => 'like',
                ],
                'group_by' => 'name',
                'pager_records_on_page' => 1000,
            ])
            ->text('name', ['link' => url('/@object/view/%d/%locale'), 'link_params' => 'locale'])
            ->lang('locale')
            ->func('text', function ($text) {
                return strlen($text);
            }, ['desc' => 'Text length'])
            ->date('add_date', ['format' => 'long', 'nowrap' => 1])
            ->btn('View as user', '/static_pages/show/%d/?lang=%locale', ['icon' => 'fa fa-eye', 'btn_no_text' => 1, 'id' => 'name', 'rewrite' => 'user'])
            ->btn_edit('', url('/@object/edit/%d/%locale'), ['no_ajax' => 1, 'btn_no_text' => 1])
            ->btn_delete('', url('/@object/delete/%d/%locale'), ['btn_no_text' => 1])
            ->btn_active('', url('/@object/active/%d/%locale'))
            ->footer_add('', url('/@object/add'), ['no_ajax' => 1, 'copy_to_header' => 1]);
    }


    public function add()
    {
        $a = (array) $_POST + (array) $a;
        $a['back_link'] = url('/@object');
        $a['locale'] = $a['locale'] ?: conf('language');
        $_this = $this;
        return form($a)
            ->validate([
                '__before__' => 'trim',
                'name' => ['required', function (&$in) use ($_this) {
                    $in = $_this->_fix_page_name($in);
                    return (bool) strlen($in);
                }, function ($name, $tmp, $d, &$error) use ($_this) {
                    $ok = ! db()->from($_this::table)->where('locale', $_POST['locale'])->where('name', $name)->get_one('id');
                    if ( ! $ok) {
                        $error = t('Page with this name and locale already exists');
                    }
                    return $ok;
                }],
                'locale' => 'required',
            ])
            ->db_insert_if_ok(self::table, ['name', 'locale', 'active'])
            ->on_after_update(function () use ($_this) {
                $id = db()->insert_id();
                module_safe('manage_revisions')->add($_this::table, $id, 'add');
                common()->admin_wall_add(['static page added: ' . $name, $id]);
                cache_del('static_pages_names');
                js_redirect('/@object/edit/' . $id . '/' . $_POST['locale']);
            })
            ->text('name')
            ->locale_box('locale')
            ->hidden('active')
            ->save_and_back();
    }


    public function edit()
    {
        $a = $this->_get_info();
        if ( ! $a) {
            return _404();
        }
        $a['back_link'] = url('/@object');
        $form_id = 'content_form';
        jquery('
			var form_id = "' . $form_id . '";
			var bak_action = $("form#" + form_id).attr("action");
			var preview_url = "' . url_user('/dynamic/preview/static_pages/' . $a['id']) . '";
			$("[type=submit].preview", "form#" + form_id).on("click", function() {
				$(this).closest("form").attr("target", "_blank").attr("action", preview_url)
			})
			$("[type=submit]:not(.preview)", "form#" + form_id).on("click", function() {
				$(this).closest("form").attr("target", "").attr("action", bak_action)
			})
		');
        // Prevent execution of template tags when editing page content
        $exec_fix = ['{' => '&#123;', '}' => '&#125;'];
        $keys_to_fix = ['text'];
        foreach ((array) $keys_to_fix as $k) {
            if (false !== strpos($a[$k], '{') && false !== strpos($a[$k], '}')) {
                $a[$k] = str_replace(array_keys($exec_fix), array_values($exec_fix), $a[$k]);
            }
        }
        $a = (array) $_POST + (array) $a;
        if (is_post()) {
            foreach ((array) $keys_to_fix as $k) {
                if (false !== strpos($_POST[$k], '{') && false !== strpos($_POST[$k], '}')) {
                    $_POST[$k] = str_replace(array_values($exec_fix), array_keys($exec_fix), $_POST[$k]);
                }
            }
        }
        $_this = $this;
        return form($a, ['hide_empty' => true, 'id' => $form_id])
            ->validate([
                '__before__' => 'trim',
                'name' => ['required', function (&$in) use ($_this) {
                    $in = $_this->_fix_page_name($in);
                    return (bool) strlen($in);
                }, function ($name, $tmp, $d, &$error) use ($_this, $a) {
                    $id = db()->from($_this::table)->where('locale', $a['locale'])->where('name', $name)->get_one('id');
                    if ($id && $id != $a['id']) {
                        $error = t('Page with this name and locale already exists');
                    }
                    return $error ? false : true;
                }],
                'text' => 'required',
            ])
            ->update_if_ok(self::table, ['name', 'text', 'page_title', 'page_heading', 'meta_keywords', 'meta_desc', 'active'], 'id=' . $a['id'])
            ->on_before_update(function () use ($a, $_this) {
                module_safe('manage_revisions')->add([
                    'object_name' => $_this::table,
                    'object_id' => $a['id'],
                    'old' => $a,
                    'new' => $_POST,
                    'action' => 'update',
                ]);
            })
            ->on_after_update(function () {
                common()->admin_wall_add(['static page updated: ' . $a['name'], $a['id']]);
                cache_del('static_pages_names');
            })
            ->container($this->_get_lang_links($a['locale'], $a['name'], 'edit'))
            ->text('name')
            ->textarea('text', ['id' => 'text', 'cols' => 200, 'rows' => 10, 'ckeditor' => ['config' => _class('admin_methods')->_get_cke_config()]])
            ->text('page_title')
            ->text('page_heading')
            ->text('meta_keywords')
            ->text('meta_desc')
            ->active_box()
            ->save_and_back()
            ->preview();
    }


    public function delete()
    {
        $a = $this->_get_info();
        if ($a) {
            module_safe('manage_revisions')->add([
                'object_name' => self::table,
                'object_id' => $a['id'],
                'old' => $a,
                'action' => 'delete',
            ]);
            db()->from(self::table)->whereid($a['id'])->delete();
            common()->admin_wall_add(['static page deleted: ' . $a['id'], $a['id']]);
            cache_del('static_pages_names');
        }
        if (is_ajax()) {
            no_graphics(true);
            echo $page_name;
        } else {
            return js_redirect(url('/@object'));
        }
    }


    public function active()
    {
        $a = $this->_get_info();
        if ( ! empty($a['id'])) {
            $n = $a;
            $n['active'] = (int) ! $a['active'];
            module_safe('manage_revisions')->add([
                'object_name' => self::table,
                'object_id' => $a['id'],
                'old' => $a,
                'new' => $n,
                'action' => 'active',
            ]);
            db()->update(self::table, ['active' => (int) ! $a['active']], (int) $a['id']);
            common()->admin_wall_add(['static page: ' . $a['name'] . ' ' . ($a['active'] ? 'inactivated' : 'activated'), $a['id']]);
            cache_del('static_pages_names');
        }
        if (is_ajax()) {
            no_graphics(true);
            echo (int) ( ! $a['active']);
        } else {
            return js_redirect(url('/@object'));
        }
    }


    public function view()
    {
        $a = $this->_get_info();
        if (empty($a)) {
            return _404();
        }
        // Prevent execution of template tags when editing page content
        if (false !== strpos($a['text'], '{') && false !== strpos($a['text'], '}')) {
            $a['text'] = str_replace(['{', '}'], ['&#123;', '&#125;'], $a['text']);
        }
        $body = stripslashes($a['text']);
        $replace = [
            'form_action' => url('/@object/edit/' . $a['id'] . '/' . $a['locale']),
            'back_link' => url('/@object'),
            'body' => $body,
        ];
        return form($replace)
            ->container($this->_get_lang_links($a['locale'], $a['name'], 'view'))
            ->container($body, '', [
                'id' => 'content_editable',
                'wide' => 1,
                'ckeditor' => [
                    'hidden_id' => 'text',
                    'config' => _class('admin_methods')->_get_cke_config(),
                ],
            ])
            ->hidden('text')
            ->save_and_back();
    }

    /**
     * @param null|mixed $id
     * @param null|mixed $lang
     */
    public function _get_info($id = null, $lang = null)
    {
        $id = isset($id) ? $id : $_GET['id'];
        $lang = isset($lang) ? $lang : $_GET['page'];
        $a = db()->from(self::table)
            ->where('locale', $lang ? strtolower($lang) : '')
            ->where('name', _strtolower(urldecode($id)))
            ->or_where('id', (int) $id)
            ->get();
        if ($a) {
            return $a;
        } elseif ($lang) {
            $all_langs = main()->get_data('locale_langs');
            if ( ! isset($all_langs[$lang])) {
                return false;
            }
            // Try with first lang as fallback
            $a = db()->from(self::table)
                ->where('name', _strtolower(urldecode($id)))
                ->or_where('id', (int) $id)
                ->get();
            // Create missing page
            if ($a && $a['locale'] && $lang !== $locale) {
                $new = $a;
                unset($new['id']);
                $new['active'] = 0;
                $new['locale'] = $lang;
                db()->insert_safe(self::table, $new);
                $new['id'] = db()->insert_id();
                return $new;
            }
            return $a;
        }
        return false;
    }

    /**
     * @param null|mixed $in
     */
    public function _fix_page_name($in = null)
    {
        if ( ! strlen($in)) {
            return '';
        }
        // Detect non-latin characters
        if (strlen($in) !== _strlen($in)) {
            $in = common()->make_translit(_strtolower($in));
        }
        $in = preg_replace('/[^a-z0-9\_\-]/i', '_', strtolower($in));
        $in = trim(str_replace(['__', '___'], '_', $in), '_');
        return $in;
    }

    /**
     * @param null|mixed $cur_lang
     * @param null|mixed $cur_name
     * @param mixed $link_for
     */
    public function _get_lang_links($cur_lang = null, $cur_name = null, $link_for = 'edit')
    {
        asset('bfh-select');
        $this->lang_def_country = main()->get_data('lang_def_country');

        foreach ((array) db()->select('name, locale')->from(self::table)->get_all() as $p) {
            $this->pages_langs[$p['name']][$p['locale']] = $p['locale'];
        }

        $lang_links = [];
        foreach (main()->get_data('locale_langs') as $lang => $l) {
            $is_selected = ($lang === $cur_lang);
            $icon = 'bfh-flag-' . $this->lang_def_country[$lang];
            if ( ! isset($this->pages_langs[$cur_name][$lang])) {
                $icon = ['fa fa-plus', $icon];
                $class = 'btn-warning';
            } else {
                $class = 'btn-success' . ($is_selected ? ' disabled' : '');
            }
            $lang_links[] = a('/@object/' . $link_for . '/' . urlencode($cur_name) . '/' . $lang, strtoupper($lang), $icon, null, $class, '');
        }
        return implode(PHP_EOL, $lang_links) . ' ' . a('/locale_editor', 'Edit locales', 'fa fa-edit');
    }


    public function _show_header()
    {
        $pheader = t('Static pages');
        $subheader = _ucwords(str_replace('_', ' ', $_GET['action']));
        $cases = [
            //$_GET['action'] => {string to replace}
            'show' => '',
            'edit' => '',
        ];
        if (isset($cases[$_GET['action']])) {
            $subheader = $cases[$_GET['action']];
        }
        return [
            'header' => $pheader,
            'subheader' => $subheader ? _prepare_html($subheader) : '',
        ];
    }


    public function filter_save()
    {
        return _class('admin_methods')->filter_save();
    }


    public function _show_filter()
    {
        if ( ! in_array($_GET['action'], ['show'])) {
            return false;
        }
        $order_fields = [
            'name' => 'name',
            'active' => 'active',
        ];
        return form($r, [
                'class' => 'form-vertical',
                'filter' => true,
            ])
            ->text('name')
// TODO: locale
//			->locale_box('locale')
            ->select_box('order_by', $order_fields, ['show_text' => 1])
            ->radio_box('order_direction', ['asc' => 'Ascending', 'desc' => 'Descending'], ['horizontal' => true])
            ->save_and_clear();
    }

    /**
     * @param mixed $params
     */
    public function _hook_widget__static_pages_list($params = [])
    {
        $meta = [
            'name' => 'Static pages quick access',
            'desc' => 'List of static pages with quick links to edit/preview',
            'configurable' => [
                'order_by' => ['id', 'name', 'active'],
            ],
        ];
        if ($params['describe_self']) {
            return $meta;
        }
        $sql = db()->from(self::table);

        $config = $params;
        $avail_orders = $meta['configurable']['order_by'];
        if (isset($avail_orders[$config['order_by']])) {
            $sql->order_by($avail_orders[$config['order_by']]);
        }
        $avail_limits = $meta['configurable']['limit'];
        if (isset($avail_limits[$config['limit']])) {
            $sql->limit($avail_limits[$config['limit']]);
        }
        return table($sql, ['no_header' => 1, 'btn_no_text' => 1])
            ->link('name', url('/@object/view/%d'), '', ['width' => '100%'])
            ->btn_edit();
    }
}
