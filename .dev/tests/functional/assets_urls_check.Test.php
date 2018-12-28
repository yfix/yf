<?php

require_once __DIR__ . '/db_real_abstract.php';

class assets_urls_check_test extends yf\tests\wrapper
{
    public function get_url_contents($url, $timeout = 5)
    {
        try {
            $content = file_get_contents($url, false, stream_context_create(['http' => ['timeout' => $timeout]]));
        } catch (Exception $e) {
            echo PHP_EOL . $e->getMessage() . PHP_EOL;
        }
        return $content;
    }
    public function get_url_size($url)
    {
        if (substr($url, 0, 2) === '//') {
            $url = 'http:' . $url;
        }
        $content = $this->get_url_contents($url, 5);
        // Possible fix for overcome errors by overload protection filters
        // Allow 1 retry after some sleep with increased timeout
        if ( ! $content) {
            sleep(1);
            $content = $this->get_url_contents($url, 15);
        }
        return strlen($content);
    }
    public function test_do()
    {
        $data = require YF_PATH . '.dev/scripts/assets/assets_urls_collect.php';
        foreach ($data['urls'] as $url) {
            $size = $this->get_url_size($url);
            foreach ($data['paths'][$url] as $path) {
                $this->assertTrue(($size > 50), $url . ' | ' . $path . ' | ' . $size);
            }
        }
    }
}
