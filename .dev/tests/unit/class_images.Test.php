<?php

require_once __DIR__ . '/yf_unit_tests_setup.php';

class class_images_test extends yf\tests\wrapper
{
    public function test_resize()
    {
        if (getenv('CI') == 'jenkins') {
            $this->markTestSkipped('Right now we skip this test, when running inside Jenkins.');
        }
        $url = 'https://s3-eu-west-1.amazonaws.com/yfix/oauth/providers/google.png';
        //		$url = 'https://www.google.com.ua/images/branding/googlelogo/1x/googlelogo_color_272x92dp.png';

        $tmp_path = '/tmp/yf_unit_tests_' . substr(md5($url), 0, 8) . '.' . pathinfo($url, PATHINFO_EXTENSION);
        if ( ! file_exists($tmp_path)) {
            file_put_contents($tmp_path, file_get_contents($url));
        }
        $this->assertFileExists($tmp_path);

        $out_path = '/tmp/yf_unit_tests_' . substr(md5($url), 0, 8) . '_out.' . pathinfo($url, PATHINFO_EXTENSION);
        if (file_exists($out_path)) {
            unlink($out_path);
        }

        $thumber = _class('make_thumb', 'classes/common/');
        $def_priority = $thumber->LIBS_PRIORITY;

        // Try imagick
        if (extension_loaded('imagick')) {
            $thumber->LIBS_PRIORITY = ['imagick'];
            $thumber->_init();
            $res = common()->make_thumb($tmp_path, $out_path, 10, 10);
            $this->assertTrue($res);
            $this->assertFileExists($out_path);
        }
        if (file_exists($out_path)) {
            unlink($out_path);
        }
        // Try GD
        if (extension_loaded('gd') && function_exists('imagejpeg')) {
            $thumber->LIBS_PRIORITY = ['gd'];
            $thumber->_init();
            $res = common()->make_thumb($tmp_path, $out_path, 10, 10);
            $this->assertTrue($res);
            $this->assertFileExists($out_path);
        }
        $thumber->LIBS_PRIORITY = $def_priority;
    }
}
