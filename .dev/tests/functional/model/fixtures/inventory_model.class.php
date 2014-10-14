<?php

class inventory_model extends yf_model {
/*
 * @property integer $inventory_id
 * @property integer $film_id
 * @property integer $store_id
 * @property string $last_update
 *
 * @property Store $store
 * @property Film $film
 * @property Rental[] $rentals
 */
/*
	public static function representingColumn() {
		return 'last_update';
	}

	public function rules() {
		return array(
			array('film_id, store_id, last_update', 'required'),
			array('film_id, store_id', 'numerical', 'integerOnly'=>true),
			array('inventory_id, film_id, store_id, last_update', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'store' => array(self::BELONGS_TO, 'Store', 'store_id'),
			'film' => array(self::BELONGS_TO, 'Film', 'film_id'),
			'rentals' => array(self::HAS_MANY, 'Rental', 'inventory_id'),
		);
	}
*/
}