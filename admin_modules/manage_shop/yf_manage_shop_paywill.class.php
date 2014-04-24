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

	public $default_unit = "шт";

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
		$Q = db()->query('SELECT o.*, p.name , u.title
							FROM '.db('shop_order_items').' as o
							RIGHT JOIN '.db('shop_products').' as p ON p.id = o.product_id
							LEFT JOIN '.db('shop_product_units').' as u ON u.id = o.unit
							WHERE o.order_id='.intval($order_info['id'])
		);
		while ($A = db()->fetch_assoc($Q)) {
			$order_items[$A['product_id']] = $A;
		}
		$price_total = 0;
		foreach ((array)$order_items as $_info) {
			$price_one  = (float)$_info[ 'price' ];
			$quantity   = (int)$_info[ 'quantity' ];
			$price_item = $price_one * $quantity;
			$out['products'][] = array(
				"product_name"		=> _prepare_html($_info['name']),
				"product_units"		=> $_info['title']? : $this->default_unit,
				"product_price_one"	=> module('shop')->_format_price( $price_one ),
				"product_quantity"	=> $quantity,
				"product_item_price"=> module('shop')->_format_price( $price_item ),
			);
			$price_total += $price_item;
		}
		foreach((array)$order_info as $k => $v){
			if(in_array($k, $this->order_address_fields) && !empty($v))
				$user_address[] = t($k).': '.$v;
		}
		// discount
		$_class_price = _class( '_shop_price', 'modules/shop/' );
		$discount     = $order_info[ 'discount'     ];
		$discount_add = $order_info[ 'discount_add' ];
		$_discount    = $discount;
		$with_discount_add = isset( $_GET[ 'with_discount_add' ] );
		if( $with_discount_add ) {
			$_discount += $discount_add;
		}
		$discount_price = $_class_price->apply_price( $price_total, $_discount );
		$discount_price -= $price_total;
		// total string
		$total_sum	= $order_info[ 'total_sum' ];
		$num_to_str	= common()->num2str( $total_sum );
		// delivery
		$_class_delivery = _class( '_shop_delivery', 'modules/shop/' );
		$delivery_name = $_class_delivery->_get_name_by_id( $order_info[ 'delivery_type' ] );
		$replace = array(
			'id'                => $order_info['id'],
			'total_sum'         => module('shop')->_format_price( $total_sum ),
			'user_address'      => implode(" / ", $user_address),
			// 'pay_type'          => module('shop')->_pay_types[$order_info['pay_type']],
			// 'payment'           => common()->get_static_conf('payment_methods', $order_info['payment']),
			'date'              => _format_date($order_info['date'], '%d.%m.%Y г.'),
			'products'          => $out['products'],
			'delivery'          => ($order_info['delivery_price'] !== '')? module('shop')->_format_price(floatval($order_info['delivery_price'])) : 'не расчитана',
			'discount'          => module('shop')->_format_price( $discount_price ),
			'num_to_str'        => $num_to_str,
			'delivery_name'     => $delivery_name,
			'delivery_location' => $order_info['delivery_location'],
		);
		return tpl()->parse('shop/paywill', $replace);
	}

}
