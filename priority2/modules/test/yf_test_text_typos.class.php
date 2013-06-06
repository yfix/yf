<?php

/**
* Test sub-class
*/
class yf_test_text_typos {

	/**
	* YF module constructor
	*/
	function _init () {
		$this->TEST_OBJ = module("test");
	}

	/**
	*/
	function run_test () {
		$GLOBALS['PROJECT_CONF']["text_typos"]["USE_RUSSIAN"] = 1;

		$OBJ = main()->init_class("text_typos", "classes/");

		$text = " и Джона Петруччи в особенности, 
играющих или обучающихся игре на гитаре,  предлагается интересная 
вещь…в общем-то эта вещь – новая книга с табулатурами/нотами 
с сольного альбома Джона, Suspended Animation. Её можно приобрести на сайте 
официального мерчендайза гитариста, вот Сегодня на свет выходит 
<img alt=\"Google\" height=110 src=\"http://google.com/intl/en_ALL/images/logo.gif\" width=276>
первый сборник Dream Theater под названием \"Greatest Hit...and 21 other pretty cool songs\", 
включающий в себя пересведённые версии классических песен с Images and Words, 
редкие радио версии песен 
с других альбомов, би-сайды и лучшие хиты группы.";

		$result = $OBJ->process($text);

		$body .= "<b>Source</b><br />".$text."<br /><hr /><br />";
		$body .= "<b>Result</b><br />".$result."<br /><hr /><br />";

		return $body;
	}
}
