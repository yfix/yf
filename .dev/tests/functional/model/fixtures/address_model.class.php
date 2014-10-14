<?php

class address_model extends yf_model {
/*
 * @property integer $address_id
 * @property string $address
 * @property string $address2
 * @property string $district
 * @property integer $city_id
 * @property string $postal_code
 * @property string $phone
 * @property string $last_update
 *
 * @property City $city
 * @property Customer[] $customers
 * @property Staff[] $staffs
 * @property Store[] $stores
*/
/*
	public static function representingColumn() {
		return 'address';
	}

	public function rules() {
		return array(
			array('address, district, city_id, phone, last_update', 'required'),
			array('city_id', 'numerical', 'integerOnly'=>true),
			array('address, address2', 'length', 'max'=>50),
			array('district, phone', 'length', 'max'=>20),
			array('postal_code', 'length', 'max'=>10),
			array('address2, postal_code', 'default', 'setOnEmpty' => true, 'value' => null),
			array('address_id, address, address2, district, city_id, postal_code, phone, last_update', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'city' => array(self::BELONGS_TO, 'City', 'city_id'),
			'customers' => array(self::HAS_MANY, 'Customer', 'address_id'),
			'staffs' => array(self::HAS_MANY, 'Staff', 'address_id'),
			'stores' => array(self::HAS_MANY, 'Store', 'address_id'),
		);
	}
*/
}