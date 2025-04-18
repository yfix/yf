<?php


class yf_form2_boxes
{
    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function country_box($name = '', $desc = '', $extra = [], $replace = [], $form = null)
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! $name) {
            $name = 'country';
        }
        $data = [];
        $row_tpl = $extra['row_tpl'] ?: '%icon %name %code';
        $countries = $extra['countries'] ?: main()->get_data('geo_countries');
        foreach ((array) $countries as $v) {
            $r = [
                '%icon' => '<i class="bfh-flag-' . strtoupper($v['code']) . '"></i>',
                '%name' => $v['name'],
                '%code' => '[' . strtoupper($v['code']) . ']',
            ];
            $data[$v['code']] = str_replace(array_keys($r), array_values($r), $row_tpl);
        }
        if (MAIN_TYPE_ADMIN && ! isset($extra['edit_link'])) {
            $extra['edit_link'] = './?object=manage_countries';
        }
        $renderer = $extra['renderer'] ?: 'list_box';
        return $form->$renderer($name, $data, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function region_box($name = '', $desc = '', $extra = [], $replace = [], $form = null)
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! $name) {
            $name = 'region';
        }
        $extra['country'] = $extra['country'] ?: 'UA';
        $data = [];
        $row_tpl = $extra['row_tpl'] ?: '%name';
        $regions = $extra['regions'] ?: main()->get_data('geo_regions', 0, ['country' => $extra['country']]);
        foreach ((array) $regions as $v) {
            $r = [
                '%name' => $v['name'],
            ];
            $data[$v['code']] = str_replace(array_keys($r), array_values($r), $row_tpl);
        }
        if (MAIN_TYPE_ADMIN && ! isset($extra['edit_link'])) {
            $extra['edit_link'] = './?object=manage_regions';
        }
        $renderer = $extra['renderer'] ?: 'list_box';
        return $form->$renderer($name, $data, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function city_box($name = '', $desc = '', $extra = [], $replace = [], $form = null)
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! $name) {
            $name = 'city';
        }
        $extra['country'] = $extra['country'] ?: 'UA';
        $data = [];
        $row_tpl = $extra['row_tpl'] ?: '%name';
        foreach ((array) main()->get_data('geo_regions', 0, ['country' => $extra['country']]) as $v) {
            $data[$v['name']] = [];
            $region_names[$v['id']] = $v['name'];
        }
        foreach ((array) main()->get_data('geo_cities', 0, ['country' => $extra['country']]) as $v) {
            $region_name = $region_names[$v['region_id']];
            if ( ! $region_name) {
                continue;
            }
            $r = [
                '%name' => $v['name'],
            ];
            $data[$region_name][$v['id']] = str_replace(array_keys($r), array_values($r), $row_tpl);
        }
        if (MAIN_TYPE_ADMIN && ! isset($extra['edit_link'])) {
            $extra['edit_link'] = './?object=manage_cities';
        }
        $renderer = $extra['renderer'] ?: 'select2_box';
        return $form->$renderer($name, $data, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function currency_box($name = '', $desc = '', $extra = [], $replace = [], $form = null)
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! $name) {
            $name = 'currency';
        }
        $data = [];
        $row_tpl = $extra['row_tpl'] ?: '%sign &nbsp; %name %code';
        foreach ((array) main()->get_data('currencies') as $v) {
            $r = [
                '%sign' => $v['sign'],
                '%name' => $v['name'],
                '%code' => '[' . $v['id'] . ']',
            ];
            $data[$v['id']] = str_replace(array_keys($r), array_values($r), $row_tpl);
        }
        if (MAIN_TYPE_ADMIN && ! isset($extra['edit_link'])) {
            $extra['edit_link'] = './?object=manage_currencies';
        }
        $renderer = $extra['renderer'] ?: 'list_box';
        return $form->$renderer($name, $data, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function locale_box($name = '', $desc = '', $extra = [], $replace = [], $form = null)
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! $name) {
            $name = 'locale';
        }
        $data = [];
        $row_tpl = $extra['row_tpl'] ?: '%icon %name %code';

        asset('bfh-select');
        $extra['style'] = $extra['style'] ?: 'max-width:200px;';

        $lang_def_country = main()->get_data('lang_def_country');
        foreach ((array) db()->from('sys_locale_langs')->get_all() as $v) {
            if ($extra['only_active'] && ! $v['active']) {
                continue;
            }
            $lang = strtolower($v['locale']);
            $country = strtoupper($lang_def_country[$lang]);
            $r = [
                '%icon' => ($country ? '<i class="bfh-flag-' . $country . '"></i> ' : ''),
                '%name' => $v['name'],
                '%code' => '[' . $lang . ']',
            ];
            $data[$lang] = str_replace(array_keys($r), array_values($r), $row_tpl);
        }
        if (MAIN_TYPE_ADMIN && ! isset($extra['edit_link'])) {
            $extra['edit_link'] = url('/locale_editor');
        }
        $renderer = $extra['renderer'] ?: 'list_box';
        return $form->$renderer($name, $data, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function language_box($name = '', $desc = '', $extra = [], $replace = [], $form = null)
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! $name) {
            $name = 'language';
        }
        $data = [];
        $row_tpl = $extra['row_tpl'] ?: '%icon %name %code';
        foreach ((array) main()->get_data('languages_new') as $v) {
            $r = [
                '%icon' => ($v['country'] ? '<i class="bfh-flag-' . strtoupper($v['country']) . '"></i> ' : ''),
                '%name' => $v['native'],
                '%code' => '[' . $v['code'] . ']',
            ];
            $data[$v['code']] = str_replace(array_keys($r), array_values($r), $row_tpl);
        }
        if (MAIN_TYPE_ADMIN && ! isset($extra['edit_link'])) {
            $extra['edit_link'] = './?object=manage_languages';
        }
        $renderer = $extra['renderer'] ?: 'list_box';
        return $form->$renderer($name, $data, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function timezone_box($name = '', $desc = '', $extra = [], $replace = [], $form = null)
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! $name) {
            $name = 'timezone';
        }
        $data = [];
        $row_tpl = $extra['row_tpl'] ?: '<small>%offset %name</small>';
        foreach ((array) main()->get_data('timezones') as $v) {
            $r = [
                '%offset' => $v['offset'],
                '%name' => $v['name'],
            ];
            $data[$v['name']] = str_replace(array_keys($r), array_values($r), $row_tpl);
        }
        if (MAIN_TYPE_ADMIN && ! isset($extra['edit_link'])) {
            $extra['edit_link'] = './?object=manage_timezones';
        }
        $renderer = $extra['renderer'] ?: 'list_box';
        return $form->$renderer($name, $data, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function icon_select_box($name = '', $desc = '', $extra = [], $replace = [], $form = null)
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! $name) {
            $name = 'icon';
        }
        $data = [];
        $row_tpl = $extra['row_tpl'] ?: '%icon %name';
        foreach ((array) main()->get_data('fontawesome_icons') as $icon) {
            $r = [
                '%icon' => '<i class="icon fa ' . $icon . '"></i> ',
                '%name' => $icon,
            ];
            $data[$icon] = str_replace(array_keys($r), array_values($r), $row_tpl);
        }
        if (MAIN_TYPE_ADMIN && ! isset($extra['edit_link'])) {
            $extra['edit_link'] = './?object=manage_icons';
        }
        $renderer = $extra['renderer'] ?: 'list_box';
        return $form->$renderer($name, $data, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function method_select_box($name = '', $desc = '', $extra = [], $replace = [], $form = null)
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! $name) {
            $name = 'method';
        }
        $data = [];
        if ($extra['for_type'] == 'admin') {
            $data = _class('admin_modules', 'admin_modules/')->_get_methods_for_select();
        } else {
            $data = _class('user_modules', 'admin_modules/')->_get_methods_for_select();
        }
        if (MAIN_TYPE_ADMIN && ! isset($extra['edit_link'])) {
            $extra['edit_link'] = $extra['for_type'] == 'admin' ? './?object=admin_modules' : './?object=user_modules';
        }
        $renderer = $extra['renderer'] ?: 'list_box';
        return $form->$renderer($name, $data, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function template_select_box($name = '', $desc = '', $extra = [], $replace = [], $form = null)
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! $name) {
            $name = 'template';
        }
        $data = [];
        if ($extra['for_type'] == 'admin') {
            $data = _class('template_editor', 'admin_modules/')->_get_stpls_for_type('admin');
        } else {
            $data = _class('template_editor', 'admin_modules/')->_get_stpls_for_type('user');
        }
        if (MAIN_TYPE_ADMIN && ! isset($extra['edit_link'])) {
            $extra['edit_link'] = $extra['for_type'] == 'admin' ? './?object=template_editor' : './?object=template_editor';
        }
        $renderer = $extra['renderer'] ?: 'list_box';
        return $form->$renderer($name, $data, $extra, $replace);
    }

    /**
     * @param mixed $name
     * @param mixed $desc
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function location_select_box($name = '', $desc = '', $extra = [], $replace = [], $form = null)
    {
        if (is_array($name)) {
            $extra = (array) $extra + $name;
            $name = '';
        }
        if (is_array($desc)) {
            $extra = (array) $extra + $desc;
            $desc = '';
        }
        if ( ! $name) {
            $name = 'location';
        }
        // TODO
        return $form->text($name, $data ?? [], $extra, $replace);
    }
}
