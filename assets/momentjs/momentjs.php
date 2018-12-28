<?php

return function () {
    $lang = conf('language');
    return [
    'versions' => [
        '2.13.0' => [
            'js' => '//cdnjs.cloudflare.com/ajax/libs/moment.js/2.13.0/moment-with-locales.min.js',
        ],
    ],
    'cdn' => [
        'url' => '//cdnjs.cloudflare.com/ajax/libs/moment.js/{version}/',
        'version' => '2.13.0',
        'js' => 'moment-with-locales.min.js',
    ],
    'add' => [
        'js' => 'moment.locale("' . $lang . '");',
    ],
    'info' => [
        'url' => 'http://momentjs.com/',
        'name' => 'Parse, validate, manipulate, and display dates in JavaScript.',
        'desc' => 'Moment was designed to work both in the browser and in Node.js.
			All code should work in both of these environments, and all unit tests are run in both of these environments.',
        'git' => 'https://github.com/moment/moment.git',
    ],
];
};
