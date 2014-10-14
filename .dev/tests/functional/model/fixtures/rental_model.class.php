<?php

class rental_model extends yf_model {
/*
 * @property integer $rental_id
 * @property string $rental_date
 * @property integer $inventory_id
 * @property integer $customer_id
 * @property string $return_date
 * @property integer $staff_id
 * @property string $last_update
 *
 * @property Payment[] $payments
 * @property Staff $staff
 * @property Inventory $inventory
 * @property Customer $customer
 */
/*
	public static function representingColumn() {
		return 'rental_date';
	}

	public function rules() {
		return array(
			array('rental_date, inventory_id, customer_id, staff_id, last_update', 'required'),
			array('inventory_id, customer_id, staff_id', 'numerical', 'integerOnly'=>true),
			array('return_date', 'safe'),
			array('return_date', 'default', 'setOnEmpty' => true, 'value' => null),
			array('rental_id, rental_date, inventory_id, customer_id, return_date, staff_id, last_update', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'payments' => array(self::HAS_MANY, 'Payment', 'rental_id'),
			'staff' => array(self::BELONGS_TO, 'Staff', 'staff_id'),
			'inventory' => array(self::BELONGS_TO, 'Inventory', 'inventory_id'),
			'customer' => array(self::BELONGS_TO, 'Customer', 'customer_id'),
		);
	}
*/
}