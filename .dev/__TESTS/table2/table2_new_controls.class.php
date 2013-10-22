<?php

class table2_new_controls {
	function show() {
		$sql = 'SELECT * FROM '.db('countries').' ORDER BY name ASC';
		return table($sql)
			->text('code', array('width' => '5%'))
			->text('name')
		;
	}
}
