<?php

class store_model extends yf_model {
/*
 * @property integer $store_id
 * @property integer $manager_staff_id
 * @property integer $address_id
 * @property string $last_update
 *
 * @property Customer[] $customers
 * @property Inventory[] $inventories
 * @property Staff[] $staffs
 * @property Staff $managerStaff
 * @property Address $address
 */
/*
	public static function representingColumn() {
		return 'last_update';
	}

	public function rules() {
		return array(
			array('manager_staff_id, address_id, last_update', 'required'),
			array('manager_staff_id, address_id', 'numerical', 'integerOnly'=>true),
			array('store_id, manager_staff_id, address_id, last_update', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'customers' => array(self::HAS_MANY, 'Customer', 'store_id'),
			'inventories' => array(self::HAS_MANY, 'Inventory', 'store_id'),
			'staffs' => array(self::HAS_MANY, 'Staff', 'store_id'),
			'managerStaff' => array(self::BELONGS_TO, 'Staff', 'manager_staff_id'),
			'address' => array(self::BELONGS_TO, 'Address', 'address_id'),
		);
	}
*/
}