<?php

/**
 * Abstraction layer over YF services.
 */
class yf_services
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
     * We cleanup object properties when cloning.
     */
    public function __clone()
    {
        foreach ((array) get_object_vars($this) as $k => $v) {
            $this->$k = null;
        }
    }

    /**
     * Need to avoid calling render() without params.
     */
    public function __toString()
    {
        try {
            return (string) $this->render();
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * @param mixed $name
     * @param mixed $params
     */
    public function require_php_lib($name, $params = [])
    {
        if (isset($this->php_libs[$name])) {
            return $this->php_libs[$name];
        }
        if ( ! isset($this->_paths_cache)) {
            $suffix = '.php';
            $patterns = [
                'framework' => [
                    YF_PATH . 'services/*' . $suffix,
                    YF_PATH . 'services/*/*' . $suffix,
                    YF_PATH . 'share/services/*' . $suffix,
                    YF_PATH . 'share/services/*/*' . $suffix,
                    YF_PATH . 'plugins/*/services/*' . $suffix,
                    YF_PATH . 'plugins/*/services/*/*' . $suffix,
                    YF_PATH . 'plugins/*/share/services/*' . $suffix,
                    YF_PATH . 'plugins/*/share/services/*/*' . $suffix,
                ],
                'app' => [
                    APP_PATH . 'services/*' . $suffix,
                    APP_PATH . 'services/*/*' . $suffix,
                    APP_PATH . 'share/services/*' . $suffix,
                    APP_PATH . 'share/services/*/*' . $suffix,
                    APP_PATH . 'plugins/*/services/*' . $suffix,
                    APP_PATH . 'plugins/*/services/*/*' . $suffix,
                    APP_PATH . 'plugins/*/share/services/*' . $suffix,
                    APP_PATH . 'plugins/*/share/services/*/*' . $suffix,
                ],
            ];

            $slen = strlen($suffix);
            $paths = [];
            foreach ($patterns as $gname => $pathsList) {
                foreach ($pathsList as $glob) {
                    foreach (glob($glob) as $_path) {
                        $_name = substr(basename($_path), 0, -$slen);
                        if ($_name == '_yf_autoloader') {
                            continue;
                        }
                        $paths[$_name] = $_path;
                    }
                }
            }

            // Ensures services with the same name can be overridden inside the project
            foreach ($paths as $_name => $_path) {
                $this->_paths_cache[$_name] = $_path;
            }
        }
        $path = $this->_paths_cache[$name];
        if ( ! $path || ! file_exists($path)) {
            throw new Exception('services ' . __FUNCTION__ . ' "' . $name . '" not found');
            return false;
        }
        ob_start();
        require_once $path;
        $this->php_libs[$name] = $path;
        return ob_get_clean();
    }

    /**
     * phpmailer fresh instance, intended to use its helper methods.
     * @param mixed $content
     * @param mixed $params
     */
    public function phpmailer($content, $params = [])
    {
        $this->require_php_lib('phpmailer');
        return new PHPMailer(true);
    }

    /**
     * Process and output JADE content.
     * @param mixed $content
     * @param mixed $params
     */
    public function jade($content, $params = [])
    {
        $this->require_php_lib('jade');
        $dumper = new \Everzet\Jade\Dumper\PHPDumper();
        $parser = new \Everzet\Jade\Parser(new \Everzet\Jade\Lexer\Lexer());
        $jade = new \Everzet\Jade\Jade($parser, $dumper);
        return $jade->render($content);
    }

    /**
     * @param mixed $content
     * @param mixed $params
     */
    public function sass($content, $params = [])
    {
        $this->require_php_lib('scss');
        $scss = new scssc();
        return $scss->compile($content);
    }

    /**
     * @param mixed $content
     * @param mixed $params
     */
    public function less($content, $params = [])
    {
        $this->require_php_lib('less');
        $less = new \lessc();
        return $less->compile($content);
    }

    /**
     * @param mixed $content
     * @param mixed $params
     */
    public function coffee($content, $params = [])
    {
        $this->require_php_lib('coffeescript');
        return \CoffeeScript\Compiler::compile($content, ['header' => false]);
    }

    /**
     * Process and output HAML content.
     * @param mixed $content
     * @param mixed $params
     */
    public function haml($content, $params = [])
    {
        $this->require_php_lib('mthaml');
        $haml = new MtHaml\Environment('php');
        $executor = new MtHaml\Support\Php\Executor($haml, [
            'cache' => sys_get_temp_dir() . '/haml',
        ]);
        $path = tempnam(sys_get_temp_dir(), 'haml');
        file_put_contents($path, $content);
        return $executor->render($path, $params);
    }

    /**
     * @param mixed $content
     * @param mixed $params
     */
    public function markdown($content, $params = [])
    {
        $this->require_php_lib('parsedown');
        $parsedown = new Parsedown();
        return $parsedown->text($input);
    }

    /**
     * @param mixed $in
     */
    public function base58_encode($in)
    {
        $this->require_php_lib('base58');
        $base58 = new StephenHill\Base58();
        return $base58->encode($in);
    }

    /**
     * @param mixed $in
     */
    public function base58_decode($in)
    {
        $this->require_php_lib('base58');
        $base58 = new StephenHill\Base58();
        return $base58->decode($in);
    }

    /**
     * @param mixed $text
     * @param mixed $lang_from
     * @param mixed $lang_to
     * @param mixed $params
     */
    public function google_translate($text, $lang_from, $lang_to, $params = [], &$cache_used = false)
    {
        if ( ! strlen($text) || ! $lang_from || ! $lang_to) {
            return false;
        }
        $md5 = md5($lang_from . '|' . $lang_to . '|' . $text);
        $table = 'cache_google_translate';
        $cached = db()->from($table)->where('lang_from', $lang_from)->where('lang_to', $lang_to)->where('md5', $md5)->get();
        if (isset($cached['translated'])) {
            $cache_used = true;
            return $cached['translated'];
        }
        $this->require_php_lib('google_translate');
        try {
            $translated = Stichoza\GoogleTranslate\TranslateClient::translate($lang_from, $lang_to, $text);
        } catch (Exception $e) {
            echo 'Error: exception caught: ' . $e->getMessage() . PHP_EOL;
        }
        db()->insert_safe($table, [
                'md5' => $md5,
                'lang_from' => $lang_from,
                'lang_to' => $lang_to,
                'source' => $text,
                'translated' => $translated,
                'date' => date('Y-m-d H:i:s'),
            ]);

        return $translated;
    }
}
