<?php

/*
 * @property integer $city_id
 * @property string $city
 * @property integer $country_id
 * @property string $last_update
 *
 * @property address[] $addresses
 * @property country $country
 */
class city_model extends yf_model {
	public static function _name_column() {
		return 'city';
	}
	public function _rules() {
		return array(
			'city, country_id, last_update' => 'required',
			'country_id' => 'integer',
			'city' => 'max_length[50]',
			'city_id, city, country_id, last_update' => 'safe[on=search]',
		);
	}
	public function addresses() {
		return $this->has_many('address', 'city_id');
	}
	public function country() {
		return $this->belongs_to('country', 'country_id');
	}
}
