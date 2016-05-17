<?php

return function() {
	return form()
		->timezone_box(['selected' => 'UTC'])
		->timezone_box(['selected' => 'UTC', 'row_tpl' => '%name %code %offset', 'renderer' => 'div_box'])
	;
};
