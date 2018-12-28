<?php

/**
 * Icons management.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_manage_icons
{
    const table = 'icons';


    public function show()
    {
        return table('SELECT * FROM ' . db(self::table), [
                'filter' => true,
                'filter_params' => ['name' => 'like'],
            ])
            ->text('name')
            ->btn_active()
            ->btn_edit()
            ->btn_delete()
            ->footer_add();
    }


    public function edit()
    {
        $a = db()->query_fetch('SELECT * FROM ' . db(self::table) . ' WHERE id=' . (int) ($_GET['id']));
        if ( ! $a['id']) {
            return _e('No id!');
        }
        $a = $_POST ? $a + $_POST : $a;
        return form($a)
            ->validate(['name' => 'trim|required|alpha-dash'])
            ->db_update_if_ok(self::table, ['name', 'active'], 'id=' . $a['id'])
            ->on_after_update(function () {
                cache_del([self::table]);
                common()->admin_wall_add(['icon updated: ' . $_POST['name'] . '', $a['id']]);
            })
            ->text('name')
            ->active_box()
            ->save_and_back();
    }


    public function add()
    {
        $a = $_POST;
        return form($a)
            ->validate(['name' => 'trim|required|alpha-dash'])
            ->db_insert_if_ok(self::table, ['name', 'active'], [])
            ->on_after_update(function () {
                cache_del([self::table]);
                common()->admin_wall_add(['icon added: ' . $_POST['name'] . '', db()->insert_id()]);
            })
            ->text('name')
            ->active_box()
            ->save_and_back();
    }


    public function delete()
    {
        return _class('admin_methods')->delete(['table' => self::table]);
    }


    public function active()
    {
        return _class('admin_methods')->active(['table' => self::table]);
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
        $per_page = ['' => '', 10 => 10, 20 => 20, 50 => 50, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000, 2000 => 2000, 5000 => 5000];
        return form($r, [
                'filter' => true,
            ])
            ->text('name')
            ->select_box('per_page', $per_page, ['class' => 'input-small'])
            ->select_box('order_by', $order_fields, ['show_text' => 1, 'class' => 'input-medium'])
            ->order_box()
            ->save_and_clear();
    }

    /**
     * @param mixed $params
     */
    public function _hook_widget__icons_list($params = [])
    {
        // TODO
    }
}
