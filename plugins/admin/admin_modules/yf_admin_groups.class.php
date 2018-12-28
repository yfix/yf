<?php

/**
 * Admin groups handling class.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_admin_groups
{
    const table = 'admin_groups';


    public function show()
    {
        $blocks = main()->get_data('blocks_all');
        foreach ((array) $blocks as $_id => $_info) {
            if ($_info['type'] == 'admin' && $_info['name'] == 'center_area') {
                $admin_center_id = $_id;
                break;
            }
        }
        $gid = main()->ADMIN_GROUP;
        $func = function ($row) use ($gid) {
            return ! ($row['id'] == $gid);
        };
        $menu_id = db()->get_one('SELECT id FROM ' . db('menus') . ' WHERE type="admin" AND active=1 LIMIT 1');
        return table('SELECT * FROM ' . db(self::table) . ' ORDER BY id ASC', [
                'custom_fields' => [
                    'members_count' => 'SELECT `group`, COUNT(*) AS num FROM ' . db('admin') . ' GROUP BY `group`',
                ],
                'hide_empty' => 1,
            ])
            ->text('name')
            ->text('go_after_login')
            ->text('members_count', ['link' => url('/admin/filter_save/clear/?filter=group:%d'), 'link_field_name' => 'id', 'icon' => 'fa fa-user'])
            ->btn_edit(['btn_no_text' => 1, 'no_ajax' => 1])
            ->btn_delete(['btn_no_text' => 1, 'display_func' => $func])
            ->btn_active(['display_func' => $func, 'short' => 1])
            ->footer_add(['class_add' => 'btn-primary', 'no_ajax' => 1])
            ->footer_link('Blocks', url('/blocks/show_rules/' . $admin_center_id))
            ->footer_link('Menu', url('/menus_editor/show_items/' . $menu_id))
            ->footer_link('Auth fails', url('/log_admin_auth_fails'));
    }


    public function add()
    {
        $a = $_POST;
        $a['redirect_link'] = url('/@object');
        return form($a, ['autocomplete' => 'off'])
            ->validate([
                'name' => 'trim|required|alpha_dash|is_unique[admin_groups.name]',
            ])
            ->db_insert_if_ok(self::table, ['name', 'go_after_login', 'active'], [])
            ->on_after_update(function () {
                cache_del([self::table, 'admin_groups_details']);
                common()->admin_wall_add(['admin group added: ' . $_POST['name'] . '', db()->insert_id()]);
            })
            ->text('name', 'Group name')
            ->text('go_after_login', 'Url after login')
            ->active_box()
            ->save_and_back();
    }


    public function edit()
    {
        $id = (int) ($_GET['id']);
        if ( ! $id) {
            return _e('No id');
        }
        $a = db()->from(self::table)->whereid($id)->get();
        $a = (array) $_POST + (array) $a;
        $a['redirect_link'] = url('/@object');
        return form($a, ['autocomplete' => 'off'])
            ->validate([
                'name' => 'trim|required|alpha_dash|is_unique_without[admin_groups.name.' . $id . ']',
            ])
            ->db_update_if_ok(self::table, ['name', 'go_after_login'], 'id=' . $id)
            ->on_after_update(function () {
                cache_del([self::table, 'admin_groups_details']);
                common()->admin_wall_add(['admin group edited: ' . $_POST['name'] . '', $id]);
            })
            ->text('name', 'Group name')
            ->text('go_after_login', 'Url after login')
            ->save_and_back();
    }


    public function delete()
    {
        $_GET['id'] = (int) ($_GET['id']);
        if ($_GET['id'] == 1) {
            $_GET['id'] = 0;
        }
        if ( ! empty($_GET['id'])) {
            db()->query('DELETE FROM ' . db(self::table) . ' WHERE id=' . (int) ($_GET['id']) . ' LIMIT 1');
            common()->admin_wall_add(['admin group deleted', $_GET['id']]);
        }
        cache_del([self::table, 'admin_groups_details']);
        if (is_ajax()) {
            no_graphics(true);
            echo $_GET['id'];
        } else {
            return js_redirect(url('/@object'));
        }
    }


    public function active()
    {
        $_GET['id'] = (int) ($_GET['id']);
        if ( ! empty($_GET['id'])) {
            $group_info = db()->query_fetch('SELECT * FROM ' . db(self::table) . ' WHERE id=' . (int) ($_GET['id']));
        }
        if ($_GET['id'] == 1) {
            $group_info = [];
        }
        if ( ! empty($group_info)) {
            db()->update_safe(self::table, ['active' => (int) ( ! $group_info['active'])], 'id=' . (int) ($_GET['id']));
            common()->admin_wall_add(['admin group ' . $group_info['name'] . ' ' . ($group_info['active'] ? 'inactivated' : 'activated'), $_GET['id']]);
        }
        cache_del([self::table, 'admin_groups_details']);
        if (is_ajax()) {
            no_graphics(true);
            echo $group_info['active'] ? 0 : 1;
        } else {
            return js_redirect(url('/@object'));
        }
    }

    /**
     * @param mixed $msg
     */
    public function _hook_wall_link($msg = [])
    {
        $action = $msg['action'] == 'delete' ? 'show' : 'edit';
        return url('/admin_groups/' . $action . '/' . $msg['object_id']);
    }
}
