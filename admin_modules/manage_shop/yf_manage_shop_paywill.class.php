<?php

class yf_manage_shop_paywill{

	/**
	*/
	function paywill(){

		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$order_info = db()->query_fetch('SELECT * FROM '.db('shop_orders').' WHERE id='.intval($_GET['id']));
		}
		if (empty($order_info)) {
			return _e('No such order');
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
		}
		foreach ((array)$order_items as $_info) {
			$_product = $products_infos[$_info['product_id']];
			$products[$_info['product_id']] = 
				'<tr style="border: 1px solid rgb(206, 206, 206);">'
				.'<td style="text-align: left; width: 350px;padding: 15px 12px;">'._prepare_html($_product['name']).'</td>'
				.'<td style="width: 45px;padding: 15px 12px;">'._prepare_html(module('shop')->CURRENCY).'</td>'
				.'<td style="width: 140px;text-align: right;padding: 15px 12px;">'.intval($_info['quantity']).'</td>'
				.'<td style="width: 140px;text-align: right;padding: 15px 12px;">'.module('shop')->_format_price($_info['quantity']*$_info['price']).'</td>'
				.'</tr>';

			$out['products'] .= $products[$_info['product_id']];
		}
		$total_sum = module('shop')->_format_price(floatval($order_info['total_sum']));
		$customer = $this->order_address($order_info['id']);
		$replace = array(
			'id'			=> $order_info['id'],
			'total_sum'		=> $total_sum,
			'user_name'		=> $customer,
//			'pay_type'		=> module('shop')->_pay_types[$order_info['pay_type']],
			'date'			=> _format_date($order_info['date'], '%d.%m.%Y г.'),
//			'payment'		=> common()->get_static_conf('payment_methods', $order_info['payment']),
			'products'		=> $out['products'],
			'delivery'		=> ($order_info['delivery_price'] !== '')? module('shop')->_format_price(floatval($order_info['delivery_price'])) : 'не расчитана',
			'discount'		=> intval(0),
			'num_to_str'	=> common()->num2str($order_info['total_sum']),
		);
		$replace_tpl = array(
			'id'			=> '__NUMBER__',
			'total_sum'		=> '__PRICE__',
			'user_name'		=> '__CUSTOMER__',
			'date'			=> '__DATE__',
			'products'		=> '__PRODUCTS__',
			'delivery'		=> '__DELIVERY__',
			'discount'		=> '__DISCOUNT__',
			'num_to_str'	=> '__NUM_TO_STR__',
		);
		$Q = db()->get_2d('SELECT text FROM '.db('static_pages').' WHERE `name`= "paywill"');
		$out = str_replace($replace_tpl, $replace, $Q[0]);
		if($_GET['pdf']){
			common()->pdf_page($out, $order_info['id']);
		}else{
			echo $out;
		}
		exit;
	}

	/**
	*/
	function order_address($order_id){
		if(!$order_id)
			return _e('No such order');
		$order_info = db()->query_fetch('SELECT * FROM '.db('shop_orders').' WHERE id='.intval($order_id));
		$replace = array(
			"name"		=> t('name').': '.$order_info['name'],
			"phone"		=> t('phone').': '.$order_info['phone'],
			"address"	=> t('address').': '.$order_info['address'],
			"house"		=> t('house').': '.$order_info['house'],
			"apartment"	=> t('apartment').': '.$order_info['apartment'],
			"floor"		=> t('floor').': '.$order_info['floor'],
			"porch"		=> t('porch').': '.$order_info['porch'],
			"intercom"	=> t('intercom').': '.$order_info['intercom'],
		);
		return implode(" / ", $replace);
	}


}