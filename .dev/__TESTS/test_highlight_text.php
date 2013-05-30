<?php  
require '../share/functions/profy_common_funcs.php';

class highlight_text extends PHPUnit_Framework_TestCase {


	var	$LANGS_TEST = array(
		'photoshop cs2 download' => '<b>photoshop</b> <b>cs2</b> <b>download</b>',//English
		'Quien esté familiarizado' => '<b>Quien</b> <b>esté</b> <b>familiarizado</b>',//Spanish
		'poiché alcunei funzionalità' => '<b>poiché</b> <b>alcunei</b> <b>funzionalità</b>',//Italian
		'télécharger skype 5.9' => '<b>télécharger</b> <b>skype</b> <b>5</b>.<b>9</b>',//French
		'Anfänger außergewöhnliche' => '<b>Anfänger</b> <b>außergewöhnliche</b>',//Deutsch
		'Se você está' => '<b>Se</b> <b>você</b> <b>está</b>',//Portuguese
		'تحميل ادوب فوتوشوب بسرعه ومجانآ' => '<b>تحميل</b> <b>ادوب</b> <b>فوتوشوب</b> <b>بسرعه</b> <b>ومجانآ</b>',//Arabic
		'lähettämisen samasta ympäristöstä' => '<b>lähettämisen</b> <b>samasta</b> <b>ympäristöstä</b>',//Finnish
		'Skype //|\[^skype]<script> - это популярное' => '<b>Skype</b> //|\[^<b>skype</b>]<<b>script</b>> <b>-</b> <b>это</b> <b>популярное</b>',//Russian
		'Als je bekend bent' => '<b>Als</b> <b>je</b> <b>bekend</b> <b>bent</b>',//Dutch
		'populært overføring å!' => '<b>populært</b> <b>overføring</b> <b>å!</b>',//Norwegian
		'photoshop　ダウンロード' => '<b>photoshop</b>　<b>ダウンロード</b>',//Japanese
		'gør hjælp står' => '<b>gør</b> <b>hjælp</b> <b>står</b>',//Danish*/
		'만약 사진 디자인과 수정할 수 있는 어플리케이션을' => '<b>만약</b> <b>사진</b> <b>디자인과</b> <b>수정할</b> <b>수</b> <b>있는</b> <b>어플리케이션을</b>',//Korean
		'您应该知道没有甚麽工具比Adobe Photoshop更强大' => '<b>您应该知道没有甚麽工具比Adobe</b> <b>Photoshop更强大</b>',//Chinese
		'käyttäjien myös' => '<b>käyttäjien</b> <b>myös</b>',//Greek
		'coś się ściągnąć?' => '<b>coś</b> <b>się</b> <b>ściągnąć?</b>',//Polish
		'möjligt för dig att hålla' => '<b>möjligt</b> <b>för</b> <b>dig</b> <b>att</b> <b>hålla</b>',//Swedish
	);


	public function test_1() {
		foreach ((array)$this->LANGS_TEST as $test_item => $result_item){
			$this->assertEquals($result_item, highlight($test_item, $test_item, 'b', ''));
		}
	}
 
	public function test_2(){
		$this->assertEquals('OK', highlight('photoshop cs2 download', 'download dow', 'b'));
	}
/*
	public function test_3() {
		$big_text = file_get_contents('big_test_text.txt');
		$search_words = 'метро андрей';
		$result = highlight($big_text, $search_words);
		file_put_contents('big_result_text.txt', $result);
		$result = file_get_contents('big_result_text.txt');

		$start_time = microtime(true);
		$this->assertEquals($result, highlight($big_text, $search_words));
		echo microtime(true) - $start_time;
	}
 */
}
