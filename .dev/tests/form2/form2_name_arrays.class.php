<?php

class form2_name_arrays {
	function show() {
		$a = array(
			'name'	=> array(
				'key1'	=> 'v1',
			),
		);
		return form($a)
			->on_post(function(){
var_dump($_POST);
			})
			->text('name[]')
			->text('name[]')
			->text('name[key1]')
			->text('name[key2]')
			->save()
		;
	}
}
