<?php

class yf_manage_shop_paywill{

	public $order_address_fields = array(
		"name",
		"phone",
		"address",
		"house",
		"apartment",
		"floor",
		"porch",
		"intercom",
	);

	public $discount = 0;

	/**
	*/
	function paywill(){
		$_GET['id'] = intval($_GET['id']);
		$replace = $this->_prepare_paywill_body($_GET['id']);
		$html = db()->get_one('SELECT text FROM '.db('static_pages').' WHERE `name`= "paywill"');
		$out = str_replace('__PAYWILL_BODY__', $replace, $html);
		if($_GET['pdf']){
			common()->pdf_page($out, $_GET['id']);
		}else{
			echo $out;
		}
		exit;
	}

	/**
	*/
	function _prepare_paywill_body($order_id = false){
		if ($order_id) {
			$order_info = db()->query_fetch('SELECT * FROM '.db('shop_orders').' WHERE id='.intval($order_id));
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
			$products_infos = db()->query_fetch_all('SELECT * FROM '.db('shop_products').' WHERE id IN('.implode(',', $products_ids).')');
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
		foreach((array)$order_info as $k => $v){
			if(in_array($k, $this->order_address_fields) && !empty($v))
				$user_address[] = t($k).': '.$v;
		}
		$replace = array(
			'id'			=> $order_info['id'],
			'total_sum'		=> $total_sum,
			'user_address'	=> implode(" / ", $user_address),
//			'pay_type'		=> module('shop')->_pay_types[$order_info['pay_type']],
			'date'			=> _format_date($order_info['date'], '%d.%m.%Y г.'),
//			'payment'		=> common()->get_static_conf('payment_methods', $order_info['payment']),
			'products'		=> $out['products'],
			'delivery'		=> ($order_info['delivery_price'] !== '')? module('shop')->_format_price(floatval($order_info['delivery_price'])) : 'не расчитана',
			'discount'		=> module('shop')->_format_price($this->discount),
			'num_to_str'	=> common()->num2str($order_info['total_sum']),
		);
		return tpl()->parse('shop/paywill', $replace);
	}

}