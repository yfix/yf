<?php

class category_model extends yf_model {
/*
 * @property integer $category_id
 * @property string $name
 * @property string $last_update
 *
 * @property Film[] $films
 */
/*
	public static function representingColumn() {
		return 'name';
	}

	public function rules() {
		return array(
			array('name, last_update', 'required'),
			array('name', 'length', 'max'=>25),
			array('category_id, name, last_update', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'films' => array(self::MANY_MANY, 'Film', 'film_category(category_id, film_id)'),
		);
	}

	public function pivotModels() {
		return array(
			'films' => 'FilmCategory',
		);
	}
*/
}