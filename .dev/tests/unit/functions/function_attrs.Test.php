<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class function_attrs_test extends yf_unit_tests {
	public function test_negative() {
		$this->assertEquals('', @_attrs());
		$this->assertEquals('', @_attrs(null));
		$this->assertEquals('', @_attrs(''));
		$this->assertEquals('', @_attrs([]));
	}
	public function test_simple() {
		$a = [
			'id' => 'myid',
			'name' => 'myname',
			'value' => '"complex string \' with quotes"',
		];
		$this->assertEquals('', @_attrs($a));
		$this->assertEquals(' id="myid"', _attrs($a, ['id']));
		$this->assertEquals(' id="myid" name="myname"', _attrs($a, ['id','name']));
		$this->assertEquals(' value="&quot;complex string &apos; with quotes&quot;"', _attrs($a, ['value']));
		$this->assertEquals(' id="myid" name="myname" value="&quot;complex string &apos; with quotes&quot;"', _attrs($a, ['id','name','value']));
		$this->assertEquals(' value="&quot;complex string &apos; with quotes&quot;" id="myid" name="myname"', _attrs($a, ['value','id','name']));
		$this->assertEquals(' value="&quot;complex string &apos; with quotes&quot;" id="myid" name="myname"', _attrs($a, ['value','id','name','']));
	}
	public function test_auto_data() {
		$a = [
			'data-unittest' => 'myval',
			'adata-unittest' => 'myval', // should not appear automatically
		];
		$this->assertEquals(' data-unittest="myval"', _attrs($a, []));
		$this->assertEquals(' adata-unittest="myval" data-unittest="myval"', _attrs($a, ['adata-unittest']));
	}
	public function test_auto_ng() {
		$a = [
			'ng-test' => 'myval',
			'ping-test' => 'myval', // should not appear automatically
		];
		$this->assertEquals(' ng-test="myval"', _attrs($a, []));
		$this->assertEquals(' ping-test="myval" ng-test="myval"', _attrs($a, ['ping-test']));
	}
	public function test_array_attr() {
		$a = [
			'name' => 'myname',
			'other' => 'attr',
			// This array will be used automatically like data-* and ng-*
			'attr' => [
				'id' => 'myid',
				'' => 'myempty', // empty keys should be hidden
			],
		];
		$this->assertEquals(' id="myid"', _attrs($a, []));
		$this->assertEquals(' id="myid"', _attrs($a, ['id']));
		$this->assertEquals(' name="myname" id="myid"', _attrs($a, ['name']));
		$this->assertEquals(' name="myname" id="myid"', _attrs($a, ['id','name']));
		$this->assertEquals(' data-unittest="testval" id="myid"', _attrs(['data-unittest' => 'testval'] + $a, []));
		$this->assertEquals(' name="myname" data-unittest="testval" id="myid"', _attrs(['data-unittest' => 'testval'] + $a, ['id','name']));
	}
	public function test_override_attr() {
		$a = [
			'id' => 'id1',
			'other' => 'attr',
			// This array will be used automatically like data-* and ng-*
			'attr' => [
				'id' => 'myid',
				'' => 'myempty', // empty keys should be hidden
			],
		];
		$this->assertEquals(' id="myid"', _attrs($a, []));
		$this->assertEquals(' id="myid"', _attrs($a, ['id']));
		$this->assertEquals(' id="myid"', _attrs($a, ['name']));
		$this->assertEquals(' id="myid"', _attrs($a, ['id','name']));
		$this->assertEquals(' data-unittest="testval" id="myid"', _attrs(['data-unittest' => 'testval'] + $a, []));
		$this->assertEquals(' id="myid" data-unittest="testval"', _attrs(['data-unittest' => 'testval'] + $a, ['id','name']));
		$this->assertEquals(' id="myid" data-unittest="testval"', _attrs(['data-unittest' => 'testval', 'id' => 'id_override'] + $a, ['id']));
		$this->assertEquals(' id="id_override" data-unittest="testval"', _attrs(['data-unittest' => 'testval', 'id' => 'id_override', 'attr' => []] + $a, ['id']));
	}
	public function test_val_array() {
		$a = [
			'key' => [
				'k1' => 'v1',
				'k2' => 'v2',
			],
		];
		$this->assertEquals('', _attrs($a, []));
		$this->assertEquals(' key="k1=v1&k2=v2"', _attrs($a, ['key']));
		$this->assertEquals(' name="test[]"', _attrs(['name' => 'test[]'], ['name']));
	}
	public function test_id() {
		$this->assertEquals(' id="myid"', _attrs(['id' => 'myid'], ['id']));
		$this->assertEquals(' id="my-id"', _attrs(['id' => 'my-id'], ['id']));
		$this->assertEquals(' id="my_id"', _attrs(['id' => 'my_id'], ['id']));
		$this->assertEquals(' id="my-id"', _attrs(['id' => 'my id'], ['id']));
		$this->assertEquals(' id="my-id"', _attrs(['id' => ' my id '], ['id']));
		$this->assertEquals(' id="my-id"', _attrs(['id' => ' My Id '], ['id']));
		$this->assertEquals(' id="my-id"', _attrs(['id' => ' MY ID '], ['id']));
		$this->assertEquals(' id="my-id"', _attrs(['id' => '   MY'."\t".'ID   '], ['id']));
		$this->assertEquals(' id="voyti"', _attrs(['id' => 'войти'], ['id']));
		$this->assertEquals(' id="voyti"', _attrs(['id' => ' войти '], ['id']));
		$this->assertEquals(' id="voyti"', _attrs(['id' => ' Войти '], ['id']));
		$this->assertEquals(' id="voyti"', _attrs(['id' => ' ВОЙТИ '], ['id']));
		$this->assertEquals(' id="voyti-voyti"', _attrs(['id' => ' ВОЙТИ ВОЙТИ '], ['id']));
		$this->assertEquals(' id="complex-string-with-quotes"', _attrs(['id' => '"complex string \' with quotes"'], ['id']));
		$this->assertEquals(' id="complex_string_with_quotes"', _attrs(['id' => '"_complex_string_\'_with_quotes_"'], ['id']));
	}
}