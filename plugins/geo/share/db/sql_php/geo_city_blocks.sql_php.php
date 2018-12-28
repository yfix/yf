<?php

return [
    'fields' => [
        'start_ip' => [
            'name' => 'start_ip',
            'type' => 'int',
            'length' => 8,
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
        'end_ip' => [
            'name' => 'end_ip',
            'type' => 'int',
            'length' => 8,
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
        'loc_id' => [
            'name' => 'loc_id',
            'type' => 'int',
            'length' => 6,
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
                'end_ip' => 'end_ip',
            ],
        ],
    ],
    'foreign_keys' => [
    ],
    'options' => [
    ],
];
