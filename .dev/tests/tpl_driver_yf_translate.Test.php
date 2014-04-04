<?php  

require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_yf_translate_test extends tpl_abstract {
	public function test_aliases() {
		$this->assertEquals('my translate', self::_tpl( '{translate("my translate")}' ));
		$this->assertEquals('my translate', self::_tpl( '{i18n("my translate")}' ));
	}
	public function test_simple_syntax() {
		$this->assertEquals('my translate', self::_tpl( '{t("my translate")}' ));
		$this->assertEquals('my translate', self::_tpl( "{t('my translate')}" ));
		$this->assertEquals('my translate', self::_tpl( '{t(my translate)}' ));
		$this->assertEquals('my translate', self::_tpl( "{t('my translate)}" ));
		$this->assertEquals('my translate', self::_tpl( '{t(my translate")}' ));
		$this->assertEquals('my translate', self::_tpl( '{t( my translate)}' ));
		$this->assertEquals('my translate', self::_tpl( '{t(my translate )}' ));
		$this->assertEquals('my translate', self::_tpl( '{t( my translate )}' ));
	}
	public function test_namespace() {
		$this->assertEquals('my translate', self::_tpl( '{t("::test::my translate")}' ));
		$this->assertEquals(':test:my translate', self::_tpl( '{t(":test:my translate")}' ));
	}
	public function test_22() {
		$this->assertEquals('my translate a=b', self::_tpl( '{t("my translate a=b")}' ));
	}
	public function test_30() {
		$this->assertEquals('my test translate', self::_tpl( '{t("::test::my %insert translate",%insert="test")}' ));
	}
	public function test_31() {
		$this->assertEquals('my test, test, test translate', self::_tpl( '{t("::test::my %insert, %insert, %insert translate",%insert="test")}' ));
	}
	public function test_32() {
		$this->assertEquals('my test1, test2, test3, test4 translate', self::_tpl( '{t("::test::my %insert1, %insert2, %insert3, %insert4 translate",%insert1="test1";%insert2="test2";%insert3="test3";%insert4="test4")}' ));
	}
	public function test_33() {
		$this->assertEquals('my <b>test1</b>, <i>test2</i> translate', self::_tpl( '{t("::test::my <b>%insert1</b>, <i>%insert2</i> translate",%insert1="test1";%insert2="test2")}' ));
	}
	public function test_41() {
		$this->assertEquals('my <img src="https://www.google.com/images/srpr/logo3w.png">, <b>test1</b>, <i>test2</i> translate', 
			self::_tpl( "{t('::test::my <img src=\"https://www.google.com/images/srpr/logo3w.png\">, <b>%insert1</b>, <i>%insert2</i> translate',%insert1=\"test1\";%insert2=\"test2\")}" ));
	}
	public function test_42() {
		$this->assertEquals('my <img src="https://www.google.com/images/srpr/logo3w.png">, <b>test1</b>, <i>test2</i>, translate value1', 
			self::_tpl( "{t('::test::my <img src=\"https://www.google.com/images/srpr/logo3w.png\">, <b>%insert1</b>, <i>%insert2</i>, translate %replace1',%insert1=\"test1\";%insert2=\"test2\";%replace1=\"{replace1}\")}" , 
				array("replace1" => "value1")));
	}
	public function test_43() {
		$this->assertEquals('my <img src="https://www.google.com/images/srpr/logo3w.png">', self::_tpl( "{catch('myimg')}<img src=\"https://www.google.com/images/srpr/logo3w.png\">{/catch}{t('::test::my @myimg')}", array("replace1" => "value1")));
	}
	public function test_44() {
		$this->assertEquals('my <img src="https://www.google.com/images/srpr/logo3w.png">', self::_tpl( "{t('::test::my @myimg')}", array("myimg" => '<img src="https://www.google.com/images/srpr/logo3w.png">')));
	}
	public function test_45() {
		$this->assertEquals('my <img src="https://www.google.com/images/srpr/logo3w.png">', self::_tpl( "{t('::test::my @my-img')}", array("my-img" => '<img src="https://www.google.com/images/srpr/logo3w.png">')));
	}
	public function test_46() {
		$this->assertEquals('my <img src="https://www.google.com/images/srpr/logo3w.png">, <b>test1</b>, <i>test2</i>, translate value1', 
			self::_tpl( "{t('::test::my @my-img, <b>%insert1</b>, <i>%insert2</i>, translate %replace1',%insert1=\"test1\";%insert2=\"test2\";%replace1=\"{replace1}\")}", 
				array("my-img" => '<img src="https://www.google.com/images/srpr/logo3w.png">', "replace1" => "value1")));
	}
	public function test_47() {
		$this->assertEquals('<a href="javascript:void(0);" onclick="external.BrowseUrl(\'#\');">License Agreement</a>', 
			self::_tpl( '{catch("eula-toggle")} href="javascript:void(0);" onclick="external.BrowseUrl(\'#\');"  {/catch}{t("::installer::<a @eula-toggle>License Agreement</a>")}'
		));
	}
}