<?php

/**
* Abstraction layer over HTML5/CSS frameworks.
* Planned support for these plugins: 
*	Bootstrap 2		http://twbs.github.io/bootstrap/2.3.2/
*	Bootstrap 3		http://twbs.github.io/bootstrap/3
*	Zurb Foundation	http://foundation.zurb.com/
*	Pure CSS		http://purecss.io/
*	Semantic UI		http://semantic-ui.com/
*	UI Kit			http://getuikit.com/
*	Maxmertkit		http://www.maxmert.com/
*/
class yf_html5fw {

	/** @var */
	public $DEFAULT_CSS_FRAMEWORK = 'bs2';

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* We cleanup object properties when cloning
	*/
	function __clone() {
		foreach ((array)get_object_vars($this) as $k => $v) {
			$this->$k = null;
		}
	}

	/**
	* Need to avoid calling render() without params
	*/
	function __toString() {
		try {
			return (string) $this->render();
		} catch (Exception $e) {
			return '';
		}
	}

	/**
	*/
	function form_row($content, $extra = [], $replace = [], $obj) {
		$css_framework = $extra['css_framework'] ?: conf('css_framework');
		if (!$css_framework) {
			$css_framework = $this->DEFAULT_CSS_FRAMEWORK;
		}
		return _class('html5fw_'.$css_framework, 'classes/html5fw/')->form_row($content, $extra, $replace, $obj);
	}

	/**
	*/
	function form_dd_row($content, $extra = [], $replace = [], $obj) {
		$css_framework = $extra['css_framework'] ?: conf('css_framework');
		if (!$css_framework) {
			$css_framework = $this->DEFAULT_CSS_FRAMEWORK;
		}
		return _class('html5fw_'.$css_framework, 'classes/html5fw/')->form_dd_row($content, $extra, $replace, $obj);
	}

	/**
	*/
	function form_render_out($content, $extra = [], $replace = [], $obj) {
		$css_framework = $extra['css_framework'] ?: conf('css_framework');
		if (!$css_framework) {
			$css_framework = $this->DEFAULT_CSS_FRAMEWORK;
		}
		return _class('html5fw_'.$css_framework, 'classes/html5fw/')->form_render_out($content, $extra, $replace, $obj);
	}
}
