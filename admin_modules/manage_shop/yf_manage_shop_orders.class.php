<?php
class yf_manage_shop_orders{

	/**
	*/
	function _init() {
		$this->SUPPLIER_ID = module('manage_shop')->SUPPLIER_ID;
	}

	/**
	*/
	function orders_manage() {
		return $this->show_orders();
	}

	/**
	*/
	function show_orders() {
		if ($this->SUPPLIER_ID) {
			$sql = 'SELECT o.*, COUNT(*) AS num_items
					FROM '.db('shop_orders').' AS o
					INNER JOIN '.db('shop_order_items').' AS i ON i.order_id = o.id
					INNER JOIN '.db('shop_products').' AS p ON i.product_id = p.id
					INNER JOIN '.db('shop_admin_to_supplier').' AS m ON m.supplier_id = p.supplier_id
					WHERE m.admin_id='.intval(main()->ADMIN_ID).' /*FILTER*/
					GROUP BY o.id /*ORDER*/'; // ORDER BY o.id DESC
		} else {
			$sql = 'SELECT o.*, COUNT(*) AS num_items
					FROM '.db('shop_orders').' AS o
					INNER JOIN '.db('shop_order_items').' AS i ON i.order_id = o.id
					WHERE 1 /*FILTER*/
					GROUP BY o.id /*ORDER*/'; //  ORDER BY o.id DESC
		}
		$filter = $_SESSION[$_GET['object'].'__orders'];
		if (!$filter['order_by']) {
			$filter['order_by'] = 'id';
			$filter['order_direction'] = 'desc';
		}
		return table($sql, array(
				'filter' => $filter,
				'filter_params' => array(
					'id'		=> array('between','o.id'),
					'status'	=> array('eq','o.status'),
					'name'		=> array('like','o.name'),
					'phone'		=> array('like','o.phone'),
					'email' 	=> array('like','o.phone'),
					'user_id'	=> array('eq','o.user_id'),
					'date'		=> array('dt_between', 'o.date'),
					'total_sum' => array('between','o.total_sum'),
				),
				'hide_empty' => 1,
			))
			->text('id')
			->date('date', array('format' => 'full', 'nowrap' => 1))
			->user('user_id')
			->text('name')
			->text('phone')
			->text('total_sum', array('nowrap' => 1))

			->text('num_items')
			->func('status', function($field, $params) {
				return common()->get_static_conf('order_status', $field);
			}, array('nowrap' => 1))
			->btn_edit('', './?object='.main()->_get('object').'&action=view_order&id=%d',array('no_ajax' => 1))
			->btn('Paywill', './?object=manage_shop&action=paywill&id=%d',array('no_ajax' => 1, 'target' => '_blank'))
			->btn('PDF', './?object=manage_shop&action=paywill&id=%d&pdf=y',array('no_ajax' => 1, 'target' => '_blank'))
		;
	}

	/**
	*/
	function view_order() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			if ($this->SUPPLIER_ID) {
				$sql = 'SELECT o.* FROM '.db('shop_orders').' AS o
						INNER JOIN '.db('shop_order_items').' AS i ON i.order_id = o.id
						INNER JOIN '.db('shop_products').' AS p ON i.product_id = p.id
						INNER JOIN '.db('shop_admin_to_supplier').' AS m ON m.supplier_id = p.supplier_id
						WHERE
							o.id='.intval($_GET['id']).'
							AND m.admin_id='.intval(main()->ADMIN_ID).'
						GROUP BY o.id';
			} else {
				$sql = 'SELECT * FROM '.db('shop_orders').' WHERE id='.intval($_GET['id']);
			}
			$order_info = db()->query_fetch($sql);
		}
		if (empty($order_info)) {
			return _e('No such order');
		}
		$recount_price = false;
		if(main()->is_post()) {
			module('manage_shop')->_product_check_first_revision('order', intval($_GET['id']));
			foreach($_POST as $k => $v) {
				if($k == 'status_item') {
					foreach ($v as $k1 => $status) {
						list ($product_id,$param_id) = explode('_',$k1);
						db()->UPDATE(db('shop_order_items'), array('status'	=> $status), ' order_id='.$_GET['id'].' AND product_id='.intval($product_id).' AND param_id='.intval($param_id));
					}
				} elseif ($k=='delete') {
					foreach ($v as $k1 => $is_del) {
						list ($product_id,$param_id) = explode('_',$k1);
						if ($is_del == 1) {
							db()->query('DELETE FROM '.db('shop_order_items').' WHERE order_id='.$_GET['id'].' AND product_id='.intval($product_id).' AND param_id='.intval($param_id));
						}
					}
					$recount_price = true;
				} elseif ($k=='qty') {
					foreach ($v as $k1 => $qty) {
						list ($product_id,$param_id) = explode('_',$k1);
						if (intval($qty) == 0) {
							db()->query('DELETE FROM '.db('shop_order_items').' WHERE order_id='.$_GET['id'].' AND product_id='.intval($product_id).' AND param_id='.intval($param_id));
						} else {
							db()->UPDATE(db('shop_order_items'), array('quantity'	=> intval($qty)), ' order_id='.$_GET['id'].' AND product_id='.intval($product_id).' AND param_id='.intval($param_id));
						}

						$recount_price = true;
					}
				} elseif ($k=='price_unit') {
					foreach ($v as $k1 => $price) {
						list ($product_id,$param_id) = explode('_',$k1);
						db()->UPDATE(db('shop_order_items'), array('price'	=> $price), ' order_id='.$_GET['id'].' AND product_id='.intval($product_id).' AND param_id='.intval($param_id));
						$recount_price = true;
					}
				}
			}

			$sql = array();
			foreach (array('address','phone','address','house','apartment','floor','porch','intercom','delivery_price','status','discount') as $f) {
				if (isset($_POST[$f])) {
					$sql[$f] = $_POST[$f];
					if (($f == 'delivery_price') && ($_POST['delivery_price'] != $order_info['delivery_price'])) {
						$sql['is_manual_delivery_price'] = 1;
						$order_info['is_manual_delivery_price'] = 1;
						$order_info['delivery_price'] = $sql['delivery_price'];
						$recount_price = true;
					}
					if( $f == 'discount' ) {
						$order_info['discount'] = $sql['discount'];
						$recount_price = true;
					}
				}
			}
			if (count($sql)>0) {
				db()->update_safe(db('shop_orders'), $sql, 'id='.intval($_GET['id']));
			}
			if ($recount_price) {
				list($order_info['total_sum'], $order_info['delivery_price']) = $this->_order_recount_price($order_info['id'],$order_info);
			}

			module('manage_shop')->_order_add_revision('edit', intval($_GET['id']));

			return js_redirect('./?object='.main()->_get('object').'&action=view_order&id='.$order_info['id']);
		}

		$products_ids = array();
		$Q = db()->query('SELECT * FROM '.db('shop_order_items').' WHERE `order_id`='.intval($order_info['id']));
		while ($_info = db()->fetch_assoc($Q)) {
			if ($_info['product_id']) {
				$products_ids[$_info['product_id']] = $_info['product_id'];
			}
			$order_items[$_info['product_id']."_".$_info['param_id']] = $_info;
		}
		if (!empty($products_ids)) {
			$products_infos = db()->query_fetch_all('SELECT * FROM '.db('shop_products').' WHERE id IN('.implode(',', $products_ids).')');
			$products_atts	= module('manage_shop')->_get_products_attributes($products_ids);
		}
		$price_total = 0;
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
			$price_one  = (float)$_info[ 'price'    ];
			$quantity   = (int)$_info[ 'quantity' ];
			$price_item = $price_one * $quantity;
			$products[$_info['product_id'].'_'.$_info['param_id']] = array(
				'product_id'   => intval($_info['product_id']),
				'param_id'     => intval($_info['param_id']),
				'param_name'   => _class( '_shop_product_params', 'modules/shop/' )->_get_name_by_option_id($_info['param_id']),
				'name'         => _prepare_html($_product['name']),
				'price_unit'   => $price_one,
				'price'        => $price_item,
				'currency'     => _prepare_html(module('manage_shop')->CURRENCY),
				'quantity'     => intval($_info['quantity']),
				'details_link' => process_url('./?object='.main()->_get('object').'&action=view&id='.$_product['id']),
				'dynamic_atts' => !empty($dynamic_atts) ? implode('<br />'.PHP_EOL, $dynamic_atts) : '',
				'status'       => module('manage_shop')->_box('status_item', $_info['status']),
				'delete'       => '', // will be filled later on table2()
			);
			$price_total += $price_item;
		}
		$discount = $order_info[ 'discount' ];
		// $_class_discount = _class( '_shop_discount', 'modules/shop/' );
		// $discount_price  = $_class_discount->calc_discount_global( $price_total, $discount );
		$_class_price = _class( '_shop_price', 'modules/shop/' );
		$discount_price = $_class_price->apply_price( $price_total, $discount );
		$discount_price -= $price_total;
		$total_price     = (float)$order_info['total_sum'];
		$replace = my_array_merge($replace, _prepare_html($order_info));
		$replace = my_array_merge($replace, array(
			'form_action'         => './?object='.main()->_get('object').'&action='.$_GET['action'].'&id='.$_GET['id'],
			'order_id'            => $order_info['id'],
			'price_total_info'    => module('manage_shop')->_format_price( $price_total ),
			'discount'            => $discount,
			'discount_price_info' => module('manage_shop')->_format_price( $discount_price ),
			'delivery_info'       => module('manage_shop')->_format_price( $order_info[ 'delivery_price' ] ),
			'total_sum'           => module('manage_shop')->_format_price( $total_price ),
			'user_link'           => _profile_link($order_info['user_id']),
			'user_name'           => _display_name(user($order_info['user_id'])),
			'error_message'       => _e(),
			'products'            => (array)$products,
			'total_price'         => module('manage_shop')->_format_price($total_price),
			'ship_type'           => module('manage_shop')->_ship_types[$order_info['ship_type']],
			'pay_type'            => module('manage_shop')->_pay_types[$order_info['pay_type']],
			'date'                => $order_info['date'],
			'status_box'          => module('manage_shop')->_box('status', $order_info['status']),
			'back_url'            => './?object='.main()->_get('object').'&action=show_orders',
			'print_url'           => './?object='.main()->_get('object').'&action=show_print&id='.$order_info['id'],
			'payment'             => common()->get_static_conf('payment_methods', $order_info['payment']),
		));

		$out = form2($replace, array('dd_mode' => 1, 'big_labels' => true))
			->info('id')
			->info('price_total_info', array( 'desc' => 'Сумма' ) )
			->text('discount', array( 'desc' => 'Скидка, %' ) )
			->info('discount_price_info', array( 'desc' => 'Скидка' ) )
			->info('delivery_info', array( 'desc' => 'Доставка' ) )
			->info('total_sum', '', array('no_escape' => 1))
			->info_date('date', array('format' => 'full'))
			->info('name')
			->email('email')
			->info('phone')
			->container('<a href="./?object='.main()->_get('object').'&action=send_sms&phone='.urlencode($replace["phone"]).'" class="btn">Send SMS</a><br /><br />')
			->text('address')
			->text('house')
			->text('apartment')
			->text('floor')
			->text('porch')
			->text('intercom')
			->info('comment')
			->info('delivery_time')
			->text('delivery_price')
			->user_info('user_id')
			->info('payment', 'Payment method')
			->container(
				table2($products)
					->link('product_id', './?object='.main()->_get('object').'&action=product_edit&id=%d')
					->func('quantity',function($f, $p, $row){
						$row['quantity'] = "<input type='text' name='qty[".$row['product_id']."_".$row['param_id']."]' value='".intval($row['quantity'])."' style='width:50px;'>";
						return $row['quantity'];
					})
					->func('price_unit',function($f, $p, $row){
						$row['price_unit'] = "<input type='text' name='price_unit[".$row['product_id']."_".$row['param_id']."]' value='".$row['price_unit']."' style='width:100px;'>";
						return $row['price_unit'];
					})
					->text('price')
					->func('name', function($f, $p, $row){
						$row['name'] = $row['name'].($row['param_name']!='' ? "<br /><small>".$row['param_name']."</small>" : '');
						return $row['name'];
					})
					->func('status', function($f, $p, $row){
						$row['status'] = str_replace("status_item", "status_item[".$row['product_id']."_".$row['param_id']."]", $row['status']);
						return $row['status'];
					})
					->func('delete',function($f, $p, $row){
						$row['delete'] = "<input type='checkbox' name='delete[".$row['product_id']."_".$row['param_id']."]' value='1'>";
						return $row['delete'];
					})
				, array('wide' => 1)
			)
			->container(tpl()->parse('manage_shop/product_search_order',array('order_id' => $_GET['id'])),'Add product')
			->box('status_box', 'Status order', array('selected' => $order_info['status']))
			->save_and_back()
		;

		// get similar orders
		$sql= "SELECT o.*, COUNT(*) AS num_items FROM `".db('shop_orders')."` AS `o`
				INNER JOIN ".db('shop_order_items')." AS i ON i.order_id = o.id
				WHERE `o`.`id`!='".$order_info['id']."'
					AND `o`.`phone`='".$order_info['phone']."'
					AND `o`.`status`='".$order_info['status']."'
				GROUP BY o.id ORDER BY o.id DESC";
		$out .= "<br /><br /><h3>".t('Similar orders')."</h3>".table($sql)
			->text('id')
			->date('date', array('format' => 'full', 'nowrap' => 1))
			->user('user_id')
			->text('name')
			->text('phone')
			->text('total_sum', array('nowrap' => 1))

			->text('num_items')
			->btn_edit('', './?object='.main()->_get('object').'&action=view_order&id=%d',array('no_ajax' => 1))
			->btn('Merge', './?object='.main()->_get('object').'&action=merge_order&id='.$order_info['id'].'&merge_id=%d',array('no_ajax' => 1))
		;

//		$out .= tpl()->parse('manage_shop/product_search',array());

		return $out;
	}

	function merge_order() {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$order_info = db()->query_fetch('SELECT * FROM '.db('shop_orders').' WHERE id='.intval($_GET['id']));
		}
		if (empty($order_info)) {
			return _e('No such order');
		}
		module('manage_shop')->_product_check_first_revision('order', $_GET['id']);

		$_GET['merge_id'] = intval($_GET['merge_id']);
		if ($_GET['merge_id']) {
			$order_info_merge = db()->query_fetch('SELECT * FROM '.db('shop_orders').' WHERE id='.intval($_GET['merge_id'])." AND `id`!='".$order_info['id']."' AND `phone`='".$order_info['phone']."' AND `status`='".$order_info['status']."'");
		}
		if (empty($order_info_merge)) {
			return _e('No order to merge');
		}
		module('manage_shop')->_product_check_first_revision('order', $_GET['merge_id']);

		$Q = db()->query('SELECT * FROM '.db('shop_order_items').' WHERE `order_id`='.intval($order_info['id']));
		while ($_info = db()->fetch_assoc($Q)) {
			$order_items[$_info['product_id']."_".$_info['param_id']] = $_info;
		}
		$Q = db()->query('SELECT * FROM '.db('shop_order_items').' WHERE `order_id`='.intval($order_info_merge['id']));
		while ($_info = db()->fetch_assoc($Q)) {
			$order_items_merge[$_info['product_id']."_".$_info['param_id']] = $_info;
		}

		foreach ($order_items_merge as $k=>$v) {
			if (!empty($order_items[$k])) {
				db()->UPDATE(db('shop_order_items'), array(
					'quantity' => $order_items[$k]['quantity'] + $v['quantity'],
				), "`order_id`='{$_GET['id']}' AND `product_id`='{$v['product_id']}' AND `param_id`='{$v['param_id']}'");
			} else {
				db()->INSERT(db('shop_order_items'), _es(array(
					'order_id'	=> $_GET['id'],
					'type'		=> $v['type'],
					'product_id' => $v['product_id'],
					'param_id'   => $v['param_id'],
					'user_id'    => $v['user_id'],
					'quantity'   => $v['quantity'],
					'price'     => number_format($v['price'], 2, '.', ''),
					'status'	=> $v['status'],
				)));
			}
		}

		$Q = db()->query('SELECT * FROM '.db('shop_order_items').' WHERE `order_id`='.intval($_GET['id']));
		while ($_info = db()->fetch_assoc($Q)) {
			$total_price += $_info['quantity']*$_info['price'];
		}
		$_class_basket  = _class( '_shop_basket', 'modules/shop/' );
		$delivery_price = $_class_basket->delivery_price( $price_total );
		$total_price += $delivery_price;

		db()->UPDATE(db('shop_orders'), array(
				'total_sum'      => number_format($total_price, 2, '.', ''),
				'delivery_price' => $delivery_price,
				'merge_id'       => $_GET['merge_id'],
			),"`id`='".$_GET['id']."'"
		);
		module('manage_shop')->_order_add_revision('merge', array($_GET['id'], $_GET['merge_id']));

		return js_redirect('./?object='.main()->_get('object').'&action=view_order&id='.$_GET['id']);
	}

	/**
	*/
	function delete_order() {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$order_info = db()->query_fetch('SELECT * FROM '.db('shop_orders').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($order_info['id'])) {
			module('manage_shop')->_product_check_first_revision('order', $_GET['id']);
			db()->query('DELETE FROM '.db('shop_orders').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			db()->query('DELETE FROM '.db('shop_order_items').' WHERE `order_id`='.intval($_GET['id']));
			common()->admin_wall_add(array('shop order deleted: '.$_GET['id'], $_GET['id']));
		}

		module('manage_shop')->_order_add_revision('delete', $_GET['id']);

		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			$_GET['id'];
		} else {
			return js_redirect('./?object='.main()->_get('object').'&action=show_orders');
		}
	}

	function order_product_add_ajax() {
		if (empty($_POST['order_id']) || empty($_POST['product_id'])) {
			return json_encode('ko');
		}

		$order_id = intval($_POST['order_id']);
		$product_id = intval($_POST['product_id']);

		if (intval($_POST['quantity']) == 0) {
			return json_encode('ko');
		}

		$order_info = db()->get("SELECT * FROM `".db('shop_orders')."` WHERE `id`=".$order_id);
		if (empty($order_info)) {
			return json_encode('ko');
		}

		$_product_info = db()->get("SELECT * FROM `".db('shop_products')."` WHERE `id`=".$product_id);
		if (empty($_product_info)) {
			return json_encode('ko');
		}

		module('manage_shop')->_product_check_first_revision('order', $order_id);
		$A = db()->get("SELECT * FROM `".db('shop_order_items')."` WHERE `order_id`=".$order_id." AND `product_id`=".$product_id);
		if (empty($A)) {
			db()->insert(db('shop_order_items'), array(
				'order_id'   => $order_id,
				'product_id' => $product_id,
				'param_id'   => 0,
				'quantity'   => intval($_POST['quantity']),
				'price'      => number_format(module('shop')->_product_get_price($_product_info), 2, '.', ''),
			));
		} else {
			db()->update(db('shop_order_items'), array(
				'quantity' => $A['quantity'] + intval($_POST['quantity']),
			)," `order_id`=".$order_id." AND `product_id`=".$product_id);
		}
		$this->_order_recount_price($_POST['order_id'], $order_info);

		module('manage_shop')->_order_add_revision('edit', $order_id);
		return json_encode('ok');
	}

	function _order_recount_price($order_id, $order_info = array()) {
		$order_id = (int)$order_id;
		$price_total = 0;
		$Q = db()->query( 'SELECT * FROM '.db('shop_order_items')." WHERE order_id=$order_id" );
		while ($_info = db()->fetch_assoc($Q)) {
			$price        = (float)$_info['price'];
			$quantity     = (float)$_info['quantity'];
			$price_total += $price * $quantity;
		}

		// discount
		$discount = $order_info[ 'discount' ];
		// $_class_discount = _class( '_shop_discount', 'modules/shop/' );
		// $discount        = $_class_discount->calc_discount_global( $price_total, $discount );
		$_class_price = _class( '_shop_price', 'modules/shop/' );
		$discount_price = $_class_price->apply_price( $price_total, $discount );
		$discount_price -= $price_total;
		if ($order_info['is_manual_delivery_price'] == 1) {
			$delivery_price = $order_info['delivery_price'];
		} else {
			$_class_basket  = _class( 'shop_basket', 'modules/shop/' );
			$delivery_price = $_class_basket->delivery_price( $price_total );
		}
		// calc total
		$price_total += $discount_price + $delivery_price;

		db()->UPDATE(db('shop_orders'), array(
				'total_sum'      => number_format($price_total, 2, '.', ''),
				'delivery_price' => $delivery_price,
			),
			"id=$order_id"
		);
		return array(
			'total_sum'      => $price_total,
			'delivery_price' => $delivery_price,
		);

	}

}
