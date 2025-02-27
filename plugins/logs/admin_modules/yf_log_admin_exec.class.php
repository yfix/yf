<?php

class yf_log_admin_exec
{
    public function show()
    {
        $sql = 'SELECT * FROM ' . db('log_admin_exec');
		$filter = _class('admin_methods')->_get_filter();
        return table($sql, [
                'filter' => $filter,
                'filter_params' => [
                    'ip'          => 'like',
                    'user_agent'  => 'like',
                    'referer'     => 'like',
                    'request_uri' => 'like',
					'__default_order' => 'ORDER BY `date` DESC',
                ],
            ])
            ->admin('admin_id')
            ->link('ip', './?object=' . $_GET['object'] . '&action=show_for_ip&id=%d')
            ->date('date', ['format' => 'full', 'nowrap' => 1])
            ->text('user_agent')
            ->text('referer')
            ->text('request_uri')
            ->text('exec_time')
            ->text('num_dbq')
            ->text('page_size');
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
        foreach (explode('|', 'admin_id|admin_group|date|ip|user_agent|referer') as $f) {
            $order_fields[$f] = $f;
        }
		return form($r, ['filter' => true])
            ->number('admin_id')
            ->text('ip')
            ->text('user_agent')
            ->text('referer')
            ->text('request_uri')
            ->select_box('order_by', $order_fields, ['show_text' => 1])
            ->order_box()
            ->save_and_clear();
    }

    public function _hook_widget__admin_access_log($params = [])
    {
        // TODO
    }
}
