<?php

return [
    'fields' => [
        'notification_id' => [
            'name' => 'notification_id',
            'type' => 'int',
            'length' => 11,
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
        'receiver_id' => [
            'name' => 'receiver_id',
            'type' => 'int',
            'length' => 11,
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
        'receiver_type' => [
            'name' => 'receiver_type',
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
                'admin_id' => 'admin_id',
                'user_id_tmp' => 'user_id_tmp',
            ],
        ],
        'is_read' => [
            'name' => 'is_read',
            'type' => 'tinyint',
            'length' => 4,
            'decimals' => null,
            'unsigned' => false,
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
        'PRIMARY' => [
            'name' => 'PRIMARY',
            'type' => 'primary',
            'columns' => [
                'notification_id' => 'notification_id',
                'receiver_id' => 'receiver_id',
                'receiver_type' => 'receiver_type',
            ],
        ],
    ],
    'foreign_keys' => [
    ],
    'options' => [
    ],
];
