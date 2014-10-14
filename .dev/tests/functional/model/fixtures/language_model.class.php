<?php

class language_model extends yf_model {
/*
 * @property integer $language_id
 * @property string $name
 * @property string $last_update
 *
 * @property Film[] $films
 * @property Film[] $films1
 */
/*
	public static function representingColumn() {
		return 'name';
	}

	public function rules() {
		return array(
			array('name, last_update', 'required'),
			array('name', 'length', 'max'=>20),
			array('language_id, name, last_update', 'safe', 'on'=>'search'),
		);
	}

	public function relations() {
		return array(
			'films' => array(self::HAS_MANY, 'Film', 'language_id'),
			'films1' => array(self::HAS_MANY, 'Film', 'original_language_id'),
		);
	}
*/
}