<?php

class yf_form2_auto
{
    /**
     * @param mixed $type
     */
    public function _field_type($type = '')
    {
        $type = trim(strtolower($type));
        if (strpos($type, 'int') !== false) {
            $type = 'int';
        } elseif (strpos($type, 'datetime') !== false) {
            $type = 'datetime';
        } elseif (strpos($type, 'date') !== false) {
            $type = 'date';
        } elseif (strpos($type, 'time') !== false) {
            $type = 'time';
        } elseif (strpos($type, 'float') !== false) {
            $type = 'float';
        } elseif (strpos($type, 'double') !== false) {
            $type = 'double';
        } elseif (strpos($type, 'decimal') !== false) {
            $type = 'decimal';
        } elseif (strpos($type, 'text') !== false) {
            $type = 'text';
        } elseif (strpos($type, 'char') !== false) {
            $type = 'char';
        } elseif (strpos($type, 'blob') !== false) {
            $type = 'blob';
        } elseif (strpos($type, 'enum') !== false) {
            $type = 'enum';
        } elseif (strpos($type, 'set') !== false) {
            $type = 'set';
        } else {
            $type = '';
        }
        return $type;
    }

    /**
     * Enable automatic fields parsing mode.
     * @param mixed $table
     * @param mixed $id
     * @param mixed $params
     * @param mixed $form
     */
    public function auto($table = '', $id = '', $params = [], $form)
    {
        if ($params['links_add']) {
            $form->_params['links_add'] = $params['links_add'];
        }
        if ($table && $id) {
            $db = ($form->_params['db'] ?: $params['db']) ?: db();
            // TODO: use info from db()->utils()->list_columns()
            $columns = $db->meta_columns($table);
            $info = $db->get('SELECT * FROM ' . $db->es($table) . ' WHERE id=' . (int) $id);
            if ( ! is_array($form->_replace)) {
                $form->_replace = [];
            }
            foreach ((array) $info as $k => $v) {
                $form->_replace[$k] = $v;
            }
            foreach ((array) $columns as $name => $a) {
                $_extra = [];
                $values = [];
                //var_dump($a);
                $type = $this->_field_type($a['type']);
                $length = (int) ($a['length']);
                // TODO: detect foreign keys as select box by constraint
                if ($name == 'id') {
                    $func = 'info';
                } elseif ($name == 'login') {
                    $func = 'login';
                } elseif ($name == 'email') {
                    $func = 'email';
                } elseif ($name == 'phone') {
                    $func = 'phone';
                } elseif ($name == 'active') {
                    $func = 'active_box';
                } elseif (in_array($type, ['enum', 'set'])) {
                    if ($a['values'] == [0, 1]) {
                        $func = 'yes_no_box';
                    } else {
                        $func = 'radio_box';
                        $values = $a['values'];
                    }
                } elseif ($type == 'datetime') {
                    $func = 'datetime_select';
                } elseif ($type == 'date') {
                    $func = 'datetime_select';
                } elseif ($type == 'time') {
                    $func = 'datetime_select';
                } elseif ($type == 'int') {
                    if ((strpos($name, 'date') !== false || strpos($name, 'time') !== false) && in_array($length, [10, 11])) {
                        $func = 'datetime_select';
                    } else {
                        $func = 'number';
                    }
                } elseif ($type == 'float') {
                    $func = 'float';
                } elseif ($type == 'double') {
                    $func = 'float';
                } elseif ($type == 'decimal') {
                    $func = 'float';
                } elseif ($type == 'text') {
                    $func = 'textarea';
                } else {
                    $func = 'text';
                }
                if (in_array($type, ['int', 'char'])) {
                    $_extra['maxlength'] = $length;
                    $type == 'int' && $_extra['max'] = pow(8, $length);
                }
                if (in_array($type, ['int', 'float', 'double', 'decimal']) && $a['unsigned']) {
                    $_extra['min'] = 0;
                }
                if ($a['default'] && ! $a['nullable']) {
                    $_extra['required'] = 1;
                }
                if (false !== strpos($func, '_box')) {
                    $_extra['name'] = $name;
                    $form->$func($name, $values, $_extra);
                } else {
                    $form->$func($name, $_extra);
                }
            }
        } elseif ($form->_sql && $form->_replace) {
            foreach ((array) $form->_replace as $name => $v) {
                // TODO: detect numeric like time: 1234567890
                // TODO: detect YYYY-mm-dd as date, YYYY-mm-dd HH:ii:ss as datetime, HH:ii:ss as time
                // TODO: all other detect rules from code above
                $form->container($v, $name);
            }
        }
        $form->save_and_back();
        return $form;
    }
}
