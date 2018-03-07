<?php

require_once __DIR__.'/yf_unit_tests_setup.php';

class class_form_validate_test extends yf\tests\wrapper {
	public static function setUpBeforeClass() {
		$GLOBALS['CONF']['form2']['CONF_CSRF_PROTECTION'] = false;
		_class('form2')->CONF_CSRF_PROTECTION = false;
	}
	public static function tearDownAfterClass() {
		common()->USER_ERRORS = [];
	}
	function test_complex() {
		$this->assertEquals('', common()->_get_error_messages());

		$old = $_SERVER['REQUEST_METHOD'];
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$form_id = md5(microtime());
		$_POST['__form_id__'] = $form_id;

		$_POST['name'] = '';
		$params = ['do_not_remove_errors' => 1, '__form_id__' => $form_id];

		form($a, $params)
			->text('name', ['validate' => 'required'])
			->validate()
			->render();
		$this->assertEquals(['name' => 'The Name field is required.'], common()->_get_error_messages());

		common()->_show_error_message($msg = '', $clear = true);
		$this->assertEquals('', common()->_get_error_messages());

		$_POST['name'] = '';

		form($a, $params)
			->text('name')
			->validate($rules = ['name' => 'required'])
			->render();
		$this->assertEquals(['name' => 'The Name field is required.'], common()->_get_error_messages());

		common()->_show_error_message($msg = '', $clear = true);
		$this->assertEquals('', common()->_get_error_messages());

		$_POST['name'] = '';

		form($a, $params)
			->text('name')
			->validate($rules = ['name' => 'trim'])
			->render();
		$this->assertEquals('', common()->_get_error_messages());

		$_POST['name'] = 'something';

		form($a, $params)
			->text('name')
			->validate($rules = ['name' => 'required'], $post = ['name' => '', '__form_id__' => $form_id])
			->render();
		$this->assertEquals(['name' => 'The Name field is required.'], common()->_get_error_messages());

		common()->_show_error_message($msg = '', $clear = true);
		$this->assertEquals('', common()->_get_error_messages());

		$_POST['name1'] = 'val';
		$_POST['name2'] = 'val';

		form($a, $params)
			->text('name1')
			->text('name2')
			->validate($rules = ['name1' => 'trim', 'name2' => 'matches:name1'])
			->render();
		$this->assertEquals('', common()->_get_error_messages());

		$_POST['name1'] = 'val';
		$_POST['name2'] = 'other';

		form($a, $params)
			->text('name1')
			->text('name2')
			->validate($rules = ['name1' => 'trim', 'name2' => 'matches:name1'])
			->render();
		$this->assertEquals(['name2' => 'The Name2 field does not match the Name1 field.'], common()->_get_error_messages());

		$_POST['name1'] = 'val';
		$_POST['name2'] = 'other';

		form($a, $params)
			->text('name1', 'Desc1')
			->text('name2', 'Desc2')
			->validate($rules = ['name1' => 'trim', 'name2' => 'matches:name1'])
			->render();
		$this->assertEquals(['name2' => 'The Desc2 field does not match the Desc1 field.'], common()->_get_error_messages());

		common()->_show_error_message($msg = '', $clear = true);
		$this->assertEquals('', common()->_get_error_messages());

		$_SERVER['REQUEST_METHOD'] = $old;
	}
}