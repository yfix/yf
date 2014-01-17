<?php

class yf_manage_shop_product_edit {

	function product_edit () {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e('Empty id');
		}
		if (module('manage_shop')->SUPPLIER_ID) {
			$sql = 'SELECT p.* FROM '.db('shop_products').' AS p
					INNER JOIN '.db('shop_admin_to_supplier').' AS m ON m.supplier_id = p.supplier_id 
					WHERE p.id='.intval($_GET['id']).'
						AND m.admin_id='.intval(main()->ADMIN_ID).'';
		} else {
			$sql = 'SELECT * FROM '.db('shop_products').' WHERE id='.$_GET['id'];
		}
		$product_info = db()->get($sql);
		if (empty($product_info['id'])) {
			return _e('Product not found');
		}
		if (main()->is_post()) {
// TODO: use validation from form2()
			if (!$_POST['name']) {
				_re('Product name must be filled', 'name');
			}
			if (!common()->_error_exists()) {
				$sql_array = array(
					'url'				=> $_POST['url'] ?: common()->_propose_url_from_name($_POST['name']),
					'price'				=> number_format($_POST['price'], 2, '.', ''),
					'price_promo'		=> number_format($_POST['price_promo'], 2, '.', ''),
					'price_partner'		=> number_format($_POST['price_partner'], 2, '.', ''),
					'price_raw'			=> number_format($_POST['price_raw'], 2, '.', ''),
					'active'			=> intval((bool)$_POST['active']),
					'update_date'		=> time(),
				);
				foreach (array('name','description','model','articul','cat_id','meta_keywords','meta_desc','featured','external_url','sku','stock_status_id','old_price','manufacturer_id','supplier_id','quantity') as $k) {
					if (isset($_POST[$k])) {
						$sql_array[$k] = $_POST[$k];
					}
				}
				if (!empty($_FILES)) {
					$last_img_insert_id = module('manage_shop')->_product_image_upload($_GET['id']);
					if ($last_img_insert_id) {
						module('manage_shop')->_product_images_add_revision($_GET['id']);
					}
				} 
				db()->update_safe(db('shop_products'), $sql_array, 'id='.$_GET['id']);
				
				$params_to_insert = array();
				foreach ((array)$_POST['productparams'] as $param_id) {
					$param_id = intval($param_id);
					if (!$param_id) {
						continue;
					}
					foreach ((array)$_POST['productparams_options_' . $param_id] as $v) {
						$params_to_insert[] = array(
							'product_id' => $_GET['id'],
							'productparam_id' => $param_id,
							'value'	=> $v,
						);
					}
				}
				if ($params_to_insert) {
					db()->query('DELETE FROM '.db('shop_products_productparams').' WHERE product_id='.intval($_GET['id']));
					db()->insert_safe('shop_products_productparams', $params_to_insert);
				}

				$product_to_category_insert = array();
				foreach ((array)$_POST['category'] as $_cat_id) {
					$_cat_id = intval($_cat_id);
					if (!$_cat_id) {
						continue;
					}
					$product_to_category_insert[] = array(
						'product_id' => $_GET['id'],
						'category_id' => $v,
					);
				}
				if ($product_to_category_insert) {
					db()->query('DELETE FROM '.db('shop_product_to_category').' WHERE product_id='.intval($_GET['id']));
					db()->insert_safe(db('shop_product_to_category'), $product_to_category_insert);
				}

				$product_related_insert = array();
				foreach ((array)$_POST['product_related'] as $related_id) {
					$related_id = intval($related_id);
					if (!$related_id) {
						continue;
					}
					$product_related_insert[] = array(
						'product_id' => $_GET['id'],
						'related_id' => $related_id,
					);
				}
				if ($product_related_insert) {
					db()->query('DELETE FROM '.db('shop_product_related').' WHERE product_id='.intval($_GET['id']));
					db()->insert_safe(db('shop_product_related'), $product_related_insert);
				}

				module('manage_shop')->_attributes_save($_GET['id']);
				module('manage_shop')->_product_add_revision('edit',$_GET['id']);
				module('manage_shop')->_product_cache_purge($_GET['id']);
				common()->admin_wall_add(array('shop product updated: '.$_POST['name'], $_GET['id']));
			}
			return js_redirect('./?object='.main()->_get('object').'&action=product_edit&id='.$_GET['id']);
		}

		$media_host = defined('MEDIA_HOST') ? MEDIA_HOST : false;
		$base_url = WEB_PATH;
		if (!empty($media_host)) {
			$base_url = '//' . $media_host . '/';
		}
		$images_items = array();
		foreach ((array)common()->shop_get_images($product_info['id']) as $a) {
			$images_items[] = tpl()->parse('manage_shop/image_items', array(
				'img_path'   => $base_url . $a['big'],
				'thumb_path' => $base_url . $a['thumb'],
				'del_url'    => './?object='.main()->_get('object').'&action=product_image_delete&id='.$product_info['id'].'&key='.$a['id'],
				'image_key'  => $a['id'],
			));
		}
		$products_to_category = array();
		foreach ((array)db()->get_all('SELECT category_id FROM '.db('shop_product_to_category').' WHERE product_id='.intval($_GET['id'])) as $a) {
			$products_to_category[$a['category_id']] = $a['category_id'];
		}	
		$replace = $product_info + array(
			'form_action'        => './?object='.main()->_get('object').'&action=product_edit&id='.$product_info['id'],
			'back_url'           => './?object='.main()->_get('object').'&action=products',
		);
		return form($replace, array(
// TODO: use validation
				'for_upload' => 1,
				'currency' => module('manage_shop')->CURRENCY,
				'hide_empty' => 1
			))
			->link('product_url_user', url('/shop/product/'.$product_info['id']), array('target' => '_blank'))
			->text('name')
			->text('articul')
			->text('url')
			->select_box('cat_id', module('manage_shop')->_cats_for_select, array('desc' => 'Main category', 'edit_link' => './?object=category_editor&action=show_items&id=shop_cats'))
// TODO: replace with similar JS container as for params and images
#			->multi_select('category', module('manage_shop')->_cats_for_select, array('desc' => 'Secondary categories', 'edit_link' => './?object=category_editor&action=show_items&id=shop_cats', 'selected' => $products_to_category))
			->select_box('manufacturer_id', module('manage_shop')->_man_for_select, array('desc' => 'Manufacturer', 'edit_link' => './?object='.main()->_get('object').'&action=manufacturers'))
			->select_box('supplier_id', module('manage_shop')->_suppliers_for_select, array('desc' => 'Supplier', 'edit_link' => './?object='.main()->_get('object').'&action=suppliers'))
			->textarea('description')
			->money('price')
			->money('price_promo')
			->money('price_partner')
			->money('price_raw')
			->number('quantity')
			->container(
				($images_items ? implode(PHP_EOL, $images_items) : ''). 
				'<a class="btn btn-mini" onclick="addImage();"><span>'.t('Add Image').'</span></a> <div id="images"></div>'
				, array('desc' => 'Images')
			)
			->link('Set main image', './?object='.$_GET['object'].'&action=set_main_image&id='.$product_info['id'], array(
				'class_add' => 'ajax_edit',
				'display_func' => function() use ($images_items) { return (is_array($images_items) && count($images_items) > 1); }
			))
			->link('Search images', './?object='.main()->_get('object').'&action=product_image_search&id='.$product_info['id'], array('class_add' => 'btn-success'))
			->container(module('manage_shop')->_productparams_container($_GET['id']), array('desc' => 'Product params'/*, 'edit_link' => './?object='.main()->_get('object').'&action=attributes'*/))
// TODO: replace with similar JS container as for params and images
#			->container(module('manage_shop')->related_products($product_info['id']), array('desc' => 'Related products'))
			->active_box('active')
			->save_and_back()
			.tpl()->parse('manage_shop/product_edit_js');
	}
}
