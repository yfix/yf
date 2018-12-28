<?php

return [
    'fields' => [
        'lang' => [
            'name' => 'lang',
            'type' => 'char',
            'length' => 2,
            'decimals' => null,
            'unsigned' => null,
            'nullable' => false,
            'default' => null,
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => true,
            'unique' => false,
            'values' => null,
        ],
        'country' => [
            'name' => 'country',
            'type' => 'char',
            'length' => 2,
            'decimals' => null,
            'unsigned' => null,
            'nullable' => false,
            'default' => null,
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => true,
            'unique' => false,
            'values' => null,
        ],
    ],
    'indexes' => [
        'PRIMARY' => [
            'name' => 'PRIMARY',
            'type' => 'primary',
            'columns' => [
                'lang' => 'lang',
                'country' => 'country',
            ],
        ],
    ],
    'foreign_keys' => [
    ],
    'options' => [
    ],
];
