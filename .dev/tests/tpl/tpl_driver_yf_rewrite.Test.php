<?php

require_once __DIR__.'/tpl__setup.php';

class tpl_driver_yf_rewrite_test extends tpl_abstract {
	public function test_url() {
		$host = 'subdomain.test.dev';
		$_SERVER['HTTP_HOST'] = $host;
		$this->assertEquals('http://'.$host.'/?object=shop&action=basket', self::_tpl( '{url(object=shop;action=basket)}' ));
		$this->assertEquals('http://'.$host.'/?object=shop&action=basket', self::_tpl( '{url(/shop/basket/)}' ));
		$this->assertEquals('http://'.$host.'/?object=shop&action=autocomplete&search_word=%QUERY', self::_tpl( '{url(object=shop;action=autocomplete;search_word=%QUERY)}' ));
		$this->assertEquals('http://'.$host.'/?object=shop&action=autocomplete&id=&search_word=%QUERY', self::_tpl( '{url(/shop/autocomplete/&search_word=%QUERY)}' ));
	}
}