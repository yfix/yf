<?php

class yf_shop_supplier_panel_orders {

	/**
	*/
	function orders() {
		$SUPPLIER_ID = module('shop_supplier_panel')->SUPPLIER_ID;

		$sql = 'SELECT o.*, COUNT(*) AS num_items 
				FROM '.db('shop_orders').' AS o 
				INNER JOIN '.db('shop_order_items').' AS i ON i.order_id = o.id 
				INNER JOIN '.db('shop_products').' AS p ON i.product_id = p.id 
				INNER JOIN '.db('shop_admin_to_supplier').' AS m ON m.supplier_id = p.supplier_id 
				WHERE 
					m.admin_id='.intval(main()->ADMIN_ID).'
				GROUP BY o.id';

		$filter_name = $_GET['object'].'__orders';
		return table2($sql, array(
				'filter' => $_SESSION[$filter_name],
			))
			->text('id')
			->date('date')
			->user('user_id')
			->text('total_sum')
			->text('num_items')
			->btn_edit('', './?object='.$_GET['object'].'&action=order_view&id=%d')
		;
	}

	/**
	*/
	function order_view() {
		$SUPPLIER_ID = module('shop_supplier_panel')->SUPPLIER_ID;

		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$sql = 'SELECT o.* FROM '.db('shop_orders').' AS o
					INNER JOIN '.db('shop_order_items').' AS i ON i.order_id = o.id 
					INNER JOIN '.db('shop_products').' AS p ON i.product_id = p.id 
					INNER JOIN '.db('shop_admin_to_supplier').' AS m ON m.supplier_id = p.supplier_id 
					WHERE 
						o.id='.intval($_GET['id']).'
						AND m.admin_id='.intval(main()->ADMIN_ID).'
					GROUP BY o.id';
			$order_info = db()->query_fetch($sql);
		}
		if (empty($order_info)) {
			return _e('No such order');
		}
		if (!empty($_POST['status'])) {
			db()->UPDATE(db('shop_orders'), _es(array(
				'status'	=> $_POST['status'],
				'comment_m'	=> $_POST['comment_m'],
				'comment_c'	=> $_POST['comment_c'],
				'address'	=> $_POST['address'],
				'phone'		=> $_POST['phone'],
			)), 'id='.intval($_GET['id']));
			return js_redirect('./?object='.$_GET['object'].'&action=orders');
		}
		$products_ids = array();
		$sql = 'SELECT i.* FROM '.db('shop_order_items').' AS i 
				INNER JOIN '.db('shop_products').' AS p ON i.product_id = p.id 
				INNER JOIN '.db('shop_admin_to_supplier').' AS m ON m.supplier_id = p.supplier_id 
				WHERE 
					i.order_id='.intval($order_info['id']).'
					AND m.admin_id='.intval(main()->ADMIN_ID).'';
		$Q = db()->query($sql);
		while ($_info = db()->fetch_assoc($Q)) {
			if ($_info['product_id']) {
				$products_ids[$_info['product_id']] = $_info['product_id'];
			}
			$order_items[$_info['product_id']] = $_info;
		}
		if (!empty($products_ids)) {
			$sql = 'SELECT p.* FROM '.db('shop_products').' AS p 
					WHERE p.id IN('.implode(',', $products_ids).') 
						AND p.supplier_id='.(int)$SUPPLIER_ID.'';
			$products_infos = db()->query_fetch_all($sql);
			$products_atts	= module('manage_shop')->_get_products_attributes($products_ids);
		}
		foreach ((array)$order_items as $_info) {
			$_product = $products_infos[$_info['product_id']];
			$dynamic_atts = array();
			if (strlen($_info['attributes']) > 3) {
				foreach ((array)unserialize($_info['attributes']) as $_attr_id) {
					$_attr_info = $products_atts[$_info['product_id']][$_attr_id];
					$dynamic_atts[$_attr_id] = '- '.$_attr_info['name'].' '.$_attr_info['value'];
					$price += $_attr_info['price'];
				}
			}
			$products[$_info['product_id']] = array(
				'product_id'	=> intval($_info['product_id']),
				'name'			=> _prepare_html($_product['name']),
				'price'			=> module('manage_shop')->_format_price($_info['sum']),
				'currency'		=> _prepare_html(module('manage_shop')->CURRENCY),
				'quantity'		=> intval($_info['quantity']),
				'details_link'	=> process_url('./?object='.$_GET['object'].'&action=view&id='.$_product['id']),
				'dynamic_atts'	=> !empty($dynamic_atts) ? implode('<br />'.PHP_EOL, $dynamic_atts) : '',
			);
			$total_price += $_info['price'] * $quantity;
		}
		$total_price = $order_info['total_sum'];
		$replace = my_array_merge($replace, _prepare_html($order_info));
		$replace = my_array_merge($replace, array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'order_id'		=> $order_info['id'],
			'total_sum'		=> module('manage_shop')->_format_price($order_info['total_sum']),
			'user_link'		=> _profile_link($order_info['user_id']),
			'user_name'		=> _display_name(user($order_info['user_id'])),
			'error_message'	=> _e(),
			'products'		=> (array)$products,
			'total_price'	=> module('manage_shop')->_format_price($total_price),
			'ship_type'		=> module('manage_shop')->_ship_types[$order_info['ship_type']],
			'pay_type'		=> module('manage_shop')->_pay_types[$order_info['pay_type']],
			'date'			=> _format_date($order_info['date'], 'long'),
			'status_box'	=> module('manage_shop')->_box('status', $order_info['status']),
			'back_url'		=> './?object='.$_GET['object'].'&action=orders',
		));
		return form2($replace)
			->info('id')
			->info('total_sum', '', array('no_escape' => 1))
			->info('date')
			->user_info('user_id')
			->container(
				table($products)
					->link('product_id', './?object='.$_GET['object'].'&action=product_edit&id=%d')
					->text('quantity')
					->text('price')
					->text('name')
				, array('wide' => 1)
			)
			->box('status_box', 'Status', array('selected' => $order_info['status']))
			->save_and_back()
		;
		return $form;
	}
}