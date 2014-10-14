<?php

class film_text_model extends yf_model {
/*
 * @property integer $film_id
 * @property string $title
 * @property string $description
 *
 */
/*
	public static function representingColumn() {
		return 'title';
	}

	public function rules() {
		return array(
			array('film_id, title', 'required'),
			array('film_id', 'numerical', 'integerOnly'=>true),
			array('title', 'length', 'max'=>255),
			array('description', 'safe'),
			array('description', 'default', 'setOnEmpty' => true, 'value' => null),
			array('film_id, title, description', 'safe', 'on'=>'search'),
		);
	}
*/
}