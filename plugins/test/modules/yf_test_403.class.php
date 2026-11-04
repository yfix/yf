<?php

class yf_test_403
{
    public function show()
    {
        no_graphics(true);
        header('X-Robots-Tag: noindex,nofollow,noarchive,nosnippet');
        header((($_SERVER['SERVER_PROTOCOL'] ?? null) ? ($_SERVER['SERVER_PROTOCOL'] ?? null) : 'HTTP/1.1') . ' 403 Forbidden');
        echo 'Сайт недоступен в вашей стране<br />' . PHP_EOL . 'This website is not available in your country';
        exit;
    }
}
