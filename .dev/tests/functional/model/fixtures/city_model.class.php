<?php

class city_model extends yf_model {
	public function addresses() {
		return $this->has_many('address', 'city_id');
	}
	public function country() {
		return $this->belongs_to('country', 'country_id');
	}
/*
 * @property integer $city_id
 * @property string $city
 * @property integer $country_id
 * @property string $last_update
 *
 * @property Address[] $addresses
 * @property Country $country
 */
/*
	public static function representingColumn() {
		return 'city';
	}

	public function rules() {
		return array(
			array('city, country_id, last_update', 'required'),
			array('country_id', 'numerical', 'integerOnly'=>true),
			array('city', 'length', 'max'=>50),
			array('city_id, city, country_id, last_update', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'addresses' => array(self::HAS_MANY, 'Address', 'city_id'),
			'country' => array(self::BELONGS_TO, 'Country', 'country_id'),
		);
	}
*/
}