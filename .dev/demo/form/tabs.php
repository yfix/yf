<?php

return function() {
	return form()
		->tab_start('home')
			->icon_select_box(['selected' => 'icon-anchor'])
			->currency_box(['selected' => 'RUB'])
			->language_box(['selected' => 'uk'])
			->timezone_box(['selected' => 'UTC'])
			->country_box(['selected' => 'US'])
		->tab_end()
		->tab_start('restrictions')
			->check_box('restricted_view', '', ['desc' => 'Ограничить просмотр (категорий +21)', 'no_label' => true])
			->check_box('restricted_view', 'Ограничить просмотр (категорий +21)')
			->currency_box(['selected' => 'RUB'])
		->tab_end()
		->tab_start('other')
			->icon_select_box(['selected' => 'icon-anchor'])
			->currency_box(['selected' => 'RUB'])
			->language_box(['selected' => 'uk'])
			->time_box()
			->date_box()
			->datetime_box()
			->birth_box()
		->tab_end()
		->save()
	;
};
