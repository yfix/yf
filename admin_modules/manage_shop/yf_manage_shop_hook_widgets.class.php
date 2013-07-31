<?php
class yf_manage_shop_hook_widgets{
	function _hook_widget__new_products ($params = array()) {
		if ($params['describe_self']) {
			return array(
				'name'	=> 'Shop: new products',
				'desc'	=> 'List of latest products added to shop database',
				'configurable'	=> array(
					'in_stock'		=> array(true, false),
//					'top_category'	=> array_keys(),
				),
			);
		}
		return 'TODO';
// TODO
	}

	function _hook_widget__latest_sold_products ($params = array()) {
		if ($params['describe_self']) {
			return array(
				'name'	=> 'Shop: latest sold products',
				'desc'	=> 'List of latest sold products',
				'configurable'	=> array(
					'in_stock'		=> array(true, false),
				),
			);
		}
		return 'TODO';
// TODO
	}

	function _hook_widget__top_sold_products ($params = array()) {
		if ($params['describe_self']) {
			return array(
				'name'	=> 'Shop: most popular products',
				'desc'	=> 'List of most popular products',
				'configurable'	=> array(
					'in_stock'		=> array(true, false),
					'period'		=> array('minutely','hourly','daily','weekly','monthly')
				),
			);
		}
		return 'TODO';
// TODO
	}

	function _hook_widget__latest_orders ($params = array()) {
		if ($params['describe_self']) {
			return array(
				'name'	=> 'Shop: latest orders',
				'desc'	=> 'List of latest orders added to shop database',
				'configurable'	=> array(
					'period'		=> array('minutely','hourly','daily','weekly','monthly')
				),
			);
		}
		return 'TODO';
// TODO
	}

	function _hook_widget__top_customers ($params = array()) {
		if ($params['describe_self']) {
			return array(
				'name'	=> 'Shop: most active customers',
				'desc'	=> 'List of most active customers',
				'configurable'	=> array(
					'period'		=> array('minutely','hourly','daily','weekly','monthly')
				),
			);
		}
		return 'TODO';
// TODO
	}

	function _hook_widget__latest_customers ($params = array()) {
		if ($params['describe_self']) {
			return array(
				'name'	=> 'Shop: new customers',
				'desc'	=> 'List of latest customers, who bought something',
				'configurable'	=> array(
					'period'		=> array('minutely','hourly','daily','weekly','monthly')
				),
			);
		}
		return 'TODO';
// TODO
	}

	function _hook_widget__stats ($params = array()) {
		if ($params['describe_self']) {
			return array(
				'name'	=> 'Shop: overall stats',
				'desc'	=> 'Overall shop stats numbers',
				'configurable'	=> array(
					'period' => array('minutely','hourly','daily','weekly','monthly')
				),
			);
		}
		return 'TODO';
// TODO
	}
}