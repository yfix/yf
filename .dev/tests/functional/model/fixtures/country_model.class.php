<?php

/*
 * @property integer $country_id
 * @property string $country
 * @property string $last_update
 *
 * @property city[] $cities
 */
class country_model extends yf_model {
	public static function _name_column() {
		return 'country';
	}
	public function _rules() {
		return array(
			'country, last_update' => 'required',
			'country' => 'max_length[50]',
			'country_id, country, last_update' => 'safe[on=search]',
		);
	}
	public function cities() {
		return $this->has_many('city', 'country_id');
	}
}
