<?php

class yf_common_static_conf {

	public $type_list = array(
		'order_status' => array(
			'0' => 'in process',
			'1' => 'confirmed',
			'2' => 'paid',
			'3' => 'delivering',
			'4' => 'completed',
			'5' => 'rejected',
		),
		'order_items_status' => array(
			'0' => 'in process',
			'1' => 'ready',
			'2' => 'cancelled',
		),
		'payment_methods' => array(
			'0' => 'cash',
			'1' => 'credit_card',
			'2' => 'onlymoney',
		),
		'product_revisions' => array(
			'edit'		=> '_edit',
			'checkout'	=> '_checkout',
			'first'		=> '_first',
			'correct_name'=> '_correct_name',
		),
		'order_revisions' => array(
			'edit'		=> '_edit',
			'checkout'	=> '_checkout',
			'merge'		=> '_merge',
			'first'		=> '_first',
		),
		'images_revisions' => array(
			'deleted'	=> '_deleted',
			'checkout'	=> '_checkout',
			'updated'	=> '_updated',
			'import'	=> '_import',
			'first'		=> '_first',
			'change_main'=>'_change_main',
		),
	);

	/*
	 * Returns all types with empty param 'type'
	 * Works in both ways: 
	 * - get status name by id
	 * - get status id by name
	 *
	 * */
	function get_static_conf($type = false, $value = false, $translate = true){

		//tree signs needs for status with id equal 0 
		if($type === false){
			return $this->type_list;
		}

		if(isset($this->type_list[$type])){
			$selected_types = $this->type_list[$type];
			if($value === false){
				if ($translate === false) {
					return $selected_types;
				}
				$translated_selected_types = array();
				foreach ($selected_types as $key => $value) {
					$translated_selected_types[$key] = t($value);
				}
				return $translated_selected_types;
			}

			$selected_types = array_merge($this->type_list[$type], array_flip($this->type_list[$type]));
			if(isset($selected_types[$value])){
				return $translate === true ? t($selected_types[$value]) : $selected_types[$value] ;
			}
		}

		return false;
	}

}