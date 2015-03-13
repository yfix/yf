<?php

class form2_navbar {
	function show() {
#		$body[] = form_item()->country_box(array('selected' => 'US'));
		$body[] = form_item()->country_box(array('selected' => 'US', 'renderer' => 'div_box'));
		$body[] = form_item()->language_box(array('selected' => 'ru', 'renderer' => 'div_box'));
		$body[] = form_item()->currency_box(array('selected' => 'UAH', 'renderer' => 'div_box'));
		$body[] = form_item()->timezone_box(array('selected' => 'UTC', 'renderer' => 'div_box'));
		return implode($body)
			. $this->_self_source(__FUNCTION__)
		;
	}
	function _self_source($method) {
		asset('highlightjs');
		$source = _class('core_api')->get_method_source(__CLASS__, $method);
		return '<div id="func_self_source_'.$name.'"><pre class="prettyprint lang-php"><code>'._prepare_html($source['source']).'</code></pre></div> ';
	}
}