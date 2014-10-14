<?php

class film_model extends yf_model {
	protected $_table = 'film';
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
 * @property Language $language
 * @property Language $originalLanguage
 * @property Actor[] $actors
 * @property Category[] $categories
 * @property Inventory[] $inventories
 */
/*
	public static function representingColumn() {
		return 'title';
	}

	public function rules() {
		return array(
			array('title, language_id, last_update', 'required'),
			array('language_id, original_language_id, rental_duration, length', 'numerical', 'integerOnly'=>true),
			array('title', 'length', 'max'=>255),
			array('release_year, rental_rate', 'length', 'max'=>4),
			array('replacement_cost, rating', 'length', 'max'=>5),
			array('description, special_features', 'safe'),
			array('description, release_year, original_language_id, rental_duration, rental_rate, length, replacement_cost, rating, special_features', 'default', 'setOnEmpty' => true, 'value' => null),
			array('film_id, title, description, release_year, language_id, original_language_id, rental_duration, rental_rate, length, replacement_cost, rating, special_features, last_update', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'language' => array(self::BELONGS_TO, 'Language', 'language_id'),
			'originalLanguage' => array(self::BELONGS_TO, 'Language', 'original_language_id'),
			'actors' => array(self::MANY_MANY, 'Actor', 'film_actor(film_id, actor_id)'),
			'categories' => array(self::MANY_MANY, 'Category', 'film_category(film_id, category_id)'),
			'inventories' => array(self::HAS_MANY, 'Inventory', 'film_id'),
		);
	}

	public function pivotModels() {
		return array(
			'actors' => 'FilmActor',
			'categories' => 'FilmCategory',
		);
	}
*/
}