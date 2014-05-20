<?php

class form2_hide {
	function show () {
		return form((array)$_POST + (array)$a)
			->validate(array('name'	=> 'trim|required'))
			->on_after_render(function($e, $r, $_this) {
				if ($_this->_validate_ok) {
					common()->message_success('ok');
					common()->message_warning('warn');
					common()->message_error('error');
					common()->message_info('info');
					$_this->_rendered = '';
				}
			})
			->text('name')
			->save();
	}
}