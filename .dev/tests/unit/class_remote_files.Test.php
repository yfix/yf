<?php

require_once __DIR__ . '/yf_unit_tests_setup.php';

/**
 * @requires extension curl
 */
class class_remote_files extends yf\tests\wrapper
{
    public function test_get_remote_page_simple()
    {
        $this->assertNotEmpty(common()->get_remote_page('http://google.com/'));
    }
}
