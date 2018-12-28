<?php

return [
    'fields' => [
        'id' => [
            'name' => 'id',
            'type' => 'char',
            'length' => 32,
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
        'ts' => [
            'name' => 'ts',
            'type' => 'timestamp',
            'length' => null,
            'decimals' => null,
            'unsigned' => null,
            'nullable' => false,
            'default' => 'CURRENT_TIMESTAMP',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
            'on_update' => 'ON UPDATE CURRENT_TIMESTAMP',
        ],
        'data' => [
            'name' => 'data',
            'type' => 'text',
            'length' => null,
            'decimals' => null,
            'unsigned' => null,
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
                'id' => 'id',
            ],
        ],
        'ts' => [
            'name' => 'ts',
            'type' => 'index',
            'columns' => [
                'ts' => 'ts',
            ],
        ],
    ],
    'foreign_keys' => [
    ],
    'options' => [
    ],
];
