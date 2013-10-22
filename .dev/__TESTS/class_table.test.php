<?php  

require dirname(__FILE__).'/yf_unit_tests_setup.php';

$_GET['object'] = 'dynamic';
$_GET['action'] = 'unit_test_table';

class class_table_test extends PHPUnit_Framework_TestCase {
	public function test_01() {
		$html = table();
		$this->assertEquals('<div class="alert alert-info">No records</div>', trim($html));
	}
	public function test_10() {
		$a = array(	array('k1' => 'v1', 'k2' => 'v2') );
		$html = table($a)->text();
		$this->assertEquals(str_replace(PHP_EOL, '', 
'<table class="table table-bordered table-striped table-hover">
<thead></thead><tbody><tr></tr></tbody>
</table>'), str_replace(PHP_EOL, '', trim($html)));
	}
	public function test_11() {
		$a = array(	array('k1' => 'v11', 'k2' => 'v21'), array('k1' => 'v12', 'k2' => 'v22') );
		$html = table($a)->text('k1');
		$this->assertEquals(str_replace(PHP_EOL, '', 
'<table class="table table-bordered table-striped table-hover">
<thead><th>K1</th></thead>
<tbody><tr><td>v11</td></tr><tr><td>v12</td></tr></tbody>
</table>'), str_replace(PHP_EOL, '', trim($html)));
	}
	public function test_12() {
		$a = array(	array('k1' => 'v11', 'k2' => 'v21'), array('k1' => 'v12', 'k2' => 'v22') );
		$html = table($a)->text('k1');
		$this->assertEquals(str_replace(PHP_EOL, '', 
'<table class="table table-bordered table-striped table-hover">
<thead><th>K1</th></thead>
<tbody><tr><td>v11</td></tr><tr><td>v12</td></tr></tbody>
</table>'), str_replace(PHP_EOL, '', trim($html)));
	}
	public function test_13() {
		$a = array(	array('k1' => 'v11', 'k2' => 'v21'), array('k1' => 'v12', 'k2' => 'v22') );
		$html = table($a)->text('k1')->text('k2');
		$this->assertEquals(str_replace(PHP_EOL, '', 
'<table class="table table-bordered table-striped table-hover">
<thead><th>K1</th><th>K2</th></thead>
<tbody><tr><td>v11</td><td>v21</td></tr><tr><td>v12</td><td>v22</td></tr></tbody>
</table>'), str_replace(PHP_EOL, '', trim($html)));
	}
	public function test_14() {
		$a = array(	array('k1' => 'v11', 'k2' => 'v21'), array('k1' => 'v12', 'k2' => 'v22') );
		$html = table($a)->text('k1')->text('k2')->text('k3');
		$this->assertEquals(str_replace(PHP_EOL, '', 
'<table class="table table-bordered table-striped table-hover">
<thead><th>K1</th><th>K2</th></thead>
<tbody><tr><td>v11</td><td>v21</td></tr><tr><td>v12</td><td>v22</td></tr></tbody>
</table>'), str_replace(PHP_EOL, '', trim($html)));
	}
}