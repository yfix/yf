#!/usr/bin/php
<?php

$files = [
    'en_content_strings.grd' => 'https://chromium.googlesource.com/chromium/src/+/master/content/app/strings/content_strings.grd?format=TEXT',
    'en_gb_content_strings.xtb' => 'https://chromium.googlesource.com/chromium/src/+/master/content/app/strings/translations/content_strings_en-GB.xtb?format=TEXT',
    'ru_content_strings.xtb' => 'https://chromium.googlesource.com/chromium/src/+/master/content/app/strings/translations/content_strings_ru.xtb?format=TEXT',
    'uk_content_strings.xtb' => 'https://chromium.googlesource.com/chromium/src/+/master/content/app/strings/translations/content_strings_uk.xtb?format=TEXT',
];
foreach ($files as $file => $url) {
    if ( ! file_exists($file)) {
        file_put_contents($file, base64_decode(file_get_contents($url)));
    }
}
