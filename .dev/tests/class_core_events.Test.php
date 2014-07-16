<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_core_events_test extends PHPUnit_Framework_TestCase {
	public function test_basic() {
		$out = null;
		$this->assertEquals(false, events()->has_listeners('testme'));
		events()->listen('testme', function($in) use (&$out) { $out = $in; });
		$this->assertEquals(true, events()->has_listeners('testme'));
		$this->assertNull($out);
		events()->fire('testme', array('Hello'));
		$this->assertEquals('Hello', $out);
		events()->fire('testme', array(null));
		$this->assertNull($out);
		$this->assertEquals(true, events()->has_listeners('testme'));

		events()->forget('testme');
		$this->assertEquals(false, events()->has_listeners('testme'));
	}
	public function test_nested() {
		$out = null;
		$this->assertEquals(false, events()->has_listeners('testme'));
		$this->assertEquals(false, events()->has_listeners('testme_nested1'));
		events()->listen('testme', function($in) {
			events()->fire('testme_nested1', array($in));
		});
		events()->fire('testme', array('Hello'));
		$this->assertNull($out);
		events()->listen('testme_nested1', function($in2) use (&$out) {
			$out = $in2;
		});
		events()->fire('testme', array('Hello'));
		$this->assertEquals('Hello', $out);

		events()->forget('testme');
		$this->assertEquals(false, events()->has_listeners('testme'));
		events()->forget('testme_nested1');
		$this->assertEquals(false, events()->has_listeners('testme_nested1'));
	}
	public function test_nested2() {
		$out = null;
		$this->assertEquals(false, events()->has_listeners('testme'));
		$this->assertEquals(false, events()->has_listeners('testme_nested1'));
		events()->listen('testme', function($in) use (&$out) {
			events()->listen('testme_nested1', function($in2) use (&$out) {
				$out = $in2;
			});
			events()->fire('testme_nested1', array($in));
		});
		events()->fire('testme', array('Hello'));
		$this->assertEquals('Hello', $out);

		events()->forget('testme');
		$this->assertEquals(false, events()->has_listeners('testme'));
		events()->forget('testme_nested1');
		$this->assertEquals(false, events()->has_listeners('testme_nested1'));
	}
	public function test_nested3() {
		$out = null;
		$this->assertEquals(false, events()->has_listeners('testme'));
		$this->assertEquals(false, events()->has_listeners('testme_nested1'));
		$this->assertEquals(false, events()->has_listeners('testme_nested2'));
		events()->listen('testme', function($in) use (&$out) {
			events()->listen('testme_nested1', function($in2) use (&$out) {
				events()->listen('testme_nested2', function($in3) use (&$out) {
					$out = $in3;
				});
				events()->fire('testme_nested2', array($in2));
				events()->forget('testme_nested2');
			});
			events()->fire('testme_nested1', array($in));
			events()->forget('testme_nested1');
		});
		events()->fire('testme', array('Hello'));
		events()->forget('testme');
		$this->assertEquals('Hello', $out);

		$this->assertEquals(false, events()->has_listeners('testme'));
		$this->assertEquals(false, events()->has_listeners('testme_nested1'));
		$this->assertEquals(false, events()->has_listeners('testme_nested2'));
	}
	public function test_responses() {
		events()->listen('testme', function() { return '111'; });
		events()->listen('testme', function() { return '222'; });
		events()->listen('testme', function() { return '333'; });
		$result = events()->fire('testme', array('Hello'));
		events()->forget('testme');
		$this->assertEquals(array('111','222','333'), $result);
	}
	public function test_responses2() {
		events()->listen('testme', function($in) { return '1_'.$in.'_1'; });
		events()->listen('testme', function($in) { return '2_'.$in.'_2'; });
		events()->listen('testme', function($in) { return '3_'.$in.'_3'; });
		$result = events()->fire('testme', array('Hello'));
		events()->forget('testme');
		$this->assertEquals(array('1_Hello_1','2_Hello_2','3_Hello_3'), $result);
	}
}