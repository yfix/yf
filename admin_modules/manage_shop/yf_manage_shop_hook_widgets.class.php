<?php

class yf_manage_shop_hook_widgets{

	/**
	*/
	function _hook_widget__new_products ($params = array()) {
		$meta = array(
			'name'	=> 'Shop: new products',
			'desc'	=> 'List of latest products added to shop database',
			'configurable'	=> array(
//				'in_stock'		=> array(true, false),
//				'top_category'	=> array_keys(),
			),
		);
		if ($params['describe_self']) {
			return $meta;
		}
		$config = $params;
		$sql = 'SELECT * FROM '.db('shop_products').' ORDER BY add_date DESC';
		return table($sql, array('no_header' => 1, 'btn_no_text' => 1, 'no_records_simple' => 1, 'no_pages' => 1))
			->text('id', array('link' => '/shop/product/%d', 'rewrite' => 1))
			->text('name')
			->text('price')
			->btn_edit('', './?object=manage_shop&action=product_edit&id=%d')
		;
	}

	/**
	*/
	function _hook_widget__latest_sold_products ($params = array()) {
		$meta = array(
			'name'	=> 'Shop: latest sold products',
			'desc'	=> 'List of latest sold products',
			'configurable'	=> array(
				'in_stock'		=> array(true, false),
			),
		);
		if ($params['describe_self']) {
			return $meta;
		}
		$config = $params;
		$sql = 'SELECT p.* FROM '.db('shop_products').' AS p 
			INNER JOIN '.db('shop_order_items').' AS i ON i.product_id = p.id
			INNER JOIN '.db('shop_orders').' AS o ON o.id = i.order_id
			GROUP BY p.id
			ORDER BY o.date DESC';
		return table($sql, array('no_header' => 1, 'btn_no_text' => 1, 'no_records_simple' => 1, 'no_pages' => 1))
			->text('id', array('link' => '/shop/product/%d', 'rewrite' => 1))
			->text('name')
			->text('price')
			->btn_edit('', './?object=manage_shop&action=product_edit&id=%d')
		;
	}

	/**
	*/
	function _hook_widget__top_sold_products ($params = array()) {
		$meta = array(
			'name'	=> 'Shop: most popular products',
			'desc'	=> 'List of most popular products',
			'configurable'	=> array(
				'in_stock'		=> array(true, false),
				'period'		=> array('minutely','hourly','daily','weekly','monthly')
			),
		);
		if ($params['describe_self']) {
			return $meta;
		}
		$config = $params;
		$sql = 'SELECT p.*, COUNT(*) AS num FROM '.db('shop_products').' AS p 
			INNER JOIN '.db('shop_order_items').' AS i ON i.product_id = p.id
			INNER JOIN '.db('shop_orders').' AS o ON o.id = i.order_id
			GROUP BY p.id
			ORDER BY COUNT(*) DESC';
		return table($sql, array('no_header' => 1, 'btn_no_text' => 1, 'no_records_simple' => 1, 'no_pages' => 1))
			->text('id', array('link' => '/shop/product/%d', 'rewrite' => 1))
			->text('name')
			->text('price')
			->text('num')
			->btn_edit('', './?object=manage_shop&action=product_edit&id=%d')
		;
	}

	/**
	*/
	function _hook_widget__latest_orders ($params = array()) {
		$meta = array(
			'name'	=> 'Shop: latest orders',
			'desc'	=> 'List of latest orders added to shop database',
			'configurable'	=> array(
				'period'		=> array('minutely','hourly','daily','weekly','monthly')
			),
		);
		if ($params['describe_self']) {
			return $meta;
		}
		$config = $params;
		$sql = 'SELECT o.*, COUNT(*) AS num FROM '.db('shop_orders').' AS o INNER JOIN '.db('shop_order_items').' AS i ON i.order_id = o.id GROUP BY o.id ORDER BY o.`date` DESC';
		return table($sql, array('no_header' => 1, 'btn_no_text' => 1, 'no_records_simple' => 1, 'no_pages' => 1))
			->date('date')
			->text('name')
			->text('phone')
			->text('total_sum')
			->text('num')
#			->text('email')
			->btn_edit('', './?object=manage_shop&action=view_order&id=%d')
		;
	}

	/**
	*/
	function _hook_widget__top_customers ($params = array()) {
		$meta = array(
			'name'	=> 'Shop: most active customers',
			'desc'	=> 'List of most active customers',
			'configurable'	=> array(
				'period'		=> array('minutely','hourly','daily','weekly','monthly')
			),
		);
		if ($params['describe_self']) {
			return $meta;
		}
		$config = $params;
		$sql = 'SELECT u.*, COUNT(*) AS num, SUM(o.total_sum) AS total FROM '.db('user').' AS u
			INNER JOIN '.db('shop_orders').' AS o ON o.user_id = u.id
			GROUP BY u.id
			ORDER BY COUNT(*) DESC';
		return table($sql, array('no_header' => 1, 'btn_no_text' => 1, 'no_records_simple' => 1, 'no_pages' => 1))
			->text('name')
			->text('phone')
#			->text('login')
#			->text('email')
			->text('num')
			->text('total')
			->btn_edit('', './?object=manage_shop&action=user_edit&id=%d')
		;
	}

	/**
	*/
	function _hook_widget__latest_customers ($params = array()) {
		$meta = array(
			'name'	=> 'Shop: new customers',
			'desc'	=> 'List of latest customers, who bought something',
			'configurable'	=> array(
				'period'		=> array('minutely','hourly','daily','weekly','monthly')
			),
		);
		if ($params['describe_self']) {
			return $meta;
		}
		$config = $params;
		$sql = 'SELECT u.*, o.user_id FROM '.db('user').' AS u
			INNER JOIN '.db('shop_orders').' AS o ON o.user_id = u.id
			GROUP BY u.id
			ORDER BY u.add_date DESC';
		return table($sql, array('no_header' => 1, 'btn_no_text' => 1, 'no_records_simple' => 1, 'no_pages' => 1))
			->text('name')
			->text('phone')
			->text('email')
#			->text('login')
			->date('add_date')
			->btn_edit('', './?object=members&action=edit&id=%d')
		;
	}

	/**
	*/
	function _hook_widget__stats ($params = array()) {
		$meta = array(
			'name'	=> 'Shop: overall stats',
			'desc'	=> 'Overall shop stats numbers',
			'configurable'	=> array(
				'period' => array('minutely','hourly','daily','weekly','monthly')
			),
		);
		if ($params['describe_self']) {
			return $meta;
		}

		$config = $params;
		$sql = array(
			'SELECT "products total" AS `name`, COUNT(*) AS num FROM '.db('shop_products').'',
			'SELECT "products with images" AS `name`, COUNT(*) AS num FROM '.db('shop_products').' WHERE image > 1',
#			'SELECT "products images" AS `name`, COUNT(*) AS num FROM '.db('shop_products_images').'',
			'SELECT "products ordered" AS `name`, COUNT(*) AS num FROM (SELECT p.id FROM '.db('shop_products').' AS p INNER JOIN '.db('shop_order_items').' AS i ON i.product_id = p.id GROUP BY p.id) AS __tmp_products_ordered',
#			'SELECT "products total price" AS `name`, SUM(price) AS num FROM '.db('shop_products').'',
#			'SELECT "products in stock" AS `name`, COUNT(*) AS num FROM '.db('shop_products').' WHERE quantity > 0',
			'SELECT "customers count" AS `name`, COUNT(*) AS num FROM (SELECT u.id FROM '.db('user').' AS u INNER JOIN '.db('shop_orders').' AS o ON o.user_id = u.id GROUP BY u.id) AS __tmp_customers_number',
			'SELECT "orders count" AS `name`, COUNT(*) AS num FROM '.db('shop_orders').'',
			'SELECT "orders total amount" AS `name`, SUM(total_sum) AS num FROM '.db('shop_orders').' AS o',
		);
		$sql = '('.implode(') UNION ALL (', $sql).')';
		$data = db()->get_all($sql);
		foreach ((array)$data as $k => $v) {
			$data[$k]['num'] = intval($v['num']);
		}
		return table($data, array('no_header' => 1, 'btn_no_text' => 1, 'no_records_simple' => 1, 'no_pages' => 1))
			->text('name', '', array('width' => '100%'))
			->text('num')
		;
	}
}