<?php

return function () {
    $a = [
        'name' => [
            'key1' => 'v1',
        ],
    ];
    return form((array) $_POST + $a)
        ->validate([
            'name[key1]' => 'trim|required',
            'name[key2]' => 'trim|required',
        ])
        ->text('name[]')
        ->text('name[]')
        ->text('name[key1]')
        ->text('name[key2]')
        ->save();
};
