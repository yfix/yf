<?php


class yf_manage_geo_countries
{
    const table = 'geo_countries';


    private $params = [
        'table' => 'geo_countries',
        'id' => 'code',
    ];


    public function show()
    {
        return table(from(self::table), [
                'id' => 'code',
                'filter' => true,
                'filter_params' => [
                    '__default_order' => 'active DESC, name ASC',
                ],
                'pager_records_on_page' => 300,
            ])
            ->text('code')
            ->text('code3')
            ->text('name')
            ->text('name_eng')
            ->text('cont')
            ->text('tld')
            ->text('currency')
            ->text('languages')
            ->text('geoname_id')
            ->btn_active();
    }


    public function active()
    {
        return _class('admin_methods')->active($this->params);
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
            'code' => 'code',
            'name' => 'name',
            'active' => 'active',
        ];
        $per_page = ['' => '', 10 => 10, 20 => 20, 50 => 50, 100 => 100, 200 => 200, 500 => 500];
        return form($r, [
                'filter' => true,
            ])
            ->text('name')
            ->select_box('per_page', $per_page, ['class' => 'input-small'])
            ->select_box('order_by', $order_fields, ['show_text' => 1, 'class' => 'input-medium'])
            ->radio_box('order_direction', ['asc' => 'Ascending', 'desc' => 'Descending'], ['horizontal' => 1, 'translate' => 1])
            ->save_and_clear();
    }
}
