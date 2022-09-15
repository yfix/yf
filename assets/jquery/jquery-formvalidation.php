<?php

return function () {
    $lang = conf('language');
    $lang_files = [
        'en' => 'en_US',
        'ru' => 'ru_RU',
        'ua' => 'ua_UA',
    ];
    return [
        'versions' => [
            '0.6.2-dev' => [
                'js' => [
                    '//cdnjs.cloudflare.com/ajax/libs/formvalidation/0.6.2-dev/js/formValidation.min.js',
                    // '//cdnjs.cloudflare.com/ajax/libs/formvalidation/0.6.2-dev/js/framework/bootstrap.min.js',
                    $lang_files[$lang] ? '//cdnjs.cloudflare.com/ajax/libs/formvalidation/0.6.2-dev/js/language/' . $lang_files[$lang] . '.js' : '',
                ],
                'css' => '//cdnjs.cloudflare.com/ajax/libs/formvalidation/0.6.2-dev/css/formValidation.min.css',
            ],
        ],
        'require' => [
            'asset' => 'jquery',
        ],
    ];
};
