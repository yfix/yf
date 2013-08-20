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
/*
	function _hook_widget__static_pages_list ($params = array()) {
		$meta = array(
			'name' => 'Static pages quick access',
			'desc' => 'List of static pages with quick links to edit/preview',
			'configurable' => array(
				'order_by'	=> array('id','name','active'),
			),
		);
		if ($params['describe_self']) {
			return $meta;
		}
		$config = $params;
		$avail_orders = $meta['configurable']['order_by'];
		if (isset($avail_orders[$config['order_by']])) {
			$order_by_sql = ' ORDER BY '.db()->es($avail_orders[$config['order_by']].'');
		}
		$avail_limits = $meta['configurable']['limit'];
		if (isset($avail_limits[$config['limit']])) {
			$limit_records = (int)$avail_limits[$config['limit']];
		}
		$sql = "SELECT * FROM ".db('static_pages'). $order_by_sql;
		return common()->table2($sql, array('no_header' => 1, 'btn_no_text' => 1))
			->link("name", './?object='.$_GET['object'].'&action=view&id=%d', '', array('width' => '100%'))
			->btn_edit()
		;
	}
*/
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