<?php

class yf_manage_shop_attributes{

	/**
	*/
	function attributes () {
		$R = db()->query('SELECT * FROM `'.db('shop_productparams').'` ORDER BY `title` ASC');
		$items = array();
		while ($A = db()->fetch_assoc($R)) {
			$A1 = db()->get_2d('SELECT `id`,`title` FROM `'.db('shop_productparams_options').'` WHERE `productparams_id`='.$A['id'].' ORDER BY `title`');
			$items[$A['id']] = $A;
			$items[$A['id']]['options'] = trim(implode(' | ',$A1));
		}
// TODO: rewrite this into pure table() with custom func to support filter
		return table($items, array(
				'filter' => $_SESSION[$_GET['object'].'__attributes']
			))
			->text('title')
			->text('options')				
			->btn_edit('', './?object='.main()->_get('object').'&action=attribute_edit&id=%d')
			->btn_delete('', './?object='.main()->_get('object').'&action=attribute_delete&id=%d')
			->footer_add('','./?object='.main()->_get('object').'&action=attribute_add')
		;
	}	

	/**
	*/
	function attribute_add () {
		
		if (main()->is_post()) {			
			if (empty($_POST['title'])) {
				_re('Title is required');
			}
			if (empty($_POST['value_list'])) {
				_re('Values list is required');
			}			
			if (!common()->_error_exists()) {
				$value_list = array();
				foreach ((array)explode("\n", $_POST['value_list']) as $val){
					$v = trim($val);
					if ($v!='') {
						$value_list[$v] = $v;
					}
				}
				db()->INSERT('shop_productparams', db()->es(array(
					'title'			=> $_POST['title'],
				)));

				$insert_id = db()->insert_id();
				foreach ($value_list as $val) {
					// insert new
					db()->INSERT('shop_productparams_options', array(
						'productparams_id' => $insert_id,
						'title' => $val,
					));
				}

				common()->admin_wall_add(array('shop product attribute added: '.$_POST['title'], $insert_id));
				return js_redirect('./?object='.main()->_get('object').'&action=attributes');
			}
		}
		$form_fields = array('name','type','value_list','default_value','order', 'comment');
		$replace = array_fill_keys($form_fields, '');
		$replace = my_array_merge($replace, array(
			'form_action'	=> './?object='.main()->_get('object').'&action='.$_GET['action'].'&id='.$_GET['id'],
			'error'			=> _e(),
			'back_url'		=> './?object='.main()->_get('object').'&action=attributes',
			'active'		=> 1,
		));
		return form($replace)
			->text('title')
			->textarea('value_list')
			->save_and_back();
	}

	/**
	*/
	function attribute_edit () {
		if (empty($_GET['id'])) {
			return _e('no id');
		}
		$_GET['id'] = intval($_GET['id']);
		$A = db()->query_fetch('SELECT * FROM `'.db('shop_productparams').'` WHERE `id`='.$_GET['id']);
		$options = db()->get_2d('SELECT `id`,`title` FROM '.db('shop_productparams_options').' WHERE `productparams_id`='.$_GET['id'].' ORDER BY `title`');
		$A['value_list'] = implode("\n",$options);
		if (main()->is_post()) {
			if (empty($_POST['title'])) {
				_re('Title is required');
			}
			if (empty($_POST['value_list'])) {
				_re('Values list is required');
			}
			if (!common()->_error_exists()) {
				$value_list = array();
				foreach ((array)explode("\n", $_POST['value_list']) as $val) {
					$v = trim($val);
					if ($v!='') {
						$value_list[$v] = $v;
					}
				}
				$options_list = array_flip($options);
				foreach ($value_list as $val) {
					if (!empty($options_list[$val])) {
						// same option - leave as is
						unset($options_list[$val]);
					} else {
						// insert new
						db()->INSERT('shop_productparams_options', array(
							'productparams_id' => $_GET['id'],
							'title' => $val,
						));
					}
				}
				if (count($options_list != 0)) {
					// options not found - delete these
					db()->query('DELETE FROM `'.db('shop_productparams_options').'` WHERE `id` IN ('.implode(',',$options_list).') AND `productparams_id`='.$_GET['id']);
				}
				
				db()->UPDATE('shop_productparams', db()->es(array(
					'title'			=> $_POST['title'],
				)), 'id='.$_GET['id']); 
				common()->admin_wall_add(array('shop product attribute updated: '.$_POST['title'], $_GET['id']));
				return js_redirect('./?object='.main()->_get('object').'&action=attributes'); 
			}
		}
		$replace = array(
			'form_action'	=> './?object='.main()->_get('object').'&action='.$_GET['action'].'&id='.$A['id'],
			'error'			=> _e(),
			'title'			=> $A['title'],
			'value_list'	=> $A['value_list'],
			'back_url'		=> './?object='.main()->_get('object').'&action=attributes',
			'active'		=> 1,
		);
		return form($replace)
			->text('title')
			->textarea('value_list')
			->save_and_back();
	}

	/**
	*/
	function attribute_delete () {
		$_GET['id'] = intval($_GET['id']);
		$field_info = db()->query_fetch('SELECT * FROM '.db('shop_productparams').' WHERE id = '.intval($_GET['id']));
		if (empty($field_info)) {
			return _e('no field');
		}
		if ($_GET['id']) {
			db()->query('DELETE FROM '.db('shop_productparams').' WHERE id='.$_GET['id']);
			db()->query('DELETE FROM '.db('shop_productparams_options').' WHERE productparams_id = '.$_GET['id']);
			common()->admin_wall_add(array('shop product attribute deleted: '.$_GET['id'], $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect($_SERVER['HTTP_REFERER'], 0);
		}
	}

	/**
	*/
	function _get_attributes ($category_id = 0) {
		if (empty($category_id)) {
			return array();
		}
		$fields_info = main()->get_data('shop_product_attributes_info');
		foreach ((array)$fields_info[$category_id] as $A){
			$A = (array)$A;
			if (!$A) {
				continue;
			}
			$attributes[$A['id']] = array(
				'title'			=> $A['name'],
				'type'			=> $A['type'],
				'value_list'	=> explode("\n", $A['value_list']),
				'default_value'	=> $A['default_value'],
			);
		}
		return $attributes;
	}

	/**
	*/
	function _get_attributes_values ($category_id = 0, $object_id = 0, $fields_ids = 0) {
		if (empty($category_id) || empty($object_id) || empty($fields_ids)) {
			return array();
		}
		$Q = db()->query(
			'SELECT field_id,value,add_value 
			FROM '.db('shop_product_attributes_values').'
			WHERE category_id = '.intval($category_id).' 
				AND object_id = '.intval($object_id).' 
				AND field_id IN('.implode(',', $fields_ids).')');
		while ($A = db()->fetch_assoc($Q)) {
			$fields_values[$A['field_id']] = array(
				'is_selected'	=> explode("\n", $A['value']),
				'value_price'	=> explode("\n", $A['add_value']),
			);
		}
		return $fields_values;
	}

	/**
	*/
	function _get_products_attributes($products_ids = array()) {
		if (is_numeric($products_ids)) {
			$return_single_id = $products_ids;
			$products_ids = array($products_ids);
		}
		if (empty($products_ids)) {
			return array();
		}
		$fields_info = main()->get_data('shop_product_attributes_info');

		$Q = db()->query('SELECT * FROM '.db('shop_product_attributes_values').' WHERE object_id IN ('.implode(',', $products_ids).')');
		while ($A = db()->fetch_assoc($Q)) {
			$_product_id = $A['object_id'];

			$A['value']		= explode("\n", $A['value']);
			$A['add_value'] = explode("\n", $A['add_value']);

			foreach ((array)$A['value'] as $_attr_id => $_dummy) {
				$_price = $A['add_value'][$_attr_id];
				$_item_id = $A['field_id'].'_'.$_attr_id;
				$_field_info = $fields_info[$A['field_id']];
				$_field_info['value_list'] = explode("\n", $_field_info['value_list']);

				$data[$_product_id][$_item_id] = array(
					'id' 			=> $_item_id,
					'price'			=> $_price,
					'name'			=> $_field_info['name'],
					'value'			=> $_field_info['value_list'][$_attr_id],
					'product_id'	=> $_product_id,
				);
			}
		}
		if ($return_single_id) {
			return $data[$return_single_id];
		}
		return $data;
	}

	/**
	*/
	function _attributes_view ($object_id = 0) {
		return module('manage_shop')->_attributes_html($object_id, true);
	}

	/**
	*/
	function _attributes_html ($object_id = 0, $only_selected = false) {
		$object_id		= $params['object_id'];
		$only_selected	= $params['only_selected'];

		$category_info = main()->get_data('dynamic_fields_categories');
		$category_id = intval(module('manage_shop')->ATTRIBUTES_CAT_ID);

		if (empty($category_id)) {
			return;
		}
		$attributes = module('manage_shop')->_get_attributes($category_id);
		if (empty($attributes) || !is_array($attributes)) {
			return;
		}
		foreach ((array)$attributes as $key => $val){
			$fields_ids[$key] = $key;
		}
		$fields_values = module('manage_shop')->_get_attributes_values ($category_id, $object_id, $fields_ids);
		foreach ((array)$attributes as $_attr_id => $_info) {
			$i++;
			foreach ((array)$_info['value_list'] as $_val_id => $_value) {
				$_item_id = $_attr_id.'_'.$_val_id;
				$selected_info = $fields_values[$_attr_id];
				if ($only_selected && !$selected_info['is_selected'][$_val_id]) {
					continue;
				}
				$data[$_item_id] = array(
					'bg_class'		=> !($i % 2) ? 'bg1' : 'bg2',
					'id'			=> $_item_id,
					'attr_checked'	=> intval((bool)$selected_info['is_selected'][$_val_id]),
					'attr_price'	=> _prepare_html($selected_info['value_price'][$_val_id]),
					'attr_name'		=> _prepare_html($_info['title']),
					'attr_value'	=> _prepare_html($_value),
				);
			}
		}
		return $data;
	}

	/**
	*/
	function _attributes_save ($object_id = 0) {
		if (empty($object_id)) {
			return;
		}
		// 2-nd case of dynamic attributes assignment
		foreach ((array)$_POST['single_attr'] as $_attr_id => $_sel_id) {
			$_item_id = $_attr_id.'_'.$_sel_id;
			$_POST['attributes_use'][$_item_id] = 1;
		}
		$category_info = main()->get_data('dynamic_fields_categories');
		$category_id = intval(module('manage_shop')->ATTRIBUTES_CAT_ID);
		if (empty($category_id)) {
			return;
		}
		$attributes = module('manage_shop')->_get_attributes($category_id);
		if (empty($attributes) || !is_array($attributes)) {
			return;
		}
		foreach ((array)$attributes as $key => $val){
			$fields_ids[$key] = $key;
		}
		$fields_values = module('manage_shop')->_get_attributes_values ($category_id, $object_id, $fields_ids);
		foreach ((array)$attributes as $_attr_id => $_info) {
			$option_values	= array();
			$value_prices	= array();

			foreach ((array)$_info['value_list'] as $_val_id => $_value) {
				$_item_id = $_attr_id.'_'.$_val_id;
				if ($_POST['attributes_use'][$_item_id]) {
					$option_values[$_val_id]	= $_POST['attributes_use'][$_item_id];
				}
				$value_prices[$_val_id]		= $_POST['attributes_price'][$_item_id];
			}
			$option_values	= serialize($option_values);
			$value_prices	= serialize($value_prices);
			if (!isset($fields_values[$_attr_id])) {
				db()->INSERT('shop_product_attributes_values', array(
					'category_id'	=> $category_id,
					'object_id'		=> $object_id,
					'field_id'		=> intval($_attr_id),
					'value'			=> _es($option_values),
					'add_value'		=> _es($value_prices),
				));
			} else {
				db()->UPDATE('shop_product_attributes_values', array(
					'value'			=> _es($option_values),
					'add_value'		=> _es($value_prices),
				), 'category_id = '.$category_id.' AND object_id = '.$object_id.' AND field_id = '.intval($_attr_id));
			}
		}
		return true;
	}

}