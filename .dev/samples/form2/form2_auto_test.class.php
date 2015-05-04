<?php

class form2_auto_test {
	function show() {
		return form('', array(
				'no_form'	=> 1,
				'tabs'		=> array(
					'class'		=> 'span6 col-md-6',
					'show_all'	=> 1,
					'no_headers'=> 1,
				),
			))
#			->tab_start('auto1')
#			->container(
#				form('SELECT * FROM '.db('user').' WHERE id=1')->auto()
#			)
#			->tab_end()
			->tab_start('auto2')
			->container(
				form()->auto(db('user'), 1)
			)
			->tab_end()
			. $this->_self_source(__FUNCTION__)
		;
	}
	function _self_source($method) {
		asset('highlightjs');
		$source = _class('core_api')->get_method_source(__CLASS__, $method);
		return '<div id="func_self_source_'.$name.'"><pre class="prettyprint lang-php"><code>'._prepare_html($source['source']).'</code></pre></div> ';
	}
}
