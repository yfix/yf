<?php

class form2_name_arrays {
	function show() {
		return form()
			->text('name[]')
			->text('name[]')
			->text('name[key1]')
			->text('name[key2]')
		;
	}
}
