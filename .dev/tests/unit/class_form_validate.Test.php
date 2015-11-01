<?php

require_once __DIR__.'/yf_unit_tests_setup.php';

class class_form_validate_test extends yf_unit_tests {
	public static function tearDownAfterClass() {
		common()->USER_ERRORS = array();
	}
	function test_complex() {
		$this->assertEquals('', common()->_get_error_messages());

		$old = $_SERVER['REQUEST_METHOD'];
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$_POST['name'] = '';
		$params = array('do_not_remove_errors' => 1);

		form($a, $params)
			->text('name', array('validate' => 'required'))
			->validate()
			->render();
		$this->assertEquals(array('name' => 'The Name field is required.'), common()->_get_error_messages());

		common()->_show_error_message($msg = '', $clear = true);
		$this->assertEquals('', common()->_get_error_messages());

		$_POST['name'] = '';

		form($a, $params)
			->text('name')
			->validate($rules = array('name' => 'required'))
			->render();
		$this->assertEquals(array('name' => 'The Name field is required.'), common()->_get_error_messages());

		common()->_show_error_message($msg = '', $clear = true);
		$this->assertEquals('', common()->_get_error_messages());

		$_POST['name'] = '';

		form($a, $params)
			->text('name')
			->validate($rules = array('name' => 'trim'))
			->render();
		$this->assertEquals('', common()->_get_error_messages());

		$_POST['name'] = 'something';

		form($a, $params)
			->text('name')
			->validate($rules = array('name' => 'required'), $post = array('name' => ''))
			->render();
		$this->assertEquals(array('name' => 'The Name field is required.'), common()->_get_error_messages());

		common()->_show_error_message($msg = '', $clear = true);
		$this->assertEquals('', common()->_get_error_messages());

		$_POST['name1'] = 'val';
		$_POST['name2'] = 'val';

		form($a, $params)
			->text('name1')
			->text('name2')
			->validate($rules = array('name1' => 'trim', 'name2' => 'matches:name1'))
			->render();
		$this->assertEquals('', common()->_get_error_messages());

		$_POST['name1'] = 'val';
		$_POST['name2'] = 'other';

		form($a, $params)
			->text('name1')
			->text('name2')
			->validate($rules = array('name1' => 'trim', 'name2' => 'matches:name1'))
			->render();
		$this->assertEquals(array('name2' => 'The Name2 field does not match the Name1 field.'), common()->_get_error_messages());

		$_POST['name1'] = 'val';
		$_POST['name2'] = 'other';

		form($a, $params)
			->text('name1', 'Desc1')
			->text('name2', 'Desc2')
			->validate($rules = array('name1' => 'trim', 'name2' => 'matches:name1'))
			->render();
		$this->assertEquals(array('name2' => 'The Desc2 field does not match the Desc1 field.'), common()->_get_error_messages());

		common()->_show_error_message($msg = '', $clear = true);
		$this->assertEquals('', common()->_get_error_messages());

		$_SERVER['REQUEST_METHOD'] = $old;
	}
}