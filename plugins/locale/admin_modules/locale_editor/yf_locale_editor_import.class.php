<?php


class yf_locale_editor_import
{
    public function _init()
    {
        $this->_parent = module('locale_editor');
    }


    public function import()
    {
        $a['back_link'] = url('/@object/vars');
        $a['redirect_link'] = $a['back_link'];
        ! $a['lang'] && $a['lang'] = 'en';
        ! isset($a['keep_existing']) && $a['keep_existing'] = 1;
        // To ensure that currently active langs are in top of the list
        $langs = [];
        foreach ((array) $this->_parent->_cur_langs as $lang => $name) {
            $langs[$lang] = $name;
        }
        $langs[''] = '-------------';
        foreach ((array) $this->_parent->_langs as $lang => $name) {
            $langs[$lang] = $name;
        }
        return $this->_parent->_header_links() . '<div class="col-md-12"><br>' .
            form($a + (array) $_POST)
            ->validate([
                'lang' => 'required',
                'format' => 'required',
            ])
            ->on_validate_ok(function ($data, $e, $vr, $form) {
                return $this->_on_validate_ok($data, $form);
            })
            ->select_box('lang', $langs)
            ->select_box('format', $this->_parent->_import_export_file_formats)
            ->file('file')
            ->yes_no_box('keep_existing')
            ->save_and_back('', ['desc' => 'Import'])
        . '</div>';
    }

    /**
     * @param mixed $params
     * @param mixed $form
     */
    public function _on_validate_ok($params = [], $form)
    {
        $p = $params ?: $_POST;
        $f = $_FILES['file'];

        $lang = $p['lang'];
        $format = $p['format'];
        $keep_existing = $p['keep_existing'];

        $raw_langs = $this->_parent->_get_iso639_list();
        if ( ! isset($this->_parent->_cur_langs[$lang])) {
            db()->insert_safe('locale_langs', [
                'locale' => $lang,
                'name' => $raw_langs[$lang][0],
                'charset' => 'utf-8',
                'active' => 1,
                'is_default' => 0,
            ]);
            cache_del('locale_langs');
        }
        if ( ! $f || ! isset($f['name']) || ! $f['tmp_name']) {
            common()->message_error('Import failed, file not uploaded at all');
            return false;
        }
        $file_name = date('YmdHis') . '.' . $format . '.import';
        $target_path = APP_PATH . 'storage/tmp/' . $file_name;
        $target_dir = dirname($target_path);
        ! file_exists($target_dir) && mkdir($target_dir, 0755, true);
        move_uploaded_file($f['tmp_name'], $target_path);
        if ( ! file_exists($target_path) || ! filesize($target_path)) {
            common()->message_error('Import failed, uploaded file not moved into tmp location');
            return false;
        }
        $raw = file_get_contents($target_path);
        $data = [];
        $format == 'csv' && $data = $this->_parse_csv($raw);
        $format == 'json' && $data = $this->_parse_json($raw);
        $format == 'yaml' && $data = $this->_parse_yaml($raw);
        $format == 'php' && $data = $this->_parse_php($raw);
        if ( ! $data) {
            common()->message_error('Import failed, data is empty, maybe parsing failed or format not recognized');
            return false;
        }
        if ($this->_parent->VARS_IGNORE_CASE) {
            $tmp = [];
            foreach ((array) $data as $source => $tr) {
                $tmp[_strtolower($source)] = $tr;
            }
            $data = $tmp;
            unset($tmp);
        }

        $vars = $this->_parent->_get_all_vars();

        $new_vars = [];
        $to_update = [];

        foreach ((array) $data as $source => $tr) {
            if ( ! strlen($source) || ! strlen($tr)) {
                continue;
            }
            if ( ! isset($vars[$source])) {
                $new_vars[$source] = $tr;
            }
        }
        foreach ((array) $vars as $source => $v) {
            if ( ! isset($data[$source])) {
                continue;
            }
            if ($data[$source] == $v['translation'][$lang]) {
                continue;
            }
            $to_update[$source] = $data[$source];
        }
        $stats = [];
        if ($new_vars) {
            $ids = [];
            foreach ((array) $new_vars as $source => $tr) {
                db()->insert_safe('locale_vars', ['value' => $source]);
                $ids[$source] = (int) db()->insert_id();
            }
            foreach ((array) $new_vars as $source => $tr) {
                $var_id = (int) $ids[$source];
                if ( ! $var_id) {
                    $failed[$source] = $tr;
                    $stats['failed']++;
                    continue;
                }
                // TODO: replace with insert/update to not change var ids
                db()->replace_safe('locale_translate', [
                    'var_id' => (int) $var_id,
                    'locale' => $lang,
                    'value' => $tr,
                ]);
                $stats['inserted']++;
            }
        }
        if ( ! $keep_existing) {
            $ids = [];
            $to_find = [];
            foreach ((array) $to_update as $source => $tr) {
                $var_id = $vars[$source]['var_id'];
                if ($var_id) {
                    $ids[$source] = (int) $var_id;
                } else {
                    $to_find[$source] = md5($source);
                }
            }
            if ($to_find) {
                $md5_to_find = array_flip($to_find);
                foreach ((array) from('locale_vars')->where_raw('MD5(value) IN("' . implode('","', $to_find) . '")')->get_2d('value,id') as $source => $var_id) {
                    $ids[$source] = (int) $var_id;
                }
                foreach ((array) $to_find as $source => $md5) {
                    if ($ids[$source]) {
                        unset($to_find[$source]);
                    }
                }
                foreach ((array) $to_find as $source => $md5) {
                    db()->insert_safe('locale_vars', ['value' => $source]);
                    $ids[$source] = (int) db()->insert_id();
                }
            }
            foreach ((array) $to_update as $source => $tr) {
                $var_id = (int) $ids[$source];
                if ( ! $var_id) {
                    $failed[$source] = $tr;
                    $stats['failed']++;
                    continue;
                }
                db()->replace_safe('locale_translate', [
                    'var_id' => (int) $var_id,
                    'locale' => $lang,
                    'value' => $tr,
                ]);
                $stats['updated']++;
            }
        }
        $stats['failed'] && common()->message_error($stats['failed'] . ' variable(s) failed to import');
        $stats['updated'] && common()->message_success($stats['updated'] . ' existing variable(s) successfully updated');
        $stats['inserted'] && common()->message_success($stats['inserted'] . ' new variable(s) successfully inserted');
        ! $stats && common()->message_info('Import done, nothing changed');

        cache_del('locale_translate_' . $lang);
        return js_redirect('/@object/vars');
    }

    /**
     * @param mixed $raw
     * @param mixed $delim
     * @param mixed $enc
     */
    public function _parse_csv($raw = '', $delim = "\t", $enc = '"')
    {
        $raw = trim($raw);
        if ( ! strlen($raw)) {
            return false;
        }
        $a = array_map(function ($in) use ($delim, $enc) {
            return str_getcsv($in, $delim, $enc) ?: [];
        }, explode(PHP_EOL, $raw));
        foreach ($a as $k => $v) {
            if ( ! $v || ! $v[0]) {
                unset($a[$k]);
            }
        }
        $header = array_shift($a);
        array_walk($a, function (&$row, $key, $header) {
            $row = array_combine($header, $row);
        }, $header);

        $out = [];
        foreach ($a as $v) {
            $out[$v['source']] = $v['translation'];
        }
        return $out;
    }

    /**
     * @param mixed $raw
     */
    public function _parse_json($raw = '')
    {
        $raw = trim($raw);
        if ( ! strlen($raw)) {
            return false;
        }
        return json_decode($raw, $array = true);
    }

    /**
     * @param mixed $raw
     */
    public function _parse_yaml($raw = '')
    {
        $raw = trim($raw);
        if ( ! strlen($raw)) {
            return false;
        }
        return yaml_parse($raw);
    }

    /**
     * @param mixed $raw
     */
    public function _parse_php($raw = '')
    {
        $raw = trim($raw);
        $prefix = '<?' . 'php' . PHP_EOL . 'return [';
        if ( ! strlen($raw) || strpos($raw, $prefix) !== 0) {
            return false;
        }
        return eval('?>' . $prefix . substr($raw, strlen($prefix)) . ';');
    }
}
