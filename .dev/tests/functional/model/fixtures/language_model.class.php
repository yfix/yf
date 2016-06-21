<?php

/*
 * @property integer $language_id
 * @property string $name
 * @property string $last_update
 *
 * @property film[] $films
 * @property film[] $films1
 */
class language_model extends yf_model {
	public static function _name_column() {
		return 'name';
	}
	public function _rules() {
		return [
			'name, last_update' => 'required',
			'name' => 'max_length[20]',
			'language_id, name, last_update' => 'safe[on=search]',
		];
	}
	public function films() {
		return $this->has_many('film', 'language_id');
	}
	public function films_by_original_language() {
		return $this->has_many('film', 'original_language_id');
	}
}
