<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class function_common_rss_page extends PHPUnit_Framework_TestCase {
	public static function setUpBeforeClass() {
	}
	public static function tearDownAfterClass() {
		_class('dir')->delete_dir(STORAGE_PATH.'uploads/', $delete_start_dir = true);
	}
	public function test_simple() {
		$data = array();
		$params = array('return_feed_text' => 1, 'use_cached' => 0, 'feed_url' => 'http://unit.dev/', 'feed_source' => 'http://unit.dev/', 'feed_desc' => 'my desc', 'feed_title' => 'my title');
		$actual = common()->rss_page($data, $params);

		$expected = '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0"
 xmlns:dc="http://purl.org/dc/elements/1.1/"
 xmlns:atom="http://www.w3.org/2005/Atom"
>
	<channel>
		<title>my title</title>
		<description><![CDATA[my desc]]></description>
		<link>http://unit.dev/</link>
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
				'link'	=> 'http://unit.dev/?object=unit_tests&action=testme&id=1',
			),
			array(
				'date'	=> strtotime('2014-08-12 12:03:20 UTC'),
				'link'	=> 'http://unit.dev/?object=unit_tests&action=testme&id=2',
			),
		);
		$params = array('return_feed_text' => 1, 'use_cached' => 0, 'feed_url' => 'http://unit.dev/', 'feed_source' => 'http://unit.dev/', 'feed_desc' => 'my desc', 'feed_title' => 'my title');
		$actual = common()->rss_page($data, $params);

		$expected = '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0"
 xmlns:dc="http://purl.org/dc/elements/1.1/"
 xmlns:atom="http://www.w3.org/2005/Atom"
>
	<channel>
		<title>my title</title>
		<description><![CDATA[my desc]]></description>
		<link>http://unit.dev/</link>
		<lastBuildDate></lastBuildDate>
		<item>
			<title></title>
			<link>http://unit.dev/?object=unit_tests&amp;action=testme&amp;id=1</link>
			<description></description>
			<pubDate>Tue, 12 Aug 2014 12:03:19</pubDate>
			<guid isPermaLink="false">http://unit.dev/?object=unit_tests&amp;action=testme&amp;id=1#da98a1f3acd5e77d15336ec3dc3d1bac</guid>
		</item>
		<item>
			<title></title>
			<link>http://unit.dev/?object=unit_tests&amp;action=testme&amp;id=2</link>
			<description></description>
			<pubDate>Tue, 12 Aug 2014 12:03:20</pubDate>
			<guid isPermaLink="false">http://unit.dev/?object=unit_tests&amp;action=testme&amp;id=2#403b486e7f8842b7c1e549d5cfaac5ca</guid>
		</item>
	</channel>
</rss>';
		$actual = preg_replace('~<lastBuildDate>[^<]+</lastBuildDate>~ims', '<lastBuildDate></lastBuildDate>', $actual);
		$this->assertSame(trim($expected), trim($actual));
	}
	public function test_with_data_full() {
		$data = array(
			array(
				'date'	=> strtotime('2014-08-12 12:03:19 UTC'),
				'link'	=> 'http://unit.dev/?object=unit_tests&action=testme&id=1',
				'author' => 'user 1',
				'title' => 'test 1',
				'description' => 'my description 1',
				'enclosure' => array(
					'url' => 'http://lh3.ggpht.com/smoliarov/Rwygj8ucrbE/AAAAAAAABIA/UkNlwQ7eniw/_200708.jpg',
					'length'=>'65036',
					'type'=>'image/jpeg',
				),
			),
			array(
				'date'	=> strtotime('2014-08-12 12:03:20 UTC'),
				'link'	=> 'http://unit.dev/?object=unit_tests&action=testme&id=2',
				'author' => 'user 2',
				'title' => 'test 2',
				'description' => 'my description 2',
				'enclosure' => array(
					'url' => 'http://lh3.ggpht.com/smoliarov/Rwygj8ucrbE/AAAAAAAABIA/UkNlwQ7eniw/_200709.jpg',
					'length'=>'65036',
					'type'=>'image/jpeg',
				),
			),
		);
		$params = array('return_feed_text' => 1, 'use_cached' => 0, 'feed_url' => 'http://unit.dev/', 'feed_source' => 'http://unit.dev/', 'feed_desc' => 'my desc', 'feed_title' => 'my title');
		$actual = common()->rss_page($data, $params);

		$expected = '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0"
 xmlns:dc="http://purl.org/dc/elements/1.1/"
 xmlns:atom="http://www.w3.org/2005/Atom"
>
	<channel>
		<title>my title</title>
		<description><![CDATA[my desc]]></description>
		<link>http://unit.dev/</link>
		<lastBuildDate></lastBuildDate>
		<item>
			<title>test 1</title>
			<link>http://unit.dev/?object=unit_tests&amp;action=testme&amp;id=1</link>
			<description><![CDATA[my description 1]]></description>
			<dc:creator>user 1</dc:creator>
			<pubDate>Tue, 12 Aug 2014 12:03:19</pubDate>
			<guid isPermaLink="false">http://unit.dev/?object=unit_tests&amp;action=testme&amp;id=1#ba27fd1444783d73c3ced7ac3580ab60</guid>
			<enclosure url="http://lh3.ggpht.com/smoliarov/Rwygj8ucrbE/AAAAAAAABIA/UkNlwQ7eniw/_200708.jpg" length="65036" type="image/jpeg"/>
		</item>
		<item>
			<title>test 2</title>
			<link>http://unit.dev/?object=unit_tests&amp;action=testme&amp;id=2</link>
			<description><![CDATA[my description 2]]></description>
			<dc:creator>user 2</dc:creator>
			<pubDate>Tue, 12 Aug 2014 12:03:20</pubDate>
			<guid isPermaLink="false">http://unit.dev/?object=unit_tests&amp;action=testme&amp;id=2#4b2a78001ba5b3ef24031afc122c6aa7</guid>
			<enclosure url="http://lh3.ggpht.com/smoliarov/Rwygj8ucrbE/AAAAAAAABIA/UkNlwQ7eniw/_200709.jpg" length="65036" type="image/jpeg"/>
		</item>
	</channel>
</rss>';
		$actual = preg_replace('~<lastBuildDate>[^<]+</lastBuildDate>~ims', '<lastBuildDate></lastBuildDate>', $actual);
		$this->assertSame(trim($expected), trim($actual));
	}
}