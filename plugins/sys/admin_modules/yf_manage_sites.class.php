<?php

/**
 * Core sites management.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_manage_sites
{
    const table = 'sites';


    public function show()
    {
        return table('SELECT * FROM ' . db(self::table))
            ->text('name')
            ->text('web_path')
            ->text('real_path')
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
            ->validate([
                'name' => 'trim|required',
            ])
            ->db_update_if_ok(self::table, ['name', 'web_path', 'real_path'], 'id=' . $a['id'])
            ->on_after_update(function () {
                cache_del([self::table]);
                common()->admin_wall_add(['site updated: ' . $_POST['name'] . '', $a['id']]);
            })
            ->text('name')
            ->text('web_path')
            ->text('real_path')
            ->active_box()
            ->save_and_back();
    }


    public function add()
    {
        return form($a)
            ->validate([
                'name' => 'trim|required',
            ])
            ->db_insert_if_ok(self::table, ['name', 'web_path', 'real_path'], [])
            ->on_after_update(function () {
                cache_del([self::table]);
                common()->admin_wall_add(['site added: ' . $_POST['name'] . '', db()->insert_id()]);
            })
            ->text('name')
            ->text('web_path')
            ->text('real_path')
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

    /**
     * @param mixed $params
     */
    public function _hook_widget__sites_list($params = [])
    {
        // TODO
    }
}
