<?php

return [
    'fields' => [
        'product_id' => [
            'name' => 'product_id',
            'type' => 'int',
            'length' => 11,
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
        'related_id' => [
            'name' => 'related_id',
            'type' => 'int',
            'length' => 11,
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
    ],
    'indexes' => [
        'PRIMARY' => [
            'name' => 'PRIMARY',
            'type' => 'primary',
            'columns' => [
                'product_id' => 'product_id',
                'related_id' => 'related_id',
            ],
        ],
    ],
    'foreign_keys' => [
    ],
    'options' => [
    ],
];
