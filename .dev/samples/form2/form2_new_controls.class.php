<?php

class form2_new_controls {

	function show() {
		$names = array();
		foreach (get_class_methods($this) as $name) {
			if (substr($name, 0, 1) === '_' || $name === 'show') {
				continue;
			}
			$names[$name] = $name;
		}
		$links = array();
		foreach ($names as $name) {
			$links[] = '<li><a href="'.url('/@object/'.$name).'">'.t($name).'</a></li>';
		}
		return implode(PHP_EOL, $links);
	}

	function captcha() {
		return form($r)
			->captcha()
		;
	}

	function icon_select_box() {
		return form($r)
			->icon_select_box(array('selected' => 'icon-anchor'))
			->icon_select_box(array('selected' => 'icon-anchor', 'row_tpl' => '%name %icon'))
		;
	}

	function currency_box() {
		return form($r)
			->currency_box(array('selected' => 'RUB'))
			->currency_box(array('selected' => 'RUB', 'row_tpl' => '%code %name %sign', 'renderer' => 'div_box'))
		;
	}

	function language_box() {
		return form($r)
			->language_box(array('selected' => 'uk'))
			->language_box(array('selected' => 'uk', 'row_tpl' => '%name %icon %code', 'renderer' => 'div_box'))
		;
	}

	function timezone_box() {
		return form($r)
			->timezone_box(array('selected' => 'UTC'))
			->timezone_box(array('selected' => 'UTC', 'row_tpl' => '%name %code %offset', 'renderer' => 'div_box'))
		;
	}

	function country_box() {
		return form($r)
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
		;
	}

	function region_box() {
		return form($r)
			->region_box()
		;
	}

	function city_box() {
		return form($r)
			->city_box()
		;
	}

	function check_box() {
		return form($r)
			->check_box( 'restricted_view', 'Ограничить просмотр (категорий +21)' )
			->check_box( 'restricted_view', '', array( 'desc' => 'Ограничить просмотр (категорий +21)', 'no_label' => true ) )
		;
	}

	function datetime_boxes() {
		return form($r)
			->datetime_select('add_date')
			->datetime_select('add_date__and')

			->time_box()
			->date_box()
			->datetime_box()
			->birth_box()
		;
	}

	function admin_boxes() {
		return form($r)
			->user_method_box(array('desc' => 'user method'))
			->admin_method_box(array('desc' => 'admin method'))
			->user_template_box(array('desc' => 'user template'))
			->admin_template_box(array('desc' => 'admin template'))
			->user_location_box(array('desc' => 'user location'))
			->admin_location_box(array('desc' => 'admin location'))
		;
	}

	function _hook_side_column() {
		$names = array();
		foreach (get_class_methods($this) as $name) {
			if (substr($name, 0, 1) === '_' || $name === 'show') {
				continue;
			}
			$names[$name] = $name;
		}
		$links = array();
		foreach ($names as $name) {
			$links[url('/@object/'.$name)] = t($name);
		}
		return html()->navlist($links);
	}
}
