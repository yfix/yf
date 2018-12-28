<?php

return function () {
    $lang = conf('language');
    return [
    'versions' => [
        'master' => [
            'js' => [
                '//rawgit.yfix.net/yfix/jquery-timeago/master/jquery.timeago.js',
                $lang && $lang != 'en' ? '//rawgit.yfix.net/yfix/jquery-timeago/master/locales/jquery.timeago.' . $lang . '.js' : '',
            ],
        ],
    ],
    'require' => [
        'asset' => 'jquery',
    ],
];
};
