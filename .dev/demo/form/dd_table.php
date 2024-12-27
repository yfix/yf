<?php

return function () {
    $get_data = function () {
        $a = [
            'id' => '2', 'type' => 'ask', 'user_id' => '4', 'currency' => '1', 'amount' => '1.00',
            'payments_period' => '1', 'percent' => '1', 'percents_period' => '1', 'split_period' => 'd',
            'frequency_payments' => 'w', 'descr' => '', 'title' => 'test', 'duration' => '3024000',
            'min_user_rating' => '0', 'add_date' => '1383137996', 'edit_date' => '1383213181', 'end_date' => '0', 'status' => '1',
        ];
        $offer_status = [
            '0' => 'closed', '1' => 'available',
        ];
        $currencies = [
            1 => 'USD', 2 => 'EUR', 3 => 'OM', 4 => 'GBP', 5 => 'CHF',
        ];
        $offer_status = [
            0 => 'closed', 1 => 'Доступно',
        ];
        return [
            'title' => $a['title'],
            'descr' => $a['descr'],
            'type' => t($a['type']),
            'currency' => isset($currencies[$a['currency']]) ? $currencies[$a['currency']] : '',
            'amount' => $a['amount'],
            'duration' => $a['duration'],
            'percent' => $a['percent'],
            'percents_period' => $a['percents_period'],
            'payments_period' => $a['payments_period'],
            'min_user_rating' => $a['min_user_rating'],
            'split_period' => $a['split_period'],
            'frequency_payments' => $a['frequency_payments'],
            'add_date' => date('d.m.Y', $a['add_date']),
            'end_date' => ! empty($a['end_date']) ? date('d.m.Y', $a['end_date']) : t('Not stated'),
            'status' => t(isset($offer_status[$a['status']]) ? $offer_status[$a['status']] : $offer_status[0]),
            'custom_html' => ! empty($custom_html) ? $custom_html : false,
            'url_owner_profile' => $url_owner_profile ?? '',
            'url_offers' => 'Offers',
            'stars' => 4.5,
            'stars2' => 4.5,
        ];
    };
    $data = $get_data();
    return html()->dd_table($data, [
            'type' => ['func' => 'info', 'label' => 'info'],
            'currency' => ['func' => 'link', 'label' => 'info', 'link' => url('/currency')],
            'descr' => '',
            'custom_html' => '',
            'stars' => 'stars',
            'stars2' => ['func' => 'stars', 'desc' => 'stars', 'max' => 10, 'stars' => 10, 'color_ok' => 'red'],
            'url_owner_profile' => ['func' => ''],
            'url_offers' => [
                'func' => 'link',
                'label' => 'info',
                'desc' => 'offers',
                'link' => url('/@object/@action/1'),
            ],
        ], [
            'legend' => $r['title'] ?? '',
        ]);
};
