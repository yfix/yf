<?php

class yf_manage_shop_users
{
    public function users()
    {
        if (empty($_SESSION[$_GET['object'] . '__users'])) {
            $_SESSION[$_GET['object'] . '__users'] = [
                'order_by' => 'add_date',
                'order_direction' => 'desc',
            ];
        }
        return table('SELECT * FROM ' . db('user'), [
                'filter' => $_SESSION[$_GET['object'] . '__users'],
                'filter_params' => [
                    'id' => 'like',
                    'name' => 'like',
                    'email' => 'like',
                    'phone' => 'like',
                    'address' => 'like',
                    'add_date' => 'dt_between',
                ],
            ])
            ->text('id')
            ->text('name')
            ->text('email')
            ->text('phone')
            ->text('address')
            ->date('add_date', ['format' => 'full', 'nowrap' => 1])
            ->btn_edit('', './?object=' . main()->_get('object') . '&action=user_edit&id=%d', ['no_ajax' => 1])
            ->btn('Login', './?object=manage_users&action=login_as&id=%d')
            ->btn_delete('', './?object=' . main()->_get('object') . '&action=user_delete&id=%d')
            ->btn_active('', './?object=' . main()->_get('object') . '&action=user_activate&id=%d');
    }


    public function user_delete()
    {
        $_GET['id'] = (int) ($_GET['id']);
        $field_info = db()->query_fetch('SELECT * FROM ' . db('user') . ' WHERE id = ' . (int) ($_GET['id']));
        if (empty($field_info)) {
            return _e('no field');
        }
        if ($_GET['id']) {
            db()->query('DELETE FROM ' . db('user') . ' WHERE id=' . $_GET['id']);
            common()->admin_wall_add(['user deleted: ' . $_GET['id'], $_GET['id']]);
        }
        if ($_POST['ajax_mode']) {
            main()->NO_GRAPHICS = true;
            echo $_GET['id'];
        } else {
            return js_redirect($_SERVER['HTTP_REFERER'], 0);
        }
    }


    public function user_activate()
    {
        if ($_GET['id']) {
            $A = db()->query_fetch('SELECT * FROM ' . db('user') . ' WHERE id=' . (int) ($_GET['id']));
            if ($A['active'] == 1) {
                $active = 0;
            } elseif ($A['active'] == 0) {
                $active = 1;
            }
            db()->UPDATE(db('user'), ['active' => $active], 'id=' . (int) ($_GET['id']));
        }
        if ($_POST['ajax_mode']) {
            main()->NO_GRAPHICS = true;
            echo $active ? 1 : 0;
        } else {
            return js_redirect('./?object=' . main()->_get('object') . '&action=users');
        }
    }


    public function user_edit()
    {
        if ($_GET['id']) {
            $A = db()->query_fetch('SELECT * FROM ' . db('user') . ' WHERE id=' . (int) ($_GET['id']));
            if (empty($A)) {
                return js_redirect('./?object=' . main()->_get('object') . '&action=users');
            }
        }
        $validate_rules = [
            'name' => ['trim|required'],
            'email' => ['trim|valid_email'],
            'phone' => ['trim|required'],
            'address' => ['trim|required'],
        ];
        $A['redirect_link'] = './?object=' . $_GET['object'] . '&action=users';
        if ($A['birthday'] !== '0000-00-00') {
            $A['birthday'] = date('d-m-Y', strtotime($A['birthday']));
        } else {
            $A['birthday'] = '';
        }
        return form($A)
            ->validate($validate_rules)
            ->db_update_if_ok('user', ['name', 'phone', 'address', 'birthday'], $_GET['id'])
            ->on_before_update(function (&$data, $table, $fields) {
                $data['birthday'] = date('Y-m-d', strtotime($data['birthday']));
            })
            ->text('name')
            ->email('email')
            ->text('phone')
            ->text('address')
            ->datetime_select('birthday', 'Дата рождения', ['no_time' => 1, 'placeholder' => 'день-месяц-год', 'value' => $A['birthday']])
            ->save();
    }
}
