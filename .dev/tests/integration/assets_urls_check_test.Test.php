<?php

require_once dirname(__DIR__) . '/yf_unit_tests_setup.php';

/**
 */
class assets_urls_check_test extends yf\tests\wrapper
{
    public $TIMEOUT = 5;

    public function get_url_contents(string $url, int $timeout = 5)
    {
        $url = trim($url);
        if (substr($url, 0, 2) === '//') {
            $url = 'https:' . $url;
        }
        if (substr($url, 0, 4) !== 'http') {
            $url = 'https://' . $url;
        }
        try {
            $content = file_get_contents($url, false, stream_context_create(['http' => ['timeout' => $timeout]]));
            $this->assertNotEmpty($content);
        } catch (Exception $e) {
            echo self::_pretty_show_exception($e);
        }
        return $content;
    }

    public function get_url_size(string $url) : int
    {
        $url = trim($url);
        if (substr($url, 0, 2) === '//') {
            $url = 'https:' . $url;
        }
        if (substr($url, 0, 4) !== 'http') {
            $url = 'https://' . $url;
        }
        $content = $this->get_url_contents($url, $this->TIMEOUT);
        $this->assertNotEmpty($content);
        // Possible fix for overcome errors by overload protection filters
        // Allow 1 retry after some sleep with increased timeout
        if ( ! $content) {
            sleep(1);
            $content = $this->get_url_contents($url, $this->TIMEOUT * 2);
        }
        return strlen($content);
    }

    public function test_do()
    {
        ini_set('default_socket_timeout', $this->TIMEOUT);

        $data = require YF_PATH . '.dev/scripts/assets/assets_urls_collect.php';

        $this->assertIsArray($data['urls']);
        $this->assertNotEmpty($data['urls']);

        $this->assertIsArray($data['paths']);
        $this->assertNotEmpty($data['paths']);

        $total = count($data['urls']);
        $i = 0;
        foreach ($data['urls'] as $_url) {
            $url = trim($_url);
            if (substr($url, 0, 2) === '//') {
                $url = 'https:' . $url;
            }
            if (substr($url, 0, 4) !== 'http') {
                $url = 'https://' . $url;
            }
            $this->assertNotEmpty($url);
            $size = $this->get_url_size($url);
            foreach ($data['paths'][$_url] as $path) {
                $this->assertTrue(($size > 50), $_url . ' | ' . $path . ' | ' . $size);
            }
            fwrite(STDERR, ++$i . '/' . $total . ' | ' . $_url  . ' | ' . $size . PHP_EOL);
            ob_flush();
        }
    }
}
