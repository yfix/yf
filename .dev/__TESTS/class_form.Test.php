<?php  

require dirname(__FILE__).'/yf_unit_tests_setup.php';

/* TODO:
* data-* attrs
* ng-* attrs
* extra merge with 1nd and 1st param
* tab_start()
* fieldset_start()
* row_start()
* array_to_form()
* auto()
* replace passing to form and directly to method
* _attrs()
* _htmlchars()
* chained_wrapper()
* clone (__clone)
* _dd_row_html()
* _input_assing_params_from_validate()
* input,textarea,number,etc
*/

class class_form_test extends PHPUnit_Framework_TestCase {
	public static function setUpBeforeClass() {
		$_GET['object'] = 'dynamic';
		$_GET['action'] = 'unit_test_form';
	}
	public static function tearDownAfterClass() {
	}
	private function form_no_chain($r = array()) {
		return form($r, array('no_form' => 1, 'only_content' => 1, 'no_chained_mode' => 1));
	}
	public function test_empty_form() {
		$this->assertEquals(  
'<form method="post" action="./?object=dynamic&action=unit_test_form" class="form-horizontal" name="form_action" autocomplete="1">
<fieldset>
</fieldset>
</form>', trim(form()) );
	}
	public function test_input_text() {
		$this->assertEquals(  
'<form method="post" action="./?object=dynamic&action=unit_test_form" class="form-horizontal" name="form_action" autocomplete="1">
<fieldset>
<div class="control-group form-group">
<div class="controls">
<input type="text" class="form-control">
</div>
</div>
</fieldset>
</form>', trim(form()->text()) );
		$this->assertEquals(  
'<form method="post" action="./?object=dynamic&action=unit_test_form" class="form-horizontal" name="form_action" autocomplete="1">
<fieldset>
<div class="control-group form-group">
<label class="control-label col-lg-4" for="name">Name</label>
<div class="controls col-lg-8">
<input name="name" type="text" id="name" class="form-control" placeholder="Name">
</div>
</div>
</fieldset>
</form>', trim(form()->text('name')) );
	}
	public function test_input_text_no_form() {
		$this->assertEquals(  
'<div class="control-group form-group">
<label class="control-label col-lg-4" for="name">Name</label>
<div class="controls col-lg-8">
<input name="name" type="text" id="name" class="form-control" placeholder="Name">
</div>
</div>', trim(form('', array('no_form' => 1))->text('name')) );
	}
	public function test_input_text_simple() {
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim(self::form_no_chain($r)->text('name')) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim(self::form_no_chain($r)->text('name', '')) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim(self::form_no_chain($r)->text('name', '', array('stacked' => 1))) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim(self::form_no_chain($r)->text('name', '', array('stacked' => 1))) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim(form('', array('no_form' => 1))->text('name', '', array('stacked' => 1))) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Desc">', trim(self::form_no_chain($r)->text('name', array('desc' => 'Desc'))) );
	}
	public function test_input_text_value() {
		$r['name'] = 'value1';
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Desc" value="value1">'
			, trim(self::form_no_chain($r)->text('name', array('desc' => 'Desc'))) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Desc" value="value1">'
			, trim(form($r, array('no_form' => 1))->text('name', array('stacked' => 1, 'desc' => 'Desc'))) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" style="color:red;" placeholder="Desc" value="value1">'
			, self::form_no_chain($r)->text('name', array('desc' => 'Desc', 'style' => 'color:red;')) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" style="color:red;" placeholder="Desc" value="value1">'
			, self::form_no_chain($r)->text('name', array('desc' => 'Desc', 'style' => 'color:red;', 'value' => 'value1')) );
	}
	public function test_input_textarea() {
		$this->assertEquals('<textarea id="name" name="name" placeholder="Name" contenteditable="true" class="ckeditor form-control"></textarea>', trim(self::form_no_chain($r)->textarea('name')) );
		$this->assertEquals('<textarea id="name" name="name" placeholder="Name" contenteditable="true" class="ckeditor form-control"></textarea>', trim(self::form_no_chain($r)->textarea('name', '')) );
		$this->assertEquals('<textarea id="name" name="name" placeholder="Desc" contenteditable="true" class="ckeditor form-control"></textarea>'
			, trim(self::form_no_chain($r)->textarea('name', '', array('desc' => 'Desc'))) );
		$this->assertEquals('<textarea id="name" name="name" placeholder="Desc" contenteditable="true" class="ckeditor form-control"></textarea>'
			, trim(self::form_no_chain($r)->textarea('name', array('desc' => 'Desc'))) );
	}
	public function test_input_hidden() {
		$this->assertEquals('<input type="hidden" id="hdn" name="hdn">', trim(self::form_no_chain($r)->hidden('hdn')) );
		$this->assertEquals('<input type="hidden" id="hdn" name="hdn" value="val1">', trim(self::form_no_chain($r)->hidden('hdn', array('value' => 'val1'))) );
	}
	public function test_container() {
		$this->assertEquals('<section id="test"></section>', trim(self::form_no_chain($r)->container('<section id="test"></section>')) );
	}
	public function test_check_box() {
		$this->assertEquals('<label class="checkbox"><input type="checkbox" name="id" id="id" value="1"> &nbsp;Id</label>'
			, trim(self::form_no_chain($r)->check_box('id')) );
		$this->assertEquals('<label class="checkbox"><input type="checkbox" name="id" id="id" value="1" checked="checked"> &nbsp;Id</label>'
			, trim(self::form_no_chain($r)->check_box('id', array('selected' => 'true'))) );
		$this->assertEquals('<label class="checkbox"><input type="checkbox" name="id" id="id" value="1" checked="checked"> &nbsp;Id</label>'
			, trim(self::form_no_chain($r)->check_box('id', '1', array('selected' => 'true'))) );
		$this->assertEquals('<label class="checkbox"><input type="checkbox" name="is_public" id="is_public" value="1" checked="checked"> &nbsp;Is public</label>'
			, trim(self::form_no_chain($r)->check_box('is_public', '1', array('selected' => 'true'))) );
		$this->assertEquals('<label class="checkbox"><input type="checkbox" name="is_public" id="is_public" value="1" checked="checked"> &nbsp;Is public</label>'
			, trim(self::form_no_chain($r)->check_box('is_public', '1', array('checked' => 'true'))) );
		$this->assertEquals('<label class="checkbox"><input type="checkbox" name="is_public" id="is_public" value="1" checked="checked"> &nbsp;Is public</label>'
			, trim(self::form_no_chain($r)->check_box('is_public', array('checked' => 'true'))) );
	}
	public function test_select_box() {
		$data = array(
			'k1' => 'v1',
			'k2' => 'v2',
		);
		$this->assertEquals('<select name="myselect" id="myselect_box" class=" form-control" >
<option value="k1" >v1</option>
<option value="k2" >v2</option>
</select>', trim(self::form_no_chain($r)->select_box('myselect', $data)) );
		$this->assertEquals('<select name="myselect" id="myselect_box" class=" form-control" ><option value="k1" >v1</option><option value="k2" >v2</option></select>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->select_box('myselect', $data))) );
	}
	public function test_select_box_subarray() {
		$data = array(
			'group1' => array('k1' => 'v1', 'k2' => 'v2'),
			'group2' => array('k3' => 'v3',	'k4' => 'v4'),
		);
		$this->assertEquals('<select name="myselect" id="myselect_box" class=" form-control" ><optgroup label="group1" title="group1"><option value="k1" >v1</option><option value="k2" >v2</option></optgroup><optgroup label="group2" title="group2"><option value="k3" >v3</option><option value="k4" >v4</option></optgroup></select>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->select_box('myselect', $data))) );

		$selected = 'k3';
		$this->assertEquals('<select name="myselect" id="myselect_box" class=" form-control" ><optgroup label="group1" title="group1"><option value="k1" >v1</option><option value="k2" >v2</option></optgroup><optgroup label="group2" title="group2"><option value="k3" selected="selected">v3</option><option value="k4" >v4</option></optgroup></select>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->select_box('myselect', $data, array('selected' => $selected)))) );
		$r['myselect'] = $selected;
		$this->assertEquals('<select name="myselect" id="myselect_box" class=" form-control" ><optgroup label="group1" title="group1"><option value="k1" >v1</option><option value="k2" >v2</option></optgroup><optgroup label="group2" title="group2"><option value="k3" selected="selected">v3</option><option value="k4" >v4</option></optgroup></select>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->select_box('myselect', $data))) );
	}
	public function test_multi_select_box() {
		$data = array(
			'k1' => 'v1',
			'k2' => 'v2',
		);
		$this->assertEquals('<select  multiple name="myselect[]" id="myselect_box" class=" form-control" ><option value="k1" >v1</option><option value="k2" >v2</option></select>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->multi_select_box('myselect', $data))) );
	}
}