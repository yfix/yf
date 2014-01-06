<?php

class form2_tabs {
	function show() {
		return form($r)
			->tab_start('home')
				->icon_select_box(array('selected' => 'icon-anchor'))
				->currency_box(array('selected' => 'RUB'))
				->language_box(array('selected' => 'uk'))
				->timezone_box(array('selected' => 'UTC'))
				->country_box(array('selected' => 'US'))
			->tab_end()

			->tab_start('restrictions')
				->check_box( 'restricted_view', '', array( 'desc' => 'Ограничить просмотр (категорий +21)', 'no_label' => true ) )
				->check_box( 'restricted_view', 'Ограничить просмотр (категорий +21)' )
			->tab_end()

			->tab_start('other')
				->time_box()
				->date_box()
				->datetime_box()
				->birth_box()
			->tab_end()

			->save()
		;
	}
}
