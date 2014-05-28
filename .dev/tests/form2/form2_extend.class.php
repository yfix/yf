<?php

class form2_extend {
	function show() {
		main()->extend('form2', 'new_control', function($name, $desc = '', $extra = array(), $replace = array(), $_this) {
			return $_this->input($name, $desc, $extra, $replace);
		});
		return form($r)
			->new_control('Hello', 'world')
			->save();
	}
}