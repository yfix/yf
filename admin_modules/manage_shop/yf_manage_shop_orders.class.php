<?php
class yf_manage_shop_orders{
	
	var $delivery_price = 30;

	/**
	*/
	function orders_manage() {
		return $this->show_orders();
	}

	/**
	*/
	function show_orders() {
		$sql = 'SELECT o.*, COUNT(*) AS num_items 
				FROM '.db('shop_orders').' AS o 
				INNER JOIN '.db('shop_order_items').' AS i ON i.order_id = o.id 
				GROUP BY o.id ORDER BY o.id DESC';
		return table($sql, array(
				'filter' => $_SESSION[$_GET['object'].'__orders']
			))
			->text('id')
			->date('date', array('format' => '%d-%m-%Y'))
			->user('user_id')
			->text('total_sum')
			->text('num_items')
			->func('status', function($field, $params) { 
				return common()->get_static_conf("order_status", $field);
			})
			->btn_edit('', './?object=manage_shop&action=view_order&id=%d',array('no_ajax' => 1))
			->btn('Paywill', './?object=paywill&id=%d',array('no_ajax' => 1))
		;
	}

	/**
	*/
	function view_order() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$order_info = db()->query_fetch('SELECT * FROM '.db('shop_orders').' WHERE id='.intval($_GET['id']));
		}
		if (empty($order_info)) {
			return _e('No such order');
		}
		if(!empty($_POST)){
			foreach($_POST as $k => $v) {
				if(is_int($k)) {
					db()->UPDATE(db('shop_order_items'), array('status'	=> $v), ' order_id='.$_GET['id'].' AND product_id='.intval($k));
				} elseif ($k=='qty') {
					foreach ($v as $product_id => $qty) {
						if (intval($qty) == 0) {
							db()->DELETE(db('shop_order_items'), ' order_id='.$_GET['id'].' AND product_id='.intval($product_id));
						} else {
							db()->UPDATE(db('shop_order_items'), array('quantity'	=> intval($qty)), ' order_id='.$_GET['id'].' AND product_id='.intval($product_id));
						}
						
						
						$total_price = 0;
						$Q = db()->query('SELECT * FROM '.db('shop_order_items').' WHERE `order_id`='.intval($order_info['id']));
						while ($_info = db()->fetch_assoc($Q)) {
							$total_price += $_info['quantity']*$_info['price'];
						}
						
						$delivery_price = ($order_info['region'] == 7)? ((intval($total_price) < 200)? $this->delivery_price : intval(0)) : NULL;
						$total_price += intval($delivery_price);
//									'delivery_price' => ($delivery_price !== NULL)?  $delivery_price : '',

						$order_info['total_sum']  = $total_price;
						$order_info['delivery_price'] = $delivery_price;
						
						db()->UPDATE(db('shop_orders'), array('total_sum' => $order_info['total_sum'],'delivery_price' => $order_info['delivery_price']),"`id`='".$_GET['id']."'");
						
					}
				}
			}
		}
		if (!empty($_POST['status'])) {
			db()->UPDATE(db('shop_orders'), array(
				'status'	=> _es($_POST['status']),
				'address'	=> _es($_POST['address']),
				'phone'		=> _es($_POST['phone']),
			), 'id='.intval($_GET['id']));
			return js_redirect('./?object=manage_shop&action=show_orders');
		}
		$products_ids = array();
		$Q = db()->query('SELECT * FROM '.db('shop_order_items').' WHERE `order_id`='.intval($order_info['id']));
		while ($_info = db()->fetch_assoc($Q)) {
			if ($_info['product_id']) {
				$products_ids[$_info['product_id']] = $_info['product_id'];
			}
			$order_items[$_info['product_id']] = $_info;
		}
		if (!empty($products_ids)) {
			$products_infos = db()->query_fetch_all('SELECT * FROM '.db('shop_products').' WHERE id IN('.implode(',', $products_ids).') AND active="1"');
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
				'price'			=> module('manage_shop')->_format_price($_info['quantity']*$_info['price']),
				'currency'		=> _prepare_html(module('manage_shop')->CURRENCY),
				'quantity'		=> intval($_info['quantity']),
				'details_link'	=> process_url('./?object=manage_shop&action=view&id='.$_product['id']),
				'dynamic_atts'	=> !empty($dynamic_atts) ? implode('<br />'.PHP_EOL, $dynamic_atts) : '',
				'status'		=> module('manage_shop')->_box('status_item', $_info['status']),
			);
			$total_price += $_info['price'] * $quantity;
		}
		$total_price = $order_info['total_sum'];
		$replace = my_array_merge($replace, _prepare_html($order_info));
		$replace = my_array_merge($replace, array(
			'form_action'	=> './?object=manage_shop&action='.$_GET['action'].'&id='.$_GET['id'],
			'order_id'		=> $order_info['id'],
			'total_sum'		=> module('manage_shop')->_format_price($order_info['total_sum']),
			'user_link'		=> _profile_link($order_info['user_id']),
			'user_name'		=> _display_name(user($order_info['user_id'])),
			'error_message'	=> _e(),
			'products'		=> (array)$products,
			'total_price'	=> module('manage_shop')->_format_price($total_price),
			'ship_type'		=> module('manage_shop')->_ship_types[$order_info['ship_type']],
			'pay_type'		=> module('manage_shop')->_pay_types[$order_info['pay_type']],
			'date'			=> _format_date($order_info['date'], '%d-%m-%Y'),
			'status_box'	=> module('manage_shop')->_box('status', $order_info['status']),
			'back_url'		=> './?object=manage_shop&action=show_orders',
			'print_url'		=> './?object=manage_shop&action=show_print&id='.$order_info['id'],
			'payment'		=> common()->get_static_conf('payment_methods', $order_info['payment']),
		));
		return form2($replace)
			->info('id')
			->info('total_sum', '', array('no_escape' => 1))
			->info('date')
			->info('name')
			->email('email')
			->info('phone')
			->container('<a href="./?object=manage_shop&action=send_sms&phone='.urlencode($replace["phone"]).'">Send SMS</a><br /><br />')
			->info('address')
			->info('house')
			->info('apartment')
			->info('floor')
			->info('porch')
			->info('intercom')
			->info('comment')
			->user_info('user_id')
			->info('payment', 'Payment method')
			->container(
				table2($products)
					->link('product_id', './?object=manage_shop&action=product_edit&id=%d')
					->func('quantity',function($f, $p, $row){
						$row['quantity'] = "<input type='text' name='qty[".$row['product_id']."]' value='".intval($row['quantity'])."' style='width:50px;'>";
						return $row['quantity'];
					})
					->text('price')
					->text('name')
					->func('status', function($f, $p, $row){
						$row['status'] = str_replace("status_item", $row['product_id'], $row['status']);
						return $row['status'];
					})
				, array('wide' => 1)
			)
			->box('status_box', 'Status order', array('selected' => $order_info['status']))
			->save_and_back()
		;
		return $form;		
	}

	/**
	*/
	function delete_order() {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$order_info = db()->query_fetch('SELECT * FROM '.db('shop_orders').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($order_info['id'])) {
			db()->query('DELETE FROM '.db('shop_orders').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			db()->query('DELETE FROM '.db('shop_order_items').' WHERE `order_id`='.intval($_GET['id']));
			common()->admin_wall_add(array('shop order deleted: '.$_GET['id'], $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			$_GET['id'];
		} else {
			return js_redirect('./?object=manage_shop&action=show_orders');
		}
	}
	
}