<?php

return [
    'fields' => [
        'product_set_id' => [
            'name' => 'product_set_id',
            'type' => 'int',
            'length' => 10,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'product_id' => [
            'name' => 'product_id',
            'type' => 'int',
            'length' => 10,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'param_id' => [
            'name' => 'param_id',
            'type' => 'int',
            'length' => 10,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
        'quantity' => [
            'name' => 'quantity',
            'type' => 'int',
            'length' => 10,
            'decimals' => null,
            'unsigned' => true,
            'nullable' => false,
            'default' => '0',
            'charset' => null,
            'collate' => null,
            'auto_inc' => false,
            'primary' => false,
            'unique' => false,
            'values' => null,
        ],
    ],
    'indexes' => [
        'basket_id' => [
            'name' => 'basket_id',
            'type' => 'index',
            'columns' => [
                'product_set_id' => 'product_set_id',
            ],
        ],
    ],
    'foreign_keys' => [
    ],
    'options' => [
    ],
];
