<?php

/*
 * @property integer $film_id
 * @property string $title
 * @property string $description
 * @property string $release_year
 * @property integer $language_id
 * @property integer $original_language_id
 * @property integer $rental_duration
 * @property string $rental_rate
 * @property integer $length
 * @property string $replacement_cost
 * @property string $rating
 * @property string $special_features
 * @property string $last_update
 *
 * @property language $language
 * @property language $original_language
 * @property actor[] $actors
 * @property category[] $categories
 * @property inventory[] $inventories
 */
class film_model extends yf_model {
	public static function _name_column() {
		return 'title';
	}
	public function _rules() {
		return [
			'title, language_id, last_update' => 'required',
			'language_id, original_language_id, rental_duration, length' => 'integer',
			'title' => 'max_length[255]',
			'release_year, rental_rate' => 'max_length[4]',
			'replacement_cost, rating' => 'max_length[5]',
			'description, special_features' => 'safe',
			'description, release_year, original_language_id, rental_duration, rental_rate, length, replacement_cost, rating, special_features' => 'default[NULL]',
			'film_id, title, description, release_year, language_id, original_language_id, rental_duration, rental_rate, length, replacement_cost, rating, special_features, last_update' => 'safe[on=search]',
		];
	}
	public function _pivot_models() {
		return [
			'actors' => 'film_actor',
			'categories' => 'film_category',
		];
	}
	public function language() {
		return $this->belongs_to('language', 'language_id');
	}
	public function original_language() {
		return $this->belongs_to('language', 'original_language_id');
	}
	public function actors() {
		return $this->belongs_to_many('actor', 'film_actor', 'film_id', 'actor_id');
	}
	public function categories() {
		return $this->belongs_to_many('category', 'film_category', 'film_id', 'category_id');
	}
	public function inventories() {
		return $this->belongs_to_many('inventory', 'film_id');
	}
}
