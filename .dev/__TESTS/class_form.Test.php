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
	public function test_01() {
		$html = form();
		$this->assertEquals(  
'<form method="post" action="./?object=dynamic&action=unit_test_form" class="form-horizontal" name="form_action" autocomplete="1">
<fieldset>
</fieldset>
</form>', trim($html));
	}
	public function test_10() {
		$html = form()->text();
		$this->assertEquals(  
'<form method="post" action="./?object=dynamic&action=unit_test_form" class="form-horizontal" name="form_action" autocomplete="1">
<fieldset>
<div class="control-group form-group">
<div class="controls col-lg-4">
<input type="text" class="form-control">
</div>
</div>
</fieldset>
</form>', trim($html));
	}
	public function test_11() {
		$html = form()->text('name');
		$this->assertEquals(  
'<form method="post" action="./?object=dynamic&action=unit_test_form" class="form-horizontal" name="form_action" autocomplete="1">
<fieldset>
<div class="control-group form-group">
<label class="control-label col-lg-2" for="name">Name</label>
<div class="controls col-lg-4">
<input name="name" type="text" id="name" class="form-control" placeholder="Name">
</div>
</div>
</fieldset>
</form>', trim($html));
	}
	public function test_12() {
		$html = form('', array('no_form' => 1))->text('name');
		$this->assertEquals(  
'<div class="control-group form-group">
<label class="control-label col-lg-2" for="name">Name</label>
<div class="controls col-lg-4">
<input name="name" type="text" id="name" class="form-control" placeholder="Name">
</div>
</div>', trim($html));
	}
	public function test_13() {
		$html = form('', array('no_form' => 1))->text('name', array('stacked' => 1));
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim($html));
	}
	public function test_14() {
		$html = form('', array('no_form' => 1))->text('name', '', array('stacked' => 1));
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim($html));
	}
	public function test_15() {
		$html = form('', array('no_form' => 1))->text('name', array('stacked' => 1, 'desc' => 'Desc'));
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Desc">', trim($html));
	}
	public function test_16() {
		$r['name'] = 'value1';
		$html = form($r, array('no_form' => 1))->text('name', array('stacked' => 1, 'desc' => 'Desc'));
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Desc" value="value1">', trim($html));
	}
	public function test_17() {
		$r['name'] = 'value1';
		$html = form($r, array('no_form' => 1))->text('name', array('stacked' => 1, 'desc' => 'Desc', 'style' => 'color:red;'));
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" style="color:red;" placeholder="Desc" value="value1">', trim($html));
	}
	public function test_18() {
		$html = form($r, array('no_form' => 1))->text('name', array('stacked' => 1, 'desc' => 'Desc', 'style' => 'color:red;', 'value' => 'value1'));
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" style="color:red;" placeholder="Desc" value="value1">', trim($html));
	}
	public function test_19() {
		$html = form($r, array('no_form' => 1))->hidden('hdn');
		$this->assertEquals('<input type="hidden" id="hdn" name="hdn">', trim($html));
	}
	public function test_20() {
		$html = form($r, array('no_form' => 1))->hidden('hdn', array('value' => 'val1'));
		$this->assertEquals('<input type="hidden" id="hdn" name="hdn" value="val1">', trim($html));
	}
}