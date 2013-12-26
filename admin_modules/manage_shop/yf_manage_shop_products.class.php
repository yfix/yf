<?php

/**
*/
class yf_manage_shop_products{

	/**
	*/
	function products () {
		return table('SELECT * FROM '.db('shop_products'), array(
				'filter' => $_SESSION[$_GET['object'].'__products'],
				'filter_params' => array(
					'name'	=> 'like',
					'price' => 'between',
				),
			))
			->image('id', '', array('width' => '50px', 'img_path_callback' => function($_p1, $_p2, $row) {
                $product_id = $row['id'];
				$image = common()->shop_get_images($product_id);
                return $image[0]['thumb'];

            }))
			->text('name')
			->link('cat_id', './?object=category_editor&action=show_items&&id=%d', _class('cats')->_get_items_names_cached('shop_cats'))
			->text('price')
			->text('quantity')
			->date('add_date')
			->btn_edit('', './?object=manage_shop&action=product_edit&id=%d',array('no_ajax' => 1))
			->btn_delete('', './?object=manage_shop&action=product_delete&id=%d')
			->btn_clone('', './?object=manage_shop&action=product_clone&id=%d')
			->btn_active('', './?object=manage_shop&action=product_activate&id=%d')
			->footer_add('Add product', './?object=manage_shop&action=product_add',array('no_ajax' => 1))
			->footer_link('Attributes', './?object=manage_shop&action=attributes')
			->footer_link('Categories', './?object=category_editor&action=show_items&id=shop_cats')
			->footer_link('Orders', './?object=manage_shop&action=show_orders');
	}

	/**
	*/
	function product_activate () {
		if ($_GET['id']){
			$A = db()->query_fetch('SELECT * FROM '.db('shop_products').' WHERE id='.intval($_GET['id']));
			if ($A['active'] == 1) {
				$active = 0;
			} elseif ($A['active'] == 0) {
				$active = 1;
			}
			db()->UPDATE(db('shop_products'), array('active' => $active), 'id='.intval($_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($active ? 1 : 0);
		} else {
			return js_redirect('./?object=manage_shop');
		}
	}

	/**
	*/
	function product_delete () {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return 'Empty ID!';
		}
		module('manage_shop')->_product_image_delete($_GET['id']);
		db()->query('DELETE FROM '.db('shop_product_to_category').' WHERE product_id='.$_GET['id']);		
		db()->query('DELETE FROM '.db('shop_product_to_region').' WHERE product_id='.$_GET['id']);		
		db()->query('DELETE FROM '.db('shop_product_productparams').' WHERE product_id='.$_GET['id']);
		db()->query('DELETE FROM '.db('shop_products').' WHERE id='.$_GET['id']);
		module("manage_shop")->_product_add_revision('delete',$_GET['id']);
		common()->admin_wall_add(array('shop product deleted: '.$_GET['id'], $_GET['id']));
		return js_redirect('./?object=manage_shopaction=products');
	}

	/**
	*/
	function product_clone () {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return 'Empty ID!';
		}
		$info = db()->query_fetch('SELECT * FROM '.db('shop_products').' WHERE id='.intval($_GET['id']));
		if (empty($info['id'])) {
			return _e('No such product!');
		}
		$sql = $info;
		$old_product_id = $sql['id'];
		unset($sql['id']);
		$sql['name'] = 'Clone '.$sql['name'];
		$sql['active'] = 0;

		db()->insert('shop_products', $sql);
		$new_product_id = db()->insert_id();
		common()->admin_wall_add(array('shop product cloned: '.$info['name'], $new_product_id));
		
		$arr =  db()->get_all("SELECT * FROM `".db('shop_products_productparams')."` WHERE `product_id`='{$new_product_id}'");
		foreach ($arr as $v) {
			db()->INSERT(array(
				'product_id' => $new_product_id,
				'productparam_id' => $v['productparam_id'],
				'value' => $v['value'],
				'articul' => $v['articul'],
				'item_quantity' => $v['item_quantity'],
				'barcode' => $v['barcode'],
			));
		}
		$arr =  db()->get_all("SELECT * FROM `".db('shop_product_to_category')."` WHERE `product_id`='{$new_product_id}'");
		foreach ($arr as $v) {
			db()->INSERT(array(
				'product_id' => $new_product_id,
				'category_id' => $v['category_id'],				
			));
		}
		$arr =  db()->get_all("SELECT * FROM `".db('shop_product_to_region')."` WHERE `product_id`='{$new_product_id}'");
		foreach ($arr as $v) {
			db()->INSERT(array(
				'product_id' => $new_product_id,
				'region_id' => $v['region_id'],
			));
		}
		$arr =  db()->get_all("SELECT * FROM `".db('shop_product_related')."` WHERE `product_id`='{$new_product_id}'");
		foreach ($arr as $v) {
			db()->INSERT(array(
				'product_id' => $new_product_id,
				'related_id' => $v['related_id'],
			));
		}
			
		if ($sql['image'] && $new_product_id) {
			$dirs = sprintf('%06s', $old_product_id);
			$dir2 = substr($dirs, -3, 3);
			$dir1 = substr($dirs, -6, 3);
			$m_path = $dir1.'/'.$dir2.'/';
			$old_images = _class('dir')->scan_dir(
				module('manage_shop')->products_img_dir. $m_path,
				true,
				'/product_'.$old_product_id.'_.+?\.jpg/'
			);
			foreach((array)$old_images as $old_image_path) {
				$nd = sprintf('%06s', $new_product_id);
				$nd2 = substr($nd, -3, 3);
				$nd1 = substr($nd, -6, 3);
				$n_path = $nd1.'/'.$nd2.'/';
				$new_image_path = str_replace('/product_'.$old_product_id.'_', '/product_'.$new_product_id.'_', str_replace($m_path, $n_path, $old_image_path));
				$new_dir = dirname($new_image_path);
				if (!file_exists($new_dir)) {
					mkdir($new_dir, 0777, true);
				}
				copy($old_image_path, $new_image_path);
			}
		}
		return js_redirect('./?object=manage_shopaction=products');
	}

	/**
	*/
	function products_show_by_category ($cat = '') {
		main()->NO_GRAPHICS = true;
		$cat_id =  $_GET['cat_id'];
		$sql1 = 'SELECT product_id FROM '.db('shop_product_to_category').' WHERE category_id ='. $cat_id ;
			$products = db()->query($sql1);
			while ($A = db()->fetch_assoc($products)) {
				$product_info .= $A['product_id'].',';
			}	
			$product_info = rtrim($product_info, ',');
			
		$sql = 'SELECT * FROM '.db('shop_products').' WHERE active="1" AND id IN ('.$product_info .')  ORDER BY name';
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