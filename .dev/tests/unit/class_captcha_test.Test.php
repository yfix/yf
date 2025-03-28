<?php

require_once __DIR__ . '/yf_unit_tests_setup.php';

/**
 */
class class_captcha_test extends yf\tests\wrapper
{
    public function test_captcha()
    {
        ob_start();
        _class('captcha')->show_image($no_header = true, $no_exit = true);
        $img = ob_get_clean();
        $this->assertNotEmpty($img);
        $this->assertGreaterThan(2000, strlen($img));
    }
}
