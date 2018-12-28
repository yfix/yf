<?php

class yf_test_404
{
    public function show()
    {
        no_graphics(true);
        header('X-Robots-Tag: noindex,nofollow,noarchive,nosnippet');
        header(($_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1') . ' 404 Not Found');
        echo 'Page not found';
        exit;
    }
}
