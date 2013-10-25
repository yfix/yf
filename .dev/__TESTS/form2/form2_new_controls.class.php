<?php

class form2_new_controls {
	function show() {
		$r['count_offers'] = 1;
		return form($r)
			->icon_select_box(array('selected' => 'icon-anchor'))
			->currency_box(array('selected' => 'RUB'))
			->language_box(array('selected' => 'uk'))
			->timezone_box(array('selected' => 'UTC'))
			->country_box(array('selected' => 'US'))

			->check_box( 'restricted_view', '', array( 'desc' => 'Ограничить просмотр (категорий +21)', 'no_label' => true ) )
			->check_box( 'restricted_view', 'Ограничить просмотр (категорий +21)' )

			->region_box() // TODO
			->image()
			->time_box()
			->date_box()
			->datetime_box()
			->birth_box()
//			->navbar_start()->div_box('testdiv1', array('val1','val2'))->navbar_end()
//			->navbar_start()->div_box('testdiv2', array('val1','val2'))->navbar_end()
			->user_method_box(array('desc' => 'user method'))
			->admin_method_box(array('desc' => 'admin method'))
			->user_template_box(array('desc' => 'user template'))
			->admin_template_box(array('desc' => 'admin template'))
			->user_location_box(array('desc' => 'user location'))
			->admin_location_box(array('desc' => 'admin location'))

			->link('count_offers', './?object=manage_shop&action=product_edit&id=%d',array('desc'=>'ffgd'))

			->save()
		;
	}
}
