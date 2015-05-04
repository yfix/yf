<?php

class form2_name_arrays {
	function show() {
		$a = array(
			'name'	=> array(
				'key1'	=> 'v1',
			),
		);
		return form((array)$_POST + $a)
			->validate(array(
				'name[key1]'	=> 'trim|required',
				'name[key2]'	=> 'trim|required',
			))
			->on_post(function(){
				//var_dump($_POST);
			})
			->text('name[]')
			->text('name[]')
			->text('name[key1]')
			->text('name[key2]')
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
