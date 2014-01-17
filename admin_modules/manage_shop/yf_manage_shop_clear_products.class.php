<?php

/**
*/
class yf_manage_shop_clear_products {

	/**
	*/
	function show_clear_patterns () {
		return table('SELECT * FROM '.db('shop_patterns'), array(
				'filter' => $_SESSION[$_GET['object'].'__patterns'],
				'filter_params' => array(
					'name'	=> 'like',
					'price' => 'between',
					'articul' => 'like',
				),
			))
			->text('search')
			->text('replace')
			->text('status')
			->btn_edit('', './?object=manage_shop&action=product_edit&id=%d',array('no_ajax' => 1))
			->btn_delete('', './?object=manage_shop&action=product_delete&id=%d')
			->btn_clone('', './?object=manage_shop&action=product_clone&id=%d')
			->btn_active('', './?object=manage_shop&action=product_activate&id=%d')
			->footer_add('Add product', './?object=manage_shop&action=product_add',array('no_ajax' => 1))
			->footer_link('Attributes', './?object=manage_shop&action=attributes')
			->footer_link('Categories', './?object=category_editor&action=show_items&id=shop_cats')
			->footer_link('Orders', './?object=manage_shop&action=show_orders');
	}

	function add_clear_pattern () {
		if (!empty($_POST)) {
			if (!$_POST["name"]) {
				_re("Product name must be filled");
			}
			if ($_POST["ext_url"]) {
				if (substr($_POST["ext_url"], 0, 7) !== "http://") {
					$_POST["ext_url"] = "http://".$_POST["ext_url"];
				}
			}
			if (!common()->_error_exists()) {
				$sql_array = array(
					"name"				=> _es($_POST["name"]),
					"model"				=> _es($_POST["model"]),
					"cat_id"			=> _es($_POST["cat_id"]),
					"url"				=> _es(common()->_propose_url_from_name($_POST["name"])),
					"description"		=> _es($_POST["desc"]),
					"meta_keywords"		=> _es($_POST["meta_keywords"]),
					"meta_desc"			=> _es($_POST["meta_desc"]),
					"external_url"		=> _es($_POST["ext_url"]),
					"quantity"			=> intval($_POST["quantity"]),
					"manufacturer_id"	=> intval($_POST["manufacturer"]),
					"supplier_id"		=> intval($_POST["supplier"]),
					"price"				=> number_format($_POST["price"], 2, '.', ''),
					"price_promo"		=> number_format($_POST["price_promo"], 2, '.', ''),
					"price_partner"		=> number_format($_POST["price_partner"], 2, '.', ''),
					"price_raw"			=> number_format($_POST["price_raw"], 2, '.', ''),
					"old_price"			=> number_format($_POST["old_price"], 2, '.', ''),
					"featured"			=> intval((bool)$_POST["featured"]),
					"currency"			=> "",// TODO
					"add_date"			=> $_SERVER['REQUEST_TIME'],
					"active"			=> 1,
				);
				db()->INSERT(db('shop_products'), $sql_array);
				$product_id = db()->INSERT_ID();				
				foreach ((array)$_POST["category"] as $k => $v){
					$cat_id ["product_id"] = $product_id;
					$cat_id ["category_id"] = $v;
					db()->INSERT(db('shop_product_to_category'), $cat_id);
				}
				
				if (count($_POST['productparams']) != 0)
					foreach ($_POST['productparams'] as $param_id)
						if (intval($param_id) != 0) 
							foreach($_POST['productparams_options_' . $param_id] as $v)
								db()->INSERT("shop_products_productparams",array(
									"product_id" => $product_id,
									"productparam_id" => $param_id,
									"value"	=> $v,
								));			
				
				// Image upload
				if (!empty($_FILES)) {
					module("manage_shop")->_product_image_upload($product_id);
					module("manage_shop")->_product_images_add_revision($product_id);
				} 
				common()->admin_wall_add(array('shop product added: '.$_POST['name'], $product_id));
				module("manage_shop")->_attributes_save($product_id);
			}
			module("manage_shop")->_product_add_revision('edit',$product_id);
			
			return js_redirect("./?object=manage_shop&action=products");
		}
		// 1-st type of assigning attributes
		$fields = module("manage_shop")->_attributes_html(0);
		// 2-nd type of assigning attributes (select boxes)
		// For case when we need just select custom attributes only one value of each
		$all_atts	= module("manage_shop")->_get_attributes();
		foreach ((array)$all_atts as $_attr_id => $_attr_info) {
			$_name_in_form = "single_attr[".$_attr_id."]";
			$_selected = "";
			$single_atts[$_attr_info["title"]] = array(
				"title"			=> _prepare_html($_attr_info["title"]),
				"name_in_form"	=> _prepare_html($_name_in_form),
				"box"			=> common()->select_box($_name_in_form, $_attr_info["value_list"], $_selected, false, 2, "", false),
			);
		}

		foreach ((array)module("manage_shop")->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = module("manage_shop")->_box($item_name, $SF[$item_name]);
		}
		
		return tpl()->parse("manage_shop/product_edit", $replace);
	}

	/*
	 * 
	 */
	function activate_clear_pattern () {
		if ($_GET['id']){
			$A = db()->query_fetch('SELECT * FROM '.db('shop_patterns').' WHERE id='.intval($_GET['id']));
			if ($A['active'] == 1) {
				$active = 0;
			} elseif ($A['active'] == 0) {
				$active = 1;
			}
			db()->UPDATE(db('shop_patterns'), array('active' => $active), 'id='.intval($_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($active ? 1 : 0);
		} else {
			return js_redirect('./?object=manage_shop&action=');
		}
	}

	/**
	*/
	function delete_clear_pattern () {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return 'Empty ID!';
		}
		db()->query('DELETE FROM '.db('shop_patterns').' WHERE id='.$_GET['id']);
		return js_redirect('./?object=manage_shop&action=show_clear_patterns');
	}

	/**
	*/
	function clone_clear_pattern () {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return 'Empty ID!';
		}
		$info = db()->query_fetch('SELECT * FROM '.db('shop_patterns').' WHERE id='.intval($_GET['id']));
		if (empty($info['id'])) {
			return _e('No such product!');
		}
		$sql = $info;
		$old_product_id = $sql['id'];
		unset($sql['id']);
		$sql['name'] = 'Clone '.$sql['name'];
		$sql['active'] = 0;

		db()->insert('shop_patterns', $sql);
		
		return js_redirect('./?object=manage_shop&action=show_clear_patterns');
	}

	/**
	*/
	function show_patterns_by_category ($cat = '') {
		main()->NO_GRAPHICS = true;
		$cat_id =  $_GET['cat_id'];
		$sql1 = 'SELECT product_id FROM '.db('shop_product_to_category').' WHERE category_id ='. $cat_id ;
			$products = db()->query($sql1);
			while ($A = db()->fetch_assoc($products)) {
				$product_info .= $A['product_id'].',';
			}	
			$product_info = rtrim($product_info, ',');
			
		$sql = 'SELECT * FROM '.db('shop_patterns').' WHERE active="1" AND id IN ('.$product_info .')  ORDER BY name';
		$product = db()->query_fetch_all($sql);
		$products = array();
		foreach ((array)$product as $v) {
			$products []  = array (
				'product_id'	=> $v['id'],
				'name'			=> $v['name'],
			);
		}
		echo json_encode($products);
	}	
}
