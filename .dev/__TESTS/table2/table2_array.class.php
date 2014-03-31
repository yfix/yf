<?php

class table2_array {
	function show() {
		$values = array(
			array('k1' => '', 'k2' => ''),
			array('k1' => '', 'k2' => ''),
		);
		return table($values)
#			->auto()
			->text('k1', 'Field desc 1')
			->text('k2')
#			->btn_func('my button', function(){
#				return '<button class="btn">Test</button>';
#			})
			->btn_edit(array('renderer' => 'button'))
			->btn_delete()
		;
	}
}
