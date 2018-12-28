<?php

return function () {
    $a = [
        'title' => 'title',
        'amount' => '50',
        'type' => common()->select_box('type', [1, 2]),
        'split_period' => common()->select_box('split', [1, 2]),
        'duration' => [
            'day' => 10,
            'week' => 2,
            'month' => 3,
            'year' => 0,
        ],
    ];
    return form((array) $_POST + $a)
        ->validate([
            '__form_id__' => 'validate_sample_form',
            'title' => 'trim|required|xss_clean',
            'type' => 'trim|required|xss_clean',
            'amount' => 'trim|required|min_length[1]|max_length[10]|numeric|xss_clean',
            'percent' => 'trim|required|min_length[1]|max_length[4]|numeric|xss_clean',
            'split_period' => 'trim|required|min_length[1]|max_length[1]|xss_clean',
            'descr' => 'trim|required|xss_clean',
            'duration' => 'required_any[duration_*]',
            'integer' => 'integer',
        ])
        ->db_insert_if_ok(
            'some_demo_table',
            ['group', 'email', 'password', 'first_name', 'last_name', 'middle_name'],
            ['add_date' => time()],
            ['on_success_text' => 'Your account was created successfully!']
        )
        ->text('title')
        ->select_box('type', [1, 2], ['desc' => 'I want'])
        ->money('amount')
        ->row_start(['desc' => 'For a period of', 'name' => 'duration'])
            ->number('duration_day', 'day', ['class' => 'input-small'])
            ->number('duration_week', 'week', ['class' => 'input-small'])
            ->number('duration_month', 'month', ['class' => 'input-small'])
            ->number('duration_year', 'year', ['class' => 'input-small'])
        ->row_end()
        ->row_start(['desc' => 'Interest rate'])
            ->number('percent', ['class' => 'input-small'])
            ->button('per', ['disabled' => 1])
            ->select_box('split_period', ['val1', 'val2'])
        ->row_end()
            ->text('integer')
            ->textarea('desc')
        ->submit();
};
