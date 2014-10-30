<?php

/*
 * @property integer $store_id
 * @property integer $manager_staff_id
 * @property integer $address_id
 * @property string $last_update
 *
 * @property customer[] $customers
 * @property inventory[] $inventories
 * @property staff[] $staffs
 * @property staff $managerStaff
 * @property address $address
 */
class store_model extends yf_model {
	public static function _name_olumn() {
		return 'last_update';
	}
	public function _rules() {
		return array(
			'manager_staff_id, address_id, last_update' => 'required',
			'manager_staff_id, address_id' => 'integer',
			'store_id, manager_staff_id, address_id, last_update' => 'safe[on=search]',
		);
	}
	public function customers() {
		return $this->has_many('customer', 'store_id');
	}
	public function inventories() {
		return $this->has_many('inventory', 'store_id');
	}
	public function staffs() {
		return $this->has_many('staff', 'store_id');
	}
	public function manager_staff() {
		return $this->belongs_to('staff', 'manager_staff_id');
	}
	public function address() {
		return $this->belongs_to('address', 'address_id');
	}
}
