<?php

return [
    'fields' => [
        'supplier_id' => [
            'name' => 'supplier_id',
            'type' => 'int',
            'length' => 11,
            'decimals' => null,
            'unsigned' => false,
            'nullable' => false,
            'default' => '0',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => true,
            'unique' => false,
            'values' => null,
        ],
        'cat_id' => [
            'name' => 'cat_id',
            'type' => 'int',
            'length' => 11,
            'decimals' => null,
            'unsigned' => false,
            'nullable' => false,
            'default' => '0',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => true,
            'unique' => false,
            'values' => null,
        ],
        'name' => [
            'name' => 'name',
            'type' => 'varchar',
            'length' => 255,
            'decimals' => null,
            'unsigned' => null,
            'nullable' => false,
            'default' => '',
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
                'supplier_id' => 'supplier_id',
                'cat_id' => 'cat_id',
                'name' => 'name',
            ],
        ],
    ],
    'foreign_keys' => [
    ],
    'options' => [
    ],
];
