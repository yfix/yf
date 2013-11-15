<?php

class form2_test_dd {
	function show() {
		$_GET['id'] = 2;
		$user_id = intval(main()->USER_ID);
		$offer_id = isset($_GET['id']) ? intval($_GET['id']) : $id;
		$a = db()->get('SELECT * FROM `'.db('offers').'` WHERE `id`='.$offer_id);
		if (empty($a)){ 
			return false;
		}
		$this->_offer_status = array(
			'0'	   => 'closed',
			'1'    => 'available'	
		);
		$this->currencies = common()->_get_currency();
		$offer_status = common()->get_static_conf('offer_status');
		$r = array(
			'title'			      => $a['title'],
			'descr'			      => $a['descr'],
			'type'			      => t($a['type']),
			'currency'		      => isset($this->currencies[$a['currency']]) ? $this->currencies[$a['currency']] : '',
			'amount'		      => $a['amount'],
			'duration'		      => common()->parse_duration($a['duration'], true),
			'percent'		      => $a['percent'],
			'percents_period'	  => $a['percents_period'],
			'payments_period'	  => $a['payments_period'],
			'min_user_rating'	  => $a['min_user_rating'],
			'split_period'	      => common()->get_static_conf('split_period', $a['split_period']),
			'frequency_payments'  => common()->get_static_conf('frequency_payments', $a['frequency_payments']),
			'add_date'            => date('d.m.Y', $a['add_date']),
			'end_date'            => !empty($a['end_date']) ? date('d.m.Y', $a['end_date']) : t('Not stated'),
			'status'              => t(isset($offer_status[$a['status']]) ? $offer_status[$a['status']] : $offer_status[0]),
			'custom_html'	      => !empty($custom_html) ? $custom_html: false,
			'url_owner_profile'	  => $url_owner_profile, 
		);
		return _class('html')->dd_table($r, array(
			'type' => array('func' => 'info', 'label' => 'info'),
			'currency' => array('func' => 'link', 'label' => 'info', 'link' => './?object=currency'),
			'descr' => '', // Remove row
			'custom_html' => '', // Remove row
			'url_owner_profile' => array('func' => ''), // Remove row
		), array(
			'legend' => $r['title'],
		));
	}
}

