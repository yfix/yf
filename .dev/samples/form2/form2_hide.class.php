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
			->save()
			. $this->_self_source(__FUNCTION__)
		;
	}
	function _self_source($method) {
		asset('highlightjs');
		$source = _class('core_api')->get_method_source(__CLASS__, $method);
		return '<div id="func_self_source_'.$name.'"><pre class="prettyprint lang-php"><code>'._prepare_html($source['source']).'</code></pre></div> ';
	}
}