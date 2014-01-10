<?php
class yf_manage_shop_express{

	var $alcohol_category = 4;

	/**
	*/
	function express () {
		if(intval($_GET['hours'])){
			return $this->get_pdf();	
		}
		$date = date("Y-m-d");
		$orders_info = db()->query_fetch_all("SELECT * FROM ".db('shop_orders')." WHERE delivery_time LIKE '".$date."%'");
		$orders = array_keys($orders_info);
		$products = db()->query_fetch_all("SELECT o.*, p.name, p.price, p.cat_id 
											FROM ".db('shop_order_items')." as o
											RIGHT JOIN ".db('shop_products')." as p
											ON o.product_id = p.id 
											WHERE o.order_id IN(".implode(",", $orders).")
											ORDER BY o.order_id DESC");
		$_category = _class("_shop_categories", "modules/shop/");
		foreach($products as $k => $v){
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
			->footer_link("PDF ".$date." 10-12", './?object='.$_GET['object'].'&action=express&hours=10-12')
			->footer_link("PDF ".$date." 13-15", './?object='.$_GET['object'].'&action=express&hours=13-15')
			->footer_link("PDF ".$date." 17-20", './?object='.$_GET['object'].'&action=express&hours=17-20')
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
	function get_pdf() {
		$date = date("Y-m-d");
		$hours = intval($_GET['hours']);
		$orders = db()->get_2d("SELECT id FROM ".db('shop_orders')." WHERE delivery_time LIKE '".$date." ".$hours."%'");
		$products = db()->query_fetch_all("SELECT o.*, p.name, p.price, p.cat_id 
											FROM ".db('shop_order_items')." as o
											RIGHT JOIN ".db('shop_products')." as p
											ON o.product_id = p.id 
											WHERE o.order_id IN(".implode(",", $orders).")");
		$ids = $replace = array();
		$_category = _class("_shop_categories", "modules/shop/");
		foreach($products as $k => $v){
			$alcohol = in_array($this->alcohol_category, $_category->recursive_get_parents_ids($v['cat_id']));
			if($alcohol){
				$replace_alcohol[$v['order_id']][$v['product_id']] = array(
					"id"		=> $v['product_id'],
					"name"		=> $v['name'],
					"quantity"	=> $v['quantity'],
					"price"		=> module('shop')->_format_price(floatval($v['price'])),
					"order"		=> $v['order_id'],
				);
				continue;
			}
			$order_ids[] = $v['order_id'];
			if(in_array($v['product_id'], $ids)){
				$replace[$v['product_id']]['quantity'] +=1;
				continue;
			}
			$ids[] = $v['product_id'];
			$replace[$v['product_id']] = array(
				"id"		=> $v['product_id'],
				"name"		=> $v['name'],
				"quantity"	=> $v['quantity'],
				"price"		=> module('shop')->_format_price(floatval($v['price'])),
				"order"		=> $v['order_id'],
			);
		}
		$out[] = $this->_prepare_pdf_tpl($replace, $order_ids);
		if($replace_alcohol){
			foreach($replace_alcohol as $order_id => $data){
				$out[] = $this->_prepare_pdf_tpl($data);
			}
		}
		$out = implode("<pagebreak />", $out);
		return common()->pdf_page($out);
	}

	/**
	*/	
	function _prepare_pdf_tpl($items = false, $orders_ids = false){
		$ids = array_keys($items);
		if (!empty($ids)) {
			$products_infos = db()->query_fetch_all('SELECT * FROM '.db('shop_products').' WHERE id IN('.implode(',', $ids).') AND active="1"');
		}
		foreach ((array)$items as $_info) {
			$_product = $products_infos[$_info['id']];
			$products[$_info['id']] = 
				'<tr style="border: 1px solid rgb(206, 206, 206);">'
				.'<td style="text-align: left; width: 350px;padding: 15px 12px;">'._prepare_html($_info['name']).'</td>'
				.'<td style="width: 45px;padding: 15px 12px;">'._prepare_html(module('shop')->CURRENCY).'</td>'
				.'<td style="width: 140px;text-align: right;padding: 15px 12px;">'.intval($_info['quantity']).'</td>'
				.'<td style="width: 140px;text-align: right;padding: 15px 12px;">'.module('shop')->_format_price(floatval($_info['quantity']*$_product['price'])).'</td>'
				.'</tr>';

			$out['products'] .= $products[$_info['id']];
			$total_sum += $_info['quantity']*$_product['price'] ;
			$order_ids[] = $_info['order'];
		}
		$order_ids = implode(",", array_unique($order_ids));
		$delivery_times = db()->get_2d("SELECT delivery_time FROM ".db('shop_orders')." WHERE id IN(".$order_ids.")");
		$replace = array(
			'total_sum'		=> module('shop')->_format_price($total_sum),
			'date'			=> implode(",", array_unique($delivery_times)),
			'products'		=> $out['products'],
			'num_to_str'	=> common()->num2str(str_replace(",", ".", $total_sum)),
			'order_numbers'	=> $order_ids,
		);
		$replace_tpl = array(
			'total_sum'		=> '__PRICE__',
			'date'			=> '__DATE__',
			'products'		=> '__PRODUCTS__',
			'num_to_str'	=> '__NUM_TO_STR__',
			'order_numbers'	=> '__ORDERS__',
		);
		$Q = db()->get_2d('SELECT text FROM '.db('static_pages').' WHERE `name`= "express"');
		return str_replace($replace_tpl, $replace, $Q[0]);
	}

}