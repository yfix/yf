<?php

class yf_manage_faq
{
    const table = 'faq';


    public function _init()
    {
        asset('bfh-select');
        if ( ! isset($this->lang_def_country)) {
            $this->lang_def_country = main()->get_data('lang_def_country');
        }
        $this->all_langs = main()->get_data('locale_langs');
    }


    public function show()
    {
        foreach ((array) $this->all_langs as $lang => $info) {
            $body[] = '<h3>' . html()->icon('bfh-flag-' . $this->lang_def_country[$lang], strtoupper($lang)) . '</h3>';
            $body[] = $this->_show_for_lang($lang);
        }
        return implode(PHP_EOL, $body);
    }

    /**
     * @param mixed $lang
     */
    public function _show_for_lang($lang)
    {
        $_GET['page'] = $lang; // Needed for html()->tree links
        $all = $this->_get_items($lang);
        $items = [];
        foreach ($all as $a) {
            $items[$a['id']] = [
                'parent_id' => $a['parent_id'],
                'name' => _truncate($a['title'], 60, true, '...'),
                'link' => url('/@object/edit/' . $a['id'] . '/@page'),
                'active' => $a['active'],
            ];
        }
        return html()->tree($items, [
            'form_action' => url('/@object/save/@id/@page'),
            'draggable' => true,
            'class_add' => 'no_hide_controls',
            'back_link' => '',
            'add_link' => url('/@object/add/@id/@page'),
            'add_no_ajax' => true,
            'no_expand' => true,
            'opened_levels' => 10, // very deep
            'show_controls' => function ($id, $item) {
                $form = form_item($item + [
                    'add_link' => url('/@object/add/' . $id . '/@page'),
                    'edit_link' => url('/@object/edit/' . $id . '/@page'),
                    'delete_link' => url('/@object/delete/' . $id . '/@page'),
                    'active_link' => url('/@object/active/' . $id . '/@page'),
                ]);
                return implode(PHP_EOL, [
                    $form->tbl_link_add(['hide_text' => 1, 'no_ajax' => 1]),
                    $form->tbl_link_edit(['hide_text' => 1, 'no_ajax' => 1]),
                    $form->tbl_link_delete(['hide_text' => 1, 'no_ajax' => 1]),
                    $form->tbl_link_active(),
                ]);
            },
        ]);
    }


    public function save()
    {
        $lang = $_GET['page'];
        if ( ! is_post() || ! $lang || ! isset($this->all_langs[$lang])) {
            return js_redirect(url('/@object'));
        }
        $items = $this->_recursive_get_items($lang);
        $old_info = $this->_auto_update_items_orders($lang);
        $batch = [];
        $old = [];
        foreach ((array) json_decode((string) $_POST['items'], $assoc = true) as $order_id => $info) {
            $item_id = (int) $info['item_id'];
            if ( ! $item_id || ! isset($items[$item_id])) {
                continue;
            }
            $parent_id = (int) $info['parent_id'];
            $new_data = [
                'id' => $item_id,
                'order' => (int) $order_id,
                'parent_id' => (int) $parent_id,
            ];
            $old_info = $cur_items[$item_id];
            $old_data = [
                'id' => $item_id,
                'order' => (int) ($old_info['order']),
                'parent_id' => (int) ($old_info['parent_id']),
            ];
            if ($new_data != $old_data) {
                $batch[$item_id] = $new_data;
                $old[$item_id] = $old_data;
            }
        }
        if ($batch) {
            db()->update_batch_safe(self::table, $batch);
            //			foreach ((array)$batch as $item_id => $info) {
//				module_safe('manage_revisions')->add(array(
//					'object_name'	=> self::table,
//					'object_id'		=> $item_id,
//					'old'			=> $old[$item_id],
//					'new'			=> $batch[$item_id],
//					'action'		=> 'drag',
//				));
//			}
        }
        return js_redirect(url('/@object'));
    }


    public function add()
    {
        $a['locale'] = substr($_GET['page'], 0, 2) ?: 'ru';
        $a['parent_id'] = (int) $_GET['id'];
        $parent = $a['parent_id'] ? (array) from(self::table)->whereid($a['parent_id'])->get() : [];
        if ( ! $a['locale'] && $parent['locale']) {
            $a['locale'] = $parent['locale'];
        }
        $a['back_link'] = url('/@object');
        $_this = $this;
        return form((array) $_POST + (array) $a)
            ->validate([
                'title' => 'trim|required',
                'text' => 'trim',
            ])
            ->db_insert_if_ok(self::table, ['title', 'text', 'parent_id', 'active', 'locale'], ['add_date' => time(), 'author_id' => main()->ADMIN_ID])
            ->on_after_update(function () use ($_this) {
                $id = db()->insert_id();
                module_safe('manage_revisions')->add($_this::table, $id, 'add');
                js_redirect(url('/@object'));
            })
            ->hidden('locale')
            ->info_lang('locale')
            ->select_box('parent_id', $this->_get_parents_for_select($a['locale']), ['desc' => t('Parent item')])
            ->text('title')
            ->textarea('text', ['id' => 'text', 'cols' => 200, 'rows' => 10, 'ckeditor' => ['config' => _class('admin_methods')->_get_cke_config()]])
            ->active_box()
            ->save_and_back();
    }


    public function edit()
    {
        $id = (int) $_GET['id'];
        if ($id) {
            $a = from(self::table)->whereid($id)->get();
        }
        if ( ! $a) {
            return _404();
        }
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
        $a['back_link'] = url('/@object');
        $_this = $this;
        return form($a)
            ->validate([
                'title' => 'trim|required',
                'text' => 'trim',
            ])
            ->update_if_ok(self::table, ['title', 'text', 'active', 'locale', 'parent_id'])
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
                js_redirect(url('/@object'));
            })
            ->info_lang('locale')
            ->select_box('parent_id', $this->_get_parents_for_select($a['locale'], $a['id']), ['desc' => t('Parent item')])
            ->text('title')
            ->textarea('text', ['id' => 'text', 'cols' => 200, 'rows' => 10, 'ckeditor' => ['config' => _class('admin_methods')->_get_cke_config()]])
            ->active_box()
            ->save_and_back();
    }


    public function delete()
    {
        $id = (int) $_GET['id'];
        if ($id) {
            $a = from(self::table)->whereid($id)->get();
            module_safe('manage_revisions')->add([
                'object_name' => self::table,
                'object_id' => $a['id'],
                'old' => $a,
                'action' => 'delete',
            ]);
            db()->delete(self::table, $id);
        }
        if (is_ajax()) {
            no_graphics(true);
            echo $id;
        } else {
            return js_redirect(url('/@object'));
        }
    }


    public function active()
    {
        $id = (int) $_GET['id'];
        if ($id) {
            $a = from(self::table)->whereid($id)->get();
        }
        if ($a) {
            $n = $a;
            $n['active'] = (int) ! $a['active'];
            module_safe('manage_revisions')->add([
                'object_name' => self::table,
                'object_id' => $a['id'],
                'old' => $a,
                'new' => $n,
                'action' => 'active',
            ]);
            db()->update_safe(self::table, ['active' => (int) ! $a['active']], $id);
        }
        if (is_ajax()) {
            no_graphics(true);
            echo (int) ( ! $a['active']);
        } else {
            return js_redirect(url('/@object'));
        }
    }

    /**
     * @param mixed $lang
     */
    public function _auto_update_items_orders($lang)
    {
        if ( ! $lang) {
            return false;
        }
        $items = $this->_recursive_get_items($lang);
        $new_order = 1;
        $batch = [];
        foreach ((array) $items as $item_id => $info) {
            if ( ! $info) {
                continue;
            }
            if ($info['order'] != $new_order) {
                $batch[$item_id] = [
                    'id' => $item_id,
                    'order' => $new_order,
                ];
                $items[$item_id]['order'] = $new_order;
            }
            $new_order++;
        }
        if ($batch) {
            db()->update_batch_safe(self::table, $batch);
        }
        return $items;
    }

    /**
     * @param mixed $lang
     * @param null|mixed $skip_id
     */
    public function _get_parents_for_select($lang, $skip_id = null)
    {
        $data = [0 => '-- TOP --'];
        foreach ((array) $this->_recursive_get_items($lang) as $id => $info) {
            if (empty($id)) {
                continue;
            }
            if ($skip_id && $id == $skip_id) {
                continue;
            }
            $data[$id] = str_repeat('&nbsp; &nbsp; &nbsp; ', $info['level']) . ' &#9492; &nbsp; ' . $info['title'];
        }
        return $data;
    }

    /**
     * @param mixed $lang
     */
    public function _get_items($lang)
    {
        $items = [];
        foreach ((array) from(self::table)->where('locale', $lang)->order_by('order ASC')->all() as $id => $item) {
            $items[$id] = $item + ['have_children' => 0];
        }
        foreach ((array) $items as $id => $item) {
            $parent_id = $item['parent_id'];
            if ( ! $parent_id) {
                continue;
            }
            @$items[$parent_id]['have_children']++;
        }
        return $items;
    }

    /**
     * @param mixed $lang
     * @param mixed $skip_item_id
     * @param mixed $parent_id
     */
    public function _recursive_get_items($lang, $skip_item_id = 0, $parent_id = 0)
    {
        if (empty($lang)) {
            return false;
        }
        if ( ! isset($this->_items[$lang])) {
            $this->_items[$lang] = $this->_get_items($lang);
        }
        if (empty($this->_items[$lang])) {
            return false;
        }
        return $this->_recursive_sort_items($this->_items[$lang], $skip_item_id, $parent_id);
    }

    /**
     * Get and sort items ordered array (recursively).
     * @param mixed $items
     * @param mixed $skip_item_id
     * @param mixed $parent_id
     * @param mixed $level
     */
    public function _recursive_sort_items($items = [], $skip_item_id = 0, $parent_id = 0, $level = 0)
    {
        $children = [];
        foreach ((array) $items as $id => $info) {
            $parent_id = $info['parent_id'];
            if ($skip_item_id == $id) {
                continue;
            }
            $children[$parent_id][$id] = $id;
        }
        $ids = $this->_count_levels($children, 0);
        $new_items = [];
        foreach ((array) $ids as $id => $level) {
            $new_items[$id] = $items[$id] + ['level' => $level];
        }
        return $new_items;
    }

    /**
     * @param mixed $start_id
     * @param mixed $level
     */
    public function _count_levels(&$children, $start_id = 0, $level = 0)
    {
        $ids = [];
        foreach ((array) $children[$start_id] as $id => $_tmp) {
            $ids[$id] = $level;
            if (isset($children[$id])) {
                foreach ((array) $this->_count_levels($children, $id, $level + 1) as $_id => $_level) {
                    $ids[$_id] = $_level;
                }
            }
        }
        return $ids;
    }
}
