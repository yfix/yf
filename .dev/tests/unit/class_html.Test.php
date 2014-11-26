<?php  

require_once __DIR__.'/yf_unit_tests_setup.php';

class class_html_test extends PHPUnit_Framework_TestCase {
	public function test_select_box() {
		html()->_ids = array();
		$data = array(
			1 => 'red',
			2 => 'green'
		);

		html()->AUTO_ASSIGN_IDS = false;
		$html = html()->select_box('myselect', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect" class="form-control"><option value="1" >red</option><option value="2" >green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->select_box('myselect2', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect2" class="form-control"><option value="1" >red</option><option value="2" >green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);

		html()->_ids = array();
		html()->AUTO_ASSIGN_IDS = true;
		$html = html()->select_box('myselect', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect" id="select_box_1" class="form-control"><option value="1" >red</option><option value="2" >green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->select_box('myselect2', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect2" id="select_box_2" class="form-control"><option value="1" >red</option><option value="2" >green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
	}
	public function test_multi_select() {
		html()->_ids = array();
		$data = array(
			1 => 'red',
			2 => 'green'
		);

		html()->AUTO_ASSIGN_IDS = true;
		$html = html()->multi_select('myselect', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect[]" id="multi_select_1" class="form-control" multiple="multiple"><option value="1" >red</option><option value="2" >green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->multi_select('myselect2', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect2[]" id="multi_select_2" class="form-control" multiple="multiple"><option value="1" >red</option><option value="2" >green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);

		html()->AUTO_ASSIGN_IDS = false;
		$html = html()->multi_select('myselect', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect[]" class="form-control" multiple="multiple"><option value="1" >red</option><option value="2" >green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
		$html = html()->multi_select('myselect2', $data);
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<select name="myselect2[]" class="form-control" multiple="multiple"><option value="1" >red</option><option value="2" >green</option></select>'
			), str_replace(PHP_EOL, '', trim($html))
		);
	}
}
