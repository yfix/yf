<?php

/*
 * @property integer $inventory_id
 * @property integer $film_id
 * @property integer $store_id
 * @property string $last_update
 *
 * @property store $store
 * @property film $film
 * @property rental[] $rentals
 */
class inventory_model extends yf_model {
	public static function _name_column() {
		return 'last_update';
	}
	public function _rules() {
		return [
			'film_id, store_id, last_update' => 'required',
			'film_id, store_id' => 'integer',
			'inventory_id, film_id, store_id, last_update' => 'safe[on=search]',
		];
	}
	public function store() {
		return $this->belongs_to('store', 'store_id');
	}
	public function film() {
		return $this->belongs_to('film', 'film_id');
	}
	public function rentals() {
		return $this->has_many('rental', 'inventory_id');
	}
}
