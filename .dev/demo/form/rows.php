<?php

return function () {
    $a = [
        'title' => 'title',
        'amount' => '50',
    ];
    return form((array) $_POST + $a)
        ->validate([
            'duration_month2' => 'trim|required|gt[10]',
            'desc' => 'trim|required',
        ])
        ->text('title')
        ->select_box('want', ['val1', 'val2'])
        ->row_start(['desc' => 'For a period of'])
            ->number('duration_day', 'day')
            ->number('duration_week', 'week')
            ->number('duration_month', 'month')
            ->number('duration_year', 'year')
        ->row_end()
        ->row_start(['desc' => 'Interest rate'])
            ->number('percent', ['class_add' => 'input-small'])
            ->button('per', ['disabled' => 1])
            ->select_box('split', ['val1', 'val2'])
        ->row_end()
        ->row_start(['desc' => 'For a period of'])
            ->number('duration_day2', 'day')
            ->number('duration_week2', 'week', ['show_label' => 1])
            ->number('duration_month2', 'month')
            ->number('duration_year2', 'year')
        ->row_end()
        ->row_start(['desc' => 'order'])
            ->select_box('order_by', ['name' => 'name', 'desc' => 'desc'], ['show_text' => 1, 'class_add' => 'input-medium'])
            ->radio_box('order_direction', ['asc' => 'Ascending', 'desc' => 'Descending'], ['outer_label' => 'Direction'])
        ->row_end()
        ->textarea('desc')
        ->submit();
};
