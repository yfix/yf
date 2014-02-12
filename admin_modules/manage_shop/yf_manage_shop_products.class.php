<?php

/**
*/
class yf_manage_shop_products{

	/**
	*/
	function products () {
		if (module('manage_shop')->SUPPLIER_ID) {
			$sql = 'SELECT p.* FROM '.db('shop_products').' AS p
					INNER JOIN '.db('shop_admin_to_supplier').' AS m ON m.supplier_id = p.supplier_id 
					WHERE 
						m.admin_id='.intval(main()->ADMIN_ID).'';
		} else {
			$sql = 'SELECT * FROM '.db('shop_products').' AS p';
		}
		return table($sql, array(
				'filter' => $_SESSION[$_GET['object'].'__products'],
				'filter_params' => array(
					'name'			=> array('like','p.name'),
					'price' 		=> array('between','p.price'),
					'articul'		=> array('like','p.articul'),
					'price'			=> array('eq','p.price'),
					'supplier_id'	=> array('eq','p.supplier_id'),
					'manufacturer_id' => array('eq','p.manufacturer_id'),
					'active'		=> array('eq','p.active'),
					'image'			=> array('eq','p.image'),
					'cat_id'		=> array('field' => 'p.cat_id'),
					'quantity'		=> array('field' => 'p.quantity'),
					'add_date'		=> array('dt_between', 'p.add_date'),
					'update_date'	=> array('field' => 'p.update_date'),
				),
				'hide_empty' => 1,
			))
			->image('id', '', array('width' => '50px', 'img_path_callback' => function($_p1, $_p2, $row) {
                $product_id = $row['id'];
				$image = common()->shop_get_images($product_id);
                return $image[0]['thumb'];

            }))
			->text('name', array('link' => '/shop/product/%d', 'rewrite' => 1, 'data' => '@name', 'link_field_name' => 'id'))
			->link('cat_id', './?object=category_editor&action=edit_item&id=%d', _class('cats')->_get_items_names_cached('shop_cats'))
			->text('price')
			->text('quantity')
			->date('add_date', array('format' => 'full', 'nowrap' => 1))
			->text('articul')
			->btn_edit('', './?object='.main()->_get('object').'&action=product_edit&id=%d',array('no_ajax' => 1))
			->btn_delete('', './?object='.main()->_get('object').'&action=product_delete&id=%d')
			->btn_clone('', './?object='.main()->_get('object').'&action=product_clone&id=%d')
			->btn_active('', './?object='.main()->_get('object').'&action=product_activate&id=%d')
			->footer_add('Add product', './?object='.main()->_get('object').'&action=product_add',array('no_ajax' => 1))
			->footer_link('Attributes', './?object='.main()->_get('object').'&action=attributes')
			->footer_link('Categories', './?object=category_editor&action=show_items&id=shop_cats')
			->footer_link('Orders', './?object='.main()->_get('object').'&action=show_orders');
	}

	/**
	*/
	function _get_product($pid) {
		if (module('manage_shop')->SUPPLIER_ID) {
			$sql = 'SELECT p.* FROM '.db('shop_products').' AS p
					INNER JOIN '.db('shop_admin_to_supplier').' AS m ON m.supplier_id = p.supplier_id 
					WHERE 
						p.id='.intval($pid).'
						AND m.admin_id='.intval(main()->ADMIN_ID).'';
		} else {
			$sql = 'SELECT * FROM '.db('shop_products').' WHERE id='.intval($pid);
		}
		return db()->get($sql);
	}

	/**
	*/
	function product_activate () {
		if ($_GET['id']) {
			$a = $this->_get_product($_GET['id']);
		}
		if ($a['id']) {
			if ($a['active'] == 1) {
				$active = 0;
			} elseif ($a['active'] == 0) {
				$active = 1;
			}
			db()->update_safe(db('shop_products'), array('active' => $active), 'id='.intval($_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($active ? 1 : 0);
		} else {
			return js_redirect('./?object='.main()->_get('object').'');
		}
	}

	/**
	*/
	function product_delete () {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$a = $this->_get_product($_GET['id']);
		}
		if ($a['id']) {
			module('manage_shop')->_product_check_first_revision('product', $_GET['id']);
			module('manage_shop')->_product_image_delete($_GET['id']);
			db()->query('DELETE FROM '.db('shop_product_to_category').' WHERE product_id='.$_GET['id']);		
			db()->query('DELETE FROM '.db('shop_product_to_region').' WHERE product_id='.$_GET['id']);		
			db()->query('DELETE FROM '.db('shop_product_productparams').' WHERE product_id='.$_GET['id']);
			db()->query('DELETE FROM '.db('shop_products').' WHERE id='.$_GET['id']);
			module("manage_shop")->_product_add_revision('delete',$_GET['id']);
			common()->admin_wall_add(array('shop product deleted: '.$_GET['id'], $_GET['id']));
		}
		return js_redirect('./?object='.main()->_get('object').'action=products');
	}

	/**
	*/
	function product_clone () {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$a = $this->_get_product($_GET['id']);
		}
		if (!$a['id']) {
			return _e('No such product!');
		}
		$sql = $a;
		$old_product_id = $sql['id'];
		unset($sql['id']);
		$sql['name'] = 'Clone '.$sql['name'];
		$sql['active'] = 0;

		db()->insert('shop_products', $sql);
		$new_product_id = db()->insert_id();
		common()->admin_wall_add(array('shop product cloned: '.$a['name'], $new_product_id));
		
		$arr =  db()->get_all("SELECT * FROM `".db('shop_products_productparams')."` WHERE `product_id`='{$new_product_id}'");
		foreach ($arr as $v) {
			db()->INSERT(array(
				'product_id' => $new_product_id,
				'productparam_id' => $v['productparam_id'],
				'value' => $v['value'],
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
		return js_redirect('./?object='.main()->_get('object').'action=products');
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
	
	function product_search_autocomplete () {
		$_GLOBALS['no_graphics'] = true;
		if(!$_GET['search_word']) return false;
		$word = common()->sphinx_escape_string($_GET['search_word']);
//		$word = str_replace("_", " ", common()->_propose_url_from_name($word));
		$result = common()->sphinx_query("
			SELECT product_id,name 
			FROM products 
			WHERE MATCH ('@name ".$word."*')
			LIMIT 20"
		);
		if(!$result) return false;
		foreach((array)$result as $k){
			$return_array[] = array(
				'id' => $k['product_id'],
				'text' => '['.$k['product_id'].'] '.$k['name'],
			);
		}
		return json_encode($return_array);

	}

}