<?php
class yf_manage_shop_product_sets{

// TODO: check and test everything

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
	}

	function product_sets () {
		return table('SELECT * FROM '.db('shop_product_sets'), array(
				'filter' => $_SESSION[$_GET['object'].'__product_sets']
			))
			->image('id', 'uploads/shop/product_sets/%d.jpg', array('width' => '50px'))
			->text('name')
			->text('description')
			->text('price')
			->text('old_price')
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
		$product_set_id = (int)$_GET[ 'id' ];
		$replace = (array)_class('admin_methods')->edit($this->_table);
		$replace['form_action'] = './?object='.main()->_get('object').'&action=product_set_edit&id='.$product_set_id;

		if( main()->is_post() ) {
			// add product to set
			$products_ids = $_POST[ 'products_ids' ];
			if( !empty( $products_ids ) ) {
				$products_ids = explode( ',', $products_ids );
				if( !empty( $products_ids ) ) {
					$products = array();
					foreach( (array)$products_ids as $id ) {
						$id = (int)$id;
						if( $id < 0 ) { continue; }
						$products[] = array(
							'product_set_id' => $product_set_id,
							'product_id'     => $id,
							'quantity'       => 1
						);
					}
					db()->replace_safe( 'shop_product_sets_items', $products );
				}
			}
		}
		return form($replace)
			->text('name')
			->text('price')
			->text('old_price')
			->textarea('description')
			->select_box('cat_id', module('manage_shop')->_cats_for_select)
			->active_box()
			->container(
				table(
					'SELECT psi.product_id as `id`, psi.product_id as `product_id`, p.active as `active`, p.image as `image`, p.name as `name`, psi.quantity as `quantity` FROM ' . db( 'shop_product_sets_items' ) . ' as psi'
					. ' INNER JOIN ' . db( 'shop_products' ) . ' as p ON( p.id = psi.product_id )'
					. ' WHERE psi.product_set_id = '. $product_set_id
					)
					->text( 'product_id' )
					->text( 'active' )
					->text( 'image' )
					->text( 'name' )
					->text( 'quantity' )
					->btn_delete( '', './?object='.main()->_get('object').'&action=product_set_delete&product_id=%d&id='. $product_set_id )
				// , array('wide' => 1)
			)
			->container( _class('manage_shop_filter', 'admin_modules/manage_shop/')->_product_search_widget('products_ids', $replace[ 'products_ids' ], true), 'Добавить продукты')
			->save_and_back()
		;
	}

	function product_set_active () {
		return _class('admin_methods')->active($this->_table);
	}

	function product_set_delete () {
		if( !empty( $_GET[ 'product_id' ] ) ) {
			$product_set_id = (int)$_GET[ 'id' ];
			$product_id = (int)$_GET[ 'product_id' ];
			$table = db( 'shop_product_sets_items' );
			$result = db()->query( "DELETE FROM $table WHERE product_set_id = $product_set_id AND product_id = $product_id" );
			return( $result );
		} else {
			return _class('admin_methods')->delete($this->_table);
		}
	}

}
