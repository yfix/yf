<?php

return function() {
	return form()
		->check_box('restricted_view', 'Ограничить просмотр (категорий +21)')
		->check_box('restricted_view', '', ['desc' => 'Ограничить просмотр (категорий +21)', 'no_label' => true])
	;
};
