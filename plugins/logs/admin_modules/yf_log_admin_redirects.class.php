<?php

class yf_log_admin_redirects
{
    public function show()
    {
        $filter_name = $_GET['object'] . '__' . $_GET['action'];
        $default_filter = [
            'order_by' => 'date',
            'order_direction' => 'desc',
        ];
        $sql = 'SELECT * FROM ' . db('log_redirects') . ' WHERE is_admin="' . (string) ( ! $this->FOR_USER ? 1 : 0) . '"';
        return table($sql, [
                'filter' => (array) $_SESSION[$filter_name] + $default_filter,
                'filter_params' => [
                    'url_from' => 'like',
                    'url_to' => 'like',
                    'ip' => 'like',
                    'user_agent' => 'like',
                    'referer' => 'like',
                ],
            ])
            ->admin('user_id')
            ->link('ip', './?object=' . $_GET['object'] . '&action=show_for_ip&id=%d')
            ->date('date', ['format' => 'full', 'nowrap' => 1])
            ->text('user_agent')
            ->text('referer')
            ->text('url_from')
            ->text('url_to')
            ->text('exec_time');
    }


    public function show_for_admin()
    {
        $_GET['page'] = 'clear';
        $_GET['filter'] = 'admin_id:' . (int) ($_GET['id']);
        return $this->filter_save();
    }


    public function show_for_ip()
    {
        $_GET['page'] = 'clear';
        $_GET['filter'] = 'ip:' . preg_replace('~[^0-9\.]+~ims', '', $_GET['id']);
        return $this->filter_save();
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
        foreach (explode('|', 'user_id|user_group|date|ip|user_agent|referer|url_from|url_to') as $f) {
            $order_fields[$f] = $f;
        }
        return form($r, [
                'filter' => true,
            ])
            ->number('user_id')
            ->text('ip')
            ->text('user_agent')
            ->text('referer')
            ->text('url_from')
            ->text('url_to')
            ->select_box('order_by', $order_fields, ['show_text' => 1])
            ->order_box()
            ->save_and_clear();
    }

    /**
     * @param mixed $params
     */
    public function _hook_widget__redirects_log($params = [])
    {
        // TODO
    }
}
