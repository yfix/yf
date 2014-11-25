<?php

require_once __DIR__.'/db_real_abstract.php';

class assets_urls_check_test extends PHPUnit_Framework_TestCase {
	public function get_url_size($url) {
		if (substr($url, 0, 2) === '//') {
			$url = 'http:'.$url;
		}
		exec('curl -4 -f --connect-timeout 3 --max-time 5 "'.$url.'" 2>/dev/null | wc -c', $out);
		return $out[0];
	}
	public function test_do() {
		require YF_PATH.'.dev/scripts/assets/assets_urls_collect.php';
		foreach ($urls as $url) {
			$size = $this->get_url_size($url);
			foreach ($url_paths[$url] as $path) {
				$this->assertTrue(($size > 50), $url.' | '.$path.' | '.$size);
			}
		}
	}
}
