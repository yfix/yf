<?php

return function() {
	return form()
		->language_box(['selected' => 'uk'])
		->language_box(['selected' => 'uk', 'row_tpl' => '%name %icon %code', 'renderer' => 'div_box'])
	;
};
