<?php

class country_model extends yf_model {
/*
 * @property integer $country_id
 * @property string $country
 * @property string $last_update
 *
 * @property City[] $cities
 */
/*
	public static function representingColumn() {
		return 'country';
	}

	public function rules() {
		return array(
			array('country, last_update', 'required'),
			array('country', 'length', 'max'=>50),
			array('country_id, country, last_update', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'cities' => array(self::HAS_MANY, 'City', 'country_id'),
		);
	}
*/
}