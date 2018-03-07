<?php

require_once __DIR__.'/yf_unit_tests_setup.php';

class class_core_events_test extends yf\tests\wrapper {
	public function test_basic() {
		$out = null;
		$this->assertEquals(false, events()->has_listeners('testme'));
		events()->listen('testme', function($in) use (&$out) { $out = $in; });
		$this->assertEquals(true, events()->has_listeners('testme'));
		$this->assertNull($out);
		events()->fire('testme', ['Hello']);
		$this->assertEquals('Hello', $out);
		events()->fire('testme', [null]);
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
			events()->fire('testme_nested1', [$in]);
		});
		events()->fire('testme', ['Hello']);
		$this->assertNull($out);
		events()->listen('testme_nested1', function($in2) use (&$out) {
			$out = $in2;
		});
		events()->fire('testme', ['Hello']);
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
			events()->fire('testme_nested1', [$in]);
		});
		events()->fire('testme', ['Hello']);
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
				events()->fire('testme_nested2', [$in2]);
				events()->forget('testme_nested2');
			});
			events()->fire('testme_nested1', [$in]);
			events()->forget('testme_nested1');
		});
		events()->fire('testme', ['Hello']);
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
		$result = events()->fire('testme', ['Hello']);
		events()->forget('testme');
		$this->assertEquals(['111','222','333'], $result);
	}
	public function test_responses2() {
		events()->listen('testme', function($in) { return '1_'.$in.'_1'; });
		events()->listen('testme', function($in) { return '2_'.$in.'_2'; });
		events()->listen('testme', function($in) { return '3_'.$in.'_3'; });
		$result = events()->fire('testme', ['Hello']);
		events()->forget('testme');
		$this->assertEquals(['1_Hello_1','2_Hello_2','3_Hello_3'], $result);
	}
	public function test_wildcard() {
		events()->listen('testme.1', function() { return '111'; });
		events()->listen('testme.*', function() { return '222'; });
		events()->listen('testme.3', function() { return '333'; });
		$result = events()->fire('testme.1', ['Hello']);
		events()->forget('testme');
		$this->assertEquals(['111','222'], $result);
	}
	public function test_until() {
		events()->listen('testme', function($in) { });
		events()->listen('testme', function($in) { return ; });
		events()->listen('testme', function($in) { return null; });
		events()->listen('testme', function($in) { return '3_'.$in.'_3'; });
		events()->listen('testme', function($in) { return '4_'.$in.'_4'; }); // This should not be called
		$result = events()->until('testme', ['Hello']);
		events()->forget('testme');
		$this->assertEquals('3_Hello_3', $result);
	}
	public function test_firing() {
		events()->listen('testmefiring.*', function($in) {
			if (events()->firing() == 'testmefiring.3') {
				return '3_'.$in.'_3';
			}
		});
		$result = events()->fire('testmefiring.1', ['Hello']);
		$this->assertEquals([null], $result);
		$result = events()->fire('testmefiring.2', ['Hello']);
		$this->assertEquals([null], $result);
		$result = events()->fire('testmefiring.3', ['Hello']);
		$this->assertEquals(['3_Hello_3'], $result);
		events()->forget('testmefiring');
	}
}