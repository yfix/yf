<?php

// TODO: requirejs integration, auto-generate its config with switcher on/off
// TODO: cache fill from console, with ability to put into cron task
// TODO: support for multiple media servers
// TODO: support for .min, using some of console minifier (yahoo, google, jsmin ...)
// TODO: move to web accessible folder only after completion to ensure atomicity
// TODO: upload to S3, FTP

class yf_manage_assets
{
    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args);
    }

    /**
     * @param mixed $method
     */
    public function _module_action_handler($method)
    {
        $prepend = $this->_menu();
        if ( ! $method || substr($method, 0, 1) === '_' || ! method_exists($this, $method)) {
            $method = 'show';
        }
        return $prepend . $this->$method() . $append;
    }


    public function _init()
    {
        _class('assets')->USE_CACHE = false;
        _class('assets')->COMBINE = false;
    }


    public function _menu()
    {
        return html()->module_menu($this, [
            ['/@object/cache_info/', 'Cache info', 'fa fa-info'],
            ['/@object/cache_purge/', 'Cache purge', 'fa fa-recycle'],
            ['/@object/cache_fill/', 'Cache fill', 'fa fa-refresh'],
            ['/@object/combine/', 'Combine', 'fa fa-rocket'],
            ['/@object/settings/', 'Settings', 'fa fa-wrench'],
            ['/@object/search_used/', 'Search used', 'fa fa-search'],
            ['/@object/upload/', 'Upload', 'fa fa-upload'],
        ]) . '<br /><br />' . PHP_EOL;
    }


    public function show()
    {
        return redirect('/@object/cache_info');
    }


    public function search_used()
    {
        $exclude_paths = [
            '*/.git/*',
            '*/.dev/*',
            '*/test/*',
            '*/tests/*',
            '*/cache/*',
            '*/test*.class.php',
            '*/' . YF_PREFIX . 'test*.class.php',
        ];
        $regex_php = '~[\s](asset|js|css)\([\s"\']+(?P<name>[^\(\)\{\}\$]+)[\s"\']+\)~ims';
        $raw_in_php = _class('dir')->grep($regex_php, APP_PATH, '*.php', ['exclude_paths' => $exclude_paths]);
        $names = [];
        foreach ((array) $raw_in_php as $path => $matches) {
            $lines = file($path);
            foreach ((array) $matches as $raw) {
                preg_match($regex_php, $raw, $m);
                $name = trim(trim(trim($m['name']), '"\''));
                $names[$name] = $name;
                $raw = trim(trim(trim($raw), '"\''));
                foreach ((array) $lines as $n => $line) {
                    if (strpos($line, $raw) !== false) {
                        $by_line[$path][$raw][$n] = $n;
                        $by_path[$name][$path][$n] = $n;
                    }
                }
            }
        }
        $regex_tpl = '~\{(asset|js|css)\(\)\}\s+(?P<name>[^\{\}\(\)\$]+?)\s+\{\/\1\}~ims';
        $raw_in_tpl = _class('dir')->grep($regex_tpl, APP_PATH, '*.stpl', ['exclude_paths' => $exclude_paths]);
        foreach ((array) $raw_in_tpl as $path => $matches) {
            $lines = file($path);
            foreach ((array) $matches as $raw) {
                preg_match($regex_tpl, $raw, $m);
                $name = trim(trim(trim($m['name']), '"\''));
                $names[$name] = $name;
                $raw = trim(trim(trim($raw), '"\''));
                foreach ((array) $lines as $n => $line) {
                    if (strpos($line, $raw) !== false) {
                        $by_line[$path][$raw][$n] = $n;
                        $by_path[$name][$path][$n] = $n;
                    }
                }
            }
        }
        $assets = _class('assets');
        $names_by_type = [
            'user' => [],
            'admin' => [],
        ];
        foreach ((array) $names as $k => $v) {
            if (substr($k, 0, 2) === '//' || substr($k, 0, 7) === 'http://' || substr($k, 0, 8) === 'https://') {
                unset($names[$k]);
                continue;
            }
            $details = $assets->get_asset_details($k);
            if (empty($details) || (isset($details['config']) && $details['config']['no_cache'])) {
                unset($names[$k]);
                continue;
            }
            if (isset($details['config']['main_type'])) {
                $main_type = $details['config']['main_type'];
                $names_by_type[$main_type][$k] = $v;
            } else {
                $names_by_type['user'][$k] = $v;
                $names_by_type['admin'][$k] = $v;
            }
        }
        ksort($names);
        ksort($names_by_type['user']);
        ksort($names_by_type['admin']);
        $table = [];
        foreach ((array) $names as $name) {
            $table[$name] = '<small>' . implode('<br>', array_keys($by_path[$name])) . '</small>';
        }
        $export = '<' . '?php' . PHP_EOL . 'return array(' . PHP_EOL
            . '\'user\' => ' . preg_replace('~\s{2}[0-9]+\s+=>\s+~i', '  ', var_export(array_keys($names_by_type['user']), 1)) . ',' . PHP_EOL
            . '\'admin\' => ' . preg_replace('~\s{2}[0-9]+\s+=>\s+~i', '  ', var_export(array_keys($names_by_type['admin']), 1)) . ',' . PHP_EOL
            . ');';
        return '<pre style="color:white;background:black;line-height:1em;font-weight:bold;"><small>' . _prepare_html($export) . '</small></pre>'
            . '<h3>Used assets</h3>' . html()->simple_table($table);
    }


    public function cache_info()
    {
        $assets = clone _class('assets');
        $assets->USE_CACHE = false;

        $cache_dir_tpl = preg_replace('~/+~', '/', str_replace('{project_path}', PROJECT_PATH, $assets->CACHE_DIR_TPL));
        $combined_dir_tpl = str_replace('{asset_name}', 'combined', $cache_dir_tpl) . '_combined.*';
        $contents[] = 'Combined info:' . PHP_EOL . shell_exec('ls -l ' . preg_replace('~\{[^\}]+\}~ims', '*', $combined_dir_tpl));

        $cache_dir = substr($cache_dir_tpl, 0, strpos($cache_dir_tpl, '{'));
        $tmp = [];
        foreach ((array) _class('dir')->rglob($cache_dir) as $path) {
            if (is_dir($path) || substr($path, -5, 5) === '.info') {
                continue;
            }
            $tmp[] = $path;
        }
        $contents[] = 'Cached assets:' . PHP_EOL . implode(PHP_EOL, $tmp);

        $contents[] = PHP_EOL . 'Shared url file cache info:' . PHP_EOL . shell_exec('ls -l /tmp/yf_assets/*');
        return 'Cache info: <pre style="line-height:1em;"><small>' . implode(PHP_EOL, $contents) . '</small></pre>';
    }


    public function cache_purge()
    {
        $assets = clone _class('assets');
        $assets->USE_CACHE = false;
        $cache_dir_tpl = preg_replace('~/+~', '/', str_replace('{project_path}', PROJECT_PATH, $assets->CACHE_DIR_TPL));
        $cache_dir = substr($cache_dir_tpl, 0, strpos($cache_dir_tpl, '{')) ?: $cache_dir_tpl;
        if (substr($cache_dir, 0, strlen(PROJECT_PATH)) === PROJECT_PATH && strlen($cache_dir) > strlen(PROJECT_PATH)) {
            if (is_console()) {
                echo 'Cleaning ' . $cache_dir . PHP_EOL;
            }
            _class('dir')->delete($cache_dir, $and_start_dir = true);
        }
        return 'Done';
    }


    public function cache_fill()
    {
        $this->cache_purge();
        // TODO: use temp dir while caching
        // TODO: verify that all files are available
        $assets = clone _class('assets');
        $assets->ADD_IS_DIRECT_OUT = true;
        $assets->USE_CACHE = true;
        $assets->COMBINE = false;
        $assets->FORCE_LOCAL_STORAGE = false;
        ($cache_dir_tpl = $GLOBALS['PROJECT_CONF']['assets']['CACHE_DIR_TPL']) && $assets->CACHE_DIR_TPL = $cache_dir_tpl;
        $combined_names = $assets->load_combined_config($force = true);

        $cur_lang = conf('language');

        $dir = _class('dir');
        $enabled_langs = main()->get_data('languages');
        $main_types = ['user', 'admin'];
        foreach ((array) $main_types as $main_type) {
            $assets->_override['main_type'] = $main_type;
            foreach ((array) $enabled_langs as $lang) {
                conf('language', $lang);
                $assets->_override['language'] = $lang;
                $assets->load_predefined_assets($force = true);
                foreach ((array) $assets->supported_out_types as $out_type) {
                    foreach ((array) $combined_names[$main_type] as $name) {
                        // echo $main_type.' | '.$lang.' | '.$out_type.' | '.$name.'<br>';
                        $direct_out = $assets->add_asset($name, $out_type);
                    }
                }
            }
        }
        conf('language', $cur_lang);
        return $this->cache_info();
    }

    /**
     * Force combine assets according to config.
     */
    public function combine()
    {
        $assets = clone _class('assets');
        $assets->clean_all();
        $assets->ADD_IS_DIRECT_OUT = false;
        $assets->USE_CACHE = true;
        $assets->COMBINE = true;
        $combined_names = $assets->load_combined_config($force = true);
        $assets->FORCE_LOCAL_STORAGE = false;
        ($cache_dir_tpl = $GLOBALS['PROJECT_CONF']['assets']['CACHE_DIR_TPL']) && $assets->CACHE_DIR_TPL = $cache_dir_tpl;

        $cur_lang = conf('language');

        $dir = _class('dir');
        $enabled_langs = main()->get_data('languages');
        $main_types = ['user', 'admin'];
        foreach ((array) $main_types as $main_type) {
            $assets->_override['main_type'] = $main_type;
            foreach ((array) $enabled_langs as $lang) {
                conf('language', $lang);
                $assets->_override['language'] = $lang;
                $assets->load_predefined_assets($force = true);
                foreach ((array) $assets->supported_out_types as $out_type) {
                    $assets->clean_all();
                    $combined_path = $assets->_get_combined_path($out_type);
                    if (file_exists($combined_path)) {
                        unlink($combined_path);
                        unlink($combined_path . '.info');
                    }
                    foreach ((array) $combined_names[$main_type] as $name) {
                        $assets->add_asset($name, $out_type);
                    }
                    $out = $assets->show($out_type);
                    $combined_dir = dirname($assets->_get_combined_path($_out_type = ''));
                    $tmp = [];
                    foreach ((array) $dir->rglob($combined_dir) as $path) {
                        if (is_dir($path) || substr($path, -5, 5) === '.info') {
                            continue;
                        }
                        $tmp[] = $path;
                    }
                    $contents[] = implode(PHP_EOL, $tmp);
                }
            }
        }
        conf('language', $cur_lang);
        return 'Combined info: <pre style="line-height:1em;"><small>' . implode(PHP_EOL, $contents) . '</small></pre>';
    }


    public function upload()
    {
        // TODO: upload cache and combined into outer storage (CDN, FTP, S3, ...)
    }


    public function settings()
    {
        $config_path = CONFIG_PATH . 'assets_combine.php';
        $combined_config = file_get_contents($config_path);
        return 'Current assets combine config: <b>' . $config_path . '</b>'
            . '<pre style="color:white;background:black;line-height:1em;font-weight:bold;"><small>' . _prepare_html($combined_config) . '</small></pre>';
        // TODO: pretty show current important assets settings and optionally allow to change them
    }
}
