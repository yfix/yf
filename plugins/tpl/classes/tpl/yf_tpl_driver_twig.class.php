<?php

/**
 * Note: currently disabled, use this console command to add it back again:
 * git submodule add https://github.com/yfix/twig.git libs/Twig/.
 */
#[AllowDynamicProperties]
class yf_tpl_driver_twig
{
    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $arguments
     */
    public function __call($name, $arguments)
    {
        trigger_error(__CLASS__ . ': No method ' . $name, E_USER_WARNING);
        return false;
    }


    public function _init()
    {
        require_php_lib('twig');

        // path
        $paths = $this->_paths();
        $loader = new \Twig\Loader\FilesystemLoader($paths, APP_PATH);
        // env
        if( is_dev() || is_debug() ) {
            $debug = true;
            $cache = false;
            $auto_reload = true;
        } else {
            $debug = false;
            $cache = STORAGE_PATH .'twig_cache/';
            $auto_reload = false;
        }
        $env = [
            'debug'       => $debug,
            'cache'       => $cache,
            'auto_reload' => $auto_reload,
            // 'autoescape'  => false, // name, html, js, css, url, html_attr, ...
        ];
        $this->env = new \Twig\Environment($loader, $env);
        $this->env->addExtension( new \Twig\Extension\StringLoaderExtension() );
        if( is_dev() || is_debug() ) {
            $this->env->addExtension( new \Twig\Extension\DebugExtension() );
        }
        // exec
        $exec = new \Twig\TwigFunction( 'exec',
            function( \Twig\Environment $env, $context, array $vars = [] ) {
                if( empty( $vars ) || !is_array( $vars ) ) { return; }
                $class   = array_shift( $vars );
                $_method = array_shift( $vars );
                $_class = module_safe( $class );
                $_status = method_exists( $_class, $_method );
                if ( ! $_status) {
                    $_class = _class_safe( $class );
                    $_status = method_exists( $_class, $_method );
                    if ( ! $_status) { return; }
                }
                return $_class->$_method(...$vars);
            },
            [
                'is_safe'           => ['html'],
                'needs_context'     => true,
                'needs_environment' => true,
                'is_variadic'       => true,
            ]
        );
        $this->env->addFunction( $exec );
    }

    public function _paths() {
        $paths = [
            '.',
            'plugins',
        ];
        // theme, user/admin
        $theme = tpl()->_THEMES_PATH;
        $theme = trim($theme, '/');
        $type = main()->type == 'admin' ? 'admin' : tpl()->_get_def_user_theme();
        // object
        $object = @$_GET['object'];
        if( $object ) {
            $p = [ 'plugins', $object, ];
            $paths[] = $p;
            if( !empty( $theme ) ) {
                $p[] = $theme;
                $paths[] = $p;
            }
            if ( ! empty($type)) {
                $p[] = $type;
                $paths[] = $p;
                $p[] = $object;
                $paths[] = $p;
            }
            $action = @$_GET['action'];
            if( $action ) {
                $p[] = $action;
                $paths[] = $p;
            }
        }
        // test
        $r = [];
        foreach( $paths as $p ) {
            !is_array( $p ) && $p = [ (string)$p ];
            $p = implode(DIRECTORY_SEPARATOR, $p) . DIRECTORY_SEPARATOR;
            is_dir( APP_PATH . $p ) && $r[] = $p;
            if( SITE_PATH != APP_PATH ) {
                is_dir( SITE_PATH . $p ) && $r[] = SITE_PATH . $p;
            }
        }
        $all_tpls_paths = tpl()->_get_cached_paths();
        $paths = [];
        foreach ($all_tpls_paths as $name => $storage) {
            foreach ($storage as $s_type => $f_type) {
                if(!in_array($s_type, ['app', 'site'])) { continue; }
                if (@$f_type[$type]) {
                    $d = &$f_type[$type];
                    $c = substr_count($name, DIRECTORY_SEPARATOR);
                    for( $i = 0; $i < $c + 1; $i++ ) {
                        $d = dirname( $d ) . DIRECTORY_SEPARATOR;
                        $paths[$d] = true;
                    }
                }
            }
        }
        foreach ($paths as $p => $_) {
            $r[] = $p;
        }
        $r = array_reverse($r);
        return  $r;
    }

    /**
     * @param mixed $name
     * @param mixed $replace
     * @param mixed $params
     */
    public function parse($name, $replace = [], $params = [])
    {
        if (@$params['no_cache']) {
            $this->env->enableAutoReload();
            $this->env->setCache(false);
        }
        if (@$params['string']) {
            $s = $params['string'];
        } else {
            $s = tpl()->get($name);
        }
        // $t = $this->env->load($name .'.tpl');
        // $t = twig_template_from_string($this->env, $s);
        $t = $this->env->createTemplate( $s, $name );
        return $t->render($replace);
    }

}
