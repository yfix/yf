<?php

class customer_model extends yf_model {
	public function address() {
		return $this->belongs_to('address', 'address_id');
	}
	public function store() {
		return $this->belongs_to('store', 'store_id');
	}
	public function payments() {
		return $this->has_many('payment', 'customer_id');
	}
	public function rentals() {
		return $this->has_many('rental', 'customer_id');
	}
/*
 * @property integer $customer_id
 * @property integer $store_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property integer $address_id
 * @property integer $active
 * @property string $create_date
 * @property string $last_update
 *
 * @property Address $address
 * @property Store $store
 * @property Payment[] $payments
 * @property Rental[] $rentals
 */
/*
	public static function representingColumn() {
		return 'first_name';
	}

	public function rules() {
		return array(
			array('store_id, first_name, last_name, address_id, create_date, last_update', 'required'),
			array('store_id, address_id, active', 'numerical', 'integerOnly'=>true),
			array('first_name, last_name', 'length', 'max'=>45),
			array('email', 'length', 'max'=>50),
			array('email, active', 'default', 'setOnEmpty' => true, 'value' => null),
			array('customer_id, store_id, first_name, last_name, email, address_id, active, create_date, last_update', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'address' => array(self::BELONGS_TO, 'Address', 'address_id'),
			'store' => array(self::BELONGS_TO, 'Store', 'store_id'),
			'payments' => array(self::HAS_MANY, 'Payment', 'customer_id'),
			'rentals' => array(self::HAS_MANY, 'Rental', 'customer_id'),
		);
	}
*/
}