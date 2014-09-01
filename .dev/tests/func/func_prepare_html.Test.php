<?php  

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class func_prepare_html_test extends PHPUnit_Framework_TestCase {
	public function test_prepare_html() {
		$this->assertEquals('test', _prepare_html('test'));
		$this->assertEquals('test'.PHP_EOL.'test', _prepare_html('test'.PHP_EOL.'test'));
		$this->assertEquals('&#123;', _prepare_html('{'));
		$this->assertEquals('&#125;', _prepare_html('}'));
		$this->assertEquals('&#92;', _prepare_html("\\\\"));
		$this->assertEquals('&#40;', _prepare_html('('));
		$this->assertEquals('&#41;', _prepare_html(')'));
		$this->assertEquals('&#63;', _prepare_html('?'));
		$this->assertEquals('&#039;', _prepare_html('\''));
		$this->assertEquals('&quot;', _prepare_html('"'));
		$this->assertEquals('&lt;', _prepare_html('<'));
		$this->assertEquals('&gt;', _prepare_html('>'));
		$this->assertEquals('&lt;script&gt;', _prepare_html('<script>'));

		$this->assertEquals('&lt;script type=&quot;text/javascript&quot;&gt;$&#40;function&#40;alert&#40;&#039;Hello&#039;&#41;&#41;&#41;&lt;/script&gt;', 
			_prepare_html('<script type="text/javascript">$(function(alert(\'Hello\')))</script>'));
		$this->assertEquals('&lt;a href=&quot;#&quot; onclick=&quot;return confirm&#40;&#039;Are you sure&#63;&#039;&#41;&quot;&gt;Link&lt;/a&gt;', 
			_prepare_html('<a href="#" onclick="return confirm(\'Are you sure?\')">Link</a>'));
		$this->assertEquals('&lt;a href=&quot;#&quot; onclick=&quot;return confirm&#40;&#039;&#123;i18n_text&#125;&#039;&#41;&quot;&gt;Link&lt;/a&gt;', 
			_prepare_html('<a href="#" onclick="return confirm(\'{i18n_text}\')">Link</a>'));

		$this->assertEquals(array(), _prepare_html(array()));
		$this->assertEquals(array('test'), _prepare_html(array('test')));
		$this->assertEquals(array('k1' => '&lt;', 'k2' => '&gt;'), _prepare_html(array('k1' => '<', 'k2' => '>')));
		$this->assertEquals(array('k1' => array(array('&lt;')), 'k2' => '&gt;'), _prepare_html(array('k1' => array(array('<')), 'k2' => '>')));

		$this->assertEquals('&gt;', _prepare_html('&gt;'));
		$this->assertEquals('&#039;', _prepare_html('&#039;'));

		$this->assertEquals('&#92;', _prepare_html("\\", $strip_slashes = false));
		$this->assertEquals('&amp;#039;', _prepare_html('&#039;', 1, $smart = false));
	}
}
