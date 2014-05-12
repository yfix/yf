<?php

class form2_new_controls {
	function show() {
		$r['count_offers'] = 1;
		return form($r)
			->hidden('hdn')

			->icon_select_box(array('selected' => 'icon-anchor'))
			->icon_select_box(array('selected' => 'icon-anchor', 'row_tpl' => '%name %icon'))
			->currency_box(array('selected' => 'RUB'))
			->currency_box(array('selected' => 'RUB', 'row_tpl' => '%code %name %sign', 'renderer' => 'div_box'))
			->language_box(array('selected' => 'uk'))
			->language_box(array('selected' => 'uk', 'row_tpl' => '%name %icon %code', 'renderer' => 'div_box'))
			->timezone_box(array('selected' => 'UTC'))
			->timezone_box(array('selected' => 'UTC', 'row_tpl' => '%name %code %offset', 'renderer' => 'div_box'))

			->country_box(array('selected' => 'US'))
			->country_box(array('selected' => 'US', 'renderer' => 'select2_box'))
			->country_box(array('selected' => array('US'=>'US','ES'=>'ES'), 'renderer' => 'select2_box', 'multiple' => 1, 'js_options' => array('width' => '400px', 'allowClear' => 'true')))
			->country_box(array('selected' => 'US', 'renderer' => 'chosen_box'))
			->country_box(array('selected' => 'US', 'renderer' => 'chosen_box', 'multiple' => 1))
			->country_box(array('selected' => 'US', 'renderer' => 'select_box'))
			->country_box(array('selected' => 'US', 'renderer' => 'multi_select_box'))
			->country_box(array('selected' => 'US', 'renderer' => 'multi_check_box'))
			->country_box(array('selected' => 'US', 'renderer' => 'radio_box'))
			->country_box(array('selected' => 'US', 'renderer' => 'radio_box', 'row_tpl' => '%name %icon'))
			->country_box(array('selected' => 'US', 'renderer' => 'div_box'))
			->country_box(array('selected' => 'US', 'renderer' => 'button_box'))
			->country_box(array('selected' => 'US', 'renderer' => 'button_split_box'))

			->check_box( 'restricted_view', 'Ограничить просмотр (категорий +21)' )
			->check_box( 'restricted_view', '', array( 'desc' => 'Ограничить просмотр (категорий +21)', 'no_label' => true ) )

			->region_box()
			->city_box()
			->image()

			->datetime_select('add_date')
			->datetime_select('add_date__and')

			->time_box()
			->date_box()
			->datetime_box()
			->birth_box()

			->user_method_box(array('desc' => 'user method'))
			->admin_method_box(array('desc' => 'admin method'))
			->user_template_box(array('desc' => 'user template'))
			->admin_template_box(array('desc' => 'admin template'))
			->user_location_box(array('desc' => 'user location'))
			->admin_location_box(array('desc' => 'admin location'))

#			->link('count_offers', './?object=manage_shop&action=product_edit&id=%d',array('desc'=>'ffgd'))
			->stars_select('stars')

			->captcha()
			->save()
		;
	}
}
