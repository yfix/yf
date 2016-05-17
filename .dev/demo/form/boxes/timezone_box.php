<?php

return function() {
	return form()
		->timezone_box(['selected' => 'UTC'])
		->timezone_box(['selected' => 'UTC', 'row_tpl' => '%name %offset', 'renderer' => 'div_box'])
	;
};
