<?php

class actor_model extends yf_model {
/*
 * @property integer $actor_id
 * @property string $first_name
 * @property string $last_name
 * @property string $last_update
 *
 * @property Film[] $films
*/
/*
	public static function representingColumn() {
		return 'first_name';
	}

	public function rules() {
		return array(
			array('first_name, last_name, last_update', 'required'),
			array('first_name, last_name', 'length', 'max'=>45),
			array('actor_id, first_name, last_name, last_update', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'films' => array(self::MANY_MANY, 'Film', 'film_actor(actor_id, film_id)'),
		);
	}

	public function pivotModels() {
		return array(
			'films' => 'FilmActor',
		);
	}
*/
}