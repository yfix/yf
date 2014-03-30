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
		$body .= _class('html')->accordion($data, array('selected' => 'second', 'class_head' => 'alert-info'));

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
			array(
				'img'	=> '//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-01.jpg',
				'desc'	=> '<h4>First Thumbnail label</h4><p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>',
			),
			array(
				'img'	=> '//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-02.jpg',
				'desc'	=> '<h4>Second Thumbnail label</h4><p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>',
			),
			array(
				'img'	=> '//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-03.jpg',
				'desc'	=> '<h4>Third Thumbnail label</h4><p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>',
			),
			'//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-01.jpg',
			'//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-02.jpg',
			'//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-03.jpg',
		));

		$body .= '<h1>navbar</h1>';
		$body .= _class('html')->navbar(array(
		));

		$body .= '<h1>breadcrumbs</h1>';
		$body .= _class('html')->breadcrumbs(array());

		$body .= '<h1>alert</h1>';
		$body .= _class('html')->alert(array());

		$body .= '<h1>pagination</h1>';
		$body .= _class('html')->pagination(array());

		$body .= '<h1>thumbnails</h1>';
		$body .= _class('html')->thumbnails(array());

		$body .= '<h1>progress_bar</h1>';
		$body .= _class('html')->progress_bar(array());

		$body .= '<h1>media_objects</h1>';
		$body .= _class('html')->media_objects(array());

		$body .= '<h1>grid</h1>';
		$body .= _class('html')->grid(array());

		if (conf('css_framework') == 'bs3') {
			$body .= '<h1>panel</h1>';
			$body .= _class('html')->panel(array());

			$body .= '<h1>list_group</h1>';
			$body .= _class('html')->list_group(array());

			$body .= '<h1>jumbotron</h1>';
			$body .= _class('html')->jumbotron(array());

			$body .= '<h1>well</h1>';
			$body .= _class('html')->well(array());
		}
		$body .= '<br><br>';
		return $body;
	}
}