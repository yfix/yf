<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_bb_codes_test extends PHPUnit_Framework_TestCase {
	function _bbcode($text) {
		return _class('bb_codes')->_process_text($text);
	}
	public function test_main() {
/*
		$this->assertEquals('', self::_bbcode('[img]http://www.google.com/intl/en_ALL/images/logo.gif[/img]'));
		$this->assertEquals('', self::_bbcode('[url]http://www.google.com/intl/en_ALL/images/logo.gif[/url]'));
		$this->assertEquals('', self::_bbcode('[url=\'Google\']http://www.google.com/intl/en_ALL/images/logo.gif[/url]'));
		$this->assertEquals('', self::_bbcode('[b]This is bold[/b]'));
		$this->assertEquals('', self::_bbcode('[i]This is italic[/i]'));
		$this->assertEquals('', self::_bbcode('[u]This is underline[/u]'));
		$this->assertEquals('', self::_bbcode('[sub]Subscript[/sub]'));
		$this->assertEquals('', self::_bbcode('[sup]Superscript[/sup]'));
		$this->assertEquals('', self::_bbcode('[li]List Item[/li]'));
		$this->assertEquals('', self::_bbcode('[color='red']Red color[/color]'));
		$this->assertEquals('', self::_bbcode('[size='large']Large size[/size]'));
		$this->assertEquals('', self::_bbcode('[quote]Quote[/quote]'));
		$this->assertEquals('', self::_bbcode('[quote='Vasya']Quote Vasya[/quote]'));
		$this->assertEquals('', self::_bbcode('[code]some code here function, class[/code]'));
		$this->assertEquals('', self::_bbcode('[imgurl=http://google.com]http://www.google.com/intl/en_ALL/images/logo.gif[/imgurl]'));
		$this->assertEquals('', self::_bbcode('[hr]'));
		$this->assertEquals('', self::_bbcode('[email]support@gmail.com[/email]'));
		$this->assertEquals('', self::_bbcode('[youtube]http://www.youtube.com/v/xlOS_31Ubdo[/youtube]'));
		$this->assertEquals('', self::_bbcode('[spoiler='Spoiler heading']
				Blablabla inside spoiler
				Long text here Long text here Long text here
				Long text here Long text here Long text here
				Long text here Long text here Long text here
				Long text here Long text here Long text here
				Long text here Long text here Long text here
			[/spoiler]'));
*/
	}
}
