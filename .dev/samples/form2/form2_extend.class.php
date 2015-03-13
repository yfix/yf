<?php

class form2_extend {
	function show() {
		main()->extend('form2', 'new_control', function($name, $desc = '', $extra = array(), $replace = array(), $_this) {
			return $_this->input($name, $desc, $extra, $replace);
		});
		return form($r)
			->new_control('Hello', 'world')
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