<?php

/*
 * @property integer $actor_id
 * @property string $first_name
 * @property string $last_name
 * @property string $last_update
 *
 * @property film[] $films
*/
class actor_model extends yf_model
{
    public static function _name_column()
    {
        return 'first_name';
    }
    public function _rules()
    {
        return [
            'first_name, last_name, last_update' => 'required',
            'first_name, last_name' => 'max_length[45]',
            'actor_id, first_name, last_name, last_update' => 'safe[on=search]',
        ];
    }
    public function _pivot_models()
    {
        return [
            'films' => 'film_actor',
        ];
    }
    public function films()
    {
        return $this->belongs_to_many('film', 'film_actor', 'actor_id', 'film_id');
    }
}
