<?php

/**
 * Core API.
 */
class yf_core_api
{
    public $section_paths = [
        'core' => 'classes/',
        'user' => 'modules/',
        'admin' => 'admin_modules/',
    ];
    /** @security Project code needed to be defended from easy traversing */
    public $SOURCE_ONLY_FRAMEWORK = false;
    public $_cache = [];

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
     * @param mixed $name
     * @param mixed $section
     * @param mixed $force_path
     */
    public function get_class_instance($name, $section, $force_path = '')
    {
        $path = $this->section_paths[$section];
        if ($force_path) {
            $path = $force_path;
        }
        return _class($name, $path);
    }

    /**
     * This method will search and call all found hook methods from active modules.
     * @example: call_hooks('settings', $params)
     * @param mixed $hook_name
     * @param mixed $section
     */
    public function call_hooks($hook_name, &$params = [], $section = 'all')
    {
        $data = [];
        foreach ((array) $this->get_hooks($hook_name) as $module => $methods) {
            foreach ((array) $methods as $method) {
                $obj = $this->get_class_instance($module, $section);
                $data[$module . '__' . $method] = $obj->$method($params);
            }
        }
        return $data;
    }

    /**
     * @param mixed $hook_name
     * @param mixed $section
     */
    public function get_hooks($hook_name, $section = 'all')
    {
        $hooks = [];
        foreach ((array) $this->get_all_hooks($section) as $module => $_hooks) {
            foreach ((array) $_hooks as $name => $method_name) {
                if ($name == $hook_name) {
                    $hooks[$module][$name] = $method_name;
                }
            }
        }
        return $hooks;
    }

    /**
     * @param mixed $section
     */
    public function get_available_hooks($section = 'all')
    {
        $avail_hooks = [];
        foreach ((array) $this->get_all_hooks($section) as $module => $_hooks) {
            foreach ((array) $_hooks as $name => $method_name) {
                $avail_hooks[$name][$module] = $method_name;
            }
        }
        return $avail_hooks;
    }

    /**
     * @param mixed $section
     * @param mixed $hooks_prefix
     */
    public function get_all_hooks($section = 'all', $hooks_prefix = '_hook_')
    {
        $hooks_pl = strlen($hooks_prefix);
        $hooks = [];
        foreach ((array) $this->get_private_methods($section) as $module => $methods) {
            foreach ((array) $methods as $method) {
                if (substr($method, 0, $hooks_pl) != $hooks_prefix) {
                    continue;
                }
                $hooks[$module][substr($method, $hooks_pl)] = $method;
            }
            if (is_array($hooks[$module])) {
                ksort($hooks[$module]);
            }
        }
        if (is_array($hooks)) {
            ksort($hooks);
        }
        return $hooks;
    }

    /**
     * @param mixed $section
     * @param mixed $prefix
     */
    public function get_widgets($section = 'all', $prefix = 'widget__')
    {
        $prefix_len = strlen($prefix);
        $data = [];
        foreach ((array) $this->get_all_hooks($section) as $module => $_hooks) {
            foreach ((array) $_hooks as $name => $method_name) {
                if (substr($name, 0, $prefix_len) != $prefix) {
                    continue;
                }
                $data[$module][$name] = $method_name;
            }
        }
        return $data;
    }

    /**
     * @param mixed $section
     */
    public function get_callbacks($section = 'all')
    {
        return $this->get_all_hooks($section, '_callback_');
    }

    /**
     * @param mixed $section
     */
    public function get_events($section = 'all')
    {
        return $this->get_all_hooks($section, '_event_');
    }

    /**
     * @param mixed $section
     */
    public function get_private_methods($section = 'all')
    {
        $data = [];
        foreach ((array) $this->get_methods($section) as $module => $methods) {
            foreach ((array) $methods as $method) {
                if ($method[0] == '_') {
                    $data[$module][$method] = $method;
                }
            }
        }
        return $data;
    }

    /**
     * @param mixed $section
     */
    public function get_public_methods($section = 'all')
    {
        $data = [];
        foreach ((array) $this->get_methods($section) as $module => $methods) {
            foreach ((array) $methods as $method) {
                if ($method[0] != '_') {
                    $data[$module][$method] = $method;
                }
            }
        }
        return $data;
    }

    /**
     * @param mixed $section
     */
    public function get_methods($section = 'all')
    {
        $methods = [];
        foreach ((array) $this->get_classes($section) as $_section => $modules) {
            foreach ((array) $modules as $module) {
                $obj = $this->get_class_instance($module, $_section);
                foreach ((array) get_class_methods($obj) as $method) {
                    $methods[$module][$method] = $method;
                }
            }
        }
        foreach ((array) $methods as $module => $_methods) {
            ksort($methods[$module]);
        }
        return $methods;
    }

    /**
     * @param mixed $section
     */
    public function get_properties($section = 'all')
    {
        $props = [];
        foreach ((array) $this->get_classes($section) as $_section => $modules) {
            foreach ((array) $modules as $module) {
                $obj = $this->get_class_instance($module, $_section);
                foreach ((array) get_object_vars($obj) as $key => $val) {
                    $props[$module][$key] = $val;
                }
            }
        }
        foreach ((array) $props as $module => $_props) {
            ksort($props[$module]);
        }
        return $props;
    }

    /**
     * @param mixed $section
     */
    public function get_classes($section = 'all')
    {
        if ( ! in_array($section, ['all', 'user', 'admin', 'core'])) {
            $section = 'all';
        }
        $modules = [];
        if (in_array($section, ['all', 'core'])) {
            $modules['core'] = $this->get_classes_by_params(['folder' => $this->section_paths['core']]);
        }
        if (in_array($section, ['all', 'user'])) {
            $modules['user'] = $this->get_classes_by_params(['folder' => $this->section_paths['user']]);
        }
        if (in_array($section, ['all', 'admin'])) {
            $modules['admin'] = $this->get_classes_by_params(['folder' => $this->section_paths['admin']]);
        }
        return $modules;
    }

    /**
     * @param mixed $section
     */
    public function get_submodules($section = 'all')
    {
        $data = [];
        foreach ($this->section_paths as $_section => $folder) {
            if ($section != 'all' && $section != $_section) {
                continue;
            }
            // Currently I do not want to analyze submodules from core
            if ($_section == 'core') {
                continue;
            }
            $_data = [];
            $paths = [];
            $this->get_classes_by_params(['folder' => $folder . '*/'], $paths);
            foreach ((array) $paths as $name => $_paths) {
                if ( ! is_array($_paths)) {
                    continue;
                }
                $path = current($_paths);
                $subdir = basename(dirname($path));
                $_data[$subdir][$name] = $name;
            }
            if (is_array($_data)) {
                ksort($_data);
            }
            $data[$_section] = $_data;
        }
        return $data;
    }

    /**
     * @param mixed $module
     * @param mixed $submodule
     * @param mixed $section
     */
    public function get_submodule_methods($module, $submodule, $section = 'all')
    {
        $obj = $this->get_class_instance($submodule, $section, $this->section_paths[$section] . $module . '/');
        $methods = $this->get_methods_sources($obj);
        return $methods;
    }


    public function get_functions()
    {
        $all = get_defined_functions();
        $funcs = array_combine($all['user'], $all['user']);
        is_array($funcs) && ksort($funcs);
        return $funcs;
    }

    /**
     * @param mixed $name
     */
    public function get_function_source($name)
    {
        $r = new ReflectionFunction($name);
        $info = [
            'name' => $r->getName(),
            'file' => $r->getFileName(),
            'line_start' => $r->getStartLine(),
            'line_end' => $r->getEndline(),
            'params' => $r->getParameters(),
            'comment' => $r->getDocComment(),
        ];
        $info['source'] = $this->get_file_slice($info['file'], $info['line_start'], $info['line_end']);
        return $info;
    }

    /**
     * @param mixed $module
     * @param mixed $method
     * @param mixed $section
     */
    public function get_method_source($module, $method, $section = 'all')
    {
        $force_path = '';
        if ( ! is_object($module)) {
            if (false !== strpos($module, '/')) {
                list($subfolder, $module) = explode('/', $module);
                if ($subfolder) {
                    $force_path = 'classes/' . $subfolder . '/';
                }
            }
            $cls = main()->load_class_file($module, $force_path);
        } else {
            $cls = get_class($module);
        }
        $methods = $this->_cache[__FUNCTION__][$cls] ?? null;
        if ($methods === null) {
            $methods = $this->get_methods_sources($cls);
            $this->_cache[__FUNCTION__][$cls] = $methods;
        }
        return $methods[$method] ?? '';
    }

    /**
     * Examples: get_gihub_link('my_array_merge'), get_gihub_link('core_css.show_css').
     * @param mixed $input
     * @param mixed $section
     */
    public function get_github_link($input, $section = 'all')
    {
        $is_module = false;
        $is_func = false;
        if (is_array($input)) {
            if ($input['is_module']) {
                $input['is_module'] = str_replace('-', '.', $input['is_module']);
                list($module, $method) = explode('.', $input['is_module']);
                if ( ! $module || ! $method) {
                    return '';
                }
                $is_module = true;
            } elseif ($input['is_func'] && $input['name'] && function_exists($input['name'])) {
                $is_func = $input['name'];
            }
        } elseif (false !== strpos($input, '.')) {
            $input = str_replace('-', '.', $input);
            list($module, $method) = explode('.', $input);
            if ( ! $module || ! $method) {
                return '';
            }
            $is_module = true;
        } elseif (is_string($input) && function_exists($input)) {
            $is_func = $input;
        }
        if ($is_module) {
            $info = $this->get_method_source($module, $method, $section);
        } elseif ($is_func) {
            $info = $this->get_function_source($is_func);
        }
        return $this->_github_link_btn($info);
    }

    /**
     * @param mixed $info
     */
    public function _github_link_btn($info = [])
    {
        if ( ! $info) {
            return false;
        }
        $gh_url = 'https://github.com/yfix/yf/tree/master/' . ltrim(substr(realpath($info['file']), strlen(realpath(YF_PATH))), '/') . '#L' . $info['line_start'];
        return '<a target="_blank" class="btn btn-primary btn-xs" href="' . $gh_url . '"><i class="icon icon-github fa fa-github"></i> Github</a>';
    }

    /**
     * @param mixed $name
     */
    public function get_item_tests($name)
    {
        $out = $this->get_module_tests($name);
        if ( ! $out) {
            $out = $this->get_function_tests($name);
        }
        return $out;
    }

    /**
     * @param mixed $module
     */
    public function get_module_tests($module)
    {
        $tests_dir = YF_PATH . '.dev/tests/';
        $path = $tests_dir . 'class_' . $module . '.Test.php';
        if (file_exists($path)) {
            return file_get_contents($path);
        }
        return false;
    }

    /**
     * @param mixed $name
     */
    public function get_function_tests($name)
    {
        $tests_dir = YF_PATH . '.dev/tests/';
        $path = $tests_dir . 'func_' . $name . '.Test.php';
        if (file_exists($path)) {
            return file_get_contents($path);
        }
        $path = $tests_dir . 'func_' . ltrim($name, '_') . '.Test.php';
        if (file_exists($path)) {
            return file_get_contents($path);
        }

        return false;
    }

    /**
     * @param mixed $name
     */
    public function get_item_docs($name)
    {
        $out = $this->get_module_docs($name);
        if ( ! $out) {
            $out = $this->get_method_docs($name);
        }
        if ( ! $out) {
            $out = $this->get_function_docs($name);
        }
        return $out;
    }

    /**
     * @param mixed $name
     */
    public function get_module_docs($name)
    {
        $replace = [];
        $docs_dir = YF_PATH . '.dev/docs/en/';
        $f = $docs_dir . $name . '.stpl';
        if (file_exists($f)) {
            return '<section class="page-contents">' . tpl()->parse_string(file_get_contents($f), $replace, 'doc_' . $name) . '</section>';
        }
        return false;
    }

    /**
     * @param mixed $name
     * @param mixed $method
     */
    public function get_method_docs($name, $method = '')
    {
        $replace = [];
        $docs_dir = YF_PATH . '.dev/docs/en/';
        if (false !== strpos($name, '.')) {
            list($name, $method) = explode('.', $name);
        }
        $f = $docs_dir . $name . '/' . $method . '.stpl';
        if (file_exists($f)) {
            return '<section class="page-contents">' . tpl()->parse_string(file_get_contents($f), $replace, 'doc_' . $name . '.' . $method) . '</section>';
        }
        return false;
    }

    /**
     * @param mixed $name
     */
    public function get_function_docs($name)
    {
        $replace = [];
        $docs_dir = YF_PATH . '.dev/docs/en/';
        $f = $docs_dir . $name . '.stpl';
        if (file_exists($f)) {
            return '<section class="page-contents">' . tpl()->parse_string(file_get_contents($f), $replace, 'doc_' . $name) . '</section>';
        }
        return false;
    }


    public function get_sites()
    {
        return main()->get_data('sites');
    }


    public function get_site_info()
    {
        $sites = $this->get_sites();
        return $sites[main()->SITE_ID];
    }


    public function get_servers()
    {
        return main()->get_data('servers');
    }


    public function get_server_info()
    {
        $servers = $this->get_servers();
        return $servers[main()->SERVER_ID];
    }


    public function get_user_roles()
    {
        return main()->get_data('user_roles');
    }


    public function get_user_groups()
    {
        return main()->get_data('user_groups');
    }


    public function get_admin_roles()
    {
        return main()->get_data('admin_roles');
    }


    public function get_admin_groups()
    {
        return main()->get_data('admin_groups');
    }


    public function get_templates()
    {
        $folder = 'templates/user/';
        $folder = 'templates/admin/';
        // TODO
    }


    public function get_tpl_themes()
    {
        $folder = 'templates/';
        // TODO
    }

    /**
     * @param mixed $name
     * @param mixed $section
     */
    public function get_template_source($name, $section = 'all')
    {
        // TODO
    }


    public function get_langs()
    {
        return main()->get_data('locale_langs');
    }


    public function get_translations()
    {
        $folder = 'share/langs/';
        // TODO
    }


    public function get_assets()
    {
        // TODO: other folders, plugins
        $folder = 'assets/';
        return $this->get_classes_by_params(['folder' => $folder, 'suffix' => '.php']);
    }


    public function get_services()
    {
        // TODO: other folders, plugins
        $folder = 'services/';
        return $this->get_classes_by_params(['folder' => $folder, 'suffix' => '.php']);
    }


    public function get_assets_filters()
    {
        $folder = 'classes/assets/';
        return $this->get_classes_by_params(['folder' => $folder, 'suffix' => '.class.php']);
    }


    public function get_event_listeners()
    {
        $folder = 'share/events/';
        return $this->get_classes_by_params(['folder' => $folder, 'suffix' => '.php']);
    }


    public function get_cron_jobs()
    {
        $folder = 'share/cron_jobs/';
        return $this->get_classes_by_params(['folder' => $folder, 'suffix' => '.php']);
    }


    public function get_fast_init()
    {
        $folder = 'share/fast_init/';
        return $this->get_classes_by_params(['folder' => $folder, 'suffix' => '.php', 'prefix' => 'func__fast_']);
    }


    public function get_data_handlers()
    {
        $folder = 'share/data_handlers/';
        return $this->get_classes_by_params(['folder' => $folder, 'suffix' => '.php']);
    }


    public function get_tables_sql_php()
    {
        $folder = 'share/db/sql_php/';
        return $this->get_classes_by_params(['folder' => $folder, 'suffix' => '.sql_php.php']);
    }


    public function get_migrations()
    {
        $folder = 'share/db/migrations/';
        return $this->get_classes_by_params(['folder' => $folder]);
    }


    public function get_models()
    {
        $folder = 'share/models/';
        return $this->get_classes_by_params(['folder' => $folder]);
    }


    public function get_plugins()
    {
        $folder = '';
        $suffix = '/';
        $libs = [];
        foreach ($this->get_globs($folder, $suffix) as $gname => $glob) {
            if (false === strpos($gname, '_plugins')) {
                continue;
            }
            if (substr($glob, -4) == '*/*/') {
                $glob = substr($glob, 0, -2);
            }
            foreach (glob($glob) as $path) {
                if ( ! is_dir($path)) {
                    continue;
                }
                $name = basename($path);
                $libs[$name] = $name;
            }
        }
        if (is_array($libs)) {
            ksort($libs);
        }
        return $libs;
    }

    /**
     * @param mixed $folder
     */
    public function get_libs($folder = 'vendor/')
    {
        $libs = [];
        $suffix = '/';
        foreach ($this->get_globs($folder, $suffix) as $glob) {
            foreach (glob($glob) as $path) {
                if ( ! is_dir($path)) {
                    continue;
                }
                $name = basename($path);
                $libs[$name] = $name;
            }
        }
        if (is_array($libs)) {
            ksort($libs);
        }
        return $libs;
    }

    /**
     * @param mixed $extra
     */
    public function get_classes_by_params($extra = [], &$paths = [])
    {
        $prefix = isset($extra['prefix']) ? $extra['prefix'] : YF_PREFIX;
        $suffix = isset($extra['suffix']) ? $extra['suffix'] : YF_CLS_EXT;
        $folder = isset($extra['folder']) ? $extra['folder'] : $this->section_paths['core'];

        $prefix_len = strlen($prefix);
        $suffix_len = strlen($suffix);
        $classes = [];
        foreach ($this->get_globs($folder, $suffix) as $glob) {
            foreach (glob($glob) as $path) {
                $name = substr(basename($path), 0, -$suffix_len);
                if (substr($name, 0, $prefix_len) == $prefix) {
                    $name = substr($name, $prefix_len);
                }
                $classes[$name] = $name;
                $paths[$name][$path] = $path;
            }
        }
        if (is_array($classes)) {
            ksort($classes);
        }
        return $classes;
    }

    /**
     * @param mixed $folder
     * @param mixed $suffix
     */
    public function get_globs($folder, $suffix = '')
    {
        $suffix = $suffix ?: YF_CLS_EXT;

        $globs = [];
        if ( ! $this->SOURCE_ONLY_FRAMEWORK) {
            $globs['app'] = APP_PATH . $folder . '*' . $suffix;
            $globs['app_plugins'] = APP_PATH . 'plugins/*/' . $folder . '*' . $suffix;
            $globs['project'] = PROJECT_PATH . $folder . '*' . $suffix;
            $globs['project_plugins'] = PROJECT_PATH . 'plugins/*/' . $folder . '*' . $suffix;
        }
        $globs['framework'] = YF_PATH . $folder . '*' . $suffix;
        $globs['framework_plugins'] = YF_PATH . 'plugins/*/' . $folder . '*' . $suffix;
        return $globs;
    }

    /***/
    public function get_file_slice($file, $line_start, $line_end)
    {
        $source = $this->_cache[__FUNCTION__][$file] ?? null;
        if ($source === null) {
            $source = file($file);
            $this->_cache[__FUNCTION__][$file] = $source;
        }
        $offset = $line_end - $line_start;
        return implode(array_slice($source, $line_start - 1, $offset + 1));
    }

    /***/
    public function get_methods_sources($cls)
    {
        if ( ! $cls) {
            return false;
        }
        if (is_object($cls)) {
            $cls = get_class($cls);
        }
        $data = [];
        $class = new ReflectionClass($cls);
        foreach ($class->getMethods() as $v) {
            $name = $v->name;
            $r = new ReflectionMethod($cls, $name);
            $info = [
                'name' => $name,
                'file' => $r->getFileName(),
                'line_start' => $r->getStartLine(),
                'line_end' => $r->getEndLine(),
                'params' => $r->getParameters(),
                'comment' => $r->getDocComment(),
            ];
            $info['source'] = $this->get_file_slice($info['file'], $info['line_start'], $info['line_end']);
            $data[$name] = $info;
        }
        return $data;
    }


    public function add_syntax_highlighter()
    {
        asset('highlightjs');
    }


    public function show_docs(array $info)
    {
        $tests = '';
        if ($info['is_func']) {
            $tests = _class('core_api')->get_function_tests($info['name']);
        } elseif ($info['is_module']) {
            list($module, $method) = explode('-', $info['is_module']);
            $tests = _class('core_api')->get_module_tests($module);
        }
        $docs = '';
        if ($info['is_func']) {
            $docs = _class('core_api')->get_function_docs($info['name']);
        } elseif ($info['is_module']) {
            list($module, $method) = explode('-', $info['is_module']);
            $docs = _class('core_api')->get_method_docs($module, $method);
            if ( ! $docs) {
                $docs = _class('core_api')->get_module_docs($module);
            }
        }
        return '
			<h3>' . $info['name'] . '</h3>
			<h4>' . $info['file'] . ':' . $info['line_start'] . ' ' . _class('core_api')->get_github_link($info) . '</h4>
			<section class="page-contents">
				<pre><code>' . ($info['comment'] ? _prepare_html($info['comment'], $strip = false) . PHP_EOL : '') . _prepare_html($info['source'], $strip = false) . '</code></pre>
				' . ($tests ? '<h4>Unit tests</h4><pre><code>' . _prepare_html($tests, $strip = false) . '</code></pre>' : '') . '
				' . ($docs ? '<h4>Documentation</h4>' . nl2br($docs) : '') . '
			</section>
		';
    }
}
