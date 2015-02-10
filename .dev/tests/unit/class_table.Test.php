<?php  

require_once __DIR__.'/yf_unit_tests_setup.php';

/* TODO:
* array filtering
* sql filtering (eq, between, callbacks, etc)
* tr params (string, array, callback)
* td params (string, array, callback)
* rotated mode
*/

class class_table_test extends PHPUnit_Framework_TestCase {
	public static function setUpBeforeClass() {
		$_GET['object'] = 'dynamic';
		$_GET['action'] = 'unit_test_table';
	}
	public function test_css_classes() {
		$this->assertEquals( _class('table2')->CLASS_TABLE_MAIN, 'table table-bordered table-striped table-hover' );
		$this->assertEquals( _class('table2')->CLASS_BTN_MINI, 'btn btn-default btn-mini btn-xs' );
		$this->assertEquals( _class('table2')->CLASS_ICON_BTN, 'icon-tasks fa fa-tasks' );
		$this->assertEquals( _class('table2')->CLASS_ICON_DELETE, 'icon-trash fa fa-trash' );
		$this->assertEquals( _class('table2')->CLASS_ICON_EDIT, 'icon-edit fa fa-edit' );
	}
	public function test_basic() {
		$table = table();
		$this->assertEquals('<div class="alert alert-info">No records</div>', trim($table));

		$a = array(
			array('k1' => 'v11', 'k2' => 'v21'),
			array('k1' => 'v12', 'k2' => 'v22')
		);

		$table = @table($a)
			->text();

		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<table class="'._class('table2')->CLASS_TABLE_MAIN.'">'.
			'<thead></thead><tbody><tr></tr><tr></tr></tbody>'.
			'</table>'), str_replace(PHP_EOL, '', trim($table)));

		$table = table($a)
			->text('k1');

		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<table class="'._class('table2')->CLASS_TABLE_MAIN.'">'.
			'<thead><th>K1</th></thead>'.
			'<tbody><tr><td>v11</td></tr><tr><td>v12</td></tr></tbody>'.
			'</table>'), str_replace(PHP_EOL, '', trim($table)));

		$table = table($a)
			->text('k1');

		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<table class="'._class('table2')->CLASS_TABLE_MAIN.'">'.
			'<thead><th>K1</th></thead>'.
			'<tbody><tr><td>v11</td></tr><tr><td>v12</td></tr></tbody>'.
			'</table>'), str_replace(PHP_EOL, '', trim($table)));

		$table = table($a)
			->text('k1')
			->text('k2');

		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<table class="'._class('table2')->CLASS_TABLE_MAIN.'">'.
			'<thead><th>K1</th><th>K2</th></thead>'.
			'<tbody><tr><td>v11</td><td>v21</td></tr><tr><td>v12</td><td>v22</td></tr></tbody>'.
			'</table>'), str_replace(PHP_EOL, '', trim($table)));

		$table = table($a)
			->text('k1')
			->text('k2')
			->text('k3');

		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<table class="'._class('table2')->CLASS_TABLE_MAIN.'">'.
			'<thead><th>K1</th><th>K2</th></thead>'.
			'<tbody><tr><td>v11</td><td>v21</td></tr><tr><td>v12</td><td>v22</td></tr></tbody>'.
			'</table>'), str_replace(PHP_EOL, '', trim($table)));

		$this->assertObjectHasAttribute('_total', $table);
		$this->assertEquals($table->_total, count($a));
		$this->assertObjectHasAttribute('_pages', $table);
		$this->assertInternalType('string', $table->_pages);
		$this->assertObjectHasAttribute('_ids', $table);
		$this->assertSame(count($table->_ids), count($a));

		$a = array();
		$table = table($a);
		$this->assertObjectHasAttribute('_total', $table);
		$this->assertEquals($table->_total, count($a));
		$this->assertObjectHasAttribute('_pages', $table);
		$this->assertObjectHasAttribute('_ids', $table);
		$this->assertSame(count($table->_ids), count($a));

		$table = table(null, array('no_records_callback' => function(){return 'Hello';}));
		$this->assertEquals('Hello', trim($table));
	}
	public function test_btn_link() {
		$a = array(
			array('id' => '1', 'user_id' => '122', 'product_id' => '133'),
			array('id' => '2', 'user_id' => '222', 'product_id' => '233'),
		);
		$table = table($a)
			->text('id')
			->btn('custom', './?object=test&uid=%d')
		;
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<table class="'._class('table2')->CLASS_TABLE_MAIN.'">'.
			'<thead><th>Id</th><th>Actions</th></thead><tbody>'.
			'<tr><td>1</td><td nowrap><a href="./?object=test&uid=1" class="'._class('table2')->CLASS_BTN_MINI.'" title="custom"><i class="'._class('table2')->CLASS_ICON_BTN.'"></i> custom</a> </td></tr>'.
			'<tr><td>2</td><td nowrap><a href="./?object=test&uid=2" class="'._class('table2')->CLASS_BTN_MINI.'" title="custom"><i class="'._class('table2')->CLASS_ICON_BTN.'"></i> custom</a> </td></tr>'.
			'</tbody></table>'), str_replace(PHP_EOL, '', trim($table)));

		$table = table($a)
			->text('id')
			->btn('custom', './?object=test&uid=%user_id&pid=%product_id', array('link_params' => 'user_id,product_id'))
		;
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<table class="'._class('table2')->CLASS_TABLE_MAIN.'">'.
			'<thead><th>Id</th><th>Actions</th></thead><tbody>'.
			'<tr><td>1</td><td nowrap><a href="./?object=test&uid=122&pid=133" class="'._class('table2')->CLASS_BTN_MINI.'" title="custom"><i class="'._class('table2')->CLASS_ICON_BTN.'"></i> custom</a> </td></tr>'.
			'<tr><td>2</td><td nowrap><a href="./?object=test&uid=222&pid=233" class="'._class('table2')->CLASS_BTN_MINI.'" title="custom"><i class="'._class('table2')->CLASS_ICON_BTN.'"></i> custom</a> </td></tr>'.
			'</tbody></table>'), str_replace(PHP_EOL, '', trim($table)));

		$table = table($a)
			->text('id')
			->btn('custom1', './?object=test&uid=%user_id&pid=%product_id', array('link_params' => 'user_id,product_id'))
			->btn('custom2', './?object=test&uid=%user_id&pid=555', array('link_params' => 'user_id'))
		;
		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<table class="'._class('table2')->CLASS_TABLE_MAIN.'">'.
			'<thead><th>Id</th><th>Actions</th></thead><tbody>'.
			'<tr><td>1</td><td nowrap><a href="./?object=test&uid=122&pid=133" class="'._class('table2')->CLASS_BTN_MINI.'" title="custom1"><i class="'._class('table2')->CLASS_ICON_BTN.'"></i> custom1</a> '
				.'<a href="./?object=test&uid=122&pid=555" class="'._class('table2')->CLASS_BTN_MINI.'" title="custom2"><i class="'._class('table2')->CLASS_ICON_BTN.'"></i> custom2</a> </td></tr>'.
			'<tr><td>2</td><td nowrap><a href="./?object=test&uid=222&pid=233" class="'._class('table2')->CLASS_BTN_MINI.'" title="custom1"><i class="'._class('table2')->CLASS_ICON_BTN.'"></i> custom1</a> '
				.'<a href="./?object=test&uid=222&pid=555" class="'._class('table2')->CLASS_BTN_MINI.'" title="custom2"><i class="'._class('table2')->CLASS_ICON_BTN.'"></i> custom2</a> </td></tr>'.
			'</tbody></table>'), str_replace(PHP_EOL, '', trim($table)));
	}
	public function test_auto() {
		$a = array(
			array('id' => '1', 'user_id' => '122', 'product_id' => '133'),
			array('id' => '2', 'user_id' => '222', 'product_id' => '233'),
		);
		$table = table($a)->auto();

		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<table class="'._class('table2')->CLASS_TABLE_MAIN.'">'.
			'<thead><th>Id</th><th>User id</th><th>Product id</th><th>Actions</th></thead><tbody>'.
			'<tr><td>1</td><td>122</td><td>133</td><td nowrap><a href="./?object=dynamic&action=edit&id=1" class="'._class('table2')->CLASS_BTN_MINI.' ajax_edit" title="Edit" data-test="edit"><i class="'._class('table2')->CLASS_ICON_EDIT.'"></i> Edit</a> '.
			'<a href="./?object=dynamic&action=delete&id=1" class="'._class('table2')->CLASS_BTN_MINI.' ajax_delete btn-danger" title="Delete" data-test="delete"><i class="'._class('table2')->CLASS_ICON_DELETE.'"></i> Delete</a> </td></tr>'.
			'<tr><td>2</td><td>222</td><td>233</td><td nowrap><a href="./?object=dynamic&action=edit&id=2" class="'._class('table2')->CLASS_BTN_MINI.' ajax_edit" title="Edit" data-test="edit"><i class="'._class('table2')->CLASS_ICON_EDIT.'"></i> Edit</a> '.
			'<a href="./?object=dynamic&action=delete&id=2" class="'._class('table2')->CLASS_BTN_MINI.' ajax_delete btn-danger" title="Delete" data-test="delete"><i class="'._class('table2')->CLASS_ICON_DELETE.'"></i> Delete</a> </td></tr>'.
			'</tbody></table>'.
			'<div class="controls"><a href="./?object=dynamic&action=add" class="'._class('table2')->CLASS_BTN_MINI.' ajax_add"><i class=" icon-plus fa fa-plus"></i> add</a> </div>'
		), str_replace(PHP_EOL, '', trim($table)));
	}
	public function test_rotate() {
		$a = array(
			array('id' => '1', 'user_id' => '122', 'product_id' => '133'),
			array('id' => '2', 'user_id' => '222', 'product_id' => '233'),
		);
		$table = table($a)
			->text('id')
			->text('user_id');

		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<table class="'._class('table2')->CLASS_TABLE_MAIN.'"><thead><th>Id</th><th>User id</th></thead><tbody><tr><td>1</td><td>122</td></tr><tr><td>2</td><td>222</td></tr></tbody></table>'
		), str_replace(PHP_EOL, '', trim($table)));

		$table = table($a, array('rotate_table' => 1))
			->text('id')
			->text('user_id');

		$this->assertEquals(str_replace(PHP_EOL, '', 
			'<table class="'._class('table2')->CLASS_TABLE_MAIN.'"><tbody><tr><td>1</td><td>2</td></tr><tr><td>122</td><td>222</td></tr></tbody></table>'
		), str_replace(PHP_EOL, '', trim($table)));
	}
}