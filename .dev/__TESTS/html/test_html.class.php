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
		$body .= '<h1>dd table</h1>';
		$body .= _class('html')->dd_table($data, array());
		$body .= '<h1>accordion</h1>';
		$body .= _class('html')->accordion($data, array('selected' => 'second'));
		$body .= '<h1>tabs</h1>';
		$body .= _class('html')->tabs($data, array('selected' => 'third'));
		$body .= '<h1>modal</h1>';
		$body .= _class('html')->modal(array(
			'inline'		=> 1,
			'show_close'	=> 1,
			'header'		=> 'Modal header',
			'body'			=> '<p>Some body</p>',
			'footer'		=> form_item()->save(),
		));
		$body .= '<h1>carousel</h1>';
		$body .= _class('html')->carousel(array(
		));
		$body .= '<h1>navbar</h1>';
		$body .= _class('html')->navbar(array(
		));
		$body .= '<h1>breadcrumbs</h1>';
		$body .= _class('html')->breadcrumbs(array(
		));
		return $body;
	}
}