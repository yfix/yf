<?php

class form2_complex {
	function show() {
		return form($replace, array(
			'tabs'	=> array(
				'class' => 'span6 col-md-6',
				'show_all' => 1,
				'no_headers' => 1,
			),
		))
		->fieldset_start(array('id' => 'fieldset1', 'legend' => 'Part 1', 'class' => 'well'))
			->tab_start('main')
				->text('name')
				->text('url')
				->textarea('description')
				->number('quantity', array('min' => 0))
				->active_box('active')
			->tab_end()
			->tab_start('prices')
				->price('old_price')
				->price('price')
				->price('price_promo')
				->price('price_partner')
				->row_start(array('desc' => 'Price raw'))
					->number('price_raw11')
					->container('and')
					->number('price_raw12')
				->row_end()
			->tab_end()
		->fieldset_end()
		->fieldset_start(array('id' => 'fieldset2', 'legend' => 'Part 2', 'class' => 'well'))
			->text('name2')
			->text('url2')
			->row_start(array('desc' => 'Prices'))
				->price('old_price2')
				->price('price2')
				->price('price_promo2')
				->price('price_partner2')
				->price('price_raw2')
			->row_end()
			->textarea('description2')
		->fieldset_end()
		->save_and_back()
		;
	}
}
