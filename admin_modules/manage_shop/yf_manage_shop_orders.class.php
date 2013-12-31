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
			->date('date', array('format' => 'full', 'nowrap' => 1))
			->user('user_id')
			->text('name')
			->text('phone')				
			->text('total_sum', array('nowrap' => 1))

			->text('num_items')
			->func('status', function($field, $params) { 
				return common()->get_static_conf('order_status', $field);
			}, array('nowrap' => 1))
			->btn_edit('', './?object=manage_shop&action=view_order&id=%d',array('no_ajax' => 1))
			->btn('Paywill', './?object=paywill&id=%d',array('no_ajax' => 1, 'target' => '_blank'))
			->btn('PDF', './?object=paywill&id=%d&pdf=y',array('no_ajax' => 1, 'target' => '_blank'))
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
		$recount_price = false;
		if(!empty($_POST)){
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
			if ($recount_price) {
				$total_price = 0;
				$Q = db()->query('SELECT * FROM '.db('shop_order_items').' WHERE `order_id`='.intval($order_info['id']));
				while ($_info = db()->fetch_assoc($Q)) {
					$total_price += $_info['quantity']*$_info['price'];
				}

				$delivery_price = ((intval($total_price) < 200)? $this->delivery_price : 0);
				$total_price += intval($delivery_price);

				$order_info['total_sum']  = $total_price;
				$order_info['delivery_price'] = $delivery_price;

				db()->UPDATE(db('shop_orders'), array('total_sum' => number_format($order_info['total_sum'], 2, '.', ''),'delivery_price' => $order_info['delivery_price']),"`id`='".$_GET['id']."'");
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
			$order_items[$_info['product_id']."_".$_info['param_id']] = $_info;
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
			$products[$_info['product_id'].'_'.$_info['param_id']] = array(
				'product_id'	=> intval($_info['product_id']),
				'param_id'	=> intval($_info['param_id']),				
				'param_name'	=> _class( '_shop_product_params', 'modules/shop/' )->_get_name_by_option_id($_info['param_id']),
				'name'			=> _prepare_html($_product['name']),
				'price_unit'	=> $_info['price'],				
				'price'			=> $_info['quantity']*$_info['price'],
				'currency'		=> _prepare_html(module('manage_shop')->CURRENCY),
				'quantity'		=> intval($_info['quantity']),
				'details_link'	=> process_url('./?object=manage_shop&action=view&id='.$_product['id']),
				'dynamic_atts'	=> !empty($dynamic_atts) ? implode('<br />'.PHP_EOL, $dynamic_atts) : '',
				'status'		=> module('manage_shop')->_box('status_item', $_info['status']),
				'delete'		=> '', // will be filled later on table2()
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
			'date'			=> $order_info['date'],
			'status_box'	=> module('manage_shop')->_box('status', $order_info['status']),
			'back_url'		=> './?object=manage_shop&action=show_orders',
			'print_url'		=> './?object=manage_shop&action=show_print&id='.$order_info['id'],
			'payment'		=> common()->get_static_conf('payment_methods', $order_info['payment']),
		));
		
		$out = form2($replace, array('dd_mode' => 1, 'big_labels' => true))
			->info('id')
			->info('total_sum', '', array('no_escape' => 1))
			->info_date('date', array('format' => 'full'))
			->info('name')
			->email('email')
			->info('phone')
			->container('<a href="./?object=manage_shop&action=send_sms&phone='.urlencode($replace["phone"]).'" class="btn">Send SMS</a><br /><br />')
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
			->box('status_box', 'Status order', array('selected' => $order_info['status']))
			->save_and_back()
		;
					
		// get similar orders
		$sql= "SELECT * FROM `".db('shop_orders')."` AS `o` INNER JOIN ".db('shop_order_items')." AS i ON i.order_id = o.id  WHERE `o`.`id`!='".$order_info['id']."' AND `o`.`phone`='".$order_info['phone']."' AND `o`.`status`='".$order_info['status']."' GROUP BY o.id ORDER BY o.id DESC";
		$out .= "<br /><br /><h3>".t('Similar orders')."</h3>".table($sql)
			->text('id')
			->date('date', array('format' => 'full', 'nowrap' => 1))
			->user('user_id')
			->text('name')
			->text('phone')				
			->text('total_sum', array('nowrap' => 1))

			->text('num_items')
			->btn_edit('', './?object=manage_shop&action=view_order&id=%d',array('no_ajax' => 1))
			->btn('Merge', './?object=manage_shop&action=merge_order&id='.$order_info['id'].'&merge_id=%d',array('no_ajax' => 1))
		;									

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
		$_GET['merge_id'] = intval($_GET['merge_id']);
		if ($_GET['merge_id']) {
			$order_info_merge = db()->query_fetch('SELECT * FROM '.db('shop_orders').' WHERE id='.intval($_GET['merge_id'])." AND `id`!='".$order_info['id']."' AND `phone`='".$order_info['phone']."' AND `status`='".$order_info['status']."'");
		}
		if (empty($order_info_merge)) {
			return _e('No order to merge');
		}
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

		$delivery_price = ((intval($total_price) < 200)? $this->delivery_price : 0);
		$total_price += intval($delivery_price);

		db()->UPDATE(db('shop_orders'), array('total_sum' => number_format($total_price, 2, '.', ''),'delivery_price' => $delivery_price),"`id`='".$_GET['id']."'");
		db()->query("DELETE FROM `".db('shop_order_items')."` WHERE `order_id`='{$_GET['merge_id']}'");
		return js_redirect("./?object=manage_shop&action=view_order&id={$_GET['id']}");
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