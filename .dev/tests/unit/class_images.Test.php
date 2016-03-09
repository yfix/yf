<?php

require_once __DIR__.'/yf_unit_tests_setup.php';

/**
 * @requires extension gd
 * @requires function imagettftext
 */
class class_images_test extends yf_unit_tests {
	public function test_resize_imagick() {
		$url = 'https://s3-eu-west-1.amazonaws.com/yfix/oauth/providers/google.png';
#		$url = 'https://www.google.com.ua/images/branding/googlelogo/1x/googlelogo_color_272x92dp.png';

		$tmp_path = '/tmp/yf_unit_tests_'. substr(md5($url), 0, 8). '.'.pathinfo($url, PATHINFO_EXTENSION);
		if (!file_exists($tmp_path)) {
			file_put_contents($tmp_path, file_get_contents($url));
		}
		$this->assertFileExists($tmp_path);

		$out_path = '/tmp/yf_unit_tests_'. substr(md5($url), 0, 8). '_out.'.pathinfo($url, PATHINFO_EXTENSION);
		if (file_exists($out_path)) {
			unlink($out_path);
		}
		$res = common()->make_thumb($tmp_path, $out_path, 10, 10);
		$this->assertTrue($res);
		$this->assertFileExists($out_path);
	}

	public function test_resize_gd() {
// TODO
	}
}