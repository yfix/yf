<?php  

require_once __DIR__.'/yf_unit_tests_setup.php';

class class_html_test extends PHPUnit_Framework_TestCase {
	public function test_select_box() {
		html()->_ids = array();

		$this->assertEmpty( html()->select_box('', array()) );

		$data = array(
			1 => 'red',
			2 => 'green'
		);

		html()->_ids = array();
		html()->AUTO_ASSIGN_IDS = false;

		$html = html()->select_box('', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select class="form-control"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->select_box('myselect', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect" class="form-control"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->select_box('myselect2', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect2" class="form-control"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);

		html()->_ids = array();
		html()->AUTO_ASSIGN_IDS = true;

		$html = html()->select_box('myselect', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect" id="select_box_1" class="form-control"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->select_box('myselect2', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect2" id="select_box_2" class="form-control"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->select_box(array('name' => 'myselect3', 'data-unittest' => 'testval'), $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3" id="select_box_3" class="form-control" data-unittest="testval"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->select_box(array(
			'name' => 'myselect3',
			'data-unittest' => 'testval',
			'values' => $data,
			'disabled' => 1
		));
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3" id="select_box_4" class="form-control" disabled="disabled" data-unittest="testval"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->select_box(array(
			'name' => 'myselect3',
			'data-unittest' => 'testval',
			'values' => $data,
			'disabled' => 1,
			'selected' => 2
		));
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3" id="select_box_5" class="form-control" disabled="disabled" data-unittest="testval"><option value="1">red</option><option value="2" selected="selected">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->select_box(array(
			'name' => 'myselect3',
			'data-unittest' => 'testval',
			'values' => $data,
			'disabled' => 1,
			'selected' => 2,
			'style' => 'color:red;',
			'class' => 'myclass',
			'add_str' => 'onclick="alert(\'Hello\')"',
			'show_text' => 1,
		));
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3" id="select_box_6" class="myclass form-control" style="color:red;" disabled="disabled" data-unittest="testval" onclick="alert(\'Hello\')">'.
			'<option value="">-select myselect3-</option><option value="1">red</option><option value="2" selected="selected">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->select_box(array(
			'name' => 'myselect3',
			'data-unittest' => 'testval',
			'values' => array('sub1' => $data),
			'disabled' => 1,
			'selected' => 2,
			'style' => 'color:red;',
			'class' => 'myclass',
			'add_str' => 'onclick="alert(\'Hello\')"',
			'show_text' => 1,
		));
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3" id="select_box_7" class="myclass form-control" style="color:red;" disabled="disabled" data-unittest="testval" onclick="alert(\'Hello\')">'.
			'<option value="">-select myselect3-</option><optgroup label="sub1" title="sub1"><option value="1">red</option><option value="2" selected="selected">green</option></optgroup></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
	}
	public function test_multi_select() {
		html()->_ids = array();

		$this->assertEmpty( html()->multi_select('', array()) );

		$data = array(
			1 => 'red',
			2 => 'green'
		);

		html()->_ids = array();
		html()->AUTO_ASSIGN_IDS = false;

		$html = html()->multi_select('', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select class="form-control" multiple="multiple"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->multi_select('myselect', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect[]" class="form-control" multiple="multiple"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->multi_select('myselect2', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect2[]" class="form-control" multiple="multiple"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);

		html()->_ids = array();
		html()->AUTO_ASSIGN_IDS = true;

		$html = html()->multi_select('myselect', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect[]" id="multi_select_1" class="form-control" multiple="multiple"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->multi_select('myselect2', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect2[]" id="multi_select_2" class="form-control" multiple="multiple"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->multi_select(array('name' => 'myselect3', 'data-unittest' => 'testval'), $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3[]" id="multi_select_3" class="form-control" multiple="multiple" data-unittest="testval"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->multi_select(array('name' => 'myselect3', 'data-unittest' => 'testval', 'values' => $data));
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3[]" id="multi_select_4" class="form-control" multiple="multiple" data-unittest="testval"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->multi_select(array('name' => 'myselect3', 'data-unittest' => 'testval', 'values' => $data, 'disabled' => 1));
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3[]" id="multi_select_5" class="form-control" multiple="multiple" disabled="disabled" data-unittest="testval"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->multi_select(array('name' => 'myselect3', 'data-unittest' => 'testval', 'values' => $data, 'disabled' => 1, 'selected' => 2));
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3[]" id="multi_select_6" class="form-control" multiple="multiple" disabled="disabled" data-unittest="testval"><option value="1">red</option><option value="2" selected="selected">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->multi_select(array('name' => 'myselect3', 'data-unittest' => 'testval', 'values' => $data, 'disabled' => 1, 'selected' => array(1 => 1, 2 => 2)));
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3[]" id="multi_select_7" class="form-control" multiple="multiple" disabled="disabled" data-unittest="testval"><option value="1" selected="selected">red</option><option value="2" selected="selected">green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
	}
}
