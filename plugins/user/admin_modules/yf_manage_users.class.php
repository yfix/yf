<?php


class yf_manage_users
{
    const table = 'user';


    public function show()
    {
        return table('SELECT * FROM ' . db(self::table), [
                'filter' => true,
                'filter_params' => [
                    'login' => 'like',
                    'email' => 'like',
                    'name' => 'like',
                ],
            ])
            ->text('id')
            ->text('login')
            ->text('email')
            ->text('name')
            ->btn_edit(['no_ajax' => 1])
            ->btn_delete()
            ->btn_active()
            ->btn('log_auth', './?object=log_user_auth&action=show_for_user&id=%d')
            ->btn('login', './?object=' . $_GET['object'] . '&action=login_as&id=%d')
            ->footer_add()
            ->footer_link('Failed auth log', './?object=log_user_auth_fails');
        // TODO: editing
    }


    public function add()
    {
        $a = $_POST;
        $a['redirect_link'] = url('/@object');
        return form($a, ['autocomplete' => 'off'])
            ->validate([
                'login' => 'trim|required|alpha_numeric|is_unique[user.login]',
                'email' => 'trim|required|valid_email|is_unique[user.email]',
            ])
            ->db_insert_if_ok(self::table, ['login', 'email', 'name', 'active'], ['add_date' => time()])
            ->on_after_update(function () {
                common()->admin_wall_add(['user added: ' . $_POST['login'] . '', db()->insert_id()]);
            })
            ->login()
            ->email()
            ->text('name')
            ->active_box()
            ->save_and_back();
    }


    public function edit()
    {
        $id = (int) ($_GET['id']);
        if ( ! $id) {
            return _e('No id');
        }
        $a = db()->query_fetch('SELECT * FROM ' . db(self::table) . ' WHERE id=' . (int) ($_GET['id']));
        $a['back_link'] = url('/@object');
        $a['redirect_link'] = url('/@object');
        return form($a, ['autocomplete' => 'off'])
            ->validate([
                'login' => 'trim|alpha_numeric|is_unique_without[user.login.' . $id . ']',
                'email' => 'trim|required|valid_email|is_unique_without[user.email.' . $id . ']',
            ])
            ->db_update_if_ok(self::table, ['login', 'email', 'name', 'active', 'birthday'], 'id=' . $id)
            ->on_after_update(function () {
                common()->admin_wall_add(['user updated: ' . $_POST['login'] . '', $id]);
            })
            ->login()
            ->email()
            ->text('name')
            ->active_box()
            ->row_start()
                ->save_and_back()
                ->link('log auth', './?object=log_user_auth&action=show_for_admin&id=' . $a['id'])
                ->link('login as', './?object=' . $_GET['object'] . '&action=login_as&id=' . $a['id'], ['display_func' => $func])
            ->row_end();
    }


    public function active()
    {
        if ( ! empty($_GET['id'])) {
            $user_info = user($_GET['id']);
        }
        if ( ! empty($user_info)) {
            db()->update(self::table, ['active' => (int) ! $user_info['active']], $user_info['id']);
        }
        cache_del(self::table);
        if (is_ajax()) {
            no_graphics(true);
            echo $user_info['active'] ? 0 : 1;
        } else {
            return js_redirect(url('/@object'));
        }
    }

    /**
     * Delete all user account information.
     */
    public function delete()
    {
        $user_id = (int) ($_GET['id']);
        if ( ! $user_id) {
            return false;
        }
        $hook_func_name = '_on_delete_account';

        $_user_modules = module('user_modules')->_get_modules();
        $_user_modules_methods = module('user_modules')->_get_methods(['private' => 1]);
        $_modules_where_exists = [];
        foreach ((array) $_user_modules_methods as $module_name => $methods) {
            if ( ! in_array($hook_func_name, $methods)) {
                continue;
            }
            $_modules_where_exists[$module_name] = $module_name;
        }
        foreach ((array) $_modules_where_exists as $_module_name) {
            $m = module($_module_name);
            if (method_exists($m, $hook_func_name)) {
                $result = $m->$hook_func_name(['user_id' => $user_id]);
            }
        }

        $user_info = user($user_id);
        $domains = main()->get_data('domains');
        if ($user_info['login'] && $user_info['domain']) {
            $user_folder_name = $user_info['login'] . '.' . $domains[$user_info['domain']];
        }
        if ($user_folder_name) {
            $user_folder_path = INCLUDE_PATH . 'users/' . $user_folder_name . '/';
        }
        if ($user_folder_path && file_exists($user_folder_path)) {
            _class('dir')->delete_dir($user_folder_path, true);
        }
        db()->query('DELETE FROM ' . db(self::table) . ' WHERE id=' . $user_id);
        return js_redirect($_SERVER['HTTP_REFERER']);
    }


    public function login_as()
    {
        $id = (int) ($_GET['id']);
        if ( ! $id) {
            return _e('Wrong id');
        }
        return _class('auth_user', 'classes/auth/')->login_as($id);
    }

    /**
     * User account confirmation.
     */
    public function do_confirm()
    {
        // TODO
/*
        if (!strlen($_POST['login'])) {
            _re('Login required');
        }
        if (!common()->_error_exists()) {
            $A = db()->query_fetch('SELECT * FROM '.db(self::table).' WHERE active='0' AND login="'._es($_POST['login']).'"');
            if (!$A['id']) {
                _re('Sorry, either someone has already confirmed membership or some important information has been missed. Please enter email below and submit');
            }
        }
        // Continue if check passed
        if (!common()->_error_exists()) {
            // Send email to the confirmed user
            $replace2 = array(
                'name'		=> _display_name($A),
                'email'		=> $A['email'],
                'password'	=> $A['password'],
            );
            $message = tpl()->parse('@object/email', $replace2);
            // Set user confirmed
            db()->query('UPDATE '.db(self::table).' SET active='1' WHERE id='.intval($A['id']));
            common()->send_mail(SITE_ADVERT_NAME, SITE_ADMIN_EMAIL, $A['email'], _display_name($A), 'Thank you for registering with us!', $message, nl2br($message));
            $replace = array(
                'name'	=> _display_name($A),
            );
            $body = tpl()->parse('@object/confirmed', $replace);
        } else {
            $body .= _e();
            $body .= $this->show($_POST);
        }
        return $body;
*/
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
        $order_fields = [];
        foreach (explode('|', 'name|login|email|add_date|last_login|num_logins|active') as $f) {
            $order_fields[$f] = $f;
        }
        return form($r, [
                'filter' => true,
            ])
            ->number('id')
            ->text('name')
            ->login('login')
            ->email('email')
            ->select_box('group', main()->get_data('user_groups'), ['show_text' => 1])
            ->select_box('order_by', $order_fields, ['show_text' => 1])
            ->order_box()
            ->save_and_clear();
    }

    /**
     * @param mixed $params
     */
    public function _hook_widget__members_stats($params = [])
    {
        // TODO
    }

    /**
     * @param mixed $params
     */
    public function _hook_widget__members_latest($params = [])
    {
        // TODO
    }
}
