<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class func_common_rss_page extends PHPUnit_Framework_TestCase {
	public static function setUpBeforeClass() {
	}
	public static function tearDownAfterClass() {
		_class('dir')->delete_dir(STORAGE_PATH.'uploads/', $delete_start_dir = true);
	}
	public function test_simple() {
		$data = array();
#		$params = array('return_feed_text' => 1);
		ob_start();
		common()->rss_page($data, $params);
		$actual = ob_get_clean();

		$expected = '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0"
 xmlns:dc="http://purl.org/dc/elements/1.1/"
 xmlns:atom="http://www.w3.org/2005/Atom"
>
	<channel>
		<title>Site feed title</title>
		<description><![CDATA[Site feed description]]></description>
		<link>./</link>
		<lastBuildDate></lastBuildDate>
	</channel>
</rss>';
		$actual = preg_replace('~<lastBuildDate>[^<]+</lastBuildDate>~ims', '<lastBuildDate></lastBuildDate>', $actual);
		$this->assertSame(trim($expected), trim($actual));
	}
	public function test_with_data() {
		$data = array(
			array(
				'date'	=> strtotime('2014-08-12 12:03:19 UTC'),
				'link'	=> url('/unit_tests/testme/1/'),
			),
			array(
				'date'	=> strtotime('2014-08-12 12:03:20 UTC'),
				'link'	=> url('/unit_tests/testme/2/'),
			),
		);
#		$params = array('return_feed_text' => 1);
		ob_start();
		common()->rss_page($data, $params);
		$actual = ob_get_clean();

		$expected = '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0"
 xmlns:dc="http://purl.org/dc/elements/1.1/"
 xmlns:atom="http://www.w3.org/2005/Atom"
>
	<channel>
		<title>Site feed title</title>
		<description><![CDATA[Site feed description]]></description>
		<link>./</link>
		<lastBuildDate></lastBuildDate>
		<item>
			<title></title>
			<link>./?object=unit_tests&amp;action=testme&amp;id=1</link>
			<description></description>
			<pubDate>Tue, 12 Aug 2014 12:03:19</pubDate>
			<guid isPermaLink="false">./?object=unit_tests&amp;action=testme&amp;id=1#1cbc7597c5e24bf5cc3f7e453ff34b03</guid>
		</item>
		<item>
			<title></title>
			<link>./?object=unit_tests&amp;action=testme&amp;id=2</link>
			<description></description>
			<pubDate>Tue, 12 Aug 2014 12:03:20</pubDate>
			<guid isPermaLink="false">./?object=unit_tests&amp;action=testme&amp;id=2#168aa275eb4cf49613c1ab23f8e9b88d</guid>
		</item>
	</channel>
</rss>';
		$actual = preg_replace('~<lastBuildDate>[^<]+</lastBuildDate>~ims', '<lastBuildDate></lastBuildDate>', $actual);
		$this->assertSame(trim($expected), trim($actual));
	}
}