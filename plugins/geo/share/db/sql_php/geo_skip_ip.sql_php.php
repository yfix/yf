<?php

return [
    'fields' => [
        'ip' => [
            'name' => 'ip',
            'type' => 'int',
            'length' => 10,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => null,
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => true,
            'unique' => false,
            'values' => null,
        ],
        'hits' => [
            'name' => 'hits',
            'type' => 'int',
            'length' => 10,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => null,
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
    ],
    'indexes' => [
        'PRIMARY' => [
            'name' => 'PRIMARY',
            'type' => 'primary',
            'columns' => [
                'ip' => 'ip',
            ],
        ],
    ],
    'foreign_keys' => [
    ],
    'options' => [
    ],
];
