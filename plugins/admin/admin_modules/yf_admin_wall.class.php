<?php

/**
 * Wall.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_admin_wall
{
    public function show()
    {
        $sql = 'SELECT * FROM ' . db('admin_walls') . ' WHERE user_id=' . (int) (main()->ADMIN_ID) . ' ORDER BY add_date DESC';
        return table($sql, [
                'filter' => true,
                'filter_params' => [
                    'message' => 'like',
                ],
            ])
            ->date('add_date')
            ->text('message')
            ->text('object')
            ->text('action')
            ->text('object_id')
            ->btn_view();
    }

    /**
     * Proxy between real link and wall contents.
     */
    public function view()
    {
        $id = (int) ($_GET['id']);
        if ($id) {
            $msg = db()->from('admin_walls')->where('user_id', (int) main()->ADMIN_ID)->where($id)->get();
        }
        if ( ! $msg['id']) {
            return _e('Wrong message id');
        }
        $link = '';
        $object = $msg['object'];
        $action = $msg['action'];
        $object_id = $msg['object_id'];
        $module = module($object);
        $hook_name = '_hook_wall_link';
        if (is_object($module) && method_exists($module, $hook_name)) {
            $link = $module->$hook_name($msg);
        }
        if ( ! $link) {
            $link = url('/' . $object . '/' . $action . '/' . $object_id);
        }
        return js_redirect($link);
    }


    public function filter_save()
    {
        $filter_name = $_GET['object'] . '__view';
        if ($_GET['page'] == 'clear') {
            $_SESSION[$filter_name] = [];
        } else {
            $_SESSION[$filter_name] = $_POST;
            foreach (explode('|', 'clear_url|form_id|submit') as $f) {
                if (isset($_SESSION[$filter_name][$f])) {
                    unset($_SESSION[$filter_name][$f]);
                }
            }
        }
        return js_redirect('./?object=' . $_GET['object'] . '&action=' . str_replace($_GET['object'] . '__', '', $filter_name));
    }


    public function _show_filter()
    {
        if ( ! in_array($_GET['action'], ['show'])) {
            return false;
        }
        $order_fields = [];
        foreach (explode('|', 'add_date|message|object|action|object_id|admin_id') as $v) {
            $order_fields[$v] = $v;
        }
        return form($r, [
                'filter' => true,
            ])
            ->text('message')
            ->text('object')
            ->text('action')
            ->integer('object_id')
            ->select_box('order_by', $order_fields, ['show_text' => 1])
            ->order_box()
            ->save_and_clear();
    }

    /**
     * @param mixed $params
     */
    public function _hook_widget__admin_wall($params = [])
    {
        $meta = [
            'name' => 'Admin wall',
            'desc' => 'Latest events for admin',
            'configurable' => [
//				'order_by'	=> array('id','name','active'),
            ],
        ];
        if ($params['describe_self']) {
            return $meta;
        }
        $config = $params;
        $sql = 'SELECT * FROM ' . db('admin_walls') . ' WHERE user_id=' . (int) (main()->ADMIN_ID) . ' ORDER BY add_date DESC';
        return table($sql, ['no_header' => 1, 'btn_no_text' => 1, 'pages_on_top' => 1, 'pager_path' => './?object=' . $_GET['object'] . '&action=show'])
            ->date('add_date')
            ->admin('user_id')
            ->text('message')
            ->btn_view();
    }


    public function _hook_settings(&$selected = [])
    {
        //		return array(
//			array('yes_no_box', 'admin_home__DISPLAY_STATS'),
//		);
    }
}
