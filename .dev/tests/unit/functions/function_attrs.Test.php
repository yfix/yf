<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class function_attrs_test extends yf_unit_tests {
	public function test_negative() {
		$this->assertEquals('', @_attrs());
		$this->assertEquals('', @_attrs(null));
		$this->assertEquals('', @_attrs(''));
		$this->assertEquals('', @_attrs(array()));
	}
	public function test_simple() {
		$a = array(
			'id' => 'myid',
			'name' => 'myname',
			'value' => '"complex string \' with quotes"',
		);
		$this->assertEquals('', @_attrs($a));
		$this->assertEquals(' id="myid"', _attrs($a, array('id')));
		$this->assertEquals(' id="myid" name="myname"', _attrs($a, array('id','name')));
		$this->assertEquals(' value="&quot;complex string &apos; with quotes&quot;"', _attrs($a, array('value')));
		$this->assertEquals(' id="myid" name="myname" value="&quot;complex string &apos; with quotes&quot;"', _attrs($a, array('id','name','value')));
		$this->assertEquals(' value="&quot;complex string &apos; with quotes&quot;" id="myid" name="myname"', _attrs($a, array('value','id','name')));
		$this->assertEquals(' value="&quot;complex string &apos; with quotes&quot;" id="myid" name="myname"', _attrs($a, array('value','id','name','')));
	}
	public function test_auto_data() {
		$a = array(
			'data-unittest' => 'myval',
			'adata-unittest' => 'myval', // should not appear automatically
		);
		$this->assertEquals(' data-unittest="myval"', _attrs($a, array()));
		$this->assertEquals(' adata-unittest="myval" data-unittest="myval"', _attrs($a, array('adata-unittest')));
	}
	public function test_auto_ng() {
		$a = array(
			'ng-test' => 'myval',
			'ping-test' => 'myval', // should not appear automatically
		);
		$this->assertEquals(' ng-test="myval"', _attrs($a, array()));
		$this->assertEquals(' ping-test="myval" ng-test="myval"', _attrs($a, array('ping-test')));
	}
	public function test_array_attr() {
		$a = array(
			'name' => 'myname',
			'other' => 'attr',
			// This array will be used automatically like data-* and ng-*
			'attr' => array(
				'id' => 'myid',
				'' => 'myempty', // empty keys should be hidden
			),
		);
		$this->assertEquals(' id="myid"', _attrs($a, array()));
		$this->assertEquals(' id="myid"', _attrs($a, array('id')));
		$this->assertEquals(' name="myname" id="myid"', _attrs($a, array('name')));
		$this->assertEquals(' name="myname" id="myid"', _attrs($a, array('id','name')));
		$this->assertEquals(' data-unittest="testval" id="myid"', _attrs(array('data-unittest' => 'testval') + $a, array()));
		$this->assertEquals(' name="myname" data-unittest="testval" id="myid"', _attrs(array('data-unittest' => 'testval') + $a, array('id','name')));
	}
	public function test_override_attr() {
		$a = array(
			'id' => 'id1',
			'other' => 'attr',
			// This array will be used automatically like data-* and ng-*
			'attr' => array(
				'id' => 'myid',
				'' => 'myempty', // empty keys should be hidden
			),
		);
		$this->assertEquals(' id="myid"', _attrs($a, array()));
		$this->assertEquals(' id="myid"', _attrs($a, array('id')));
		$this->assertEquals(' id="myid"', _attrs($a, array('name')));
		$this->assertEquals(' id="myid"', _attrs($a, array('id','name')));
		$this->assertEquals(' data-unittest="testval" id="myid"', _attrs(array('data-unittest' => 'testval') + $a, array()));
		$this->assertEquals(' id="myid" data-unittest="testval"', _attrs(array('data-unittest' => 'testval') + $a, array('id','name')));
		$this->assertEquals(' id="myid" data-unittest="testval"', _attrs(array('data-unittest' => 'testval', 'id' => 'id_override') + $a, array('id')));
		$this->assertEquals(' id="id_override" data-unittest="testval"', _attrs(array('data-unittest' => 'testval', 'id' => 'id_override', 'attr' => array()) + $a, array('id')));
	}
	public function test_val_array() {
		$a = array(
			'key' => array(
				'k1' => 'v1',
				'k2' => 'v2',
			),
		);
		$this->assertEquals('', _attrs($a, array()));
		$this->assertEquals(' key="k1=v1&k2=v2"', _attrs($a, array('key')));
		$this->assertEquals(' name="test[]"', _attrs(array('name' => 'test[]'), array('name')));
	}
}