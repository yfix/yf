<?php  

require dirname(__FILE__).'/yf_unit_tests_setup.php';

/* TODO:
* tab_start()
* fieldset_start()
* row_start()
* _attrs()
* _htmlchars()
* clone (__clone)
* _dd_row_html()
* _input_assing_params_from_validate()
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
	public function test_form_from_array() {
		$a = array(array('text','name'));
		$this->assertEquals('<form method="post" action="./?object=dynamic&action=unit_test_form" class="form-horizontal" name="form_action" autocomplete="1"><fieldset><div class="control-group form-group"><label class="control-label col-lg-4" for="name">Name</label><div class="controls col-lg-8"><input name="name" type="text" id="name" class="form-control" placeholder="Name"></div></div></fieldset></form>'
			, str_replace(PHP_EOL, '', trim(form()->array_to_form($a))) );
	}
	public function test_form_auto() {
		$data = array('user' => 'name', 'email' => 'some@email.com');
		$this->assertEquals('<form method="post" action="./?object=dynamic&action=unit_test_form" class="form-horizontal" name="form_action" autocomplete="1"><fieldset><div class="control-group form-group"><div class="controls"><button type="submit" name="back_link" id="back_link" class="btn btn-default btn-primary" value="Save"><i class="icon-save"></i> Save</button></div></div></fieldset></form>'
			, str_replace(PHP_EOL, '', trim(form($data)->auto())) );
	}
	public function test_input_text_simple() {
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim(form_item($r)->text('name')) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim(self::form_no_chain($r)->text('name')) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim(self::form_no_chain($r)->text('name', '')) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim(self::form_no_chain($r)->text('name', '', array('stacked' => 1))) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim(self::form_no_chain($r)->text('name', '', array('stacked' => 1))) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim(form('', array('no_form' => 1))->text('name', '', array('stacked' => 1))) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Desc">', trim(self::form_no_chain($r)->text('name', array('desc' => 'Desc'))) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Desc">', trim(self::form_no_chain($r)->text('name', 'Desc')) );
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
	public function test_input_text_attr_data() {
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name" data-something="5">'
			, trim(self::form_no_chain($r)->text('name', array('data-something' => '5'))) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name" data-a1="a11" data-b1="b11">'
			, trim(self::form_no_chain($r)->text('name', array('data-a1' => 'a11', 'data-b1' => 'b11'))) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name" data-test-escape="!@#$%^&*(&quot;&apos;&lt;&gt;?&gt;&lt;:;">'
			, trim(self::form_no_chain($r)->text('name', array('data-test-escape' => '!@#$%^&*("\'<>?><:;'))) );
	}
	public function test_input_text_attr_ng() {
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name" ng-something="5">'
			, trim(self::form_no_chain($r)->text('name', array('ng-something' => '5'))) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name" ng-a1="a11" ng-b1="b11">'
			, trim(self::form_no_chain($r)->text('name', array('ng-a1' => 'a11', 'ng-b1' => 'b11'))) );
		$this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name" ng-test-escape="!@#$%^&*(&quot;&apos;&lt;&gt;?&gt;&lt;:;">'
			, trim(self::form_no_chain($r)->text('name', array('ng-test-escape' => '!@#$%^&*("\'<>?><:;'))) );
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
		$this->assertEquals('<form method="post" action="./?object=dynamic&action=unit_test_form" class="form-horizontal" name="form_action" autocomplete="1"><fieldset><div class="control-group form-group"><div class="controls"><section id="test"></section></div></div></fieldset></form>'
			, str_replace(PHP_EOL, '', trim(form()->container('<section id="test"></section>'))) );
		$this->assertEquals('<section id="test"></section>', trim(self::form_no_chain($r)->container('<section id="test"></section>')) );
	}
	public function test_select_box() {
		$data = array('k1' => 'v1',	'k2' => 'v2');
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
		$selected = 'k3';
		$this->assertEquals('<select name="myselect" id="myselect_box" class=" form-control" ><optgroup label="group1" title="group1"><option value="k1" >v1</option><option value="k2" >v2</option></optgroup><optgroup label="group2" title="group2"><option value="k3" >v3</option><option value="k4" >v4</option></optgroup></select>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->select_box('myselect', $data))) );
		$this->assertEquals('<select name="myselect" id="myselect_box" class=" form-control" ><optgroup label="group1" title="group1"><option value="k1" >v1</option><option value="k2" >v2</option></optgroup><optgroup label="group2" title="group2"><option value="k3" selected="selected">v3</option><option value="k4" >v4</option></optgroup></select>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->select_box('myselect', $data, array('selected' => $selected)))) );
		$r['myselect'] = $selected;
		$this->assertEquals('<select name="myselect" id="myselect_box" class=" form-control" ><optgroup label="group1" title="group1"><option value="k1" >v1</option><option value="k2" >v2</option></optgroup><optgroup label="group2" title="group2"><option value="k3" selected="selected">v3</option><option value="k4" >v4</option></optgroup></select>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->select_box('myselect', $data))) );
	}
	public function test_multi_select_box() {
		$data = array('k1' => 'v1', 'k2' => 'v2', 'k3' => 'v2');
		$selected = array('k2' => '1', 'k3' => '1');
		$this->assertEquals('<select  multiple name="myselect[]" id="myselect_box" class=" form-control" ><option value="k1" >v1</option><option value="k2" >v2</option><option value="k3" >v2</option></select>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->multi_select_box('myselect', $data))) );
		$this->assertEquals('<select  multiple name="myselect[]" id="myselect_box" class=" form-control" ><option value="k1" >v1</option><option value="k2" selected="selected">v2</option><option value="k3" selected="selected">v2</option></select>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->multi_select_box('myselect', $data, array('selected' => $selected)))) );
		$r['myselect'] = $selected;
		$this->assertEquals('<select  multiple name="myselect[]" id="myselect_box" class=" form-control" ><option value="k1" >v1</option><option value="k2" selected="selected">v2</option><option value="k3" selected="selected">v2</option></select>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->multi_select_box('myselect', $data))) );
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
	public function test_multi_check_box() {
		$data = array('k1' => 'v1', 'k2' => 'v2');
		$selected = array('k2' => '1');
		$this->assertEquals('<label class="checkbox">	<input type="checkbox" name="mycheck_k1" value="k1"  >	v1 &nbsp;</label><label class="checkbox">	<input type="checkbox" name="mycheck_k2" value="k2"  >	v2 &nbsp;</label>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->multi_check_box('mycheck', $data))) );
		$this->assertEquals('<label class="checkbox">	<input type="checkbox" name="mycheck_k1" value="k1"  >	v1 &nbsp;</label><label class="checkbox">	<input type="checkbox" name="mycheck_k2" value="k2" checked >	v2 &nbsp;</label>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->multi_check_box('mycheck', $data, array('selected' => $selected)))) );
	}
	public function test_radio_box() {
		$data = array('k1' => 'v1', 'k2' => 'v2');
		$selected = 'k2';
		$this->assertEquals('<label class="radio">	<input type="radio" name="myradio" value="k1"  >	v1&nbsp;</label><label class="radio">	<input type="radio" name="myradio" value="k2"  >	v2&nbsp;</label>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->radio_box('myradio', $data))) );
		$this->assertEquals('<label class="radio">	<input type="radio" name="myradio" value="k1"  >	v1&nbsp;</label><label class="radio">	<input type="radio" name="myradio" value="k2"  checked="true">	v2&nbsp;</label>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->radio_box('myradio', $data, array('selected' => $selected)))) );
	}
	public function test_div_box() {
		$data = array('k1' => 'v1', 'k2' => 'v2');
		$selected = 'k2';
		$this->assertEquals('<li class="dropdown" style="list-style-type:none;"><a class="dropdown-toggle" data-toggle="dropdown">Mydiv&nbsp;<span class="caret"></span></a><ul class="dropdown-menu"><li class="dropdown"><a data-value="k1" >v1</a></li><li class="dropdown"><a data-value="k2" >v2</a></li></ul></li>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->div_box('mydiv', $data))) );
		$this->assertEquals('<li class="dropdown" style="list-style-type:none;"><a class="dropdown-toggle" data-toggle="dropdown">v2&nbsp;<span class="caret"></span></a><ul class="dropdown-menu"><li class="dropdown"><a data-value="k1" >v1</a></li><li class="dropdown"><a data-value="k2" data-selected="selected">v2</a></li></ul></li>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->div_box('mydiv', $data, array('selected' => $selected)))) );
	}
	public function test_list_box() {
		$data = array('k1' => 'v1', 'k2' => 'v2');
		$selected = 'k2';
		$this->assertEquals('<div class="bfh-selectbox"><input type="hidden" name="mylist" value=""><a class="bfh-selectbox-toggle" role="button" data-toggle="bfh-selectbox" href="#"><span class="bfh-selectbox-option bfh-selectbox-medium" data-option=""></span><b class="caret"></b></a><div class="bfh-selectbox-options"><input type="text" class="bfh-selectbox-filter"><div role="listbox"><ul role="option"><li><a tabindex="-1" href="#" data-option="k1">v1</a></li><li><a tabindex="-1" href="#" data-option="k2">v2</a></li></ul></div></div></div>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->list_box('mylist', $data))) );
		$this->assertEquals('<div class="bfh-selectbox"><input type="hidden" name="mylist" value="k2"><a class="bfh-selectbox-toggle" role="button" data-toggle="bfh-selectbox" href="#"><span class="bfh-selectbox-option bfh-selectbox-medium" data-option="k2">v2</span><b class="caret"></b></a><div class="bfh-selectbox-options"><input type="text" class="bfh-selectbox-filter"><div role="listbox"><ul role="option"><li><a tabindex="-1" href="#" data-option="k1">v1</a></li><li><a tabindex="-1" href="#" data-option="k2">v2</a></li></ul></div></div></div>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->list_box('mylist', $data, array('selected' => $selected)))) );
	}
	public function test_fieldset_start() {
		$this->assertEquals('<fieldset name="f1">', trim(self::form_no_chain($r)->fieldset_start('f1')) );
	}
	public function test_input() {
		$this->assertEquals('<input name="test" type="text" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->input('test')) );
	}
	public function test_password() {
		$this->assertEquals('<input name="test" type="password" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->password('test')) );
	}
	public function test_file() {
		$this->assertEquals('<input name="test" type="file" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->file('test')) );
	}
	public function test_button() {
		$this->assertEquals('<input name="test" type="button" id="test" class="form-control btn btn-default" placeholder="Test" value="Test">', trim(self::form_no_chain($r)->button('test')) );
	}
	public function test_login() {
		$this->assertEquals('<input name="login" type="text" id="login" class="form-control" placeholder="Login">', trim(self::form_no_chain($r)->login()) );
		$this->assertEquals('<input name="test" type="text" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->login('test')) );
	}
	public function test_email() {
		$this->assertEquals('<input name="email" type="email" id="email" class="form-control" placeholder="Email">', trim(self::form_no_chain($r)->email()) );
		$this->assertEquals('<input name="test" type="email" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->email('test')) );
	}
	public function test_number() {
		$this->assertEquals('<input name="test" type="number" id="test" class="form-control input-small" placeholder="Test" maxlength="10">', trim(self::form_no_chain($r)->number('test')) );
	}
	public function test_integer() {
		$this->assertEquals('<input name="test" type="number" id="test" class="form-control input-small" placeholder="Test" maxlength="10">', trim(self::form_no_chain($r)->integer('test')) );
	}
	public function test_money() {
		$this->assertEquals('<input name="test" type="text" id="test" class="form-control input-small" placeholder="Test" maxlength="8">', trim(self::form_no_chain($r)->money('test')) );
	}
	public function test_url() {
		$this->assertEquals('<input name="url" type="url" id="url" class="form-control" placeholder="Url">', trim(self::form_no_chain($r)->url()) );
		$this->assertEquals('<input name="test" type="url" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->url('test')) );
	}
	public function test_color() {
		$this->assertEquals('<input name="color" type="color" id="color" class="form-control" placeholder="Color">', trim(self::form_no_chain($r)->color()) );
		$this->assertEquals('<input name="test" type="color" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->color('test')) );
	}
	public function test_date() {
		$this->assertEquals('<input name="date" type="date" id="date" class="form-control" placeholder="Date">', trim(self::form_no_chain($r)->date()) );
		$this->assertEquals('<input name="test" type="date" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->date('test')) );
	}
	public function test_datetime() {
		$this->assertEquals('<input name="datetime" type="datetime" id="datetime" class="form-control" placeholder="Datetime">', trim(self::form_no_chain($r)->datetime()) );
		$this->assertEquals('<input name="test" type="datetime" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->datetime('test')) );
	}
	public function test_datetime_local() {
		$this->assertEquals('<input name="datetime_local" type="datetime-local" id="datetime_local" class="form-control" placeholder="Datetime local">', trim(self::form_no_chain($r)->datetime_local()) );
		$this->assertEquals('<input name="test" type="datetime-local" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->datetime_local('test')) );
	}
	public function test_month() {
		$this->assertEquals('<input name="month" type="month" id="month" class="form-control" placeholder="Month">', trim(self::form_no_chain($r)->month()) );
		$this->assertEquals('<input name="test" type="month" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->month('test')) );
	}
	public function test_range() {
		$this->assertEquals('<input name="range" type="range" id="range" class="form-control" placeholder="Range">', trim(self::form_no_chain($r)->range()) );
		$this->assertEquals('<input name="test" type="range" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->range('test')) );
	}
	public function test_search() {
		$this->assertEquals('<input name="search" type="search" id="search" class="form-control" placeholder="Search">', trim(self::form_no_chain($r)->search()) );
		$this->assertEquals('<input name="test" type="search" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->search('test')) );
	}
	public function test_tel() {
		$this->assertEquals('<input name="tel" type="tel" id="tel" class="form-control" placeholder="Tel">', trim(self::form_no_chain($r)->tel()) );
		$this->assertEquals('<input name="test" type="tel" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->tel('test')) );
	}
	public function test_phone() {
		$this->assertEquals('<input name="phone" type="tel" id="phone" class="form-control" placeholder="Phone">', trim(self::form_no_chain($r)->phone()) );
		$this->assertEquals('<input name="test" type="tel" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->phone('test')) );
	}
	public function test_time() {
		$this->assertEquals('<input name="time" type="time" id="time" class="form-control" placeholder="Time">', trim(self::form_no_chain($r)->time()) );
		$this->assertEquals('<input name="test" type="time" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->time('test')) );
	}
	public function test_week() {
		$this->assertEquals('<input name="week" type="week" id="week" class="form-control" placeholder="Week">', trim(self::form_no_chain($r)->week()) );
		$this->assertEquals('<input name="test" type="week" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->week('test')) );
	}
	public function test_active_box() {
		$this->assertEquals('<label class="radio radio-horizontal">	<input type="radio" name="active" value="0"  >	<span class="btn btn-default btn-mini btn-xs btn-warning"><i class="icon-ban-circle"></i> Disabled</span>&nbsp;</label><label class="radio radio-horizontal">	<input type="radio" name="active" value="1"  >	<span class="btn btn-default btn-mini btn-xs btn-success"><i class="icon-ok"></i> Active</span>&nbsp;</label>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->active_box())) );
		$this->assertEquals('<label class="radio radio-horizontal">	<input type="radio" name="test" value="0"  >	<span class="btn btn-default btn-mini btn-xs btn-warning"><i class="icon-ban-circle"></i> Disabled</span>&nbsp;</label><label class="radio radio-horizontal">	<input type="radio" name="test" value="1"  >	<span class="btn btn-default btn-mini btn-xs btn-success"><i class="icon-ok"></i> Active</span>&nbsp;</label>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->active_box('test'))) );
	}
	public function test_allow_deny_box() {
		$this->assertEquals('<label class="radio radio-horizontal">	<input type="radio" name="active" value="DENY"  >	<span class="btn btn-default btn-mini btn-xs btn-warning"><i class="icon-ban-circle"></i> Deny</span>&nbsp;</label><label class="radio radio-horizontal">	<input type="radio" name="active" value="ALLOW"  >	<span class="btn btn-default btn-mini btn-xs btn-success"><i class="icon-ok"></i> Allow</span>&nbsp;</label>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->allow_deny_box())) );
		$this->assertEquals('<label class="radio radio-horizontal">	<input type="radio" name="test" value="DENY"  >	<span class="btn btn-default btn-mini btn-xs btn-warning"><i class="icon-ban-circle"></i> Deny</span>&nbsp;</label><label class="radio radio-horizontal">	<input type="radio" name="test" value="ALLOW"  >	<span class="btn btn-default btn-mini btn-xs btn-success"><i class="icon-ok"></i> Allow</span>&nbsp;</label>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->allow_deny_box('test'))) );
	}
	public function test_yes_no_box() {
		$this->assertEquals('<label class="radio radio-horizontal">	<input type="radio" name="active" value="0"  >	<span class="btn btn-default btn-mini btn-xs btn-warning"><i class="icon-ban-circle"></i> No</span>&nbsp;</label><label class="radio radio-horizontal">	<input type="radio" name="active" value="1"  >	<span class="btn btn-default btn-mini btn-xs btn-success"><i class="icon-ok"></i> Yes</span>&nbsp;</label>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->yes_no_box())) );
		$this->assertEquals('<label class="radio radio-horizontal">	<input type="radio" name="test" value="0"  >	<span class="btn btn-default btn-mini btn-xs btn-warning"><i class="icon-ban-circle"></i> No</span>&nbsp;</label><label class="radio radio-horizontal">	<input type="radio" name="test" value="1"  >	<span class="btn btn-default btn-mini btn-xs btn-success"><i class="icon-ok"></i> Yes</span>&nbsp;</label>'
			, str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->yes_no_box('test'))) );
	}
	public function test_submit() {
		$this->assertEquals('<button type="submit" id="save" class="btn btn-default btn-primary" value="Save">Save</button>', trim(self::form_no_chain($r)->submit()) );
		$this->assertEquals('<button type="submit" name="test" id="test" class="btn btn-default btn-primary" value="Save">Save</button>', trim(self::form_no_chain($r)->submit('test')) );
	}
	public function test_save() {
		$this->assertEquals('<button type="submit" id="save" class="btn btn-default btn-primary" value="Save"><i class="icon-save"></i> Save</button>', trim(self::form_no_chain($r)->save()) );
		$this->assertEquals('<button type="submit" name="test" id="test" class="btn btn-default btn-primary" value="Save"><i class="icon-save"></i> Save</button>', trim(self::form_no_chain($r)->save('test')) );
	}
	public function test_save_and_back() {
#		$r['back_link'] = 'http://somewhere.com/';
		$this->assertEquals('<button type="submit" name="back_link" id="back_link" class="btn btn-default btn-primary" value="Save"><i class="icon-save"></i> Save</button>'
			, trim(self::form_no_chain($r)->save_and_back()) );
		$this->assertEquals('<button type="submit" name="test" id="test" class="btn btn-default btn-primary" value="Save"><i class="icon-save"></i> Save</button>'
			, trim(self::form_no_chain($r)->save_and_back('test')) );
	}
	public function test_save_and_clear() {
		$this->assertEquals('<button type="submit" name="clear_link" id="clear_link" class="btn btn-default btn-primary" value="Save"><i class="icon-save"></i> Save</button>'
			, trim(self::form_no_chain($r)->save_and_clear()) );
		$this->assertEquals('<button type="submit" name="test" id="test" class="btn btn-default btn-primary" value="Save"><i class="icon-save"></i> Save</button>'
			, trim(self::form_no_chain($r)->save_and_clear('test')) );
	}
	public function test_info() {
		$this->assertEquals('<span class=" label label-info"></span>', trim(self::form_no_chain($r)->info()) );
		$this->assertEquals('<span class=" label label-info"></span>', trim(self::form_no_chain($r)->info('test')) );
		$r['test'] = 'some info';
		$this->assertEquals('<span class=" label label-info">some info</span>', trim(self::form_no_chain($r)->info('test')) );
	}
	public function test_info_date() {
		$this->assertEquals('<span class=" label label-info"></span>', trim(self::form_no_chain($r)->info_date()) );
		$this->assertEquals('<span class=" label label-info"></span>', trim(self::form_no_chain($r)->info_date('test')) );
		$r['test'] = '2015-01-01';
		$this->assertEquals('<span class=" label label-info">2015/01/01</span>', trim(self::form_no_chain($r)->info_date('test', '%Y/%m/%d')) );
	}
	public function test_info_link() {
		$this->assertEquals('<span class=" label label-info"></span>', trim(self::form_no_chain($r)->info_link()) );
		$this->assertEquals('<span class=" label label-info"></span>', trim(self::form_no_chain($r)->info_link('test')) );
		$r['test'] = './?object=someobject&action=someaction';
		$this->assertEquals('<a href="./?object=someobject&action=someaction" name="test" class=" btn btn-default btn-mini btn-xs" title="Test">./?object=someobject&action=someaction</a>'
			, trim(self::form_no_chain($r)->info_link('test')) );
	}
	public function test_tbl_link() {
		$this->assertEquals('<a class="btn btn-default btn-mini btn-xs"><i class="icon-tasks"></i> </a>', trim(self::form_no_chain($r)->tbl_link()) );
		$this->assertEquals('<a name="test" class="btn btn-default btn-mini btn-xs"><i class="icon-tasks"></i> test</a>', trim(self::form_no_chain($r)->tbl_link('test')) );
		$this->assertEquals('<a name="test" href="./?object=someobject&action=someaction" class="btn btn-default btn-mini btn-xs"><i class="icon-tasks"></i> test</a>'
			, trim(self::form_no_chain($r)->tbl_link('test', './?object=someobject&action=someaction')) );
	}
	public function test_tbl_link_edit() {
		$this->assertEquals('<a name="Edit" class="btn btn-default btn-mini btn-xs ajax_edit"><i class="icon-edit"></i> Edit</a>', trim(self::form_no_chain($r)->tbl_link_edit()) );
		$this->assertEquals('<a name="test" class="btn btn-default btn-mini btn-xs ajax_edit"><i class="icon-edit"></i> test</a>', trim(self::form_no_chain($r)->tbl_link_edit('test')) );
		$r['edit_link'] = './?object=someobject&action=someaction';
		$this->assertEquals('<a name="test" href="./?object=someobject&action=someaction" class="btn btn-default btn-mini btn-xs ajax_edit"><i class="icon-edit"></i> test</a>'
			, trim(self::form_no_chain($r)->tbl_link_edit('test')) );
	}
	public function test_tbl_link_delete() {
		$this->assertEquals('<a name="Delete" class="btn btn-default btn-mini btn-xs ajax_delete btn-danger"><i class="icon-trash"></i> Delete</a>', trim(self::form_no_chain($r)->tbl_link_delete()) );
		$this->assertEquals('<a name="test" class="btn btn-default btn-mini btn-xs ajax_delete btn-danger"><i class="icon-trash"></i> test</a>', trim(self::form_no_chain($r)->tbl_link_delete('test')) );
		$r['delete_link'] = './?object=someobject&action=someaction';
		$this->assertEquals('<a name="test" href="./?object=someobject&action=someaction" class="btn btn-default btn-mini btn-xs ajax_delete btn-danger"><i class="icon-trash"></i> test</a>'
			, trim(self::form_no_chain($r)->tbl_link_delete('test')) );
	}
	public function test_tbl_link_clone() {
		$this->assertEquals('<a name="Clone" class="btn btn-default btn-mini btn-xs ajax_clone"><i class="icon-plus"></i> Clone</a>', trim(self::form_no_chain($r)->tbl_link_clone()) );
		$this->assertEquals('<a name="test" class="btn btn-default btn-mini btn-xs ajax_clone"><i class="icon-plus"></i> test</a>', trim(self::form_no_chain($r)->tbl_link_clone('test')) );
		$r['clone_link'] = './?object=someobject&action=someaction';
		$this->assertEquals('<a name="test" href="./?object=someobject&action=someaction" class="btn btn-default btn-mini btn-xs ajax_clone"><i class="icon-plus"></i> test</a>'
			, trim(self::form_no_chain($r)->tbl_link_clone('test')) );
	}
	public function test_tbl_link_view() {
		$this->assertEquals('<a name="View" class="btn btn-default btn-mini btn-xs ajax_view"><i class="icon-eye-open"></i> View</a>', trim(self::form_no_chain($r)->tbl_link_view()) );
		$this->assertEquals('<a name="test" class="btn btn-default btn-mini btn-xs ajax_view"><i class="icon-eye-open"></i> test</a>', trim(self::form_no_chain($r)->tbl_link_view('test')) );
		$r['view_link'] = './?object=someobject&action=someaction';
		$this->assertEquals('<a name="test" href="./?object=someobject&action=someaction" class="btn btn-default btn-mini btn-xs ajax_view"><i class="icon-eye-open"></i> test</a>'
			, trim(self::form_no_chain($r)->tbl_link_view('test')) );
	}
	public function test_tbl_link_active() {
#		$this->assertEquals('<a href="active_link" class="change_active"><button class="btn btn-default btn-mini btn-xs btn-warning"><i class="icon-ban-circle"></i> Disabled</button></a>'
#			, trim(self::form_no_chain($r)->tbl_link_active()) );
#		$this->assertEquals('<a href="active_link" class="change_active"><button class="btn btn-default btn-mini btn-xs btn-warning"><i class="icon-ban-circle"></i> Disabled</button></a>'
#			, trim(self::form_no_chain($r)->tbl_link_active('test')) );
		$r['active_link'] = './?object=someobject&action=someaction';
		$this->assertEquals('<a href="./?object=someobject&action=someaction" class="change_active"><button class="btn btn-default btn-mini btn-xs btn-warning"><i class="icon-ban-circle"></i> Disabled</button></a>'
			, trim(self::form_no_chain($r)->tbl_link_active('test')) );
	}
}