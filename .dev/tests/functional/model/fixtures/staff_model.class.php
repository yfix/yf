<?php

class staff_model extends yf_model {
/*
 * @property integer $staff_id
 * @property string $first_name
 * @property string $last_name
 * @property integer $address_id
 * @property string $picture
 * @property string $email
 * @property integer $store_id
 * @property integer $active
 * @property string $username
 * @property string $password
 * @property string $last_update
 *
 * @property Payment[] $payments
 * @property Rental[] $rentals
 * @property Store $store
 * @property Address $address
 * @property Store[] $stores
 */
/*
	public static function representingColumn() {
		return 'first_name';
	}

	public function rules() {
		return array(
			array('first_name, last_name, address_id, store_id, username, last_update', 'required'),
			array('address_id, store_id, active', 'numerical', 'integerOnly'=>true),
			array('first_name, last_name', 'length', 'max'=>45),
			array('email', 'length', 'max'=>50),
			array('username', 'length', 'max'=>16),
			array('password', 'length', 'max'=>40),
			array('picture', 'safe'),
			array('picture, email, active, password', 'default', 'setOnEmpty' => true, 'value' => null),
			array('staff_id, first_name, last_name, address_id, picture, email, store_id, active, username, password, last_update', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'payments' => array(self::HAS_MANY, 'Payment', 'staff_id'),
			'rentals' => array(self::HAS_MANY, 'Rental', 'staff_id'),
			'store' => array(self::BELONGS_TO, 'Store', 'store_id'),
			'address' => array(self::BELONGS_TO, 'Address', 'address_id'),
			'stores' => array(self::HAS_MANY, 'Store', 'manager_staff_id'),
		);
	}
*/
}