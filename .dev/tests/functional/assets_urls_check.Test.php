<?php

require_once __DIR__.'/db_real_abstract.php';

class assets_urls_check_test extends yf_unit_tests {
	public function get_url_size($url) {
		if (substr($url, 0, 2) === '//') {
			$url = 'http:'.$url;
		}
		return strlen(file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 5]])));
	}
	public function test_do() {
		$data = require YF_PATH.'.dev/scripts/assets/assets_urls_collect.php';
		foreach ($data['urls'] as $url) {
			$size = $this->get_url_size($url);
			foreach ($data['paths'][$url] as $path) {
				$this->assertTrue(($size > 50), $url.' | '.$path.' | '.$size);
			}
		}
	}
}
