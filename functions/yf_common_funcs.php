<?php

require_once __DIR__ . '/yf_compat_funcs.php';
require_once __DIR__ . '/yf_aliases.php';
require_once __DIR__ . '/yf_is_funcs.php';
require_once __DIR__ . '/yf_array_funcs.php';

if ( ! function_exists('show_text')) {
    function show_text($text = '')
    {
        return _class('utils')->show_text($text);
    }
}
if ( ! function_exists('error_back')) {
    function error_back($where_go_back = '', $what_to_say = 'error')
    {
        return _class('utils')->error_back($where_go_back, $what_to_say);
    }
}
if ( ! function_exists('back')) {
    function back($where_go_back = '', $what_to_say = 'back')
    {
        return _class('utils')->back($where_go_back, $what_to_say);
    }
}
if ( ! function_exists('js_alert')) {
    function js_alert($text)
    {
        return _class('utils')->js_alert($text);
    }
}
if ( ! function_exists('generate_password')) {
    function generate_password($Length)
    {
        return substr(base64_encode(md5(microtime(true))), 0, $Length);
    }
}
if ( ! function_exists('highlight')) {
    function highlight($string, $search_words, $tag = 'span', $class = 's_word')
    {
        return _class('utils')->highlight($string, $search_words, $tag, $class);
    }
}
if ( ! function_exists('text_filter')) {
    function text_filter($str)
    {
        return _class('utils')->text_filter($str);
    }
}
if ( ! function_exists('_check_words_length')) {
    function _check_words_length($text, $length = 0, $do_encode_email = false)
    {
        return _class('utils')->_check_words_length($text, $length, $do_encode_email);
    }
}
if ( ! function_exists('_filter_text')) {
    function _filter_text($body, $length = 0)
    {
        return _check_words_length(preg_replace("/([^\s]+)\r\n/i", "\$1 \r\n", $body), $length);
    }
}
if ( ! function_exists('_format_date')) {
    function _format_date($input_date = '', $type = 'short')
    {
        return _class('utils')->_format_date($input_date, $type);
    }
}
if ( ! function_exists('_show_avatar')) {
    function _show_avatar($user_id = 0, $user_name = '', $as_link = 0, $is_middle = 0, $only_img_src = 0, $force_link = '')
    {
        return _class('utils')->_show_avatar($user_id, $user_name, $as_link, $is_middle, $only_img_src, $force_link);
    }
}
if ( ! function_exists('_avatar_exists')) {
    function _avatar_exists($user_id = 0, $is_middle = 0)
    {
        return _class('utils')->_avatar_exists($user_id, $is_middle);
    }
}
if ( ! function_exists('_get_age_from_birth')) {
    function _get_age_from_birth($birth_date = '0000-00-00')
    {
        return _class('utils')->_get_age_from_birth($birth_date);
    }
}
if ( ! function_exists('xsb_encode')) {
    function xsb_encode($string)
    {
        return _class('utils')->xsb_encode($string);
    }
}
if ( ! function_exists('xsb_decode')) {
    function xsb_decode($string)
    {
        return _class('utils')->xsb_decode($string);
    }
}
if ( ! function_exists('_display_name')) {
    function _display_name($user_info = [])
    {
        if (is_string($user_info)) {
            return $user_info;
        }
        return empty($user_info['display_name']) ? (empty($user_info['name']) ? $user_info['nick'] : $user_info['name']) : $user_info['display_name'];
    }
}
if ( ! function_exists('_day_suffix_eng')) {
    function _day_suffix_eng($timestamp = 0)
    {
        return _class('utils')->_day_suffix_eng($timestamp);
    }
}
if ( ! function_exists('_add_login_activity')) {
    function _add_login_activity()
    {
        return _class('utils')->_add_login_activity();
    }
}
if ( ! function_exists('_prepare_phone')) {
    function _prepare_phone($phone = '')
    {
        return preg_replace('/[^0-9]/ims', '', $phone);
    }
}
if ( ! function_exists('smart_htmlspecialchars')) {
    function smart_htmlspecialchars($html_text = '')
    {
        return _class('utils')->smart_htmlspecialchars($html_text);
    }
}
if ( ! function_exists('format_bbcode_text')) {
    function format_bbcode_text($body = '')
    {
        return $body ? _class('bb_codes')->_process_text($body) : '';
    }
}
if ( ! function_exists('_mkdir_m')) {
    function _mkdir_m($path_to_create = '', $dir_mode = 0755, $create_index_htmls = 0, $start_folder = '')
    {
        return file_exists($path_to_create) || _class('dir')->mkdir_m($path_to_create, $dir_mode, $create_index_htmls, $start_folder);
    }
}
if ( ! function_exists('_mklink')) {
    function _mklink($target, $link)
    {
        return _class('dir')->mklink($target, $link);
    }
}
if ( ! function_exists('_gen_dir_path')) {
    function _gen_dir_path($id, $path = '', $make = false, $dir_mode = 0755, $create_index_htmls = 1)
    {
        return _class('dir')->_gen_dir_path($id, $path, $make, $dir_mode, $create_index_htmls);
    }
}
if ( ! function_exists('my_array_merge')) {
    function my_array_merge($a1, $a2)
    {
        foreach ((array) $a2 as $k => $v) {
            if (isset($a1[$k]) && is_array($a1[$k])) {
                if (is_array($a2[$k])) {
                    foreach ((array) $a2[$k] as $k2 => $v2) {
                        if (isset($a1[$k][$k1]) && is_array($a1[$k][$k1])) {
                            $a1[$k][$k2] += $v2;
                        } else {
                            $a1[$k][$k2] = $v2;
                        }
                    }
                } else {
                    $a1[$k] += $v;
                }
            } else {
                $a1[$k] = $v;
            }
        }
        return $a1;
    }
}
if ( ! function_exists('_prepare_for_stpl_exec')) {
    function _prepare_for_stpl_exec($source = '')
    {
        return preg_replace('/[^a-z0-9\-_\s]/ims', '', $source);
    }
}
if ( ! function_exists('_profile_link')) {
    function _profile_link($user_info = 0, $skip_get_array = [], $do_add_get = true)
    {
        return _class('utils')->_profile_link($user_info, $skip_get_array, $do_add_get);
    }
}
if ( ! function_exists('_error_need_login')) {
    function _error_need_login($go_after_login = '')
    {
        return _class('utils')->_error_need_login($go_after_login);
    }
}
if ( ! function_exists('_output_cache_trigger')) {
    function _output_cache_trigger($data = [])
    {
        return main()->OUTPUT_CACHING ? _class('output_cache')->_exec_trigger($data) : false;
    }
}
if ( ! function_exists('_country_name')) {
    function _country_name($code = '')
    {
        return _class('utils')->_country_name($code);
    }
}
if ( ! function_exists('_region_name')) {
    function _region_name($region_code = '', $country_code = '')
    {
        return _class('utils')->_region_name($region_code, $country_code);
    }
}
if ( ! function_exists('_email_link')) {
    function _email_link($user_id = 0, $skip_get_array = [], $do_add_get = true)
    {
        return _class('utils')->_email_link($user_id, $skip_get_array, $do_add_get);
    }
}
if ( ! function_exists('_prepare_members_link')) {
    function _prepare_members_link($url = '')
    {
        return _class('utils')->_prepare_members_link($url);
    }
}
if ( ! function_exists('_range')) {
    function _range($_start = 0, $_end = 10)
    {
        $data = [];
        for ($i = $_start; $i <= $_end; $i++) {
            $data[$i] = $i;
        }
        return $data;
    }
}
if ( ! function_exists('_my_strip_tags')) {
    function _my_strip_tags($_text = '')
    {
        return _class('utils')->_my_strip_tags($_text);
    }
}
if ( ! function_exists('checkdnsrr')) {
    function checkdnsrr($hostName, $recType = '')
    {
        return _class('utils')->checkdnsrr($hostName, $recType);
    }
}
if ( ! function_exists('_rename')) {
    function _rename($src_filename, $dest_filename)
    {
        return _class('utils')->_rename($src_filename, $dest_filename);
    }
}
if ( ! function_exists('_cut_bb_codes')) {
    function _cut_bb_codes($body = '')
    {
        return preg_replace('/\[[^\]]+\]/ims', '', $body);
    }
}
if ( ! function_exists('_server_info')) {
    function _server_info($server_id)
    {
        return _class('utils')->_server_info($server_id);
    }
}
if ( ! function_exists('_account_info')) {
    function _account_info($account_id)
    {
        return _class('utils')->_account_info($account_id);
    }
}
if ( ! function_exists('my_explode')) {
    function my_explode($string = '', $divider = PHP_EOL)
    {
        $result = explode($divider, trim($string));
        foreach ((array) $result as $k => $v) {
            $v = trim($v);
            if ( ! strlen($v)) {
                unset($result[$k]);
            } else {
                $result[$k] = $v;
            }
        }
        return array_values($result);
    }
}
if ( ! function_exists('_exec_in_background')) {
    function _exec_in_background($cmd)
    {
        return _class('utils')->_exec_in_background($cmd);
    }
}
if ( ! function_exists('object_to_array')) {
    function object_to_array($d)
    {
        if (is_object($d)) {
            $d = get_object_vars($d);
        }
        if (is_array($d)) {
            return array_map(__FUNCTION__, $d);
        }
        // Return array
        return $d;
    }
}
if ( ! function_exists('obj2arr')) {
    // Much faster (10x) implementation of object_to_array()
    function obj2arr(&$obj)
    {
        $obj = (array) $obj;
        foreach ($obj as &$v) {
            if (is_array($v)) {
                obj2arr($v);
            }
        }
        return $obj;
    }
}
if ( ! function_exists('array_to_object')) {
    function array_to_object($d)
    {
        if (is_array($d)) {
            return (object) array_map(__FUNCTION__, $d);
        }
        // Return object
        return $d;
    }
}
if ( ! function_exists('_floatval')) {
    function _floatval($val = 0)
    {
        return tofloat($val);
    }
}
/*
* This function takes the last comma or dot (if any) to make a clean float, ignoring thousand separator, currency or any other letter
* $num = '1.999,369€';  var_dump(tofloat($num)); // float(1999.369)
* $otherNum = '126,564,789.33 m²';  var_dump(tofloat($otherNum)); // float(126564789.33)
*/
if ( ! function_exists('tofloat')) {
    function tofloat($num = 0)
    {
        if (is_array($num)) {
            return array_map(__FUNCTION__, $num);
        }
        $dotPos = strrpos($num, '.');
        $commaPos = strrpos($num, ',');
        $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
            ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
        if ( ! $sep) {
            return (float) (preg_replace('/[^0-9\-]/', '', $num));
        }
        return (float) (
            preg_replace('/[^0-9\-]/', '', substr($num, 0, $sep)) . '.' .
            preg_replace('/[^0-9]/', '', substr($num, $sep + 1, strlen($num)))
        );
    }
}
// Use this to corrently insert user input into mysql decimal field with float typecasting in the middle
if ( ! function_exists('todecimal')) {
    function todecimal($num = 0, $digits = 2)
    {
        if (is_array($num)) {
            return array_map(__FUNCTION__, $num);
        }
        return str_replace(',', '.', round(tofloat($num), $digits));
    }
}

// Used by tpl, form, table to convert str like this: k1=v1,k2=v2;k3=v3
if ( ! function_exists('_attrs_string2array')) {
    function _attrs_string2array($string = '', $strip_quotes = true)
    {
        $output_array = [];
        if ( ! is_string($string)) {
            return [];
        }
        foreach (explode(';', str_replace(',', ';', trim($string))) as $tmp_string) {
            $tmp_string = trim($tmp_string);
            if ($strip_quotes) {
                $tmp_string = trim(trim($tmp_string, '"\''));
            }
            list($try_key, $try_value) = explode('=', $tmp_string);
            $try_key && $try_key = trim($try_key);
            $try_value && $try_value = trim($try_value);
            if ($strip_quotes) {
                $try_key && $try_key = trim(trim($try_key, '"\''));
                $try_value && $try_value = trim(trim($try_value, '"\''));
            }
            if (strlen($try_key)) {
                $output_array[$try_key] = (string) $try_value;
            }
        }
        return $output_array;
    }
}

// We need this to avoid encoding & => &amp; by standard htmlspecialchars()
if ( ! function_exists('_htmlchars')) {
    function _htmlchars($str = '')
    {
        if (is_array($str)) {
            foreach ((array) $str as $k => $v) {
                $str[$k] = _htmlchars($v);
            }
            return $str;
        }
        $replace = [
            '"' => '&quot;',
            "'" => '&apos;',
            '<' => '&lt;',
            '>' => '&gt;',
        ];
        return str_replace(array_keys($replace), array_values($replace), $str);
    }
}

// Make sure that class attribute contains unique names and also cleanup extra spaces
if ( ! function_exists('_attr_class_clean')) {
    function _attr_class_clean($class = '')
    {
        if ( ! strlen($class) || strpos($class, ' ') === false) {
            return $class;
        }
        $tmp = [];
        foreach (explode(' ', trim($class)) as $v) {
            $v = trim($v);
            if (strlen($v)) {
                $tmp[$v] = $v;
            }
        }
        return implode(' ', $tmp);
    }
}

// Build string of html attributes, used by high-level html generators like form, table, html
if ( ! function_exists('_attrs')) {
    function _attrs($extra, $names)
    {
        $body = [];
        $a = [];
        foreach ((array) $names as $name) {
            if (strlen($name) && isset($extra[$name])) {
                $a[$name] = $extra[$name];
            }
        }
        // Try to find and allow all data-* and ng-* attributes automatically
        foreach ((array) $extra as $name => $val) {
            if (strpos($name, 'data-') === 0 || strpos($name, 'ng-') === 0) {
                $a[$name] = $val;
            }
        }
        // Custom html attributes forced with sub-array "attr"
        if (isset($extra['attr']) && is_array($extra['attr'])) {
            foreach ($extra['attr'] as $name => $val) {
                if (strlen($name)) {
                    $a[$name] = $val;
                }
            }
        }
        // Make sure that class attribute contains unique names and also cleanup extra spaces
        if (isset($a['class']) && strpos($a['class'], ' ') !== false) {
            $a['class'] = _attr_class_clean($a['class']);
        }
        foreach ($a as $name => $val) {
            if (is_array($val)) {
                $body[$name] = _htmlchars($name) . '="' . http_build_query(_htmlchars($val)) . '"';
            } else {
                if ( ! strlen($val)) {
                    continue;
                }
                if ($name == 'id') {
                    $val = fix_html_attr_id($val);
                }
                $body[$name] = _htmlchars($name) . '="' . _htmlchars($val) . '"';
            }
        }
        return $body ? ' ' . implode(' ', $body) : '';
    }
}

if ( ! function_exists('fix_html_attr_id')) {
    function fix_html_attr_id($val)
    {
        $val = trim($val);
        // fix for space-like chars
        $val = str_replace([' ', "\t", "\n", "\r"], '-', $val);
        // fix for cyryllic symbols
        if (strlen($val) && preg_match('~[а-яА-Я]+~ims', $val)) {
            $val = common()->make_translit($val);
        }
        $val = substr(strtolower($val), 0, 128);
        // fix for not allowed chars
        $val = preg_replace('~[^a-z0-9\_-]+~', '', $val);
        // duplicated chars
        $val = trim($val, '-_');
        $val = preg_replace('~([-_])+~', '\1', $val);
        return $val;
    }
}

if ( ! function_exists('load_db_class')) {
    function load_db_class()
    {
        static $_loaded_class;
        if ($_loaded_class) {
            return $_loaded_class;
        }
        $suffix = 'db.class.php';
        $patterns = [
            'db' => [
                APP_PATH . 'classes/' . $suffix,
                APP_PATH . 'plugins/*/classes/' . $suffix,
            ],
            'yf_db' => [
                YF_PATH . 'classes/yf_' . $suffix,
                YF_PATH . 'plugins/*/classes/yf_' . $suffix,
            ],
        ];

        foreach ($patterns as $cls => $paths) {
            foreach ($paths as $path) {
                foreach (glob($path) as $file) {
                    require_once $file;
                    if (class_exists($cls)) {
                        $_loaded_class = $cls;
                        return $_loaded_class;
                    }
                }
            }
        }
        return false;
    }
}

if ( ! function_exists('cache_memcached_connect')) {
    function cache_memcached_connect($params = [])
    {
        global $memcache_obj, $CONF;
        if (isset($memcache_obj)) {
            return $memcache_obj;
        }
        if ( ! $params) {
            $conf_host = $CONF['MEMCACHED_HOST'];
            $conf_port = $CONF['MEMCACHED_PORT'];
            $params = [
            'host' => $conf_host ?: '127.0.0.1',
            'port' => $conf_port ?: 11211,
        ];
        }
        $memcache_obj = null;
        if (class_exists('Memcached')) {
            $memcache_obj = new Memcached();
            $memcache_obj->addServer($params['host'], $params['port']);
        } elseif (function_exists('memcache_connect')) {
            $memcache_obj = memcache_connect($params['host'], $params['port']);
        }
        return $memcache_obj;
    }
}

if ( ! function_exists('_var_dump')) {
    function _var_dump($data)
    {
        ob_start();
        var_dump($data);
        return ob_get_clean();
    }
}

if ( ! function_exists('_var_export')) {
    function _var_export($data)
    {
        $str = var_export($data, 1);
        $str = str_replace('  ', "\t", $str);
        $str = preg_replace('~=>[\s]+array\s\(~ims', '=> [', $str);
        $str = str_replace('array (', '[', $str);
        $str = str_replace(')', ']', $str);
        $str = preg_replace('~=>[\s]+array\(~ims', '=> [', $str);
        return $str;
    }
}

// String comparison with wildcard. See http://php.net/manual/en/function.fnmatch.php
if ( ! function_exists('wildcard_compare')) {
    function wildcard_compare($wild, $string)
    {
        if (is_array($wild)) {
            $func = __FUNCTION__;
            foreach ($wild as $_wild) {
                if ($func($_wild, $string)) {
                    return true;
                }
            }
            return false;
        }
        return fnmatch($wild, $string);
    }
}

if ( ! function_exists('class_basename')) {
    function class_basename($class, $prefix = '', $suffix = '')
    {
        $class = is_object($class) ? get_class($class) : $class;
        $class = basename(str_replace('\\', '/', $class));
        $prefixes = [
            'yf' => YF_PREFIX,
            'site' => 'site__',
            'admin' => 'adm__',
        ];
        if ($prefix) {
            $prefixes['custom'] = $prefix;
        }
        foreach ($prefixes as $_prefix) {
            $plen = strlen($_prefix);
            if ($plen && substr($class, 0, $plen) === $_prefix) {
                $class = substr($class, $plen);
            }
        }
        $slen = strlen($suffix);
        if ($slen && substr($class, -$slen) === $suffix) {
            $class = substr($class, 0, -$slen);
        }
        return $class;
    }
}

// base64 safe for use in urls
// https://en.wikipedia.org/wiki/Base64#Implementations_and_history
// https://tools.ietf.org/html/rfc7515#appendix-C
if ( ! function_exists('base64_encode_safe')) {
    function base64_encode_safe($in)
    {
        return rtrim(strtr(base64_encode($in), '+/', '-_'), '=');
    }
}
if ( ! function_exists('base64_decode_safe')) {
    function base64_decode_safe($in)
    {
        return base64_decode(strtr($in, '-_', '+/'));
    }
}

// https://en.wikipedia.org/wiki/Base58
if ( ! function_exists('base58_encode')) {
    function base58_encode($in)
    {
        return services()->base58_encode($in);
    }
}
if ( ! function_exists('base58_decode')) {
    function base58_decode($in)
    {
        return services()->base58_decode($in);
    }
}
