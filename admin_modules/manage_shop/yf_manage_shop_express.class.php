<?php
class yf_manage_shop_express{

	public $alcohol_category = 4;

	public $default_unit = "шт";
	
	function _init(){
		$this->PATH_TO_PDF = PROJECT_PATH."uploads/pdf/";
	}

	/**
	*/
	function express () {
		$date = date("Y-m-d");
		$orders_info = db()->query_fetch_all("SELECT * FROM ".db('shop_orders')." WHERE delivery_time LIKE '".$date."%' AND status = 1");
		if(!empty($orders_info)){
			$orders = array_keys($orders_info);
			$products = db()->query_fetch_all("SELECT o.*, p.name, p.price, p.cat_id 
											FROM ".db('shop_order_items')." as o
											RIGHT JOIN ".db('shop_products')." as p ON o.product_id = p.id 
											WHERE o.order_id IN(".implode(",", $orders).") AND o.status = 1
											ORDER BY o.order_id DESC");
		}
		$_category = _class("_shop_categories", "modules/shop/");
		//always add one empty row in table for ajax
		if(empty($products)){
			$products[] = array(
				'product_id'	=> '-',
				'name'			=> '-',
				'quantity'		=> '-',
				'price'			=> '-',
				'order_id'		=> '-',
			);
			$orders_info['-']['delivery_time'] = '-';
		}
		foreach((array)$products as $k => $v){
			$replace[] = array(
				"product_id"	=> $v['product_id'],
				"name"			=> $v['name'],
				"quantity"		=> $v['quantity'],
				"price"			=> module('shop')->_format_price(floatval($v['price'])),
				"order_id"		=> $v['order_id'],
				"id"			=> $v['order_id'].'_'.$v['product_id'],//unique_id
				"time"			=> str_replace($date, "", $orders_info[$v['order_id']]['delivery_time']),
			);
			$table_tr[] = 'data-id="'.$v['order_id'].'_'.$v['product_id'].'" ' ;
		}
		if(!empty($_GET['ajax_mode'])){
			return json_encode($replace);
		}
		$table = table($replace)
			->text('order_id')
			->text('time')
			->text('name')
			->text('quantity')
			->text('product_id')
			->footer_link("PDF ".$date." 10-12", './?object='.$_GET['object'].'&action=express_pdf&hours=10-12')
			->footer_link("PDF ".$date." 13-15", './?object='.$_GET['object'].'&action=express_pdf&hours=13-15')
			->footer_link("PDF ".$date." 17-20", './?object='.$_GET['object'].'&action=express_pdf&hours=17-20')
			->render(array(
				'table_attr' => 'id="express_catalog"',
				'tr' => $table_tr
			))
		;
		$replace = array(
			'table' => $table,
		);
		return tpl()->parse("manage_shop/express", $replace);
	}

	/**
	*/
	function express_pdf($send_mail = false) {
		$date = date("Y-m-d");
		$hours = intval($_GET['hours']);
		$orders = db()->get_2d("SELECT id FROM ".db('shop_orders')." WHERE delivery_time LIKE '".$date." ".$hours."%' AND status = 1");
		if(empty($orders)){
			common()->message_warning("No orders for this time");
			return js_redirect("./?object=manage_shop&action=express");
		}
		$products = db()->query_fetch_all("SELECT o.*, p.name, p.cat_id, u.title
											FROM ".db('shop_order_items')." as o
											RIGHT JOIN ".db('shop_products')." as p	ON o.product_id = p.id 
											LEFT JOIN ".db('shop_product_units')." as u ON u.id = o.unit
											WHERE o.order_id IN(".implode(",", $orders).")
												AND o.status = 1");
		if(empty($products)){
			common()->message_warning("No products for this time");
			return js_redirect("./?object=manage_shop&action=express");
		}
		$ids = $replace = array();
		$_category = _class("_shop_categories", "modules/shop/");
		foreach((array)$products as $k => $v){
			$p_id = $v['product_id'];
			$item = array(
				"id"		=> $v['product_id'],
				"name"		=> $v['name'],
				"quantity"	=> $v['quantity'],
				"price"		=> $v['price'],
				"order_id"	=> $v['order_id'],
				"unit"		=> $v['title'],
			);
			$alcohol = in_array($this->alcohol_category, $_category->recursive_get_parents_ids($v['cat_id']));
			if($alcohol){
				$replace[$v['order_id']][$p_id] = $item;
				continue;
			}
			if(in_array($p_id, $ids)){
				$replace['product'][$p_id]['quantity'] +=$v['quantity'];
				continue;
			}
			$ids[] = $p_id;
			$replace['product'][$p_id] = $item;
		}
		foreach($replace as $k => $data){
			$out[] = $this->_prepare_express_pdf($data);
		}
		$out = implode("<pagebreak />", $out);
		if($send_mail){
			return array("body" => $out, "name" => date("Y-m-d H-i"));
		}else{
			return common()->pdf_page($out);
		}
	}

	/**
	*/	
	function _prepare_express_pdf($items = false){
		if(empty($items))
			return false;
		foreach ((array)$items as $_info) {
			$price_item = $_info['price'] * $_info['quantity'];
			$out['products'][] = array(
				"product_name"		=> _prepare_html($_info['name']),
				"product_units"		=> $_info['unit']? : $this->default_unit,
				"product_price_one"	=> module('shop')->_format_price($_info['price']),
				"product_quantity"	=> intval($_info['quantity']),
				"product_item_price"=> module('shop')->_format_price($price_item),
			);
			$order_ids[] = $_info['order_id'];
			$total_sum += $price_item; 
		}
		$order_ids = implode(",", array_unique($order_ids));
		$delivery_times = db()->get_2d("SELECT delivery_time FROM ".db('shop_orders')." WHERE id IN(".$order_ids.")");

		$replace = array(
			'order_ids'		=> $order_ids,
			'total_sum'		=> module('shop')->_format_price(floatval($total_sum)),
			'date'			=> implode(",", array_unique($delivery_times)),
			'products'		=> $out['products'],
			'num_to_str'	=> common()->num2str($total_sum),
		);
		return tpl()->parse('shop/express_pdf', $replace);
	}

	/**
	*/
	function mail_pdf(){
		$time = intval($_GET['hours']);
		if(empty($time)){
			return _e("No delivery time");
		}
		$pdf = $this->express_pdf(true);
		if(!$pdf['body']){
			return false;
		}	
		common()->pdf_page($pdf['body'], $pdf['name'], "F");
		$path_to_pdf = $this->PATH_TO_PDF.$pdf['name'].".pdf";
		$path_to_pdf = file_exists($path_to_pdf) ? $path_to_pdf : '';
		_class('_shop_mail', 'modules/shop/')->send_by_event( array(
			'event'     => 'express_ticket',
			'message' 	=> $pdf['body'],
			'attaches'  => array($path_to_pdf),
		));
		return true;
	}

}