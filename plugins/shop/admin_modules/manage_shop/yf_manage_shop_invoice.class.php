<?php

class yf_manage_shop_invoice{

	public $order_address_fields = [
		"name",
		"phone",
		"address",
		"house",
		"apartment",
		"floor",
		"porch",
		"intercom",
	];

	public $default_unit = "шт";

	private $_class_price      = false;
	private $_class_units      = false;
	private $_class_region     = false;
	private $_class_categories = false;
	private $_class_basket     = false;
	private $_class_shop       = false;

	function _init(){
		$this->_class_price      = _class( '_shop_price',         'modules/shop/' );
		$this->_class_units      = _class( '_shop_product_units', 'modules/shop/' );
		$this->_class_region     = _class( '_shop_region',        'modules/shop/' );
		$this->_class_categories = _class( '_shop_categories',    'modules/shop/' );
		$this->_class_basket     = _class( 'shop_basket',         'modules/shop/' );
		$this->_class_shop       = module( 'shop' );
	}

	function invoice() {
		$id = (int)$_GET[ 'id' ];
		$_GET[ 'id' ] = $id;
		list( $css, $html ) = $this->_prepare_invoice_body( $id );
		$tpl = db()->get_one('SELECT text FROM '.db('static_pages').' WHERE `name`= "invoice"');
		if( empty( $tpl ) ) {
			$tpl = '
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
</head>
<body>

__INVOICE__

</body>
</html>
';
		}
		if( $_GET[ 'pdf' ] ) {
			$html_page = str_replace( '__INVOICE__', $html, $tpl );
			common()->pdf_page( [
				'css'  => $css,
				'html' => $html_page,
				'file' => $id,
			]);
		} else {
			$body = sprintf(
				'<style>%s</style>%s'
				, $css
				, $html
			);
			$body = str_replace( '__INVOICE__', $body, $tpl );
			echo $body;
		}
		exit;
	}

	/**
	*/
	function _prepare_invoice_body($order_id = false){
		$_class_price      = $this->_class_price;
		$_class_units      = $this->_class_units;
		$_class_region     = $this->_class_region;
		$_class_categories = $this->_class_categories;
		$_class_basket     = $this->_class_basket;
		$_class_shop       = $this->_class_shop;
		if ($order_id) {
			$order_info = db()->query_fetch('SELECT * FROM '.db('shop_orders').' WHERE id='.intval($order_id));
		}
		if (empty($order_info)) {
			return _e('No such order');
		}
		$id = (int)$order_info[ 'id' ];
		$Q = db_get_all('SELECT * FROM '.db('shop_order_items').' WHERE order_id='.$id);
		// while ($A = db()->fetch_assoc($Q)) {
			// $order_items[$A['product_id']] = $A;
		// }

		// Get products from db
		$products_ids = [];
		// type: 0 - product; 1 - product set
		foreach( (array)$Q as $_id => $item ) {
			$type        = (int)$item[ 'type'       ];
			$product_id  = (int)$item[ 'product_id' ];
			if( $product_id ) {
				$products_ids[ $type ][ $product_id ] = $product_id;
			}
		}
		$infos = [];
		if( !empty( $products_ids[ 0 ] ) ) {
			$ids = array_keys( $products_ids[ 0 ] );
			$ids_sql = implode( ',', $ids );
			$infos[ 0 ]   = db()->query_fetch_all('SELECT * FROM ' . db('shop_products') . ' WHERE id IN(' . $ids_sql . ')');
			$_class_units   = $this->_class_units;
			$products_units = $_class_units->get_by_product_ids( $ids );
		}
		if( !empty( $products_ids[ 1 ] ) ) {
			$ids = array_keys( $products_ids[ 1 ] );
			$ids_sql = implode( ',', $ids );
			$infos[ 1 ] = db()->query_fetch_all('SELECT * FROM '.db('shop_product_sets').' WHERE id IN('. $ids_sql .')');
		}

		$price_total = 0;
		// foreach ((array)$Q as $_info) {
		foreach( (array)$Q as $item ) {
			$param_id   = (int)$item[ 'param_id' ];
			$product_id = (int)$item[ 'product_id' ];
			$type       = (int)$item[ 'type'       ];
			$quantity   = (int)$item[ 'quantity'   ];
			$unit       = (int)$item[ 'unit'       ];
			$info       = &$infos[ $type ][ $product_id ];
			$units = $unit > 0 ? $products_units[ $product_id ] : 0;
			// price
			// $price_one  = (float)$info[ 'price' ];
			$price_one  = $_class_basket->_get_price_one( $item );
			$price_item = $price_one * $quantity;
			$out['products'][] = [
				"product_name"		=> _prepare_html($info['name']),
				"product_units"		=> $units[ $unit ]['title'] ?: $this->default_unit,
				"product_price_one"	=> $_class_shop->_format_price( $price_one ),
				"product_quantity"	=> $quantity,
				"product_item_price"=> $_class_shop->_format_price( $price_item ),
			];
			$price_total += $price_item;
		}
		foreach((array)$order_info as $k => $v){
			if(in_array($k, $this->order_address_fields) && !empty($v))
				$user_address[] = t($k).': '.$v;
		}
		// discount
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
		$total_sum	= (float)$order_info[ 'total_sum' ];
		$num_to_str	= common()->num2str( $total_sum );
		// delivery
		$_class_delivery = _class( '_shop_delivery', 'modules/shop/' );
		$delivery_id   = (int)$order_info[ 'delivery_id'   ];
		$delivery_type = (int)$order_info[ 'delivery_type' ];
		$delivery_name = $_class_delivery->_get_name_by_id( $delivery_type );
		$region        = $_class_region->_get_user_region();
		$replace = [
			'id'			=> $id,
			'total_sum'		=> $_class_shop->_format_price( $total_sum ),
			'user_address'	=> implode(" / ", $user_address),
//			'pay_type'		=> $_class_shop->_pay_types[$order_info['pay_type']],
			'date'			=> _format_date($order_info['date'], '%d.%m.%Y г.'),
//			'payment'		=> common()->get_static_conf('payment_methods', $order_info['payment']),
			'products'		=> $out['products'],
			'region'		=> $region,
			'delivery'		=>
				// empty( $order_info[ 'delivery_price' ] ) ?
					// '&mdash;' :
					// $_class_shop->_format_price(0) :
					$_class_shop->_format_price(floatval($order_info['delivery_price'])) ,
			'delivery_id'       => $delivery_id,
			'delivery_type'     => $delivery_type,
			'delivery_name'     => $delivery_name,
			'delivery_location' => $order_info['delivery_location'],
			'discount'		=> $_class_shop->_format_price( $discount_price ),
			'num_to_str'	=> $num_to_str,
		];
		return( [
			tpl()->parse('shop/invoice_css',  $replace),
			tpl()->parse('shop/invoice_html', $replace),
		]);
	}

}
