<?php

class yf_manage_shop_product_sets {

// TODO: improve filter

	function _init() {
		// Params for the 'admin_methods'
		$this->_table = array(
			'table' => 'shop_product_sets',
			'fields' => array(
				'active',
				'cat_id',
				'price',
				'old_price',
				'name',
				'description',
			),
			'back_link' => './?object='.main()->_get('object').'&action=product_sets',
		);

		$this->_sql_sets_prices_total =
			'SELECT product_set_id, SUM(qprice) AS total
			FROM (
				SELECT i.product_set_id, p.price * i.quantity AS qprice, p.price, i.quantity
				FROM '.db('shop_product_sets_items').' AS i
				INNER JOIN '.db('shop_products').' AS p ON p.id = i.product_id
			) AS tmp
			GROUP BY product_set_id';

		$this->_sql_set_list_products =
			'SELECT psi.product_id AS id, psi.product_id, p.active, p.image, p.name, psi.quantity, p.price
			FROM '.db('shop_product_sets_items').' AS psi
			LEFT JOIN '.db('shop_products').' AS p ON p.id = psi.product_id
			WHERE psi.product_set_id = %sid';
	}

	function product_sets () {
		return table('SELECT * FROM '.db('shop_product_sets'), array(
				'filter' => $_SESSION[$_GET['object'].'__product_sets'],
				'custom_fields' => array(
					'products_items' => 'SELECT product_set_id, COUNT(*) AS num FROM '.db('shop_product_sets_items').' GROUP BY product_set_id',
					'products_price' => $this->_sql_sets_prices_total,
				),
			))
			->image('id', 'uploads/shop/product_sets/%d_thumb.jpg', array('width' => '50px'))
			->text('name')
			->text('price')
			->text('products_price')
			->text('products_items')
			->link('cat_id', './?object=category_editor&action=show_items&id=%d', _class('cats')->_get_items_names_cached('shop_cats'))
			->btn_active('', './?object='.main()->_get('object').'&action=product_set_active&id=%d')
			->btn_edit( '','./?object='.main()->_get('object').'&action=product_set_edit&id=%d', array( 'no_ajax' => true ) )
			->btn_delete('','./?object='.main()->_get('object').'&action=product_set_delete&id=%d')
			->footer_add('','./?object='.main()->_get('object').'&action=product_set_add')
		;
	}

	function product_set_add () {
		$replace = (array)_class('admin_methods')->add($this->_table);
		$replace['form_action'] = './?object='.main()->_get('object').'&action=product_set_add';
		return form($replace)
			->text('name')
			->save_and_back()
		;
	}

	function product_set_edit () {
		$product_set_id = (int)$_GET['id'];
		$a = db()->from('shop_product_sets')->whereid($product_set_id)->get();

		if (input()->is_post()) {
			// save image
			if( $_FILES ) {
				$result = true;
				if( main()->is_ajax() ) {
					// prepare file options
					$path      = 'uploads/shop/product_sets/';
					$file_path = PROJECT_PATH . $path;
					$uri_path  = WEB_PATH     . $path;
					$file_original = $file_path . $product_set_id . '.jpg';
					$file_big      = $file_path . $product_set_id . '_big.jpg';
					$file_thumb    = $file_path . $product_set_id . '_thumb.jpg';
					$url_thumb     = $uri_path  . $product_set_id . '_thumb.jpg';
					$file_watermark = PROJECT_PATH . SITE_WATERMARK_FILE;
					$max_width  = module( 'manage_shop' )->BIG_X;
					$max_height = module( 'manage_shop' )->BIG_Y;
					// processing upload
					$upload_handler = _class( 'upload_handler' );
					$upload_handler->options( 'param_name', 'image' );
					$result = $upload_handler->post_handler( array(
						'image_versions' => array(
							'image' => array(
								'original' => array(
									'file'       => $file_original
								),
								'big' => array(
									'max_width'  => $max_width,
									'max_height' => $max_height,
									'file'       => $file_big,
									'watermark'  => $file_watermark,
								),
								'thumbnail' => array(
									'max_width'  => 324,
									'max_height' => 216,
									'file'       => $file_thumb,
								),
							)
						),
						// 'upload_remove' => false,
					));
					if( empty( $result[ 'versions' ] ) ) {
						$status    = false;
						$url_thumb = false;
					} else {
						$status = true;
					}
					echo json_encode( array(
						'status' => $status,
						'image'  => $url_thumb,
					));
					exit;
				}
			}
			$up = array();
			// Add products to current set
			if ($_POST['products_ids']) {
				$ids = array();
				foreach(explode(',', $_POST['products_ids']) as $id) {
					$id = intval($id);
					$id && $ids[$id] = $id;
				}
				if ($ids) {
					$ids = db()->select('id')->from('shop_products')->whereid($ids)->get_2d();
					$ids && $ids = array_combine($ids, $ids);
				}
				$insert_items = array();
				if ($ids) {
					$current_ids = array_keys( (array)db()->get_all(str_replace('%sid', $product_set_id, $this->_sql_set_list_products)) );
					$current_ids && $current_ids = array_combine($current_ids, $current_ids);
					foreach ((array)$ids as $id) {
						if (isset($current_ids[$id])) {
							continue;
						}
						$insert_items[$id] = array(
							'product_set_id' => $product_set_id,
							'product_id'     => $id,
							'quantity'       => 1,
						);
					}
				}
				if ($insert_items) {
					db()->replace_safe('shop_product_sets_items', $insert_items);
				}
			}
			// Editable quantity for each product in list
			if (!empty($_POST['quantity']) && is_array($_POST['quantity'])) {
				$current_items = (array)db()->get_all(str_replace('%sid', $product_set_id, $this->_sql_set_list_products));
				$current_ids = array_keys($current_items);
				$current_ids && $current_ids = array_combine($current_ids, $current_ids);
				foreach ((array)$_POST['quantity'] as $id => $quantity) {
					if (!isset($current_ids[$id])) {
						continue;
					}
					$item = $current_items[$id];
					if ($item['quantity'] == $quantity) {
						continue;
					}
					$qup = array(
						'product_id'=> (int)$id,
						'quantity'	=> (int)$quantity,
					);
					db()->update_safe('shop_product_sets_items', $qup, 'product_id='.(int)$id.' AND product_set_id='.(int)$product_set_id);
				}
			}
			// Process common form fields
			foreach ((array)$this->_table['fields'] as $f) {
// TODO: form validation, name=required
				if ($a[$f] != $_POST[$f] && isset($_POST[$f])) {
					$up[$f] = $_POST[$f];
				}
			}
			// Count price from real product prices
			if (empty($_POST['price'])) {
				$product_prices = db()->get_2d($this->_sql_sets_prices_total);
				$total_price = $product_prices[$product_set_id];
				if ($total_price) {
					$up['price'] = todecimal($total_price);
				}
			}
			if ($up) {
				db()->update_safe('shop_product_sets', $up, $product_set_id);
			}
			return js_redirect('');
		}
		$product_prices = db()->get_2d($this->_sql_sets_prices_total);

		$a = (array)$_POST + (array)$a;
		$a['form_action'] = './?object='.main()->_get('object').'&action=product_set_edit&id='.$product_set_id;
		$a['back_link'] = './?object='.main()->_get('object').'&action=product_sets';
		$a['products_price'] = $product_prices[$product_set_id];
		$image = _class( '_shop_products', 'modules/shop/' )->_product_set_image( $product_set_id, $a[ 'cat_id' ], 'thumb' );
		return form($a)
			->upload( 'image', 'Изображение', array( 'preview' => $image ) )
		. form($a)
			->text('name')
			->textarea('description')
			->text('price', array('class' => 'input-mini')) // TODO: float() input type
			->info('products_price')
			->container(
				table(str_replace('%sid', $product_set_id, $this->_sql_set_list_products), array('pager_records_on_page' => 1000))
				->image('id', array('width' => '50px', 'no_link' => 1, 'img_path_callback' => function($_p1, $_p2, $row) {
					$image = common()->shop_get_images($row['id']);
					return $image[0]['thumb'];
    	        }))
				->text('product_id', array('link' => './?object=manage_shop&action=product_edit&id=%d'))
				->text('name')
				->text('price')
				->input('quantity', array('class' => 'input-mini', 'type' => 'number'))
				->btn_delete('', './?object='.main()->_get('object').'&action=product_set_delete&product_id=%d&id='. $product_set_id)
				->btn_active('active', array(), array('disabled' => 1))
#				, array('wide' => 1)
			)
			->container(
				_class('manage_shop_filter', 'admin_modules/manage_shop/')->_product_search_widget('products_ids', $replace[ 'products_ids' ], true)
				, 'Добавить продукты'
			)
			->select_box('cat_id', module('manage_shop')->_cats_for_select)
			->active_box()
			->save_and_back()
		;
	}

	function product_set_active () {
		return _class('admin_methods')->active($this->_table);
	}

	function product_set_delete () {
		if (!empty($_GET['product_id'])) {
			$result = db()->query( 'DELETE FROM '.db('shop_product_sets_items').' WHERE product_set_id = '.(int)$_GET['id'].' AND product_id = '.(int)$_GET['product_id'] );
			return $result;
		} else {
			return _class('admin_methods')->delete($this->_table);
		}
	}
}
