<?php

class yf_manage_shop_coupons
{
    public function _init()
    {
        $this->_statuses = [
            '0' => 'not used',
            '1' => 'used',
        ];
    }


    public function coupons()
    {
        return table('SELECT * FROM ' . db('shop_coupons'), [
//				'filter' => $_SESSION[$_GET['object'].'__coupons'],
            ])
            ->text('code')
            ->user('user_id')
            ->text('total_sum', ['nowrap' => 1])
            ->date('time_start', ['format' => 'full', 'nowrap' => 1])
            ->date('time_end', ['format' => 'full', 'nowrap' => 1])
            ->link('cat_id', './?object=category_editor&action=edit_item&id=%d', _class('cats')->_get_items_names_cached('shop_cats'))
            ->link('order_id', './?object=manage_shop&action=view_order&id=%d')
            ->link('status', '', $this->_statuses)
            ->btn_edit('', './?object=' . main()->_get('object') . '&action=coupon_edit&id=%d', ['no_ajax' => 1])
            ->btn_view('', './?object=' . main()->_get('object') . '&action=coupon_view&id=%d', ['no_ajax' => 1])
            ->btn_delete('', './?object=' . main()->_get('object') . '&action=coupon_delete&id=%d')
            ->footer_add('', './?object=' . main()->_get('object') . '&action=coupon_add', ['no_ajax' => 1]);
    }



    public function coupon_delete()
    {
        $_GET['id'] = (int) ($_GET['id']);
        if ( ! empty($_GET['id'])) {
            $info = db()->query_fetch('SELECT * FROM ' . db('shop_coupons') . ' WHERE id=' . (int) ($_GET['id']));
        }
        if ( ! empty($info['id'])) {
            db()->query('DELETE FROM ' . db('shop_coupons') . ' WHERE id=' . (int) ($_GET['id']) . ' LIMIT 1');
            common()->admin_wall_add(['coupon deleted: ' . $_GET['id'], $_GET['id']]);
        }
        if ($_POST['ajax_mode']) {
            main()->NO_GRAPHICS = true;
            echo $_GET['id'];
        } else {
            return js_redirect('./?object=' . main()->_get('object') . '&action=coupons');
        }
    }


    public function coupon_add()
    {
        if (main()->is_post()) {
            if ( ! $_POST['code']) {
                _re('Code must be entered');
            } else {
                $_POST['code'] = $this->_cleanup_code($_POST['code']);
                $cnt = db()->get_one('SELECT COUNT(`id`) AS `cnt` FROM `' . db('shop_coupons') . "` WHERE `code`='" . $_POST['code'] . "'");
                if ($cnt != 0) {
                    _re('Code already exists');
                }
            }
            if ( ! common()->_error_exists()) {
                $sql_array = [
                    'code' => $this->_cleanup_code($_POST['code']),
                    'user_id' => (int) ($_POST['user_id']),
                    'sum' => (int) ($_POST['sum']),
                    'status' => (int) ($_POST['status']),
                    'cat_id' => (int) ($_POST['cat_id']),
                    'order_id' => (int) ($_POST['order_id']),
                    'time_start' => strtotime($_POST['time_start']),
                    'time_end' => strtotime($_POST['time_end']),
                ];
                db()->insert(db('shop_coupons'), db()->es($sql_array));
                common()->admin_wall_add(['shop coupon added: ' . $this->_cleanup_code($_POST['code']), db()->insert_id()]);
                return js_redirect('./?object=' . main()->_get('object') . '&action=coupons');
            }
        }

        $replace = [
            'form_action' => './?object=' . main()->_get('object') . '&action=coupon_add',
            'back_url' => './?object=' . main()->_get('object') . '&action=coupons',
        ];
        return form($replace)
            ->text('code')
            ->integer('user_id')
            ->integer('sum')
            ->select_box('status', $this->_statuses)
            ->select_box('cat_id', module('manage_shop')->_cats_for_select, ['desc' => 'Main category', 'edit_link' => './?object=category_editor&action=show_items&id=shop_cats', 'translate' => 0])
            ->integer('order_id')
            ->datetime_select('time_start', null, ['with_time' => 1])
            ->datetime_select('time_end', null, ['with_time' => 1])
            ->save_and_back();
    }


    public function coupon_edit()
    {
        $_GET['id'] = (int) ($_GET['id']);
        if (empty($_GET['id'])) {
            return _e('Empty ID!');
        }
        $coupon_info = db()->query_fetch('SELECT * FROM ' . db('shop_coupons') . ' WHERE id=' . $_GET['id']);
        if (main()->is_post()) {
            if ( ! $_POST['code']) {
                _re('Code must be entered');
            } else {
                $_POST['code'] = $this->_cleanup_code($_POST['code']);
                $cnt = db()->get_one('SELECT COUNT(`id`) AS `cnt` FROM `' . db('shop_coupons') . "` WHERE `code`='" . $_POST['code'] . "' AND `id`!=" . $_GET['id']);
                if ($cnt != 0) {
                    _re('Code already exists');
                }
            }
            if ( ! common()->_error_exists()) {
                $sql_array = [
                    'code' => $this->_cleanup_code($_POST['code']),
                    'user_id' => (int) ($_POST['user_id']),
                    'sum' => (int) ($_POST['sum']),
                    'status' => (int) ($_POST['status']),
                    'cat_id' => (int) ($_POST['cat_id']),
                    'order_id' => (int) ($_POST['order_id']),
                    'time_start' => strtotime($_POST['time_start']),
                    'time_end' => strtotime($_POST['time_end']),
                ];
                db()->update('shop_coupons', db()->es($sql_array), 'id=' . $_GET['id']);
                common()->admin_wall_add(['shop coupon updated: ' . $this->_cleanup_code($_POST['code']), $_GET['id']]);
                return js_redirect('./?object=' . main()->_get('object') . '&action=coupons');
            }
        }
        $replace = [
            'code' => $coupon_info['code'],
            'user_id' => $coupon_info['user_id'],
            'sum' => $coupon_info['sum'],
            'status' => $coupon_info['status'],
            'cat_id' => $coupon_info['cat_id'],
            'order_id' => $coupon_info['order_id'],
            'time_start' => date('d.m.Y I:s', $coupon_info['time_start']),
            'time_end' => date('d.m.Y I:s', $coupon_info['time_end']),
            'form_action' => './?object=' . main()->_get('object') . '&action=coupon_edit&id=' . $coupon_info['id'],
            'back_url' => './?object=' . main()->_get('object') . '&action=coupons',
        ];
        return form($replace)
            ->text('code')
            ->integer('user_id')
            ->integer('sum')
            ->select_box('status', $this->_statuses)
            ->select_box('cat_id', module('manage_shop')->_cats_for_select, ['desc' => 'Main category', 'edit_link' => './?object=category_editor&action=show_items&id=shop_cats', 'translate' => 0])
            ->integer('order_id')
            ->datetime_select('time_start', null, ['with_time' => 1])
            ->datetime_select('time_end', null, ['with_time' => 1])
            ->save_and_back();
    }



    public function coupon_view()
    {
        $_GET['id'] = (int) ($_GET['id']);
        if ( ! empty($_GET['id'])) {
            $info = db()->query_fetch('SELECT * FROM ' . db('shop_coupons') . ' WHERE id=' . (int) ($_GET['id']));
        }
        if (empty($info['id'])) {
            return js_redirect('./?object=' . main()->_get('object') . '&action=coupons');
        }
        $info['status'] = $this->_statuses[$info['status']];
        $cats = _class('cats')->_get_items_names_cached('shop_cats');
        $info['cat_id'] = $cats[$info['cat_id']];

        $out = form2($info, ['dd_mode' => 1, 'big_labels' => true])
            ->info('code')
            ->user_info('user_id')
            ->info_date('time_start', ['format' => 'full'])
            ->info_date('time_end', ['format' => 'full'])
            ->info('sum')
            ->info('status')
            ->info('cat_id');
        $out .= table('SELECT * FROM ' . db('shop_coupons_log') . " WHERE `code`='" . $info['code'] . "' ORDER BY `time` DESC")
            ->date('time', ['format' => 'full', 'nowrap' => 1])
            ->text('action');

        return $out;
    }

    public function _cleanup_code($code)
    {
        return preg_replace('/[^0-9]+/ims', '', strip_tags($code));
    }
}
