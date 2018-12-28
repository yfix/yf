<?php

return function () {
    $offer_types = [
        'buy' => 'buy',
        'ask' => 'ask',
    ];
    $currencies = [
        'UAH' => 'UAH',
        'USD' => 'USD',
    ];
    $split_period = [
        '1 day' => '1 day',
        '2 days' => '2 days',
        '3 days' => '3 days',
    ];
    $order_fields = [
        'id', 'title', 'amount', 'percent',
    ];
    return form($replace, [
            'filter' => true,
        ])
        ->text('title', ['class' => 'input-medium', 'tip' => 'Title field helping description'])
        ->select_box('type', $offer_types, ['show_text' => 1, 'class_add' => 'input-medium'])
        ->select_box('currency', $currencies, ['show_text' => 1, 'class_add' => 'input-medium'])
        ->ui_range('amount', [
            'create_inputs' => false,
            'slide_event' => '$( "#amount" ).val( ui.values[ 0 ] );$( "#amount_and" ).val( ui.values[ 1 ] );',
        ])
        ->row_start(['desc' => 'Amount from/to'])
            ->money('amount')
            ->money('amount__and')
        ->row_end()
        ->row_start(['desc' => 'Interest rate from/to'])
            ->number('percent', ['class_add' => 'input-small'])
            ->number('percent__and', ['class_add' => 'input-small'])
        ->row_end()
        ->row_start(['desc' => 'per'])
            ->select_box('split_period', $split_period, ['show_text' => 1, 'class_add' => 'input-medium'])
        ->row_end()
        ->select_box('order_by', $order_fields, ['show_text' => 1, 'class_add' => 'input-medium'])
        ->radio_box('order_direction', ['asc' => 'Ascending', 'desc' => 'Descending']/*, ['selected' => 'asc']*/)
        ->save_and_clear();
};
