<?php

class test_html {
	function show() {
		$data = array(
			'first' 	=> 'first text',
			'second'	=> 'second text',
			'third'		=> 'Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. 
				Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. 
				Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. 
				Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably havent heard of them accusamus labore sustainable VHS.',
		);
		$body .= _class('html')->dd_table($data, array());
		$body .= _class('html')->accordion($data, array('selected' => 'second'));
		$body .= _class('html')->tabs($data, array('selected' => 'third'));
		return $body;
	}
}