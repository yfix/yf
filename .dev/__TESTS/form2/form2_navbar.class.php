<?php

class form2_navbar {
	function go() {
		$body[] = form('', array('no_form' => 1))->country_box(array('selected' => 'US', 'renderer' => 'div_box', 'stacked' => 1));
		$body[] = form('', array('no_form' => 1))->language_box(array('selected' => 'ru', 'renderer' => 'div_box', 'stacked' => 1));
		$body[] = form('', array('no_form' => 1))->currency_box(array('selected' => 'UAH', 'renderer' => 'div_box', 'stacked' => 1));
		return implode($body);
	}
}