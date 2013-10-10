<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

function _bbcode($text) {
	return _class('bb_codes')->_process_text($text);
}
/*
class class_bb_codes_test extends PHPUnit_Framework_TestCase {
	public function test_main() {
		$this->assertEquals('', _bbcode('[img]http://www.google.com/intl/en_ALL/images/logo.gif[/img]'));
		$this->assertEquals('', _bbcode('[url]http://www.google.com/intl/en_ALL/images/logo.gif[/url]'));
		$this->assertEquals('', _bbcode('[url=\'Google\']http://www.google.com/intl/en_ALL/images/logo.gif[/url]'));
		$this->assertEquals('', _bbcode('[b]This is bold[/b]'));
		$this->assertEquals('', _bbcode('[i]This is italic[/i]'));
		$this->assertEquals('', _bbcode('[u]This is underline[/u]'));
		$this->assertEquals('', _bbcode('[sub]Subscript[/sub]'));
		$this->assertEquals('', _bbcode('[sup]Superscript[/sup]'));
		$this->assertEquals('', _bbcode('[li]List Item[/li]'));
		$this->assertEquals('', _bbcode('[color='red']Red color[/color]'));
		$this->assertEquals('', _bbcode('[size='large']Large size[/size]'));
		$this->assertEquals('', _bbcode('[quote]Quote[/quote]'));
		$this->assertEquals('', _bbcode('[quote='Vasya']Quote Vasya[/quote]'));
		$this->assertEquals('', _bbcode('[code]some code here function, class[/code]'));
		$this->assertEquals('', _bbcode('[imgurl=http://google.com]http://www.google.com/intl/en_ALL/images/logo.gif[/imgurl]'));
		$this->assertEquals('', _bbcode('[hr]'));
		$this->assertEquals('', _bbcode('[email]support@gmail.com[/email]'));
		$this->assertEquals('', _bbcode('[youtube]http://www.youtube.com/v/xlOS_31Ubdo[/youtube]'));
		$this->assertEquals('', _bbcode('[spoiler='Spoiler heading']
				Blablabla inside spoiler
				Long text here Long text here Long text here
				Long text here Long text here Long text here
				Long text here Long text here Long text here
				Long text here Long text here Long text here
				Long text here Long text here Long text here
			[/spoiler]'));
	}
}
*/