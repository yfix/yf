<?php

class yf_manage_shop_product_edit {

	function product_edit () {
		$id = (int)$_GET[ 'id' ];
		$_GET[ 'id' ] = $id;
		if (empty($id)) {
			return _e('Empty id');
		}
		$product_info = module('manage_shop')->_product_get_info($id);
		if (empty($product_info['id'])) {
			return _e('Product not found');
		}
		// prepare region
		$_region = _class( '_shop_region', 'modules/shop/' )->_get_list();
		$region = _class( '_shop_region', 'modules/shop/' )->_get_by_product_ids( $id, $force = true );
			$region = $region[ $id ];
		// -----
		if (main()->is_post()) {
// TODO: use validation from form2()
			if (!$_POST['name']) {
				_re('Product name must be filled', 'name');
			}
			if (!common()->_error_exists()) {
				module('manage_shop')->_product_check_first_revision('product', $id);
				$sql = array(
					'url'				=> $_POST['url'] ?: common()->_propose_url_from_name($_POST['name']),
					'active'			=> intval((bool)$_POST['active']),
					'update_date'		=> time(),
				);
				foreach (array('name','description','model','articul','cat_id','meta_keywords','meta_desc','featured','external_url','sku','stock_status_id','manufacturer_id','supplier_id','quantity') as $k) {
					if (isset($_POST[$k])) {
						$sql[$k] = $_POST[$k];
					}
				}
				foreach (array('price','price_promo','price_partner','price_raw','old_price') as $k) {
					if (isset($_POST[$k])) {
						$sql[$k] = number_format($_POST[$k], 2, '.', '');
					}
				}
				db()->update_safe(db('shop_products'), $sql, 'id='.$id);

				if (!empty($_FILES)) {
					module('manage_shop')->_product_image_upload($id);
				}

				$params_to_insert = array();
				foreach ((array)$_POST['productparams'] as $param_id) {
					db()->query('DELETE FROM '.db('shop_products_productparams').' WHERE product_id='.$id);
					$param_id = intval($param_id);
					if (!$param_id) {
						continue;
					}
					foreach ((array)$_POST['productparams_options_' . $param_id] as $v) {
						$params_to_insert[] = array(
							'product_id' => $id,
							'productparam_id' => $param_id,
							'value'	=> $v,
						);
					}
				}
				if ($params_to_insert) {
					db()->insert_safe('shop_products_productparams', $params_to_insert);
				}

				$product_to_category_insert = array();
				foreach ((array)$_POST['category'] as $_cat_id) {
					$_cat_id = intval($_cat_id);
					if (!$_cat_id) {
						continue;
					}
					$product_to_category_insert[] = array(
						'product_id' => $id,
						'category_id' => $_cat_id,
					);
				}
				if ($product_to_category_insert) {
					db()->query('DELETE FROM '.db('shop_product_to_category').' WHERE product_id='.$id);
					db()->insert_safe(db('shop_product_to_category'), $product_to_category_insert);
				}

				$product_related_insert = array();
				foreach ((array)$_POST['product_related'] as $related_id) {
					$related_id = intval($related_id);
					if (!$related_id) {
						continue;
					}
					$product_related_insert[] = array(
						'product_id' => $id,
						'related_id' => $related_id,
					);
				}
				if ($product_related_insert) {
					db()->query('DELETE FROM '.db('shop_product_related').' WHERE product_id='.$id);
					db()->insert_safe(db('shop_product_related'), $product_related_insert);
				}

				// update region
				$_table  = 'shop_product_to_region';
				$_post   = _class( '_shop_region', 'modules/shop/' )->_check_by_product_id( $_POST[ 'region' ] );
				$_insert = array_diff( $_post, $region );
				$_delete = array_diff( $region, $_post );
				// insert
				if( !empty( $_insert ) ) {
					$_data = array();
					foreach( $_insert as $_id ) {
						$_data[] = array( 'product_id' => $id, 'region_id' => $_id );
					}
					db_query( 'START TRANSACTION' );
						db()->insert_on_duplicate_key_update( $_table, $_data );
					db_query( 'COMMIT' );
				}
				// delete
				if( !empty( $_delete ) ) {
					$_data = array( '__args__' => array(
						array( 'product_id', 'in', $id ),
						'and',
						array( 'region_id',  'in', $_delete ),
					));
					db_query( 'START TRANSACTION' );
						db()->delete( $_table, $_data );
					db_query( 'COMMIT' );
				}
				$region = _class( '_shop_region', 'modules/shop/' )->_get_by_product_ids( $id, true );
					$region = $region[ $id ];
				// -----
				$product_to_unit_insert = array();
				foreach ((array)$_POST['units'] as $_unit_id) {
					$_unit_id = (int)$_unit_id;
					if( empty( $_unit_id ) ) { continue; }
					$product_to_unit_insert[] = array(
						'product_id' => $id,
						'unit_id'    => $_unit_id,
					);
				}
				db()->query('DELETE FROM '.db('shop_product_to_unit').' WHERE product_id='.$id);
				if ($product_to_unit_insert) {
					db()->insert_safe(db('shop_product_to_unit'), $product_to_unit_insert);
				}

				module('manage_shop')->_attributes_save($id);
				module('manage_shop')->_product_add_revision('edit',$id);
				module('manage_shop')->_product_cache_purge($id);
				common()->admin_wall_add(array('shop product updated: '.$_POST['name'], $id));
				// sphinx reindex by flag file
				exec( 'touch /tmp/sphinx/indexer-kupi' );
				exec( 'touch /tmp/sphinx/indexer-kupi_dev' );
			}
			return js_redirect('./?object='.main()->_get('object').'&action=product_edit&id='.$id);
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
				'data-test'	 => 'delete_image_btn',
			));
		}
		$products_to_category = array();
		foreach ((array)db()->get_all('SELECT category_id FROM '.db('shop_product_to_category').' WHERE product_id='.$id) as $a) {
			$products_to_category[$a['category_id']] = $a['category_id'];
		}
		$products_to_unit = array();
		foreach ((array)db()->get_all('SELECT unit_id FROM '.db('shop_product_to_unit').' WHERE product_id='.$id) as $a) {
			$products_to_unit[$a['unit_id']] = $a['unit_id'];
		}
		$replace = $product_info + array(
			'form_action' => './?object='.main()->_get('object').'&action=product_edit&id='.$product_info['id'],
			'back_url'    => './?object='.main()->_get('object').'&action=products',
			'units'       => $products_to_unit,
		);
		$textarea_id = 'description';
		return form($replace, array(
// TODO: use validation
				'for_upload' => 1,
				'currency' => module('manage_shop')->CURRENCY,
				'hide_empty' => 1,
				'tabs'	=> array(
					'class' => 'span6 col-md-6',
					'show_all' => 1,
					'no_headers' => 1,
				),
			))
		->tab_start('tab_desc', array('tab_body' => array('class' => 'active span12 col-md-12')))
			->textarea('description', array('style' => 'min-width:100%', 'cols' => 200, 'rows' => 10, 'ckeditor' => array('config' => _class('admin_methods')->_get_cke_config())))
		->tab_end()
		->tab_start('main')
			->link('product_url_user', url_user('/shop/product/'.$product_info['id']), array('target' => '_blank'))
			->info('id')
			->text('name')
			->text('articul')
			->text('url')
			->chosen_box('cat_id', module('manage_shop')->_cats_for_select, array('desc' => 'Main category', 'edit_link' => './?object=category_editor&action=show_items&id=shop_cats', 'translate' => 0, 'data-test' => 'select_category'))
// TODO: replace with similar JS container as for params and images
#			->multi_select('category', module('manage_shop')->_cats_for_select, array('desc' => 'Secondary categories', 'edit_link' => './?object=category_editor&action=show_items&id=shop_cats', 'selected' => $products_to_category, 'translate' => 0))
			->chosen_box('manufacturer_id', module('manage_shop')->_man_for_select, array('desc' => 'Manufacturer', 'edit_link' => './?object='.main()->_get('object').'&action=manufacturers', 'translate' => 0, 'data-test' => 'select_manufacturer'))
			->chosen_box('supplier_id', module('manage_shop')->_suppliers_for_select, array('desc' => 'Supplier', 'edit_link' => './?object='.main()->_get('object').'&action=suppliers', 'data-test' => 'select_supplier'))
			->select2_box( array(
				'desc'      => 'Регион',
				'name'      => 'region',
				'multiple'  => true,
				'values'    => $_region,
				'selected'  => $region,
				'edit_link' => url_admin( '/manage_shop/region' ),
				'data-test' => 'select_region',
			))
			->number('quantity', array('min' => 0))
			->active_box('active')
		->tab_end()
		->tab_start('params')
			->link('Search images', './?object='.main()->_get('object').'&action=product_image_search&id='.$product_info['id'], array('class_add' => 'btn-success', 'data-test' => 'search_image_btn'))
			->container(
				($images_items ? implode(PHP_EOL, $images_items) : '').
				'<a class="btn btn-default btn-mini btn-xs" data-test="add_image" onclick="addImage();"><span>'.t('Add Image').'</span></a> <div id="images"></div>'
				, array('desc' => 'Images')
			)
			->link('Set main image', './?object='.$_GET['object'].'&action=set_main_image&id='.$product_info['id'], array(
				'class_add' => 'ajax_edit',
				'data-test' => 'set_main_image_btn',
				'display_func' => function() use ($images_items) { return (is_array($images_items) && count($images_items) > 1); }
			))
			->container(module('manage_shop')->_productparams_container($id), array('desc' => 'Product params'/*, 'edit_link' => './?object='.main()->_get('object').'&action=attributes'*/))
// TODO: replace with similar JS container as for params and images
#			->container(module('manage_shop')->related_products($product_info['id']), array('desc' => 'Related products'))
			// ->multi_select2_box('units', module('manage_shop')->_units_for_select, array('desc' => 'Units', 'edit_link' => './?object='.main()->_get('object').'&action=units', 'show_text' => 1))
			->select2_box( array(
				'desc'      => 'Ед. измерения',
				'name'      => 'units',
				'multiple'  => true,
				'values'    => module('manage_shop')->_units_for_select,
				'edit_link' => url_admin( '/manage_shop/units' ),
				'data-test' => 'select_units',
			))
			->price('old_price')
			->price('price')
			->price('price_promo')
			->price('price_partner')
			->price('price_raw')
		->tab_end()
		->tab_start('tab_save', array('tab_body' => array('class' => 'active span12 col-md-12')))
			->save_and_back()
		->tab_end()
			.tpl()->parse('manage_shop/product_edit_js');
	}
}
