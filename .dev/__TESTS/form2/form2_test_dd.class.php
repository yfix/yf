<?php

class form2_test_dd {
	function show() {
		$a = array (
			'id' => '2', 'type' => 'ask', 'user_id' => '4', 'currency' => '1', 'amount' => '1.00', 'payments_period' => '1', 'percent' => '1', 
			'percents_period' => '1', 'split_period' => 'd', 'frequency_payments' => 'w', 'descr' => '', 'title' => 'test', 'duration' => '3024000', 
			'min_user_rating' => '0', 'add_date' => '1383137996', 'edit_date' => '1383213181', 'end_date' => '0', 'status' => '1', 
		);
		if (empty($a)){ 
			return false;
		}
		$offer_status = array(
			'0'	   => 'closed',
			'1'    => 'available'	
		);
		$currencies = array ( 1 => 'USD', 2 => 'EUR', 3 => 'OM', 4 => 'GBP', 5 => 'CHF');
		$offer_status = array ( 0 => 'closed', 1 => 'Доступно');

		$r = array(
			'title'			      => $a['title'],
			'descr'			      => $a['descr'],
			'type'			      => t($a['type']),
			'currency'		      => isset($currencies[$a['currency']]) ? $currencies[$a['currency']] : '',
			'amount'		      => $a['amount'],
			'duration'		      => $a['duration'],
			'percent'		      => $a['percent'],
			'percents_period'	  => $a['percents_period'],
			'payments_period'	  => $a['payments_period'],
			'min_user_rating'	  => $a['min_user_rating'],
			'split_period'	      => $a['split_period'],
			'frequency_payments'  => $a['frequency_payments'],
			'add_date'            => date('d.m.Y', $a['add_date']),
			'end_date'            => !empty($a['end_date']) ? date('d.m.Y', $a['end_date']) : t('Not stated'),
			'status'              => t(isset($offer_status[$a['status']]) ? $offer_status[$a['status']] : $offer_status[0]),
			'custom_html'	      => !empty($custom_html) ? $custom_html: false,
			'url_owner_profile'	  => $url_owner_profile,
			'url_offers'          => 'Offers',
			'stars'				  => 4.5,
			'stars2'			  => 4.5,
		);
		return _class('html')->dd_table($r, array(
			'type' => array('func' => 'info', 'label' => 'info'),
			'currency' => array('func' => 'link', 'label' => 'info', 'link' => './?object=currency'),
			'descr' => '', // Remove row
			'custom_html' => '', // Remove row
			'url_owner_profile' => array('func' => ''), // Remove row
			'url_offers' => array(
				'func' => 'link',
				'label' => 'info',
				'desc' => 'offers',
				'link' => './?object=offers&action=user_offers&id=1',
#				'display_func' => function($row){ return rand(0,1); }
			),
			'stars' => 'stars',
			'stars2' => array('func' => 'stars', 'desc' => 'stars', 'max' => 10, 'stars' => 10, 'color_ok' => 'red'),
		), array(
			'legend' => $r['title'],
		));
	}
}

