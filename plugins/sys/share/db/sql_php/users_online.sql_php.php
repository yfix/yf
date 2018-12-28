<?php

return [
    'fields' => [
        'user_id' => [
            'name' => 'user_id',
            'type' => 'bigint',
            'length' => 20,
            'decimals' => null,
            'unsigned' => false,
            'nullable' => false,
            'default' => null,
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => true,
            'unique' => false,
            'values' => null,
        ],
        'user_type' => [
            'name' => 'user_type',
            'type' => 'enum',
            'length' => null,
            'decimals' => null,
            'unsigned' => null,
            'nullable' => false,
            'default' => null,
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => true,
            'unique' => false,
            'values' => [
                'user_id' => 'user_id',
                'user_id_tmp' => 'user_id_tmp',
                'admin_id' => 'admin_id',
            ],
        ],
        'time' => [
            'name' => 'time',
            'type' => 'int',
            'length' => 11,
            'decimals' => null,
            'unsigned' => false,
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
                'user_id' => 'user_id',
                'user_type' => 'user_type',
            ],
        ],
    ],
    'foreign_keys' => [
    ],
    'options' => [
    ],
];
