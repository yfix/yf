<?php

/**
* Absttraction layer over HTML5/CSS frameworks.
* Planned support for these plugins: 
*	Bootstrap 2		http://twbs.github.io/bootstrap/2.3.2/
*	Bootstrap 3		http://twbs.github.io/bootstrap/3
*	Zurb Foundation	http://foundation.zurb.com/
*	Pure CSS		http://purecss.io/
*/
class yf_html {

	/**
	*/
	function form_row ($content, $extra = array(), $replace = array(), $obj) {
		$css_framework = conf('css_framework');
		if ($css_framework) {
			return _class('html_'.$css_framework, 'classes/html/')->form_row($content, $extra, $replace, $obj);
		}
	}

	/**
	*/
	function table_header () {
// TODO
	}

	/**
	*/
	function navbar () {
// TODO
	}

	/**
	*/
	function breadcrumbs () {
// TODO
	}
}