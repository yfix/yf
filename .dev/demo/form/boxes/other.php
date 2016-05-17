<?php

return function() {
	return form()
		->time_box()
		->date_box()
		->datetime_box()
		->birth_box()
		->user_method_box(['desc' => 'user method'])
		->admin_method_box(['desc' => 'admin method'])
		->user_template_box(['desc' => 'user template'])
		->admin_template_box(['desc' => 'admin template'])
		->user_location_box(['desc' => 'user location'])
		->admin_location_box(['desc' => 'admin location'])
	;
};
