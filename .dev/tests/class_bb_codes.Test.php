<?php

require __DIR__.'/yf_unit_tests_setup.php';

class class_bb_codes_test extends PHPUnit_Framework_TestCase {
	public static $_er = array();
	public static function setUpBeforeClass() {
		self::$_er = error_reporting();
		error_reporting(0);
	}
	public static function tearDownAfterClass() {
		error_reporting(self::$_er);
	}
	function _bbcode($text) {
		return _class('bb_codes')->_process_text($text);
	}
	public function test_10() {
		$this->assertEquals('<div class="bb_remote_image"><img src="http://www.google.com/intl/en_ALL/images/logo.gif"></div>', 
			self::_bbcode('[img]http://www.google.com/intl/en_ALL/images/logo.gif[/img]'));
		$this->assertEquals('<a href="http://www.google.com/intl/en_ALL/images/logo.gif" target="blank">http://www.google.com/intl/en_ALL/images/logo.gif</a>', 
			self::_bbcode('[url]http://www.google.com/intl/en_ALL/images/logo.gif[/url]'));
		$this->assertEquals('<a href="Google" target="blank">http://www.google.com/intl/en_ALL/images/logo.gif</a>', 
			self::_bbcode('[url="Google"]http://www.google.com/intl/en_ALL/images/logo.gif[/url]'));
		$this->assertEquals('<b>This is bold</b>', self::_bbcode('[b]This is bold[/b]'));
		$this->assertEquals('<i>This is italic</i>', self::_bbcode('[i]This is italic[/i]'));
		$this->assertEquals('<u>This is underline</u>', self::_bbcode('[u]This is underline[/u]'));
		$this->assertEquals('<sub>Subscript</sub>', self::_bbcode('[sub]Subscript[/sub]'));
		$this->assertEquals('<sup>Superscript</sup>', self::_bbcode('[sup]Superscript[/sup]'));
		$this->assertEquals('<li>List Item</li>', self::_bbcode('[li]List Item[/li]'));
		$this->assertEquals('<span style="color:red">Red color</span>', self::_bbcode('[color="red"]Red color[/color]'));
		$this->assertEquals('<span style="font-size:14px;">Large size</span>', self::_bbcode('[size="14"]Large size[/size]'));
		$this->assertEquals('<div>quote <b></b> :</div><div class="forum_quote">Quote</div>', 
			self::_bbcode('[quote]Quote[/quote]'));
		$this->assertEquals('<div>quote <b>Vasya</b> :</div><div class="forum_quote">Quote Vasya</div>', 
			self::_bbcode('[quote="Vasya"]Quote Vasya[/quote]'));
		$this->assertEquals('<pre class="forum_code">some code here function, class</pre>', 
			self::_bbcode('[code]some code here function, class[/code]'));
		$this->assertEquals('<a href="http://google.com" target="blank"><img src="http://www.google.com/intl/en_ALL/images/logo.gif" border="0"></a>', 
			self::_bbcode('[imgurl=http://google.com]http://www.google.com/intl/en_ALL/images/logo.gif[/imgurl]'));
		$this->assertEquals('<hr />', self::_bbcode('[hr]'));
		$this->assertEquals('<a href="mailto:support@gmail.com">support@gmail.com</a>', self::_bbcode('[email]support@gmail.com[/email]'));
		$this->assertEquals('<object width="425" height="350"><param name="movie" value="http://www.youtube.com/v/xlOS_31Ubdo"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/xlOS_31Ubdo" type="application/x-shockwave-flash" wmode="transparent" width="425" height="350"></embed></object>', 
			self::_bbcode('[youtube]http://www.youtube.com/v/xlOS_31Ubdo[/youtube]'));
		$this->assertEquals('<div class="spoiler_block"><div class="spoiler_head"><input type="button" class="toggle_button" value="+">Spoiler heading&nbsp;</div><div class="spoiler_body"><br />
				Blablabla inside spoiler<br />
				Long text here Long text here Long text here<br />
				Long text here Long text here Long text here<br />
				Long text here Long text here Long text here<br />
				Long text here Long text here Long text here<br />
				Long text here Long text here Long text here<br />
			</div></div>', self::_bbcode('[spoiler="Spoiler heading"]
				Blablabla inside spoiler
				Long text here Long text here Long text here
				Long text here Long text here Long text here
				Long text here Long text here Long text here
				Long text here Long text here Long text here
				Long text here Long text here Long text here
			[/spoiler]'));
	}
}
