<?php

// TODO: compare versions with require_php_lib('php_semver')
// TODO: decide with images: jpeg, png, gif, sprites
// TODO: requirejs integration, auto-generate its config with switcher on/off

class yf_assets
{
    /** @array */
    public $supported_asset_types = [
        'jquery', 'js', 'css', 'less', 'sass', 'coffee', 'bundle', 'asset', /*, 'img', 'font'*/
    ];
    /** @array */
    public $inherit_asset_types_map = [
        'js' => ['jquery'],
    ];
    /** @array */
    public $supported_content_types = [
        'asset', 'url', 'file', 'inline',
    ];
    /** @array */
    public $supported_out_types = [
        'js', 'css', /*, 'images', 'fonts',*/
    ];
    /** @string Set to blank to disable */
    public $MAIN_TPL_CSS = 'style_css';
    /** @string Set to blank to disable */
    public $MAIN_TPL_JS = 'script_js';
    /** @bool Needed to ensure smooth transition of existing codebase. If enabled - then each add() call will immediately return generated content */
    public $ADD_IS_DIRECT_OUT = false;
    /** @int */
    public $URL_TIMEOUT = 15;
    /** @int */
    public $URL_FILE_CACHE_TTL = 3600;
    /** @bool */
    public $USE_CACHE = false;
    /** @int */
    public $CACHE_TTL = 86400;
    /** @string */ // '{project_path}/templates/{main_type}/cache/{host}/{lang}/{asset_name}/{version}/{out_type}/'; // full variant with domain and lang
    public $CACHE_DIR_TPL = '{project_path}/templates/{main_type}/cache/{lang}/{asset_name}/{version}/{out_type}/'; // shorter variant
    /** @bool */
    public $CACHE_INLINE_ALLOW = true;
    /** @int */
    public $CACHE_INLINE_MIN_SIZE = 1000;
    /** @bool */
    public $CACHE_IMAGES_USE_DATA_URI = false;
    /** @int */
    public $CACHE_IMAGES_DATA_URI_MAX_SIZE = 5000;
    /** @bool */
    public $CACHE_OUT_ADD_MTIME = true;
    /** @bool Skip auto-generate cached files on production */
    public $FORCE_LOCAL_STORAGE = false;
    /** @int */
    public $FORCE_LOCAL_TTL = 86400000; // 1000 days * 24 hours * 60 minutes * 60 seconds == almost forever
    /** @bool */
    public $INLINE_ASSETS_USE_DATA_URI = false;
    /** @bool */
    public $FILE_ASSETS_USE_DATA_URI = false;
    /** @int */
    public $FILE_ASSETS_DATA_URI_MAX_SIZE = 5000;
    /** @bool */
    public $COMBINE = false;
    /** @string */
    public $COMBINED_VERSION_TPL = '{year}{month}';
    /** @bool Do not generate combined file on-the-fly */
    public $COMBINED_LOCK = false;
    /** @bool */
    public $COMBINED_CONFIG = null;
    /** @bool */
    public $SHORTEN_LOCAL_URL = true;
    /** @bool */
    public $USE_REQUIRE_JS = false;
    /** @bool */
    public $OUT_ADD_ASSET_NAME = true;
    /** @bool */
    public $ALLOW_URL_CONTROL = true;
    /** @bool */
    public $ALLOW_GET_FROM_BOWER = false;
    /** @bool */
    public $ALLOW_GET_FROM_GITHUB = false;
    /** @bool */
    public $ALLOW_GET_FROM_CDN = false;
    /** @array */
    public $ASSETS_GET_PRIORITY = ['bower', 'github', 'cdn', 'versions'];
    /** @string */
    public $BOWER_COMPONENTS_DIR_TPL = '{app_path}/bower_components/{name}/';
    /** @string */
    public $BOWER_TARGET_DIR_TPL = '{project_path}/uploads/bower_components/{name}/{version}/';
    /** @string */
    public $GITHUB_RAW_URL_TPL = 'https://raw.githubusercontent.com/{repo}/{branch}/';
    /** @string */
    public $GITHUB_TARGET_DIR_TPL = '{project_path}/uploads/github_cache/{repo}/{version}/';
    /** @string */
    public $CDN_TARGET_DIR_TPL = '{project_path}/uploads/cdn_cache/{name}/{version}/';
    /** @bool */
    public $ALLOW_CONTENT_SASS = false;
    /** @bool */
    public $ALLOW_CONTENT_LESS = false;
    /** @bool */
    public $ALLOW_CONTENT_COFFEE = false;

    /** @array Container for added content */
    protected $content = [];
    /** @array List of pre-defined assets */
    protected $assets = [];
    /** @array All filters to apply stored here */
    protected $filters = [];

    public $_assets_added = [];
    public $_bundles_added = [];
    public $_time = null;
    public $_init_js_done = null;
    public $_init_css_done = null;
    public $_autoload_registered = null;
    public $_avail_filters = null;
    public $already_required = [];
    public $_cache_bowerphp = [];
    public $_cache_github = [];
    public $_cache_cdn = [];
    public $_cache_language = null;
    public $_override = [];
    public $_cache_html5fw = null;
    public $_cache_date = null;

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
     * Direct call to object as to string is not allowed, return empty string instead.
     */
    public function __toString()
    {
        return '';
    }


    public function __clone()
    {
        return $this->clean_all();
    }


    public function clean_all()
    {
        $this->content = [];
        $this->filters = [];
        $this->_assets_added = [];
        $this->_bundles_added = [];
    }


    public function _init()
    {
        $this->_url_control();
        if ($this->FORCE_LOCAL_STORAGE) {
            $this->USE_CACHE = true;
            $this->CACHE_TTL = $this->FORCE_LOCAL_TTL;
        }
        $this->load_predefined_assets();
        $this->load_combined_config();
    }


    public function _url_control()
    {
        if ( ! $this->ALLOW_URL_CONTROL) {
            return false;
        }
        $used = [];
        if (isset($_GET['assets_cache'])) {
            $this->USE_CACHE = (bool) $_GET['assets_cache'];
            $used[] = 'assets_cache';
        }
        if (isset($_GET['assets_combine'])) {
            $this->COMBINE = (bool) $_GET['assets_combine'];
            if ($this->COMBINE) {
                $this->USE_CACHE = true;
            }
            $used[] = 'assets_combine';
        }
        if (isset($_GET['assets_requirejs'])) {
            $this->USE_REQUIRE_JS = (bool) $_GET['assets_require_js'];
            $used[] = 'assets_requirejs';
        }
        if (isset($_GET['assets_out_mtime'])) {
            $this->CACHE_OUT_ADD_MTIME = (bool) $_GET['assets_out_mtime'];
            $used[] = 'assets_out_mtime';
        }
        if (DEBUG_MODE) {
            if ($_GET['assets_do_cache']) {
                $this->_do_cache();
                $used[] = 'assets_do_cache';
            }
            if ($_GET['assets_do_combine']) {
                $this->_do_combine();
                $used[] = 'assets_do_combine';
            }
            if ($_GET['assets_do_purge']) {
                $this->_do_purge();
                $used[] = 'assets_do_purge';
            }
        }
        if ($used) {
            header('X-Robots-Tag: noindex,nofollow,noarchive,nosnippet');
        }
    }


    public function _do_purge()
    {
        $cache_dir_tpl = preg_replace('~/+~', '/', str_replace('{project_path}', PROJECT_PATH, $this->CACHE_DIR_TPL));
        $cache_dir = substr($cache_dir_tpl, 0, strpos($cache_dir_tpl, '{')) ?: $cache_dir_tpl;
        if (substr($cache_dir, 0, strlen(PROJECT_PATH)) === PROJECT_PATH && strlen($cache_dir) > strlen(PROJECT_PATH)) {
            _class('dir')->delete($cache_dir, $and_start_dir = true);
        }
        header('X-YF-assets-do-purge: true');
    }


    public function _do_cache()
    {
        $this->_do_purge();

        $bak['ADD_IS_DIRECT_OUT'] = $this->ADD_IS_DIRECT_OUT;
        $this->ADD_IS_DIRECT_OUT = true;
        $bak['USE_CACHE'] = $this->USE_CACHE;
        $this->USE_CACHE = true;

        $combined_names = $this->load_combined_config($force = true);
        foreach ((array) $this->supported_out_types as $out_type) {
            foreach ((array) $combined_names[MAIN_TYPE] as $name) {
                $direct_out = $this->add_asset($name, $out_type);
            }
        }
        header('X-YF-assets-do-cache: true');

        $this->ADD_IS_DIRECT_OUT = $bak['ADD_IS_DIRECT_OUT'];
        $this->USE_CACHE = $bak['USE_CACHE'];
    }


    public function _do_combine()
    {
        $this->_do_cache();

        $bak['ADD_IS_DIRECT_OUT'] = $this->ADD_IS_DIRECT_OUT;
        $this->ADD_IS_DIRECT_OUT = true;
        $bak['USE_CACHE'] = $this->USE_CACHE;
        $this->USE_CACHE = true;
        $bak['COMBINE'] = $this->COMBINE;
        $this->COMBINE = true;

        $combined_names = $this->load_combined_config($force = true);
        foreach ((array) $this->supported_out_types as $out_type) {
            $combined_path = $this->_get_combined_path($out_type);
            if (file_exists($combined_path)) {
                unlink($combined_path);
                unlink($combined_path . '.info');
            }
            foreach ((array) $combined_names[MAIN_TYPE] as $name) {
                $this->add_asset($name, $out_type);
            }
            $out = $this->show($out_type);
        }
        header('X-YF-assets-do-combine: true');

        $this->ADD_IS_DIRECT_OUT = $bak['ADD_IS_DIRECT_OUT'];
        $this->USE_CACHE = $bak['USE_CACHE'];
        $this->COMBINE = $bak['COMBINE'];
    }


    public function _get_media_path()
    {
        $media_path = MEDIA_PATH;
        if (substr($media_path, 0, 2) === '//') {
            $media_path = 'http:' . $media_path;
        }
        return $media_path;
    }

    /**
     * Smart wrapper with temp file cache.
     * @param mixed $url
     */
    public function _url_get_contents($url)
    {
        if ( ! strlen($url)) {
            return false;
        }
        // Possibly inline code
        if (false !== strpos($url, "\t") && false !== strpos($url, PHP_EOL) || strlen($url) >= 512) {
            return $url;
        }
        $media_path = $this->_get_media_path();
        // Do not use web server for self-accessible paths
        if (substr($url, 0, strlen($media_path)) === $media_path) {
            $path = PROJECT_PATH . substr($url, strlen($media_path));
            // This line needed to strip query string from file name like this:
            // /templates/user/css/style.css?1416914173 -> templates/user/css/style.css
            $path = parse_url($path, PHP_URL_PATH);
            return file_get_contents($path);
        }
        $url = (substr($url, 0, 2) === '//' ? 'http:' : '') . $url;
        // Save syscall
        if ( ! isset($this->_time)) {
            $this->_time = time();
        }
        $cache_path = '/tmp/yf_assets/' . urlencode($url) . '.cache';
        // 24 hours tmp file cache
        if ($cache_path && file_exists($cache_path) && filemtime($cache_path) > ($this->_time - $this->URL_FILE_CACHE_TTL)) {
            return file_get_contents($cache_path);
        }
        $data = file_get_contents($url, false, stream_context_create([
            'http' => ['timeout' => $this->URL_TIMEOUT],
        ]));
        if ($cache_path) {
            $cache_dir = dirname($cache_path);
            if ( ! file_exists($cache_dir)) {
                mkdir($cache_dir, 0755, $recurse = true);
            }
            file_put_contents($cache_path, $data);
        }
        return $data;
    }

    /**
     * Main JS from theme stpl.
     * @param mixed $force
     */
    public function init_js($force = false)
    {
        if ($this->_init_js_done && ! $force) {
            return false;
        }
        $this->_init_js_done = true;
        // Check if disabled
        if ( ! $this->MAIN_TPL_JS) {
            return false;
        }
        $main_script_js = trim(tpl()->parse_if_exists($this->MAIN_TPL_JS));
        // single string = automatically generated by compass
        if (strlen($main_script_js) && strpos($main_script_js, "\n") === false && preg_match('~^js/script.js\?[0-9]{10}$~ims', $main_script_js)) {
            $media_path = $this->_get_media_path();
            $this->add($media_path . tpl()->TPL_PATH . $main_script_js, 'js', 'url');
        } else {
            $this->add($main_script_js, 'js', 'inline');
        }
    }

    /**
     * Main CSS from theme stpl.
     * @param mixed $force
     */
    public function init_css($force = false)
    {
        if ($this->_init_css_done && ! $force) {
            return false;
        }
        $this->_init_css_done = true;
        // Check if disabled
        if ( ! $this->MAIN_TPL_CSS) {
            return false;
        }
        $main_style_css = trim(tpl()->parse_if_exists($this->MAIN_TPL_CSS));
        // single string = automatically generated by compass
        if (strlen($main_style_css) && strpos($main_style_css, "\n") === false && preg_match('~^css/style.css\?[0-9]{10}$~ims', $main_style_css)) {
            $media_path = $this->_get_media_path();
            $this->add($media_path . tpl()->TPL_PATH . $main_style_css, 'css', 'inline');
        } else {
            $this->add($main_style_css, 'css', 'inline');
        }
    }

    /**
     * @param mixed $force
     */
    public function load_combined_config($force = false)
    {
        if ( ! $this->COMBINE && ! $force) {
            return false;
        }
        if (isset($this->COMBINED_CONFIG) && ! $force) {
            return $this->COMBINED_CONFIG;
        }
        $path = CONFIG_PATH . 'assets_combine.php';
        if (file_exists($path)) {
            $this->COMBINED_CONFIG = include $path;
        }
        return $this->COMBINED_CONFIG;
    }


    public function _autoload_libs()
    {
        if (isset($this->_autoload_registered)) {
            return true;
        }
        $paths = [
            'app' => APP_PATH . 'vendor/autoload.php',
            'yf' => YF_PATH . 'vendor/autoload.php',
            'server' => '/usr/local/share/composer/vendor/autoload.php',
        ];
        $path_loaded = '';
        foreach ($paths as $name => $path) {
            if (file_exists($path)) {
                $path_loaded = $name;
                require_once $path;
                break;
            }
        }
        $this->_autoload_registered = $paths[$path_loaded];
    }

    /**
     * @param mixed $force
     */
    public function load_predefined_assets($force = false)
    {
        // Cleanup previously filled assets
        if ($force) {
            $this->assets = [];
        }
        $assets = [];
        $suffix = '.php';
        $pattern = '{,plugins/*/}{assets/,share/assets/}{*,*/*}' . $suffix;
        $globs = [
            'framework' => YF_PATH . $pattern,
            'project' => PROJECT_PATH . $pattern,
            'app' => APP_PATH . $pattern,
        ];
        if (is_site_path()) {
            $globs['site'] = SITE_PATH . $pattern;
        }
        $slen = strlen($suffix);
        $names = [];
        foreach ($globs as $gname => $glob) {
            foreach (glob($glob, GLOB_BRACE) as $path) {
                $name = substr(basename($path), 0, -$slen);
                $names[$name] = $path;
                $names_paths[$name][$gname] = $path;
            }
        }
        // This double iterating code ensures we can inherit/replace assets with same name inside project
        foreach ($names as $name => $path) {
            $assets[$name] = include $path;
            if (defined('DEBUG_INFO') && constant('DEBUG_INFO')) {
                debug('assets_names[]', [
                    'name' => $name,
                    'path' => $path,
                    'content' => $assets[$name],
                ]);
            }
        }
        $this->assets += $assets;
        return $assets;
    }

    /**
     * Get list of built-in filters.
     */
    public function filters_get_avail()
    {
        if (isset($this->_avail_filters)) {
            return $this->_avail_filters;
        }
        $names = [];
        $suffix = '.class.php';
        $prefix = 'assets_filter_';
        $prefix2 = YF_PREFIX;
        $pattern = '{,plugins/*/}classes/{assets,assets_filters}/*' . $prefix . '*' . $suffix;
        $globs = [
            'framework' => YF_PATH . $pattern,
            'project' => PROJECT_PATH . $pattern,
            'app' => APP_PATH . $pattern,
        ];
        if (is_site_path()) {
            $globs['site'] = SITE_PATH . $pattern;
        }
        $slen = strlen($suffix);
        $plen = strlen($prefix);
        $plen2 = strlen($prefix2);
        $names = [];
        foreach ($globs as $gname => $glob) {
            foreach (glob($glob, GLOB_BRACE) as $path) {
                $name = substr(basename($path), 0, -$slen);
                if (substr($name, 0, $plen2) === $prefix2) {
                    $name = substr($name, $plen2);
                }
                if (substr($name, 0, $plen) === $prefix) {
                    $name = substr($name, $plen);
                }
                $names[$name] = $path;
            }
        }
        $this->_avail_filters = $names;
        return $names;
    }

    /**
     * Register new bundle or replace existing by name on-the-fly.
     * @param mixed $name
     */
    public function bundle_register($name, array $config)
    {
        $this->assets[$name] = $config;
        return $this;
    }

    /**
     * Search for assets for current module in several places, where it can be stored.
     * @param mixed $asset_type
     * @param mixed $module
     */
    public function find_asset_type_for_module($asset_type, $module = '')
    {
        if ( ! $module) {
            $module = $_GET['object'];
        }
        $ext = '.' . $asset_type;
        $path = $module . '/' . $module . $ext;
        $paths = [
            'yf_admin' => MAIN_TYPE_ADMIN ? YF_PATH . 'templates/admin/' . $path : '',
            'yf_user' => YF_PATH . 'templates/user/' . $path,
            'yf_plugins_admin' => MAIN_TYPE_ADMIN ? YF_PATH . 'plugins/' . $module . '/templates/admin/' . $path : '',
            'yf_plugins_user' => YF_PATH . 'plugins/' . $module . '/templates/user/' . $path,
            'project_admin' => MAIN_TYPE_ADMIN ? PROJECT_PATH . 'templates/admin/' . $path : '',
            'project_user' => PROJECT_PATH . 'templates/user/' . $path,
            'site_user' => is_site_path() ? SITE_PATH . 'templates/user/' . $path : '',
        ];
        $found = '';
        foreach (array_reverse($paths, true) as $path) {
            if ( ! strlen($path)) {
                continue;
            }
            if (file_exists($path)) {
                $found = $path;
                break;
            }
        }
        return $found;
    }

    /**
     * Helper for jquery on document ready.
     * @param mixed $content
     * @param mixed $params
     */
    public function jquery($content, $params = [])
    {
        return $this->helper_js_library(__FUNCTION__, $content, $params + ['wrap' => '$(function(){' . PHP_EOL . '%s' . PHP_EOL . '})']);
    }

    /**
     * Helper for JS library code.
     * @param mixed $lib_name
     * @param mixed $content
     * @param mixed $params
     */
    public function helper_js_library($lib_name, $content, $params = [])
    {
        $asset_type = 'js';
        if ( ! isset($this->already_required[$asset_type][$lib_name])) {
            $this->add_asset($lib_name, $asset_type, $this->ADD_IS_DIRECT_OUT ? ['direct_out' => false] : []);
            $this->already_required[$asset_type][$lib_name] = true;
        }
        return $this->add($content, $asset_type, 'inline', $params);
    }

    /**
     * @param mixed $name
     */
    public function get_asset_details($name)
    {
        // If overall asset is callable - call it and save result to prevent multiple calls
        if ( ! is_string($this->assets[$name]) && is_callable($this->assets[$name])) {
            $func = $this->assets[$name];
            $this->assets[$name] = $func($this);
        }
        return $this->assets[$name];
    }

    /**
     * Return named asset, also can return specific version.
     * @name can be just asset name or also contain version: "jquery", "jquery#1.*"
     * @param mixed $name
     * @param mixed $asset_type
     * @param mixed $version
     */
    public function get_asset($name, $asset_type, $version = '')
    {
        if ( ! $name) {
            return null;
        }
        strpos($name, '#') !== false && list($name, $version) = explode('#', $name);
        $asset_data = $this->get_asset_details($name);
        if ( ! $asset_data) {
            return null;
        }
        if ( ! is_string($asset_data) && is_callable($asset_data)) {
            $asset_data = $asset_data($this);
        }
        foreach ((array) $this->ASSETS_GET_PRIORITY as $method) {
            if (in_array($method, ['bower', 'github'])) {
                $data = $asset_data[$method] ?: [];
                $_files = $data[$asset_type];
                if ($_files) {
                    $_name = $data['name'] ?: $name;
                    strpos($_name, '#') !== false && list($_name, $_version) = explode('#', $_name);
                    $_version = $version ?: $_version ?: $data['version'] ?: 'master';
                }
            }
            if ($method == 'bower' && $this->ALLOW_GET_FROM_BOWER && $_files) {
                return $this->get_asset_from_bower($_name, $_version, $data, $asset_type);
            } elseif ($method == 'github' && $this->ALLOW_GET_FROM_GITHUB && $_files) {
                return $this->get_asset_from_github($_name, $_version, $data, $asset_type);
            } elseif ($method == 'cdn' && $this->ALLOW_GET_FROM_CDN && isset($asset_data[$method]) && isset($asset_data[$method][$asset_type])) {
                $data = $asset_data[$method];
                $_version = $version ?: $data['version'] ?: 'master';
                return $this->get_asset_from_cdn($name, $_version, $data, $asset_type);
            } elseif ($method == 'versions') {
                if ( ! is_array($asset_data[$method])) {
                    return null;
                }
                $data = $asset_data[$method] ?: [];
                $version = $this->find_version_best_match($version, array_keys($data));
                if ( ! $version || ! isset($data[$version])) {
                    return null;
                }
                $version_info = $data[$version];
                $content = $version_info[$asset_type];
                return $content;
            }
        }
    }

    /**
     * @param mixed $name
     * @param mixed $version
     * @param mixed $asset_data
     * @param mixed $asset_type
     */
    public function get_asset_from_bower($name, $version = 'master', $asset_data = [], $asset_type)
    {
        if ( ! $name || ! $asset_data || ! isset($asset_data[$asset_type])) {
            return false;
        }
        $files = $asset_data[$asset_type];
        ! $version && $version = 'master';
        $cache = &$this->_cache_bowerphp[$asset_type];
        if (isset($cache[$name])) {
            return $cache[$name];
        }
        $replace = $this->_get_asset_tpl_replace($name, $version);
        $target_dir = str_replace(['///', '//'], '/', str_replace(array_keys($replace), array_values($replace), $this->BOWER_TARGET_DIR_TPL));
        // Pre-check if all required files are there
        $need_to_continue = false;
        $web = [];
        foreach ((array) $files as $from => $to) {
            if (is_numeric($from)) {
                $from = $to;
            }
            $to_path = $target_dir . $to;
            if ( ! file_exists($to_path)) {
                $need_to_continue = true;
                break;
            }
            $web[] = MEDIA_PATH . substr($to_path, strlen(PROJECT_PATH));
        }
        if ( ! $need_to_continue) {
            $cache[$name] = $web;
            return $cache[$name];
        }
        $need_bower_run = true;
        // Start full variant if something is missing on previous step
        $bower_component_dir = str_replace(['///', '//'], '/', str_replace(array_keys($replace), array_values($replace), $this->BOWER_COMPONENTS_DIR_TPL));
        if (file_exists($bower_component_dir . 'bower.json')) {
            if ($version == 'master') {
                $need_bower_run = false;
            } else {
                $bconf = json_decode(file_get_contents($bower_component_dir . 'bower.json'), true);
                if ($bconf['version'] && $bconf['version'] == $version) {
                    $need_bower_run = false;
                }
            }
        }
        if ($need_bower_run) {
            $bowerphp_bin = '/usr/local/share/composer/vendor/bin/bowerphp';
            if ( ! file_exists($bowerphp_bin)) {
                exec('/usr/local/bin/composer global require beelab/bowerphp', $out, $ret);
                if ( ! file_exists($bowerphp_bin)) {
                    return false;
                }
            }
            exec('cd ' . APP_PATH . ' && ' . $bowerphp_bin . ' install ' . $name . '#' . $version, $out, $ret);
            if ( ! file_exists($bower_component_dir)) {
                return false;
            }
        }
        $web = [];
        foreach ((array) $files as $from => $to) {
            if (is_numeric($from)) {
                $from = $to;
            }
            $from_path = $bower_component_dir . $from;
            $to_path = $target_dir . $to;
            if ( ! file_exists($to_path) || filesize($from_path) != filesize($to_path)) {
                $dir_to = dirname($to_path);
                ! file_exists($dir_to) && mkdir($dir_to, 0755, true);
                // Copy instead of symlink to allow to delete or miss source bower components dir
                file_put_contents($to_path, file_get_contents($from_path));
            }
            $web[] = MEDIA_PATH . substr($to_path, strlen(PROJECT_PATH));
        }
        $cache[$name] = $web;
        return $cache[$name];
    }

    /**
     * @param mixed $name
     * @param mixed $version
     * @param mixed $asset_data
     * @param mixed $asset_type
     */
    public function get_asset_from_github($name, $version = 'master', $asset_data = [], $asset_type)
    {
        if ( ! $name || ! $asset_data || ! isset($asset_data[$asset_type])) {
            return false;
        }
        $files = $asset_data[$asset_type];
        ! $version && $version = 'master';
        $cache = &$this->_cache_github[$asset_type];
        if (isset($cache[$name])) {
            return $cache[$name];
        }
        $replace = $this->_get_asset_tpl_replace($name, $version);
        $target_dir = str_replace(['///', '//'], '/', str_replace(array_keys($replace), array_values($replace), $this->GITHUB_TARGET_DIR_TPL));
        // Please do not replace 2 slashes inside url
        $github_url = str_replace(array_keys($replace), array_values($replace), $this->GITHUB_RAW_URL_TPL);
        $web = [];
        foreach ((array) $files as $from => $to) {
            if (is_numeric($from)) {
                $from = $to;
            }
            $to_path = rtrim($target_dir, '/') . '/' . ltrim($to, '/');
            if ( ! file_exists($to_path)) {
                $url = rtrim($github_url, '/') . '/' . ltrim($from, '/');
                $data = $this->_url_get_contents($url);
                if (strlen($data)) {
                    $dir_to = dirname($to_path);
                    ! file_exists($dir_to) && mkdir($dir_to, 0755, true);
                    file_put_contents($to_path, $data);
                }
            }
            $web[] = MEDIA_PATH . substr($to_path, strlen(PROJECT_PATH));
        }
        $cache[$name] = $web;
        return $cache[$name];
    }

    /**
     * @param mixed $name
     * @param mixed $version
     * @param mixed $asset_data
     * @param mixed $asset_type
     */
    public function get_asset_from_cdn($name, $version = 'master', $asset_data = [], $asset_type)
    {
        if ( ! $name || ! $asset_data || ! isset($asset_data[$asset_type]) || ! $asset_data['url']) {
            return false;
        }
        $cdn_url_tpl = $asset_data['url'];
        $files = $asset_data[$asset_type];
        ! $version && $version = 'master';
        $cache = &$this->_cache_cdn[$asset_type];
        if (isset($cache[$name])) {
            return $cache[$name];
        }
        $replace = $this->_get_asset_tpl_replace($name, $version);
        $target_dir = str_replace(['///', '//'], '/', str_replace(array_keys($replace), array_values($replace), $this->CDN_TARGET_DIR_TPL));
        // Please do not replace 2 slashes inside url
        $cdn_url = str_replace(array_keys($replace), array_values($replace), $cdn_url_tpl);
        $web = [];
        foreach ((array) $files as $from => $to) {
            if (is_numeric($from)) {
                $from = $to;
            }
            $to_path = rtrim($target_dir, '/') . '/' . ltrim($to, '/');
            if ( ! file_exists($to_path)) {
                $url = rtrim($cdn_url, '/') . '/' . ltrim($from, '/');
                $data = $this->_url_get_contents($url);
                if (strlen($data)) {
                    $dir_to = dirname($to_path);
                    ! file_exists($dir_to) && mkdir($dir_to, 0755, true);
                    file_put_contents($to_path, $data);
                }
            }
            $web[] = MEDIA_PATH . substr($to_path, strlen(PROJECT_PATH));
        }
        $cache[$name] = $web;
        return $cache[$name];
    }

    /**
     * @param mixed $name
     * @param mixed $version
     */
    public function _get_asset_tpl_replace($name, $version = 'master')
    {
        ! isset($this->_cache_language) && $this->_cache_language = conf('language') ?: 'en';
        return [
            '{site_path}' => rtrim(SITE_PATH, '/'),
            '{app_path}' => rtrim(APP_PATH, '/'),
            '{project_path}' => rtrim(PROJECT_PATH, '/'),
            '{repo}' => $name,
            '{name}' => $name,
            '{version}' => $version,
            '{branch}' => $version,
            '{main_type}' => $this->_override['main_type'] ?: MAIN_TYPE,
            '{host}' => $this->_override['host'] ?: $_SERVER['HTTP_HOST'],
            '{lang}' => $this->_cache_language,
        ];
    }

    /**
     * Add asset item into current workflow.
     *
     * $content: string/array
     * $asset_type: = bundle|asset|js|jquery|css|img|less|sass|font
     * $content_type_hint: = auto|asset|url|file|inline
     * @param mixed $content
     * @param mixed $asset_type
     * @param mixed $content_type_hint
     * @param mixed $params
     */
    public function add($content, $asset_type = 'bundle', $content_type_hint = 'auto', $params = [])
    {
        if (DEBUG_MODE) {
            $trace = main()->trace_string();
        }
        if ( ! is_string($content) && is_callable($content)) {
            $content = $content($params, $this);
        }
        if ($asset_type === 'js' && ! $this->_init_js_done) {
            $this->init_js();
        } elseif ($asset_type === 'css' && ! $this->_init_css_done) {
            $this->init_css();
        }
        if (is_array($content_type_hint)) {
            $params = (array) $params + $content_type_hint;
            $content_type_hint = $params['type'];
        }
        if ( ! $asset_type || $asset_type === 'asset') {
            $asset_type = 'bundle';
        }
        if ( ! in_array($asset_type, $this->supported_asset_types)) {
            throw new Exception('Assets add(): unsupported asset type: ' . $asset_type);
            return $this;
        }
        if (is_array($content) && isset($content['content'])) {
            if (is_array($content['params'])) {
                $params += $content['params'];
            }
            $content = $content['content'];
        }
        if ($asset_type === 'jquery') {
            return $this->jquery($content, $params);
        }
        $DIRECT_OUT = isset($params['direct_out']) ? $params['direct_out'] : $this->ADD_IS_DIRECT_OUT;
        if (empty($content)) {
            return $DIRECT_OUT ? $this->show($asset_type) : $this;
        }
        if ( ! is_array($content)) {
            $content = [$content];
        }
        if ( ! $content_type_hint) {
            $content_type_hint = 'auto';
        }
        if (is_array($content_type_hint)) {
            $params = (array) $params + $content_type_hint;
            $content_type_hint = $params['type'];
        }
        foreach ((array) $content as $_content) {
            $_params = $params;
            if ( ! is_string($_content) && is_callable($_content)) {
                $_content = $_content($_params, $this);
            }
            if (is_array($_content) && isset($_content['content'])) {
                if (is_array($_content['params'])) {
                    $_params += $_content['params'];
                }
                $_content = $_content['content'];
            }
            if (is_array($_content)) {
                $this->add($_content, $asset_type, $content_type_hint, $_params);
                continue;
            }
            $_content = trim($_content);
            if ( ! $_content) {
                continue;
            }
            $md5 = md5($_content);
            if ($this->_is_content_added($asset_type, $md5)) {
                continue;
            }
            if ($asset_type === 'bundle') {
                $this->_add_bundle($_content, $_params);
                continue;
            }
            $content_type = '';
            if (in_array($content_type_hint, $this->supported_content_types)) {
                $content_type = $content_type_hint;
            } else {
                $content_type = $this->detect_content_type($asset_type, $_content);
            }
            $asset_data = [];
            if ($content_type === 'asset') {
                $this->_add_asset($_content, $asset_type, $_params);
            } elseif ($content_type === 'url') {
                $this->set_content($asset_type, $md5, 'url', $_content, $_params);
            } elseif ($content_type === 'file') {
                if (file_exists($_content)) {
                    $str = file_get_contents($_content);
                    if (strlen($str)) {
                        $this->set_content($asset_type, $md5, 'file', $_content, $_params);
                    }
                }
            } elseif ($content_type === 'inline') {
                $this->set_content($asset_type, $md5, 'inline', $_content, $_params);
            }
            if (DEBUG_MODE) {
                debug('assets_add[]', [
                    'asset_type' => $asset_type,
                    'content_type' => $content_type,
                    'md5' => $md5,
                    'content' => $_content,
                    'is_added' => $this->get_content($asset_type, $md5) !== null,
                    'preview' => '',
                    'params' => $_params,
                    'trace' => $trace,
                ]);
            }
        }
        return $DIRECT_OUT ? $this->show_css() . $this->show_js() : $this;
    }

    /**
     * @param mixed $_content
     * @param mixed $_params
     */
    public function _add_bundle($_content, $_params = [])
    {
        if ( ! $_content) {
            return false;
        }
        // Prevent recursion
        if (is_string($_content)) {
            if (isset($this->_bundles_added[$_content])) {
                return false;
            }
            $this->_bundles_added[$_content] = true;
        }
        $bundle_details = $this->get_asset_details($_content);
        if ( ! $bundle_details) {
            return false;
        }
        if ( ! is_string($bundle_details) && is_callable($bundle_details)) {
            $bundle_details = $bundle_details($_params, $this);
        }
        if ( ! $bundle_details) {
            return false;
        }
        if (isset($bundle_details['config']) && is_array($bundle_details['config'])) {
            $_params['config'] = (array) $_params['config'] + (array) $bundle_details['config'];
        }
        $DIRECT_OUT = isset($_params['direct_out']) ? $_params['direct_out'] : $this->ADD_IS_DIRECT_OUT;
        $_params += ($DIRECT_OUT ? ['direct_out' => false] : []);
        if (is_string($_content)) {
            $_params['name'] = $_content;
        }
        $__params = $_params;
        // Prevent inherit no_cache and other package-related settings into other required packages
        if (isset($__params['config'])) {
            unset($__params['config']);
        }
        $inherit_types_map = $this->inherit_asset_types_map;
        $types = [];
        foreach ((array) $this->supported_asset_types as $k => $atype) {
            if ($atype === 'jquery') {
                continue;
            }
            $types[$atype] = $atype;
            $inherit_types = (array) $inherit_types_map[$atype] ?: [];
            foreach ((array) $inherit_types as $inherit_type) {
                $types[$inherit_type] = $inherit_type;
            }
        }
        foreach ((array) $types as $atype) {
            $data = $bundle_details['require'][$atype] ?? null;
            if ($data) {
                $this->_sub_add($data, $atype, $__params);
            }
        }
        foreach ((array) $types as $atype) {
            $data = $this->get_asset($_content, $atype);
            if ($data) {
                $this->_sub_add($data, $atype, $_params);
            }
        }
        foreach ((array) $types as $atype) {
            $data = $bundle_details['add'][$atype] ?? null;
            if ($data) {
                $this->_sub_add($data, $atype, $__params);
            }
        }
    }

    /**
     * @param mixed $_content
     * @param mixed $asset_type
     * @param mixed $_params
     */
    public function _add_asset($_content, $asset_type, $_params = [])
    {
        if ( ! $_content) {
            return false;
        }
        // Prevent recursion
        if (is_string($_content)) {
            if (isset($this->_assets_added[$asset_type][$_content])) {
                return false;
            }
            $this->_assets_added[$asset_type][$_content] = true;
        }
        $asset_data = $this->get_asset_details($_content);
        if ( ! $asset_data) {
            return false;
        }
        if ( ! is_string($asset_data) && is_callable($asset_data)) {
            $asset_data = $asset_data($_params, $this);
        }
        if ( ! $asset_data) {
            return false;
        }
        if (isset($asset_data['config']) && is_array($asset_data['config'])) {
            $_params['config'] = (array) $_params['config'] + (array) $asset_data['config'];
        }
        $DIRECT_OUT = isset($_params['direct_out']) ? $_params['direct_out'] : $this->ADD_IS_DIRECT_OUT;
        $_params += ($DIRECT_OUT ? ['direct_out' => false] : []);
        if (is_string($_content)) {
            $_params['name'] = $_content;
        }
        $__params = $_params;
        // Prevent inherit no_cache and other package-related settings into other required packages
        if (isset($__params['config'])) {
            unset($__params['config']);
        }

        $inherit_types_map = $this->inherit_asset_types_map;
        $types = [];
        $types[$asset_type] = $asset_type;
        $inherit_types = (array) $inherit_types_map[$asset_type] ?: [];
        foreach ((array) $inherit_types as $inherit_type) {
            $types[$inherit_type] = $inherit_type;
        }
        foreach ((array) $types as $atype) {
            $data = $asset_data['require'][$atype] ?? null;
            if ($data) {
                $this->_sub_add($data, $atype, $__params);
            }
        }
        foreach ((array) $types as $atype) {
            $data = $this->get_asset($_content, $atype);
            if ($data) {
                $this->_sub_add($data, $atype, $_params);
            }
        }
        foreach ((array) $types as $atype) {
            $data = $asset_data['add'][$atype] ?? null;
            if ($data) {
                $this->_sub_add($data, $atype, $__params);
            }
        }
    }

    /**
     * @param mixed $info
     * @param mixed $asset_type
     * @param mixed $_params
     */
    public function _sub_add($info, $asset_type, $_params = [])
    {
        if ( ! $info) {
            return false;
        }
        if ( ! is_string($info) && is_callable($info)) {
            $info = $info($_params, $this);
        }
        if ( ! $info) {
            return false;
        }
        if ( ! is_array($info)) {
            $info = [$info];
        }
        if (is_array($info) && isset($info['content'])) {
            if (is_array($info['params'])) {
                $_params += $info['params'];
            }
            $info = $info['content'];
            if ( ! $info) {
                return false;
            }
            if ( ! is_array($info)) {
                $info = [$info];
            }
        }
        if ( ! $info) {
            return false;
        }
        foreach ((array) $info as $_info) {
            if ($_info) {
                $this->add($_info, $asset_type, 'auto', $_params);
            }
        }
    }

    /**
     * Shortcut.
     * @param mixed $content
     * @param mixed $asset_type
     * @param mixed $params
     */
    public function add_url($content, $asset_type, $params = [])
    {
        return $this->add($content, $asset_type, 'url', $params);
    }

    /**
     * Shortcut.
     * @param mixed $content
     * @param mixed $asset_type
     * @param mixed $params
     */
    public function add_file($content, $asset_type, $params = [])
    {
        return $this->add($content, $asset_type, 'file', $params);
    }

    /**
     * Shortcut.
     * @param mixed $content
     * @param mixed $asset_type
     * @param mixed $params
     */
    public function add_inline($content, $asset_type, $params = [])
    {
        return $this->add($content, $asset_type, 'inline', $params);
    }

    /**
     * Shortcut.
     * @param mixed $content
     * @param mixed $asset_type
     * @param mixed $params
     */
    public function add_asset($content, $asset_type, $params = [])
    {
        return $this->add($content, $asset_type, 'asset', $params);
    }

    /**
     * Return content for given asset type, optionally only for md5 of it.
     * @param mixed $asset_type
     * @param mixed $params
     */
    public function get_content($asset_type, $params = [])
    {
        $md5 = (is_string($params) && strlen($params) === 32) ? $params : (isset($params['md5']) ? $params['md5'] : '');
        if ( ! isset($this->content[$asset_type])) {
            $this->content[$asset_type] = [];
        }
        return $md5 ? $this->content[$asset_type][$md5] : $this->content[$asset_type];
    }

    /**
     * @param mixed $asset_type
     * @param mixed $md5
     */
    public function _is_content_added($asset_type, $md5)
    {
        return isset($this->content[$asset_type][$md5]);
    }

    /**
     * Set unique content entry for given asset type.
     * @param mixed $asset_type
     * @param mixed $md5
     * @param mixed $content_type
     * @param mixed $content
     * @param mixed $params
     */
    public function set_content($asset_type, $md5, $content_type, $content, $params = [])
    {
        if (isset($this->content[$asset_type][$md5])) {
            return $this->content[$asset_type][$md5];
        }
        if (isset($params['wrap']) && false !== strpos($params['wrap'], '%s')) {
            $content = str_replace('%s', $content, $params['wrap']);
        }
        if (isset($params['name'])) {
            $name = $params['name'];
            unset($params['name']);
        }
		// Skip any related content, that was specially cached or listed, but not need to be included here
		if (in_array($content_type, ['file']) && in_array($asset_type, ['js','css'])) {
			// This line needed to strip query string from file name like this:
			// /templates/user/css/style.css?1416914173 -> templates/user/css/style.css
			if (pathinfo(parse_url($content, PHP_URL_PATH), PATHINFO_EXTENSION) != $asset_type) {
				$content = '';
			}
		} elseif (in_array($content_type, ['url']) && in_array($asset_type, ['js','css'])) {
			// This line needed to strip query string from file name like this:
			// /templates/user/css/style.css?1416914173 -> templates/user/css/style.css
            $parsed_url = parse_url($content);
			if ( ! strlen($parsed_url['host']) && strlen($parsed_url['path']) && pathinfo($parsed_url['path'], PATHINFO_EXTENSION) != $asset_type) {
				$content = '';
			}
		}
        return $this->content[$asset_type][$md5] = [
            'content_type' => $content_type,
            'content' => $content,
            'name' => $name,
            'version' => $name ?: 'master',
            'params' => $params,
        ];
    }

    /**
     * Clean content for given asset type.
     * @param mixed $asset_type
     */
    public function clean_content($asset_type)
    {
        if ( ! $this->ADD_IS_DIRECT_OUT) {
            $this->already_required[$asset_type] = [];
        }
        $this->content[$asset_type] = [];
        $this->_assets_added[$asset_type] = [];
        $this->_bundles_added = [];
        return [];
    }

    /**
     * Shortcut.
     * @param mixed $content
     * @param mixed $content_type_hint
     * @param mixed $params
     */
    public function add_js($content, $content_type_hint = 'auto', $params = [])
    {
        return $this->add($content, 'js', $content_type_hint, $params);
    }

    /**
     * Shortcut.
     * @param mixed $content
     * @param mixed $content_type_hint
     * @param mixed $params
     */
    public function add_css($content, $content_type_hint = 'auto', $params = [])
    {
        return $this->add($content, 'css', $content_type_hint, $params);
    }

    /**
     * Shortcut.
     * @param mixed $content
     * @param mixed $content_type_hint
     * @param mixed $params
     */
    public function add_sass($content, $content_type_hint = 'auto', $params = [])
    {
        return $this->add($content, 'sass', $content_type_hint, $params);
    }

    /**
     * Shortcut.
     * @param mixed $content
     * @param mixed $content_type_hint
     * @param mixed $params
     */
    public function add_less($content, $content_type_hint = 'auto', $params = [])
    {
        return $this->add($content, 'less', $content_type_hint, $params);
    }

    /**
     * Shortcut.
     * @param mixed $content
     * @param mixed $content_type_hint
     * @param mixed $params
     */
    public function add_coffee($content, $content_type_hint = 'auto', $params = [])
    {
        return $this->add($content, 'coffee', $content_type_hint, $params);
    }

    /**
     * @param mixed $params
     */
    // TODO: check if really working
    public function get_sass_content($params = [])
    {
        if ( ! $this->ALLOW_CONTENT_SASS) {
            return [];
        }
        $out = [];
        $content = $this->get_content('sass');
        if (empty($content)) {
            return [];
        }
        require_php_lib('scss');
        if (class_exists('scssc')) {
            $scss = new scssc();
            foreach ((array) $content as $md5 => $v) {
                $v['content'] = $scss->compile($v['content']);
                $out[$md5] = $v;
            }
        }
        return $out;
    }

    /**
     * @param mixed $params
     */
    // TODO: check if really working
    public function get_less_content($params = [])
    {
        if ( ! $this->ALLOW_CONTENT_LESS) {
            return [];
        }
        $out = [];
        $content = $this->get_content('less');
        if (empty($content)) {
            return [];
        }
        require_php_lib('less');
        if (class_exists('lessc')) {
            $less = new lessc();
            foreach ((array) $content as $md5 => $v) {
                $v['content'] = $less->compile($v['content']);
                $out[$md5] = $v;
            }
        }
        return $out;
    }

    /**
     * @param mixed $params
     */
    // TODO: check if really working
    public function get_coffee_content($params = [])
    {
        if ( ! $this->ALLOW_CONTENT_COFFEE) {
            return [];
        }
        $out = [];
        $content = $this->get_content('coffee');
        if (empty($content)) {
            return [];
        }
        require_php_lib('coffeescript');
        foreach ((array) $content as $md5 => $v) {
            $v['content'] = \CoffeeScript\Compiler::compile($v['content'], ['header' => false]);
            $out[$md5] = $v;
        }
        return $out;
    }

    /**
     * @param mixed $out_type
     * @param mixed $params
     */
    public function _get_all_content_for_out($out_type, $params = [])
    {
        $is_ajax = main()->is_ajax();
        // Move down inlined content
        $all_content = $this->get_content($out_type);
        if ($out_type === 'css') {
            $this->ALLOW_CONTENT_SASS && $all_content = (array) $all_content + (array) $this->get_sass_content($params);
            $this->ALLOW_CONTENT_LESS && $all_content = (array) $all_content + (array) $this->get_less_content($params);
        } elseif ($out_type === 'js') {
            $this->ALLOW_CONTENT_COFFEE && $all_content = (array) $all_content + (array) $this->get_coffee_content($params);
        }
        $top = [];
        $bottom = [];
        $last = [];
        $names_to_md5 = [];
        $out_before = [];
        $out_after = [];
        foreach ((array) $all_content as $md5 => $v) {
            if ($v['name']) {
                $names_to_md5[$v['name']] = $md5;
            }
        }
        foreach ((array) $all_content as $md5 => $v) {
            if ($v['params']['out_before']) {
                $out_before[$md5] = $names_to_md5[$v['params']['out_before']];
            } elseif ($v['params']['out_after']) {
                $out_after[$md5] = $names_to_md5[$v['params']['out_after']];
            }
            $content_type = $v['content_type'];
            if ($is_ajax && $content_type !== 'inline') {
                continue;
            }
            if ($v['params']['is_last']) {
                $last[$md5] = $v;
            } elseif (in_array($content_type, ['inline'])) {
                $bottom[$md5] = $v;
            } else {
                $top[$md5] = $v;
            }
        }
        $data = $top + $bottom + $last;
        if ($out_before) {
            foreach ((array) $out_before as $self_md5 => $before_md5) {
                $pos = 0;
                $self_data = [$self_md5 => $data[$self_md5]];
                unset($data[$self_md5]);
                foreach ($data as $_md5 => $v) {
                    if ($_md5 === $before_md5) {
                        break;
                    }
                    $pos++;
                }
                if ($pos && $self_data) {
                    $data_before = array_slice($data, 0, $pos, $preserve_keys = true);
                    $data_after = array_slice($data, $pos, null, $preserve_keys = true);
                    $data = $data_before + $self_data + $data_after;
                }
            }
        }
        if ($out_after) {
            foreach ((array) $out_after as $self_md5 => $after_md5) {
                $pos = 0;
                $self_data = [$self_md5 => $data[$self_md5]];
                unset($data[$self_md5]);
                foreach ($data as $_md5 => $v) {
                    if ($_md5 === $after_md5) {
                        break;
                    }
                    $pos++;
                }
                if ($pos && $self_data) {
                    $data_before = array_slice($data, 0, $pos + 1, $preserve_keys = true);
                    $data_after = array_slice($data, $pos + 1, null, $preserve_keys = true);
                    $data = $data_before + $self_data + $data_after;
                }
            }
        }
        return $data;
    }

    /**
     * Main method to display overall content by out type (js, css, images, fonts).
     * Can be called from main template like this: {exec_last(assets,show_js)} {exec_last(assets,show_css)}.
     * @param mixed $out_type
     * @param mixed $params
     */
    public function show($out_type, $params = [])
    {
        if ( ! $out_type || ! in_array($out_type, $this->supported_out_types)) {
            throw new Exception('Assets: unsupported out content type: ' . $out_type);
            return null;
        }
        if ($out_type === 'js') {
            $this->init_js();
        } elseif ($out_type === 'css') {
            $this->init_css();
        }
        if ( ! is_array($params)) {
            $params = ! empty($params) ? [$params] : [];
        }
        // Assets from current module
        $module_assets_path = $this->find_asset_type_for_module($out_type, $_GET['object']);
        if ($module_assets_path) {
            $this->add_file($module_assets_path, $out_type);
        }
        if ($out_type === 'js' && $this->USE_REQUIRE_JS) {
            return $this->show_require_js($params);
        }
        if ($this->COMBINE) {
            $combined_file = $this->_get_combined_path($out_type);
            $md5_inside_combined = [];
            if (file_exists($combined_file)) {
                $combined_info = json_decode(file_get_contents($combined_file . '.info'), $as_array = true);
                $md5_inside_combined = explode(',', $combined_info['elements']);
                $md5_inside_combined = array_combine($md5_inside_combined, $md5_inside_combined);
            }
        }
        $bs_current_theme = common()->bs_current_theme();
        $media_path = $this->_get_media_path();
        $prepend = _class('core_events')->fire('assets.prepend');
        // Process previously added content, depending on its type
        $out = [];
        $to_combine = [];
        foreach ((array) $this->_get_all_content_for_out($out_type) as $md5 => $v) {
            if ( ! is_array($v)) {
                continue;
            }
            $_params = ((array) $v['params'] + (array) $params) ?? [];
            $content_type = $v['content_type'];
            $cached_path = '';
            $use_cache = $this->USE_CACHE && ! $_params['no_cache'] && ! $_params['config']['no_cache'];
            if ($use_cache && $content_type === 'inline') {
                if ( ! $this->CACHE_INLINE_ALLOW || ! $_params['config']['inline_cache'] || strlen($v['content']) < $this->CACHE_INLINE_MIN_SIZE) {
                    $use_cache = false;
                }
            }
            if ($use_cache) {
                if ($v['name'] === 'bootstrap-theme') {
                    $v['name'] .= '-' . $bs_current_theme;
                }
                $cached_path = $this->get_cache($out_type, $md5, $v);
                if ( ! $cached_path && ! $this->FORCE_LOCAL_STORAGE) {
                    $cached_path = $this->set_cache($out_type, $md5, $v, $_params);
                }
                if ($cached_path) {
                    $content_type = 'url';
                    $v['content'] = $media_path . substr($cached_path, strlen(PROJECT_PATH));
                }
            }
            $str = $v['content'];
            $before = $_params['config']['before'] ?? '';
            $after = $_params['config']['after'] ?? '';
            if ($_params['config']['class'] ?? '') {
                $_params['class'] = $_params['config']['class'];
            }
            $use_combine = $this->COMBINE && $use_cache && in_array($content_type, ['url', 'file']) && empty($before) && empty($after) && empty($_params['class']) && empty($_params['id']);
            if ($use_combine) {
                $to_combine[$md5] = [
                    'content' => $str,
                    'content_type' => $content_type,
                    'name' => $v['name'],
                ];
            }
            if (DEBUG_MODE) {
                $debug = [];
                foreach ((array) debug('assets_add') as $d) {
                    if ($d['md5'] === $md5) {
                        $debug = $d;
                        break;
                    }
                }
                $dname = $out_type . '_' . $md5;
                $trace_short = str_replace(['<', '>'], ['&lt;', '&gt;'], implode('; ', array_slice(explode(PHP_EOL, $debug['trace']), 2, 2, true)));
                $ctype = $debug['content_type'];
                if ($ctype === 'asset') {
                    $ctype .= ':' . $debug['content'];
                }
                $before = PHP_EOL . '<!-- asset start: ' . $dname . ' | ' . $ctype . ' | ' . $trace_short . ' -->' . PHP_EOL . $before;
                $after = $after . PHP_EOL . '<!-- asset end: ' . $dname . ' -->' . PHP_EOL;
                debug('assets_out[]', [
                    'out_type' => $out_type,
                    'name' => $v['name'],
                    'md5' => $md5,
                    'content_type' => $content_type,
                    'content' => $str,
                    'preview' => '',
                    'params' => $_params,
                    'cached' => $cached_path ? 1 : 0,
                    'combined' => (int) isset($to_combine[$md5]),
                    'trace' => $debug['trace'],
                ]);
            }
            $out[$md5] = $before . $this->html_out($out_type, $content_type, $str, $_params + ['asset_name' => $v['name']]) . $after;
        }
        if ($this->COMBINE && $to_combine) {
            $out = $this->_combine_content($out, $out_type, $to_combine, $combined_file, $md5_inside_combined);
        }
        $append = _class('core_events')->fire('assets.append', ['out' => &$out]);
        $this->clean_content($out_type);
        return implode(PHP_EOL, $prepend) . implode(PHP_EOL, $out) . implode(PHP_EOL, $append);
    }

    /**
     * @param mixed $out_type
     */
    public function _get_combined_path($out_type)
    {
        return $this->_cache_path($out_type, '', [
            'name' => 'combined',
            'version' => $this->_get_combined_version($out_type),
        ]);
    }

    /**
     * @param mixed $out_type
     * @param mixed $combined_file
     */
    public function _combine_content(array $out, $out_type, array $to_combine, $combined_file, array $md5_inside_combined)
    {
        if ( ! file_exists($combined_file)) {
            $divider = PHP_EOL;
            if ($out_type === 'js') {
                $divider = PHP_EOL . ';' . PHP_EOL;
            }
            $combined = [];
            $combined_names = [];
            foreach ($to_combine as $md5 => $info) {
                $content_type = $info['content_type'];
                $content = $info['content'];
                if ($content_type === 'url') {
                    $combined[$md5] = $this->_url_get_contents($content);
                } elseif ($content_type === 'file' && file_exists($content)) {
                    $combined[$md5] = file_get_contents($content);
                }
                if ($out_type === 'css' && in_array($content_type, ['url', 'inline'])) {
                    $combined[$md5] = $this->_css_urls_rewrite_and_save($combined[$md5], $content, $combined_file, $content_type);
                }
                if ($out_type === 'js' && $content_type === 'url') {
                    $this->_js_map_save($combined[$md5], $content, $combined_file);
                }
                $md5_inside_combined[$md5] = $md5;
                $combined_names[$info['name']] = $info['name'];
            }
            if ($combined) {
                $combined_md5 = array_keys($combined);
                $combined = implode($divider, $combined);
                file_put_contents($combined_file, $combined);
                $this->_write_cache_info($combined_file, '', $combined, ['elements' => implode(',', $combined_md5), 'names' => implode(',', $combined_names)]);
            }
        }
        foreach ($to_combine as $md5 => $info) {
            if (isset($md5_inside_combined[$md5])) {
                unset($out[$md5]);
            }
        }
        $before = '';
        $after = '';
        if (DEBUG_MODE) {
            $dname = 'combined';
            $trace = main()->trace_string();
            $trace_short = str_replace(['<', '>'], ['&lt;', '&gt;'], implode('; ', array_slice(explode(PHP_EOL, $trace), 2, 2, true)));
            $before = PHP_EOL . '<!-- asset start: ' . $dname . ' | ' . $out_type . ' | ' . $trace_short . ' -->' . PHP_EOL . $before;
            $after = $after . PHP_EOL . '<!-- asset end: ' . $dname . ' -->' . PHP_EOL;
            debug('assets_out[]', [
                'out_type' => $out_type,
                'name' => $dname,
                'md5' => '',
                'content_type' => 'file',
                'content' => $combined_file,
                'preview' => '',
                'params' => '',
                'cached' => '1',
                'combined' => '',
                'trace' => $trace,
            ]);
        }
        return [
            md5($combined) => $before . $this->html_out($out_type, 'file', $combined_file) . $after,
        ] + $out;
    }

    /**
     * Shortcut.
     * @param mixed $params
     */
    public function show_js($params = [])
    {
        return $this->show('js', $params);
    }

    /**
     * Shortcut.
     * @param mixed $params
     */
    public function show_css($params = [])
    {
        return $this->show('css', $params);
    }

    /**
     * @param mixed $out_type
     * @param mixed $md5
     * @param mixed $data
     */
    public function get_cache($out_type, $md5, $data = [])
    {
        $cache_path = $this->_cache_path($out_type, $md5, $data);
        if (file_exists($cache_path) && ! $this->_cache_expired($cache_path)) {
            return $cache_path;
        }
        return false;
    }

    /**
     * @param mixed $out_type
     * @param mixed $md5
     * @param mixed $data
     */
    public function set_cache($out_type, $md5, $data = [])
    {
        if ( ! $this->USE_CACHE) {
            return false;
        }
        $cache_path = $this->_cache_path($out_type, $md5, $data);
        $content = $data['content'];
        $content_type = $data['content_type'];
        $content_url = '';
        if ($content_type === 'url') {
            $content_url = $content;
            $content = $this->_url_get_contents($content_url);
        } elseif ($content_type === 'file') {
            $content = file_get_contents($content);
        }
        if ( ! strlen($content)) {
            return false;
        }
        // Content is same, no need to overwrite it
        $cache_existed = file_exists($cache_path);
        if ($cache_existed && file_get_contents($cache_path) === $content) {
            is_writable($cache_path) && touch($cache_path);
            return $cache_path;
        }
        file_put_contents($cache_path, $content);
        // Decode gzip content to not confuse browser and web server, as gzdecode($content) is PHP 5.4+ only
        // gzip file beginnning: \x1F\x8B
        if (bin2hex(substr($content, 0, 2)) === '1f8b') {
            ob_start();
            readgzfile($cache_path);
            $content = ob_get_clean();
            file_put_contents($cache_path, $content);
        }
        if ($out_type === 'css' && in_array($content_type, ['url', 'inline', 'file'])) {
            $content_before = $content;
            $content = $this->_css_urls_rewrite_and_save($content, $content_url, $cache_path, $content_type, $data['content']);
            if ($content_before !== $content) {
                file_put_contents($cache_path, $content);
            }
            unset($content_before);
        }
        if ($out_type === 'js' && $content_type === 'url') {
            $this->_js_map_save($content, $content_url, $cache_path);
        }
        // Content is same, no need to overwrite info about it
        // This is not a mistake to do this check again, after url rewrite content can still be same
        if ($cache_existed && file_get_contents($cache_path) === $content) {
            return $cache_path;
        }
        $this->_write_cache_info($cache_path, $content_url, $content);
        return $cache_path;
    }

    /**
     * Try to find and save JS map file. It is used for navigating minified files inside browser's developer tools.
     * @param mixed $content
     * @param mixed $content_url
     * @param mixed $cache_path
     */
    public function _js_map_save($content, $content_url, $cache_path)
    {
        $map_ext = '.map';
        $map_url = '';
        // Parse inline map url suggest, example: //# sourceMappingURL=lightbox.min.map
        if (preg_match('~#\s*sourceMappingURL=(?P<map_url>.+\.map)~ims', $content, $m)) {
            $map_url = trim($m['map_url']);
            if (strlen($map_url) && strpos($map_url, '/') === false) {
                $map_url = dirname($content_url) . '/' . $map_url;
            }
        }
        if ( ! $map_url) {
            return false;
        }
        $map_path = dirname($cache_path) . '/' . basename($map_url);
        if (file_exists($map_path)) {
            return true;
        }
        $map_content = $this->_url_get_contents($map_url);
        if ( ! strlen($map_content)) {
            return false;
        }
        if (file_exists($map_path) && file_get_contents($map_path) === $map_content) {
            is_writable($map_path) && touch($map_path);
            return true;
        }
        $this->_write_cache_info($map_path, $map_url, $map_content);
        return file_put_contents($map_path, $map_content);
    }

    /**
     * @param mixed $cache_path
     * @param mixed $url
     * @param mixed $content
     * @param mixed $extra
     */
    public function _write_cache_info($cache_path, $url, $content, $extra = [])
    {
        $data = [
            'url' => $url,
            'date' => date('Y-m-d H:i:s'),
            'md5' => md5($content),
        ];
        if ($extra) {
            foreach ($extra as $k => $v) {
                $data[$k] = $v;
            }
        }
        return file_put_contents($cache_path . '.info', json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * process and save CSS url() and @import.
     * @param mixed $content
     * @param mixed $content_url
     * @param mixed $cache_path
     * @param mixed $content_type
     * @param mixed $orig_content
     */
    public function _css_urls_rewrite_and_save($content, $content_url, $cache_path, $content_type = 'url', $orig_content = '')
    {
        $_this = $this;
        $self_func = __FUNCTION__;
        return preg_replace_callback('~url\([\'"\s]*(?P<url>[^\'"\)]+?)[\'"\s]*\)~ims', function ($m) use ($_this, $content_url, $cache_path, $content_type, $orig_content, $self_func) {
            $url = trim($m['url']);
            if (strpos($url, 'data:') === 0) {
                return $m[0];
            }
            $str = '';
            $save_path = '';
            $is_local_file = false;
            if ($content_type === 'file') {
                // examples: ../ ./
                if (substr($url, 0, 1) === '.') {
                    $is_local_file = true;
                // full url like http://test.dev/image.png
                } elseif (false === strpos($url, 'http://') && false === strpos($url, 'https://')) {
                    $is_local_file = true;
                // should not match: //domain.com/some_path
                } elseif (substr($url, 0, 1) === '/' && substr($url, 1, 1) !== '/') {
                    $is_local_file = true;
                }
            }
            if ($is_local_file) {
                $basename = basename($url);
                // Fix for local files and url params inside path
                if (false !== ($pos = strpos($basename, '?'))) {
                    $url = dirname($url) . '/' . substr($basename, 0, $pos);
                }
                // /templates/user/theme/default/image/smile_1.0_unactive.png
                if (substr($url, 0, 1) === '/') {
                    $try_path = PROJECT_PATH . ltrim($url, '/');
                } else {
                    $try_path = dirname($orig_content) . '/' . ltrim($url, '/');
                }
                $path = $_this->_get_absolute_path($try_path);
                $path = '/' . ltrim($path, '/');

                $save_path = $_this->_get_absolute_path(dirname($cache_path) . '/' . basename($path));
                $save_path = '/' . ltrim($save_path, '/');
                // path cleanup from *.*#someworlds
                $path = preg_replace('/([\/\w\d]+?\.[a-z]{1,4})#[\w\d\-]+$/is', '${1}', $path);
				// singleton for getting urls contents
				if (!file_exists($save_path) && file_exists($path)) {
					$str = file_get_contents($path);
				}
            } else {
                $orig_url = $url;
                if (substr($url, 0, 2) !== '//' && substr($url, 0, strlen('http')) !== 'http') {
                    $url = dirname($content_url) . '/' . $url;
                }
                $u = parse_url($url);
                $host = $u['host'];
                $path = $u['path'];
                $query = $u['query'];
                // example: //fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,400,700
                if ($host === 'fonts.googleapis.com') {
                    $ext = '.' . ($path === '/css' ? 'css' : trim($path, '/')); // example: /css
                    $path = preg_replace('~[^a-z0-9_-]~ims', '_', strtolower($query)) . $ext;
                }
                $save_path = $_this->_get_absolute_path(dirname($cache_path) . '/' . basename($path));
                $save_path = '/' . ltrim($save_path, '/');
                // singleton for getting urls contents
                if ( ! file_exists($save_path)) {
                    $url = $_this->_get_absolute_url($url) . ($query ? '?' . $query : '');
                    $str = $_this->_url_get_contents($url);
                }
            }
            if (strlen($str)) {
                $str = $_this->$self_func($str, $content_url, $cache_path, $content_type, $orig_content);
                file_put_contents($save_path, $str);
                $_this->_write_cache_info($save_path, $url, $str);
            }
            if ($_this->CACHE_IMAGES_USE_DATA_URI) {
                $ext = strtolower(pathinfo($save_path, PATHINFO_EXTENSION));
                if (in_array($ext, ['png', 'gif', 'jpg', 'jpeg'])) {
                    $max_size = $_this->CACHE_IMAGES_DATA_URI_MAX_SIZE;
                    $size = filesize($save_path);
                    if ($size <= $max_size) {
                        $data_type = 'image/' . ($ext == 'jpg' ? 'jpeg' : $ext);
                        return 'url(\'data:' . $data_type . ';base64,' . base64_encode(file_get_contents($save_path)) . '\')';
                    }
                }
            }
            return 'url(\'' . basename($save_path) . '\')';
        }, $content);
    }

    /**
     * @param mixed $url
     */
    public function _get_absolute_url($url)
    {
        $u = parse_url($url);
        if (substr($url, 0, 2) === '//') {
            $u['scheme'] = 'http';
        }
        $host = $u['host'];
        $path = $this->_get_absolute_path($u['path']);
        return strlen($host) && strlen($path) ? $u['scheme'] . '://' . $host . '/' . ltrim($path, '/') : null;
    }

    /**
     * @param mixed $path
     */
    public function _get_absolute_path($path)
    {
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' == $part) {
                continue;
            }
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return implode(DIRECTORY_SEPARATOR, $absolutes);
    }

    /**
     * @param mixed $out_type
     * @param mixed $md5
     * @param mixed $data
     */
    public function _cache_path($out_type, $md5, $data = [])
    {
        $cache_dir = $this->_cache_dir($out_type, $data['name'], $data['version']);
        $cache_name = $this->_cache_name($out_type, $md5, $data);
        return $cache_dir . ($cache_name ?: ($data['name'] ? $md5 . '_' . $data['name'] : '') ?: $md5) . '.' . $out_type;
    }

    /**
     * @param mixed $out_type
     * @param mixed $md5
     * @param mixed $data
     */
    public function _cache_name($out_type, $md5, $data = [])
    {
        $content = $data['content'];
        $content_type = $data['content_type'];
        $_name = '';
        if ($content_type === 'url') {
            $_name = pathinfo(parse_url($content, PHP_URL_PATH), PATHINFO_FILENAME);
            while (pathinfo($_name, PATHINFO_EXTENSION) === $out_type && $out_type) {
                $_name = pathinfo($_name, PATHINFO_FILENAME);
            }
        } elseif ($content_type === 'file') {
            $_name = pathinfo($content, PATHINFO_FILENAME);
        } elseif ($content_type === 'inline') {
            $_name = md5($content);
        }
        return $_name;
    }

    /**
     * @param mixed $out_type
     * @param mixed $asset_name
     * @param mixed $version
     */
    public function _cache_dir($out_type, $asset_name = '', $version = '')
    {
        if ( ! is_string($this->CACHE_DIR_TPL) && is_callable($this->CACHE_DIR_TPL)) {
            $func = $this->CACHE_DIR_TPL;
            $cache_dir = $func($out_type, $asset_name, $version, $this);
        } else {
            $main_type = $this->_override['main_type'] ?: MAIN_TYPE;
            $host = $this->_override['host'] ?: $_SERVER['HTTP_HOST'];

            ! isset($this->_cache_language) && $this->_cache_language = conf('language') ?: 'en';
            $lang = $this->_override['language'] ?: $this->_cache_language;

            ! isset($this->_cache_html5fw) && $this->_cache_html5fw = conf('css_framework') ?: 'bs3';
            $html5fw = $this->_override['html5fw'] ?: $this->_cache_html5fw;

            ! isset($this->_cache_date) && $this->_cache_date = explode('-', date('Y-m-d-H-i-s'));
            $date = $this->_override['date'] ?: $this->_cache_date;

            $replace = [
                '{site_path}' => SITE_PATH,
                '{app_path}' => APP_PATH,
                '{project_path}' => PROJECT_PATH,
                '{main_type}' => $main_type,
                '{host}' => $host,
                '{lang}' => $lang,
                '{asset_name}' => $asset_name,
                '{version}' => $version,
                '{out_type}' => $out_type,
                '{html5fw}' => $html5fw,
                '{year}' => $date[0],
                '{month}' => $date[1],
                '{day}' => $date[2],
                '{hour}' => $date[3],
                '{minute}' => $date[4],
                '{second}' => $date[5],
            ];
            $cache_dir = str_replace(['///', '//'], '/', str_replace(array_keys($replace), array_values($replace), $this->CACHE_DIR_TPL));
        }
        ! file_exists($cache_dir) && mkdir($cache_dir, 0755, true);
        return $cache_dir;
    }

    /**
     * @param mixed $out_type
     */
    public function _get_combined_version($out_type = '')
    {
        if ( ! is_string($this->COMBINED_VERSION_TPL) && is_callable($this->COMBINED_VERSION_TPL)) {
            $func = $this->COMBINED_VERSION_TPL;
            $version = $func($out_type, $this);
        } else {
            $main_type = $this->_override['main_type'] ?: MAIN_TYPE;
            $host = $this->_override['host'] ?: $_SERVER['HTTP_HOST'];

            ! isset($this->_cache_language) && $this->_cache_language = conf('language') ?: 'en';
            $lang = $this->_override['language'] ?: $this->_cache_language;

            ! isset($this->_cache_html5fw) && $this->_cache_html5fw = conf('css_framework') ?: 'bs3';
            $html5fw = $this->_override['html5fw'] ?: $this->_cache_html5fw;

            ! isset($this->_cache_date) && $this->_cache_date = explode('-', date('Y-m-d-H-i-s'));
            $date = $this->_override['date'] ?: $this->_cache_date;

            $replace = [
                '{site_path}' => SITE_PATH,
                '{app_path}' => APP_PATH,
                '{project_path}' => PROJECT_PATH,
                '{main_type}' => MAIN_TYPE,
                '{host}' => $host,
                '{lang}' => $lang,
                '{out_type}' => $out_type,
                '{html5fw}' => $html5fw,
                '{year}' => $date[0],
                '{month}' => $date[1],
                '{day}' => $date[2],
                '{hour}' => $date[3],
                '{minute}' => $date[4],
                '{second}' => $date[5],
            ];
            $version = str_replace(['///', '//'], '/', str_replace(array_keys($replace), array_values($replace), $this->COMBINED_VERSION_TPL));
        }
        return $version;
    }

    /**
     * @param mixed $cache_path
     */
    public function _cache_expired($cache_path)
    {
        return file_exists($cache_path) && filesize($cache_path) > 10 && filemtime($cache_path) <= (time() - $this->CACHE_TTL);
    }

    /**
     * Add modification time to url, if this is local file, no matter cached or not, we just need to be able to get its mtime.
     * @param mixed $str
     */
    public function _cached_url_get_mtime($str = '')
    {
        $url_param = 'yfmt';
        if ( ! $this->CACHE_OUT_ADD_MTIME || ! strlen($str) || false !== strpos($str, $url_param . '=')) {
            return false;
        }
        $mtime = '';
        $file = '';
        $has_question_sign = (false !== strpos($str, '?'));
        $media_path = $this->_get_media_path();
        $media_path_len = strlen($media_path);
        // short url paths like /templates/user/cache/..., but not //domain.com/
        if (substr($str, 0, 1) === '/' && substr($str, 1, 1) !== '/') {
            if (substr($str, 0, strlen(PROJECT_PATH)) === PROJECT_PATH) {
                $file = $str;
            } else {
                $file = PROJECT_PATH . ltrim($str, '/');
            }
        } elseif (substr($str, 0, $media_path_len) === $media_path) {
            $file = PROJECT_PATH . ltrim(substr($str, $media_path_len), '/');
        }
        // Avoid different url params when checking local file path
        if ($file) {
            $file = parse_url($file, PHP_URL_PATH);
        }
        if ($file && file_exists($file)) {
            $mtime = filemtime($file);
        }
        return $mtime ? ($has_question_sign ? '&' : '?') . urlencode($url_param) . '=' . $mtime : '';
    }

    /**
     * Generate html output for desired asset out type and content type.
     * @param mixed $out_type
     * @param mixed $content_type
     * @param mixed $str
     * @param mixed $params
     */
    public function html_out($out_type, $content_type, $str, $params = [])
    {
        if ( ! $out_type || ! $content_type || ! strlen($str)) {
            return false;
        }
        $func = __FUNCTION__;
        $out = '';
        $media_path = $this->_get_media_path();
        // try to find web path for file and show it as url
        if ($content_type === 'file') {
            if (substr($str, 0, strlen(PROJECT_PATH)) === PROJECT_PATH) {
                $url = $media_path . substr($str, strlen(PROJECT_PATH));
                return $this->$func($out_type, 'url', $url, $params);
            }
        }
        if ($content_type === 'url' && $this->SHORTEN_LOCAL_URL && MEDIA_PATH == WEB_PATH) {
            $slen = strlen($media_path);
            if (substr($str, 0, $slen) === $media_path) {
                $str = '/' . substr($str, $slen);
            }
        }
        if ($this->OUT_ADD_ASSET_NAME && $params['asset_name'] && ! isset($params['id']) && in_array($content_type, ['inline', 'file'])) {
            $params['data-asset'] = 'asset_' . $out_type . '_' . $content_type . '_' . $params['asset_name'];
        }
        if ($out_type === 'js') {
            $params['type'] = 'text/javascript';
            if ($content_type === 'file') {
                $str = file_get_contents($str);
            }
            if ($this->INLINE_ASSETS_USE_DATA_URI && $content_type === 'inline') {
                $content_type = 'url';
                $params['src'] = 'data:' . $params['type'] . ';base64,' . base64_encode($this->_strip_js_input($str));
            } elseif ($this->FILE_ASSETS_USE_DATA_URI && strlen($str) <= $this->FILE_ASSETS_DATA_URI_MAX_SIZE) {
                $content_type = 'url';
                $params['src'] = 'data:' . $params['type'] . ';base64,' . base64_encode($str);
            }
            if ($content_type === 'url') {
                if ( ! isset($params['src'])) {
                    $params['src'] = $str . $this->_cached_url_get_mtime($str);
                }
                $out = '<script' . _attrs($params, ['src', 'type', 'class', 'id']) . '></script>';
            } elseif ($content_type === 'file') {
                if( @$params[ 'raw' ] ) {
                    $out = $str;
                } else {
                    $out = '<script' . _attrs($params, ['type', 'class', 'id']) . '>' . PHP_EOL . $str . PHP_EOL . '</script>';
                }
            } elseif ($content_type === 'inline') {
                if( @$params[ 'raw' ] ) {
                    $out = $str;
                } else {
                    $str = $this->_strip_js_input($str);
                    $out = '<script' . _attrs($params, ['type', 'class', 'id']) . '>' . PHP_EOL . $str . PHP_EOL . '</script>';
                }
            }
        } elseif ($out_type === 'css') {
            $params['type'] = 'text/css';
            if ($content_type === 'file') {
                $str = file_get_contents($str);
            }
            if ($this->INLINE_ASSETS_USE_DATA_URI && $content_type === 'inline') {
                $content_type = 'url';
                $params['href'] = 'data:' . $params['type'] . ';base64,' . base64_encode($this->_strip_css_input($str));
            } elseif ($this->FILE_ASSETS_USE_DATA_URI && strlen($str) <= $this->FILE_ASSETS_DATA_URI_MAX_SIZE) {
                $content_type = 'url';
                $params['href'] = 'data:' . $params['type'] . ';base64,' . base64_encode($str);
            }
            if ($content_type === 'url') {
                $params['rel'] = 'stylesheet';
                if ( ! isset($params['href'])) {
                    $params['href'] = $str . $this->_cached_url_get_mtime($str);
                }
                $out = '<link' . _attrs($params, ['href', 'rel', 'class', 'id']) . ' />';
            } elseif ($content_type === 'file') {
                $out = '<style' . _attrs($params, ['type', 'class', 'id']) . '>' . PHP_EOL . $str . PHP_EOL . '</style>';
            } elseif ($content_type === 'inline') {
                $str = $this->_strip_css_input($str);
                $out = '<style' . _attrs($params, ['type', 'class', 'id']) . '>' . PHP_EOL . $str . PHP_EOL . '</style>';
            }
        }
        return $out;
    }

    /**
     * Auto-detection on content type.
     * @param mixed $asset_type
     * @param mixed $content
     */
    public function detect_content_type($asset_type, $content = '')
    {
        $content = trim($content);
        $type = false;
        if (isset($this->assets[$content])) {
            $type = 'asset';
        } elseif ($asset_type === 'js') {
            if (preg_match('~^(http://|https://|//)[a-z0-9]+~ims', $content) && ! preg_match('~\s+~ims', $content)) {
                $type = 'url';
            } elseif (preg_match('~^/[a-z0-9\./_-]+\.js$~ims', $content) && file_exists($content)) {
                $type = 'file';
            } else {
                $type = 'inline';
            }
        } elseif ($asset_type === 'css') {
            if (preg_match('~^(http://|https://|//)[a-z0-9]+~ims', $content) && ! preg_match('~\s+~ims', $content)) {
                $type = 'url';
            } elseif (preg_match('~^/[a-z0-9\./_-]+\.css$~ims', $content) && file_exists($content)) {
                $type = 'file';
            } else {
                $type = 'inline';
            }
            // Support for other composite data formats like sass, less, coffee
        } elseif ( ! in_array($asset_type, ['js', 'css'])) {
            $type = 'inline';
        }
        return $type;
    }

    /**
     * Cleanup for CSS strings.
     * @param mixed $str
     */
    public function _strip_css_input($str)
    {
        // Extracting url from <link rel="stylesheet" href="path.to/style.css">
        $str = preg_replace_callback('~<link[\s]+[^>]*href=["\']([^"\']+?)["\'][^>]*>~ims', function ($m) {
            return $m[1];
        }, $str);

        for ($i = 0; $i < 10; $i++) {
            if (strpos($str, 'style') === false) {
                break;
            }
            $str = preg_replace('~^<style[^>]*?>~ims', '', $str);
            $str = preg_replace('~</style>$~ims', '', $str);
        }
        return $str;
    }

    /**
     * Cleanup for JS strings.
     * @param mixed $str
     */
    public function _strip_js_input($str)
    {
        // Extracting url from <script src="path.to/scripts.js"></script>
        $str = preg_replace_callback('~<script[\s]+[^>]*src=["\']([^"\']+?)["\'][^>]*>~ims', function ($m) {
            return $m[1];
        }, $str);

        for ($i = 0; $i < 10; $i++) {
            if (strpos($str, 'script') === false) {
                break;
            }
            $str = preg_replace('~^<script[^>]*?>~ims', '', $str);
            $str = preg_replace('~</script>$~ims', '', $str);
        }
        return $str;
    }

    /**
     * Shortcut for filters_add with js asset.
     * @param mixed $callback
     * @param mixed $params
     */
    public function filters_add_js($callback, $params = [])
    {
        return $this->filters_add('js', $callback, $params);
    }

    /**
     * Shortcut for filters_add with css asset.
     * @param mixed $callback
     * @param mixed $params
     */
    public function filters_add_css($callback, $params = [])
    {
        return $this->filters_add('css', $callback, $params);
    }

    /**
     * Add filters to processing chain, both custom and built-in supported.
     * @param mixed $asset_type
     * @param mixed $callback
     * @param mixed $params
     */
    public function filters_add($asset_type, $callback, $params = [])
    {
        if ( ! $asset_type) {
            throw new Exception('Assets: ' . __FUNCTION__ . ' missing asset_type');
            return $this;
        }
        if (is_array($callback)) {
            $func = __FUNCTION__;
            foreach ($callback as $k => $v) {
                $this->$func($asset_type, $v, $params);
            }
            return $this;
        }
        $this->filters[$asset_type][] = [
            'callback' => $callback,
            'params' => $params,
        ];
        return $this;
    }

    /**
     * Get list of filters, added to procesing chain, both custom and built-in.
     * @param mixed $asset_type
     */
    public function filters_get_added($asset_type)
    {
        if ( ! $asset_type) {
            return false;
        }
        return $this->filters[$asset_type];
    }

    /**
     * Clean list of current filters to apply automatically to output.
     * @param mixed $asset_type
     */
    public function filters_clean($asset_type = '')
    {
        if ($asset_type) {
            $this->filters[$asset_type] = [];
        } else {
            $this->filters = [];
        }
    }

    /**
     * Apply filters from names array to input string.
     * @param mixed $in
     * @param mixed $filters
     * @param mixed $params
     */
    public function filters_process_input($in, $filters = [], $params = [])
    {
        if (is_array($in)) {
            $out = [];
            $func = __FUNCTION__;
            foreach ($in as $k => $v) {
                $out[$k] = $this->$func($v, $filters, $params);
            }
            return $out;
        }
        $this->_autoload_libs();

        if ( ! is_array($filters)) {
            $filters = [$filters];
        }
        $out = $in;
        $avail_filters = $this->filters_get_avail();
        foreach ($filters as $filter) {
            $_params = [];
            if (is_array($filter)) {
                $_params = $filter['params'];
                $filter = $filter['callback'];
            }
            if ( ! $filter) {
                continue;
            }
            if ( ! is_string($filter) && is_callable($filter)) {
                $out = $filter($out, $params, $this);
            } elseif (is_string($filter) && isset($avail_filters[$filter])) {
                $out = _class('assets_filter_' . $filter, 'classes/assets/')->apply($out, $params);
            }
        }
        return $out;
    }

    /**
     * Shortcut for filters_content_process with js asset.
     * @param mixed $params
     */
    public function filters_process_js($params = [])
    {
        return $this->filters_process_added('js', $params);
    }

    /**
     * Shortcut for filters_content_process with css asset.
     * @param mixed $params
     */
    public function filters_process_css($params = [])
    {
        return $this->filters_process_added('css', $params);
    }

    /**
     * Apply added filters to gathered content of the given asset type.
     * @param mixed $asset_type
     * @param mixed $params
     */
    public function filters_process_added($asset_type, $params = [])
    {
        if ( ! $asset_type) {
            return false;
        }
        $filters = $this->filters_get_added($asset_type);
        if ( ! $filters) {
            return $this;
        }
        $content = $this->get_content($asset_type);
        if ( ! $content) {
            return $this;
        }
        foreach ($content as $md5 => $info) {
            // TODO: support for other content types
            if ($info['content_type'] !== 'inline') {
                continue;
            }
            $_content = $info['content'];
            $processed = $this->filters_process_input($_content, $filters, $params);
            if ($_content !== $processed) {
                $this->content[$asset_type][$md5]['content'] = $processed;
            }
        }
        return $this;
    }

    /**
     * Versions idea from  https://getcomposer.org/doc/01-basic-usage.md#package-versions
     * In the previous example we were requiring version 1.0.* of monolog. This means any version in the 1.0 development branch. It would match 1.0.0, 1.0.2 or 1.0.20.
     * Version constraints can be specified in a few different ways.
     * Exact version	1.0.2	You can specify the exact version of a package.
     * Range			   >=1.0 >=1.0,<2.0 >=1.0,<1.1 | >=1.2
     *		By using comparison operators you can specify ranges of valid versions. Valid operators are >, >=, <, <=, !=.
     *		You can define multiple ranges. Ranges separated by a comma (,) will be treated as a logical AND. A pipe (|) will be treated as a logical OR. AND has higher precedence than OR.
     * Wildcard		 1.0.* You can specify a pattern with a * wildcard. 1.0.* is the equivalent of >=1.0,<1.1.
     * Tilde Operator   ~1.2 Very useful for projects that follow semantic versioning. ~1.2 is equivalent to >=1.2,<2.0. For more details, read the next section below.
     * @param mixed $version
     * @param mixed $avail_versions
     */
    public function find_version_best_match($version = '', $avail_versions = [])
    {
        if (empty($avail_versions)) {
            return null;
        }
        if ( ! $version) {
            return current(array_slice($avail_versions, -1, 1, true));
        }
        // TODO: comparing versions and return best match
        //		require_php_lib('php_semver')
        return $version;
    }

    /**
     * @param mixed $params
     */
    public function show_require_js($params = [])
    {
        $out_type = 'js';
        $out = [];
        foreach ((array) $this->_get_all_content_for_out($out_type) as $md5 => $v) {
            if ( ! is_array($v)) {
                continue;
            }
            $out[$md5] = $this->html_out($out_type, $v['content_type'], $v['content'], (array) $v['params'] + (array) $params);
        }
        $this->clean_content($out_type);
        $out = '
<script src="//cdnjs.cloudflare.com/ajax/libs/require.js/2.1.15/require.js" type="text/javascript"></script>
<script type="text/javascript">
requirejs.config({ baseUrl: "/templates/"' . MAIN_TYPE . '"/cache/" });
define("jquery", [], function() { });
requirejs( [ "module1", "module2" ], function( angular ) {
	console.log( "modules load" );
});
</script>
				'/*. PHP_EOL. implode(PHP_EOL, $out)*/;
        var_dump($out);
        return $out;
    }
}
