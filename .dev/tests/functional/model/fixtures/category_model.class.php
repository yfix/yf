<?php

/*
 * @property integer $category_id
 * @property string $name
 * @property string $last_update
 *
 * @property film[] $films
 */
class category_model extends yf_model {
	public static function _name_column() {
		return 'name';
	}
	public function _rules() {
		return [
			'name, last_update'	=> 'required',
			'name'	=> 'max_length[25]',
			'category_id, name, last_update' => 'safe[on=search]',
		];
	}
	public function _pivot_models() {
		return [
			'films' => 'film_category',
		];
	}
	public function films() {
		return $this->belongs_to_many('film', 'film_category', 'category_id', 'film_id');
	}
}
