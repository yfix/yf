<?php  

require_once __DIR__.'/yf_unit_tests_setup.php';

class class_html_test extends PHPUnit_Framework_TestCase {
	public function test_select_box() {
		$html = html();
		$html->_ids = array();

		$this->assertEmpty( $html->select_box('', array()) );

		$data = array(
			1 => 'red',
			2 => 'green'
		);

		$html->_ids = array();
		$html->AUTO_ASSIGN_IDS = false;

		$str = $html->select_box('', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select class="form-control"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->select_box('myselect', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect" class="form-control"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->select_box('myselect2', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect2" class="form-control"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);

		$html->_ids = array();
		$html->AUTO_ASSIGN_IDS = true;

		$str = $html->select_box('myselect', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect" id="select_box_1" class="form-control"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->select_box('myselect2', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect2" id="select_box_2" class="form-control"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->select_box(array('name' => 'myselect3', 'data-unittest' => 'testval'), $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3" id="select_box_3" class="form-control" data-unittest="testval"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->select_box(array(
			'name' => 'myselect3',
			'data-unittest' => 'testval',
			'values' => $data,
			'disabled' => 1
		));
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3" id="select_box_4" class="form-control" disabled="disabled" data-unittest="testval"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->select_box(array(
			'name' => 'myselect3',
			'data-unittest' => 'testval',
			'values' => $data,
			'disabled' => 1,
			'selected' => 2
		));
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3" id="select_box_5" class="form-control" disabled="disabled" data-unittest="testval"><option value="1">red</option><option value="2" selected="selected">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->select_box(array(
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
			'<option value="" class="opt-default">- Select myselect3 -</option><option value="1">red</option><option value="2" selected="selected">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->select_box(array(
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
			'<option value="" class="opt-default">- Select myselect3 -</option><optgroup label="sub1" title="sub1"><option value="1">red</option><option value="2" selected="selected">green</option></optgroup></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->select_box(array(
			'name' => 'myselect3',
			'values' => $data,
			'show_text' => 'my default text',
		));
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3" id="select_box_8" class="form-control"><option value="" class="opt-default">my default text</option><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
	}
	public function test_multi_select() {
		$html = html();
		$html->_ids = array();

		$this->assertEmpty( $html->multi_select('', array()) );

		$data = array(
			1 => 'red',
			2 => 'green'
		);

		$html->_ids = array();
		$html->AUTO_ASSIGN_IDS = false;

		$str = $html->multi_select('', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select class="form-control" multiple="multiple"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->multi_select('myselect', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect[]" class="form-control" multiple="multiple"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->multi_select('myselect2', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect2[]" class="form-control" multiple="multiple"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);

		$html->_ids = array();
		$html->AUTO_ASSIGN_IDS = true;

		$str = $html->multi_select('myselect', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect[]" id="multi_select_1" class="form-control" multiple="multiple"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->multi_select('myselect2', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect2[]" id="multi_select_2" class="form-control" multiple="multiple"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->multi_select(array('name' => 'myselect3', 'data-unittest' => 'testval'), $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3[]" id="multi_select_3" class="form-control" multiple="multiple" data-unittest="testval"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->multi_select(array('name' => 'myselect3', 'data-unittest' => 'testval', 'values' => $data));
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3[]" id="multi_select_4" class="form-control" multiple="multiple" data-unittest="testval"><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->multi_select(array('name' => 'myselect3', 'data-unittest' => 'testval', 'values' => $data, 'disabled' => 1));
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3[]" id="multi_select_5" class="form-control" multiple="multiple" disabled="disabled" data-unittest="testval"><option value="1">red</option>'.
			'<option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->multi_select(array('name' => 'myselect3', 'data-unittest' => 'testval', 'values' => $data, 'disabled' => 1, 'selected' => 2));
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3[]" id="multi_select_6" class="form-control" multiple="multiple" disabled="disabled" data-unittest="testval"><option value="1">red</option>'.
			'<option value="2" selected="selected">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->multi_select(array('name' => 'myselect3', 'data-unittest' => 'testval', 'values' => $data, 'disabled' => 1, 'selected' => array(1 => 1, 2 => 2)));
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3[]" id="multi_select_7" class="form-control" multiple="multiple" disabled="disabled" data-unittest="testval"><option value="1" selected="selected">red</option>'.
			'<option value="2" selected="selected">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->multi_select(array(
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
			'<select name="myselect3[]" id="multi_select_8" class="myclass form-control" style="color:red;" multiple="multiple" disabled="disabled" data-unittest="testval" onclick="alert(\'Hello\')">'.
			'<option value="" class="opt-default">- Select myselect3 -</option><optgroup label="sub1" title="sub1"><option value="1">red</option><option value="2" selected="selected">green</option></optgroup></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
		$str = $html->multi_select(array(
			'name' => 'myselect3',
			'values' => $data,
			'show_text' => 'my default text',
		));
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect3[]" id="multi_select_9" class="form-control" multiple="multiple"><option value="" class="opt-default">my default text</option><option value="1">red</option><option value="2">green</option></select>'
			), str_replace(PHP_EOL, '', trim($str))
		);
	}
	public function test_check_box() {
		$html = html();
		$def_class = $html->CLASS_LABEL_CHECKBOX.' '.$html->CLASS_LABEL_CHECKBOX_INLINE;

		$html->_ids = array();
		$html->AUTO_ASSIGN_IDS = false;

		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="checkbox" value="1"> &nbsp;Checkbox</label>', $html->check_box());
		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="checkbox" value="1"> &nbsp;Checkbox</label>', $html->check_box('', ''));
		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="test" value="1"> &nbsp;Test</label>', $html->check_box('test'));
		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="test" value="true"> &nbsp;Test</label>', $html->check_box('test', 'true'));
		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="checkbox" value="true"> &nbsp;Checkbox</label>', $html->check_box('', 'true'));
		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="test" value="1"> &nbsp;Test</label>', $html->check_box(array(
			'name' => 'test',
		)));
		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="test" value="true"> &nbsp;Test</label>', $html->check_box(array(
			'name' => 'test',
			'value' => 'true',
		)));
		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="test" id="myid" value="true"> &nbsp;Test</label>', $html->check_box(array(
			'name' => 'test',
			'value' => 'true',
			'id' => 'myid',
		)));
		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="test" id="myid" value="true"> &nbsp;</label>', $html->check_box(array(
			'name' => 'test',
			'value' => 'true',
			'id' => 'myid',
			'desc' => '',
		)));
		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="test" id="myid" value="true"> &nbsp;My desc</label>', $html->check_box(array(
			'name' => 'test',
			'value' => 'true',
			'id' => 'myid',
			'desc' => 'My desc',
		)));
		$this->assertEquals('<label><input type="checkbox" name="test" id="myid" value="true"> &nbsp;My desc</label>', $html->check_box(array(
			'name' => 'test',
			'value' => 'true',
			'id' => 'myid',
			'desc' => 'My desc',
			'class_label_checkbox' => '',
		)));
		$this->assertEquals('<label class="testme"><input type="checkbox" name="test" id="myid" value="true"> &nbsp;My desc</label>', $html->check_box(array(
			'name' => 'test',
			'value' => 'true',
			'id' => 'myid',
			'desc' => 'My desc',
			'class_label_checkbox' => 'testme',
		)));
		$this->assertEquals('<label class="testme"><input type="checkbox" name="test" id="myid" value="true"> &nbsp;My desc</label>', $html->check_box(array(
			'name' => 'test',
			'value' => 'true',
			'id' => 'myid',
			'desc' => 'My desc',
			'label_extra' => array('class' => 'testme'),
		)));
		$this->assertEquals('<label class="'.$def_class.' testme"><input type="checkbox" name="test" id="myid" value="true"> &nbsp;My desc</label>', $html->check_box(array(
			'name' => 'test',
			'value' => 'true',
			'id' => 'myid',
			'desc' => 'My desc',
			'class_add_label_checkbox' => 'testme',
		)));
		$this->assertEquals('<label class="'.$def_class.' active"><input type="checkbox" name="checkbox" value="1" checked="checked"> &nbsp;Checkbox</label>', $html->check_box(array(
			'selected' => true,
		)));
		$this->assertEquals('<label class="'.$def_class.' active"><input type="checkbox" name="checkbox" value="1" checked="checked"> &nbsp;Checkbox</label>', $html->check_box(array(
			'checked' => true,
		)));
		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="checkbox" value="1"> &nbsp;Checkbox</label>', $html->check_box(array(
			'selected' => false,
		)));
		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="checkbox" value="1"> &nbsp;Checkbox</label>', $html->check_box(array(
			'checked' => false,
		)));
		$this->assertEquals(
			'<label class="'.$def_class.' testme active"><input type="checkbox" name="test" id="myid" value="true" checked="checked"> &nbsp;My desc</label>'
			, $html->check_box(array(
			'name' => 'test',
			'value' => 'true',
			'id' => 'myid',
			'desc' => 'My desc',
			'class_add_label_checkbox' => 'testme',
			'selected' => true,
		)));
		$this->assertEquals(
			'<label class="'.$def_class.' testme active"><input type="checkbox" name="test" id="myid" value="true" checked="checked" style="color:red;"> &nbsp;My desc</label>'
			, $html->check_box(array(
			'name' => 'test',
			'value' => 'true',
			'id' => 'myid',
			'desc' => 'My desc',
			'class_add_label_checkbox' => 'testme',
			'selected' => true,
			'style' => 'color:red;',
		)));

		$html->_ids = array();
		$html->AUTO_ASSIGN_IDS = true;

		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="checkbox" id="check_box_1" value="1"> &nbsp;Checkbox</label>', $html->check_box());
		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="checkbox" id="check_box_2" value="1"> &nbsp;Checkbox</label>', $html->check_box('', ''));
		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="test" id="check_box_3" value="1"> &nbsp;Test</label>', $html->check_box('test'));
		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="test" id="check_box_4" value="true"> &nbsp;Test</label>', $html->check_box('test', 'true'));
		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="checkbox" id="check_box_5" value="true"> &nbsp;Checkbox</label>', $html->check_box('', 'true'));
		$this->assertEquals('<label class="'.$def_class.'"><input type="checkbox" name="test" id="check_box_6" value="1"> &nbsp;Test</label>', $html->check_box(array(
			'name' => 'test',
		)));
		$this->assertEquals(
			'<label class="'.$def_class.' testme active"><input type="checkbox" name="test" id="myid" value="true" checked="checked" style="color:red;"> &nbsp;My desc</label>'
			, $html->check_box(array(
			'name' => 'test',
			'value' => 'true',
			'id' => 'myid',
			'desc' => 'My desc',
			'class_add_label_checkbox' => 'testme',
			'selected' => true,
			'style' => 'color:red;',
		)));
	}
}
