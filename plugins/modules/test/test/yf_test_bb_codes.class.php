<?php

/**
* Test sub-class
*/
class yf_test_bb_codes {

	/**
	*/
	function test () {
		$BB_OBJ = main()->init_class("bb_codes", "classes/");

		$string = "
			[img]http://www.google.com/intl/en_ALL/images/logo.gif[/img]

			[url]http://www.google.com/intl/en_ALL/images/logo.gif[/url]

			[url='Google']http://www.google.com/intl/en_ALL/images/logo.gif[/url]

			[b]This is bold[/b] 

			[i]This is italic[/i] 

			[u]This is underline[/u] 

			[sub]Subscript[/sub] 

			[sup]Superscript[/sup] 

			[li]List Item[/li] 

			[color='red']Red color[/color] 

			[size='large']Large size[/size] 

			[quote]Quote[/quote] 

			[quote='Vasya']Quote Vasya[/quote] 

			[code]some code here function, class[/code] 

			[imgurl=http://google.com]http://www.google.com/intl/en_ALL/images/logo.gif[/imgurl]

			[hr] 

			[email]support@gmail.com[/email]

			[youtube]http://www.youtube.com/v/xlOS_31Ubdo[/youtube] 

			[spoiler='Spoiler heading']
				Blablabla inside spoiler
				Long text here Long text here Long text here
				Long text here Long text here Long text here
				Long text here Long text here Long text here
				Long text here Long text here Long text here
				Long text here Long text here Long text here
			[/spoiler] 
		";

		$body = $BB_OBJ->_process_text($string);

		return $body;
	}
}
