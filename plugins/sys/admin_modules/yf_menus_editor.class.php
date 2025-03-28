<?php

/**
 * System-wide menus editor.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
#[AllowDynamicProperties]
class yf_menus_editor
{
    /** @var string Path to icons */
    public $ICONS_PATH = 'uploads/icons/';


    public function _init()
    {
        $array_all = ['' => '-- ALL --'];
        $this->_groups = [
            'user' => $array_all + (array) db()->get_2d('SELECT id,name FROM ' . db('user_groups') . ' WHERE active="1"'),
            'admin' => $array_all + (array) db()->get_2d('SELECT id,name FROM ' . db('admin_groups') . ' WHERE active="1"'),
        ];
        $this->_sites = $array_all + (array) db()->get_2d('SELECT id,name FROM ' . db('sites') . ' WHERE active="1"');
        $this->_servers = $array_all + (array) db()->get_2d('SELECT id,name FROM ' . db('core_servers') . ' WHERE active="1"');
        $this->_menu_types = [
            'user' => 'user',
            'admin' => 'admin',
        ];
        $this->_item_types = [
            1 => 'Internal link',
            2 => 'External link',
            3 => 'Spacer',
            4 => 'Divider',
        ];
    }


    public function _purge_caches()
    {
        cache_del(['menus', 'menu_items']);
    }

    /**
     * Display menus.
     */
    public function show()
    {
        $num_items = db()->get_2d('SELECT m.id, COUNT(i.id) AS num FROM ' . db('menus') . ' AS m LEFT JOIN ' . db('menu_items') . ' AS i ON m.id = i.menu_id GROUP BY m.id');
        $data = db()->from('menus')->order_by('name ASC, type DESC')->get_all();
        return table($data, [
                'pager_records_on_page' => 1000,
                'group_by' => 'name',
                'hide_empty' => 1,
            ])
            ->link('name', url('/@object/show_items/%d'))
            ->text('id', 'Num Items', ['data' => $num_items])
            ->text('type')
            ->text('stpl_name')
            ->text('method_name')
            ->text('custom_fields')
            ->btn('Drag items', url('/@object/drag_items/%id'), ['icon' => 'icon-move fa fa-arrows', 'btn_no_text' => 1])
            ->btn('View Items', url('/@object/show_items/%d'), ['btn_no_text' => 1])
            ->btn_clone('', url('/@object/clone_menu/%d'), ['btn_no_text' => 1])
//			->btn('Export', url('/@object/export/%d'), array('btn_no_text' => 1, 'icon' => 'fa fa-angle-double-up'))
            ->btn_edit(['no_ajax' => 1, 'btn_no_text' => 1])
            ->btn_delete(['btn_no_text' => 1])
            ->btn_active()
            ->footer_add(['no_ajax' => 1]);
    }


    public function add()
    {
        $a = $_POST;
        $a['redirect_link'] = url('/@object');
        return form($a, ['autocomplete' => 'off'])
            ->validate([
                'name' => 'trim|required|is_unique[menus.name]',
                'type' => 'trim|required',
            ])
            ->db_insert_if_ok('menus', ['type', 'name', 'desc', 'stpl_name', 'method_name', 'custom_fields', 'active'], [])
            ->on_after_update(function () {
                common()->admin_wall_add(['menu added: ' . $_POST['name'] . '', db()->insert_id()]);
                module('menus_editor')->_purge_caches();
            })
            ->radio_box('type', ['user' => 'User', 'admin' => 'Admin'])
            ->text('name')
            ->text('desc', 'Description')
            ->text('stpl_name')
            ->text('method_name')
            ->text('custom_fields')
            ->active_box()
            ->save_and_back();
    }


    public function edit()
    {
        $id = (int) ($_GET['id']);
        if ( ! $id) {
            return _e('No id');
        }
        $a = db()->from('menus')->whereid($id)->get();
        $a['redirect_link'] = url('/@object');
        return form($a, ['autocomplete' => 'off'])
            ->validate([
                'name' => 'trim|required',
            ])
            ->db_update_if_ok('menus', ['name', 'desc', 'stpl_name', 'method_name', 'custom_fields', 'active'], 'id=' . $id)
            ->on_after_update(function () {
                common()->admin_wall_add(['menu updated: ' . $_POST['name'] . '', $menu_info['id']]);
                module('menus_editor')->_purge_caches();
            })
            ->info('type')
            ->text('name')
            ->text('desc', 'Description')
            ->text('stpl_name')
            ->text('method_name')
            ->text('custom_fields')
            ->active_box()
            ->save_and_back();
    }

    /**
     * Get array of templates for the given init type.
     * @param mixed $type
     */
    public function _get_stpls($type = 'user')
    {
        return module('template_editor')->_get_stpls_for_type($type);
    }

    /**
     * Clone menus.
     */
    public function clone_menu()
    {
        $_GET['id'] = (int) ($_GET['id']);
        if (empty($_GET['id'])) {
            return _e('No id!');
        }
        $menu_info = db()->query_fetch('SELECT * FROM ' . db('menus') . ' WHERE id=' . (int) ($_GET['id']));
        if (empty($menu_info['id'])) {
            return _e('No such menu!');
        }
        $sql = $menu_info;
        unset($sql['id']);
        $sql['name'] = $sql['name'] . '_clone';

        db()->INSERT('menus', $sql);
        $NEW_MENU_ID = db()->INSERT_ID();

        $old_items = $this->_recursive_get_menu_items($menu_info['id']);
        foreach ((array) $old_items as $_id => $_info) {
            unset($_info['id']);
            unset($_info['level']);
            $_info['menu_id'] = $NEW_MENU_ID;

            db()->insert_safe('menu_items', $_info);
            $NEW_ITEM_ID = db()->INSERT_ID();

            $_old_to_new[$_id] = $NEW_ITEM_ID;
            $_new_to_old[$NEW_ITEM_ID] = $_id;
        }
        foreach ((array) $_new_to_old as $_new_id => $_old_id) {
            $_old_info = $old_items[$_old_id];
            $_old_parent_id = $_old_info['parent_id'];
            if ( ! $_old_parent_id) {
                continue;
            }
            $_new_parent_id = (int) ($_old_to_new[$_old_parent_id]);
            db()->update_safe('menu_items', ['parent_id' => $_new_parent_id], 'id=' . (int) $_new_id);
        }
        common()->admin_wall_add(['menu cloned: ' . $menu_info['name'] . '', $NEW_ITEM_ID]);
        module('menus_editor')->_purge_caches();
        return js_redirect('./?object=' . $_GET['object'] . '&action=edit&id=' . (int) $NEW_MENU_ID);
    }

    /**
     * Delete menu and all sub items.
     */
    public function delete()
    {
        $_GET['id'] = (int) ($_GET['id']);
        if ( ! empty($_GET['id'])) {
            $menu_info = db()->query_fetch('SELECT * FROM ' . db('menus') . ' WHERE id=' . (int) ($_GET['id']));
        }
        if ( ! empty($menu_info['id'])) {
            db()->query('DELETE FROM ' . db('menus') . ' WHERE id=' . (int) ($_GET['id']) . ' LIMIT 1');
            db()->query('DELETE FROM ' . db('menu_items') . ' WHERE menu_id=' . (int) ($_GET['id']));
            common()->admin_wall_add(['menu deleted: ' . $menu_info['name'] . '', $menu_info['id']]);
            module('menus_editor')->_purge_caches();
        }
        if (is_ajax()) {
            no_graphics(true);
            echo $_GET['id'];
        } else {
            return js_redirect(url('/@object'));
        }
    }

    /**
     * Change menu activity.
     */
    public function active()
    {
        if ( ! empty($_GET['id'])) {
            $menu_info = db()->query_fetch('SELECT * FROM ' . db('menus') . ' WHERE id=' . (int) ($_GET['id']));
        }
        if ( ! empty($menu_info)) {
            db()->UPDATE('menus', ['active' => (int) ! $menu_info['active']], 'id=' . (int) ($menu_info['id']));
            common()->admin_wall_add(['menu: ' . $menu_info['name'] . ' ' . ($menu_info['active'] ? 'inactivated' : 'activated'), $menu_info['id']]);
            module('menus_editor')->_purge_caches();
        }
        if (is_ajax()) {
            no_graphics(true);
            echo $menu_info['active'] ? 0 : 1;
        } else {
            return js_redirect(url('/@object'));
        }
    }

    /**
     * Display menu items for the given.
     */
    public function show_items()
    {
        $menu_info = db()->query_fetch('SELECT * FROM ' . db('menus') . ' WHERE id=' . (int) ($_GET['id']) . ' OR name="' . db()->es($_GET['id']) . '"');
        if (empty($menu_info)) {
            return _e('No such menu!');
        }
        $_GET['id'] = (int) ($menu_info['id']);

        $menu_items = $this->_auto_update_items_orders($menu_info['id']);
        if (main()->is_post()) {
            $batch = [];
            foreach ((array) $menu_items as $a) {
                if ( ! isset($_POST['name'][$a['id']])) {
                    continue;
                }
                $batch[$a['id']] = [
                    'id' => $a['id'],
                    'name' => $_POST['name'][$a['id']],
                    'location' => $_POST['location'][$a['id']],
                ];
            }
            if ($batch) {
                db()->update_batch('menu_items', db()->es($batch));
                common()->admin_wall_add(['menu items updated: ' . $menu_info['name'] . '', $menu_info['id']]);
                module('menus_editor')->_purge_caches();
            }
            return js_redirect('./?object=' . $_GET['object'] . '&action=show_items&id=' . $_GET['id']);
        }
        $groups = $this->_groups[$menu_info['type']];
        return table($menu_items, ['pager_records_on_page' => 10000, 'condensed' => 1, 'hide_empty' => 1])
            ->form()
            ->icon('icon')
            ->input_padded('name', ['width' => '300'])
            ->input('location', ['width' => '300'])
            ->text('type_id', 'Item type', ['data' => $this->_item_types, 'nowrap' => 1])
            ->data('user_groups', $groups, ['desc' => 'Groups'])
            ->data('sites', $this->_sites)
            ->data('servers', $this->_servers)
            ->text('other_info')
            ->btn_edit('', url('/@object/edit_item/%d'), ['no_ajax' => 1, 'btn_no_text' => 1])
            ->btn_delete('', url('/@object/delete_item/%d'), ['btn_no_text' => 1])
            ->btn_clone('', url('/@object/clone_item/%d'), ['btn_no_text' => 1])
            ->btn_active('', url('/@object/activate_item/%d'))
            ->footer_add('Add item', url('/@object/add_item/@id'), ['copy_to_header' => 1, 'no_ajax' => 1])
            ->footer_link('Drag items', url('/@object/drag_items/@id'), ['icon' => 'icon-move fa fa-arrows', 'copy_to_header' => 1, 'no_ajax' => 1])
            ->footer_submit();
    }


    public function drag_items()
    {
        if (empty($_GET['id'])) {
            return _e('No id!');
        }
        $menu_info = db()->query_fetch('SELECT * FROM ' . db('menus') . ' WHERE id=' . (int) ($_GET['id']) . ' OR name="' . db()->es($_GET['id']) . '"');
        if (empty($menu_info)) {
            return _e('No such menu!');
        }
        $items = $this->_show_menu([
            'force_stpl_name' => $_GET['object'] . '/drag',
            'name' => $menu_info['name'],
            'return_array' => 1,
        ]);
        if (main()->is_post()) {
            $old_info = $this->_auto_update_items_orders($menu_info['id']);
            $batch = [];
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
                }
            }
            if ($batch) {
                db()->update_batch('menu_items', db()->es($batch));
                common()->admin_wall_add(['menu items dragged: ' . $menu_info['name'] . '', $menu_info['id']]);
                module('menus_editor')->_purge_caches();
            }
            if (is_ajax()) {
                no_graphics(true);
            } else {
                js_redirect(url('/@object/@action/@id/@page'));
            }
            return false;
        }
        if (isset($items[''])) {
            unset($items['']);
        }
        $tpl_items = [];
        foreach ((array) $items as $id => $item) {
            if ( ! $id) {
                continue;
            }
            $item['edit_link'] = './?object=' . $_GET['object'] . '&action=edit_item&id=' . $id;
            $item['delete_link'] = './?object=' . $_GET['object'] . '&action=delete_item&id=' . $id;
            $item['active_link'] = './?object=' . $_GET['object'] . '&action=activate_item&id=' . $id;
            $item['clone_link'] = './?object=' . $_GET['object'] . '&action=clone_item&id=' . $id;
            $tpl_items[$id] = tpl()->parse('@object/drag_item', $item);
        }
        $replace = [
            'items' => implode(PHP_EOL, (array) $tpl_items),
            'form_action' => './?object=' . $_GET['object'] . '&action=' . $_GET['action'] . '&id=' . $_GET['id'],
            'add_link' => './?object=' . $_GET['object'] . '&action=add_item&id=' . $_GET['id'],
            'back_link' => './?object=' . $_GET['object'] . '&action=show_items&id=' . $_GET['id'],
        ];
        return tpl()->parse('@object/drag_main', $replace);
    }

    /**
     * @param mixed $menu_id
     */
    public function _auto_update_items_orders($menu_id)
    {
        if ( ! $menu_id) {
            return false;
        }
        $menu_items = $this->_recursive_get_menu_items($menu_id);
        $new_order = 1;
        $batch = [];
        foreach ((array) $menu_items as $item_id => $info) {
            if ( ! $info) {
                continue;
            }
            if ($info['order'] != $new_order) {
                $batch[$item_id] = [
                    'id' => $item_id,
                    'order' => $new_order,
                ];
                $menu_items[$item_id]['order'] = $new_order;
            }
            $new_order++;
        }
        if ($batch) {
            db()->update_batch('menu_items', $batch);
        }
        return $menu_items;
    }

    /**
     * Compatibility with format main()->get_data('menu_items'), diference is that we show inactive here too.
     * @param mixed $menu_id
     */
    public function _get_menu_items($menu_id)
    {
        $items = [];
        foreach ((array) db()->get_all('SELECT * FROM ' . db('menu_items') . ' WHERE menu_id=' . (int) $menu_id . ' ORDER BY `order` ASC') as $id => $item) {
            $items[$id] = $item + ['have_children' => 0];
        }
        foreach ((array) $items as $id => $item) {
            $parent_id = $item['parent_id'];
            if ( ! $parent_id) {
                continue;
            }
            $items[$parent_id]['have_children']++;
        }
        return $items;
    }

    /**
     * Show menu, it is customized comparing to classes/core_menu, for the needs of managing menus.
     * @param mixed $input
     */
    public function _show_menu($input = [])
    {
        $RETURN_ARRAY = isset($input['return_array']) ? $input['return_array'] : false;
        $force_stpl_name = isset($input['force_stpl_name']) ? $input['force_stpl_name'] : false;
        $menu_name = $input['name'];
        if (empty($menu_name)) {
            return false;
        }
        if ( ! isset($this->_menus_infos)) {
            $this->_menus_infos = db()->get_all('SELECT * FROM ' . db('menus'));
        }
        if (empty($this->_menus_infos)) {
            return false;
        }
        $MENU_EXISTS = false;
        foreach ((array) $this->_menus_infos as $menu_info) {
            if ($menu_info['name'] == $menu_name) {
                $MENU_EXISTS = true;
                $menu_id = $menu_info['id'];
                break;
            }
        }
        if ( ! $MENU_EXISTS) {
            return false;
        }
        $cur_menu_info = &$this->_menus_infos[$menu_id];
        if ( ! isset($this->_menu_items)) {
            $this->_menu_items[$menu_id] = $this->_get_menu_items($menu_id);
        }
        // Do not show menu if there is no items in it
        if (empty($this->_menu_items[$menu_id])) {
            return false;
        }
        $menu_items = $this->_recursive_get_menu_items($menu_id);
        if ($force_stpl_name) {
            $cur_menu_info['stpl_name'] = $force_stpl_name;
        }
        $STPL_MENU_ITEM = ! empty($cur_menu_info['stpl_name']) ? $cur_menu_info['stpl_name'] . '_item' : 'system/menu_item';
        $STPL_MENU_MAIN = ! empty($cur_menu_info['stpl_name']) ? $cur_menu_info['stpl_name'] : 'system/menu_main';
        $STPL_MENU_PAD = ! empty($cur_menu_info['stpl_name']) ? $cur_menu_info['stpl_name'] . '_pad' : 'system/menu_pad';
        $level_pad_text = tpl()->parse($STPL_MENU_PAD);

        $menu_items_to_display = [];
        foreach ((array) $menu_items as $item_id => $item_info) {
            if (empty($item_info)) {
                continue;
            }
            // Internal link
            if ($item_info['type_id'] == 1 && strlen($item_info['location']) > 0) {
                parse_str($item_info['location'], $_item_parts);
            }
            $menu_items_to_display[] = $item_info;
        }
        $num_menu_items = count((array) $menu_items_to_display);
        $_prev_level = 0;
        $_next_level = 0;
        $item_counter = 0;

        $ICONS_DIR = _class('graphics')->ICONS_PATH;
        $MEDIA_PATH = _class('graphics')->MEDIA_PATH;

        foreach ((array) $menu_items_to_display as $i => $item_info) {
            $item_counter++;
            $_next_info = isset($menu_items_to_display[$i + 1]) ? $menu_items_to_display[$i + 1] : [];
            $_next_level = isset($_next_info['level']) ? (int) $_next_info['level'] : 0;
            $is_cur_page = false;
            $item_link = '';
            // Internal link
            if ($item_info['type_id'] == 1 && strlen($item_info['location']) > 0) {
                parse_str($item_info['location'], $_item_parts);
                $item_link = './?' . $item_info['location'];
            } elseif ($item_info['type_id'] == 2) {
                $item_link = $item_info['location'];
            }
            $icon = trim($item_info['icon']);
            $icon_path = '';
            $icon_class = '';
            if ($icon) {
                // Icon class from bootstrap icon class names
                if (preg_match('/^icon\-[a-z0-9_-]+$/i', $icon) || (strpos($icon, '.') === false)) {
                    $icon_class = $icon;
                } else {
                    $_icon_fs_path = PROJECT_PATH . $ICONS_DIR . $icon;
                    if (file_exists($_icon_fs_path)) {
                        $icon_path = $MEDIA_PATH . $ICONS_DIR . $icon;
                    }
                }
            }
            $replace2 = [
                'item_id' => (int) ($item_info['id']),
                'parent_id' => (int) ($item_info['parent_id']),
                'bg_class' => ! (++$i % 2) ? 'bg1' : 'bg2',
                'link' => ! empty($IN_OUTPUT_CACHE) ? process_url($item_link) : $item_link,
                'name' => _prepare_html(t($item_info['name'])),
                'level_pad' => str_repeat($level_pad_text, $item_info['level']),
                'level_num' => (int) ($item_info['level']),
                'prev_level' => (int) $_prev_level,
                'next_level' => (int) $_next_level,
                'type_id' => $item_info['type_id'],
                'icon_path' => $icon_path,
                'icon_class' => $icon_class,
                'is_first_item' => (int) ($item_counter == 1),
                'is_last_item' => (int) ($item_counter == $num_menu_items),
                'is_cur_page' => (int) $is_cur_page,
                'have_children' => (int) ((bool) $item_info['have_children']),
                'next_level_diff' => (int) (abs($item_info['level'] - $_next_level)),
                'active' => (int) ($item_info['active']),
            ];
            $items[$item_info['id']] = $replace2;
            // Save current level for the next iteration
            $_prev_level = $item_info['level'];
        }
        if ($RETURN_ARRAY) {
            return $items;
        }
        foreach ((array) $items as $id => $item) {
            $items[$id] = tpl()->parse($STPL_MENU_ITEM, $item);
        }
        $replace = [
            'items' => implode('', (array) $items),
        ];
        return tpl()->parse($STPL_MENU_MAIN, $replace);
    }

    /**
     * @param mixed $menu_id
     * @param mixed $skip_item_id
     * @param mixed $parent_id
     */
    public function _recursive_get_menu_items($menu_id = 0, $skip_item_id = 0, $parent_id = 0)
    {
        if (empty($menu_id)) {
            return false;
        }
        if ( ! isset($this->_menu_items[$menu_id])) {
            $this->_menu_items[$menu_id] = $this->_get_menu_items($menu_id);
        }
        if (empty($this->_menu_items[$menu_id])) {
            return false;
        }
        return $this->_recursive_sort_items($this->_menu_items[$menu_id], $skip_item_id, $parent_id);
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


    public function add_item()
    {
        $menu_info = db()->get('SELECT * FROM ' . db('menus') . ' WHERE id=' . (int) ($_GET['id']));
        if (empty($menu_info['id'])) {
            return _e('No such menu!');
        }
        $_GET['id'] = (int) ($menu_info['id']);

        $multi_selects = ['user_groups', 'site_ids', 'server_ids'];
        if (main()->is_post()) {
            $tmp = [];
            foreach (explode(',', $menu_info['custom_fields']) as $field_name) {
                if ($field_name && $_POST['custom'][$field_name]) {
                    $tmp[$field_name] = $field_name . '=' . $_POST['custom'][$field_name];
                }
            }
            $_POST['other_info'] = implode(';' . PHP_EOL, $tmp);
            foreach ($multi_selects as $k) {
                $_POST[$k] = $this->_multi_html_to_db($_POST[$k]);
            }
            /*
        } else {
            foreach ($multi_selects as $k) {
                $a[$k] = $this->_multi_db_to_html($a[$k]);
            }
             */
        }
        $a = $_POST;
        $a['redirect_link'] = './?object=' . $_GET['object'] . '&action=show_items&id=' . $menu_info['id'];
        return form($a)
            ->validate([
                'name' => 'trim|required',
            ])
            ->db_insert_if_ok('menu_items', ['type_id', 'parent_id', 'name', 'location', 'icon', 'user_groups', 'site_ids', 'server_ids', 'active', 'other_info'], ['menu_id' => $menu_info['id']])
            ->on_after_update(function () {
                common()->admin_wall_add(['menu item added: ' . $_POST['name'] . '', db()->insert_id()]);
                module('menus_editor')->_purge_caches();
            })
            ->select_box('type_id', $this->_item_types)
            ->select_box('parent_id', $this->_get_parents_for_select($menu_info['id']), ['desc' => 'Parent item'])
            ->text('name')
            ->location_select_box('location')
            ->multi_select_box('user_groups', $this->_groups[$menu_info['type']], ['edit_link' => './?object=' . $menu_info['type'] . '_groups', 'desc' => 'Groups'])
            ->multi_select_box('site_ids', $this->_sites, ['edit_link' => './?object=manage_sites', 'desc' => 'Sites'])
            ->multi_select_box('server_ids', $this->_servers, ['edit_link' => './?object=manage_servers', 'desc' => 'Servers'])
            ->icon_select_box('icon')
            ->active_box()
            ->custom_fields('other_info', $menu_info['custom_fields'])
            ->save_and_back();
    }


    public function edit_item()
    {
        $item_info = db()->query_fetch('SELECT * FROM ' . db('menu_items') . ' WHERE id=' . (int) ($_GET['id']));
        if (empty($item_info['id'])) {
            return _e('No such menu item!');
        }
        $menu_info = db()->get('SELECT * FROM ' . db('menus') . ' WHERE id=' . (int) ($item_info['menu_id']));
        if (empty($menu_info['id'])) {
            return _e('No such menu!');
        }
        $_GET['id'] = (int) ($item_info['id']);

        $multi_selects = ['user_groups', 'site_ids', 'server_ids'];
        if (main()->is_post()) {
            $tmp = [];
            foreach (explode(',', $menu_info['custom_fields']) as $field_name) {
                if ($field_name && $_POST['custom'][$field_name]) {
                    $tmp[$field_name] = $field_name . '=' . $_POST['custom'][$field_name];
                }
            }
            $_POST['other_info'] = implode(';' . PHP_EOL, $tmp);
            foreach ($multi_selects as $k) {
                $_POST[$k] = $this->_multi_html_to_db($_POST[$k]);
            }
        } else {
            foreach ($multi_selects as $k) {
                $item_info[$k] = $this->_multi_db_to_html($item_info[$k]);
            }
        }
        $a = $item_info;
        $a['redirect_link'] = './?object=' . $_GET['object'] . '&action=show_items&id=' . $menu_info['id'];
        return form($a)
            ->validate([
                'name' => 'trim|required',
            ])
            ->db_update_if_ok('menu_items', [
                'type_id', 'parent_id', 'name', 'location', 'icon', 'user_groups', 'site_ids', 'server_ids', 'active', 'other_info',
            ], 'id=' . $item_info['id'])
            ->on_after_update(function () {
                common()->admin_wall_add(['menu item updated: ' . $_POST['name'] . '', $item_info['id']]);
                module('menus_editor')->_purge_caches();
            })
            ->select_box('type_id', $this->_item_types)
            ->select_box('parent_id', $this->_get_parents_for_select($menu_info['id'], $item_info['id']), ['desc' => 'Parent item'])
            ->text('name')
            ->location_select_box('location')
            ->multi_select_box('user_groups', $this->_groups[$menu_info['type']], ['edit_link' => './?object=' . $menu_info['type'] . '_groups', 'desc' => 'Groups'])
            ->multi_select_box('site_ids', $this->_sites, ['edit_link' => './?object=manage_sites', 'desc' => 'Sites'])
            ->multi_select_box('server_ids', $this->_servers, ['edit_link' => './?object=manage_servers', 'desc' => 'Servers'])
            ->icon_select_box('icon')
            ->active_box()
            ->custom_fields('other_info', $menu_info['custom_fields'])
            ->save_and_back();
    }

    /**
     * @param mixed $menu_id
     * @param null|mixed $skip_id
     */
    public function _get_parents_for_select($menu_id, $skip_id = null)
    {
        $data = [0 => '-- TOP --'];
        foreach ((array) $this->_recursive_get_menu_items($menu_id/*, $skip_id*/) as $cur_item_id => $cur_item_info) {
            if (empty($cur_item_id)) {
                continue;
            }
            if ($skip_id && $cur_item_id == $skip_id) {
                continue;
            }
            $data[$cur_item_id] = str_repeat('&nbsp; &nbsp; &nbsp; ', $cur_item_info['level']) . ' &#9492; &nbsp; ' . $cur_item_info['name'];
        }
        return $data;
    }

    /**
     * Clone menu item.
     */
    public function clone_item()
    {
        $_GET['id'] = (int) ($_GET['id']);
        if (empty($_GET['id'])) {
            return _e('No id!');
        }
        $item_info = db()->query_fetch('SELECT * FROM ' . db('menu_items') . ' WHERE id=' . (int) ($_GET['id']));
        if (empty($item_info['id'])) {
            return _e('No such menu item!');
        }
        $menu_info = db()->query_fetch('SELECT * FROM ' . db('menus') . ' WHERE id=' . (int) ($item_info['menu_id']));
        if (empty($menu_info['id'])) {
            return _e('No such menu!');
        }
        $sql = $item_info;
        unset($sql['id']);
        db()->insert_safe('menu_items', $sql);
        common()->admin_wall_add(['menu item cloned: ' . $item_info['name'] . '', $item_info['id']]);
        module('menus_editor')->_purge_caches();
        return js_redirect('./?object=' . $_GET['object'] . '&action=show_items&id=' . $menu_info['id']);
    }


    public function activate_item()
    {
        if ( ! empty($_GET['id'])) {
            $item_info = db()->query_fetch('SELECT * FROM ' . db('menu_items') . ' WHERE id=' . (int) ($_GET['id']));
        }
        if ( ! empty($item_info)) {
            db()->update('menu_items', ['active' => (int) ! $item_info['active']], 'id=' . (int) ($item_info['id']));
            common()->admin_wall_add(['menu item: ' . $item_info['name'] . ' ' . ($item_info['active'] ? 'inactivated' : 'activated'), $item_info['id']]);
        }
        module('menus_editor')->_purge_caches();
        if (is_ajax()) {
            no_graphics(true);
            echo $item_info['active'] ? 0 : 1;
        } else {
            return js_redirect('./?object=' . $_GET['object'] . '&action=show_items&id=' . $item_info['menu_id']);
        }
    }


    public function delete_item()
    {
        $_GET['id'] = (int) ($_GET['id']);
        if ( ! empty($_GET['id'])) {
            $item_info = db()->query_fetch('SELECT * FROM ' . db('menu_items') . ' WHERE id=' . (int) ($_GET['id']));
        }
        if ( ! empty($item_info)) {
            db()->query('DELETE FROM ' . db('menu_items') . ' WHERE id=' . (int) ($_GET['id']));
            db()->update('menu_items', ['parent_id' => 0], 'parent_id=' . (int) ($_GET['id']));
            common()->admin_wall_add(['menu item deleted: ' . $item_info['name'] . '', $item_info['id']]);
        }
        module('menus_editor')->_purge_caches();
        if (is_ajax()) {
            no_graphics(true);
            echo $_GET['id'];
        } else {
            return js_redirect('./?object=' . $_GET['object'] . '&action=show_items&id=' . $item_info['menu_id']);
        }
    }

    /**
     * Export menu items.
     */
    public function export()
    {
        $_GET['id'] = (int) ($_GET['id']);
        $menu_info = db()->query_fetch('SELECT * FROM ' . db('menus') . ' WHERE id=' . (int) ($_GET['id']));
        $params = [
            'single_table' => '',
            'tables' => [db('menus'), db('menu_items')],
            'full_inserts' => 1,
            'ext_inserts' => 1,
            'export_type' => 'insert',
            'silent_mode' => true,
        ];
        if ($menu_info['id']) {
            $params['where'] = [
                db('menus') => 'id=' . (int) ($menu_info['id']),
                db('menu_items') => 'menu_id=' . (int) ($menu_info['id']),
            ];
        }
        $EXPORTED_SQL = module('db_manager')->export($params);
        $replace = [
            'sql_text' => _prepare_html($EXPORTED_SQL, 0),
            'back_link' => url('/@object'),
        ];
        return tpl()->parse('db_manager/export_text_result', $replace);
    }

    /**
     * @param mixed $input
     */
    public function _multi_html_to_db($input = [])
    {
        if (is_array($input)) {
            $input = ',' . implode(',' . PHP_EOL, $input) . ',';
        }
        return (string) str_replace([' ', "\t", "\r", ',,'], '', $input);
    }

    /**
     * @param mixed $input
     */
    public function _multi_db_to_html($input = '')
    {
        if ( ! is_array($input)) {
            $input = explode(',', str_replace([' ', "\t", "\r", "\n", ',,'], '', $input));
        }
        $output = [];
        foreach ((array) $input as $v) {
            if ($v) {
                $output[$v] = $v;
            }
        }
        return (array) $output;
    }

    /**
     * Execute this before redirect.
     */
    public function _on_before_redirect()
    {
        if (defined('ADMIN_FRAMESET_MODE')) {
            $_SESSION['_menu_js_refresh_frameset'] = true;
        }
    }

    /**
     * @param mixed $params
     */
    public function _hook_widget__menus($params = [])
    {
        // TODO
    }

    /**
     * @param mixed $params
     */
    public function _hook_widget__menu_items($params = [])
    {
        // TODO
    }
}
