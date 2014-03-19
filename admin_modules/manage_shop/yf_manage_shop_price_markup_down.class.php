<?php

class yf_manage_shop_price_markup_down {

	private $_table = array(
		'table' => 'shop_price_markup_down',
		'fields' => array(
			'value',
			'description',
		),
	);

	private $_uri            = '';

	function _init() {
		$_object = main()->_get( 'object' );
		$_module = 'price_markup_down';
		$_uri_object = "./?object=$_object";
		$_uri_action = "$_uri_object&action=";

		$this->_uri = array(
			'show'   => $_uri_action . 'price_markup_down',
			'add'    => $_uri_action . 'price_markup_down_add',
			'edit'   => $_uri_action . 'price_markup_down_edit'   . '&id=%d',
			'delete' => $_uri_action . 'price_markup_down_delete' . '&id=%d',
			'active' => $_uri_action . 'price_markup_down_active' . '&id=%d',
		);
		$this->_table[ 'back_link' ] = $this->_uri[ 'show' ];
	}

	function price_markup_down() {
		return table('SELECT * FROM '.db('shop_price_markup_down'), array(
				'filter'        => $_SESSION[ $_GET[ 'object' ]. $_GET[ 'action' ] ],
				'filter_params' => array( 'description' => 'like' ),
				// 'hide_empty'    => 1,
			))
			->text( 'value' )
			->text( 'description' )
			// ->btn_delete('', './?object='.main()->_get('object').'&action=unit_delete&id=%d')
			->btn_edit(   '', $this->_uri[ 'edit'   ], array( 'no_ajax' => 1 ) )
			->btn_active( '', $this->_uri[ 'active' ] )
			->footer_add( '', $this->_uri[ 'add'    ] );
		;
	}

	function price_markup_down_active() {
		return _class( 'admin_methods' )->active( $this->_table );
	}

	function price_markup_down_add() {
		$replace = _class( 'admin_methods' )->add( $this->_table );
		return form( $replace )
			->text( 'value' )
			->text( 'description' )
			->active_box('active')
			->save_and_back();
	}

	function price_markup_down_edit() {
		$replace = _class( 'admin_methods' )->edit( $this->_table );
		$form = form( $replace )
			->text( 'value' )
			->text( 'description' )
			->active_box('active')
			->save_and_back();
		return( $form );
	}

	/**
	*/
	function unit_add () {
		if (main()->is_post()) {
			if (!$_POST['title']) {
				_re('Unit title must be filled');
			}
			if (!common()->_error_exists()) {
				$sql_array = array(
					'title'			=> $_POST['title'],
					'description'	=> $_POST['description'],
					'step'			=> intval($_POST['step']),
					'k'				=> floatval($_POST['k']),
				);
				db()->insert(db('shop_product_units'), db()->es($sql_array));
				common()->admin_wall_add(array('shop product unit added: '.$_POST['title'], db()->insert_id()));
			}
			return js_redirect('./?object='.main()->_get('object').'&action=units');
		}

		$replace = array(
			'title'				=> '',
			'description'		=> '',
			'step'				=> '',
			'k'					=> '',
			'form_action'		=> './?object='.main()->_get('object').'&action=unit_add',
			'back_url'			=> './?object='.main()->_get('object').'&action=units',
		);
		return form($replace)
			->text('title')
			->textarea('description','Description')
			->text('step')
			->text('k')
			->save_and_back();
	}

	/**
	*/
	function unit_edit () {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e('Empty ID!');
		}
		$unit_info = db()->query_fetch('SELECT * FROM '.db('shop_product_units').' WHERE id='.$_GET['id']);
		if (main()->is_post()) {
			if (!$_POST['title']) {
				_re('Unit title must be filled');
			}
			if (!common()->_error_exists()) {
				$sql_array = array(
					'title'			=> $_POST['title'],
					'description'	=> $_POST['description'],
					'step'			=> intval($_POST['step']),
					'k'				=> floatval($_POST['k']),
				);
				db()->update('shop_product_units', db()->es($sql_array), 'id='.$_GET['id']);
				common()->admin_wall_add(array('shop product unit updated: '.$_POST['title'], $_GET['id']));
			}
			return js_redirect('./?object='.main()->_get('object').'&action=units');
		}
		$replace = array(
			'title'				=> $unit_info['title'],
			'description'		=> $unit_info['description'],
			'step'				=> $unit_info['step'],
			'k'					=> $unit_info['k'],
			'form_action'		=> './?object='.main()->_get('object').'&action=unit_edit&id='.$unit_info['id'],
			'back_url'			=> './?object='.main()->_get('object').'&action=units',
		);
		return form($replace)
			->text('title')
			->textarea('description','Description')
			->text('step')
			->text('k')
			->save_and_back();
	}

	/**
	*/
	function unit_delete () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$info = db()->query_fetch('SELECT * FROM '.db('shop_product_units').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($info['id'])) {
			db()->query('DELETE FROM '.db('shop_product_units').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			common()->admin_wall_add(array('shop product unit deleted: '.$info['name'], $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.main()->_get('object').'&action=units');
		}
	}
}
